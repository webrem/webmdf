<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
require_once __DIR__ . '/../sync_time.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /../login.php");
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>💰 Calcul Prix — R.E.Mobiles</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<!-- ✅ CSS CENTRALISÉ -->
<link rel="stylesheet" href="../assets/style.css">


<!-- ✅ Mise en page plus pro (scopée à cette page, sans toucher au code principal) -->
<style>
  .page-calcul .page-head{
    max-width: 1100px;
    margin: 0 auto 16px auto;
    padding: 0 12px;
  }
  .page-calcul .page-title{
    display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
    margin-bottom: 10px;
  }
  .page-calcul .page-title h2{ margin:0; }
  .page-calcul .page-subtitle{ margin:0; opacity:.85; }
  .page-calcul .quick-actions{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }

  .page-calcul .content-wrap{
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 12px;
  }
  .page-calcul .glass .block-title{
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    margin-bottom: 14px;
  }
  .page-calcul .glass .block-title h5{ margin:0; }
  .page-calcul .form-section{
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,.08);
  }
  .page-calcul .form-label{
    font-weight: 600;
    font-size: .92rem;
    margin-bottom: .35rem;
  }
  .page-calcul .hint{
    font-size: .85rem;
    opacity: .8;
  }
  .page-calcul .chip{
    display:inline-flex; align-items:center; gap:6px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(0,0,0,.12);
    font-size: .86rem;
    white-space: nowrap;
  }
  .page-calcul .sticky-preview{
    position: sticky;
    top: 14px;
  }

  /* cadre aperçu (sans casser ton CSS centralisé) */
  .page-calcul #ticketFrame{
    width: 100%;
    height: 62vh;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 12px;
    background: rgba(0,0,0,.18);
  }
  @media (max-width: 991.98px){
    .page-calcul #ticketFrame{ height: 52vh; }
    .page-calcul .sticky-preview{ position: static; }
  }

  .page-calcul .preview-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:flex-end;
    margin-top: 12px;
  }
  .page-calcul .meta-row{
    display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;
    margin-bottom: 12px;
  }
</style>
</head>

<body class="page-calcul">

<div class="grid-pattern"></div>
<?php include '../header.php'; ?>

<div class="header-bar">
  <span>💰 Calculateur — R.E.Mobiles</span>
  <div class="d-flex gap-2 flex-wrap">
    <a href="/../pages/dashboard.php" class="btn btn-outline-light btn-sm">⬅ Tableau</a>
    <a href="/../pages/devices_list.php" class="btn btn-light btn-sm">📱 Réparations</a>
  </div>
</div>

<div class="page-head fade-in">
  <div class="page-title">
    <div>
      <h2 class="fw-bold text-danger text-uppercase mb-1">Calcul de Prix & Génération PDF</h2>
      <p class="page-subtitle text-muted">
        Remplissez le formulaire, puis <strong>Enregistrer</strong> ou <strong>Prévisualiser</strong> pour générer le PDF.
      </p>
    </div>

    <div class="quick-actions">
      <a href="/../pages/admin.php" class="btn btn-outline-accent btn-sm">
        <i class="bi bi-journal-text"></i> Voir les tickets
      </a>
      <span class="chip">
        <i class="bi bi-shield-check"></i>
        Session active
      </span>
    </div>
  </div>
</div>

