<?php
class Invoice
{
    private $host = 'localhost';
    private $user = 'root';
    private $password = "";
    private $database = "pcs_invoice";
    private $invoiceUserTable = 'invoice_user';
    private $invoiceOrderTable = 'invoice_order';
    private $invoiceOrderItemTable = 'invoice_order_item';
    private $dbConnect = false;

    //Connect
    public function __construct()
    {
        if (!$this->dbConnect) {
            $conn = new mysqli($this->host, $this->user, $this->password, $this->database);
            if ($conn->connect_error) {
                die("Error failed to connect to MySQL: " . $conn->connect_error);
            } else {
                $this->dbConnect = $conn;
            }
        }
    }
    private function getData($sqlQuery)
    {
        $result = mysqli_query($this->dbConnect, $sqlQuery);
        if (!$result) {
            trigger_error('Error in query: ' . mysqli_error($this->dbConnect) . ' SQL: ' . $sqlQuery, E_USER_WARNING);
            return [];
        }
        $data = [];
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    private function getNumRows($sqlQuery)
    {
        $result = mysqli_query($this->dbConnect, $sqlQuery);
        if (!$result) {
            trigger_error('Error in query: ' . mysqli_error($this->dbConnect) . ' SQL: ' . $sqlQuery, E_USER_WARNING);
            return 0;
        }
        return mysqli_num_rows($result);
    }

    private function getCurrentUserId()
    {
        return isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 0;
    }

    // User login
    public function loginUsers($email, $password)
    {
        $sqlQuery = "
            SELECT id, email, first_name, last_name, address, mobile 
            FROM " . $this->invoiceUserTable . " 
            WHERE email='" . $email . "' AND password='" . md5($password) . "'";
        return $this->getData($sqlQuery);
    }

    // After Loggedin
    public function checkLoggedIn()
    {
        if (empty($_SESSION['userId'])) {
            header('Location:index.php');
            exit;
        }
    }

    private function normalizeCurrency($currency)
    {
        $currency = strtoupper(trim((string) $currency));
        return $currency === 'USD' ? 'USD' : 'INR';
    }

    private function normalizeGstType($gstType, $currency)
    {
        $gstType = trim((string) $gstType);
        if ($currency !== 'INR') {
            return 'without_gst';
        }
        return $gstType === 'without_gst' ? 'without_gst' : 'with_gst';
    }

    private function sanitizeString($value)
    {
        return mysqli_real_escape_string($this->dbConnect, trim((string) $value));
    }

    private function parseInvoiceItems($POST)
    {
        $items = [];
        $productCodes = $POST['productCode'] ?? [];
        $productNames = $POST['productName'] ?? [];
        $prices = $POST['price'] ?? [];
        $taxableValues = $POST['taxableValue'] ?? [];

        $rows = max(count($productCodes), count($productNames), count($prices), count($taxableValues));
        for ($i = 0; $i < $rows; $i++) {
            $name = trim((string) ($productNames[$i] ?? ''));
            $code = trim((string) ($productCodes[$i] ?? ''));
            $price = floatval($prices[$i] ?? 0);
            $taxableValue = floatval($taxableValues[$i] ?? 0);

            if ($taxableValue <= 0 && $price > 0) {
                $taxableValue = $price;
            }

            if ($name === '' && $code === '' && $price <= 0 && $taxableValue <= 0) {
                continue;
            }

            $items[] = [
                'name' => $name,
                'hsn_sac' => $code,
                'price' => $price,
                'taxable_value' => $taxableValue,
            ];
        }

        return $items;
    }

    private function calculateTotals($items, $currency, $gstType)
    {
        $rate = 0;
        foreach ($items as $item) {
            $rate += floatval($item['taxable_value']);
        }

        $result = [
            'rate' => round($rate, 2),
            'cgst_tax' => 0,
            'sgst_tax' => 0,
            'cgst_amount' => 0,
            'sgst_amount' => 0,
            'total' => round($rate, 2),
        ];

        if ($currency === 'INR' && $gstType === 'with_gst') {
            $result['cgst_tax'] = 9;
            $result['sgst_tax'] = 9;
            $result['cgst_amount'] = round($rate * 0.09, 2);
            $result['sgst_amount'] = round($rate * 0.09, 2);
            $result['total'] = round($rate + $result['cgst_amount'] + $result['sgst_amount'], 2);
        }

        return $result;
    }

    // Create and Save 
    public function saveInvoice($POST)
    {
        $currency = $this->normalizeCurrency($POST['currency'] ?? 'INR');
        $gstType = $this->normalizeGstType($POST['gst_type'] ?? 'with_gst', $currency);

        $items = $this->parseInvoiceItems($POST);
        $totals = $this->calculateTotals($items, $currency, $gstType);

        $userId = intval($POST['userId'] ?? 0);
        $receiverName = $this->sanitizeString($POST['receiverName'] ?? '');
        $receiverAddress = $this->sanitizeString($POST['receiverAddress'] ?? '');
        $gstNo = $this->sanitizeString($POST['gstNo'] ?? '');
        $panNo = $this->sanitizeString($POST['panNo'] ?? '');
        $supply = $this->sanitizeString($POST['supply'] ?? '');
        $note = $this->sanitizeString($POST['notes'] ?? '');

        $cgstAmount = $totals['cgst_amount'] === null ? 'NULL' : $totals['cgst_amount'];
        $sgstAmount = $totals['sgst_amount'] === null ? 'NULL' : $totals['sgst_amount'];
        $cgstTax = $totals['cgst_tax'] === null ? 'NULL' : $totals['cgst_tax'];
        $sgstTax = $totals['sgst_tax'] === null ? 'NULL' : $totals['sgst_tax'];
        $vatTax = 'NULL';

        $sqlInsert = "INSERT INTO " . $this->invoiceOrderTable . " 
            (user_id, receiver_name, receiver_address, gst_no, pan_no, supply, currency, gst_type, rate, 
            cgst_amount, cgst_tax, sgst_amount, sgst_tax, vat_tax, total, note)
            VALUES (
                $userId,
                '$receiverName',
                '$receiverAddress',
                '$gstNo',
                '$panNo',
                '$supply',
                '$currency',
                '$gstType',
                {$totals['rate']},
                $cgstAmount,
                $cgstTax,
                $sgstAmount,
                $sgstTax,
                $vatTax,
                {$totals['total']},
                '$note'
            )";

        mysqli_query($this->dbConnect, $sqlInsert);
        $lastInsertId = mysqli_insert_id($this->dbConnect);

        $receiverInitials = '';
        foreach (explode(' ', strtoupper($receiverName)) as $word) {
            if ($word !== '') {
                $receiverInitials .= $word[0];
            }
        }
        $receiverCode = $receiverInitials . str_pad($lastInsertId, 3, '0', STR_PAD_LEFT);

        $updateQuery = "UPDATE " . $this->invoiceOrderTable . " SET receiver_id = '$receiverCode' WHERE id = '$lastInsertId'";
        mysqli_query($this->dbConnect, $updateQuery);

        foreach ($items as $item) {
            $itemName = $this->sanitizeString($item['name']);
            $itemCode = $this->sanitizeString($item['hsn_sac']);
            $itemPrice = floatval($item['price']);
            $itemTaxable = floatval($item['taxable_value']);

            $sqlInsertItem = "INSERT INTO " . $this->invoiceOrderItemTable . "
                (order_id, hsn_sac, name, price, taxable_value)
                VALUES ($lastInsertId, '$itemCode', '$itemName', $itemPrice, $itemTaxable)";
            mysqli_query($this->dbConnect, $sqlInsertItem);
        }
    }

    // Edit and Update(Save)
    public function updateInvoice($POST)
    {
        if (empty($POST['invoiceId'])) {
            echo "❌ Missing invoice ID.";
            return false;
        }

        $invoiceId = intval($POST['invoiceId']);
        $currency = $this->normalizeCurrency($POST['currency'] ?? 'INR');
        $gstType = $this->normalizeGstType($POST['gst_type'] ?? 'with_gst', $currency);

        $items = $this->parseInvoiceItems($POST);
        $totals = $this->calculateTotals($items, $currency, $gstType);

        $userId = intval($POST['userId'] ?? 0);
        $receiverName = $this->sanitizeString($POST['receiverName'] ?? '');
        $receiverAddress = $this->sanitizeString($POST['receiverAddress'] ?? '');
        $gstNo = $this->sanitizeString($POST['gstNo'] ?? '');
        $panNo = $this->sanitizeString($POST['panNo'] ?? '');
        $supply = $this->sanitizeString($POST['supply'] ?? '');
        $note = $this->sanitizeString($POST['notes'] ?? '');

        $receiverInitials = '';
        foreach (explode(' ', strtoupper($receiverName)) as $word) {
            if ($word !== '') {
                $receiverInitials .= $word[0];
            }
        }
        $receiverId = $receiverInitials . str_pad($invoiceId, 3, '0', STR_PAD_LEFT);

        $cgstAmount = $totals['cgst_amount'] === null ? 'NULL' : $totals['cgst_amount'];
        $sgstAmount = $totals['sgst_amount'] === null ? 'NULL' : $totals['sgst_amount'];
        $cgstTax = $totals['cgst_tax'] === null ? 'NULL' : $totals['cgst_tax'];
        $sgstTax = $totals['sgst_tax'] === null ? 'NULL' : $totals['sgst_tax'];
        $vatTax = 'NULL';

        $sqlUpdate = "UPDATE {$this->invoiceOrderTable} SET 
                user_id = $userId,
                receiver_id = '$receiverId',
                receiver_name = '$receiverName',
                receiver_address = '$receiverAddress',
                gst_no = '$gstNo',
                pan_no = '$panNo',
                supply = '$supply',
                currency = '$currency',
                gst_type = '$gstType',
                rate = {$totals['rate']},
                cgst_tax = $cgstTax,
                sgst_tax = $sgstTax,
                cgst_amount = $cgstAmount,
                sgst_amount = $sgstAmount,
                vat_tax = $vatTax,
                total = {$totals['total']},
                note = '$note'
            WHERE id = $invoiceId";

        if (!mysqli_query($this->dbConnect, $sqlUpdate)) {
            echo "❌ Update failed: " . mysqli_error($this->dbConnect);
            return false;
        }

        $this->deleteInvoiceItems($invoiceId);

        foreach ($items as $item) {
            $itemName = $this->sanitizeString($item['name']);
            $itemCode = $this->sanitizeString($item['hsn_sac']);
            $itemPrice = floatval($item['price']);
            $itemTaxable = floatval($item['taxable_value']);

            $sqlInsertItem = "INSERT INTO {$this->invoiceOrderItemTable} 
                (order_id, name, hsn_sac, price, taxable_value)
                VALUES ($invoiceId, '$itemName', '$itemCode', $itemPrice, $itemTaxable)";

            if (!mysqli_query($this->dbConnect, $sqlInsertItem)) {
                echo "❌ Item insert failed: " . mysqli_error($this->dbConnect);
                return false;
            }
        }

        return true;
    }

    public function getInvoiceList($filters = [], $page = 1, $perPage = 6)
    {
        $userId = $_SESSION['userId'];
        $whereClauses = ["user_id = '$userId'"];

        if (!empty($filters['month'])) {
            $month = intval($filters['month']);
            if ($month >= 1 && $month <= 12) {
                $whereClauses[] = "MONTH(created_at) = $month";
            }
        }

        if (!empty($filters['date'])) {
            $date = trim($filters['date']);
            $dateObject = DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObject && $dateObject->format('Y-m-d') === $date) {
                $whereClauses[] = "DATE(created_at) = '$date'";
            }
        }

        if (!empty($filters['currency'])) {
            $currency = strtoupper(trim($filters['currency']));
            if (in_array($currency, ['INR', 'USD'])) {
                $whereClauses[] = "currency = '$currency'";
            }
        }

        if (!empty($filters['gst_type']) && (!empty($filters['currency']) && strtoupper($filters['currency']) === 'INR')) {
            $gstType = strtolower(trim($filters['gst_type']));
            if (in_array($gstType, ['with_gst', 'without_gst'])) {
                $whereClauses[] = "gst_type = '$gstType'";
            }
        }

        $where = implode(' AND ', $whereClauses);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM {$this->invoiceOrderTable} WHERE $where";
        $countResult = $this->getData($countQuery);
        $total = $countResult[0]['total'] ?? 0;

        // Get paged data
        $offset = ($page - 1) * $perPage;
        $sqlQuery = "SELECT * FROM {$this->invoiceOrderTable} WHERE $where ORDER BY created_at DESC LIMIT $offset, $perPage";
        $invoices = $this->getData($sqlQuery);

        return [
            'invoices' => $invoices,
            'total' => $total,
            'totalPages' => ceil($total / $perPage),
            'currentPage' => $page
        ];
    }
    
