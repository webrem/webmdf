<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
date_default_timezone_set('America/Cayenne');

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

$now     = new DateTime();
$today   = $now->format('Y-m-d');
$hourNow = (int)$now->format('H');
$force = $isAdmin && isset($_GET['force']) && $_GET['force'] == 1;

$error = "";

/* =========================
   CAISSE OUVERTE (PEU IMPORTE LA DATE)
   ========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM caisse_jour
    WHERE heure_fermeture IS NULL
    ORDER BY date_caisse ASC
    LIMIT 1
");
$stmt->execute();
$caisse = $stmt->get_result()->fetch_assoc();

if (!$caisse) {
    header("Location: dashboard.php");
    exit;
}

$dateCaisse = $caisse['date_caisse'];

/* 🔒 VERROUILLAGE APRÈS VALIDATION */
if (!empty($caisse['validated_at'])) {
    die("❌ Caisse déjà validée par le gérant. Modification interdite.");
}

/* =========================
   CONTRÔLE HORAIRE (SAUF ADMIN)
   ========================= */
if (!$isAdmin && !$force) {
    if ($hourNow < 18) {
        $error = "⏰ Fermeture autorisée à partir de 18h00.";
    }
    if ($hourNow > 24) {
        $error = "❌ Heure limite dépassée (contacter un administrateur).";
    }
}

/* =========================
   VENTES
   ========================= */