<main class="content-wrap pb-4 pb-lg-5 fade-in">
  <div class="row g-4 align-items-start">
    <!-- FORMULAIRE -->
    <div class="col-lg-5">
      <div class="glass p-4 h-100">
        <div class="block-title">
          <h5 class="text-danger fw-bold"><i class="bi bi-pencil-square"></i> Formulaire</h5>
          <span class="hint text-muted">Champs requis: <span class="text-danger">*</span></span>
        </div>

        <form id="prixForm">
          <input type="hidden" name="action" id="actionInput" value="save">

          <!-- Section: Client -->
          <div class="form-section pt-0 mt-0 border-0">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-semibold">Client</span>
              <span class="hint text-muted"><i class="bi bi-person-lines-fill me-1"></i> Infos client</span>
            </div>

            <label class="form-label">Nom & Prénom du client <span class="text-danger">*</span></label>
            <div class="position-relative">
              <input
                type="text"
                id="client_nom"
                name="clientNom"
                class="form-control"
                placeholder="Tapez le nom du client..."
                autocomplete="off"
                required
                aria-describedby="clientHelp"
              >
              <div
                id="suggestions"
                class="list-group position-absolute w-100"
                style="z-index:1000;"
                role="listbox"
                aria-label="Suggestions clients"
              ></div>
            </div>
            <small id="clientHelp" class="hint text-muted d-block mt-1">
              Astuce : tapez 2 caractères minimum pour l’autocomplétion.
            </small>

            <div class="row g-3 mt-1">
              <div class="col-12">
                <label class="form-label mb-1">Téléphone du client <span class="text-danger">*</span></label>
                <input type="text" name="clientTel" class="form-control" placeholder="Ex : 0694 12 34 56" inputmode="tel" required>
              </div>
              <div class="col-12">
                <label class="form-label mb-1">Email du client <span class="text-muted">(optionnel)</span></label>
                <input type="email" name="clientEmail" class="form-control" placeholder="exemple@mail.com" autocomplete="email">
              </div>
            </div>
          </div>

          <!-- Section: Document -->
          <div class="form-section">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-semibold">Document</span>
              <span class="hint text-muted"><i class="bi bi-file-earmark-text me-1"></i> Type & quantité</span>
            </div>

            <div class="row g-3">
              <div class="col-6">
                <label class="form-label mb-1">Type de document</label>
                <select name="docType" class="form-select">
                  <option value="DEVIS">DEVIS</option>
                  <option value="PROFORMA">PROFORMA</option>
                  <option value="FACTURE">FACTURE</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label mb-1">Quantité <span class="text-danger">*</span></label>
                <input type="number" name="quantite" class="form-control" value="1" min="1" required>
              </div>
            </div>
          </div>

          <!-- Section: Articles -->
          <div class="form-section">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-semibold">Articles</span>
              <button type="button" id="addArticle" class="btn btn-outline-accent btn-sm">
                <i class="bi bi-plus-circle"></i> Ajouter
              </button>
            </div>

            <div id="articlesContainer">
              <div class="article-item mb-2 p-2 rounded border border-danger-subtle" style="background:rgba(255,255,255,0.05)">
                <div class="row g-2">
                  <div class="col-5">
                    <label class="form-label mb-1">Pièce <span class="text-danger">*</span></label>
                    <input type="text" name="piece[]" class="form-control" placeholder="Nom de la pièce" required>
                  </div>
                  <div class="col-4">
                    <label class="form-label mb-1">Référence</label>
                    <input type="text" name="reference[]" class="form-control" placeholder="Référence">
                  </div>
                  <div class="col-3">
                    <label class="form-label mb-1">Prix (€) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="prixAchat[]" class="form-control" placeholder="Prix (€)" required>
                  </div>
                </div>
              </div>
            </div>

            <div class="hint text-muted mt-1">
              Ajoutez autant de lignes que nécessaire (pièce + prix).
            </div>
          </div>

          <!-- Section: Main d’œuvre / fournisseur -->
          <div class="form-section">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-semibold">Coûts</span>
              <span class="hint text-muted"><i class="bi bi-cash-coin me-1"></i> Main d’œuvre & fournisseur</span>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label mb-1">Main d'œuvre (€) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="mainOeuvre" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label mb-1">Fournisseur</label>
                <input type="text" name="fournisseur" class="form-control" placeholder="Ex : PhoneParts EU">
              </div>
            </div>

            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" id="saveClient" name="saveClient" value="1">
              <label class="form-check-label" for="saveClient">✅ Enregistrer ce client dans la base</label>
            </div>
          </div>

          <!-- Actions -->
          <div class="form-section">
            <div class="d-grid gap-2">
              <div class="btn-group" role="group">
                <button type="submit" id="btnSave" class="btn btn-accent btn-lg">
                  <i class="bi bi-save2 me-1"></i> Enregistrer
                </button>
                <button type="submit" id="btnPreview" class="btn btn-outline-accent btn-lg">
                  <i class="bi bi-eye me-1"></i> Prévisualiser
                </button>
              </div>
              <div class="hint text-muted">
                “Enregistrer” active le bouton Télécharger. “Prévisualiser” affiche seulement l’aperçu.
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- APERÇU PDF -->
    <div class="col-lg-7">
      <div class="sticky-preview">
        <div class="glass p-4 h-100">
          <div class="meta-row">
            <div class="block-title mb-0">
              <h5 class="text-danger fw-bold"><i class="bi bi-file-earmark-text"></i> Aperçu du PDF</h5>
            </div>
            <span class="chip">
              <i class="bi bi-eye"></i>
              Aperçu en temps réel
            </span>
          </div>

          <iframe id="ticketFrame" title="Aperçu PDF"></iframe>

          <div class="preview-actions">
            <button id="downloadBtn" class="btn btn-light"><i class="bi bi-download me-1"></i> Télécharger</button>
            <button id="printBtn" class="btn btn-outline-accent"><i class="bi bi-printer me-1"></i> Imprimer</button>
            <button id="convertBtn" class="btn btn-danger"><i class="bi bi-file-earmark-text me-1"></i> Transformer en DEVIS A4</button>
          </div>

          <div id="emptyHint" class="mt-3">
            <i class="bi bi-file-earmark-text fs-3 d-block mb-2"></i>
            Remplissez le formulaire à gauche puis cliquez sur <strong>Enregistrer</strong> ou <strong>Prévisualiser</strong>.
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- OVERLAY -->
<div class="loading-overlay" id="loadingOverlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);backdrop-filter:blur(3px);align-items:center;justify-content:center;">
  <div class="loading-box bg-dark text-danger p-3 rounded d-flex align-items-center gap-2 border border-danger-subtle shadow-lg">
    <div class="spinner-border text-danger" role="status"></div>
    <div>Génération du PDF…</div>
  </div>
