<?php
/**
 * Knitin Portfolio — Pure PHP IMAP Client
 * Zero-dependency email retrieval client supporting SSL/TLS socket connections and MIME parsing.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class ImapClient {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $mailbox;
    private int $timeout = 20;
    private $socket = null;
    private int $tagIndex = 0;

    public function __construct(?array $config = null) {
        $this->host = $config['host'] ?? (string)env('IMAP_HOST', 'imap.hostinger.com');
        $this->port = (int)($config['port'] ?? env('IMAP_PORT', 993));
        $this->username = $config['user'] ?? (string)env('IMAP_USER', '');
        $this->password = $config['pass'] ?? (string)env('IMAP_PASS', '');
        $this->mailbox = $config['mailbox'] ?? (string)env('IMAP_MAILBOX', 'INBOX');
    }

    /**
     * Test connection and authentication.
     */
    public function testConnection(): array {
        if (empty($this->username) || empty($this->password)) {
            return ['success' => false, 'message' => 'IMAP Username or Password not configured in .env.'];
        }

        try {
            $this->connect();
            $this->disconnect();
            return ['success' => true, 'message' => "Successfully connected to {$this->host}:{$this->port} and authenticated!"];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'IMAP Connection Test Failed: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch unread/new messages.
     *
     * @param int $limit Max messages to fetch per run
     * @return array List of parsed message arrays
     */
    public function fetchUnread(int $limit = 25): array {
        if (empty($this->username) || empty($this->password) || $this->password === 'your_email_password_here') {
            return [];
        }

        $messages = [];

        // Check if native imap_* extension is available
        if (function_exists('imap_open')) {
            try {
                return $this->fetchWithNativeImap($limit);
            } catch (Throwable $e) {
                error_log("Native IMAP failed, attempting pure-socket fallback: " . $e->getMessage());
            }
        }

        try {
            $this->connect();
            $this->sendCommand("SELECT \"{$this->mailbox}\"");

            // Search for unread messages
            $searchResp = $this->sendCommand("SEARCH UNSEEN");
            $uids = [];
            foreach ($searchResp as $line) {
                if (str_starts_with($line, '* SEARCH')) {
                    $parts = explode(' ', trim(substr($line, 8)));
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if (is_numeric($p)) $uids[] = (int)$p;
                    }
                }
            }

            if (empty($uids)) {
                $this->disconnect();
                return [];
            }

            // Limit batch
            $uids = array_slice($uids, 0, $limit);

            foreach ($uids as $seq) {
                // Fetch full raw message
                $rawMsg = $this->fetchRawMessage($seq);
                if (!empty($rawMsg)) {
                    $parsed = $this->parseRawEmail($rawMsg);
                    $parsed['uid'] = (string)$seq;
                    $messages[] = $parsed;
                }
            }

            $this->disconnect();
        } catch (Throwable $e) {
            error_log("IMAP Socket Fetch Error: " . $e->getMessage());
            if ($this->socket) {
                @fclose($this->socket);
                $this->socket = null;
            }
        }

        return $messages;
    }

    private function connect(): void {
        $remote = "ssl://{$this->host}:{$this->port}";
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $this->socket = @stream_socket_client($remote, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$this->socket) {
            throw new RuntimeException("Could not connect to IMAP server {$this->host}:{$this->port} ({$errno}: {$errstr})");
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Read initial server greeting
        $greeting = fgets($this->socket, 1024);
        if (!$greeting || !str_starts_with($greeting, '* OK')) {
            throw new RuntimeException("Invalid IMAP greeting: {$greeting}");
        }

        // Login
        $loginRes = $this->sendCommand("LOGIN \"{$this->username}\" \"{$this->password}\"");
        $lastLine = end($loginRes);
        if (!str_contains($lastLine, ' OK ')) {
            throw new RuntimeException("IMAP authentication failed for {$this->username}");
        }
    }

    private function disconnect(): void {
        if ($this->socket) {
            try {
                $this->sendCommand("LOGOUT");
            } catch (Throwable $e) {}
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    private function sendCommand(string $cmd): array {
        $this->tagIndex++;
        $tag = 'TAG' . $this->tagIndex;
        fputs($this->socket, "{$tag} {$cmd}\r\n");

        $lines = [];
        while ($line = fgets($this->socket, 4096)) {
            $lines[] = rtrim($line, "\r\n");
            if (str_starts_with($line, $tag . ' ')) {
                break;
            }
        }
        return $lines;
    }

    private function fetchRawMessage(int $seq): string {
        $this->tagIndex++;
        $tag = 'TAG' . $this->tagIndex;
        fputs($this->socket, "{$tag} FETCH {$seq} (BODY.PEEK[])\r\n");

        $raw = '';
        $inBody = false;
        $bytesLeft = 0;

        while ($line = fgets($this->socket, 4096)) {
            if (!$inBody && preg_match('/BODY\[\]\s*\{(\d+)\}/', $line, $m)) {
                $inBody = true;
                $bytesLeft = (int)$m[1];
                continue;
            }
            if ($inBody) {
                $raw .= $line;
                $bytesLeft -= strlen($line);
                if ($bytesLeft <= 0) {
                    break;
                }
            }
        }

        // Read till tag completion
        while ($line = fgets($this->socket, 4096)) {
            if (str_starts_with($line, $tag . ' ')) break;
        }

        return $raw;
    }

    /**
     * Fallback to native PHP imap_* if available on Hostinger.
     */
    private function fetchWithNativeImap(int $limit): array {
        $mailboxStr = "{" . $this->host . ":" . $this->port . "/imap/ssl/novalidate-cert}" . $this->mailbox;
        $inbox = @imap_open($mailboxStr, $this->username, $this->password);
        if (!$inbox) {
            throw new RuntimeException("imap_open failed: " . imap_last_error());
        }

        $emails = @imap_search($inbox, 'UNSEEN');
        if (!$emails) {
            imap_close($inbox);
            return [];
        }

        $messages = [];
        $count = 0;
        rsort($emails);

        foreach ($emails as $emailNumber) {
            if ($count >= $limit) break;

            $header = imap_headerinfo($inbox, $emailNumber);
            $structure = imap_fetchstructure($inbox, $emailNumber);

            $fromEmail = $header->from[0]->mailbox . "@" . $header->from[0]->host;
            $fromName = isset($header->from[0]->personal) ? $this->decodeMimeStr($header->from[0]->personal) : '';
            $subject = isset($header->subject) ? $this->decodeMimeStr($header->subject) : '(No Subject)';
            $messageId = isset($header->message_id) ? trim($header->message_id) : ('<' . md5($emailNumber . time()) . '@knitin525.in>');
            $inReplyTo = isset($header->in_reply_to) ? trim($header->in_reply_to) : null;
            $references = isset($header->references) ? trim($header->references) : null;
            $date = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : date('Y-m-d H:i:s');

            // Extract body & attachments
            $bodyPlain = '';
            $bodyHtml = '';
            $attachments = [];

            $this->extractNativeParts($inbox, $emailNumber, $structure, "", $bodyPlain, $bodyHtml, $attachments);

            $messages[] = [
                'uid' => (string)$emailNumber,
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo,
                'references' => $references,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'to_email' => $this->username,
                'subject' => $subject,
                'text' => $bodyPlain,
                'html' => $bodyHtml,
                'date' => $date,
                'attachments' => $attachments,
            ];

            $count++;
        }

        imap_close($inbox);
        return $messages;
    }

    private function extractNativeParts($inbox, $msgNo, $structure, $prefix, &$plain, &$html, &$attachments): void {
        if (!isset($structure->parts)) {
            $data = imap_body($inbox, $msgNo);
            if ($structure->encoding == 3) $data = base64_decode($data);
            elseif ($structure->encoding == 4) $data = quoted_printable_decode($data);
            if ($structure->subtype == 'HTML') $html = $data;
            else $plain = $data;
            return;
        }

        foreach ($structure->parts as $index => $subPart) {
            $partNum = $prefix ? ($prefix . "." . ($index + 1)) : (string)($index + 1);

            // Check if attachment
            $filename = '';
            if ($subPart->ifdparameters) {
                foreach ($subPart->dparameters as $param) {
                    if (strtolower($param->attribute) == 'filename') $filename = $param->value;
                }
            }
            if (!$filename && $subPart->ifparameters) {
                foreach ($subPart->parameters as $param) {
                    if (strtolower($param->attribute) == 'name') $filename = $param->value;
                }
            }

            if ($filename) {
                $content = imap_fetchbody($inbox, $msgNo, $partNum);
                if ($subPart->encoding == 3) $content = base64_decode($content);
                elseif ($subPart->encoding == 4) $content = quoted_printable_decode($content);

                $saved = $this->saveAttachmentData($this->decodeMimeStr($filename), $content);
                if ($saved) $attachments[] = $saved;
            } elseif ($subPart->type == 0) { // Text
                $textData = imap_fetchbody($inbox, $msgNo, $partNum);
                if ($subPart->encoding == 3) $textData = base64_decode($textData);
                elseif ($subPart->encoding == 4) $textData = quoted_printable_decode($textData);

                if (strtoupper($subPart->subtype) == 'HTML') {
                    $html = $textData;
                } else {
                    $plain = $textData;
                }
            } elseif (isset($subPart->parts)) {
                $this->extractNativeParts($inbox, $msgNo, $subPart, $partNum, $plain, $html, $attachments);
            }
        }
    }

    /**
     * Parse raw RFC 822 email text into structured data.
     */
    public function parseRawEmail(string $raw): array {
        [$headersRaw, $bodyRaw] = explode("\r\n\r\n", $raw, 2) + ['', ''];
        $headers = $this->parseHeaders($headersRaw);

        $from = $this->parseEmailAddress($headers['from'] ?? '');
        $subject = $this->decodeMimeStr($headers['subject'] ?? '(No Subject)');
        $messageId = trim($headers['message-id'] ?? ('<' . md5($raw) . '@knitin525.in>'));
        $inReplyTo = isset($headers['in-reply-to']) ? trim($headers['in-reply-to']) : null;
        $references = isset($headers['references']) ? trim($headers['references']) : null;
        $date = isset($headers['date']) ? date('Y-m-d H:i:s', strtotime($headers['date'])) : date('Y-m-d H:i:s');

        $contentType = $headers['content-type'] ?? 'text/plain';
        $bodyPlain = '';
        $bodyHtml = '';
        $attachments = [];

        if (stripos($contentType, 'multipart/') !== false && preg_match('/boundary="?([^";]+)"?/i', $contentType, $bm)) {
            $boundary = $bm[1];
            $parts = explode("--{$boundary}", $bodyRaw);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '' || $part === '--') continue;
                [$partHeadersRaw, $partBody] = explode("\r\n\r\n", $part, 2) + ['', ''];
                $partHeaders = $this->parseHeaders($partHeadersRaw);
                $pType = $partHeaders['content-type'] ?? 'text/plain';
                $pEncoding = strtolower($partHeaders['content-transfer-encoding'] ?? '');

                // Decode body
                if ($pEncoding === 'base64') {
                    $partBody = base64_decode(str_replace(["\r", "\n"], '', $partBody));
                } elseif ($pEncoding === 'quoted-printable') {
                    $partBody = quoted_printable_decode($partBody);
                }

                // Check attachment
                $disposition = $partHeaders['content-disposition'] ?? '';
                if (stripos($disposition, 'attachment') !== false || preg_match('/filename="?([^";]+)"?/i', $pType . ';' . $disposition, $fm)) {
                    $filename = $fm[1] ?? 'attachment_' . time();
                    $saved = $this->saveAttachmentData($this->decodeMimeStr($filename), $partBody);
                    if ($saved) $attachments[] = $saved;
                } elseif (stripos($pType, 'text/html') !== false) {
                    $bodyHtml = $partBody;
                } elseif (stripos($pType, 'text/plain') !== false) {
                    $bodyPlain = $partBody;
                }
            }
        } else {
            $encoding = strtolower($headers['content-transfer-encoding'] ?? '');
            if ($encoding === 'base64') {
                $bodyRaw = base64_decode(str_replace(["\r", "\n"], '', $bodyRaw));
            } elseif ($encoding === 'quoted-printable') {
                $bodyRaw = quoted_printable_decode($bodyRaw);
            }
            if (stripos($contentType, 'text/html') !== false) {
                $bodyHtml = $bodyRaw;
            } else {
                $bodyPlain = $bodyRaw;
            }
        }

        return [
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'references' => $references,
            'from_email' => $from['email'],
            'from_name' => $from['name'],
            'to_email' => $this->username,
            'subject' => $subject,
            'text' => $bodyPlain,
            'html' => $bodyHtml,
            'date' => $date,
            'attachments' => $attachments,
        ];
    }

    private function parseHeaders(string $raw): array {
        $headers = [];
        $lines = explode("\r\n", $raw);
        $currentKey = '';

        foreach ($lines as $line) {
            if (isset($line[0]) && ($line[0] === ' ' || $line[0] === "\t")) {
                if ($currentKey) {
                    $headers[$currentKey] .= ' ' . trim($line);
                }
            } elseif (strpos($line, ':') !== false) {
                [$key, $val] = explode(':', $line, 2);
                $currentKey = strtolower(trim($key));
                $headers[$currentKey] = trim($val);
            }
        }
        return $headers;
    }

    private function parseEmailAddress(string $raw): array {
        $name = '';
        $email = '';
        if (preg_match('/^(.*?)\s*<([^>]+)>$/', $raw, $m)) {
            $name = trim($m[1], " \"'");
            $email = trim($m[2]);
        } else {
            $email = trim($raw, " <>");
        }
        return [
            'name' => $this->decodeMimeStr($name),
            'email' => filter_var($email, FILTER_SANITIZE_EMAIL) ?: 'unknown@domain.com',
        ];
    }

    private function decodeMimeStr(string $str): string {
        $elements = imap_mime_header_decode($str);
        if (!$elements) return $str;
        $decoded = '';
        foreach ($elements as $el) {
            $charset = strtoupper($el->charset);
            $text = $el->text;
            if ($charset !== 'DEFAULT' && $charset !== 'UTF-8' && function_exists('mb_convert_encoding')) {
                $text = @mb_convert_encoding($text, 'UTF-8', $charset) ?: $text;
            }
            $decoded .= $text;
        }
        return $decoded;
    }

    private function saveAttachmentData(string $filename, string $data): ?array {
        $cleanName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $ext = strtolower(pathinfo($cleanName, PATHINFO_EXTENSION));

        // Block dangerous file extensions
        $blockedExts = ['php', 'phtml', 'exe', 'bat', 'sh', 'cmd', 'js', 'vbs', 'scr'];
        if (in_array($ext, $blockedExts, true)) {
            return null;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetDir = UPLOADS_PATH;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $storedName;
        if (file_put_contents($targetPath, $data) !== false) {
            return [
                'original_name' => $cleanName,
                'stored_name' => $storedName,
                'file_path' => 'uploads/' . $storedName,
                'mime_type' => mime_content_type($targetPath) ?: 'application/octet-stream',
                'file_size' => strlen($data),
            ];
        }
        return null;
    }
}
