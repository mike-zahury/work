<?php include 'employee_dashboard_init.php'; ?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .container { max-width: 1200px; margin: 50px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); position: relative; }
        h1, h2, h3 { text-align: center; }
        form { margin-bottom: 20px; }
        label, input, select, textarea { display: block; width: 100%; margin-bottom: 10px; }
        input[type="text"], input[type="number"], input[type="date"], select, textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background-color: #4CAF50; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .success { color: green; text-align: center; }
        .error { color: red; text-align: center; }
        .work-entries { margin-top: 20px; }
        .work-entries h4 { margin-top: 20px; }
        .work-entry { padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; background-color: #f9f9f9; }
        .logout-button { position: absolute; top: 20px; right: 20px; }
        .logout-button form { display: inline; }
        .logout-button input[type="submit"] { background-color: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .logout-button input[type="submit"]:hover { background-color: #d32f2f; }
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #4CAF50; }
        input:checked + .slider:before { transform: translateX(26px); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #777; }
        .paid { text-decoration: line-through; }
        .link-section { margin-top: 20px; text-align: center; }
        .link-section a { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; }
        .link-section a:hover { background-color: #45a049; }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="logout-button">
            <form method="post" action="logout.php">
                <input type="submit" value="Odhlásit se">
            </form>
        </div>
        <h2><?php echo $employee['name']; ?></h2>

        <!-- Zobrazení informací o zaměstnanci -->
        <h3>Vaše pozice: <?php echo $employee['position']; ?></h3>
        <h3>Bankovní účet: <?php echo $employee['bankovni_ucet']; ?></h3>

        <h3>Celková nevyplacená částka: <?php echo number_format($unpaid_amount, 2); ?> Kč</h3>
        <h3>Celková vyplacená částka: <?php echo number_format($paid_amount, 2); ?> Kč</h3>

        <h3>Zaznamenat práci</h3>
        <?php if (isset($_SESSION['success'])) { echo "<p class='success'>{$_SESSION['success']}</p>"; unset($_SESSION['success']); } ?>
        <?php if (isset($_SESSION['error'])) { echo "<p class='error'>{$_SESSION['error']}</p>"; unset($_SESSION['error']); } ?>
        <form method="post" action="">
            <input type="hidden" name="add_work">
            <label for="customer">Zákazník:</label>
            <select name="customer_id" required>
                <?php while ($row = $customers->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                <?php } ?>
            </select><br>

            <label for="work_description">Popis práce:</label>
            <textarea name="work_description" rows="4" required></textarea><br>

            <label for="hours_worked">Odpracované hodiny:</label>
            <input type="number" name="hours_worked" step="0.1" required><br>

            <label for="work_type">Typ práce:</label>
            <select name="work_type" required>
                <option value="onsite">Onsite</option>
                <option value="remote">Remote</option>
            </select><br>

            <label for="include_travel">Zahrnout dopravné:</label>
            <label class="switch">
                <input type="checkbox" name="include_travel">
                <span class="slider"></span>
            </label><br>

            <label for="material">Materiál:</label>
            <textarea name="material" rows="2"></textarea><br>

            <label for="material_cost">Cena materiálu:</label>
            <input type="number" name="material_cost" step="0.01"><br>

            <label for="date">Datum:</label>
            <input type="date" name="date" required><br>

            <input type="submit" value="Zaznamenat práci">
        </form>

        <div class="work-entries">
            <h3>Záznamy práce</h3>
            <form method="get" action="">
                <label for="month">Měsíc:</label>
                <select name="month" required>
                    <?php while ($row = $available_months->fetch_assoc()) { ?>
                        <option value="<?php echo $row['month']; ?>"><?php echo $row['month']; ?></option>
                    <?php } ?>
                </select>
                <input type="submit" value="Zobrazit záznamy">
            </form>

            <?php if ($work_entries && $work_entries->num_rows > 0) { ?>
                <div style="overflow-x:auto;">
                    <table id="workTable" class="display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Zákazník</th>
                                <th>Datum</th>
                                <th>Popis práce</th>
                                <th>Zaplaceno</th>
                                <th>Odpracované hodiny</th>
                                <th>Typ práce</th>
                                <th>Doprava</th>
                                <th>Vydělané peníze</th>
                                <th>Materiál</th>
                                <th>Cena materiálu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $work_entries->fetch_assoc()) { 
                                $hourly_rate = ($row['work_type'] == 'onsite') ? $row['onsite_rate'] : $row['remote_rate'];
                                $travel_rate = $row['include_travel'] ? $row['travel_rate'] : 0;
                                $money_earned = ($hourly_rate * $row['hours_worked']) + $travel_rate + $row['material_cost'];
                                $paid_class = $row['paid'] ? 'paid' : '';
                            ?>
                            <tr class="<?php echo $paid_class; ?>">
                                <td><?php echo $row['customer_name']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($row['date'])); ?></td>
                                <td><?php echo $row['work_description']; ?></td>
                                <td><?php echo $row['paid'] ? 'Ano' : 'Ne'; ?></td>
                                <td><?php echo $row['hours_worked']; ?></td>
                                <td><?php echo ucfirst($row['work_type']); ?></td>
                                <td><?php echo $row['include_travel'] ? $row['travel_rate'] . ' Kč' : 'Ne'; ?></td>
                                <td><?php echo $money_earned; ?> Kč</td>
                                <td><?php echo $row['material']; ?></td>
                                <td><?php echo $row['material_cost']; ?> Kč</td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" action="">
                    <input type="hidden" name="export_to_pdf">
                    <input type="hidden" name="month" value="<?php echo $_GET['month']; ?>">
                    <input type="submit" value="Exportovat do PDF">
                </form>
            <?php } ?>
        </div>

        <!-- Odkaz na externí stránku se seznamem zákazníků -->
        <div class="link-section">
            <h3>Seznam zákazníků</h3>
            <a href="customer_list.php">Zobrazit seznam zákazníků</a>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> Zahu s.r.o.. Všechna práva vyhrazena. | Verze <?php echo $version; ?>
    </div>

    <script>
        $(document).ready(function () {
            $('#workTable').DataTable({
                responsive: true,
                dom: 'lrtip' // Remove the search box
            });
        });
    </script>
</body>
</html>