<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Accès refusé.");
}

$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset("utf8mb4");

$id = (int)($_GET['id'] ?? 0);

/* CAISSE */
$stmt = $conn->prepare("
  SELECT c.*, 
         uf.username AS user_close
  FROM caisse_jour c
  LEFT JOIN users uf ON uf.id = c.user_fermeture
  WHERE c.id = ?
  LIMIT 1
");
$stmt->bind_param("i",$id);
$stmt->execute();
$caisse = $stmt->get_result()->fetch_assoc();

if (!$caisse || empty($caisse['heure_fermeture'])) {
  die("Caisse non fermée.");
}

if (!empty($caisse['validated_at'])) {
  die("Caisse déjà validée.");
}

/* SIGNATURE CAISSIER */
$signature_caissier = hash(
  'sha256',
  $caisse['id'].'|'.
  $caisse['date_caisse'].'|'.
  $caisse['total_especes'].'|'.
  $caisse['total_cb'].'|'.
  $caisse['user_fermeture'].'|'.
  $caisse['heure_fermeture']
);

/* VALIDATION */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $signature_gerant = hash(
    'sha256',
    $signature_caissier.'|'.
    $_SESSION['user_id'].'|'.
    date('Y-m-d H:i:s')
  );

  $stmt = $conn->prepare("
    UPDATE caisse_jour SET
      validated_at = NOW(),
      validated_by = ?,
      signature_gerant = ?
    WHERE id = ?
  ");
  $stmt->bind_param(
    "isi",
    $_SESSION['user_id'],
    $signature_gerant,
    $id
  );
  $stmt->execute();

  header("Location: print_pdf_fermeture.php?id=".$id);
  exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Validation gérant</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">

<div class="bg-slate-800 p-6 rounded-xl w-full max-w-md">

<h1 class="text-xl font-bold text-yellow-400 mb-3 text-center">
🔐 Validation gérant
</h1>

<p class="text-sm mb-2">
Date caisse : <strong><?= htmlspecialchars($caisse['date_caisse']) ?></strong><br>
Fermée par : <strong><?= htmlspecialchars($caisse['user_close']) ?></strong>
</p>

<div class="bg-slate-700 p-3 rounded text-xs break-all mb-3">
<strong>Signature caissier :</strong><br>
<?= $signature_caissier ?>
</div>

<form method="post">
<button class="w-full bg-green-600 hover:bg-green-700 p-3 rounded font-semibold">
✅ Valider définitivement la caisse
</button>
</form>

<p class="text-xs text-center text-gray-400 mt-3">
Cette action est irréversible.
</p>

</div>
</body>
</html>
