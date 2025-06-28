<?php
include 'db.php';

$employee_id = $_POST['employee_id'];
$customer_id = $_POST['customer_id'];
$work_description = $_POST['work_description'];
$hours_worked = $_POST['hours_worked'];
$date = $_POST['date'];

$sql = "INSERT INTO attendance (employee_id, customer_id, work_description, hours_worked, date) 
        VALUES ('$employee_id', '$customer_id', '$work_description', '$hours_worked', '$date')";

if ($conn->query($sql) === TRUE) {
    echo "Docházka úspěšně zaznamenána.";
} else {
    echo "Chyba: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>