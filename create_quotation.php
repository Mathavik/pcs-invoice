<?php
require_once 'session.php';
ensureSessionStarted();
require_once 'Invoice.php';
require_once 'Quotation.php';

$invoice = new Invoice();
$invoice->checkLoggedIn();

$quotation = new Quotation();
$csrfToken = Quotation::csrfToken();
$formErrors = [];
$formMode = 'create';
$statusOptions = ['Draft', 'Sent'];
$actionUrl = 'create_quotation.php';

$quotationData = [
    'quotation_no' => $quotation->generateQuotationNumber(),
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
];
$formItems = [[]];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Quotation::validateCsrf($_POST['csrf_token'] ?? '')) {
        $formErrors[] = 'Your session security token has expired. Please refresh the page and try again.';
    } else {
        $prepared = $quotation->preparePayload($_POST);
        $formErrors = $prepared['errors'];

        // Keep the read-only number in sync if the server had to generate a
        // fresh one (for example, after a duplicate from another tab).
        if (!empty($prepared['data']['quotation_no'])) {
            $_POST['quotation_no'] = $prepared['data']['quotation_no'];
        }
        if (!empty($prepared['data']['status'])) {
            $_POST['status'] = $prepared['data']['status'];
        }

        if (empty($formErrors)) {
            try {
                $quotationId = $quotation->saveQuotation($_POST);
                header('Location: quotation_view.php?id=' . $quotationId . '&created=1');
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
        'status',
        'notes',
        'terms_conditions',
    ] as $field) {
        if (isset($_POST[$field]) && is_scalar($_POST[$field])) {
            $quotationData[$field] = (string) $_POST[$field];
        }
    }

    if ($quotationData['quotation_no'] === ''
        || !preg_match('/^[A-Za-z0-9\-\/]{3,50}$/', $quotationData['quotation_no'])) {
        $quotationData['quotation_no'] = $quotation->generateQuotationNumber(true);
    }

    $postedItems = Quotation::itemsFromPost($_POST);
    $formItems = empty($postedItems) ? [[]] : $postedItems;
}

$quotationForForm = $quotationData;

include('header.php');
?>
<title>Create Quotation</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/quotation.css">
</head>

<body class="quotation-body">
    <?php
    $quotation = $quotationForForm;
    include('quotation_form.php');
    ?>
    <?php include('footer.php'); ?>
