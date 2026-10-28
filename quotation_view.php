<?php
require_once 'session.php';
ensureSessionStarted();
require_once 'Invoice.php';
require_once 'Quotation.php';

$invoice = new Invoice();
$invoice->checkLoggedIn();

$quotationService = new Quotation();
$quotationId = intval($_GET['id'] ?? 0);
$quotation = $quotationId > 0 ? $quotationService->getQuotation($quotationId) : [];
$items = $quotation ? $quotationService->getQuotationItems($quotationId) : [];
$csrfToken = Quotation::csrfToken();

$pageError = $quotation ? '' : 'The quotation you are looking for could not be found.';

if ($pageError !== '') {
    include('header.php');
    ?>
    <title>Quotation Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/quotation.css">
    </head>

    <body class="quotation-body">
        <div class="quotation-page">
            <div class="card quotation-card">
                <div class="card-body">
                    <h2 class="quotation-title">Quotation Details</h2>
                    <div class="alert alert-danger mt-3"><?php echo Quotation::escape($pageError); ?></div>
                    <a href="main.php?page=quotation_list" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Back to Quotation List
                    </a>
                </div>
            </div>
        </div>
    <?php include('footer.php'); ?>
    <?php
    exit;
}

$currency = (string) ($quotation['currency'] ?? 'INR');
$currencySymbol = Quotation::currencySymbol($currency);
$status = (string) $quotation['status'];
$isConverted = $status === 'Converted';
$allowedStatuses = array_values(array_filter(
    Quotation::allowedTransitions($status),
    static function ($allowedStatus) {
        return $allowedStatus !== 'Converted';
    }
));

$whatsApp = Quotation::formatWhatsAppNumber($quotation['customer_phone'] ?? '');
$shareMessage = 'Hello ' . ($quotation['customer_name'] ?? '') . ",\n\n"
    . 'Please find our quotation ' . ($quotation['quotation_no'] ?? '') . ' dated '
    . date('d M Y', strtotime((string) $quotation['quotation_date']))
    . ' for ' . $currencySymbol . ' ' . number_format((float) $quotation['grand_total'], 2) . ".\n"
    . 'Valid until: ' . date('d M Y', strtotime((string) $quotation['valid_until'])) . ".\n\n"
    . "Thank you,\nPavitha Consultancy Services OPC Pvt Ltd.";
$whatsAppUrl = $whatsApp !== ''
    ? 'https://wa.me/' . rawurlencode($whatsApp) . '?text=' . rawurlencode($shareMessage)
    : '';
$emailUrl = !empty($quotation['customer_email'])
    ? 'mailto:' . $quotation['customer_email']
        . '?subject=' . rawurlencode('Quotation ' . $quotation['quotation_no'])
        . '&body=' . rawurlencode($shareMessage)
    : '';

$statusNotice = '';
if (isset($_GET['created'])) {
    $statusNotice = 'Quotation created successfully. You can print, share or download it below.';
} elseif (isset($_GET['updated'])) {
    $statusNotice = 'Quotation updated successfully.';
} elseif (isset($_GET['converted'])) {
    $statusNotice = 'Quotation converted to an invoice successfully.';
} elseif (isset($_GET['notice']) && $_GET['notice'] === 'converted') {
    $statusNotice = 'Converted quotations cannot be edited.';
}

include('header.php');
?>
<title>Quotation <?php echo Quotation::escape($quotation['quotation_no']); ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/quotation.css">
</head>

