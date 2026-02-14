<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require 'db.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO stock_articles (reference, designation, prix_vente, quantite) VALUES (?,?,?,?)");
    $stmt->execute([$_POST['reference'], $_POST['designation'], $_POST['prix'], $_POST['quantite']]);
}

if ($action === 'edit') {
    $stmt = $pdo->prepare("UPDATE stock_articles SET prix_vente=?, quantite=? WHERE id=?");
    $stmt->execute([$_POST['prix'], $_POST['quantite'], $_POST['id']]);
}

if ($action === 'move') {
    $stmt = $pdo->prepare("UPDATE stock_articles SET quantite = quantite + ? WHERE id=?");
    $stmt->execute([$_POST['quantite'], $_POST['id']]);
}

if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM stock_articles WHERE id=?");
    $stmt->execute([$_POST['id']]);
}