</div>

<!-- Alert -->
<div id="alertBox" class="alert-animated" role="status" aria-live="polite">✅ Action effectuée</div>

<script>
function showAlert(msg){
  const box=document.getElementById('alertBox');
  box.textContent=msg;
  box.classList.add('show');
  setTimeout(()=>box.classList.remove('show'),3000);
}

const form=document.getElementById("prixForm");
const actionInput=document.getElementById("actionInput");
const btnSave=document.getElementById("btnSave");
const btnPreview=document.getElementById("btnPreview");
const iframe=document.getElementById("ticketFrame");
const dlBtn=document.getElementById("downloadBtn");
const printBtn=document.getElementById("printBtn");
const previewActions=document.querySelector(".preview-actions");
const emptyHint=document.getElementById("emptyHint");
const overlay=document.getElementById("loadingOverlay");
const convertBtn=document.getElementById("convertBtn");

btnSave.addEventListener("click",()=>actionInput.value="save");
btnPreview.addEventListener("click",()=>actionInput.value="preview");

/* ✅ UPDATE (UX) : état initial propre */
(() => {
  iframe.style.display = "none";
  previewActions.style.display = "none";
  dlBtn.style.display = "none";
  printBtn.disabled = true;
  convertBtn.disabled = true;
})();

function setLoading(isLoading){
  overlay.style.display=isLoading?"flex":"none";
  btnSave.disabled=isLoading;
  btnPreview.disabled=isLoading;
}

/* ✅ UPDATE (fix bug) :
   L’ajout client était exécuté au chargement de la page (donc jamais au bon moment).
   On le transforme en fonction appelée après la génération du PDF. */
async function maybeSaveClient(){
  const cb = document.getElementById("saveClient");
  if (!cb || !cb.checked) return;

  const nom = document.getElementById("client_nom").value.trim();
  const telEl = form.querySelector("input[name='clientTel']");
  const mailEl = form.querySelector("input[name='clientEmail']");
  const tel = telEl ? telEl.value.trim() : "";
  const mail = mailEl ? mailEl.value.trim() : "";

  if (!nom || !tel) return;

  const data = new FormData();
  data.append("nom", nom);
  data.append("telephone", tel);
  data.append("email", mail);

  try {
    const r = await fetch("/../client_add_index.php", { method: "POST", body: data });
    const res = await r.json();

    if (res.status === "ok") showAlert("👤 Client ajouté à la base clients");
    else if (res.status === "exists") showAlert("⚠️ Client déjà enregistré");
  } catch (err) {
    console.error("Erreur ajout client :", err);
  }
}

/* ✅ UPDATE (mineur) : évite fuite mémoire Blob URL */
let lastBlobUrl = null;

