<?php
// Fetch all customers
include 'db.php';

$customers = $conn->query("SELECT * FROM customers");
if (!$customers) {
    die("Chyba při načítání zákazníků: " . $conn->error);
}
?>

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