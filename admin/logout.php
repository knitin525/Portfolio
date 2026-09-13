<?php
/**
 * Knitin Portfolio — Admin Logout
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

Auth::logout();
redirect('login.php');