/* === SOUMISSION PRINCIPALE === */
form.addEventListener("submit",function(e){
  e.preventDefault();
  const formData=new FormData(this);
  const action=formData.get("action")||"save";
  setLoading(true);

  fetch("/../generate_pdf.php?action="+encodeURIComponent(action),{
    method:"POST",body:formData
  })
  .then(r=>{if(!r.ok)throw new Error("Erreur PDF");return r.blob();})
  .then(async blob=>{
    if (lastBlobUrl) URL.revokeObjectURL(lastBlobUrl);
    const url=URL.createObjectURL(blob);
    lastBlobUrl = url;

    iframe.src=url;
    iframe.style.display="block";
    emptyHint.style.display="none";
    previewActions.style.display="flex";

    // boutons disponibles dès que le PDF est prêt
    printBtn.disabled = false;
    convertBtn.disabled = false;

    await fetch("/../store_price.php",{method:"POST",body:formData});

    // ✅ FIX : ajout client au bon moment
    await maybeSaveClient();

    if(action==="save"){
      dlBtn.style.display="inline-block";
      dlBtn.disabled = false;
      dlBtn.onclick=()=>{
        const a=document.createElement("a");
        a.href=url;
        a.download="document.pdf";
        a.click();
        showAlert("💾 Document enregistré avec succès");
      };
    } else {
      dlBtn.style.display="none";
    }

    printBtn.onclick=()=>{
      if(iframe && iframe.contentWindow) iframe.contentWindow.print();
    };

    showAlert(action==="save" ? "✅ PDF généré et enregistré" : "✅ PDF prévisualisé");
  })
  .catch(err=>alert("Erreur PDF : "+err.message))
  .finally(()=>setLoading(false));
});

/* === Transformer en DEVIS A4 === */
convertBtn.addEventListener("click",()=>{
  const formData=new FormData(form);
  const tempForm=document.createElement("form");
  tempForm.method="POST";
  tempForm.action="/../preview_pdf.php";
  tempForm.target="_blank";
  for(const [k,v] of formData.entries()){
    const input=document.createElement("input");
    input.type="hidden";
    input.name=k;
    input.value=v;
    tempForm.appendChild(input);
  }
  document.body.appendChild(tempForm);
  tempForm.submit();
  document.body.removeChild(tempForm);
});

/* === AJOUT DYNAMIQUE D’ARTICLES === */
const addArticleBtn=document.getElementById("addArticle");
addArticleBtn.addEventListener("click",()=>{
  const container=document.getElementById("articlesContainer");
  const item=document.createElement("div");
  item.className="article-item mb-2 p-2 rounded border border-danger-subtle";
  item.style.background="rgba(255,255,255,0.05)";
  item.innerHTML=`
    <div class="row g-2">
      <div class="col-5"><input type="text" name="piece[]" class="form-control" placeholder="Nom de la pièce" required></div>
      <div class="col-4"><input type="text" name="reference[]" class="form-control" placeholder="Référence"></div>
      <div class="col-3"><input type="number" step="0.01" name="prixAchat[]" class="form-control" placeholder="Prix (€)" required></div>
    </div>`;
  container.appendChild(item);
});

/* === AUTOCOMPLÉTION CLIENT === */
document.getElementById("client_nom").addEventListener("input",function(){
  const q=this.value.trim();
  const box=document.getElementById("suggestions");
  box.innerHTML="";
  if(q.length<2){
    box.style.display="none";
    return;
  }
  fetch("/../clients_autocomplete.php?q="+encodeURIComponent(q))
  .then(res=>res.json()).then(data=>{
    box.innerHTML="";
    if(!data.length){
      box.style.display="none";
      return;
    }
    data.forEach(c=>{
      const div=document.createElement("div");
      div.className="list-group-item list-group-item-action";
      div.textContent=c.nom+(c.telephone?" ("+c.telephone+")":"");
      div.onclick=()=>{
        document.getElementById("client_nom").value=c.nom;
        const tel=document.querySelector("input[name='clientTel']");
        if(tel && c.telephone) tel.value=c.telephone;
        box.innerHTML="";
        box.style.display="none";
      };
      box.appendChild(div);
    });
    box.style.display="block";
  }).catch(()=>box.style.display="none");
});

document.addEventListener("click",(e)=>{
  const box=document.getElementById("suggestions");
  const input=document.getElementById("client_nom");
  if(!box.contains(e.target) && e.target!==input){
    box.innerHTML="";
    box.style.display="none";
  }
});
</script>
</body>
</html>
