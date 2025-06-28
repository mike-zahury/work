<?php
// Fetch all customers
include 'db.php';

$customers = $conn->query("SELECT * FROM customers");
if (!$customers) {
    die("Chyba při načítání zákazníků: " . $conn->error);
}

while ($row = $customers->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['name']}</option>";
}
?>