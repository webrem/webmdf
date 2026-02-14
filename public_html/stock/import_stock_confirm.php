<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../config/db.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès réservé aux administrateurs');
}

if (!isset($_SESSION['CSV_ROWS'], $_POST['map'])) {
    die('Import invalide');
}

$rows = $_SESSION['CSV_ROWS'];
$map  = $_POST['map'];

$created = $updated = 0;

/* IGNORER LIGNE D’ENTÊTE */
array_shift($rows);

foreach ($rows as $line) {

    $data = [];
    foreach ($map as $field => $index) {
        $data[$field] = trim($line[$index] ?? '');
    }

    if (!$data['reference'] && !$data['ean']) continue;

    $stmt = $pdo->prepare("
        SELECT id, quantite 
        FROM stock_articles 
        WHERE reference = ? OR ean = ?
        LIMIT 1
    ");
    $stmt->execute([$data['reference'], $data['ean']]);
    $exist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exist) {
        $newQty = $exist['quantite'] + (int)$data['stock'];

        $u = $pdo->prepare("
            UPDATE stock_articles SET
                designation = ?,
                prix_achat = ?,
                prix_vente = ?,
                fournisseur = ?,
                ean = ?,
                quantite = ?
            WHERE id = ?
        ");
        $u->execute([
            $data['designation'],
            (float)$data['prix'],
            (float)$data['prix_ht'],
            $data['fournisseur'],
            $data['ean'],
            $newQty,
            $exist['id']
        ]);
        $updated++;
    } else {
        $i = $pdo->prepare("
            INSERT INTO stock_articles
            (reference, designation, prix_achat, prix_vente, quantite, fournisseur, ean)
            VALUES (?,?,?,?,?,?,?)
        ");
        $i->execute([
            $data['reference'],
            $data['designation'],
            (float)$data['prix'],
            (float)$data['prix_ht'],
            (int)$data['stock'],
            $data['fournisseur'],
            $data['ean']
        ]);
        $created++;
    }
}

unset($_SESSION['CSV_ROWS']);

echo "✅ Import terminé — Créés : $created / Mis à jour : $updated";
echo "Import terminé. Redirection en cours...";
header("Refresh:3; url=../stock/stock.php");
exit;
