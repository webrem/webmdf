<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// === Connexion MySQL ===
$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur connexion DB : " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// === Création auto des tables manquantes ===
$conn->query("CREATE TABLE IF NOT EXISTS device_videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_ref VARCHAR(50),
  video_path VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS device_parts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_ref VARCHAR(50),
  stock_ref VARCHAR(50),
  designation VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// === Récupération des réparateurs depuis users ===
$technicians = [];
$resTech = $conn->query("
    SELECT username
    FROM users
    WHERE role IN ('admin','user')
    ORDER BY username ASC
");

if ($resTech) {
    while ($row = $resTech->fetch_assoc()) {
        $technicians[] = $row['username'];
    }
}
// === Génération de référence ===
function generate_ref($conn) {
    $next = 1;
    $sql = "SELECT MAX(CAST(SUBSTRING(ref, 4) AS UNSIGNED)) AS maxnum FROM devices WHERE ref REGEXP '^REM[0-9]+$'";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) $next = ((int)$row['maxnum']) + 1;
    $res->free();
    return "REM" . str_pad($next, 4, "0", STR_PAD_LEFT);
}

$errors = [];
$success_msg = '';
$generated_ref = null;

// === Traitement formulaire ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = fn($v) => htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
    $client_name = $f($_POST['client_name'] ?? '');
    $client_phone = $f($_POST['client_phone'] ?? '');
    $client_email = $f($_POST['client_email'] ?? '');
    $client_address = $f($_POST['client_address'] ?? '');
    $model = $f($_POST['model'] ?? '');
    $problem = $f($_POST['problem'] ?? '');
    $technician_name = $f($_POST['technician_name'] ?? '');
    $imei_serial = $f($_POST['imei_serial'] ?? '');
    $device_lock = $f($_POST['device_lock'] ?? '');
    $other_checks = $f($_POST['other_checks'] ?? '');
    $notes = $f($_POST['notes'] ?? '');
    $price_repair = floatval($_POST['price_repair'] ?? 0);
    $price_diagnostic = floatval($_POST['price_diagnostic'] ?? 0);
    $selected_parts = $_POST['selected_parts'] ?? [];

    $parts = ['speaker','lcd','front_cam','back_cam','microphone','fingerprint','home_button','volume_button','power_button','signal_carrier','battery','wifi_bt','ear_speaker','charging_port'];
    foreach ($parts as $p) $parts_vals[$p] = isset($_POST[$p]) ? 1 : 0;

    if (!$client_name) $errors[] = "Nom client obligatoire.";
    if (!$client_phone) $errors[] = "Téléphone obligatoire.";
    if (!$model) $errors[] = "Modèle obligatoire.";

    if (empty($errors)) {
        $ref = generate_ref($conn);
        $status = 'Reçu';

        // Vérification stock
        foreach ($selected_parts as $refPart) {
            $refPart = $f($refPart);
            $stmt = $conn->prepare("SELECT quantite, designation FROM stock_articles WHERE reference=?");
            $stmt->bind_param("s", $refPart);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            if (!$r) $errors[] = "❌ Pièce $refPart introuvable.";
            elseif ($r['quantite'] <= 0) $errors[] = "⚠️ Pièce {$r['designation']} hors stock.";
            $stmt->close();
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO devices
        (ref, client_name, client_phone, client_email, client_address,
         model, problem, technician_name, imei_serial, device_lock,
         speaker,lcd,front_cam,back_cam,microphone,fingerprint,
         home_button,volume_button,power_button,signal_carrier,
         battery,wifi_bt,ear_speaker,charging_port,
         other_checks,notes,status,price_repair,price_diagnostic)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param(
            "ssssssssssiiiiiiiiiiiiissssdd",
            $ref, $client_name, $client_phone, $client_email, $client_address,
            $model, $problem, $technician_name, $imei_serial, $device_lock,
            $parts_vals['speaker'],$parts_vals['lcd'],$parts_vals['front_cam'],$parts_vals['back_cam'],
            $parts_vals['microphone'],$parts_vals['fingerprint'],$parts_vals['home_button'],
            $parts_vals['volume_button'],$parts_vals['power_button'],$parts_vals['signal_carrier'],
            $parts_vals['battery'],$parts_vals['wifi_bt'],$parts_vals['ear_speaker'],$parts_vals['charging_port'],
            $other_checks,$notes,$status,$price_repair,$price_diagnostic
        );

        if ($stmt->execute()) {
            $generated_ref = $ref;
            $success_msg = "✅ Appareil enregistré avec succès — Référence : <strong>{$ref}</strong>";

            // Upload photos
            if (!empty($_FILES['photos']['name'][0])) {
                $dir = __DIR__ . "/uploads/devices/";
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                            $filename = uniqid("photo_") . ".$ext";
                            move_uploaded_file($tmp, $dir . $filename);
                            $rel = "uploads/devices/$filename";
                            $conn->query("INSERT INTO device_photos (device_ref, photo_path) VALUES ('$ref','$rel')");
                        }
                    }
                }
            }

            // Upload vidéos
            if (!empty($_FILES['videos']['name'][0])) {
                $dir = __DIR__ . "/uploads/devices/";
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                foreach ($_FILES['videos']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['videos']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['videos']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['mp4','mov','avi','mkv'])) {
                            $filename = uniqid("video_") . ".$ext";
                            move_uploaded_file($tmp, $dir . $filename);
                            $rel = "uploads/devices/$filename";
                            $conn->query("INSERT INTO device_videos (device_ref, video_path) VALUES ('$ref','$rel')");
                        }
                    }
                }
            }

            // Décrément stock
            foreach ($selected_parts as $p) {
                $p = $f($p);
                $r = $conn->query("SELECT designation FROM stock_articles WHERE reference='$p'")->fetch_assoc();
                if ($r) {
                    $designation = $r['designation'];
                    $conn->query("INSERT INTO device_parts (device_ref, stock_ref, designation) VALUES ('$ref','$p','$designation')");
                    $conn->query("UPDATE stock_articles SET quantite = GREATEST(quantite - 1, 0) WHERE reference='$p'");
                }
            }

        } else $errors[] = "Erreur SQL : " . $conn->error;
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>➕ Enregistrer un appareil — R.E.Mobiles</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #dc3545;
  --primary-dark: #a71d2a;
  --light: #fff;
  --dark: #0a0a0a;
}
* { font-family: 'Inter', sans-serif; }

