<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'db.php';

// Check if the database connection was successful
if (!$conn) {
    die("Connection failed: Connection object is null");
} else {
    echo "Connection successful<br>";
    echo "Server info: " . $conn->server_info . "<br>";
}

// Fetch all employees with unpaid amount
$sql = "
    SELECT e.id, e.name, e.position, u.username, 
           COALESCE(SUM(a.hours_worked * IFNULL(ecr.hourly_rate, 0)), 0) AS unpaid_amount 
    FROM employees e 
    JOIN users u ON e.id = u.employee_id 
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.paid = 0 
    LEFT JOIN employee_customer_rate ecr ON e.id = ecr.employee_id AND a.customer_id = ecr.customer_id 
    WHERE u.role = 'employee' 
    GROUP BY e.id, e.name, e.position, u.username
";
$employees = $conn->query($sql);

if (!$employees) {
    die("Chyba při načítání zaměstnanců: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Peněžní přehled</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); position: relative; }
        h1, h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; text-align: center; }
        .error { color: red; text-align: center; }
        .logout-button { position: absolute; top: 20px; right: 20px; }
        .logout-button form { display: inline; }
        .logout-button input[type="submit"] { background-color: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .logout-button input[type="submit"]:hover { background-color: #d32f2f; }
    </style>
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</head>
<body>
    <div class="container">
        <div class="logout-button">
            <form method="post" action="logout.php">
                <input type="submit" value="Odhlásit se">
            </form>
        </div>
        <h1>Peněžní přehled</h1>
        <h2>Přehled nevyplacených částek zaměstnancům</h2>

        <?php
        if (isset($_SESSION['success'])) {
            echo "<p class='success'>{$_SESSION['success']}</p>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<p class='error'>{$_SESSION['error']}</p>";
            unset($_SESSION['error']);
        }
        ?>

        <table id="moneyTable" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Jméno</th>
                    <th>Pozice</th>
                    <th>Uživatelské jméno</th>
                    <th>Neproplacená částka</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $employees->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['position']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo number_format($row['unpaid_amount'], 2); ?> Kč</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready( function () {
            $('#moneyTable').DataTable();
        } );
    </script>
</body>
</html>