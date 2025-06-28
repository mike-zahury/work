<?php
session_start();

// Include database connection
include '../db.php';

// Include export to PDF function
include 'export_to_pdf.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if month is provided
if (!isset($_GET['month'])) {
    die("Prosím zadejte měsíc ve formátu YYYY-MM pomocí parametru ?month=YYYY-MM.");
}

// Get query parameters
$month = $_GET['month'];
$employee_id = $_GET['employee_id'];
$customer_id = $_GET['customer_id'];
$export_type = $_GET['export_type'];
$unpaid_only = isset($_GET['unpaid_only']) ? true : false;

// Fetch all employees if 'all' is selected, otherwise fetch specific employee
$employees_query = $employee_id === 'all' ? "SELECT id, name FROM employees" : "SELECT id, name FROM employees WHERE id = $employee_id";
$employees = $conn->query($employees_query);
if (!$employees) {
    die("Chyba při načítání zaměstnanců: " . $conn->error);
}

// Fetch all customers if 'all' is selected, otherwise fetch specific customer
$customers_query = $customer_id === 'all' ? "SELECT id, name FROM customers" : "SELECT id, name FROM customers WHERE id = $customer_id";
$customers = $conn->query($customers_query);
if (!$customers) {
    die("Chyba při načítání zákazníků: " . $conn->error);
}

// Initialize PDF
require_once('../tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->Cell(0, 5, 'work.zahu.cz - docházkový systém', 0, 1, 'C');
        $this->Cell(0, 5, 'Zahu s.r.o. | Žitavská 56/50, Liberec | tel.: 776 188 697 | mail: servis@zahu.cz', 0, 0, 'C');
    }
}

$pdf = new MYPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Employee Dashboard');
$pdf->SetTitle('Pracovní záznamy za ' . $month);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(10, 10, 10, true);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$generated = false;

// Loop through employees and customers and fetch data
while ($employee = $employees->fetch_assoc()) {
    $employee_id = $employee['id'];
    $employee_name = $employee['name'];
    while ($customer = $customers->fetch_assoc()) {
        $customer_id = $customer['id'];
        $customer_name = $customer['name'];

        // Query for fetching attendance data
        $query = "
            SELECT a.*, c.name AS customer_name, ecr.onsite_rate, ecr.remote_rate, ecr.travel_rate 
            FROM attendance a 
            JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN employee_customer_rate ecr 
            ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
            WHERE a.employee_id = $employee_id 
            AND a.customer_id = $customer_id 
            AND DATE_FORMAT(a.date, '%Y-%m') = '$month'";
        
        // Add filter for unpaid items if selected
        if ($unpaid_only) {
            $query .= " AND a.paid = 0"; // Filter for unpaid items
        }

        $query .= " ORDER BY a.date DESC";
        $result = $conn->query($query);

        // Check if there are results and generate export
        if ($result && $result->num_rows > 0) {
            export_to_pdf($result, $employee_name, $customer_name, $month, $pdf);
            $generated = true;
        }
    }
    // Reset the customers result set pointer to the beginning
    $customers->data_seek(0);
}

// Output or display message if no data found
if ($generated) {
    $pdf->Output("vykazy_{$month}.pdf", 'D');
} else {
    echo "Žádná data k zobrazení.";
}

// Close the connection
$conn->close();
?>