<body class="quotation-body">
    <div class="quotation-page">
        <div class="card quotation-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                    <div class="pr-3">
                        <div class="d-flex flex-wrap align-items-center">
                            <h2 class="quotation-title mr-2">Quotation Details</h2>
                            <span class="quotation-status <?php echo Quotation::escape('status-' . strtolower($status)); ?>">
                                <?php echo Quotation::escape($status); ?>
                            </span>
                        </div>
                        <p class="quotation-subtitle mb-0"><?php echo Quotation::escape($quotation['quotation_no']); ?></p>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <a href="main.php?page=quotation_list" class="btn btn-outline-secondary mb-2">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <?php if ($statusNotice !== ''): ?>
                    <div class="alert alert-info" role="alert"><?php echo Quotation::escape($statusNotice); ?></div>
                <?php endif; ?>

                <div class="mb-3">
                    <a href="quotation_pdf.php?id=<?php echo $quotationId; ?>" target="_blank" rel="noopener"
                        class="btn btn-outline-dark mb-2">
                        <i class="bi bi-printer"></i> Print / Open PDF
                    </a>
                    <a href="quotation_pdf.php?id=<?php echo $quotationId; ?>&download=1"
                        class="btn btn-outline-dark mb-2">
                        <i class="bi bi-download"></i> Download PDF
                    </a>
                    <button type="button" class="btn btn-success mb-2" data-toggle="modal" data-target="#shareQuotationModal">
                        <i class="bi bi-share"></i> Share Quotation
                    </button>
                    <?php if (!$isConverted): ?>
                        <a href="edit_quotation.php?update_id=<?php echo $quotationId; ?>" class="btn btn-outline-primary mb-2">
                            <i class="bi bi-pencil-square"></i> Edit Quotation
                        </a>
                    <?php endif; ?>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="quotation-view-meta">
                            <div class="label">Quotation No.</div>
                            <div class="value"><?php echo Quotation::escape($quotation['quotation_no']); ?></div>
                            <div class="label">Quotation Date</div>
                            <div class="value"><?php echo date('d M Y', strtotime((string) $quotation['quotation_date'])); ?></div>
                            <div class="label">Valid Until</div>
                            <div class="value"><?php echo date('d M Y', strtotime((string) $quotation['valid_until'])); ?></div>
                            <div class="label">Status</div>
                            <div class="value mb-0"><?php echo Quotation::escape($status); ?></div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="quotation-view-meta">
                            <div class="label">Customer</div>
                            <div class="value"><?php echo Quotation::escape($quotation['customer_name']); ?></div>
                            <div class="label">Phone / WhatsApp</div>
                            <div class="value"><?php echo $quotation['customer_phone'] !== '' ? Quotation::escape($quotation['customer_phone']) : '&mdash;'; ?></div>
                            <div class="label">Email</div>
                            <div class="value"><?php echo $quotation['customer_email'] !== '' ? Quotation::escape($quotation['customer_email']) : '&mdash;'; ?></div>
                            <div class="label">Address</div>
                            <div class="value mb-0">
                                <?php echo $quotation['customer_address'] !== '' ? nl2br(Quotation::escape($quotation['customer_address'])) : '&mdash;'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 mb-3">
                        <div class="quotation-view-meta h-100">
                            <div class="label">Grand Total</div>
                            <div class="value" style="font-size: 1.5rem; color: #1e3c72;">
                                <?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $quotation['grand_total'], 2)); ?>
                            </div>
                            <div class="label">Notes</div>
                            <div class="value mb-0">
                                <?php echo $quotation['notes'] !== '' ? nl2br(Quotation::escape($quotation['notes'])) : '&mdash;'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="quotation-view-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th>Product / Service</th>
                                <th style="width: 70px;">Qty</th>
                                <th style="width: 110px;">Unit Price</th>
                                <th style="width: 100px;">Discount</th>
                                <th style="width: 80px;">Tax %</th>
                                <th style="width: 120px;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7" class="text-center quotation-muted">No items were saved for this quotation.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo Quotation::escape($item['product_name']); ?></strong>
                                            <?php if (!empty($item['product_description'])): ?>
                                                <div class="small quotation-muted mt-1">
                                                    <?php echo nl2br(Quotation::escape($item['product_description'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo rtrim(rtrim(number_format((float) $item['quantity'], 2, '.', ''), '0'), '.'); ?></td>
                                        <td><?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $item['unit_price'], 2)); ?></td>
                                        <td><?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $item['discount'], 2)); ?></td>
                                        <td><?php echo number_format((float) $item['tax_percent'], 2); ?>%</td>
                                        <td><strong><?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $item['line_total'], 2)); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-7 col-md-6 mb-3">
                        <div class="quotation-view-meta">
                            <div class="label">Terms &amp; Conditions</div>
                            <div class="value mb-0">
                                <?php echo $quotation['terms_conditions'] !== '' ? nl2br(Quotation::escape($quotation['terms_conditions'])) : 'Thanks for doing business with us!'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-6 mb-3">
                        <div class="quotation-totals">
                            <table>
                                <tr>
                                    <th>Subtotal</th>
                                    <td><?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $quotation['subtotal'], 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Discount</th>
                                    <td><?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $quotation['total_discount'], 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>Tax / GST</th>
                                    <td><?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $quotation['tax_amount'], 2)); ?></td>
                                </tr>
                                <tr class="grand-total">
                                    <th>Grand Total</th>
                                    <td><?php echo Quotation::escape($currencySymbol . ' ' . number_format((float) $quotation['grand_total'], 2)); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-3">
                        <div class="quotation-view-meta h-100">
                            <div class="label">Update Status</div>
                            <?php if ($isConverted): ?>
                                <div class="value mb-0">
                                    This quotation has been converted to an invoice.
                                    <?php if (!empty($quotation['converted_invoice_id'])): ?>
                                        <a href="edit_invoice.php?update_id=<?php echo intval($quotation['converted_invoice_id']); ?>">
                                            View invoice #<?php echo intval($quotation['converted_invoice_id']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-wrap align-items-center">
                                    <select id="quotationStatusSelect" class="form-control mr-2 mb-2" style="max-width: 200px;">
                                        <?php foreach ($allowedStatuses as $allowedStatus): ?>
                                            <option value="<?php echo Quotation::escape($allowedStatus); ?>" <?php echo $allowedStatus === $status ? 'selected' : ''; ?>>
                                                <?php echo Quotation::escape($allowedStatus); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-primary mb-2" id="updateQuotationStatus">
                                        <i class="bi bi-check2"></i> Update Status
                                    </button>
                                </div>
                                <div id="statusUpdateAlert" class="small"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-5 mb-3">
                        <div class="quotation-view-meta h-100">
                            <div class="label">Convert to Invoice</div>
                            <?php if ($isConverted): ?>
                                <p class="mb-2">Converted successfully.</p>
                                <?php if (!empty($quotation['converted_invoice_id'])): ?>
                                    <a href="edit_invoice.php?update_id=<?php echo intval($quotation['converted_invoice_id']); ?>"
                                        class="btn btn-outline-primary">
                                        <i class="bi bi-receipt"></i> Open Invoice
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($status === 'Accepted'): ?>
                                <p class="mb-2">This quotation is accepted and ready to become an invoice.</p>
                                <button type="button" class="btn btn-success" id="convertQuotation">
                                    <i class="bi bi-arrow-right-circle"></i> Convert to Invoice
                                </button>
                                <div id="convertQuotationAlert" class="small mt-2"></div>
                            <?php else: ?>
                                <p class="mb-0 quotation-muted">Set the status to <strong>Accepted</strong> before converting this quotation to an invoice.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shareQuotationModal" tabindex="-1" role="dialog" aria-labelledby="shareQuotationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareQuotationModalLabel">Share Quotation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><strong><?php echo Quotation::escape($quotation['quotation_no']); ?></strong><br>
                        <span class="small quotation-muted"><?php echo Quotation::escape($quotation['customer_name']); ?></span>
                    </p>
                    <div class="mb-3">
                        <?php if ($whatsAppUrl !== ''): ?>
                            <a href="<?php echo Quotation::escape($whatsAppUrl); ?>" target="_blank" rel="noopener"
                                class="btn btn-success btn-block mb-2">
                                <i class="bi bi-whatsapp"></i> Share on WhatsApp
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-success btn-block mb-2" disabled>
                                <i class="bi bi-whatsapp"></i> WhatsApp (no saved phone)
                            </button>
                        <?php endif; ?>

                        <?php if ($emailUrl !== ''): ?>
                            <a href="<?php echo Quotation::escape($emailUrl); ?>" class="btn btn-outline-primary btn-block mb-2">
                                <i class="bi bi-envelope"></i> Email Quotation Details
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-primary btn-block mb-2" disabled>
                                <i class="bi bi-envelope"></i> Email (no saved email)
                            </button>
                        <?php endif; ?>

                        <a href="quotation_pdf.php?id=<?php echo $quotationId; ?>&download=1"
                            class="btn btn-outline-dark btn-block mb-2">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                        <a href="quotation_pdf.php?id=<?php echo $quotationId; ?>" target="_blank" rel="noopener"
                            class="btn btn-outline-secondary btn-block mb-2">
                            <i class="bi bi-printer"></i> Print / Open PDF
                        </a>
                        <button type="button" class="btn btn-outline-success btn-block" id="copyQuotationMessageView">
                            <i class="bi bi-clipboard"></i> Copy Share Message
                        </button>
                    </div>

                    <label class="font-weight-bold small" for="shareQuotationMessageView">Message Preview</label>
                    <textarea id="shareQuotationMessageView" class="form-control" rows="6" readonly><?php echo Quotation::escape($shareMessage); ?></textarea>
                    <div id="copyQuotationAlertView" class="small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        'use strict';

        var quotationId = <?php echo $quotationId; ?>;
        var csrfToken = <?php echo json_encode($csrfToken); ?>;

        function alertBox(id, message, type) {
            var box = document.getElementById(id);
            if (!box) {
                return;
            }
            box.className = 'small ' + (type === 'error' ? 'text-danger' : 'text-success');
            box.textContent = message;
        }

        var copyButton = document.getElementById('copyQuotationMessageView');
        if (copyButton) {
            copyButton.addEventListener('click', function () {
                var textarea = document.getElementById('shareQuotationMessageView');
                var done = function () {
                    alertBox('copyQuotationAlertView', 'Share message copied to the clipboard.', 'success');
                };
                var fallback = function () {
                    textarea.focus();
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        done();
                    } catch (error) {
                        alertBox('copyQuotationAlertView', 'Could not copy automatically. Please select and copy the message.', 'error');
                    }
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(textarea.value).then(done).catch(fallback);
                } else {
                    fallback();
                }
            });
        }

        var updateButton = document.getElementById('updateQuotationStatus');
        if (updateButton) {
            updateButton.addEventListener('click', function () {
                var select = document.getElementById('quotationStatusSelect');
                if (!select || !select.value) {
                    return;
                }

                updateButton.disabled = true;
                updateButton.textContent = 'Updating...';

                $.ajax({
                    url: 'action.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'update_quotation_status',
                        id: quotationId,
                        status: select.value,
                        csrf_token: csrfToken
                    }
                }).done(function (response) {
                    if (response && Number(response.status) === 1) {
                        alertBox('statusUpdateAlert', response.message || 'Status updated.', 'success');
                        window.location.reload();
                    } else {
                        alertBox('statusUpdateAlert', (response && response.message) || 'Unable to update status.', 'error');
                    }
                }).fail(function (xhr) {
                    alertBox('statusUpdateAlert', (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to update status.', 'error');
                }).always(function () {
                    updateButton.disabled = false;
                    updateButton.innerHTML = '<i class="bi bi-check2"></i> Update Status';
                });
            });
        }

        var convertButton = document.getElementById('convertQuotation');
        if (convertButton) {
            convertButton.addEventListener('click', function () {
                if (!window.confirm('Convert this accepted quotation into an invoice? This action cannot be undone.')) {
                    return;
                }

                convertButton.disabled = true;
                convertButton.textContent = 'Converting...';

                $.ajax({
                    url: 'action.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'convert_quotation',
                        id: quotationId,
                        csrf_token: csrfToken
                    }
                }).done(function (response) {
                    if (response && Number(response.status) === 1 && response.invoice_id) {
                        window.location.href = 'edit_invoice.php?update_id=' + encodeURIComponent(response.invoice_id);
                    } else {
                        alertBox('convertQuotationAlert', (response && response.message) || 'Unable to convert quotation.', 'error');
                    }
                }).fail(function (xhr) {
                    alertBox('convertQuotationAlert', (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to convert quotation.', 'error');
                }).always(function () {
                    convertButton.disabled = false;
                    convertButton.innerHTML = '<i class="bi bi-arrow-right-circle"></i> Convert to Invoice';
                });
            });
        }
    }());
    </script>

    <?php include('footer.php'); ?>