$stmt = $conn->prepare("
    SELECT
        SUM(CASE
            WHEN mode_paiement = 'Espèces' THEN prix_total
            WHEN mode_paiement = 'Mixte' THEN paiement_especes
            ELSE 0 END) AS ventes_especes,

        SUM(CASE
            WHEN mode_paiement = 'Carte Bancaire' THEN prix_total
            WHEN mode_paiement = 'Mixte' THEN paiement_cb
            ELSE 0 END) AS ventes_cb
    FROM ventes
    WHERE vente_principale = 1
    AND DATE(date_vente) = ?
");
$stmt->bind_param("s", $dateCaisse);
$stmt->execute();
$v = $stmt->get_result()->fetch_assoc();

$ventesEspeces = (float)($v['ventes_especes'] ?? 0);
$ventesCB      = (float)($v['ventes_cb'] ?? 0);


/* =========================
   ACOMPTES (FIX CB / ESPECES)
   ========================= */
$stmt = $conn->prepare("
    SELECT
        SUM(
            CASE
                WHEN UPPER(mode_paiement) LIKE 'ESP%'
                    THEN prix_total
                ELSE 0
            END
        ) AS acompte_especes,

        SUM(
            CASE
                WHEN mode_paiement IN ('CB','Carte Bancaire','CARTE BANCAIRE','cb')
                    THEN prix_total
                ELSE 0
            END
        ) AS acompte_cb
    FROM ventes_historique
    WHERE LOWER(type) = 'acompte'
    AND DATE(date_vente) = ?
");
$stmt->bind_param("s", $dateCaisse);
$stmt->execute();
$a = $stmt->get_result()->fetch_assoc();

$acompteEspeces = (float)($a['acompte_especes'] ?? 0);
$acompteCB      = (float)($a['acompte_cb'] ?? 0);


/* =========================
   RETRAITS ESPÈCES
   ========================= */
$stmt = $conn->prepare("
    SELECT
        SUM(prix_total) AS retrait_especes
    FROM ventes_historique
    WHERE LOWER(type) = 'retrait'
    AND mode_paiement = 'Espèces'
    AND DATE(date_vente) = ?
");
$stmt->bind_param("s", $dateCaisse);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

/*
 prix_total est déjà négatif (-50),
 donc on additionne tel quel
*/
$retraitsEspeces = (float)($r['retrait_especes'] ?? 0);




/* =========================
   FOND DE CAISSE (OUVERTURE)
   ========================= */
$fondCaisse = (float)($caisse['montant_ouverture'] ?? 0);

/* =========================
   TOTAUX OFFICIELS
   ========================= */
$totalEspeces = round(
    $fondCaisse
    + $ventesEspeces
    + $acompteEspeces
    + $retraitsEspeces, // (négatif)
    2
);

$totalCB      = round($ventesCB + $acompteCB, 2);
$totalGlobal  = round($totalEspeces + $totalCB, 2);

/* =========================
   TRAITEMENT FERMETURE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {





    $detailsEspeces = $_POST['details_especes'] ?? null;

    if (empty($detailsEspeces)) {
        die("❌ ERREUR : détail des billets non transmis.");
    }
    $montantCoffre = isset($_POST['montant_coffre'])
    ? round((float)$_POST['montant_coffre'], 2)
    : 0.0;


    if ($montantCoffre < 0) {
        die("❌ Montant coffre invalide.");
    }
    
    if ($montantCoffre > $totalEspeces) {
        die("❌ Le montant envoyé au coffre ne peut pas dépasser l'argent en caisse.");
    }

    
/* =========================
   ÉTAPE 2 — SAUVEGARDE COMPTAGE ESPÈCES
   ========================= */

// 1️⃣ Récupération du comptage envoyé par le formulaire
$detailsJson = $_POST['details_especes'] ?? '';

// Sécurité : doit être un JSON valide
$details = json_decode($detailsJson, true);
if (!is_array($details)) {
    die("❌ Erreur : comptage espèces invalide.");
}

// 2️⃣ Recalcul TOTAL côté serveur (ANTI TRICHE)
$totalCalcule = 0.0;

foreach ($details as $valeur => $quantite) {
    $valeur = (float)$valeur;
    $quantite = (int)$quantite;

    if ($valeur <= 0 || $quantite < 0) {
        die("❌ Données de comptage incorrectes.");
    }

    $totalCalcule += ($valeur * $quantite);
}

$totalCalcule = round($totalCalcule, 2);

// 3️⃣ Vérification stricte avec le total attendu
if ($totalCalcule !== $totalEspeces) {
    die("❌ Écart détecté : espèces comptées ≠ espèces attendues.");
}

// 4️⃣ Enregistrement du comptage en base
$stmt = $conn->prepare("
    INSERT INTO caisse_comptage
        (caisse_id, type, details_json, total_calcule, user_id)
    VALUES (?, 'fermeture', ?, ?, ?)
");

$stmt->bind_param(
    "isdi",
    $caisse['id'],
    $detailsJson,
    $totalCalcule,
    $_SESSION['user_id']
);

$stmt->execute();

    $stmt = $conn->prepare("
        UPDATE caisse_jour SET
            heure_fermeture = NOW(),
            total_especes = ?,
            total_cb = ?,
            montant_coffre = ?,
            details_especes = ?,
            user_fermeture = ?
        WHERE id = ?

    ");
    $stmt->bind_param(
    "dddsii",
        $totalEspeces,
        $totalCB,
        $montantCoffre,
        $detailsEspeces,
        $_SESSION['user_id'],
        $caisse['id']
    );
    $stmt->execute();

    header("Location: print_confirmation_fermeture.php?id=".$caisse['id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fermeture de caisse</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">

<div class="bg-slate-800 p-6 rounded-xl shadow-xl w-full max-w-md">

<h1 class="text-xl font-bold mb-4 text-center text-red-400">🔴 Fermeture de caisse</h1>

<div class="bg-slate-700 p-4 rounded mb-4 space-y-2 text-sm">
   
   <h1 class="text-xl font-bold mb-4 text-center text-red-400">
🔴 Fermeture de caisse (<?= date('d/m/Y', strtotime($dateCaisse)) ?>)
</h1> 
    <div class="text-blue-400">
      💼 Argent de départ (ouverture) :
      <strong><?= number_format($fondCaisse,2,',',' ') ?> €</strong>
    </div>
    
    <hr class="border-slate-600 my-2">
    
    <div>🛒 Ventes espèces : <strong><?= number_format($ventesEspeces,2,',',' ') ?> €</strong></div>
    <div>💰 Acomptes espèces : <strong><?= number_format($acompteEspeces,2,',',' ') ?> €</strong></div>
    
    <div class="text-red-400">
  ➖ Retraits espèces :
  <strong><?= number_format(abs($retraitsEspeces),2,',',' ') ?> €</strong>
</div>
    
    <div class="border-t border-slate-600 pt-2">💵 <strong>Total espèces :</strong> <?= number_format($totalEspeces,2,',',' ') ?> €</div>

    <hr class="border-slate-600 my-2">

    <div>💳 Ventes CB : <strong><?= number_format($ventesCB,2,',',' ') ?> €</strong></div>
    <div>💳 Acomptes CB : <strong><?= number_format($acompteCB,2,',',' ') ?> €</strong></div>
    <div class="border-t border-slate-600 pt-2">💳 <strong>Total CB :</strong> <?= number_format($totalCB,2,',',' ') ?> €</div>

    <hr class="border-slate-600 my-2">

    <div class="text-lg text-green-400 font-bold text-center">
        TOTAL ENCAISSÉ : <?= number_format($totalGlobal,2,',',' ') ?> €
    </div>

</div>

<div class="mt-4 space-y-2">
  <a target="_blank" href="print_caisse.php?id=<?= $caisse['id'] ?>" class="block text-center bg-slate-600 p-2 rounded">🧾 Imprimer fermeture caisse</a>
  <a target="_blank" href="print_ventes_jour.php?date=<?= $today ?>" class="block text-center bg-slate-600 p-2 rounded">📄 Imprimer ventes du jour</a>
</div>

<button onclick="openModal()" class="w-full bg-red-600 p-3 rounded mt-4">🔒 Fermer la caisse</button>
</div>

<!-- MODALE COMPTAGE -->
<div id="modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
<div class="bg-slate-800 p-6 rounded-xl w-full max-w-lg">

<h2 class="text-yellow-400 font-bold mb-4 text-center">🧮 Comptage caisse</h2>

<form method="post" id="closeForm">

<input type="hidden" name="details_especes" id="details_especes">

<div class="grid grid-cols-2 gap-3 text-sm">
<?php foreach ([0.10,0.20,0.50,1,2,5,10,20,50,100] as $b): ?>
<div>
<label><?= number_format($b,2,',',' ') ?> €</label>
<input type="number" min="0" value="0" data-val="<?= $b ?>" class="count w-full bg-slate-700 p-2 rounded">
</div>
<?php endforeach; ?>
</div>

<div class="mt-3 text-sm">
💵 Compté : <strong id="counted">0,00 €</strong><br>
💵 Attendu : <strong><?= number_format($totalEspeces,2,',',' ') ?> €</strong>
</div>

<div class="mt-3 text-sm">
  🔐 Montant envoyé au coffre :
  <input
    type="number"
    step="0.01"
    min="0"
    name="montant_coffre"
    class="w-full bg-slate-700 p-2 rounded mt-1"
    placeholder="Ex: 50.00"
    required
  >
</div>


<label class="flex items-center gap-2 mt-3 text-sm">
<input type="checkbox" id="cbOK"> 💳 CB vérifié
</label>

<div id="err" class="text-red-400 text-sm mt-2 hidden"></div>

<button class="w-full bg-green-600 p-3 rounded mt-4">✅ Confirmer fermeture</button>
</form>

<button onclick="closeModal()" class="w-full text-gray-400 text-sm mt-2">Annuler</button>

</div>
</div>

<script>
const expectedCash = <?= json_encode($totalEspeces) ?>;

function openModal() {
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}
function closeModal() {
  modal.classList.add('hidden');
}

function calc() {
  let t = 0;
  let details = {};

  document.querySelectorAll('.count').forEach(i => {
    const val = parseFloat(i.dataset.val);
    const qty = parseInt(i.value) || 0;
    if (qty > 0) {
      details[val] = qty;
      t += val * qty;
    }
  });

  document.getElementById('counted').innerText =
    t.toLocaleString('fr-FR',{minimumFractionDigits:2})+' €';

  document.getElementById('details_especes').value =
    JSON.stringify(details);

  return Math.round(t*100)/100;
}

document.querySelectorAll('.count').forEach(i => i.addEventListener('input', calc));

document.getElementById('closeForm').addEventListener('submit', e => {
  const counted = calc();
  const cbOK = document.getElementById('cbOK').checked;
  const err = document.getElementById('err');
  err.classList.add('hidden');

  if (counted !== expectedCash) {
    e.preventDefault();
    err.innerText = "❌ Montant espèces incorrect.";
    err.classList.remove('hidden');
  }
  if (!cbOK) {
    e.preventDefault();
    err.innerText = "❌ Confirmation CB obligatoire.";
    err.classList.remove('hidden');
  }
});
</script>

</body>
</html>
