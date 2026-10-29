<?php
require_once 'session.php';
ensureSessionStarted();
require_once 'Invoice.php';
require_once 'Quotation.php';

$invoice = new Invoice();
$invoice->checkLoggedIn();

$quotationService = new Quotation();
$quotationId = intval($_GET['update_id'] ?? $_POST['quotation_id'] ?? 0);
$quotationRecord = $quotationId > 0 ? $quotationService->getQuotation($quotationId) : [];
$customerSuggestions = $quotationService->getCustomerSuggestions();

$pageError = '';
if (empty($quotationRecord)) {
    $pageError = 'The quotation you are trying to edit could not be found.';
} elseif ($quotationRecord['status'] === 'Converted') {
    header('Location: quotation_view.php?id=' . $quotationId . '&notice=converted');
    exit;
}

if ($pageError !== '') {
    include('header.php');
    ?>
    <title>Edit Quotation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/quotation.css?v=<?php echo filemtime(__DIR__ . '/css/quotation.css'); ?>">
    </head>

    <body class="quotation-body">
        <div class="quotation-page">
            <div class="card quotation-card">
                <div class="card-body">
                    <h2 class="quotation-title">Edit Quotation</h2>
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

$csrfToken = Quotation::csrfToken();
$formErrors = [];
$formMode = 'edit';
$quotationData = $quotationRecord;
$actionUrl = 'edit_quotation.php?update_id=' . $quotationId;
$statusOptions = array_values(array_filter(
    Quotation::allowedTransitions($quotationRecord['status']),
    static function ($status) {
        return !in_array($status, ['Converted', 'Accepted'], true);
    }
));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Quotation::validateCsrf($_POST['csrf_token'] ?? '')) {
        $formErrors[] = 'Your session security token has expired. Please refresh the page and try again.';
    } else {
        // Notes can only be set on the create page; keep the saved value when
        // editing so removing the field from the form never clears it.
        $_POST['notes'] = (string) ($quotationRecord['notes'] ?? '');

        $prepared = $quotationService->preparePayload($_POST, true, $quotationId);
        $formErrors = $prepared['errors'];

        if (empty($formErrors)) {
            try {
                $quotationService->updateQuotation($quotationId, $_POST);
                header('Location: quotation_view.php?id=' . $quotationId . '&updated=1');
                exit;
            } catch (Throwable $exception) {
                $formErrors[] = $exception->getMessage();
            }
        }
    }

    foreach ([
        'quotation_no',
        'quotation_date',
        'valid_until',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'gst_type',
        'notes',
        'terms_conditions',
    ] as $field) {
        if (isset($_POST[$field]) && is_scalar($_POST[$field])) {
            $quotationData[$field] = (string) $_POST[$field];
        }
    }

    if (isset($_POST['status'])
        && in_array($_POST['status'], $statusOptions, true)
        && $_POST['status'] !== 'Converted') {
        $quotationData['status'] = (string) $_POST['status'];
    }

    $postedItems = Quotation::itemsFromPost($_POST);
    $formItems = empty($postedItems) ? [[]] : $postedItems;
} else {
    $formItems = $quotationService->getQuotationItems($quotationId);
    if (empty($formItems)) {
        $formItems = [[]];
    }
}

$quotationForForm = $quotationData;

include('header.php');
?>
<title>Edit Quotation</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/quotation.css?v=<?php echo filemtime(__DIR__ . '/css/quotation.css'); ?>">
</head>

<body class="quotation-body">
    <?php
    $quotation = $quotationForForm;
    include('quotation_form.php');
    ?>
    <?php include('footer.php'); ?>