body {
  background: linear-gradient(135deg, var(--dark), #1a1a1a, #000);
  color: var(--light);
  min-height: 100vh;
  overflow-x: hidden;
}

/* Grille animée */
.grid-pattern {
  position: fixed; top: 0; left: 0;
  width: 100%; height: 100%;
  background-image:
    linear-gradient(rgba(220,53,69,0.08) 1px, transparent 1px),
    linear-gradient(90deg, rgba(220,53,69,0.08) 1px, transparent 1px);
  background-size: 50px 50px;
  animation: gridMove 20s linear infinite;
  z-index: -1;
}
@keyframes gridMove { 0%{transform:translate(0,0);}100%{transform:translate(50px,50px);} }

/* Header */
.header-bar {
  background: linear-gradient(90deg, var(--primary), var(--primary-dark));
  padding: 12px 20px;
  border-radius: 0 0 20px 20px;
  display: flex; justify-content: space-between; align-items: center;
  color: #fff;
  box-shadow: 0 0 25px rgba(220,53,69,0.3);
}
.header-bar a { margin-left: 6px; }

/* Carte verre */
.glass {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(220,53,69,0.25);
  border-radius: 18px;
  padding: 1.5rem;
  box-shadow: 0 0 25px rgba(0,0,0,0.5);
  transition: 0.3s;
}
.glass:hover { box-shadow: 0 0 25px rgba(220,53,69,0.3); }

label { color: var(--primary); font-weight:600; }
.form-control, .form-select {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.2);
  color: #fff; border-radius: 10px;
}
.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(220,53,69,0.25); }

