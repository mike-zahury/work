<?php
session_start();

// Kontrola přihlášení uživatele
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Připojení k databázi
include 'db.php';

// Inicializace chybových a úspěšných zpráv
$error = '';
$success = '';

// Kontrola, zda je nastaveno ID zákazníka
if (!isset($_GET['id'])) {
    header("Location: employee_dashboard.php");
    exit();
}

$customer_id = $_GET['id'];

// Načtení detailů zákazníka
$stmt = $conn->prepare("SELECT name, address, phone, www, text, sazba FROM customers WHERE id = ?");
if (!$stmt) {
    die("Příprava dotazu selhala: " . $conn->error);
}
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    $_SESSION['error'] = "Zákazník nebyl nalezen.";
    header("Location: employee_dashboard.php");
    exit();
}

// Zpracování editace pole "text" (pouze admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_text']) && $_SESSION['role'] == 'admin') {
    $edited_text = $_POST['text'];

    $stmt = $conn->prepare("UPDATE customers SET text = ? WHERE id = ?");
    if (!$stmt) {
        die("Příprava dotazu selhala: " . $conn->error);
    }
    $stmt->bind_param("si", $edited_text, $customer_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Popis zákazníka byl úspěšně aktualizován.";
        $customer['text'] = $edited_text; // Aktualizace lokální proměnné
    } else {
        $_SESSION['error'] = "Chyba při aktualizaci popisu: " . $stmt->error;
    }

    $stmt->close();
}

// Zpracování editace pole "sazba" (pouze admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_sazba']) && $_SESSION['role'] == 'admin') {
    $edited_sazba = $_POST['sazba'];

    $stmt = $conn->prepare("UPDATE customers SET sazba = ? WHERE id = ?");
    if (!$stmt) {
        die("Příprava dotazu selhala: " . $conn->error);
    }
    $stmt->bind_param("si", $edited_sazba, $customer_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Sazba zákazníka byla úspěšně aktualizována.";
        $customer['sazba'] = $edited_sazba; // Aktualizace lokální proměnné
    } else {
        $_SESSION['error'] = "Chyba při aktualizaci sazby: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();

// Funkce pro vytváření klikacích odkazů
function makeClickableLinks($text) {
    return preg_replace(
        '~[a-z]+://\S+~',
        '<a href="$0" target="_blank">$0</a>',
        $text
    );
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Detail zákazníka</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); position: relative; }
        h1, h2, h3 { text-align: center; }
        form { margin-bottom: 20px; }
        label, textarea, input[type="text"] { display: block; width: 100%; margin-bottom: 10px; }
        textarea, input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background-color: #4CAF50; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .success { color: green; text-align: center; }
        .error { color: red; text-align: center; }
        .note { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .back-to-list { position: absolute; top: 20px; right: 20px; }
        .back-to-list form { display: inline; }
        .back-to-list input[type="submit"] { background-color: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .back-to-list input[type="submit"]:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Zelené tlačítko Zpět na detail zákazníků nahoře -->
        <div class="back-to-list">
            <form method="get" action="customer_list.php">
                <input type="submit" value="Zpět na detail zákazníků">
            </form>
        </div>
        
        <h1>Detail zákazníka</h1>

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

        <h2><?php echo $customer['name']; ?></h2>
        <p>Adresa: <?php echo nl2br($customer['address']); ?></p>
        <p>Telefon: <?php echo nl2br($customer['phone']); ?></p>
        <p>WWW: <?php echo makeClickableLinks($customer['www']); ?></p><br>

        <!-- Editace pole "sazba" -->
        <?php if ($_SESSION['role'] == 'admin') { ?>
            <form method="post" action="">
                <input type="hidden" name="edit_sazba">
                <textarea name="sazba" rows="2" required><?php echo htmlspecialchars($customer['sazba']); ?></textarea>
                <input type="submit" value="Uložit sazbu">
            </form>
        <?php } ?>

        <!-- Editace pole "text" -->
        <?php if ($_SESSION['role'] == 'admin') { ?>
            <form method="post" action="">
                <input type="hidden" name="edit_text">
                <textarea name="text" rows="4" required><?php echo htmlspecialchars($customer['text']); ?></textarea>
                <input type="submit" value="Uložit změny">
            </form>
        <?php } else { ?>
            <p><i><?php echo nl2br($customer['text']); ?></i></p>
        <?php } ?>

        <h3>Poznámky</h3>
        <form method="post" action="">
            <input type="hidden" name="add_note">
            <label for="note">Text:</label>
            <textarea name="note" rows="4" required></textarea><br>

            <input type="submit" value="Přidat poznámku">
        </form>

        <?php
        if ($notes->num_rows > 0) {
            while ($row = $notes->fetch_assoc()) { ?>
                <div class="note">
                    <p><?php echo nl2br($row['note']); ?></p>
                    <small>Autor: <?php echo htmlspecialchars($row['username']); ?> | <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></small>
                </div>
            <?php }
        } else {
            echo "<p>Žádné poznámky nejsou k dispozici.</p>";
        }
        ?>
    </div>
</body>
</html>