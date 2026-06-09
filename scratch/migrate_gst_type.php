<?php
$host = 'localhost';
$user = 'root';
$password = "";
$database = "pcs_invoice";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SHOW COLUMNS FROM invoice_order LIKE 'gst_type'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE invoice_order ADD COLUMN gst_type VARCHAR(20) DEFAULT 'with_gst' AFTER currency";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'gst_type' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'gst_type' already exists.";
}

$conn->close();
?>
