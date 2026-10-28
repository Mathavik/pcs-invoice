<?php
require_once __DIR__ . '/invoice_pdf_generator.php';
require_once __DIR__ . '/Quotation.php';

function quotationPdfDate($value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y', $timestamp) : '—';
}

function quotationPdfHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function buildQuotationHtml(array $quotation, array $items): string
{
    if (empty($quotation)) {
        return '';
    }

    $currency = (string) ($quotation['currency'] ?? 'INR');
    $currencySymbol = Quotation::currencySymbol($currency);
    $quotationDate = quotationPdfDate($quotation['quotation_date'] ?? '');
    $validUntil = quotationPdfDate($quotation['valid_until'] ?? '');
    $status = (string) ($quotation['status'] ?? 'Draft');
    $grandTotal = floatval($quotation['grand_total'] ?? 0);
    $amountInWords = getAmountInWords($grandTotal, $currency);

    $logoPath = __DIR__ . '/img/PCS-Logo.png';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        if ($logoData !== false) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
    }

    $customerAddress = trim((string) ($quotation['customer_address'] ?? ''));
    $customerEmail = trim((string) ($quotation['customer_email'] ?? ''));
    $customerPhone = trim((string) ($quotation['customer_phone'] ?? ''));
    $notes = trim((string) ($quotation['notes'] ?? ''));
    $terms = trim((string) ($quotation['terms_conditions'] ?? ''));
    if ($terms === '') {
        $terms = 'Thanks for doing business with us!';
    }

    $output = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quotation - ' . quotationPdfHtml($quotation['quotation_no'] ?? '') . '</title>
    <style>
        @page { margin: 22px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5px; color: #333; margin: 0; padding: 0; line-height: 1.45; }
        .header-table { width: 100%; border: none; margin-bottom: 4px; }
        .header-left { width: 70%; vertical-align: top; }
        .header-right { width: 30%; text-align: right; vertical-align: top; }
        .company-name { font-size: 17px; font-weight: bold; color: #1f4e79; margin: 0; padding: 0; }
        .company-subtitle { font-size: 10px; font-weight: bold; color: #444; margin-bottom: 4px; }
        .company-details { font-size: 8.5px; color: #555; line-height: 1.5; }
        .logo { width: 125px; }
        .divider { border-top: 2px solid #1f4e79; margin: 9px 0; height: 0; }
        .title-section { text-align: center; margin: 8px 0 12px; }
        .title-section h1 { font-size: 18px; text-transform: uppercase; margin: 0; color: #1f4e79; letter-spacing: 1.4px; }
        .title-section .subtitle { font-size: 9px; color: #666; margin-top: 3px; }
        .info-table { width: 100%; margin: 10px 0 14px; border: none; }
        .info-table td { vertical-align: top; border: none; padding: 0; }
        .info-left { width: 58%; padding-right: 15px; }
        .info-right { width: 42%; }
        .section-label { font-weight: bold; font-size: 10px; color: #1f4e79; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .customer-name { font-weight: bold; font-size: 12.5px; margin-bottom: 2px; }
        .customer-line { color: #555; font-size: 9.5px; line-height: 1.5; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 2px 0; border: none; vertical-align: top; }
        .meta-label { font-weight: bold; color: #444; width: 95px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items-table th { background-color: #1f4e79; color: #ffffff; padding: 7px 5px; text-align: center; font-weight: bold; border: 1px solid #1f4e79; font-size: 9px; }
        .items-table td { padding: 6px 5px; border: 1px solid #d9e2ee; vertical-align: top; font-size: 9.5px; }
        .items-table .description { text-align: left; }
        .items-table .item-description { color: #666; font-size: 8.5px; margin-top: 2px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .summary-table { width: 100%; margin-top: 12px; border: none; }
        .summary-table td { vertical-align: top; border: none; padding: 0; }
        .summary-left { width: 55%; padding-right: 18px; }
        .summary-right { width: 45%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 4px 5px; border: none; }
        .totals-label { text-align: right; padding-right: 12px; width: 62%; color: #444; }
        .totals-value { text-align: right; width: 38%; font-weight: bold; white-space: nowrap; }
        .grand-total-box { background-color: #1f4e79; color: #fff; }
        .grand-total-box td { padding: 7px 5px; font-size: 12px; }
        .amount-words { margin-top: 10px; font-size: 9.5px; }
        .amount-words .label { font-weight: bold; }
        .amount-words .words { font-style: italic; color: #444; }
        .notes-section { margin-top: 14px; }
        .notes-title { font-weight: bold; color: #1f4e79; text-decoration: underline; margin-bottom: 3px; }
        .notes-body { color: #444; font-size: 9.5px; }
        .footer-table { width: 100%; margin-top: 30px; border: none; }
        .footer-table td { vertical-align: bottom; border: none; }
        .auth-section { text-align: right; padding-top: 28px; }
        .computer-note { color: #888; font-size: 8px; margin-top: 24px; text-align: center; }
        .status-text { font-weight: bold; color: #1f4e79; }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td class="header-left">
            <div class="company-name">Pavitha Consultancy Services OPC Pvt Ltd.</div>
            <div class="company-subtitle">(Software Training and Development)</div>
            <div class="company-details">
                P.M.Complex, 14-1-123/7, Sankarankovil Road,<br>
                Surandai - 627859, Tenkasi(Dt).<br>
                Email: contact@pcstech.in | Web: www.pcstech.in<br>
                GSTIN: 33AALCP8510A1ZS
            </div>
        </td>
        <td class="header-right">
            <img src="' . $logoBase64 . '" class="logo" alt="PCS Logo" />
        </td>
    </tr>
</table>

<div class="divider"></div>

<div class="title-section">
    <h1>Quotation</h1>
    <div class="subtitle">Quotation No: ' . quotationPdfHtml($quotation['quotation_no'] ?? '') . '</div>
</div>

<table class="info-table">
    <tr>
        <td class="info-left">
            <div class="section-label">Customer Details</div>
            <div class="customer-name">' . quotationPdfHtml($quotation['customer_name'] ?? '') . '</div>
            <div class="customer-line">';
            if ($customerAddress !== '') {
                $output .= nl2br(quotationPdfHtml($customerAddress)) . '<br>';
            }
            if ($customerPhone !== '') {
                $output .= 'Phone: ' . quotationPdfHtml($customerPhone) . '<br>';
            }
            if ($customerEmail !== '') {
                $output .= 'Email: ' . quotationPdfHtml($customerEmail);
            }
            $output .= '
            </div>
        </td>
        <td class="info-right">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Quotation No:</td>
                    <td>' . quotationPdfHtml($quotation['quotation_no'] ?? '') . '</td>
                </tr>
                <tr>
                    <td class="meta-label">Date:</td>
                    <td>' . quotationPdfHtml($quotationDate) . '</td>
                </tr>
                <tr>
                    <td class="meta-label">Valid Until:</td>
                    <td>' . quotationPdfHtml($validUntil) . '</td>
                </tr>
                <tr>
                    <td class="meta-label">Status:</td>
                    <td class="status-text">' . quotationPdfHtml($status) . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width: 30px;">Sl.</th>
            <th>Description</th>
            <th style="width: 45px;">Qty</th>
            <th style="width: 75px;">Unit Price</th>
            <th style="width: 65px;">Discount</th>
            <th style="width: 45px;">Tax %</th>
            <th style="width: 85px;">Amount (' . quotationPdfHtml($currencySymbol) . ')</th>
        </tr>
    </thead>
    <tbody>';

    $count = 1;
    foreach ($items as $item) {
        $description = (string) ($item['product_description'] ?? '');
        $output .= '
        <tr>
            <td class="text-center">' . $count++ . '</td>
            <td class="description">
                <div class="bold">' . quotationPdfHtml($item['product_name'] ?? '') . '</div>';
        if ($description !== '') {
            $output .= '<div class="item-description">' . nl2br(quotationPdfHtml($description)) . '</div>';
        }
        $output .= '
            </td>
            <td class="text-center">' . rtrim(rtrim(number_format(floatval($item['quantity'] ?? 0), 2, '.', ''), '0'), '.') . '</td>
            <td class="text-right">' . number_format(floatval($item['unit_price'] ?? 0), 2) . '</td>
            <td class="text-right">' . number_format(floatval($item['discount'] ?? 0), 2) . '</td>
            <td class="text-center">' . number_format(floatval($item['tax_percent'] ?? 0), 2) . '%</td>
            <td class="text-right">' . number_format(floatval($item['line_total'] ?? 0), 2) . '</td>
        </tr>';
    }

    if (empty($items)) {
        $output .= '
        <tr>
            <td colspan="7" class="text-center">No items available.</td>
        </tr>';
    }

    $output .= '
    </tbody>
</table>

<table class="summary-table">
    <tr>
        <td class="summary-left">
            <div class="amount-words">
                <div class="label">Amount in Words:</div>
                <div class="words">' . quotationPdfHtml($amountInWords) . '</div>
            </div>

            <div class="notes-section">
                <div class="notes-title">Terms &amp; Conditions</div>
                <div class="notes-body">' . nl2br(quotationPdfHtml($terms)) . '</div>
            </div>';

    if ($notes !== '') {
        $output .= '
            <div class="notes-section">
                <div class="notes-title">Notes</div>
                <div class="notes-body">' . nl2br(quotationPdfHtml($notes)) . '</div>
            </div>';
    }

    $output .= '
        </td>
        <td class="summary-right">
            <table class="totals-table">
                <tr>
                    <td class="totals-label">Subtotal</td>
                    <td class="totals-value">' . quotationPdfHtml($currencySymbol) . ' ' . number_format(floatval($quotation['subtotal'] ?? 0), 2) . '</td>
                </tr>
                <tr>
                    <td class="totals-label">Total Discount</td>
                    <td class="totals-value">' . quotationPdfHtml($currencySymbol) . ' ' . number_format(floatval($quotation['total_discount'] ?? 0), 2) . '</td>
                </tr>
                <tr>
                    <td class="totals-label">Tax / GST</td>
                    <td class="totals-value">' . quotationPdfHtml($currencySymbol) . ' ' . number_format(floatval($quotation['tax_amount'] ?? 0), 2) . '</td>
                </tr>
                <tr class="grand-total-box">
                    <td class="totals-label">Grand Total</td>
                    <td class="totals-value">' . quotationPdfHtml($currencySymbol) . ' ' . number_format($grandTotal, 2) . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="footer-table">
    <tr>
        <td style="width: 60%;">
            <div style="font-size: 9px; color: #666;">
                This quotation is valid until ' . quotationPdfHtml($validUntil) . '.<br>
                Prices are subject to the terms and conditions stated above.
            </div>
        </td>
        <td style="width: 40%;" class="auth-section">
            <div style="font-weight: bold; margin-bottom: 42px;">For Pavitha Consultancy Services</div>
            <div class="bold">Authorized Signatory</div>
        </td>
    </tr>
</table>

<div class="computer-note">This is a computer generated quotation.</div>

</body>
</html>';

    return $output;
}

function generateQuotationHtml($quotationId): string
{
    $quotationId = intval($quotationId);
    if ($quotationId <= 0) {
        return '';
    }

    $quotationService = new Quotation();
    $quotation = $quotationService->getQuotation($quotationId);
    if (empty($quotation)) {
        return '';
    }

    $items = $quotationService->getQuotationItems($quotationId);
    if (!is_array($items)) {
        $items = [];
    }

    return buildQuotationHtml($quotation, $items);
}

function generateQuotationPdfBytes($quotationId): string
{
    $html = generateQuotationHtml($quotationId);
    if ($html === '') {
        return '';
    }

    $dompdf = createInvoiceDompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}
