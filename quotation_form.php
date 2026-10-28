<?php
/**
 * Shared create/edit quotation form. Expects:
 * $quotation, $formItems, $formErrors, $statusOptions, $csrfToken,
 * $formMode ('create'|'edit'), $actionUrl, optional $cancelUrl.
 */
if (!defined('QUOTATION_FORM_INCLUDED')) {
    define('QUOTATION_FORM_INCLUDED', true);
}

$formMode = $formMode ?? 'create';
$isEdit = $formMode === 'edit';
$formErrors = isset($formErrors) && is_array($formErrors) ? $formErrors : [];
$statusOptions = isset($statusOptions) && is_array($statusOptions) ? $statusOptions : ['Draft', 'Sent'];
$csrfToken = $csrfToken ?? '';
$actionUrl = $actionUrl ?? ($isEdit ? 'edit_quotation.php' : 'create_quotation.php');
$cancelUrl = $cancelUrl ?? 'main.php?page=quotation_list';
$currencySymbol = Quotation::currencySymbol('INR');

if (empty($statusOptions)) {
    $statusOptions = ['Draft', 'Sent'];
}
$currentStatus = (string) ($quotation['status'] ?? 'Draft');
if (!in_array($currentStatus, $statusOptions, true) && $currentStatus !== 'Converted') {
    $statusOptions[] = $currentStatus;
}

$quotation = array_merge([
    'quotation_no' => '',
    'quotation_date' => date('Y-m-d'),
    'valid_until' => date('Y-m-d', strtotime('+15 days')),
    'customer_name' => '',
    'customer_phone' => '',
    'customer_email' => '',
    'customer_address' => '',
    'currency' => 'INR',
    'status' => 'Draft',
    'notes' => '',
    'terms_conditions' => '',
], is_array($quotation ?? null) ? $quotation : []);

if (empty($formItems) || !is_array($formItems)) {
    $formItems = [[]];
}

$e = static function ($value): string {
    return Quotation::escape($value);
};

