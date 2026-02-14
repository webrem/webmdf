<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../config/db.php';
if ($_SESSION['role'] !== 'admin') die('Accès refusé');

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM stock_articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE stock_articles SET
            reference = ?,
            designation = ?,
            prix_achat = ?,
            prix_vente = ?,
            quantite = ?,
            seuil = ?,
            ean = ?,
            fournisseur = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['reference'],
        $_POST['designation'],
        $_POST['prix_achat'],
        $_POST['prix_vente'],
        $_POST['quantite'],
        $_POST['seuil'],
        $_POST['ean'],
        $_POST['fournisseur'],
        $id
    ]);
    header('Location: stock.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier produit</title>

<style>
body {
    background: radial-gradient(circle at top, #1a1a1a, #000);
    font-family: 'Segoe UI', sans-serif;
    color: #f5f5f5;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 650px;
    margin: 60px auto;
    background: linear-gradient(145deg, #0d0d0d, #1b1b1b);
    border-radius: 16px;
    padding: 30px 40px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.8);
    border: 1px solid #c9a44c;
}

h2 {
    text-align: center;
    color: #c9a44c;
    margin-bottom: 30px;
    letter-spacing: 1px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #d4af37;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

input {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: none;
    background: #111;
    color: #fff;
    font-size: 15px;
    outline: none;
    box-shadow: inset 0 0 0 1px #333;
    transition: 0.2s;
}

input:focus {
    box-shadow: inset 0 0 0 1px #c40000;
}

.actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

button {
    background: linear-gradient(135deg, #c40000, #7a0000);
    border: none;
    padding: 14px 26px;
    color: #fff;
    border-radius: 10px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
    box-shadow: 0 10px 30px rgba(196,0,0,0.4);
}

button:hover {
    transform: translateY(-2px);
}

.back {
    background: transparent;
    border: 1px solid #444;
    color: #ccc;
    box-shadow: none;
}

.back:hover {
    background: #222;
}
</style>
</head>

<body>

<div class="container">
    <h2>✏️ Modifier le produit</h2>

    <form method="post">
        <div class="form-group">
            <label>Référence</label>
            <input name="reference" value="<?= htmlspecialchars($article['reference']) ?>">
        </div>

        <div class="form-group">
            <label>Désignation</label>
            <input name="designation" value="<?= htmlspecialchars($article['designation']) ?>">
        </div>

        <div class="form-group">
            <label>Prix d'achat (€)</label>
            <input name="prix_achat" value="<?= $article['prix_achat'] ?>">
        </div>

        <div class="form-group">
            <label>Prix de vente (€)</label>
            <input name="prix_vente" value="<?= $article['prix_vente'] ?>">
        </div>

        <div class="form-group">
            <label>Quantité en stock</label>
            <input name="quantite" value="<?= $article['quantite'] ?>">
        </div>

        <div class="form-group">
            <label>Seuil d’alerte</label>
            <input name="seuil" value="<?= $article['seuil'] ?>">
        </div>

        <div class="form-group">
            <label>EAN / Code-barres</label>
            <input name="ean" value="<?= htmlspecialchars($article['ean']) ?>">
        </div>

        <div class="form-group">
            <label>Fournisseur</label>
            <input name="fournisseur" value="<?= htmlspecialchars($article['fournisseur']) ?>">
        </div>

        <div class="actions">
            <button type="button" class="back" onclick="window.location='stock.php'">⬅ Retour</button>
            <button type="submit">💾 Mettre à jour</button>
        </div>
    </form>
</div>

</body>
</html>
