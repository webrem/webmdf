<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require 'db.php';

if ($_FILES) {
    $file = fopen($_FILES['csv']['tmp_name'], 'r');
    fgetcsv($file);
    while ($row = fgetcsv($file)) {
        $stmt = $pdo->prepare("
            INSERT INTO stock_articles (reference, designation, prix_vente, quantite)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE
            prix_vente=VALUES(prix_vente),
            quantite=VALUES(quantite)
        ");
        $stmt->execute($row);
    }
    fclose($file);
    header('Location: stock.php');
    exit;
}
?>

<form method="post" enctype="multipart/form-data">
<input type="file" name="csv" required>
<button>Importer</button>
</form>
