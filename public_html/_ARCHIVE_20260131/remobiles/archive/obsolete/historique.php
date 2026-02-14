
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
$req = $conn->query("SELECT * FROM historiques ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des tickets</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        h2 { text-align: center; color: #c62828; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 8px; font-size: 14px; }
        th { background-color: #2e7d32; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        a.button { padding: 6px 12px; background: #c62828; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
<h2>📄 Historique des tickets générés</h2>
<table>
    <tr>
        <th>Date</th><th>Client</th><th>Pièce</th><th>Quantité</th><th>Prix Final</th><th>Action</th>
    </tr>
    <?php while ($row = $req->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
        <td><?= htmlspecialchars($row['client_nom']) ?></td>
        <td><?= htmlspecialchars($row['piece']) ?></td>
        <td><?= $row['quantite'] ?></td>
        <td><?= number_format($row['prix_final'], 2) ?> €</td>
        <td><a class="button" href="telecharger_ticket.php?id=<?= $row['id'] ?>">📥 Télécharger</a></td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
