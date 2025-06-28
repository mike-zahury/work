<?php
session_start();

// Include database connection
include '../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch available months from attendance table
$available_months = $conn->query("SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') AS month FROM attendance ORDER BY month DESC");
if (!$available_months) {
    die("Chyba při načítání dostupných měsíců: " . $conn->error);
}

// Fetch all employees
$employees = $conn->query("SELECT id, name FROM employees");
if (!$employees) {
    die("Chyba při načítání zaměstnanců: " . $conn->error);
}

// Fetch all customers
$customers = $conn->query("SELECT id, name FROM customers");
if (!$customers) {
    die("Chyba při načítání zákazníků: " . $conn->error);
}

// Read the version number from the file
$version = trim(file_get_contents('../version.txt'));
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Výběr měsíce pro export</title>
    <link rel="stylesheet" type="text/css" href="../styles.css">
</head>
<body>
    <div class="container">
        <div class="logout-button">
            <form action="../admin_dashboard.php" method="get">
                <input type="submit" value="Zpět na přehled">
            </form>
        </div>
        <h1>Výběr měsíce pro export</h1>

        <form method="get" action="vykazy_unpaid.php">
            <label for="month">Vyberte měsíc:</label>
            <select name="month" required>
                <?php while ($row = $available_months->fetch_assoc()) { ?>
                    <option value="<?php echo $row['month']; ?>"><?php echo $row['month']; ?></option>
                <?php } ?>
            </select>

            <label for="employee">Vyberte zaměstnance:</label>
            <select name="employee_id">
                <option value="all">Všichni zaměstnanci</option>
                <?php while ($row = $employees->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                <?php } ?>
            </select>

            <label for="customer">Vyberte zákazníka:</label>
            <select name="customer_id">
                <option value="all">Všichni zákazníci</option>
                <?php while ($row = $customers->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                <?php } ?>
            </select>

            <label for="export_type">Vyberte typ exportu:</label>
            <select name="export_type" required>
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
            </select>

            <label for="unpaid_only">Pouze nezaplacené položky:</label>
            <input type="checkbox" name="unpaid_only" value="1">

            <input type="submit" value="Generovat">
        </form>
    </div>
    <div class="footer">
        &copy; <?php echo date("Y"); ?> Zahu s.r.o.. Všechna práva vyhrazena. | Verze <?php echo $version; ?>
    </div>
</body>
</html>

<?php
// Close the connection
$conn->close();
?>