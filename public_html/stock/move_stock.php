<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../config/db.php';
if (($_SESSION['role'] ?? '') !== 'admin') die('Accès refusé');

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $qte = (int)$_POST['quantite'];
    if ($qte === 0) {
        header('Location: stock.php');
        exit;
    }

    /* =========================
       MISE À JOUR STOCK ARTICLE
       ========================= */
    $stmt = $pdo->prepare("
        UPDATE stock_articles
        SET quantite = quantite + ?
        WHERE id = ?
    ");
    $stmt->execute([$qte, $id]);

    /* =========================
       TYPE DE MOUVEMENT (ENUM)
       ========================= */
    $type = $qte > 0 ? 'in' : 'out';

    /* =========================
       HISTORIQUE MOUVEMENT
       ========================= */
    $stmt = $pdo->prepare("
        INSERT INTO stock_movements
            (article_id, type, qty_change, user_id)
        VALUES
            (?, ?, ?, ?)
    ");
    $stmt->execute([
        $id,
        $type,
        abs($qte),
        $_SESSION['user_id']
    ]);

    header('Location: stock.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mouvement stock</title>
</head>
<body>

<h2>📦 Mouvement de stock</h2>

<form method="post">
    <input type="number"
           name="quantite"
           placeholder="+ entrée / - sortie"
           required>
    <button>Valider</button>
</form>

</body>
</html>
