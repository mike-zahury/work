<?php
include 'db.php';

// Uživatelské jméno a heslo pro administrátora
$username = 'spravce'; // Změňte dle potřeby
$password = password_hash('145876', PASSWORD_DEFAULT); // Změňte dle potřeby

// Připravení a provedení SQL dotazu pro přidání administrátora
$stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password);

if ($stmt->execute()) {
    echo "Admin added successfully.";
} else {
    echo "Error adding admin.";
}

$stmt->close();
$conn->close();
?>