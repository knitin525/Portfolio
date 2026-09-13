<?php
/**
 * Knitin Portfolio — Pure PHP SMTP Mail Sender
 * Zero-dependency client supporting SSL/TLS, attachments, and email threading headers.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class MailSender {
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private int $timeout = 15;

    public function __construct(?array $config = null) {
        $this->host = $config['host'] ?? (string)env('SMTP_HOST', 'smtp.hostinger.com');
        $this->port = (int)($config['port'] ?? env('SMTP_PORT', 465));
        $this->encryption = strtolower((string)($config['encryption'] ?? env('SMTP_ENCRYPTION', 'ssl')));
        $this->username = $config['user'] ?? (string)env('SMTP_USER', '');
        $this->password = $config['pass'] ?? (string)env('SMTP_PASS', '');
        $this->fromEmail = $config['from_email'] ?? (string)env('SMTP_USER', 'contact@knitin525.in');
        $this->fromName = $config['from_name'] ?? (string)env('SMTP_FROM_NAME', 'Nitin Kumar');
    }

    /**
     * Send an email.
     *
     * @param array $options [
     *   'to' => 'recipient@example.com',
     *   'to_name' => 'Recipient Name',
     *   'subject' => 'Subject',
     *   'text' => 'Plain text message',
     *   'html' => '<p>HTML message</p>',
     *   'reply_to' => 'email',
     *   'reply_to_name' => 'Name',
     *   'cc' => ['email@example.com'],
     *   'bcc' => ['email@example.com'],
     *   'in_reply_to' => '<message-id>',
     *   'references' => '<message-id>',
     *   'attachments' => [ ['path' => '...', 'name' => '...'] ],
     *   'message_id' => '<custom-id>'
     * ]
     * @return array ['success' => bool, 'message' => string, 'message_id' => string]
     */
    public function send(array $options): array {
        $to = filter_var($options['to'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$to) {
            return ['success' => false, 'message' => 'Invalid recipient email address.'];
        }

        $subject = $options['subject'] ?? '(No Subject)';
        $textBody = $options['text'] ?? strip_tags($options['html'] ?? '');
        $htmlBody = $options['html'] ?? nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8'));
        $attachments = $options['attachments'] ?? [];

        // Generate Message-ID
        $domain = substr(strrchr($this->fromEmail, "@"), 1) ?: 'knitin525.in';
        $messageId = $options['message_id'] ?? ('<' . bin2hex(random_bytes(16)) . '.' . time() . '@' . $domain . '>');

        // If credentials are blank, try PHP mail() fallback
        if (empty($this->username) || empty($this->password) || $this->password === 'your_email_password_here') {
            return $this->fallbackMail($to, $subject, $textBody, $htmlBody, $messageId, $options);
        }

        try {
            $socket = $this->connect();
            $this->authenticate($socket);

            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>", 250);

            // RCPT TO
            $recipients = [$to];
            if (!empty($options['cc'])) {
                $ccs = is_array($options['cc']) ? $options['cc'] : explode(',', $options['cc']);
                foreach ($ccs as $c) {
                    $c = trim($c);
                    if (filter_var($c, FILTER_VALIDATE_EMAIL)) $recipients[] = $c;
                }
            }
            if (!empty($options['bcc'])) {
                $bccs = is_array($options['bcc']) ? $options['bcc'] : explode(',', $options['bcc']);
                foreach ($bccs as $b) {
                    $b = trim($b);
                    if (filter_var($b, FILTER_VALIDATE_EMAIL)) $recipients[] = $b;
                }
            }

            foreach ($recipients as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient}>", 250);
            }

            // DATA
            $this->sendCommand($socket, "DATA", 354);

            // Build MIME message
            $mime = $this->buildMimeMessage($to, $subject, $textBody, $htmlBody, $messageId, $options, $attachments);

            // Send payload
            fputs($socket, $mime . "\r\n.\r\n");
            $response = $this->readResponse($socket);
            if (!str_starts_with($response, '250')) {
                throw new RuntimeException("DATA command failed: {$response}");
            }

            // QUIT
            $this->sendCommand($socket, "QUIT", 221);
            fclose($socket);

            return [
                'success' => true,
                'message' => 'Email sent successfully via SMTP.',
                'message_id' => $messageId,
            ];
        } catch (Throwable $e) {
            error_log("SMTP Send Error: " . $e->getMessage());
            // Attempt PHP mail() fallback
            return $this->fallbackMail($to, $subject, $textBody, $htmlBody, $messageId, $options, $e->getMessage());
        }
    }

    /**
     * Test SMTP connection and authentication.
     */
    public function testConnection(): array {
        if (empty($this->username) || empty($this->password)) {
            return ['success' => false, 'message' => 'SMTP Username or Password not configured in .env.'];
        }

        try {
            $socket = $this->connect();
            $this->authenticate($socket);
            $this->sendCommand($socket, "QUIT", 221);
            fclose($socket);
            return ['success' => true, 'message' => "Successfully connected to {$this->host}:{$this->port} and authenticated!"];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'SMTP Connection Test Failed: ' . $e->getMessage()];
        }
    }

    private function connect() {
        $protocol = ($this->encryption === 'ssl') ? 'ssl://' : '';
        $remote = $protocol . $this->host . ':' . $this->port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $socket = @stream_socket_client($remote, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException("Could not connect to SMTP host {$this->host}:{$this->port} ({$errno}: {$errstr})");
        }

        stream_set_timeout($socket, $this->timeout);
        $greeting = $this->readResponse($socket);
        if (!str_starts_with($greeting, '220')) {
            throw new RuntimeException("Unexpected SMTP greeting: {$greeting}");
        }

        return $socket;
    }

    private function authenticate($socket): void {
        $hostname = gethostname() ?: 'localhost';
        $this->sendCommand($socket, "EHLO {$hostname}", 250);

        if ($this->encryption === 'tls') {
            $this->sendCommand($socket, "STARTTLS", 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException("Failed to establish TLS encryption with SMTP server.");
            }
            $this->sendCommand($socket, "EHLO {$hostname}", 250);
        }

        // AUTH LOGIN
        $this->sendCommand($socket, "AUTH LOGIN", 334);
        $this->sendCommand($socket, base64_encode($this->username), 334);
        $this->sendCommand($socket, base64_encode($this->password), 235);
    }

    private function sendCommand($socket, string $cmd, int $expectedCode): string {
        fputs($socket, $cmd . "\r\n");
        $response = $this->readResponse($socket);
        if (!str_starts_with($response, (string)$expectedCode)) {
            throw new RuntimeException("SMTP Command [{$cmd}] failed with response: {$response}");
        }
        return $response;
    }

    private function readResponse($socket): string {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    private function buildMimeMessage(string $to, string $subject, string $text, string $html, string $messageId, array $options, array $attachments): string {
        $fromName = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';
        $toName = !empty($options['to_name']) ? '=?UTF-8?B?' . base64_encode($options['to_name']) . '?=' : '';
        $toHeader = $toName ? "{$toName} <{$to}>" : $to;
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers = [];
        $headers[] = "Date: " . date('r');
        $headers[] = "From: {$fromName} <{$this->fromEmail}>";
        $headers[] = "To: {$toHeader}";
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "Message-ID: {$messageId}";

        if (!empty($options['reply_to'])) {
            $replyToName = !empty($options['reply_to_name']) ? '=?UTF-8?B?' . base64_encode($options['reply_to_name']) . '?= ' : '';
            $headers[] = "Reply-To: {$replyToName}<{$options['reply_to']}>";
        }

        if (!empty($options['in_reply_to'])) {
            $headers[] = "In-Reply-To: {$options['in_reply_to']}";
        }
        if (!empty($options['references'])) {
            $headers[] = "References: {$options['references']}";
        }

        $headers[] = "MIME-Version: 1.0";
        $headers[] = "X-Mailer: Knitin-Portfolio-CRM/2.0";

        // Multipart boundary
        $boundaryMixed = '==_Mixed_' . md5($messageId . 'mixed');
        $boundaryAlt = '==_Alt_' . md5($messageId . 'alt');

        if (empty($attachments)) {
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundaryAlt}\"";
            $body = "--{$boundaryAlt}\r\n" .
                    "Content-Type: text/plain; charset=UTF-8\r\n" .
                    "Content-Transfer-Encoding: base64\r\n\r\n" .
                    chunk_split(base64_encode($text)) . "\r\n" .
                    "--{$boundaryAlt}\r\n" .
                    "Content-Type: text/html; charset=UTF-8\r\n" .
                    "Content-Transfer-Encoding: base64\r\n\r\n" .
                    chunk_split(base64_encode($html)) . "\r\n" .
                    "--{$boundaryAlt}--\r\n";
        } else {
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundaryMixed}\"";
            $body = "--{$boundaryMixed}\r\n" .
                    "Content-Type: multipart/alternative; boundary=\"{$boundaryAlt}\"\r\n\r\n" .
                    "--{$boundaryAlt}\r\n" .
                    "Content-Type: text/plain; charset=UTF-8\r\n" .
                    "Content-Transfer-Encoding: base64\r\n\r\n" .
                    chunk_split(base64_encode($text)) . "\r\n" .
                    "--{$boundaryAlt}\r\n" .
                    "Content-Type: text/html; charset=UTF-8\r\n" .
                    "Content-Transfer-Encoding: base64\r\n\r\n" .
                    chunk_split(base64_encode($html)) . "\r\n" .
                    "--{$boundaryAlt}--\r\n\r\n";

            foreach ($attachments as $att) {
                if (file_exists($att['path'])) {
                    $filename = $att['name'] ?? basename($att['path']);
                    $mimeType = mime_content_type($att['path']) ?: 'application/octet-stream';
                    $content = chunk_split(base64_encode(file_get_contents($att['path'])));

                    $body .= "--{$boundaryMixed}\r\n" .
                             "Content-Type: {$mimeType}; name=\"{$filename}\"\r\n" .
                             "Content-Disposition: attachment; filename=\"{$filename}\"\r\n" .
                             "Content-Transfer-Encoding: base64\r\n\r\n" .
                             $content . "\r\n";
                }
            }

            $body .= "--{$boundaryMixed}--\r\n";
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function fallbackMail(string $to, string $subject, string $text, string $html, string $messageId, array $options, string $smtpErr = ''): array {
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        if (!empty($options['reply_to'])) {
            $headers .= "Reply-To: {$options['reply_to']}\r\n";
        }
        $headers .= "Message-ID: {$messageId}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $sent = @mail($to, $subject, $html, $headers);
        if ($sent) {
            return [
                'success' => true,
                'message' => 'Email sent via server mail() (SMTP fallback).',
                'message_id' => $messageId,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to send email. ' . ($smtpErr ? "SMTP error: {$smtpErr}" : "Configure SMTP credentials in .env"),
            'message_id' => $messageId,
        ];
    }
}
