<?php
// SMTP configuration for PHPMailer email sending.
// Use environment variables for secure values, especially the Gmail App Password.

define('SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', intval(getenv('MAIL_SMTP_PORT') ?: 587));
define('SMTP_SECURE', getenv('MAIL_SMTP_SECURE') ?: 'tls');
define('SMTP_USER', getenv('MAIL_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'PCS Invoice');
$smtpPassword = getenv('MAIL_SMTP_PASSWORD') ?: '';
define('SMTP_PASSWORD', preg_replace('/\s+/', '', (string) $smtpPassword));
