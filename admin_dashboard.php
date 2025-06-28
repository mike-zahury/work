<?php include 'admin_dashboard_init.php'; ?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
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
        <h1>Admin Dashboard</h1>
        <h2>Vítejte, <?php echo $_SESSION['username']; ?></h2>

        <?php if (isset($_SESSION['success'])) { echo "<p class='success'>{$_SESSION['success']}</p>"; unset($_SESSION['success']); } ?>
        <?php if (isset($_SESSION['error'])) { echo "<p class='error'>{$_SESSION['error']}</p>"; unset($_SESSION['error']); } ?>

        <h3>Přidat nového zákazníka</h3>
        <form method="post" action="">
            <input type="hidden" name="add_customer">
            <label for="name">Jméno zákazníka:</label>
            <input type="text" name="name" required>
            <input type="submit" value="Přidat zákazníka">
        </form>

        <h3>Přidat nového zaměstnance</h3>
        <form method="post" action="">
            <input type="hidden" name="add_employee">
            <label for="name">Jméno zaměstnance:</label>
            <input type="text" name="name" required>
            <label for="position">Pozice:</label>
            <input type="text" name="position" required>
            <label for="username">Uživatelské jméno:</label>
            <input type="text" name="username" required>
            <label for="password">Heslo:</label>
            <input type="password" name="password" required>
            <input type="submit" value="Přidat zaměstnance">
        </form>

        <h3>Nastavit sazby zaměstnanci</h3>
        <form method="post" action="">
            <input type="hidden" name="set_rate">
            <label for="employee">Zaměstnanec:</label>
            <select name="employee_id">
                <?php 
                // Reset pointer and fetch all rows again
                $employees->data_seek(0);
                while ($row = $employees->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']} ({$row['username']})</option>";
                } 
                ?>
            </select>
            <label for="customer">Zákazník:</label>
            <select name="customer_id">
                <?php 
                // Reset pointer and fetch all rows again
                $customers->data_seek(0);
                while ($row = $customers->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                } 
                ?>
            </select>
            <label for="onsite_rate">Onsite sazba:</label>
            <input type="number" name="onsite_rate" step="0.01" required>
            <label for="remote_rate">Remote sazba:</label>
            <input type="number" name="remote_rate" step="0.01" required>
            <label for="travel_rate">Dopravné:</label>
            <input type="number" name="travel_rate" step="0.01" required>
            <input type="submit" value="Nastavit sazby">
        </form>

        <h3>Seznam zákazníků</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Jméno</th>
            </tr>
            <?php
            if ($customers->num_rows > 0) {
                // Reset pointer and fetch all rows again
                $customers->data_seek(0);
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
        </table>

        <!-- Button to open vykazy_form.php -->
        <div class="section">
            <h3>Generovat PDF výkazy</h3>
            <form action="vykazy/vykazy_form.php" method="get">
                <input type="submit" value="Otevřít formulář pro výkazy">
            </form>
        </div>

        <h3>Seznam zaměstnanců</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Jméno</th>
                <th>Pozice</th>
                <th>Bankovní účet</th>
                <th>Neproplacená částka</th>
            </tr>
            <?php
            if ($employees->num_rows > 0) {
                // Reset pointer and fetch all rows again
                $employees->data_seek(0);
                while ($row = $employees->fetch_assoc()) {
                    $employee_id = $row['id'];
                    $unpaid_amount = isset($unpaid_amounts[$employee_id]) ? $unpaid_amounts[$employee_id] : 0;
                ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><a href="?employee_id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
                        <td><?php echo $row['position']; ?></td>
                        <td><?php echo $row['bankovni_ucet']; ?></td>
                        <td><?php echo number_format($unpaid_amount, 2); ?> Kč</td>
                    </tr>
                <?php }
            } else {
                echo "<tr><td colspan='5'>Žádní zaměstnanci nenalezeni</td></tr>";
            }
            ?>
        </table>

        <?php if (isset($_GET['employee_id']) && !isset($_GET['month'])) { ?>
            <div class="month-selection">
                <h3>Vyberte měsíc pro zobrazení práce zaměstnance: <?php echo $selected_employee['name']; ?></h3>
                <form method="get" action="">
                    <input type="hidden" name="employee_id" value="<?php echo $_GET['employee_id']; ?>">
                    <label for="month">Měsíc:</label>
                    <select name="month" required>
                        <?php 
                        while ($row = $available_months->fetch_assoc()) {
                            echo "<option value='{$row['month']}'>{$row['month']}</option>";
                        }
                        ?>
                    </select>
                    <input type="submit" value="Zobrazit záznamy">
                </form>
            </div>
        <?php } ?>

        <?php if (isset($work_entries) && $work_entries->num_rows > 0) { ?>
            <div class="work-entries">
                <h3>Záznamy práce pro zaměstnance: <?php echo $selected_employee['name']; ?></h3>
                <table id="workTable" class="display">
                    <thead>
                        <tr>
                            <th>Zákazník</th>
                            <th>Datum</th>
                            <th>Popis práce</th>
                            <th>Materiál</th>
                            <th>Vydělané peníze</th>
                            <th>Zaplaceno</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $work_entries->fetch_assoc()) { 
                            $hourly_rate = ($row['work_type'] == 'onsite') ? $row['onsite_rate'] : $row['remote_rate'];
                            $travel_rate = $row['include_travel'] ? $row['travel_rate'] : 0;
                            $money_earned = ($hourly_rate * $row['hours_worked']) + $travel_rate + $row['material_cost'];
                            $paid_class = $row['paid'] ? 'paid' : '';
                            $paid_text = $row['paid'] ? 'Ano' : 'Ne';
                            $toggle_value = $row['paid'] ? 0 : 1;
                        ?>
                        <tr class="<?php echo $paid_class; ?>">
                            <td><?php echo $row['customer_name']; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($row['date'])); ?></td>
                            <td><?php echo $row['work_description']; ?></td>
                            <td><?php echo $row['material']; ?></td>
                            <td><?php echo $money_earned; ?> Kč</td>
                            <td class="paid-toggle" data-id="<?php echo $row['id']; ?>" data-employee="<?php echo $_GET['employee_id']; ?>" data-paid="<?php echo $toggle_value; ?>"><?php echo $paid_text; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> Zahu s.r.o.. Všechna práva vyhrazena. | Verze <?php echo $version; ?>
    </div>

    <script>
        $(document).ready(function () {
            $('#workTable').DataTable();

            $('.paid-toggle').click(function() {
                var attendance_id = $(this).data('id');
                var employee_id = $(this).data('employee');
                var paid = $(this).data('paid');

                $.post("", {
                    toggle_paid: true,
                    attendance_id: attendance_id,
                    employee_id: employee_id,
                    paid: paid
                }, function(response) {
                    location.reload();
                });
            });
        });
    </script>
</body>
</html>