    public function getInvoice($invoiceId)
    {
        $userId = $this->getCurrentUserId();
        $invoiceId = intval($invoiceId);
        if ($userId === 0 || $invoiceId === 0) {
            return [];
        }

        $sqlQuery = "SELECT * FROM {$this->invoiceOrderTable} WHERE user_id = '$userId' AND id = '$invoiceId'";
        $result = mysqli_query($this->dbConnect, $sqlQuery);
        if (!$result) {
            trigger_error('Error in getInvoice: ' . mysqli_error($this->dbConnect), E_USER_WARNING);
            return [];
        }
        return mysqli_fetch_array($result, MYSQLI_ASSOC) ?: [];
    }
    
    public function getInvoiceItems($invoiceId)
    {
        $sqlQuery = "SELECT * FROM {$this->invoiceOrderItemTable} WHERE order_id = '$invoiceId'";
        return $this->getData($sqlQuery);
    }

    public function deleteInvoiceItems($invoiceId)
    {
        $invoiceId = intval($invoiceId);
        $sqlQuery = "DELETE FROM {$this->invoiceOrderItemTable} WHERE order_id = '$invoiceId'";
        $result = mysqli_query($this->dbConnect, $sqlQuery);
        if (!$result) {
            die("Delete Error: " . mysqli_error($this->dbConnect));
        }
    }
    
