<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function sendJsonAndExit(int $status, string $message): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit;
}

set_error_handler(function ($severity, $errorMessage) {
    throw new ErrorException($errorMessage, 0, $severity);
});

set_exception_handler(function (Throwable $exception) {
    sendJsonAndExit(0, $exception->getMessage());
});

register_shutdown_function(function () {
    $fatal = error_get_last();
    if ($fatal && in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        sendJsonAndExit(0, 'Server error while sending invoice email.');
    }
});

require_once 'session.php';
ensureSessionStarted();
require_once 'Invoice.php';
require_once 'mail_config.php';
require_once 'invoice_pdf_generator.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$invoice = new Invoice();

if (isset($_POST['action']) && $_POST['action'] === 'send_invoice_email') {
    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    $toEmail = trim($_POST['to_email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Invoice PDF');
    $message = trim($_POST['message'] ?? 'Please find attached invoice PDF.');

    if ($invoiceId <= 0) {
        sendJsonAndExit(0, 'Invalid invoice ID.');
    }
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        sendJsonAndExit(0, 'Invalid recipient email address.');
    }
    if ($subject === '') {
        sendJsonAndExit(0, 'Email subject cannot be empty.');
    }
    if ($message === '') {
        sendJsonAndExit(0, 'Email message cannot be empty.');
    }

    $smtpPassword = preg_replace('/\s+/', '', (string) SMTP_PASSWORD);
    if ($smtpPassword === '') {
        sendJsonAndExit(0, 'SMTP password is not configured.');
    }

    $invoiceValues = $invoice->getInvoice($invoiceId);
    if (empty($invoiceValues)) {
        sendJsonAndExit(0, 'Invoice not found.');
    }

    $pdfBytes = generateInvoicePdfBytes($invoiceId);
    if ($pdfBytes === '') {
        sendJsonAndExit(0, 'Failed to generate invoice PDF.');
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAutoTLS = true;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $mail->AltBody = $message;
        $mail->addStringAttachment($pdfBytes, 'Invoice-' . $invoiceId . '.pdf', 'base64', 'application/pdf');
        $mail->send();

        sendJsonAndExit(1, 'Invoice email sent successfully');
    } catch (Exception $e) {
        $errorMessage = isset($mail) && $mail->ErrorInfo ? $mail->ErrorInfo : $e->getMessage();
        sendJsonAndExit(0, $errorMessage);
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'delete_invoice' && isset($_POST['id'])) {
    $result = $invoice->deleteInvoice($_POST['id']);
    sendJsonAndExit($result ? 1 : 0, $result ? 'Invoice deleted successfully.' : 'Failed to delete invoice.');
}

if (isset($_POST['action']) && $_POST['action'] == 'logout') {
	session_unset();
	session_destroy();
    sendJsonAndExit(1, 'Logged out successfully.');
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
	session_unset();
	session_destroy();
    sendJsonAndExit(1, 'Logged out successfully.');
}

sendJsonAndExit(0, 'Invalid request.');