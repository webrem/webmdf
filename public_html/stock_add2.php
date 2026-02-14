 <?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

include 'header.php';
require_once __DIR__ . '/stock_utils.php';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur de connexion : " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference   = trim($_POST['reference'] ?? '');
    $ean         = trim($_POST['ean'] ?? '');
    $imei        = trim($_POST['imei'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $categorie   = trim($_POST['categorie'] ?? '');
    $marque      = trim($_POST['marque'] ?? '');
    $modele      = trim($_POST['modele'] ?? '');
    $couleur     = trim($_POST['couleur'] ?? '');
    $capacite    = trim($_POST['capacite'] ?? '');
    $etat        = trim($_POST['etat'] ?? 'Neuf');
    $fournisseur = trim($_POST['fournisseur'] ?? '');
    $facture     = trim($_POST['facture'] ?? '');
    $date_achat  = $_POST['date_achat'] ?? date('Y-m-d');
    $lieu        = trim($_POST['lieu'] ?? 'Entrepôt');
    $garantie    = (int)($_POST['garantie'] ?? 0);
    $prix_achat  = as_float($_POST['prix_achat'] ?? 0);
    $frais_tr    = as_float($_POST['frais_transport'] ?? 0);
    $douane_pct  = as_float($_POST['douane_pct'] ?? 0);
    $autres      = as_float($_POST['autres_frais'] ?? 0);
    $marge_pct   = as_float($_POST['marge_pct'] ?? 50);
    $tva_pct     = as_float($_POST['tva_pct'] ?? 0);
    $qte         = (int)($_POST['quantite'] ?? 1);
    $min         = (int)($_POST['seuil_min'] ?? 0);
    $max         = (int)($_POST['seuil_max'] ?? 0);
    $unite       = trim($_POST['unite'] ?? 'pièce');
    $actif       = isset($_POST['actif']) ? 1 : 0;
    $tags        = trim($_POST['tags'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');

    // --- Calcul des coûts ---
    $cout = $prix_achat + $frais_tr + ($prix_achat * $douane_pct / 100) + $autres;
    $prix_vente_calc = $cout * (1 + $marge_pct / 100) * (1 + $tva_pct / 100);
    $prix_vente = as_float($_POST['prix_vente'] ?? $prix_vente_calc);

    // --- 💰 Arrondi intelligent R.E.Mobiles ---
    if ($prix_vente < 5) $prix_vente = 4.99;
    elseif ($prix_vente < 10) $prix_vente = 9.99;
    elseif ($prix_vente < 15) $prix_vente = 14.99;
    elseif ($prix_vente < 20) $prix_vente = 19.99;
    else $prix_vente = ceil($prix_vente / 5) * 5 - 0.01;

    // --- Vérifie si déjà existant ---
    $stmt = $conn->prepare("SELECT id, quantite FROM stock_articles WHERE reference = ? OR (imei IS NOT NULL AND imei = ?) LIMIT 1");
    bindParamsDynamic($stmt, [$reference, $imei]);
    $stmt->execute();
    $res = $stmt->get_result();

    $now = now_guyane();

    if ($a = $res->fetch_assoc()) {
        if ($isAdmin) {
            $id = $a['id'];
            $newqte = $a['quantite'] + $qte;

            $upd = $conn->prepare("
                UPDATE stock_articles SET
                ean=?, imei=?, designation=?, categorie=?, marque=?, modele=?, couleur=?, capacite=?, etat=?,
                fournisseur=?, facture_achat=?, date_achat=?, lieu=?, garantie_mois=?, prix_achat=?, frais_transport=?,
                douane_pct=?, autres_frais=?, cout_revient=?, marge_pct=?, prix_vente=?, tva_pct=?, quantite=?,
                seuil_min=?, seuil_max=?, unite=?, actif=?, tags=?, notes=?, updated_at=?
                WHERE id=?");
            bindParamsDynamic($upd, [
                $ean, $imei, $designation, $categorie, $marque, $modele, $couleur, $capacite, $etat,
                $fournisseur, $facture, $date_achat, $lieu, $garantie, $prix_achat, $frais_tr, $douane_pct,
                $autres, $cout, $marge_pct, $prix_vente, $tva_pct, $newqte, $min, $max, $unite, $actif,
                $tags, $notes, $now, $id
            ]);
            $upd->execute();
            $msg = "✅ Article mis à jour (+$qte) avec arrondi automatique appliqué.";
        } else {
            $msg = "⚠️ Vous n’avez pas les droits pour modifier cet article.";
        }
    } else {
        $ins = $conn->prepare("
            INSERT INTO stock_articles(reference, ean, imei, designation, categorie, marque, modele, couleur, capacite, etat,
              fournisseur, facture_achat, date_achat, lieu, garantie_mois, prix_achat, frais_transport,
              douane_pct, autres_frais, cout_revient, marge_pct, prix_vente, tva_pct, quantite,
              seuil_min, seuil_max, unite, actif, tags, notes, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        bindParamsDynamic($ins, [
            $reference, $ean, $imei, $designation, $categorie, $marque, $modele, $couleur, $capacite, $etat,
            $fournisseur, $facture, $date_achat, $lieu, $garantie, $prix_achat, $frais_tr, $douane_pct,
            $autres, $cout, $marge_pct, $prix_vente, $tva_pct, $qte, $min, $max, $unite, $actif,
            $tags, $notes, $now, $now
        ]);
        $ins->execute();
        $msg = "✅ Nouvel article ajouté avec succès (prix arrondi automatiquement).";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>📦 Ajouter un article — R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  background: radial-gradient(circle at top left,#0a0a0a,#1a1a1a);
  color: #fff;
  font-family: "Poppins", sans-serif;
}
h2 {
  color: #0dcaf0;
  text-transform: uppercase;
  font-weight: 800;
  letter-spacing: .5px;
  text-align:center;
  margin-bottom:2rem;
}
.glass {
  background: rgba(20,20,20,0.85);
  border: 1px solid rgba(13,202,240,0.25);
  border-radius: 18px;
  backdrop-filter: blur(8px);
  box-shadow: 0 0 20px rgba(13,202,240,0.15);
  padding: 25px;
  transition: all .3s ease;
}
.glass:hover { transform: translateY(-4px); box-shadow: 0 0 25px rgba(13,202,240,.3); }
.section-title {
  color: #0dcaf0;
  font-weight: 700;
  margin-top: 25px;
  border-left: 5px solid #0dcaf0;
  padding-left: 10px;
  text-transform: uppercase;
}
label { font-weight:600; color:#cfeeff; }
.form-control, .form-select {
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.15);
  color: #fff;
  border-radius: 10px;
}
.form-control:focus, .form-select:focus {
  border-color: #0dcaf0;
  box-shadow: 0 0 0 0.2rem rgba(13,202,240,.25);
}
.btn-primary {
  background: linear-gradient(90deg,#0d6efd,#0dcaf0);
  border:none; font-weight:700; border-radius:12px;
  transition:.25s;
}
.btn-primary:hover { transform:scale(1.05); box-shadow:0 0 20px rgba(13,202,240,0.5); }
.btn-outline-secondary {
  border:2px solid #666; color:#ddd; border-radius:12px;
}
.btn-outline-secondary:hover { background:#666; color:#fff; }
.alert-success {
  background: rgba(13,202,240,0.15);
  border:1px solid #0dcaf0;
  color:#0dcaf0;
  font-weight:600;
}
</style>
</head>
<body>
<div class="container py-4">
  <h2><i class="bi bi-box-seam"></i> Gestion du stock — Ajout / Mise à jour</h2>

  <?php if($msg): ?>
    <div class="alert alert-success text-center"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="post" class="glass mb-5">
    <div class="section-title"><i class="bi bi-phone"></i> Informations Produit</div>
    <div class="row g-3">
      <div class="col-md-3"><label>Référence *</label><input name="reference" class="form-control" required></div>
      <div class="col-md-3"><label>EAN / Code Barre</label>
        <div class="input-group">
          <input name="ean" id="ean" class="form-control" placeholder="9781234567897">
          <button class="btn btn-outline-secondary" type="button" id="generateEAN">🔄</button>
        </div>
      </div>
      <div class="col-md-3"><label>IMEI / Série</label><input name="imei" class="form-control"></div>
      <div class="col-md-3"><label>Désignation *</label><input name="designation" class="form-control" required></div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-md-2"><label>Catégorie</label><input name="categorie" class="form-control"></div>
      <div class="col-md-2"><label>Marque</label><input name="marque" class="form-control"></div>
      <div class="col-md-2"><label>Modèle</label><input name="modele" class="form-control"></div>
      <div class="col-md-2"><label>Couleur</label><input name="couleur" class="form-control"></div>
      <div class="col-md-2"><label>Capacité</label><input name="capacite" class="form-control"></div>
      <div class="col-md-2">
        <label>État</label>
        <select name="etat" class="form-select">
          <option>Neuf</option><option>Occasion</option><option>Reconditionné</option>
        </select>
      </div>
    </div>

    <div class="section-title"><i class="bi bi-cash-stack"></i> Tarification</div>
    <div class="row g-3">
      <div class="col-md-2"><label>Prix achat (€)</label><input name="prix_achat" class="form-control calc" value="0"></div>
      <div class="col-md-2"><label>Transport (€)</label><input name="frais_transport" class="form-control calc" value="0"></div>
      <div class="col-md-2"><label>Douane %</label><input name="douane_pct" class="form-control calc" value="0"></div>
      <div class="col-md-2"><label>Autres frais (€)</label><input name="autres_frais" class="form-control calc" value="0"></div>
      <div class="col-md-2"><label>Marge %</label><input name="marge_pct" class="form-control calc" value="50"></div>
      <div class="col-md-2"><label>TVA %</label><input name="tva_pct" class="form-control calc" value="0"></div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-md-3"><label>Coût de revient</label><input id="cout_revient" class="form-control fw-bold text-danger" readonly></div>
      <div class="col-md-3"><label>Prix vente TTC (€)</label><input name="prix_vente" id="prix_vente" class="form-control fw-bold text-success"></div>
    </div>

    <div class="section-title"><i class="bi bi-stack"></i> Stock</div>
    <div class="row g-3">
      <div class="col-md-2"><label>Quantité *</label><input name="quantite" type="number" class="form-control" min="1" value="1"></div>
      <div class="col-md-2"><label>Seuil Min</label><input name="seuil_min" type="number" class="form-control" value="0"></div>
      <div class="col-md-2"><label>Seuil Max</label><input name="seuil_max" type="number" class="form-control" value="0"></div>
      <div class="col-md-2"><label>Unité</label><input name="unite" class="form-control" value="pièce"></div>
      <div class="col-md-2 d-flex align-items-center">
        <div class="form-check mt-4">
          <input type="checkbox" class="form-check-input" name="actif" id="actif" checked>
          <label for="actif" class="form-check-label fw-bold text-success">✔ Actif</label>
        </div>
      </div>
    </div>

    <div class="section-title"><i class="bi bi-pencil"></i> Notes</div>
    <div class="row g-3">
      <div class="col-md-6"><label>Tags</label><input name="tags" class="form-control" placeholder="ex: iPhone, 128Go, Noir"></div>
      <div class="col-md-6"><label>Notes internes</label><input name="notes" class="form-control"></div>
    </div>

    <div class="mt-4 text-center">
      <button class="btn btn-primary px-5 py-2 fw-bold">💾 Enregistrer</button>
      <button type="reset" class="btn btn-outline-secondary ms-2 px-4 py-2">♻ Réinitialiser</button>
    </div>
  </form>
</div>

<script>
function fval(n){ return parseFloat((n||'0').toString().replace(',','.'))||0; }
function arrondiREM(val){
  if(val < 5) return 4.99;
  else if(val < 10) return 9.99;
  else if(val < 15) return 14.99;
  else if(val < 20) return 19.99;
  else return Math.ceil(val / 5)*5 - 0.01;
}
function recalc(){
  const pa=fval(document.querySelector('[name=prix_achat]').value);
  const tr=fval(document.querySelector('[name=frais_transport]').value);
  const dou=fval(document.querySelector('[name=douane_pct]').value);
  const aut=fval(document.querySelector('[name=autres_frais]').value);
  const marge=fval(document.querySelector('[name=marge_pct]').value);
  const tva=fval(document.querySelector('[name=tva_pct]').value);
  const cout=pa+tr+(pa*dou/100)+aut;
  const pv_ht=cout*(1+marge/100);
  const pv_ttc=arrondiREM(pv_ht*(1+tva/100));
  document.getElementById('cout_revient').value=cout.toFixed(2);
  const fieldPv=document.getElementById('prix_vente');
  if(!fieldPv.dataset.touched){ fieldPv.value=pv_ttc.toFixed(2); }
}
document.querySelectorAll('.calc').forEach(i=>i.addEventListener('input',recalc));
document.getElementById('prix_vente').addEventListener('input',e=>e.target.dataset.touched=true);
recalc();

function generateEAN13(){
  let base=Math.floor(100000000000+Math.random()*900000000000).toString();
  let sum=0;for(let i=0;i<12;i++){let d=parseInt(base.charAt(i));sum+=(i%2===0)?d:d*3;}
  let check=(10-(sum%10))%10;
  document.getElementById('ean').value=base+check;
}
document.getElementById('generateEAN').addEventListener('click',generateEAN13);
</script>

</body> </html> ```
