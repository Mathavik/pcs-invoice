<?php
require_once 'session.php';
ensureSessionStarted();
include('header.php');
require_once 'Invoice.php';
$invoice = new Invoice();
$invoice->checkLoggedIn();

if (!empty($_POST['receiverName']) && $_POST['receiverName']) {
   $invoice->saveInvoice($_POST);
   header("Location:main.php?page=invoice_list");
}
?>
<title>Create Invoice</title>
<script src="js/invoice.js"></script>
<!-- <link href="css/style.css" rel="stylesheet"> -->

<style>
.custom-pill-toggle .btn-check:checked + .btn {
    background: linear-gradient(135deg, #0f1e45 0%, #1e3c72 100%);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(30, 60, 114, 0.4);
    transform: translateY(-1px);
}
.custom-pill-toggle .btn {
    border-radius: 50px !important;
    padding: 0.5rem 1.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
    color: #6c757d;
    background-color: transparent;
    margin: 0 2px;
}
.custom-pill-toggle .btn-check:not(:checked) + .btn:hover {
    color: #1e3c72;
    background-color: #e9ecef;
}
.custom-pill-toggle input[type="radio"],
.custom-pill-toggle .btn-check {
    display: none !important;
    opacity: 0 !important;
    position: absolute !important;
    z-index: -1 !important;
    pointer-events: none !important;
}
.custom-pill-toggle {
    background-color: #f1f3f5;
    padding: 4px;
    border-radius: 50px;
    display: inline-flex;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
}
.form-select.shadow-sm {
    border: 1px solid #ced4da;
    padding: 0.5rem 1rem;
    height: calc(2.25rem + 12px); /* matching toggle height roughly */
}
</style>

<body
    style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0f1e45 0%, #1e3c72 100%); margin: 0; padding: 0;">
    <main class="container py-4">
        <div class="card shadow rounded-3">
            <div class="card-body">
                <form action="" id="invoice-form" method="post" class="invoice-form" role="form" novalidate>
                    <div class="load-animate animated fadeInUp">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h2 class="text-primary">Create Invoice</h2>

                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-secondary">From</h5>
                                <p class="mb-1"><?php echo $_SESSION['user']; ?></p>
                                <p class="mb-1"><?php echo $_SESSION['address']; ?></p>
                                <p class="mb-1"><?php echo $_SESSION['mobile']; ?></p>
                                <p class="mb-1"><?php echo $_SESSION['email']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-secondary">To</h5>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control" name="receiverName" placeholder="Name"
                                        autocomplete="off">
                                </div>
                                <div class="form-group mb-3">
                                    <textarea class="form-control" rows="2" name="receiverAddress"
                                        placeholder="Address"></textarea>
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control" name="gstNo" placeholder="GST No."
                                        autocomplete="off">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control" name="panNo" placeholder="PAN No."
                                        autocomplete="off">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control" name="supply" placeholder="Place of Supply"
                                        autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-4 align-items-center mb-3">
                            <div class="form-group" style="width: 250px;">
                                <label for="currency" class="form-label fw-bold text-secondary mb-2">Currency Type :</label>
                                <select name="currency" id="currency" class="form-select shadow-sm" style="border-radius: 8px;" required>
                                    <option value="INR" selected>₹ Rupees</option>
                                    <option value="USD">$ Dollars</option>
                                </select>
                            </div>

                            <div class="form-group" id="gst-toggle-wrapper">
                                <label class="form-label d-block fw-bold text-secondary mb-2">Invoice Type :</label>
                                <div class="custom-pill-toggle" role="group" aria-label="GST Type">
                                    <input type="radio" class="btn-check" name="gst_type" id="with_gst" value="with_gst" checked autocomplete="off">
                                    <label class="btn" for="with_gst">With GST</label>

                                    <input type="radio" class="btn-check" name="gst_type" id="without_gst" value="without_gst" autocomplete="off">
                                    <label class="btn" for="without_gst">Without GST</label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover" id="invoiceItem">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="2%" class="text-center align-middle">
                                            <div class="form-check m-0">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                            </div>
                                        </th>
                                        <th class="align-middle">Name of the Product / Service</th>
                                        <th class="align-middle">HSN / SAC</th>
                                        <th class="align-middle">Price</th>
                                        <th class="align-middle">Taxable Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="form-check-input itemRow" id="itemRow_1">
                                            </div>
                                        </td>
                                        <td><input type="text" name="productName[]" id="productName_1"
                                                class="form-control" autocomplete="off"></td>
                                        <td><input type="text" name="productCode[]" id="productCode_1"
                                                class="form-control" value="998314" autocomplete="off"></td>
                                        <td><input type="number" name="price[]" id="price_1" class="form-control price"
                                                autocomplete="off"></td>
                                        <td><input type="number" name="taxableValue[]" id="taxableValue_1"
                                                class="form-control taxableValue" autocomplete="off"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="d-flex gap-2">
                                <button class="btn btn-danger" id="removeRows" type="button">- Delete</button>&nbsp;
                                <button class="btn btn-primary" id="addRows" type="button">+ Add More</button>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Rate:</label>
                                <div class="input-group">
                                    <span class="input-group-text currency-symbol">₹</span>
                                    <input type="number" class="form-control" name="rate" id="rate" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Total:</label>
                                <div class="input-group">
                                    <span class="input-group-text currency-symbol">₹</span>
                                    <input type="number" class="form-control" name="total" id="total" readonly>
                                </div>
                            </div>
                        </div>

                        <div id="gst-fields-wrapper" class="row">
                            <div class="col-md-3 mb-3 gst-only-fields">
                                <label for="cgstTax">CGST (%)</label>
                                <div class="input-group">
                                    <span class="input-group-text">%</span>
                                    <input type="number" class="form-control" name="cgstTax" id="cgstTax" value="9" readonly>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3 gst-only-fields">
                                <label>CGST Amount:</label>
                                <div class="input-group">
                                    <span class="input-group-text currency-symbol">₹</span>
                                    <input type="number" class="form-control" name="cgstAmount" id="cgstAmount" readonly>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3 gst-only-fields">
                                <label for="sgstTax">SGST (%)</label>
                                <div class="input-group">
                                    <span class="input-group-text">%</span>
                                    <input type="number" class="form-control" name="sgstTax" id="sgstTax" value="9" readonly>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3 gst-only-fields">
                                <label>SGST Amount:</label>
                                <div class="input-group">
                                    <span class="input-group-text currency-symbol">₹</span>
                                    <input type="number" class="form-control" name="sgstAmount" id="sgstAmount" readonly>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group mb-3">
                            <label>Notes:</label>
                            <textarea class="form-control" rows="3" name="notes" id="notes"
                                placeholder="Additional Notes"></textarea>
                        </div>

                        <div class="form-group text-end">
                            <input type="hidden" value="<?php echo $_SESSION['userId']; ?>" name="userId">
                            <button type="submit" name="invoice_btn" class="btn btn-success px-3">Save</button>
                            <button type="button" style="background-color: #f05050; color: white;"
                                class="btn btn-close px-3"
                                onclick="window.location.href='main.php?page=invoice_list'">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>

<?php include('footer.php'); ?>