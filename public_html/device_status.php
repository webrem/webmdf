<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once __DIR__ . '/sync_time.php';
require_once __DIR__ . '/device_utils.php';


// Connexion DB
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$ref = $_GET['ref'] ?? '';
if ($ref === '') die("Référence manquante.");

$device = $conn->query("SELECT * FROM devices WHERE ref='$ref' LIMIT 1")->fetch_assoc();
if (!$device) die("Appareil introuvable.");

$photos = $conn->query("SELECT * FROM device_photos WHERE device_ref='$ref' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$videos = $conn->query("SELECT * FROM device_videos WHERE device_ref='$ref' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$notes  = $conn->query("SELECT * FROM device_notes WHERE device_ref='$ref' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

$msg = "";

/* --- Modifier infos --- */
if (isset($_POST['update_info'])) {
    $stmt = $conn->prepare("UPDATE devices SET client_name=?, client_phone=?, client_email=?, client_address=?, model=?, problem=?, technician_name=?, updated_at=NOW() WHERE ref=?");
    $stmt->bind_param("ssssssss", $_POST['client_name'], $_POST['client_phone'], $_POST['client_email'], $_POST['client_address'], $_POST['model'], $_POST['problem'], $_POST['technician_name'], $ref);
    $stmt->execute();
    $msg = "✅ Informations mises à jour avec succès.";
}

/* --- Ajouter note --- */
if (isset($_POST['add_note'])) {
    $note = trim($_POST['note']);
    $user = $_SESSION['username'] ?? 'Technicien';
    $photo_path = null;
    if (!empty($_FILES['note_photo']['tmp_name'])) {
        @mkdir(__DIR__ . "/uploads/notes", 0775, true);
        $name = time() . "_" . basename($_FILES['note_photo']['name']);
        $photo_path = "uploads/notes/$name";
        move_uploaded_file($_FILES['note_photo']['tmp_name'], __DIR__ . "/$photo_path");
    }
    $stmt = $conn->prepare("INSERT INTO device_notes (device_ref, note, photo_path, user, created_at) VALUES (?,?,?,?,NOW())");
    $stmt->bind_param("ssss", $ref, $note, $photo_path, $user);
    $stmt->execute();
    $msg = "📝 Note ajoutée avec succès.";
}

/* --- Modifier statut --- */
if (isset($_POST['status'])) {
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE devices SET status=?, updated_at=NOW() WHERE ref=?");
    $stmt->bind_param("ss", $status, $ref);
    $stmt->execute();
    $msg = "✅ Statut mis à jour.";
}

/* --- Upload photo --- */
if (isset($_POST['upload_photo']) && !empty($_FILES['photo']['tmp_name'])) {
    @mkdir(__DIR__ . "/uploads/photos", 0775, true);
    $name = time() . "_" . basename($_FILES['photo']['name']);
    $dest = "uploads/photos/$name";
    if (move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . "/$dest")) {
        $stmt = $conn->prepare("INSERT INTO device_photos (device_ref, photo_path) VALUES (?, ?)");
        $stmt->bind_param("ss", $ref, $dest);
        $stmt->execute();
        $msg = "📸 Photo ajoutée.";
    } else $msg = "⚠️ Erreur upload photo.";
}

/* --- Upload vidéo --- */
if (isset($_POST['upload_video']) && !empty($_FILES['video']['tmp_name'])) {
    @mkdir(__DIR__ . "/uploads/videos", 0775, true);
    $name = time() . "_" . basename($_FILES['video']['name']);
    $dest = "uploads/videos/$name";
    if (move_uploaded_file($_FILES['video']['tmp_name'], __DIR__ . "/$dest")) {
        $stmt = $conn->prepare("INSERT INTO device_videos (device_ref, video_path) VALUES (?, ?)");
        $stmt->bind_param("ss", $ref, $dest);
        $stmt->execute();
        $msg = "🎥 Vidéo ajoutée.";
    } else $msg = "⚠️ Erreur upload vidéo.";
}

$statuses = ["Reçu","En cours","Attente Pcs","Non Réparable","Annulé","Terminé","Récupéré"];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Fiche réparation - <?= htmlspecialchars($device['ref']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  background: radial-gradient(circle at top left, #0a0a0a, #1a1a1a);
  color: #fff;
  font-family: "Poppins", sans-serif;
  min-height: 100vh;
}
h2 { color: #0dcaf0; font-weight: 700; text-align: center; margin-bottom: 2rem; }
.container { max-width: 1100px; }
.card.glass {
  background: rgba(25,25,25,0.9);
  border: 1px solid rgba(13,202,240,0.4);
  border-radius: 20px;
  box-shadow: 0 0 25px rgba(0,0,0,0.5);
  padding: 1.5rem;
}
.info-box {
  background: rgba(255,255,255,0.06);
  border-radius: 10px;
  padding: 15px;
  margin-bottom: 1rem;
}
.info-box p { color: #fff; margin-bottom: 0.4rem; }
label { color: #0dcaf0; font-weight: 600; }
.photo-thumb {
  border: 2px solid #0dcaf0;
  border-radius: 10px;
  margin: 5px;
  width: 100px;
  height: 100px;
  object-fit: cover;
  cursor: pointer;
  transition: 0.3s;
}
.photo-thumb:hover { transform: scale(1.08); }
.viewer {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.9);
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.viewer img {
  max-width: 90%;
  max-height: 90%;
  border-radius: 10px;
  border: 3px solid #0dcaf0;
}
.viewer.active { display: flex; }
.note-box {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(13,202,240,0.2);
  border-radius: 10px;
  padding: 10px;
  margin-bottom: 10px;
}
.header-bar {
  background: linear-gradient(90deg, #0dcaf0, #0b5ed7);
  color: #000;
  padding: 12px 20px;
  border-radius: 12px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.header-bar a { margin-left: 5px; }
.alert-animated {
  position: fixed;
  top: 20px; right: 20px;
  background: #0dcaf0;
  color: #000;
  font-weight: bold;
  padding: 10px 20px;
  border-radius: 8px;
  box-shadow: 0 0 10px #0dcaf0;
  opacity: 0;
  transform: translateY(-20px);
  transition: all 0.6s ease;
}
.alert-animated.show { opacity: 1; transform: translateY(0); }
</style>
</head>
<body>
<div class="container py-4">
  <?php include 'header.php'; ?>

  <div class="header-bar">
    <span>⚡ R.E.Mobiles — Réf : <strong><?= htmlspecialchars($device['ref']) ?></strong></span>
    <div>
      <a href="devices_list.php" class="btn btn-outline-light btn-sm">⬅ Retour</a>
      <a href="device_receipt.php?ref=<?= urlencode($device['ref']) ?>" target="_blank" class="btn btn-light btn-sm">🧾 Ticket 80mm</a>
      <button id="toggleEdit" class="btn btn-warning btn-sm">✏️ Modifier</button>
    </div>
  </div>

  <?php if ($msg): ?>
    <div id="alertBox" class="alert-animated"><?= htmlspecialchars($msg) ?></div>
    <script>
      setTimeout(()=>{document.getElementById('alertBox').classList.add('show');},200);
      setTimeout(()=>{document.getElementById('alertBox').classList.remove('show');},3500);
    </script>
  <?php endif; ?>

  <div class="card glass">
    <h2>📱 Détails de la réparation</h2>

    <div id="viewInfo" class="info-box">
      <p><strong>Client :</strong> <?= htmlspecialchars($device['client_name']) ?> — <?= htmlspecialchars($device['client_phone']) ?></p>
      <p><strong>Email :</strong> <?= htmlspecialchars($device['client_email']) ?></p>
      <p><strong>Adresse :</strong> <?= nl2br(htmlspecialchars($device['client_address'])) ?></p>
      <p><strong>Modèle :</strong> <?= htmlspecialchars($device['model']) ?></p>
      <p><strong>Problème :</strong> <?= nl2br(htmlspecialchars($device['problem'])) ?></p>
      <p><strong>Réparateur :</strong> <?= htmlspecialchars($device['technician_name']) ?></p>
      <p><strong>Statut :</strong> <span class="badge bg-info text-dark"><?= htmlspecialchars($device['status']) ?></span></p>
    </div>

    <form method="post" id="editForm" style="display:none;">
      <input type="hidden" name="update_info" value="1">
      <div class="row g-2">
        <div class="col-md-4"><label>Nom client</label><input name="client_name" class="form-control" value="<?= htmlspecialchars($device['client_name']) ?>"></div>
        <div class="col-md-4"><label>Téléphone</label><input name="client_phone" class="form-control" value="<?= htmlspecialchars($device['client_phone']) ?>"></div>
        <div class="col-md-4"><label>Email</label><input name="client_email" class="form-control" value="<?= htmlspecialchars($device['client_email']) ?>"></div>
        <div class="col-12"><label>Adresse</label><textarea name="client_address" class="form-control"><?= htmlspecialchars($device['client_address']) ?></textarea></div>
        <div class="col-md-6"><label>Modèle</label><input name="model" class="form-control" value="<?= htmlspecialchars($device['model']) ?>"></div>
        <div class="col-md-6"><label>Technicien</label><input name="technician_name" class="form-control" value="<?= htmlspecialchars($device['technician_name']) ?>"></div>
        <div class="col-12"><label>Problème</label><textarea name="problem" class="form-control"><?= htmlspecialchars($device['problem']) ?></textarea></div>
        <div class="col-12 mt-2"><button class="btn btn-success">💾 Enregistrer</button></div>
      </div>
    </form>

    <hr class="text-light">

    <h4 class="text-warning mb-3">📸 Photos</h4>
    <div class="d-flex flex-wrap">
      <?php if($photos): foreach($photos as $p): ?>
        <img src="<?= htmlspecialchars($p['photo_path']) ?>" class="photo-thumb" onclick="openViewer('<?= htmlspecialchars($p['photo_path']) ?>')">
      <?php endforeach; else: ?><p>Aucune photo.</p><?php endif; ?>
    </div>

    <form method="post" enctype="multipart/form-data" class="mt-2">
      <input type="hidden" name="upload_photo" value="1">
      <div class="input-group"><input type="file" name="photo" accept="image/*" class="form-control" required><button class="btn btn-info">➕ Ajouter photo</button></div>
    </form>

    <h4 class="text-warning mt-4 mb-3">🎥 Vidéos</h4>
    <div class="d-flex flex-wrap">
      <?php if($videos): foreach($videos as $v): ?>
        <video width="160" controls><source src="<?= htmlspecialchars($v['video_path']) ?>"></video>
      <?php endforeach; else: ?><p>Aucune vidéo.</p><?php endif; ?>
    </div>

    <form method="post" enctype="multipart/form-data" class="mt-2">
      <input type="hidden" name="upload_video" value="1">
      <div class="input-group"><input type="file" name="video" accept="video/*" class="form-control" required><button class="btn btn-info">➕ Ajouter vidéo</button></div>
    </form>

    <h4 class="text-warning mt-4 mb-3">📝 Notes internes</h4>
    <?php if($notes): foreach($notes as $n): ?>
      <div class="note-box">
        <div><?= nl2br(htmlspecialchars($n['note'])) ?></div>
        <?php if(!empty($n['photo_path'])): ?><img src="<?= htmlspecialchars($n['photo_path']) ?>" class="photo-thumb mt-2" onclick="openViewer('<?= htmlspecialchars($n['photo_path']) ?>')"><?php endif; ?>
        <small>✍️ <?= htmlspecialchars($n['user']) ?> — <?= htmlspecialchars($n['created_at']) ?></small>
      </div>
    <?php endforeach; else: ?><p>Aucune note.</p><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="mt-2">
      <input type="hidden" name="add_note" value="1">
      <div class="mb-2"><textarea name="note" class="form-control" placeholder="Ajouter une note..." required></textarea></div>
      <div class="input-group"><input type="file" name="note_photo" accept="image/*" class="form-control"><button class="btn btn-warning">➕ Ajouter note</button></div>
    </form>

    <h4 class="text-warning mt-4 mb-3">🔧 Changer le statut</h4>
    <form method="post">
      <div class="row g-2">
        <div class="col-md-6">
          <select name="status" class="form-select">
            <?php foreach($statuses as $s): ?><option value="<?= $s ?>" <?= $device['status']==$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><button class="btn btn-success w-100">✅ Enregistrer</button></div>
      </div>
    </form>
  </div>
</div>

<!-- Viewer plein écran -->
<div id="viewer" class="viewer" onclick="this.classList.remove('active')">
  <img src="" alt="zoom">
</div>

<script>
function openViewer(src){
  const v=document.getElementById('viewer');
  v.querySelector('img').src=src;
  v.classList.add('active');
}
document.addEventListener('DOMContentLoaded',()=>{
  const btn=document.getElementById('toggleEdit');
  const form=document.getElementById('editForm');
  const view=document.getElementById('viewInfo');
  if(btn){btn.addEventListener('click',()=>{
    const edit=(form.style.display==='none'||form.style.display==='');
    form.style.display=edit?'block':'none';
    view.style.display=edit?'none':'block';
    btn.textContent=edit?'❌ Annuler':'✏️ Modifier';
  });}
});
</script>
</body>
</html>
