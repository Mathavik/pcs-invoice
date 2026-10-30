<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function sendJsonResponse(array $payload): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($payload);
    exit;
}

function sendJsonAndExit(int $status, string $message): void
{
    sendJsonResponse([
        'status' => $status,
        'message' => $message
    ]);
}

function requireQuotationCsrf(): void
{
    if (!Quotation::validateCsrf($_POST['csrf_token'] ?? '')) {
        sendJsonAndExit(0, 'Your security token has expired. Please refresh the page and try again.');
    }
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
        sendJsonAndExit(0, 'Server error while processing the request.');
    }
});

require_once 'session.php';
ensureSessionStarted();

function destroyLoginSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    destroyLoginSession();
    header('Location: index.php', true, 302);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    destroyLoginSession();
    sendJsonAndExit(1, 'Logged out successfully.');
}

require_once 'Invoice.php';
require_once 'Quotation.php';
require_once 'mail_config.php';

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

    require_once 'invoice_pdf_generator.php';
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

if (isset($_POST['action']) && $_POST['action'] === 'send_quotation_email') {
    requireQuotationCsrf();
    $quotationId = intval($_POST['quotation_id'] ?? 0);
    if ($quotationId <= 0) {
        sendJsonAndExit(0, 'Invalid quotation ID.');
    }

    $quotationService = new Quotation();
    $result = $quotationService->sendQuotationEmail($quotationId, trim((string) ($_POST['to_email'] ?? '')));
    sendJsonAndExit(
        !empty($result['ok']) ? 1 : 0,
        (string) ($result['message'] ?? 'Unable to send the quotation email.')
    );
}

if (isset($_POST['action']) && $_POST['action'] === 'resend_quotation') {
    requireQuotationCsrf();
    $quotationId = intval($_POST['id'] ?? 0);
    if ($quotationId <= 0) {
        sendJsonAndExit(0, 'Invalid quotation ID.');
    }

    $quotationService = new Quotation();
    $result = $quotationService->resendQuotationEmail($quotationId);
    sendJsonAndExit(
        !empty($result['ok']) ? 1 : 0,
        (string) ($result['message'] ?? 'Unable to resend the quotation.')
    );
}

if (isset($_POST['action']) && $_POST['action'] === 'delete_quotation') {
    requireQuotationCsrf();
    $quotationId = intval($_POST['id'] ?? 0);
    if ($quotationId <= 0) {
        sendJsonAndExit(0, 'Invalid quotation ID.');
    }

    $quotation = new Quotation();
    $deleted = $quotation->deleteQuotation($quotationId);
    sendJsonAndExit(
        $deleted ? 1 : 0,
        $deleted
            ? 'Quotation deleted successfully.'
            : 'Unable to delete this quotation. It may be converted or no longer available.'
    );
}

if (isset($_POST['action']) && $_POST['action'] === 'convert_quotation') {
    // Conversion now happens on the Create Invoice page so the invoice is
    // saved only when the user submits that form.
    sendJsonAndExit(0, 'Please use the Convert to Invoice button on the quotation page.');
}

sendJsonAndExit(0, 'Invalid request.');