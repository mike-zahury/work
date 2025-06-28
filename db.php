<?php
$servername = "m33133";
$username = "zahu";
$password = "Michs64sd.";
$dbname = "customer_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>