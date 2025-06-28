<?php
session_start();

// Kontrola, zda je uživatel přihlášen a je employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("Location: login.php");
    exit();
}

// Nastavení timeoutu (15 minut)
$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Připojení k databázi
include 'db.php';

// Zahrnutí funkce pro export do PDF
include 'export_to_pdf.php';

// Zpracování zaznamenání nové práce
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_work'])) {
    $employee_id = $_SESSION['employee_id'];
    $customer_id = $_POST['customer_id'];
    $work_description = $_POST['work_description'];
    $hours_worked = $_POST['hours_worked'];
    $date = date('Y-m-d', strtotime($_POST['date']));
    $work_type = $_POST['work_type'];
    $include_travel = isset($_POST['include_travel']) ? 1 : 0;
    $material = $_POST['material'];
    $material_cost = $_POST['material_cost'];

    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, customer_id, work_description, hours_worked, date, work_type, include_travel, material, material_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdssdsd", $employee_id, $customer_id, $work_description, $hours_worked, $date, $work_type, $include_travel, $material, $material_cost);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Práce úspěšně zaznamenána.";
    } else {
        $_SESSION['error'] = "Chyba při zaznamenávání práce: " . $stmt->error;
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Zpracování exportu do PDF
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['export_to_pdf'])) {
    export_to_pdf($conn, $_SESSION['employee_id'], $_POST['month']);
}

// Načtení seznamu zákazníků
$customers = $conn->query("SELECT * FROM customers");
if (!$customers) {
    die("Chyba při načítání zákazníků: " . $conn->error);
}

// Načtení záznamů práce pro přihlášeného zaměstnance
$employee_id = $_SESSION['employee_id'];
if (empty($employee_id)) {
    die("Není nastaveno ID zaměstnance.");
} else {
    $unpaid_amount_result = $conn->query("
        SELECT COALESCE(SUM(a.hours_worked * 
            CASE 
                WHEN a.work_type = 'onsite' THEN IFNULL(ecr.onsite_rate, 0)
                WHEN a.work_type = 'remote' THEN IFNULL(ecr.remote_rate, 0)
            END
            + IF(a.include_travel, ecr.travel_rate, 0) + IFNULL(a.material_cost, 0)), 0) AS unpaid_amount 
        FROM attendance a 
        LEFT JOIN employee_customer_rate ecr 
        ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
        WHERE a.employee_id = $employee_id AND a.paid = 0
    ");
    $unpaid_amount = $unpaid_amount_result ? $unpaid_amount_result->fetch_assoc()['unpaid_amount'] : 0;

    $paid_amount_result = $conn->query("
        SELECT COALESCE(SUM(a.hours_worked * 
            CASE 
                WHEN a.work_type = 'onsite' THEN IFNULL(ecr.onsite_rate, 0)
                WHEN a.work_type = 'remote' THEN IFNULL(ecr.remote_rate, 0)
            END
            + IF(a.include_travel, ecr.travel_rate, 0) + IFNULL(a.material_cost, 0)), 0) AS paid_amount 
        FROM attendance a 
        LEFT JOIN employee_customer_rate ecr 
        ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
        WHERE a.employee_id = $employee_id AND a.paid = 1
    ");
    $paid_amount = $paid_amount_result ? $paid_amount_result->fetch_assoc()['paid_amount'] : 0;

    $available_months = $conn->query("SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') AS month FROM attendance WHERE employee_id = $employee_id ORDER BY month DESC");
    if (!$available_months) {
        die("Chyba při načítání dostupných měsíců: " . $conn->error);
    }

    $work_entries = null;
    if (isset($_GET['month'])) {
        $month = $_GET['month'];
        $sql = "
            SELECT a.*, c.name AS customer_name, ecr.onsite_rate, ecr.remote_rate, ecr.travel_rate, a.paid
            FROM attendance a 
            JOIN customers c ON a.customer_id = c.id 
            LEFT JOIN employee_customer_rate ecr 
            ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
            WHERE a.employee_id = $employee_id AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
            ORDER BY a.date DESC
        ";
        $work_entries = $conn->query($sql);
        if (!$work_entries) {
            die("Chyba při načítání záznamů práce: " . $conn->error . " -- SQL: $sql");
        }
    }
}

// Načtení informací o zaměstnanci
$employee_info = $conn->query("SELECT name, position, bankovni_ucet FROM employees WHERE id = $employee_id");
if (!$employee_info) {
    die("Chyba při načítání informací o zaměstnanci: " . $conn->error);
}
$employee = $employee_info->fetch_assoc();

$conn->close();

// Načtení verze z verze souboru
$version = trim(file_get_contents('version.txt'));
?>