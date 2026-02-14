<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
date_default_timezone_set("America/Cayenne");

/* --- Vérification de session --- */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

/* --- Connexion DB --- */
$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if($conn->connect_error) die("Erreur DB : ".$conn->connect_error);
$conn->set_charset("utf8mb4");

/* === Helper: bind auto === */
function stmt_bind_auto($stmt,...$vars){
    if(empty($vars)) return;
    $types='';
    foreach($vars as $v){ $types .= match(gettype($v)){ 'integer'=>'i','double'=>'d', default=>'s' }; }
    $stmt->bind_param($types,...$vars);
}

/* === Init panier & états === */
if(!isset($_SESSION['panier'])) $_SESSION['panier']=[];
$msg='';
$ticket_ref=null;
$facture_ref=null;

/* === Fonction d’enregistrement dans ventes & ventes_historique === */
function save_vente($conn,$ref,$type,$client_nom,$client_tel,$mode_paiement,$remise_pct,$remise_montant,$vendeur){
    $total=0;

    foreach($_SESSION['panier'] as $item){
        $ptotal = $item['prix'] * $item['quantite'];
        $ptotal_remise = $remise_montant > 0 ? max($ptotal - $remise_montant, 0) : $ptotal - ($ptotal * $remise_pct / 100);
        $total += $ptotal_remise;

        // 👉 Enregistrement dans la table ventes
        $stmt = $conn->prepare("
            INSERT INTO ventes (ref_vente, produit_ref, designation, quantite, prix_unitaire, prix_total, mode_paiement, client_nom, client_tel, vendeur, remise_pct, remise_montant)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        stmt_bind_auto($stmt, $ref, $item['reference'], $item['designation'], $item['quantite'], $item['prix'], $ptotal_remise, $mode_paiement, $client_nom, $client_tel, $vendeur, $remise_pct, $remise_montant);
        $stmt->execute();

        // 👉 Enregistrement dans la table ventes_historique
        $stmt2 = $conn->prepare("
            INSERT INTO ventes_historique(ref_vente, designation, quantite, prix_total, client_nom, client_tel, vendeur, mode_paiement, date_vente)
            VALUES (?,?,?,?,?,?,?,?,NOW())
        ");
        stmt_bind_auto($stmt2, $ref, $item['designation'], $item['quantite'], $ptotal_remise, $client_nom, $client_tel, $vendeur, $mode_paiement);
        $stmt2->execute();

        // 👉 Décrémentation du stock
        $ustk = $conn->prepare("UPDATE stock_articles SET quantite = GREATEST(quantite - ?, 0) WHERE reference = ?");
        stmt_bind_auto($ustk, $item['quantite'], $item['reference']);
        $ustk->execute();
    }

    // 👉 Ligne TOTAL dans ventes_historique
    $designation="TOTAL ".$type;
    $stmt3 = $conn->prepare("
        INSERT INTO ventes_historique(ref_vente, designation, quantite, prix_total, client_nom, client_tel, vendeur, mode_paiement, date_vente)
        VALUES (?,?,?,?,?,?,?,?,NOW())
    ");
    stmt_bind_auto($stmt3, $ref, $designation, 0, $total, $client_nom, $client_tel, $vendeur, $mode_paiement);
    $stmt3->execute();

    return $total;
}

/* === Validation : TICKET (thermique) === */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['valider_vente'])){
    if(empty($_SESSION['panier'])){ $msg="⚠️ Aucun article dans le panier."; }
    else {
        $mode_paiement=$_POST['mode_paiement'];
        $client_nom=trim($_POST['client_nom']);
        $client_tel=trim($_POST['client_tel']);
        $remise_pct=(int)($_POST['remise_pct'] ?? 0);
        $remise_montant=(float)($_POST['remise_montant'] ?? 0);

        $ticket_ref='POS-'.date('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(2)));
        $vendeur=$_SESSION['username'] ?? 'Inconnu';

        $total=save_vente($conn,$ticket_ref,"TICKET",$client_nom,$client_tel,$mode_paiement,$remise_pct,$remise_montant,$vendeur);

        $msg="✅ Ticket enregistré (Total : ".number_format($total,2,',',' ')." €)";
        $_SESSION['panier']=[];
    }
}

/* === Validation : FACTURE (A4) === */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['valider_facture'])){
    if(empty($_SESSION['panier'])){ $msg="⚠️ Aucun article dans le panier."; }
    else {
        $mode_paiement=$_POST['mode_paiement'];
        $client_nom=trim($_POST['client_nom']);
        $client_tel=trim($_POST['client_tel']);
        $remise_pct=(int)($_POST['remise_pct'] ?? 0);
        $remise_montant=(float)($_POST['remise_montant'] ?? 0);

        $facture_ref='FAC-'.date('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(2)));
        $vendeur=$_SESSION['username'] ?? 'Inconnu';

        $total=save_vente($conn,$facture_ref,"FACTURE",$client_nom,$client_tel,$mode_paiement,$remise_pct,$remise_montant,$vendeur);

        $msg="✅ Facture générée (Total : ".number_format($total,2,',',' ')." €)";
        $_SESSION['panier']=[];
    }
}

/* === Ajout article au panier === */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajouter_article'])){
    $ref=trim($_POST['produit_ref']);
    $qte=max(1,(int)$_POST['quantite']);
    if($ref!==''){
        $stmt=$conn->prepare("SELECT reference,ean,designation,prix_vente,quantite FROM stock_articles 
                              WHERE reference=? OR ean=? OR designation LIKE CONCAT('%',?,'%') LIMIT 1");
        stmt_bind_auto($stmt,$ref,$ref,$ref);
        $stmt->execute(); $res=$stmt->get_result();
        if($prod=$res->fetch_assoc()){
            if($prod['quantite'] >= $qte){
                $_SESSION['panier'][]=[
                    'reference'=>$prod['reference'],
                    'designation'=>$prod['designation'],
                    'prix'=>(float)$prod['prix_vente'],
                    'quantite'=>$qte
                ];
                $msg="🧾 Article ajouté : {$prod['designation']}";
            } else { $msg="⚠️ Stock insuffisant (dispo : {$prod['quantite']})"; }
        } else { $msg="❌ Produit introuvable : {$ref}"; }
    }
}

/* === Suppression article === */
if(isset($_GET['del'])){
    unset($_SESSION['panier'][(int)$_GET['del']]);
    $_SESSION['panier']=array_values($_SESSION['panier']);
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>🛒 POS - R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
body{background:#0d1117;color:#e6edf3;font-family:"Poppins",sans-serif;}
h2{color:#0dcaf0;font-weight:700;}
.card{background:#161b22;border:1px solid #30363d;border-radius:14px;box-shadow:0 4px 15px rgba(0,0,0,.4);}
label{color:#9bb4d0;font-weight:600;}
.table{color:#e6edf3;}
.btn-ticket{background:#0d6efd;color:#fff;font-weight:600;}
.btn-facture{background:#ffc107;color:#000;font-weight:600;}
.autocomplete-results{position:absolute;top:100%;left:0;right:0;background:#fff;color:#000;border:1px solid #0dcaf0;border-radius:6px;z-index:2000;display:none;max-height:320px;overflow:auto;font-size:14px;}
.autocomplete-item{padding:8px 12px;cursor:pointer;border-bottom:1px solid #eee;}
.autocomplete-item:hover{background:#0dcaf0;color:#fff;}
iframe.ticket,iframe.invoice{width:100%;border-radius:12px;background:#fff}
</style>
</head>
<body>
<div class="container py-4">

<?php include 'header.php'; ?>

<h2 class="text-center mb-4"><i class="bi bi-cart-check"></i> Point de Vente</h2>
<?php if($msg): ?><div class="alert alert-info text-center"><?=$msg?></div><?php endif; ?>

<!-- 🔎 Ajout produit -->
<form method="POST" class="card p-3 mb-4 position-relative">
  <label>Réf / EAN / Désignation</label>
  <input type="text" name="produit_ref" id="produit_ref" class="form-control" autocomplete="off" required>
  <div id="resultsStock" class="autocomplete-results"></div>
  <div class="row mt-2 align-items-end">
    <div class="col-md-2">
      <label>Qté</label>
      <input type="number" name="quantite" value="1" min="1" class="form-control">
    </div>
    <div class="col-md-4">
      <button type="submit" name="ajouter_article" class="btn btn-success w-100">➕ Ajouter</button>
    </div>
  </div>
</form>

<!-- 🧾 Panier -->
<div class="card p-3 mb-4">
  <h5 class="text-info fw-bold"><i class="bi bi-bag-check"></i> Panier</h5>
  <?php if(empty($_SESSION['panier'])): ?>
    <p class="text-muted mb-0">Aucun article.</p>
  <?php else: ?>
  <table class="table table-dark table-striped text-center align-middle">
    <thead><tr><th>#</th><th>Réf</th><th>Désignation</th><th>Qté</th><th>PU</th><th>Total</th><th>❌</th></tr></thead>
    <tbody>
      <?php $sous=0;$i=0; foreach($_SESSION['panier'] as $item): $t=$item['prix']*$item['quantite']; $sous+=$t; ?>
        <tr>
          <td><?=++$i?></td>
          <td><?=htmlspecialchars($item['reference'])?></td>
          <td class="text-start"><?=htmlspecialchars($item['designation'])?></td>
          <td><?=$item['quantite']?></td>
          <td><?=number_format($item['prix'],2,',',' ')?> €</td>
          <td class="fw-bold"><?=number_format($t,2,',',' ')?> €</td>
          <td><a href="?del=<?=$i-1?>" class="btn btn-danger btn-sm">✖</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><th colspan="5" class="text-end">SOUS-TOTAL</th><th colspan="2" class="text-warning"><?=number_format($sous,2,',',' ')?> €</th></tr>
    </tfoot>
  </table>
  <?php endif; ?>
</div>

<!-- 💳 Finalisation -->
<form method="POST" class="card p-3 mb-4">
  <h5 class="text-info fw-bold"><i class="bi bi-credit-card"></i> Finaliser</h5>
  <div class="row g-3">
    <div class="col-md-4">
      <label>Mode de paiement</label>
      <select name="mode_paiement" class="form-select" required>
        <option>Espèces</option><option>Carte Bancaire</option><option>Virement</option><option>Autre</option>
      </select>
    </div>
    <div class="col-md-4 position-relative">
      <label>Nom client</label>
      <input type="text" id="client_nom" name="client_nom" class="form-control" autocomplete="off">
      <div id="suggestions" class="list-group position-absolute w-100"></div>
    </div>
    <div class="col-md-4">
      <label>Téléphone</label>
      <input type="text" name="client_tel" class="form-control" placeholder="+594 ...">
    </div>

    <div class="col-md-3">
      <label>Remise (%)</label>
      <input type="number" name="remise_pct" id="remiseInput" class="form-control" value="0" min="0" max="100">
    </div>
    <div class="col-md-3">
      <label>Remise (€)</label>
      <input type="number" name="remise_montant" class="form-control" step="0.01" value="0">
    </div>
  </div>

  <div class="text-center mt-3 d-flex justify-content-center gap-3">
    <button type="submit" name="valider_vente" class="btn btn-ticket px-5">💾 Valider Ticket</button>
    <button type="submit" name="valider_facture" class="btn btn-facture px-5">🧾 Valider Facture</button>
  </div>
</form>

<!-- Prévisualisation PDF -->
<?php if(!empty($ticket_ref) && isset($_POST['valider_vente'])): ?>
  <div class="card p-3 mb-4">
    <h5 class="text-info fw-bold"><i class="bi bi-printer"></i> Ticket de caisse</h5>
    <iframe class="ticket" src="ticket_pos.php?ref=<?=urlencode($ticket_ref)?>" style="height:620px;border:2px solid #0dcaf0;"></iframe>
  </div>
<?php endif; ?>

<?php if(!empty($facture_ref) && isset($_POST['valider_facture'])): ?>
  <div class="card p-3 mb-4">
    <h5 class="text-warning fw-bold"><i class="bi bi-file-earmark-pdf"></i> Facture A4</h5>
    <iframe class="invoice" src="invoice_pos.php?ref=<?=urlencode($facture_ref)?>" style="height:920px;border:2px solid #ffc107;"></iframe>
  </div>
<?php endif; ?>

</div>

<!-- ===== Autocomplete JS ===== -->
<script>
$(function(){
  // === Articles ===
  const $input=$("#produit_ref");
  const $box=$("#resultsStock");
  let timer;
  $input.on("input", function(){
    clearTimeout(timer);
    const q=$(this).val().trim();
    if(q.length<2){ $box.hide(); return; }
    timer=setTimeout(()=>{
      $.getJSON("ajax_search_stock.php",{q:q}, function(data){
        $box.empty();
        if(!data||data.length===0){ $box.hide(); return; }
        data.forEach(it=>{
          const dispo=it.quantite>0?`✅ ${it.quantite} en stock`:'❌ Rupture';
          const $row=$(`<div class="autocomplete-item">
              <strong>${it.reference}</strong> — ${it.designation} | ${parseFloat(it.prix_vente).toFixed(2)} €<br>
              <small>${dispo}</small>
          </div>`);
          $row.on('click',()=>{ $input.val(it.reference); $box.hide(); });
          $box.append($row);
        });
        $box.show();
      });
    },180);
  });
  $(document).on('click',(e)=>{ if(!$(e.target).closest('#produit_ref,#resultsStock').length){ $box.hide(); } });
});

// === Clients ===
document.getElementById("client_nom").addEventListener("input", function(){
  const q=this.value.trim();
  const box=document.getElementById("suggestions");
  box.innerHTML="";
  if(q.length<2) return;
  fetch("clients_autocomplete.php?q="+encodeURIComponent(q))
    .then(res=>res.json())
    .then(data=>{
      box.innerHTML="";
      data.forEach(c=>{
        const div=document.createElement("div");
        div.className="list-group-item list-group-item-action bg-light text-dark";
        div.textContent=c.nom+" "+(c.telephone?"("+c.telephone+")":"");
        div.onclick=()=>{
          document.getElementById("client_nom").value=c.nom;
          const tel=document.querySelector("input[name='client_tel']");
          if(tel&&c.telephone) tel.value=c.telephone;
          if(typeof c.remise_pct!=="undefined"){ document.getElementById("remiseInput").value=parseInt(c.remise_pct,10)||0; }
          box.innerHTML="";
        };
        box.appendChild(div);
      });
    });
});
</script>

</body>
</html>
