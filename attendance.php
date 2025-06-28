<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Docházka</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; }
        label, input, select, textarea { display: block; width: 100%; margin-bottom: 10px; }
        input[type="number"], input[type="date"], select, textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background-color: #4CAF50; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .hourly-rate { margin-top: 10px; font-weight: bold; }
    </style>
    <script>
        function updateHourlyRate() {
            var employeeId = document.getElementById("employee").value;
            var customerId = document.getElementById("customer").value;
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "get_hourly_rate.php?employee_id=" + employeeId + "&customer_id=" + customerId, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById("hourly_rate").innerText = "Hodinová sazba: " + xhr.responseText + " Kč";
                }
            };
            xhr.send();
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Zaznamenat docházku</h1>
        <form action="record_attendance.php" method="post">
            <label for="employee">Zaměstnanec:</label>
            <select name="employee_id" id="employee" onchange="updateHourlyRate()">
                <?php
                include 'db.php';
                $result = $conn->query("SELECT * FROM employees");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
            </select><br>

            <label for="customer">Zákazník:</label>
            <select name="customer_id" id="customer" onchange="updateHourlyRate()">
                <?php
                include 'db.php';
                $result = $conn->query("SELECT * FROM customers");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
            </select><br>

            <p id="hourly_rate" class="hourly-rate">Hodinová sazba: </p>

            <label for="work_description">Popis práce:</label>
            <textarea name="work_description" rows="4" cols="50"></textarea><br>

            <label for="hours_worked">Odpracované hodiny:</label>
            <input type="number" name="hours_worked" step="0.1"><br>

            <label for="date">Datum:</label>
            <input type="date" name="date"><br>

            <input type="submit" value="Zaznamenat docházku">
        </form>
    </div>
</body>
</html>