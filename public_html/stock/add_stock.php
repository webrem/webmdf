<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('America/Cayenne');

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
if (!$isAdmin) die('Accès refusé');

$msg = '';
$isOk = false;

function ffloat($v): float {
    $v = trim((string)$v);
    if ($v === '') return 0.0;
    $v = str_replace([' ', ','], ['', '.'], $v);
    return is_numeric($v) ? (float)$v : 0.0;
}
function now_guyane(): string { return date('Y-m-d H:i:s'); }

/**
 * 💰 Arrondi R.E.Mobiles (nouvelle règle)
 * -> prix fini par .00 ou .05 (jamais .99)
 * - < 5€ : arrondi au 0.05 le plus proche (min 0.00)
 * - >= 5€ : arrondi au 0.05 le plus proche
 * Option: si tu veux TOUJOURS arrondir vers le haut, dis-moi.
 */
function arrondiREM(float $prix): float {
    // Arrondi au pas de 0.05 (5 centimes)
    $rounded = round($prix / 0.05) * 0.05;
    // Sécurité anti -0.00
    if (abs($rounded) < 0.00001) $rounded = 0.0;
    return round($rounded, 2);
}

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

    $prix_achat  = ffloat($_POST['prix_achat'] ?? 0);
    $frais_tr    = ffloat($_POST['frais_transport'] ?? 0);
    $douane_pct  = ffloat($_POST['douane_pct'] ?? 0);
    $autres      = ffloat($_POST['autres_frais'] ?? 0);
    $marge_pct   = ffloat($_POST['marge_pct'] ?? 50);
    $tva_pct     = ffloat($_POST['tva_pct'] ?? 0);

    $qte         = max(1, (int)($_POST['quantite'] ?? 1));
    $seuil_min   = (int)($_POST['seuil_min'] ?? 0);
    $seuil_max   = (int)($_POST['seuil_max'] ?? 0);
    $unite       = trim($_POST['unite'] ?? 'pièce');
    $actif       = isset($_POST['actif']) ? 1 : 0;

    $tags        = trim($_POST['tags'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');

    if ($reference === '' || $designation === '') {
        $msg = "⚠️ Référence et désignation obligatoires.";
    } else {
        $cout = $prix_achat + $frais_tr + ($prix_achat * $douane_pct / 100) + $autres;

        $prix_vente_calc  = $cout * (1 + $marge_pct / 100) * (1 + $tva_pct / 100);
        $prix_vente_input = ffloat($_POST['prix_vente'] ?? 0);
        $prix_vente       = ($prix_vente_input > 0) ? $prix_vente_input : $prix_vente_calc;

        // ✅ Nouveau type d'arrondi (0.00 / 0.05)
        $prix_vente = arrondiREM($prix_vente);

        $now = now_guyane();

        try {
            $pdo->beginTransaction();

            $sqlFind = "SELECT id, quantite FROM stock_articles WHERE reference = :reference";
            $paramsFind = [':reference' => $reference];

            if ($imei !== '') {
                $sqlFind .= " OR (imei IS NOT NULL AND imei <> '' AND imei = :imei)";
                $paramsFind[':imei'] = $imei;
            }
            $sqlFind .= " LIMIT 1";

            $st = $pdo->prepare($sqlFind);
            $st->execute($paramsFind);
            $found = $st->fetch(PDO::FETCH_ASSOC);

            if ($found) {
                $id = (int)$found['id'];
                $newqte = (int)$found['quantite'] + $qte;

                $upd = $pdo->prepare("
                    UPDATE stock_articles SET
                        ean=:ean, imei=:imei, designation=:designation, categorie=:categorie, marque=:marque,
                        modele=:modele, couleur=:couleur, capacite=:capacite, etat=:etat,
                        fournisseur=:fournisseur, facture_achat=:facture_achat, date_achat=:date_achat,
                        lieu=:lieu, garantie_mois=:garantie_mois,
                        prix_achat=:prix_achat, frais_transport=:frais_transport, douane_pct=:douane_pct, autres_frais=:autres_frais,
                        cout_revient=:cout_revient, marge_pct=:marge_pct, prix_vente=:prix_vente, tva_pct=:tva_pct,
                        quantite=:quantite, seuil_min=:seuil_min, seuil_max=:seuil_max, unite=:unite, actif=:actif,
                        tags=:tags, notes=:notes, updated_at=:updated_at
                    WHERE id=:id
                ");
                $upd->execute([
                    ':ean' => $ean,
                    ':imei' => $imei,
                    ':designation' => $designation,
                    ':categorie' => $categorie,
                    ':marque' => $marque,
                    ':modele' => $modele,
                    ':couleur' => $couleur,
                    ':capacite' => $capacite,
                    ':etat' => $etat,
                    ':fournisseur' => $fournisseur,
                    ':facture_achat' => $facture,
                    ':date_achat' => $date_achat,
                    ':lieu' => $lieu,
                    ':garantie_mois' => $garantie,
                    ':prix_achat' => $prix_achat,
                    ':frais_transport' => $frais_tr,
                    ':douane_pct' => $douane_pct,
                    ':autres_frais' => $autres,
                    ':cout_revient' => $cout,
                    ':marge_pct' => $marge_pct,
                    ':prix_vente' => $prix_vente,
                    ':tva_pct' => $tva_pct,
                    ':quantite' => $newqte,
                    ':seuil_min' => $seuil_min,
                    ':seuil_max' => $seuil_max,
                    ':unite' => $unite,
                    ':actif' => $actif,
                    ':tags' => $tags,
                    ':notes' => $notes,
                    ':updated_at' => $now,
                    ':id' => $id,
                ]);

                $pdo->commit();
                $msg = "✅ Article mis à jour (+{$qte}) avec arrondi automatique (.00 / .05) appliqué.";
                $isOk = true;
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO stock_articles(
                        reference, ean, imei, designation, categorie, marque, modele, couleur, capacite, etat,
                        fournisseur, facture_achat, date_achat, lieu, garantie_mois,
                        prix_achat, frais_transport, douane_pct, autres_frais, cout_revient, marge_pct, prix_vente, tva_pct,
                        quantite, seuil_min, seuil_max, unite, actif, tags, notes, created_at, updated_at
                    ) VALUES (
                        :reference, :ean, :imei, :designation, :categorie, :marque, :modele, :couleur, :capacite, :etat,
                        :fournisseur, :facture_achat, :date_achat, :lieu, :garantie_mois,
                        :prix_achat, :frais_transport, :douane_pct, :autres_frais, :cout_revient, :marge_pct, :prix_vente, :tva_pct,
                        :quantite, :seuil_min, :seuil_max, :unite, :actif, :tags, :notes, :created_at, :updated_at
                    )
                ");
                $ins->execute([
                    ':reference' => $reference,
                    ':ean' => $ean,
                    ':imei' => $imei,
                    ':designation' => $designation,
                    ':categorie' => $categorie,
                    ':marque' => $marque,
                    ':modele' => $modele,
                    ':couleur' => $couleur,
                    ':capacite' => $capacite,
                    ':etat' => $etat,
                    ':fournisseur' => $fournisseur,
                    ':facture_achat' => $facture,
                    ':date_achat' => $date_achat,
                    ':lieu' => $lieu,
                    ':garantie_mois' => $garantie,
                    ':prix_achat' => $prix_achat,
                    ':frais_transport' => $frais_tr,
                    ':douane_pct' => $douane_pct,
                    ':autres_frais' => $autres,
                    ':cout_revient' => $cout,
                    ':marge_pct' => $marge_pct,
                    ':prix_vente' => $prix_vente,
                    ':tva_pct' => $tva_pct,
                    ':quantite' => $qte,
                    ':seuil_min' => $seuil_min,
                    ':seuil_max' => $seuil_max,
                    ':unite' => $unite,
                    ':actif' => $actif,
                    ':tags' => $tags,
                    ':notes' => $notes,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);

                $pdo->commit();
                $msg = "✅ Nouvel article ajouté (arrondi automatique .00 / .05).";
                $isOk = true;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $msg = "❌ Erreur : " . $e->getMessage();
            $isOk = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>📦 Ajouter un produit — Stock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="addstock.css">

<link rel="stylesheet" href="../assets/style.css">

</head>
<body>
<div class="container py-4">
    
    
  <h2><i class="bi bi-box-seam"></i> Gestion du stock — Ajout / Mise à jour</h2>


<?php include '../header.php'; ?>

  <?php if($msg): ?>
    <div class="alert <?= $isOk ? 'alert-success' : 'alert-danger' ?> text-center">
      <?= htmlspecialchars($msg) ?>
    </div>
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

    <div class="section-title"><i class="bi bi-receipt"></i> Achat / Fournisseur</div>
    <div class="row g-3">
      <div class="col-md-3"><label>Fournisseur</label><input name="fournisseur" class="form-control"></div>
      <div class="col-md-3"><label>Facture achat</label><input name="facture" class="form-control"></div>
      <div class="col-md-3"><label>Date achat</label><input name="date_achat" type="date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></div>
      <div class="col-md-3"><label>Lieu</label><input name="lieu" class="form-control" value="Entrepôt"></div>
      <div class="col-md-3"><label>Garantie (mois)</label><input name="garantie" type="number" class="form-control" value="0"></div>
    </div>

    <div class="section-title"><i class="bi bi-pencil"></i> Notes</div>
    <div class="row g-3">
      <div class="col-md-6"><label>Tags</label><input name="tags" class="form-control" placeholder="ex: iPhone, 128Go, Noir"></div>
      <div class="col-md-6"><label>Notes internes</label><input name="notes" class="form-control"></div>
    </div>

    <div class="mt-4 text-center">
      <a href="stock.php" class="btn btn-outline-secondary ms-2 px-4 py-2">⬅ Retour</a>
      <button class="btn btn-primary px-5 py-2 fw-bold">💾 Enregistrer</button>
      <button type="reset" class="btn btn-outline-secondary ms-2 px-4 py-2">♻ Réinitialiser</button>
    </div>
  </form>
</div>

<script>
function fval(n){ return parseFloat((n||'0').toString().replace(',','.'))||0; }

// ✅ Arrondi au pas de 0.05 (donc fini par 0 ou 5)
function arrondiREM(val){
  const r = Math.round(val / 0.05) * 0.05;
  return Math.round(r * 100) / 100;
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
</body>
</html>
