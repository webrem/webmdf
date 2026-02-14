<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 require 'db.php';
if ($_POST) {
    $pdo->prepare("
        INSERT INTO stock_suppliers (nom, contact, telephone)
        VALUES (?,?,?)
    ")->execute([$_POST['nom'], $_POST['contact'], $_POST['tel']]);
}
$suppliers = $pdo->query("SELECT * FROM stock_suppliers")->fetchAll();
?>
<h2>🏢 Fournisseurs</h2>
<form method="post">
<input name="nom" placeholder="Nom fournisseur">
<input name="contact" placeholder="Contact">
<input name="tel" placeholder="Téléphone">
<button>Ajouter</button>
</form>
