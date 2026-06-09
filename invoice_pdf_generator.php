<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Invoice.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function getAmountInWords($number, $currency = 'INR')
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digitsLength = strlen((string) $no);
    $i = 0;
    $str = [];
    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
        50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    ];
    $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

    while ($i < $digitsLength) {
        $divider = ($i === 2) ? 10 : 100;
        $numberPart = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider === 10) ? 1 : 2;
        if ($numberPart) {
            $plural = (($counter = count($str)) && $numberPart > 9) ? 's' : '';
            $hundred = ($counter === 1 && !empty($str[0])) ? ' and ' : '';
            $str[] = ($numberPart < 21)
                ? $words[$numberPart] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
                : $words[floor($numberPart / 10) * 10] . ' ' . $words[$numberPart % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else {
            $str[] = null;
        }
    }

    $rupees = implode('', array_reverse($str));
    $decimal = intval($decimal);
    $tens = intdiv($decimal, 10) * 10;
    $ones = $decimal % 10;
    $paise = ($decimal > 0) ? "and " . ($words[$tens] . " " . $words[$ones]) . ' Paise' : '';
    $unit = strtoupper((string) $currency) === 'USD' ? 'Dollars' : 'Rupees';

    return ($rupees ? $rupees . $unit . ' ' : '') . $paise . ' Only';
}

function createInvoiceDompdf(): Dompdf
{
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    return new Dompdf($options);
}

