<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once '../config/db.php';

function mouvementStock($article_id, $type, $qte, $prix = 0, $motif = null) {
    global $pdo;

    // Sécurité stock lors d'une vente
    if ($type === 'vente') {
        $check = $pdo->prepare("SELECT quantite FROM produits WHERE id = ?");
        $check->execute([$article_id]);
        $stock = $check->fetchColumn();

        if ($stock < $qte) {
            return false; // stock insuffisant
        }

        $pdo->prepare("
            UPDATE produits 
            SET quantite = quantite - ? 
            WHERE id = ?
        ")->execute([$qte, $article_id]);
    }

    // Ajout / correction / import
    if (in_array($type, ['ajout','correction','import'])) {
        $pdo->prepare("
            UPDATE produits 
            SET quantite = quantite + ? 
            WHERE id = ?
        ")->execute([$qte, $article_id]);
    }

    // Historique
    $pdo->prepare("
        INSERT INTO stock_mouvements 
        (article_id, type, qte, prix_unitaire, motif, user, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ")->execute([
        $article_id,
        $type,
        $qte,
        $prix,
        $motif,
        $_SESSION['user'] ?? 'system'
    ]);

    return true;
}