    public function deleteInvoice($invoiceId)
    {
        $invoiceId = intval($invoiceId);
        $this->deleteInvoiceItems($invoiceId);
        $sqlQuery = "DELETE FROM {$this->invoiceOrderTable} WHERE id = '$invoiceId'";
        $result = mysqli_query($this->dbConnect, $sqlQuery);
        return $result ? 1 : 0;
    }

    // 📊 For Dashboard 
    public function getTotalInvoices()
    {
        $userId = $this->getCurrentUserId();
        $sql = "SELECT COUNT(*) as total FROM {$this->invoiceOrderTable} WHERE user_id = '$userId'";
        $result = mysqli_query($this->dbConnect, $sql);
        if (!$result) {
            trigger_error('Error in getTotalInvoices: ' . mysqli_error($this->dbConnect), E_USER_WARNING);
            return 0;
        }
        return intval(mysqli_fetch_assoc($result)['total'] ?? 0);
    }

    public function getTotalCustomers()
    {
        $userId = $this->getCurrentUserId();
        $sql = "SELECT COUNT(DISTINCT receiver_name) as total_customers FROM {$this->invoiceOrderTable} WHERE user_id = '$userId'";
        $result = mysqli_query($this->dbConnect, $sql);
        if (!$result) {
            trigger_error('Error in getTotalCustomers: ' . mysqli_error($this->dbConnect), E_USER_WARNING);
            return 0;
        }
        return intval(mysqli_fetch_assoc($result)['total_customers'] ?? 0);
    }

