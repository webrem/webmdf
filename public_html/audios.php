<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin') die("⛔ Accès refusé.");

$transcription = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio'])) {
    $file = $_FILES['audio'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'ogg' && $ext !== 'mp3' && $ext !== 'wav') {
            $error = "❌ Seuls les fichiers .ogg (WhatsApp), .mp3 ou .wav sont acceptés.";
        } else {
            $apiKey = "9pjCj1WAESQtcZo3sFoKiKd2N6NJwFZa"; // ta clé API
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://api.lemonfox.ai/v1/audio/transcriptions");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $apiKey"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                "model" => "whisper-1",
                "file" => new CURLFile($file['tmp_name'], "audio/$ext", $file['name']),
                "response_format" => "text",
                "language" => "es"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $transcription = htmlspecialchars($response);
            } else {
                $error = "⚠️ Erreur API Lemonfox (Code $httpCode)";
            }
        }
    } else {
        $error = "⚠️ Erreur lors de l'upload.";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Transcrire audio WhatsApp - R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: radial-gradient(circle at top left, #050505, #111);
  color: #fff;
  font-family: "Poppins", sans-serif;
}
h2 { color: #0dcaf0; margin-bottom: 2rem; font-weight: 700; }
.card-custom {
  background: linear-gradient(145deg, #1a1a1a, #0a0a0a);
  border: 2px solid #0dcaf0;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 0 15px rgba(13,202,240,0.6);
}
textarea {
  width: 100%;
  min-height: 200px;
  border-radius: 12px;
  padding: 10px;
  background: #111;
  color: #0dcaf0;
  border: 1px solid #0dcaf0;
}
</style>
</head>
<body>
<div class="container py-5">

  <?php include 'header.php'; ?>
  <h2 class="text-center">🎙️ Transcrire audio WhatsApp (.ogg)</h2>

  <form method="post" enctype="multipart/form-data" class="card-custom mb-4">
    <div class="mb-3">
      <label class="form-label">Sélectionner un fichier audio</label>
      <input type="file" name="audio" class="form-control" accept=".ogg,.mp3,.wav" required>
    </div>
    <button type="submit" class="btn btn-info">
      <i class="bi bi-upload"></i> Transcrire
    </button>
  </form>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($transcription): ?>
    <h4>📝 Transcription :</h4>
    <textarea readonly><?= $transcription ?></textarea>
  <?php endif; ?>
</div>
</body>
</html>
