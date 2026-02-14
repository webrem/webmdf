<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

function generateEAN13(): string {
    $base = '376';
    while (strlen($base) < 12) {
        $base .= random_int(0, 9);
    }
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int)$base[$i];
        $sum += ($i % 2 === 0) ? $digit : $digit * 3;
    }
    $checkDigit = (10 - ($sum % 10)) % 10;
    return $base . $checkDigit;
}

function eanExists(PDO $pdo, string $ean): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_articles WHERE ean13 = ?");
    $stmt->execute([$ean]);
    return $stmt->fetchColumn() > 0;
}

function generateUniqueEAN13(PDO $pdo): string {
    do {
        $ean = generateEAN13();
    } while (eanExists($pdo, $ean));
    return $ean;
}

function assignEANIfMissing(PDO $pdo, int $id): string {
    $stmt = $pdo->prepare("SELECT ean13 FROM stock_articles WHERE id = ?");
    $stmt->execute([$id]);
    $ean = $stmt->fetchColumn();

    if (!empty($ean)) return $ean;

    $new = generateUniqueEAN13($pdo);
    $upd = $pdo->prepare("UPDATE stock_articles SET ean13 = ? WHERE id = ?");
    $upd->execute([$new, $id]);

    return $new;
}
