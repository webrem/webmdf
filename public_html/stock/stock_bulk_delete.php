<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Accès refusé');
}

if (!empty($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM stock_articles WHERE id IN ($in)");
    $stmt->execute($ids);
}

header('Location: stock.php');
exit;
