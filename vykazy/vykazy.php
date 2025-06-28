<?php
session_start();

// Include database connection
include '../db.php';

// Include export to PDF and Excel functions
include 'export_to_pdf.php';
include 'export_to_excel.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if month is provided
if (!isset($_GET['month'])) {
    die("Prosím zadejte měsíc ve formátu YYYY-MM pomocí parametru ?month=YYYY-MM.");
}

$month = $_GET['month'];
$employee_id = $_GET['employee_id'];
$customer_id = $_GET['customer_id'];
$export_type = $_GET['export_type']; // Add export type parameter

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
    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);

        // First line of footer
        $additional_info = 'work.zahu.cz - docházkový systém';
        $this->Cell(0, 5, $additional_info, 0, 1, 'C'); // The '5' in the third parameter changes the cell height

        // Second line of footer
        $company_info = 'Zahu s.r.o. | Žitavská 56/50, Liberec | tel.: 776 188 697 | mail: servis@zahu.cz | IČ: 19957998 | datová schránka: bwgrwpe';
        $this->Cell(0, 5, $company_info, 0, 0, 'C'); // The '5' in the third parameter changes the cell height
    }
}

$pdf = new MYPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Employee Dashboard');
$pdf->SetTitle('Pracovní záznamy za ' . $month);
$pdf->SetSubject('Work Entries');
$pdf->SetKeywords('TCPDF, PDF, work, entries, export');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 10, 10, true);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Generate PDF or Excel for each employee and each customer
$generated = false;
while ($employee = $employees->fetch_assoc()) {
    $employee_id = $employee['id'];
    $employee_name = $employee['name'];
    while ($customer = $customers->fetch_assoc()) {
        $customer_id = $customer['id'];
        $customer_name = $customer['name'];
        $result = $conn->query("
            SELECT a.*, c.name AS customer_name, ecr.onsite_rate, ecr.remote_rate, ecr.travel_rate 
            FROM attendance a 
            JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN employee_customer_rate ecr 
            ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
            WHERE a.employee_id = $employee_id AND a.customer_id = $customer_id AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
            ORDER BY a.date DESC
        ");
        if ($result && $result->num_rows > 0) {
            if ($export_type == 'pdf') {
                export_to_pdf($conn, $employee_name, $customer_name, $month, $pdf);
            } elseif ($export_type == 'excel') {
                export_to_excel($conn, $employee_name, $customer_name, $month);
            }
            $generated = true;
        }
    }
    // Reset the customers result set pointer to the beginning
    $customers->data_seek(0);
}

if ($generated && $export_type == 'pdf') {
    // Output the PDF as a download
    $pdf->Output("vykazy_{$month}.pdf", 'D');
} elseif (!$generated) {
    echo "Žádná data k zobrazení.";
}

// Close the connection
$conn->close();
?>