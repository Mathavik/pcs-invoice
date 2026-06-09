<?php
require_once 'session.php';
ensureSessionStarted();
require_once 'Invoice.php';
require_once 'invoice_pdf_generator.php';

$invoice = new Invoice();
$invoice->checkLoggedIn();

$invoiceId = intval($_GET['invoice_id'] ?? 0);
if ($invoiceId <= 0) {
    http_response_code(400);
    echo 'Invalid invoice ID.';
    exit;
}

$pdfBytes = generateInvoicePdfBytes($invoiceId);
if ($pdfBytes === '') {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="Invoice-' . $invoiceId . '.pdf"');
header('Content-Length: ' . strlen($pdfBytes));
echo $pdfBytes;
exit;