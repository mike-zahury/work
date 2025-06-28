<?php
include 'db.php';

$employee_id = $_GET['employee_id'];
$customer_id = $_GET['customer_id'];

$stmt = $conn->prepare("SELECT hourly_rate FROM employee_customer_rate WHERE employee_id = ? AND customer_id = ?");
$stmt->bind_param("ii", $employee_id, $customer_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($hourly_rate);

if ($stmt->num_rows > 0) {
    $stmt->fetch();
    echo $hourly_rate;
} else {
    // Pokud není nastavena specifická sazba, vrátí se výchozí hodinová sazba zákazníka
    $stmt = $conn->prepare("SELECT hourly_rate FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hourly_rate);
    $stmt->fetch();
    echo $hourly_rate;
}

$stmt->close();
$conn->close();
?>