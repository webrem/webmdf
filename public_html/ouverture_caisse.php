<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);
$conn->set_charset("utf8mb4");
/* =========================
   🚨 CAISSE NON FERMÉE — FERMETURE OBLIGATOIRE
   ========================= */
$stmt = $conn->prepare("
    SELECT id, date_caisse
    FROM caisse_jour
    WHERE heure_fermeture IS NULL
    LIMIT 1
");
$stmt->execute();
$caisseOuverte = $stmt->get_result()->fetch_assoc();

if ($caisseOuverte) {
    header("Location: fermeture_caisse.php");
    exit;
}

$today   = date('Y-m-d');
$hourNow = (int)date('H');
$error   = "";

/* ⛔ Horaire (sauf admin) */
if (!$isAdmin && $hourNow < 8) {
    $error = "⏰ L’ouverture est autorisée à partir de 08h00.";
}

/* =========================
   🔒 VERROUILLAGE VALIDATION GÉRANT
   ========================= */
$stmt = $conn->prepare("
    SELECT id, validated_at
    FROM caisse_jour
    WHERE date_caisse = ?
    LIMIT 1
");
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();
$caisseLock = $res->fetch_assoc();

if ($caisseLock && !empty($caisseLock['validated_at'])) {
    $error = "🔒 Cette journée est déjà validée par le gérant. Ouverture interdite.";
}

/* =========================
   TRAITEMENT OUVERTURE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $details = $_POST['details_ouverture'] ?? '';
    $detailsArray = json_decode($details, true);

    if (!is_array($detailsArray)) {
        $error = "❌ Comptage invalide.";
    }

    $total = 0;
    foreach ($detailsArray as $val => $qty) {
        $total += ((float)$val) * ((int)$qty);
    }
    $total = round($total, 2);

    if ($total <= 0) {
        $error = "❌ Le fond de caisse ne peut pas être nul.";
    }

    if (!$error) {

        // 🔐 Types forcés (IMPORTANT)
        $total  = (float)$total;
        $userId = (int)$_SESSION['user_id'];

        // Caisse existante aujourd’hui ?
        $stmt = $conn->prepare("
            SELECT id FROM caisse_jour WHERE date_caisse = ? LIMIT 1
        ");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $caisse = $res->fetch_assoc();

        if ($caisse) {

            $caisseId = (int)$caisse['id'];

            $stmt = $conn->prepare("
                UPDATE caisse_jour SET
                    heure_ouverture = NOW(),
                    heure_fermeture = NULL,
                    montant_ouverture = ?,
                    details_ouverture = ?,
                    user_ouverture = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "dsii",
                $total,
                $details,
                $userId,
                $caisseId
            );
            $stmt->execute();

            $idCaisse = $caisseId;

        } else {

            $stmt = $conn->prepare("
                INSERT INTO caisse_jour
                (date_caisse, heure_ouverture, montant_ouverture, details_ouverture, user_ouverture)
                VALUES (?, NOW(), ?, ?, ?)
            ");
            $stmt->bind_param(
                "sdsi",   // ✅ CORRECT – SANS ESPACE
                $today,
                $total,
                $details,
                $userId
            );
            $stmt->execute();

            $idCaisse = $conn->insert_id;
        }

        header("Location: print_confirmation_ouverture.php?id=" . $idCaisse);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ouverture de caisse</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">

<div class="bg-slate-800 p-6 rounded-xl w-full max-w-md">
<h1 class="text-xl font-bold text-green-400 text-center mb-4">🟢 Ouverture de caisse</h1>

<?php if ($error): ?>
<div class="bg-red-500/20 text-red-300 p-3 rounded mb-4 text-center">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<button onclick="openModal()" class="w-full bg-green-600 p-3 rounded font-semibold">
🧮 Compter le fond de caisse
</button>
</div>

<!-- MODALE -->
<div id="modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
<div class="bg-slate-800 p-6 rounded-xl w-full max-w-lg">

<h2 class="text-center text-green-400 font-bold mb-4">🧮 Comptage fond de caisse</h2>

<form method="post" id="openForm">
<input type="hidden" name="details_ouverture" id="details">

<div class="grid grid-cols-2 gap-3 text-sm">
<?php foreach ([0.10,0.20,0.50,1,2,5,10,20,50,100] as $b): ?>
<div>
<label><?= number_format($b,2,',',' ') ?> €</label>
<input type="number" min="0" value="0"
       data-val="<?= $b ?>"
       class="count w-full bg-slate-700 p-2 rounded">
</div>
<?php endforeach; ?>
</div>

<div class="mt-3 text-sm">
💵 Total compté : <strong id="total">0,00 €</strong>
</div>

<div id="err" class="text-red-400 text-sm mt-2 hidden"></div>

<button class="w-full bg-green-600 p-3 rounded mt-4">
✅ Confirmer l’ouverture
</button>
</form>

<button onclick="closeModal()" class="w-full text-gray-400 text-sm mt-2">Annuler</button>
</div>
</div>

<script>
function openModal(){
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}
function closeModal(){
  modal.classList.add('hidden');
}

function calc(){
  let t=0, d={};
  document.querySelectorAll('.count').forEach(i=>{
    let v=parseFloat(i.dataset.val), q=parseInt(i.value)||0;
    if(q>0){ d[v]=q; t+=v*q; }
  });
  total.innerText=t.toLocaleString('fr-FR',{minimumFractionDigits:2})+' €';
  details.value=JSON.stringify(d);
  return t;
}

document.querySelectorAll('.count').forEach(i=>i.addEventListener('input',calc));

openForm.addEventListener('submit',e=>{
  if(calc()<=0){
    e.preventDefault();
    err.innerText="❌ Le fond de caisse est vide.";
    err.classList.remove('hidden');
  }
});
</script>

</body>
</html>
