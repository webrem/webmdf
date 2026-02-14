<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 include 'header.php'; ?>
<h2 class="text-center mb-4 fw-bold">📦 GESTION DE STOCK - AJOUT / MISE À JOUR</h2>
<?php
require_once __DIR__ . '/stock_utils.php';

$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) { die("Erreur de connexion : " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$msg = ''; $existing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference  = trim($_POST['reference'] ?? '');
    $ean        = trim($_POST['ean'] ?? '');
    $imei       = trim($_POST['imei'] ?? '');
    $designation= trim($_POST['designation'] ?? '');
    $categorie  = trim($_POST['categorie'] ?? '');
    $marque     = trim($_POST['marque'] ?? '');
    $modele     = trim($_POST['modele'] ?? '');
    $couleur    = trim($_POST['couleur'] ?? '');
    $capacite   = trim($_POST['capacite'] ?? '');
    $etat       = trim($_POST['etat'] ?? 'Neuf');
    $fournisseur= trim($_POST['fournisseur'] ?? '');
    $facture    = trim($_POST['facture'] ?? '');
    $date_achat = $_POST['date_achat'] ?? date('Y-m-d');
    $lieu       = trim($_POST['lieu'] ?? 'Entrepôt');
    $garantie   = (int)($_POST['garantie'] ?? 0);
    $prix_achat = as_float($_POST['prix_achat'] ?? 0);
    $frais_tr   = as_float($_POST['frais_transport'] ?? 0);
    $douane_pct = as_float($_POST['douane_pct'] ?? 0);
    $autres     = as_float($_POST['autres_frais'] ?? 0);
    $marge_pct  = as_float($_POST['marge_pct'] ?? 50);
    $tva_pct    = as_float($_POST['tva_pct'] ?? 0);
    $qte        = (int)($_POST['quantite'] ?? 1);
    $min        = (int)($_POST['seuil_min'] ?? 0);
    $max        = (int)($_POST['seuil_max'] ?? 0);
    $unite      = trim($_POST['unite'] ?? 'pièce');
    $actif      = isset($_POST['actif']) ? 1 : 0;
    $tags       = trim($_POST['tags'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');
    $admin_code = $_POST['admin_code'] ?? '';

    $cout = $prix_achat + $frais_tr + ($prix_achat * $douane_pct / 100) + $autres;
    $prix_vente_calc = $cout * (1 + ($marge_pct / 100));
    $prix_vente = as_float($_POST['prix_vente'] ?? $prix_vente_calc);

    // Vérifie si article existe déjà
    $stmt = $conn->prepare("SELECT id, quantite FROM stock_articles WHERE reference=? OR (imei IS NOT NULL AND imei=?) LIMIT 1");
    $stmt->bind_param("ss", $reference, $imei);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($existing = $res->fetch_assoc()) {
        if (!require_admin_or_code($conn, $admin_code)) {
            $msg = "🛑 Modification bloquée : code admin requis.";
        } else {
            $id = (int)$existing['id'];
            $newqte = $existing['quantite'] + $qte;
            $now = now_guyane();

            $upd = $conn->prepare("UPDATE stock_articles SET ean=?, imei=?, designation=?, categorie=?, marque=?, modele=?, couleur=?, capacite=?, etat=?, fournisseur=?, facture_achat=?, date_achat=?, lieu=?, garantie_mois=?, prix_achat=?, frais_transport=?, douane_pct=?, autres_frais=?, cout_revient=?, marge_pct=?, prix_vente=?, tva_pct=?, quantite=?, seuil_min=?, seuil_max=?, unite=?, actif=?, tags=?, notes=?, updated_at=? WHERE id=?");
            $upd->bind_param("sssssssssssssiddddddiddiiissssii",
                $ean,$imei,$designation,$categorie,$marque,$modele,$couleur,$capacite,$etat,$fournisseur,$facture,$date_achat,$lieu,$garantie,$prix_achat,$frais_tr,$douane_pct,$autres,$cout,$marge_pct,$prix_vente,$tva_pct,$newqte,$min,$max,$unite,$actif,$tags,$notes,$now,$id
            );
            $upd->execute();

            $mv = $conn->prepare("INSERT INTO stock_mouvements(article_id,type,qte,prix_unitaire,motif,user,created_at) VALUES (?,?,?,?,?,?,?)");
            $motif = "Mise à jour du stock (admin)";
            $u = $_SESSION['username'] ?? 'system';
            $mv->bind_param("isidsss", $id, $t='modif', $qte, $prix_vente, $motif, $u, $now);
            $mv->execute();

            $msg = "✅ Article mis à jour (+$qte ajoutées).";
        }
    } else {
        $now = now_guyane();
        $ins = $conn->prepare("INSERT INTO stock_articles(reference,ean,imei,designation,categorie,marque,modele,couleur,capacite,etat,fournisseur,facture_achat,date_achat,lieu,garantie_mois,prix_achat,frais_transport,douane_pct,autres_frais,cout_revient,marge_pct,prix_vente,tva_pct,quantite,seuil_min,seuil_max,unite,actif,tags,notes,created_at,updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $ins->bind_param("sssssssssssssiddddddiddiiissssss",
            $reference,$ean,$imei,$designation,$categorie,$marque,$modele,$couleur,$capacite,$etat,$fournisseur,$facture,$date_achat,$lieu,$garantie,$prix_achat,$frais_tr,$douane_pct,$autres,$cout,$marge_pct,$prix_vente,$tva_pct,$qte,$min,$max,$unite,$actif,$tags,$notes,$now,$now
        );
        $ins->execute(); $id = $ins->insert_id;

        $mv = $conn->prepare("INSERT INTO stock_mouvements(article_id,type,qte,prix_unitaire,motif,user,created_at) VALUES (?,?,?,?,?,?,?)");
        $motif="Ajout stock (nouvel article)";
        $u = $_SESSION['username'] ?? 'system';
        $mv->bind_param("isidsss",$id,$t='ajout',$qte,$prix_vente,$motif,$u,$now);
        $mv->execute();

        $msg = "✅ Nouvel article ajouté avec succès.";
    }
}
?>

<style>
body { background: #f8f9fa; }
.card-pro { max-width: 1100px; margin: auto; border: 2px solid #ccc; border-radius: 15px; }
.section-title { font-weight: 700; font-size: 1.1rem; background: #212529; color: #fff; padding: 6px 12px; border-radius: 6px; }
label { font-weight: 600; }
input[readonly] { background: #e9ecef; }
</style>

<div class="container mb-5">
  <?php if($msg): ?><div class="alert alert-info text-center fw-bold"><?=h($msg)?></div><?php endif; ?>

  <form method="post" class="card card-pro shadow-lg p-4 bg-white" id="formStock">
    <h4 class="text-center mb-4 fw-bold text-primary">🧾 Fiche d'article</h4>

    <div class="section-title mt-3 mb-3">📱 Informations Produit</div>
    <div class="row g-3">
      <div class="col-md-3"><label>Référence *</label><input name="reference" class="form-control" required></div>
      <div class="col-md-4">
        <label>EAN / Code Barre</label>
        <div class="input-group">
          <input name="ean" id="ean" class="form-control" placeholder="Ex : 9781234567897">
          <button class="btn btn-outline-secondary" type="button" id="generateEAN">🔄 Générer</button>
        </div>
      </div>
      <div class="col-md-3"><label>IMEI / N° Série</label><input name="imei" class="form-control"></div>
      <div class="col-md-2"><label>Désignation *</label><input name="designation" class="form-control" required></div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-md-2"><label>Catégorie</label><input name="categorie" class="form-control"></div>
      <div class="col-md-2"><label>Marque</label><input name="marque" class="form-control"></div>
      <div class="col-md-2"><label>Modèle</label><input name="modele" class="form-control"></div>
      <div class="col-md-2"><label>Couleur</label><input name="couleur" class="form-control"></div>
      <div class="col-md-2"><label>Capacité</label><input name="capacite" class="form-control"></div>
      <div class="col-md-2"><label>État</label>
        <select name="etat" class="form-select"><option>Neuf</option><option>Occasion</option><option>Reconditionné</option></select>
      </div>
    </div>

    <div class="section-title mt-4 mb-3">🏢 Fournisseur</div>
    <div class="row g-3">
      <div class="col-md-3"><label>Fournisseur</label><input name="fournisseur" class="form-control"></div>
      <div class="col-md-2"><label>Facture</label><input name="facture" class="form-control"></div>
      <div class="col-md-2"><label>Date d'achat</label><input type="date" name="date_achat" class="form-control" value="<?=date('Y-m-d')?>"></div>
      <div class="col-md-2"><label>Lieu</label><input name="lieu" class="form-control" value="Entrepôt"></div>
      <div class="col-md-2"><label>Garantie (mois)</label><input name="garantie" type="number" class="form-control" value="0"></div>
    </div>

    <div class="section-title mt-4 mb-3">💰 Tarification et Coûts</div>
    <div class="row g-3">
      <div class="col-md-2"><label>Prix d'achat (€)</label><input name="prix_achat" class="form-control calc sensible" value="0"></div>
      <div class="col-md-2"><label>Frais transport (€)</label><input name="frais_transport" class="form-control calc sensible" value="0"></div>
      <div class="col-md-2"><label>% Douane</label><input name="douane_pct" class="form-control calc sensible" value="0"></div>
      <div class="col-md-2"><label>Autres frais (€)</label><input name="autres_frais" class="form-control calc sensible" value="0"></div>
      <div class="col-md-2"><label>% Marge</label><input name="marge_pct" class="form-control calc sensible" value="50"></div>
      <div class="col-md-2"><label>% TVA</label><input name="tva_pct" class="form-control calc sensible" value="0"></div>
      <div class="col-md-3"><label>Coût de revient</label><input id="cout_revient" class="form-control fw-bold text-danger" readonly></div>
      <div class="col-md-3"><label>Prix de vente TTC (€)</label><input name="prix_vente" id="prix_vente" class="form-control fw-bold text-success sensible"></div>
    </div>

    <div class="section-title mt-4 mb-3">📦 Gestion du Stock</div>
    <div class="row g-3">
      <div class="col-md-2"><label>Quantité *</label><input name="quantite" type="number" class="form-control" min="1" value="1"></div>
      <div class="col-md-2"><label>Seuil Min</label><input name="seuil_min" type="number" class="form-control" value="0"></div>
      <div class="col-md-2"><label>Seuil Max</label><input name="seuil_max" type="number" class="form-control" value="0"></div>
      <div class="col-md-2"><label>Unité</label><input name="unite" class="form-control" value="pièce"></div>
      <div class="col-md-2 d-flex align-items-center">
        <div class="form-check mt-3">
          <input type="checkbox" class="form-check-input" name="actif" id="actif" checked>
          <label for="actif" class="form-check-label fw-bold">Actif</label>
        </div>
      </div>
    </div>

    <div class="section-title mt-4 mb-3">🗒️ Notes et Tags</div>
    <div class="row g-3">
      <div class="col-md-6"><label>Tags</label><input name="tags" class="form-control" placeholder="ex: iPhone, 128Go, Noir"></div>
      <div class="col-md-6"><label>Notes internes</label><input name="notes" class="form-control"></div>
    </div>

    <?php if(!is_admin()): ?>
      <input type="hidden" name="admin_code" id="admin_code">
    <?php endif; ?>

    <div class="mt-5 text-center">
      <button class="btn btn-primary px-4 fw-bold">💾 Enregistrer</button>
      <button type="reset" class="btn btn-outline-secondary ms-2">♻️ Réinitialiser</button>
    </div>
  </form>
</div>

<!-- 🔐 Modal code admin -->
<div class="modal fade" id="adminModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-dark text-white">
      <h5 class="modal-title">🔐 Validation administrateur requise</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <label class="form-label">Code admin</label>
      <input type="password" class="form-control" id="admin_code_input" placeholder="Mot de passe admin ou PIN">
      <small class="text-muted">Requis pour modifier un article existant ou les prix/frais.</small>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
      <button class="btn btn-dark" id="btnUnlock">Déverrouiller</button>
    </div>
  </div></div>
</div>

<script>
function fval(n){return parseFloat((n||'0').toString().replace(',','.'))||0;}
function recalc(){
  const pa=fval(document.querySelector('[name=prix_achat]').value);
  const tr=fval(document.querySelector('[name=frais_transport]').value);
  const dou=fval(document.querySelector('[name=douane_pct]').value);
  const aut=fval(document.querySelector('[name=autres_frais]').value);
  const marge=fval(document.querySelector('[name=marge_pct]').value);
  const tva=fval(document.querySelector('[name=tva_pct]').value);
  const cout = pa + tr + (pa * dou / 100) + aut;
  const pv_ht = cout * (1 + marge / 100);
  const pv_ttc = pv_ht * (1 + tva / 100);
  document.getElementById('cout_revient').value = cout.toFixed(2);
  const fieldPv=document.getElementById('prix_vente');
  if(!fieldPv.dataset.touched){ fieldPv.value = pv_ttc.toFixed(2); }
}
document.querySelectorAll('.calc').forEach(i=>i.addEventListener('input',recalc));
document.getElementById('prix_vente').addEventListener('input', e=>e.target.dataset.touched=true);
recalc();

function generateEAN13(){
  let base = Math.floor(100000000000 + Math.random() * 900000000000).toString();
  let sum = 0;
  for(let i=0;i<12;i++){ let d=parseInt(base.charAt(i)); sum+=i%2===0?d:d*3; }
  let check=(10-(sum%10))%10;
  document.getElementById('ean').value=base+check;
}
document.getElementById('generateEAN').addEventListener('click', generateEAN13);

<?php if(!is_admin()): ?>
const sens=document.querySelectorAll('.sensible');
sens.forEach(i=>i.setAttribute('disabled','disabled'));
document.getElementById('btnUnlock').addEventListener('click', ()=>{
  const code=document.getElementById('admin_code_input').value.trim();
  if(!code){alert('Entre le code admin.');return;}
document.getElementById('admin_code').value=code;
sens.forEach(i=>i.removeAttribute('disabled'));
const modal=bootstrap.Modal.getInstance(document.getElementById('adminModal'));
modal.hide();
});

<?php endif; ?> </script> ```