function buildInvoiceHtml(array $invoiceValues, array $invoiceItems): string
{
    if (empty($invoiceValues)) {
        return '';
    }

    $invoiceDate = date("d M Y", strtotime((string) $invoiceValues['created_at']));
    $invoiceMonth = date("M", strtotime((string) $invoiceValues['created_at']));
    $currency = (string) ($invoiceValues['currency'] ?? 'INR');
    $currencySymbol = strtoupper($currency) === 'USD' ? '$' : '₹';

    $gstTypeRaw = isset($invoiceValues['gst_type']) ? trim((string) $invoiceValues['gst_type']) : '';
    $gstTypeLower = strtolower($gstTypeRaw);
    $cgstAmountValue = floatval($invoiceValues['cgst_amount'] ?? 0);
    $sgstAmountValue = floatval($invoiceValues['sgst_amount'] ?? 0);
    if (strtoupper($currency) !== 'INR') {
        $gstType = 'without_gst';
    } elseif ($gstTypeLower === 'with_gst') {
        $gstType = 'with_gst';
    } elseif ($gstTypeLower === 'without_gst') {
        $gstType = 'without_gst';
    } else {
        $gstType = ($cgstAmountValue > 0 || $sgstAmountValue > 0) ? 'with_gst' : 'without_gst';
    }

    $showGstOnPdf = (strtoupper($currency) === 'INR' && $gstType === 'with_gst');
    $invoiceHeading = $showGstOnPdf ? 'Tax Invoice' : 'Invoice';
    $receiverGstRow = $showGstOnPdf
        ? '<div style="margin-top: 5px;"><b>GSTIN:</b> ' . (($invoiceValues['gst_no'] ?? '') ?: 'N/A') . '</div>'
        : '';

    $logoPath = __DIR__ . '/img/PCS-Logo.png';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        if ($logoData !== false) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
    }

    $amountInWords = getAmountInWords(floatval($invoiceValues['total'] ?? 0), $currency);

    $output = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $invoiceHeading . ' - ' . ($invoiceValues['id'] ?? '') . '</title>
    <style>
        @page { margin: 20px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; line-height: 1.4; }
        .header-table { width: 100%; border: none; margin-bottom: 5px; }
        .header-left { width: 65%; vertical-align: top; }
        .header-right { width: 35%; text-align: right; vertical-align: top; }
        .company-name { font-size: 18px; font-weight: bold; color: #1f4e79; margin: 0; padding: 0; }
        .company-subtitle { font-size: 11px; font-weight: bold; color: #444; margin-bottom: 5px; }
        .company-details { font-size: 9px; color: #555; }
        .logo { width: 120px; }
        .divider { border-top: 1px solid #1f4e79; margin: 10px 0; height: 0; }
        
        .title-section { text-align: center; margin: 10px 0; }
        .title-section h1 { font-size: 16px; text-transform: uppercase; margin: 0; color: #000; letter-spacing: 1px; }
        .title-section h1.tax-invoice { color: #1f4e79; }
        .gstin-bold { font-weight: bold; font-size: 12px; margin-top: 3px; }

        .info-table { width: 100%; margin: 15px 0; border: none; }
        .info-table td { vertical-align: top; border: none; padding: 0; }
        .info-left { width: 60%; }
        .info-right { width: 40%; }
        .label { font-weight: bold; width: 80px; display: inline-block; }
        .bill-to-title { font-weight: bold; font-size: 12px; margin-bottom: 5px; color: #1f4e79; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { background-color: #1f4e79; color: #ffffff; padding: 10px 5px; text-align: center; font-weight: bold; border: none; }
        .items-table td { padding: 10px 5px; border: none; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }

        .summary-section { margin-top: 15px; width: 100%; }
        .summary-left { width: 55%; vertical-align: top; }
        .summary-right { width: 45%; vertical-align: top; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 5px; border: none; }
        .total-row-label { text-align: right; padding-right: 15px; width: 60%; }
        .total-row-value { text-align: right; width: 40%; font-weight: bold; }
        .grand-total-box { background-color: #1f4e79; color: #fff; }
        .grand-total-box td { padding: 8px 5px; }

        .amount-words { margin-top: 20px; font-weight: bold; }
        .amount-words span { font-weight: normal; font-style: italic; }

        .terms-section { margin-top: 30px; }
        .terms-title { font-weight: bold; text-decoration: underline; margin-bottom: 3px; }
        
        .footer-table { width: 100%; margin-top: 40px; border: none; }
        .footer-table td { vertical-align: top; border: none; }
        .bank-details { font-size: 9px; line-height: 1.5; color: #444; }
        .bank-title { font-weight: bold; margin-bottom: 3px; font-size: 10px; color: #1f4e79; }
        .auth-section { text-align: right; padding-top: 50px; }
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
                Email: contact@pcstech.in | Web: www.pcstech.in
            </div>
        </td>
        <td class="header-right">
            <img src="' . $logoBase64 . '" class="logo" alt="PCS Logo" />
        </td>
    </tr>
</table>

<div class="divider"></div>

<div class="title-section">
    <h1' . ($showGstOnPdf ? ' class="tax-invoice"' : '') . '>' . $invoiceHeading . '</h1>';
    if ($showGstOnPdf) {
        $output .= '
    <div class="gstin-bold">GSTIN: 33AALCP8510A1ZS</div>';
    }
    $output .= '
</div>

<table class="info-table">
    <tr>
        <td class="info-left">
            <div class="bill-to-title">Bill To:</div>
            <div style="font-weight: bold; font-size: 13px;">' . ($invoiceValues['receiver_name'] ?? '') . '</div>
            <div style="margin-top: 3px; color: #555;">' . nl2br((string) ($invoiceValues['receiver_address'] ?? '')) . '</div>
            ' . $receiverGstRow . '
        </td>
        <td class="info-right">
            <table style="width: 100%;">
                <tr>
                    <td class="bold" style="width: 100px;">Invoice No:</td>
                    <td>23/24 0025' . ($invoiceValues['id'] ?? '') . '</td>
                </tr>
                <tr>
                    <td class="bold">Date:</td>
                    <td>' . $invoiceDate . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width: 40px;">Sl.No</th>
            <th class="text-center">Description</th>
            <th style="width: 80px;">SAC Code</th>
            <th style="width: 60px;">Month</th>
            <th style="width: 100px;">Amount (' . $currencySymbol . ')</th>
        </tr>
    </thead>
    <tbody>';

    $count = 1;
    foreach ($invoiceItems as $item) {
        $output .= '
        <tr>
            <td class="text-center">' . $count++ . '</td>
            <td class="text-center">' . ($item['name'] ?? '') . '</td>
            <td class="text-center">' . ($item['hsn_sac'] ?? '') . '</td>
            <td class="text-center">' . $invoiceMonth . '</td>
            <td class="text-right">' . number_format(floatval($item['taxable_value'] ?? 0), 2) . '</td>
        </tr>';
    }

    $taxable = floatval($invoiceValues['rate'] ?? 0);
    $cgst = floatval($invoiceValues['cgst_amount'] ?? 0);
    $sgst = floatval($invoiceValues['sgst_amount'] ?? 0);
    $totalTax = $cgst + $sgst;
    $grandTotal = $taxable + ($showGstOnPdf ? $totalTax : 0);

    $output .= '
    </tbody>
</table>

<table class="summary-section">
    <tr>
        <td class="summary-left">
            <div class="amount-words">Amount in Words: <br><span>' . $amountInWords . '</span></div>
            
            <div class="terms-section">
                <div class="terms-title">Terms and Conditions</div>
                <div>Thanks for doing business with us!</div>
            </div>
        </td>
        <td class="summary-right">
            <table class="totals-table">
                <tr>
                    <td class="total-row-label">Subtotal</td>
                    <td class="total-row-value">' . $currencySymbol . ' ' . number_format($taxable, 2) . '</td>
                </tr>';

    if ($showGstOnPdf) {
        $output .= '
                <tr>
                    <td class="total-row-label">GST (18%)</td>
                    <td class="total-row-value">' . $currencySymbol . ' ' . number_format($totalTax, 2) . '</td>
                </tr>';
    }

    $output .= '
                <tr class="grand-total-box">
                    <td class="total-row-label">Final Total</td>
                    <td class="total-row-value" style="font-size: 14px;">' . $currencySymbol . ' ' . number_format($grandTotal, 2) . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="footer-table">
    <tr>
        <td style="width: 60%;">
            <div class="bank-title">Bank Details:</div>
            <div class="bank-details">
                <b>Receiver Name:</b> PAVITHA CONSULTANCY SERVICES OPC Pvt Ltd<br>
                <b>Bank Name:</b> FEDERAL BANK, SURANDAI<br>
                <b>Account Number:</b> 17480200004021<br>
                <b>IFSC:</b> FDRL0001748<br>
                <b>SWIFT Code:</b> FDRLINBBIBD
            </div>
        </td>
        <td style="width: 40%; text-align: right; vertical-align: bottom;">
            <div style="font-weight: bold; margin-bottom: 50px;">For Pavitha Consultancy Services</div>
            <div class="bold">Authorized Signatory</div>
        </td>
    </tr>
</table>

</body>
</html>';

    return $output;
}

function generateInvoiceHtml($invoiceId): string
{
    $invoiceId = intval($invoiceId);
    if ($invoiceId <= 0) {
        return '';
    }

    $invoice = new Invoice();
    $invoiceValues = $invoice->getInvoice($invoiceId);
    if (empty($invoiceValues)) {
        return '';
    }

    $invoiceItems = $invoice->getInvoiceItems($invoiceId);
    if (!is_array($invoiceItems)) {
        $invoiceItems = [];
    }

    return buildInvoiceHtml($invoiceValues, $invoiceItems);
}

function generateInvoicePdfBytes($invoiceId): string
{
    $html = generateInvoiceHtml($invoiceId);
    if ($html === '') {
        return '';
    }

    $dompdf = createInvoiceDompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}