    public function getTotalBilledAmount()
    {
        $userId = $this->getCurrentUserId();
        $sql = "SELECT SUM(total) as total_billed FROM {$this->invoiceOrderTable} WHERE user_id = '$userId'";
        $result = mysqli_query($this->dbConnect, $sql);
        if (!$result) {
            trigger_error('Error in getTotalBilledAmount: ' . mysqli_error($this->dbConnect), E_USER_WARNING);
            return 0;
        }
        return floatval(mysqli_fetch_assoc($result)['total_billed'] ?? 0);
    }

    public function getInvoicesByCurrency()
    {
        $userId = $this->getCurrentUserId();
        $currencyStats = ['INR' => 0, 'USD' => 0];
        $sql = "SELECT currency, COUNT(*) as count FROM {$this->invoiceOrderTable} WHERE user_id = '$userId' GROUP BY currency";
        $result = mysqli_query($this->dbConnect, $sql);
        if (!$result) {
            trigger_error('Error in getInvoicesByCurrency: ' . mysqli_error($this->dbConnect), E_USER_WARNING);
            return $currencyStats;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $key = strtoupper(trim((string)($row['currency'] ?? '')));
            if ($key === 'USD' || $key === 'INR') {
                $currencyStats[$key] = intval($row['count']);
            }
        }
        return $currencyStats;
    }

    public function getRecentInvoices($limit = 3)
    {
        $userId = $this->getCurrentUserId();
        $limit = intval($limit);
        if ($limit <= 0) {
            $limit = 3;
        }
        $sql = "SELECT * FROM {$this->invoiceOrderTable} WHERE user_id = '$userId' ORDER BY created_at DESC LIMIT $limit";
        return $this->getData($sql);
    }

    public function getTopReceivers($limit = 3)
    {
        $userId = $this->getCurrentUserId();
        $limit = intval($limit);
        if ($limit <= 0) {
            $limit = 3;
        }
        $sql = "SELECT receiver_name, COUNT(*) as count FROM {$this->invoiceOrderTable} WHERE user_id = '$userId' GROUP BY receiver_name ORDER BY count DESC LIMIT $limit";
        return $this->getData($sql);
    }

    public function getGraphData($days = 10)
    {
        $userId = $this->getCurrentUserId();
        $days = intval($days);
        if ($days <= 0) {
            $days = 10;
        }
        $sql = "SELECT DATE(created_at) as date, SUM(total) as total FROM {$this->invoiceOrderTable} WHERE user_id = '$userId' GROUP BY DATE(created_at) ORDER BY date DESC LIMIT $days";
        return $this->getData($sql);
    }
}
?>