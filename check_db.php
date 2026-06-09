<?php
$c = new mysqli('localhost', 'root', '', 'pcs_invoice');
if ($c->connect_error) {
    die("Connection failed: " . $c->connect_error);
}

$r = $c->query("SHOW COLUMNS FROM invoice_order LIKE 'gst_type'");
if ($r->num_rows == 0) {
    if ($c->query("ALTER TABLE invoice_order ADD gst_type VARCHAR(20) DEFAULT 'with_gst' AFTER currency")) {
        echo "Added gst_type\n";
    } else {
        echo "Error adding gst_type: " . $c->error . "\n";
    }
} else {
    echo "gst_type exists\n";
}

$r = $c->query("SHOW COLUMNS FROM invoice_order LIKE 'currency'");
if ($r->num_rows == 0) {
    if ($c->query("ALTER TABLE invoice_order ADD currency VARCHAR(10) DEFAULT 'INR' AFTER supply")) {
        echo "Added currency\n";
    } else {
        echo "Error adding currency: " . $c->error . "\n";
    }
} else {
    echo "currency exists\n";
}
?>
