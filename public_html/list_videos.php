<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin') die("⛔ Accès refusé.");

$videoDir = __DIR__ . "/videos/";      
$tmpDir   = $videoDir . "tmp/";        
$videos = [];
if (is_dir($videoDir)) {
    $all = array_diff(scandir($videoDir), ['.','..','tmp']);
    foreach ($all as $file) {
        if (is_file($videoDir . $file)) $videos[] = $file;
    }
}

// === Suppression ===
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $path = $videoDir . $file;
    if (file_exists($path)) unlink($path);
    $_SESSION['msg'] = "✅ Vidéo supprimée.";
    header("Location: list_videos.php");
    exit;
}

// === Renommage ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_file'])) {
    $old = basename($_POST['rename_file']);
    $newName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $_POST['new_name']); // sécurisation
    $oldPath = $videoDir . $old;
    $newPath = $videoDir . $newName;
    if (file_exists($oldPath) && !empty($newName)) {
        rename($oldPath, $newPath);
        $_SESSION['msg'] = "✏️ Vidéo renommée en : " . htmlspecialchars($newName);
    }
    header("Location: list_videos.php");
    exit;
}

$msg = $_SESSION['msg'] ?? "";
unset($_SESSION['msg']);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Liste vidéos - R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
body {
  background: radial-gradient(circle at top left, #050505, #111);
  color: #fff;
  font-family: "Poppins", sans-serif;
}
h2 { color: #0dcaf0; font-weight: 700; margin-bottom: 2rem; }
.video-row {
  background: linear-gradient(145deg, #1a1a1a, #0a0a0a);
  border-radius: 12px;
  border: 1px solid #0dcaf0;
  padding: 15px;
  margin-bottom: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 0 15px rgba(13,202,240,0.3);
}
.btn-futur {
  border-radius: 8px;
  font-weight: 600;
  transition: 0.3s;
}
.btn-futur:hover {
  transform: scale(1.05);
  box-shadow: 0 0 10px rgba(13,202,240,0.6);
}
.role-banner {
  background: linear-gradient(90deg, #0dcaf0, #00ffcc);
  color: white;
  padding: 10px;
  border-radius: 10px;
  text-align: center;
  font-weight: 600;
  margin-bottom: 2rem;
}
</style>
</head>
<body>
<div class="container py-5">

  <?php include 'header.php'; ?>
  <h2 class="text-center">📂 Gestion des vidéos</h2>

  <div class="role-banner">
    👤 Connecté en tant que <strong><?= strtoupper($role) ?></strong>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-info"><?= $msg ?></div>
  <?php endif; ?>

  <?php if (empty($videos)): ?>
    <div class="alert alert-warning">Aucune vidéo disponible.</div>
  <?php else: ?>
    <?php foreach ($videos as $vid): ?>
      <div class="video-row">
        <span><i class="bi bi-film text-info"></i> <?= htmlspecialchars($vid) ?></span>
        <div>
          <!-- Aperçu -->
          <button class="btn btn-sm btn-secondary btn-futur me-1"
                  data-bs-toggle="modal" 
                  data-bs-target="#previewModal" 
                  data-src="videos/<?= urlencode($vid) ?>">
            <i class="bi bi-eye"></i>
          </button>
          <!-- Télécharger -->
          <a href="videos/<?= urlencode($vid) ?>" download class="btn btn-sm btn-primary btn-futur me-1">
            <i class="bi bi-download"></i>
          </a>
          <!-- Supprimer -->
          <a href="?delete=<?= urlencode($vid) ?>" 
             onclick="return confirm('⚠️ Supprimer cette vidéo ?');" 
             class="btn btn-sm btn-danger btn-futur me-1">
            <i class="bi bi-trash"></i>
          </a>
          <!-- Renommer -->
          <button class="btn btn-sm btn-warning btn-futur" 
                  data-bs-toggle="modal" 
                  data-bs-target="#renameModal" 
                  data-file="<?= htmlspecialchars($vid) ?>">
            <i class="bi bi-pencil-square"></i>
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="mt-3">
    <a href="upload_video.php" class="btn btn-success btn-futur">
      <i class="bi bi-plus-circle"></i> Ajouter une vidéo
    </a>
    <a href="dashboard.php" class="btn btn-secondary btn-futur">
      <i class="bi bi-arrow-left"></i> Retour dashboard
    </a>
  </div>
</div>

<!-- Modal Aperçu -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">🎬 Aperçu vidéo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <video id="previewVideo" src="" width="100%" controls></video>
      </div>
    </div>
  </div>
</div>

<!-- Modal Renommer -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">✏️ Renommer la vidéo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <input type="hidden" name="rename_file" id="renameFile">
          <div class="mb-3">
            <label class="form-label">Nouveau nom</label>
            <input type="text" class="form-control" name="new_name" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">✅ Valider</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Annuler</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const previewModal = document.getElementById('previewModal');
previewModal.addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  const src = button.getAttribute('data-src');
  const video = document.getElementById('previewVideo');
  video.src = src;
  video.load();
});
previewModal.addEventListener('hidden.bs.modal', () => {
  const video = document.getElementById('previewVideo');
  video.pause();
  video.src = "";
});

const renameModal = document.getElementById('renameModal');
renameModal.addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  const file = button.getAttribute('data-file');
  document.getElementById('renameFile').value = file;
});
</script>
</body>
</html>
