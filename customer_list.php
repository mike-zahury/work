<?php
session_start();

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Připojení k databázi
include 'db.php';

// Načtení seznamu zákazníků
$customers = $conn->query("SELECT * FROM customers");
if (!$customers) {
    die("Chyba při načítání zákazníků: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Seznam zákazníků</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; color: #4CAF50; }
        a:hover { text-decoration: underline; }
        .back-to-dashboard { position: absolute; top: 20px; right: 20px; }
        .back-to-dashboard form { display: inline; }
        .back-to-dashboard input[type="submit"] { background-color: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .back-to-dashboard input[type="submit"]:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Zelené tlačítko Zpět na dashboard nahoře -->
        <div class="back-to-dashboard">
            <form method="get" action="employee_dashboard.php">
                <input type="submit" value="Zpět na dashboard">
            </form>
        </div>

        <h1>Seznam zákazníků</h1>

        <table id="customerTable" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Jméno</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($customers->num_rows > 0) {
                    while ($row = $customers->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><a href="customer_detail.php?id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
                        </tr>
                    <?php }
                } else {
                    echo "<tr><td colspan='2'>Žádní zákazníci nenalezeni</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> Zahu s.r.o.. Všechna práva vyhrazena.
    </div>

    <script>
        $(document).ready(function () {
            $('#customerTable').DataTable();
        });
    </script>
</body>
</html>