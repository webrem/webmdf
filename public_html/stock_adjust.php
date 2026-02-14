<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// stock_adjust.php
include 'header.php';
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die('DB error');
$conn->set_charset('utf8mb4');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) { $msg = 'Accès refusé : admin uniquement.'; }
    else {
        $article_id = (int)$_POST['article_id'];
        $new_qty = (int)$_POST['new_qty'];
        $note = trim($_POST['note'] ?? 'Ajustement manuel');

        $stmt = $conn->prepare("SELECT quantite FROM stock_articles WHERE id = ?");
        $stmt->bind_param('i',$article_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows==0) { $msg='Article introuvable'; }
        else {
            $row = $res->fetch_assoc();
            $old = (int)$row['quantite'];
            $delta = $new_qty - $old;
            $conn->begin_transaction();
            try {
                $stmt2 = $conn->prepare("UPDATE stock_articles SET quantite = ? WHERE id = ?");
                $stmt2->bind_param('ii', $new_qty, $article_id);
                if (!$stmt2->execute()) throw new Exception('Update failed');
                if ($conn->query("SHOW TABLES LIKE 'stock_movements'")->num_rows > 0) {
                    $stmt3 = $conn->prepare("INSERT INTO stock_movements (article_id, type, qty_change, reference, user_id, note, created_at) VALUES (?, 'adjust', ?, ?, ?, ?, NOW())");
                    $ref = 'ADJ-'.date('YmdHis');
                    $user = (int)$_SESSION['user_id'];
                    $stmt3->bind_param('iiiss', $article_id, $delta, $ref, $user, $note);
                    if (!$stmt3->execute()) throw new Exception('Insert movement failed');
                }
                $conn->commit(); $msg = 'Ajustement enregistré.';
            } catch (Exception $e) {
                $conn->rollback(); $msg = 'Erreur: '.$e->getMessage();
            }
        }
    }
}

// fetch small list of articles
$res = $conn->query("SELECT id, reference, designation, quantite FROM stock_articles ORDER BY designation LIMIT 500");
$articles = [];
while ($r = $res->fetch_assoc()) $articles[] = $r;
?>
<?php include 'header.php'; ?>
<h2 class="text-center mb-4">📊 Tableau de bord — Ajuster stock</h2>
<div class="container py-3">
  <?php if($msg): ?><div class="alert alert-info"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label>Article</label>
      <select name="article_id" class="form-control" required>
        <?php foreach($articles as $a): ?>
        <option value="<?=$a['id']?>"><?=htmlspecialchars($a['reference'].' — '.$a['designation'].' (stock '.$a['quantite'].')')?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label>Nouvelle quantité</label>
      <input type="number" name="new_qty" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Motif / Note</label>
      <input type="text" name="note" class="form-control">
    </div>
    <button class="btn btn-primary">Enregistrer</button>
  </form>
</div>