$totals = ['subtotal' => 0.0, 'discount' => 0.0, 'tax' => 0.0, 'grand' => 0.0];
$formItems = array_values($formItems);
?>
<div class="quotation-page">
    <div class="card quotation-card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                <div class="pr-3">
                    <h2 class="quotation-title">
                        <?php echo $isEdit ? 'Edit Quotation' : 'Create Quotation'; ?>
                    </h2>
                    <p class="quotation-subtitle mb-0">
                        Pavitha Consultancy Services OPC Pvt Ltd. &middot; GSTIN: 33AALCP8510A1ZS
                    </p>
                </div>
                <a href="main.php?page=quotation_list" class="btn btn-outline-secondary mt-2 mt-sm-0">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>

            <div id="quotationFormErrors" class="alert alert-danger <?php echo empty($formErrors) ? 'd-none' : ''; ?>" role="alert">
                <?php if (!empty($formErrors)): ?>
                    <ul class="mb-0">
                        <?php foreach ($formErrors as $error): ?>
                            <li><?php echo $e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <form id="quotationForm" method="post" action="<?php echo $e($actionUrl); ?>" novalidate
                data-currency-symbol="<?php echo $e($currencySymbol); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="quotation_id" value="<?php echo intval($quotation['id'] ?? 0); ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="quotation_no">Quotation No.</label>
                        <input type="text" class="form-control" id="quotation_no" name="quotation_no"
                            value="<?php echo $e($quotation['quotation_no']); ?>" readonly>
                        <small class="form-text text-muted">Generated automatically.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="quotation_date">Quotation Date *</label>
                        <input type="date" class="form-control" id="quotation_date" name="quotation_date"
                            value="<?php echo $e($quotation['quotation_date']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="valid_until">Valid Until *</label>
                        <input type="date" class="form-control" id="valid_until" name="valid_until"
                            value="<?php echo $e($quotation['valid_until']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label font-weight-bold" for="customer_name">Customer Name *</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name"
                            maxlength="150" value="<?php echo $e($quotation['customer_name']); ?>"
                            placeholder="Customer or company name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?php echo $e($status); ?>" <?php echo $currentStatus === $status ? 'selected' : ''; ?>>
                                    <?php echo $e($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="customer_phone">Phone / WhatsApp</label>
                        <input type="text" class="form-control" id="customer_phone" name="customer_phone"
                            maxlength="20" value="<?php echo $e($quotation['customer_phone']); ?>"
                            placeholder="+91 98765 43210">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="customer_email">Email</label>
                        <input type="email" class="form-control" id="customer_email" name="customer_email"
                            maxlength="150" value="<?php echo $e($quotation['customer_email']); ?>"
                            placeholder="customer@example.com">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="customer_address">Address</label>
                        <textarea class="form-control" id="customer_address" name="customer_address" rows="2"
                            maxlength="1000" placeholder="Billing address"><?php echo $e($quotation['customer_address']); ?></textarea>
                    </div>
                </div>

                <hr>

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="h5 font-weight-bold text-dark mb-1">Products / Services</h4>
                        <p class="text-muted mb-0 small">Add each product or service on its own row.</p>
                    </div>
                    <button type="button" class="btn btn-primary mt-2 mt-sm-0" id="addItemRow">
                        <i class="bi bi-plus-circle"></i> Add Row
                    </button>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered quotation-items-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th style="min-width: 180px;">Product / Service *</th>
                                <th style="min-width: 200px;">Description</th>
                                <th style="width: 85px;">Qty *</th>
                                <th style="width: 120px;">Unit Price *</th>
                                <th style="width: 110px;">Discount</th>
                                <th style="width: 90px;">Tax %</th>
                                <th style="width: 125px;">Line Total</th>
                                <th style="width: 55px;"></th>
                            </tr>
                        </thead>
                        <tbody id="quotationItemsBody">
                            <?php foreach ($formItems as $index => $item):
                                $item = array_merge([
                                    'product_name' => '',
                                    'product_description' => '',
                                    'quantity' => 1,
                                    'unit_price' => 0,
                                    'discount' => 0,
                                    'tax_percent' => 0,
                                ], is_array($item) ? $item : []);

                                $quantity = is_numeric($item['quantity']) ? (float) $item['quantity'] : 0.0;
                                $unitPrice = is_numeric($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
                                $discount = is_numeric($item['discount']) ? (float) $item['discount'] : 0.0;
                                $taxPercent = is_numeric($item['tax_percent']) ? (float) $item['tax_percent'] : 0.0;

                                $gross = round($quantity * $unitPrice, 2);
                                $appliedDiscount = min(max($discount, 0), $gross);
                                $lineTotal = round(max(0, $gross - $appliedDiscount), 2);
                                $taxAmount = round($lineTotal * max(0, $taxPercent) / 100, 2);

                                $totals['subtotal'] += $gross;
                                $totals['discount'] += $appliedDiscount;
                                $totals['tax'] += $taxAmount;
                            ?>
                                <tr class="item-row">
                                    <td class="row-index text-center"><?php echo $index + 1; ?></td>
                                    <td>
                                        <input type="text" class="form-control item-name" name="product_name[]"
                                            data-field="product_name" maxlength="255"
                                            value="<?php echo $e($item['product_name']); ?>"
                                            placeholder="Product or service" required>
                                    </td>
                                    <td>
                                        <textarea class="form-control item-description" name="product_description[]"
                                            data-field="product_description" rows="2" maxlength="2000"
                                            placeholder="Optional details"><?php echo $e($item['product_description']); ?></textarea>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-qty" name="quantity[]"
                                            data-field="quantity" min="0.01" step="0.01"
                                            value="<?php echo $e($item['quantity']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-price" name="unit_price[]"
                                            data-field="unit_price" min="0" step="0.01"
                                            value="<?php echo $e($item['unit_price']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-discount" name="discount[]"
                                            data-field="discount" min="0" step="0.01"
                                            value="<?php echo $e($item['discount']); ?>">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-tax" name="tax_percent[]"
                                            data-field="tax_percent" min="0" max="100" step="0.01"
                                            value="<?php echo $e($item['tax_percent']); ?>">
                                    </td>
                                    <td class="text-right line-total"><?php echo $e($currencySymbol . ' ' . number_format($lineTotal, 2)); ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger removeItemRow"
                                            title="Remove row">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <template id="quotationItemTemplate">
                    <tr class="item-row">
                        <td class="row-index text-center">1</td>
                        <td>
                            <input type="text" class="form-control item-name" name="product_name[]"
                                data-field="product_name" maxlength="255" placeholder="Product or service" required>
                        </td>
                        <td>
                            <textarea class="form-control item-description" name="product_description[]"
                                data-field="product_description" rows="2" maxlength="2000"
                                placeholder="Optional details"></textarea>
                        </td>
                        <td>
                            <input type="number" class="form-control item-qty" name="quantity[]"
                                data-field="quantity" min="0.01" step="0.01" value="1" required>
                        </td>
                        <td>
                            <input type="number" class="form-control item-price" name="unit_price[]"
                                data-field="unit_price" min="0" step="0.01" value="0.00" required>
                        </td>
                        <td>
                            <input type="number" class="form-control item-discount" name="discount[]"
                                data-field="discount" min="0" step="0.01" value="0.00">
                        </td>
                        <td>
                            <input type="number" class="form-control item-tax" name="tax_percent[]"
                                data-field="tax_percent" min="0" max="100" step="0.01" value="0">
                        </td>
                        <td class="text-right line-total"><?php echo $e($currencySymbol . ' 0.00'); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger removeItemRow" title="Remove row">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>

                <?php $totals['grand'] = round($totals['subtotal'] - $totals['discount'] + $totals['tax'], 2); ?>
                <div class="row justify-content-end mb-4">
                    <div class="col-lg-5 col-md-7">
                        <div class="quotation-totals">
                            <table>
                                <tr>
                                    <th>Subtotal</th>
                                    <td id="summarySubtotal"><?php echo $e($currencySymbol . ' ' . number_format($totals['subtotal'], 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Discount</th>
                                    <td id="summaryDiscount"><?php echo $e($currencySymbol . ' ' . number_format($totals['discount'], 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>Tax / GST</th>
                                    <td id="summaryTax"><?php echo $e($currencySymbol . ' ' . number_format($totals['tax'], 2)); ?></td>
                                </tr>
                                <tr class="grand-total">
                                    <th>Grand Total</th>
                                    <td id="summaryGrandTotal"><?php echo $e($currencySymbol . ' ' . number_format($totals['grand'], 2)); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="5000"
                            placeholder="Notes visible on the quotation"><?php echo $e($quotation['notes']); ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="terms_conditions">Terms &amp; Conditions</label>
                        <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4"
                            maxlength="5000"
                            placeholder="Payment terms, delivery timeline, validity, etc."><?php echo $e($quotation['terms_conditions']); ?></textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end pt-2">
                    <a href="<?php echo $e($cancelUrl); ?>" class="btn btn-outline-secondary mr-2 mb-2 mb-sm-0">Cancel</a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check2-circle"></i>
                        <?php echo $isEdit ? 'Update Quotation' : 'Save Quotation'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/quotation.js"></script>
