<?php
session_start();

// Kontrola, zda je uživatel přihlášen a je admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Připojení k databázi
include 'db.php';

// Inicializace chybových a úspěšných zpráv
$error = '';
$success = '';

// Zpracování vytvoření nového zákazníka
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customer'])) {
    $name = $_POST['name'];

    $stmt = $conn->prepare("INSERT INTO customers (name) VALUES (?)");
    $stmt->bind_param("s", $name);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Nový zákazník úspěšně přidán.";
    } else {
        $_SESSION['error'] = "Chyba při přidávání zákazníka: " . $stmt->error;
    }

    $stmt->close();

    // Přesměrování, aby se zabránilo opětovnému odeslání formuláře
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Zpracování vytvoření nového zaměstnance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Vložení do tabulky zaměstnanců
    $stmt = $conn->prepare("INSERT INTO employees (name, position) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $position);
    if ($stmt->execute()) {
        $employee_id = $stmt->insert_id;
        // Vložení do tabulky uživatelů
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, employee_id) VALUES (?, ?, 'employee', ?)");
        $stmt->bind_param("ssi", $username, $password, $employee_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Nový zaměstnanec úspěšně přidán.";
        } else {
            $_SESSION['error'] = "Chyba při přidávání uživatelského účtu: " . $stmt->error;
        }
    } else {
        $_SESSION['error'] = "Chyba při přidávání zaměstnance: " . $stmt->error;
    }

    $stmt->close();

    // Přesměrování, aby se zabránilo opětovnému odeslání formuláře
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Zpracování nastavování hodinových sazeb zaměstnanci
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_rate'])) {
    $employee_id = $_POST['employee_id'];
    $customer_id = $_POST['customer_id'];
    $onsite_rate = $_POST['onsite_rate'];
    $remote_rate = $_POST['remote_rate'];
    $travel_rate = $_POST['travel_rate'];

    $stmt = $conn->prepare("REPLACE INTO employee_customer_rate (employee_id, customer_id, onsite_rate, remote_rate, travel_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiddi", $employee_id, $customer_id, $onsite_rate, $remote_rate, $travel_rate);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Sazby úspěšně nastaveny.";
    } else {
        $_SESSION['error'] = "Chyba při nastavování sazeb: " . $stmt->error;
    }

    $stmt->close();

    // Přesměrování, aby se zabránilo opětovnému odeslání formuláře
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Zpracování označování docházky jako zaplacené/nezaplacené
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_paid'])) {
    $attendance_id = $_POST['attendance_id'];
    $paid = $_POST['paid'];

    $stmt = $conn->prepare("UPDATE attendance SET paid = ? WHERE id = ?");
    $stmt->bind_param("ii", $paid, $attendance_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Stav platby úspěšně změněn.";
    } else {
        $_SESSION['error'] = "Chyba při změně stavu platby: " . $stmt->error;
    }

    $stmt->close();

    // Přesměrování, aby se zabránilo opětovnému odeslání formuláře
    header("Location: " . $_SERVER['PHP_SELF'] . "?employee_id=" . $_POST['employee_id'] . "&month=" . $_POST['month']);
    exit();
}

// Načtení všech zákazníků
$customers = $conn->query("SELECT * FROM customers");
if (!$customers) {
    $error = "Chyba při načítání zákazníků: " . $conn->error;
}

// Načtení všech zaměstnanců
$employees = $conn->query("SELECT e.*, u.username FROM employees e JOIN users u ON e.id = u.employee_id WHERE u.role = 'employee'");
if (!$employees) {
    $error = "Chyba při načítání zaměstnanců: " . $conn->error;
}

// Výpočet neproplacené částky pro každého zaměstnance
$unpaid_amounts = [];
while ($employee = $employees->fetch_assoc()) {
    $employee_id = $employee['id'];
    $unpaid_amount_sql = "
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
    ";
    $unpaid_amount_result = $conn->query($unpaid_amount_sql);
    if ($unpaid_amount_result) {
        $unpaid_amount_row = $unpaid_amount_result->fetch_assoc();
        $unpaid_amounts[$employee_id] = $unpaid_amount_row['unpaid_amount'];
    } else {
        $unpaid_amounts[$employee_id] = 0;
    }
}

// Načtení dostupných měsíců z docházkových záznamů
$available_months = $conn->query("SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') AS month FROM attendance ORDER BY month DESC");
if (!$available_months) {
    $error = "Chyba při načítání dostupných měsíců: " . $conn->error;
}

// Načtení záznamů práce pro konkrétního zaměstnance a měsíc, pokud jsou nastaveny employee_id a month
$work_entries = null;
$selected_employee = null;
if (isset($_GET['employee_id']) && isset($_GET['month'])) {
    $employee_id = $_GET['employee_id'];
    $month = $_GET['month'];
    $sql = "
        SELECT a.*, c.name AS customer_name, ecr.onsite_rate, ecr.remote_rate, ecr.travel_rate 
        FROM attendance a 
        JOIN customers c ON a.customer_id = c.id 
        LEFT JOIN employee_customer_rate ecr 
        ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
        WHERE a.employee_id = $employee_id AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
        ORDER BY a.date DESC
    ";
    $work_entries = $conn->query($sql);
    if (!$work_entries) {
        $error = "Chyba při načítání záznamů práce: " . $conn->error;
    }

    // Načtení údajů o vybraném zaměstnanci
    $selected_employee = $conn->query("SELECT * FROM employees WHERE id = $employee_id")->fetch_assoc();
}

$conn->close();

// Načtení verze z verze souboru
$version = trim(file_get_contents('version.txt'));
?>