.btn-accent {
  background: linear-gradient(90deg, var(--primary), var(--primary-dark));
  border: none; color: #fff; font-weight:700;
  border-radius:12px; transition:0.25s;
}
.btn-accent:hover { transform:scale(1.05); box-shadow:0 0 20px rgba(220,53,69,0.5); }

.alert { border:none; border-left:4px solid var(--primary); border-radius:10px; background:rgba(220,53,69,0.15); color:#fff; }

.badge-part { background: var(--primary); color:#fff; border-radius:8px; padding:4px 8px; margin:3px; cursor:pointer; }

.list-group-item { background:#111; color:#fff; border:none; cursor:pointer; }
.list-group-item:hover { background:var(--primary); color:#fff; }
</style>
</head>
<body>
<div class="grid-pattern"></div>
<?php include 'header.php'; ?>

<div class="header-bar">
  <span>📱 Enregistrer un appareil — R.E.Mobiles</span>
  <div>
    <a href="devices_list.php" class="btn btn-outline-light btn-sm">📋 Liste</a>
    <a href="dashboard.php" class="btn btn-light btn-sm">🏠 Tableau</a>
  </div>
</div>

<div class="container py-4">
  <div class="glass mb-4">
    <h3 class="text-danger fw-bold mb-4"><i class="bi bi-plus-circle"></i> Nouveau dépôt</h3>

    <?php if(!empty($errors)): ?>
      <div class="alert alert-danger"><?=implode('<br>', array_map('htmlspecialchars',$errors))?></div>
    <?php endif; ?>

    <?php if($success_msg): ?>
      <div class="alert alert-success"><?=$success_msg?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6 position-relative">
          <label>Nom *</label>
          <input type="text" id="client_name" name="client_name" class="form-control" required>
          <div id="suggestions" class="list-group position-absolute w-100"></div>
        </div>
        <div class="col-md-6"><label>Téléphone *</label><input name="client_phone" class="form-control" required></div>
        <div class="col-md-6"><label>Email</label><input type="email" name="client_email" class="form-control"></div>
        <div class="col-md-6"><label>Adresse</label><input name="client_address" class="form-control"></div>
        <div class="col-md-6"><label>Modèle *</label><input name="model" class="form-control" required></div>
        <div class="col-md-6"><label>Problème</label><input name="problem" class="form-control"></div>
        
        <div class="col-md-6"><label>Réparateur</label><select name="technician_name" class="form-select"><option value="">-- Sélectionner --</option><?php foreach ($technicians as $tech): ?><option value="<?= htmlspecialchars($tech) ?>"><?= htmlspecialchars($tech) ?></option><?php endforeach; ?></select></div>

        
        <div class="col-md-6"><label>IMEI / N° série</label><input name="imei_serial" class="form-control"></div>
        <div class="col-md-6"><label>Code verrou</label><input name="device_lock" class="form-control"></div>

        <div class="col-12 mt-3">
          <label>🚫 Pièces non fonctionnelles</label>
          <div class="d-flex flex-wrap gap-3">
            <?php foreach(['speaker'=>'Haut-parleur','lcd'=>'Écran','front_cam'=>'Caméra avant','back_cam'=>'Caméra arrière','microphone'=>'Micro','fingerprint'=>'Empreinte','home_button'=>'Accueil','volume_button'=>'Volume','power_button'=>'Power','signal_carrier'=>'Signal','battery'=>'Batterie','wifi_bt'=>'Wi-Fi/Bluetooth','ear_speaker'=>'Écouteur','charging_port'=>'Port charge'] as $k=>$v): ?>
              <label><input type="checkbox" name="<?=$k?>"> <?=$v?></label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-12 mt-3">
          <label>🔩 Ajouter une pièce depuis le stock</label>
          <input type="text" id="searchStock" class="form-control" placeholder="Rechercher une pièce...">
          <div id="stockList" class="list-group mt-2"></div>
          <div id="selectedParts" class="mt-2"></div>
        </div>

        <div class="col-12"><label>Remarques</label><textarea name="other_checks" class="form-control"></textarea></div>
        <div class="col-12"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
        <div class="col-md-6"><label>Prix réparation (€)</label><input type="number" step="0.01" name="price_repair" class="form-control"></div>
        <div class="col-md-6"><label>Prix diagnostic (€)</label><input type="number" step="0.01" name="price_diagnostic" class="form-control"></div>
        <div class="col-12"><label>📸 Photos</label><input type="file" name="photos[]" multiple accept="image/*" class="form-control"></div>
        <div class="col-12"><label>🎥 Vidéos</label><input type="file" name="videos[]" multiple accept="video/*" class="form-control"></div>
      </div>

      <div class="d-flex justify-content-end gap-3 mt-4">
        <button type="submit" class="btn btn-accent">✅ Enregistrer</button>
      </div>
    </form>
  </div>

  <?php if($generated_ref): ?>
  <div class="glass p-3">
    <h5 class="text-danger mb-3"><i class="bi bi-receipt"></i> Fiche générée</h5>
    <iframe src="device_receipt.php?ref=<?=htmlspecialchars($generated_ref)?>" width="100%" height="500px" style="border:2px solid #dc3545;border-radius:10px;"></iframe>
  </div>
  <?php endif; ?>
</div>

<script>
// === Stock autocomplete ===
const input=document.getElementById('searchStock'),list=document.getElementById('stockList'),selected=document.getElementById('selectedParts'),refs=new Set();
input.addEventListener('input',()=>{
  const q=input.value.trim(); list.innerHTML=''; if(q.length<2)return;
  fetch('search_stock.php?q='+encodeURIComponent(q))
  .then(r=>r.json()).then(data=>{
    list.innerHTML=''; if(!data.length){list.innerHTML='<div class="list-group-item">Aucune pièce trouvée</div>';return;}
    data.forEach(it=>{
      const d=document.createElement('div'); d.className='list-group-item list-group-item-action';
      d.innerHTML=`<strong>${it.reference}</strong> - ${it.designation}`;
      d.onclick=()=>{if(refs.has(it.reference))return;refs.add(it.reference);
        const b=document.createElement('span');b.className='badge-part';
        b.textContent=it.reference+' ✖';b.onclick=()=>{b.remove();refs.delete(it.reference);};
        selected.appendChild(b);
        const h=document.createElement('input');h.type='hidden';h.name='selected_parts[]';h.value=it.reference;selected.appendChild(h);
        list.innerHTML='';input.value='';
      };
      list.appendChild(d);
    });
  });
});

// === Autocomplétion client ===
const clientInput=document.getElementById('client_name');
const clientBox=document.getElementById('suggestions');
clientInput.addEventListener('input',()=>{
  const q=clientInput.value.trim();
  clientBox.innerHTML='';
  if(q.length<2)return;
  fetch('clients_autocomplete.php?q='+encodeURIComponent(q))
    .then(r=>r.json())
    .then(data=>{
      clientBox.innerHTML='';
      if(!data.length){clientBox.innerHTML='<div class="list-group-item">Aucun client trouvé</div>';return;}
      data.forEach(client=>{
        const div=document.createElement('div');
        div.className='list-group-item list-group-item-action';
        div.innerHTML=`<strong>${client.nom}</strong> (${client.telephone||''})`;
        div.onclick=()=>{
          clientInput.value=client.nom;
          document.querySelector('input[name="client_phone"]').value=client.telephone||'';
          document.querySelector('input[name="client_email"]').value=client.email||'';
          document.querySelector('input[name="client_address"]').value=client.adresse||'';
          clientBox.innerHTML='';
        };
        clientBox.appendChild(div);
      });
    });
});
document.addEventListener('click',e=>{
  if(!e.target.closest('#client_name,#suggestions'))clientBox.innerHTML='';
});