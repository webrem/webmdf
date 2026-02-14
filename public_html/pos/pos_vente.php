<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();

require_once __DIR__ . '/../sync_time.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';



$conn = new mysqli("localhost", "u498346438_calculrem", "Calculrem1", "u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
if ($conn->connect_error) die("Erreur DB : " . $conn->connect_error);
$conn->set_charset("utf8mb4");


/* ==========================
   CONTRÔLE CAISSE OUVERTE (FIX FINAL)
   ========================== */
$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT id
    FROM caisse_jour
    WHERE date_caisse = ?
      AND heure_ouverture IS NOT NULL
      AND heure_fermeture IS NULL
    LIMIT 1
");
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("⛔ Caisse fermée. Ouvrez la caisse pour effectuer des ventes.");
}


/* === Initialisation du panier === */
if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];
$msg = '';
$msg_type = 'secondary'; // success | warning | danger | info | secondary
$ticket_ref = null;

/* ======================================================================
   AJOUT (FEATURE 1 & 2) — Helpers + flags UI
   - Feature 1: si article saisi manuellement absent en DB => ajout stock qty=0
   - Feature 2: si rupture stock (quantite=0) et vente tentée => demande identifiant+code admin
   ====================================================================== */
$show_admin_stock_modal = false;
$pending_ref = '';
$pending_designation = '';

function pos_make_auto_ref(string $designation): string {
    $base = trim($designation);
    if (function_exists('mb_strtolower')) $base = mb_strtolower($base, 'UTF-8');
    else $base = strtolower($base);
    return 'AUTO-' . strtoupper(substr(md5($base), 0, 8));
}

/* === Feature 2: Validation admin pour ajouter 1 pièce en stock (rupture) === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_stock_override'])) {
    $override_ref = trim($_POST['override_ref'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_code = (string)($_POST['admin_code'] ?? '');

    if ($override_ref === '' || $admin_username === '' || $admin_code === '') {
        $msg = "⚠️ Identifiant et code administrateur requis.";
        $msg_type = "warning";
        $show_admin_stock_modal = true;
        $pending_ref = $override_ref;
    } else {
        /* NOTE: si votre table utilisateurs a un autre nom, adaptez ici.
           Table attendue: users(username, password, role) */
        $stmtU = $conn->prepare("SELECT username, password, role FROM users WHERE username = ? LIMIT 1");
        if ($stmtU === false) {
            $msg = "❌ Vérification admin impossible (table utilisateurs non trouvée).";
            $msg_type = "danger";
            $show_admin_stock_modal = true;
            $pending_ref = $override_ref;
        } else {
            $stmtU->bind_param("s", $admin_username);
            $stmtU->execute();
            $resU = $stmtU->get_result();
            $u = $resU->fetch_assoc();

            $ok = false;
            if ($u && ($u['role'] ?? '') === 'admin') {
                $hash = (string)($u['password'] ?? '');
                // Support hash (password_hash) + fallback éventuel en clair
                if ((function_exists('password_verify') && password_verify($admin_code, $hash)) || hash_equals($hash, $admin_code)) {
                    $ok = true;
                }
            }

            if (!$ok) {
                $msg = "❌ Accès refusé : identifiant/code invalide ou utilisateur non administrateur.";
                $msg_type = "danger";
                $show_admin_stock_modal = true;
                $pending_ref = $override_ref;
            } else {
                // Ajoute 1 pièce au stock
                $up = $conn->prepare("UPDATE stock_articles SET quantite = quantite + 1 WHERE reference = ? LIMIT 1");
                $up->bind_param("s", $override_ref);
                $up->execute();

                // Recharge le produit et l'ajoute au panier (1 unité)
                $p = $conn->prepare("SELECT reference, designation, prix_vente FROM stock_articles WHERE reference = ? LIMIT 1");
                $p->bind_param("s", $override_ref);
                $p->execute();
                $rp = $p->get_result();
                if ($prod = $rp->fetch_assoc()) {
                    $_SESSION['panier'][] = [
                        'reference' => $prod['reference'],
                        'designation' => $prod['designation'],
                        'prix' => (float)$prod['prix_vente'],
                        'quantite' => 1
                    ];
                    $msg = "✅ Validation admin OK : 1 pièce ajoutée au stock et article ajouté au panier.";
                    $msg_type = "success";
                } else {
                    $msg = "✅ Validation admin OK : stock incrémenté, mais produit introuvable après mise à jour.";
                    $msg_type = "warning";
                }
            }
        }
    }
}

/* === Suppression AJAX (CORRIGÉE) === */
if (isset($_POST['ajax_del'])) {
    header('Content-Type: application/json; charset=utf-8');

    $index = (int)$_POST['ajax_del'];
    $success = false;

    if (isset($_SESSION['panier'][$index])) {
        unset($_SESSION['panier'][$index]);
        $_SESSION['panier'] = array_values($_SESSION['panier']); // re-index serveur
        $success = true;
    }

    // Recalcul serveur (source de vérité)
    $subtotal = 0.0;
    foreach ($_SESSION['panier'] as $it) {
        $subtotal += ((float)$it['prix']) * ((int)$it['quantite']);
    }

    echo json_encode([
        "success"  => $success,
        "count"    => count($_SESSION['panier']),
        "subtotal" => $subtotal
    ]);
    exit;
}

/* === Validation vente === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_vente'])) {
    if (empty($_SESSION['panier'])) {
        $msg = "⚠️ Aucun article dans le panier.";
        $msg_type = "warning";
    } else {
        $mode_paiement = $_POST['mode_paiement'];

        $paiement_especes = ($_POST['paiement_especes'] !== '') ? (float)$_POST['paiement_especes'] : null;
        $paiement_cb = ($_POST['paiement_cb'] !== '') ? (float)$_POST['paiement_cb'] : null;

        $client_nom = trim($_POST['client_nom']);
        $client_tel = trim($_POST['client_tel']);
        $remise_pct = max(0, min(100, (float)($_POST['remise_pct'] ?? 0)));
        $remise_montant = max(0, (float)($_POST['remise_montant'] ?? 0));
        $vendeur = $_SESSION['username'] ?? 'Inconnu';

        /* 🔢 Sous-total global */
        $sous_total = 0;
        foreach ($_SESSION['panier'] as $it) {
            $sous_total += $it['prix'] * $it['quantite'];
        }

        /* 🎯 Application remise GLOBALE */
        if ($remise_montant > 0) {
            $total = max($sous_total - $remise_montant, 0);
        } else {
            $total = $sous_total * (1 - $remise_pct / 100);
        }

        $ticket_ref = 'POS-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

        $is_first_line = true;
        /* 💾 Enregistrement lignes (répartition proportionnelle) */
        foreach ($_SESSION['panier'] as $item) {
            $ligne_total = $item['prix'] * $item['quantite'];
            $ratio = ($sous_total > 0) ? ($ligne_total / $sous_total) : 0;
            $ligne_apres_remise = round($total * $ratio, 2);

            $stmt = $conn->prepare("
             INSERT INTO ventes (
                ref_vente, produit_ref, designation, quantite,
                prix_unitaire, prix_total,
                mode_paiement, paiement_especes, paiement_cb,
                client_nom, client_tel, vendeur,
                remise_pct, remise_montant,
                vente_principale
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ");

            $vente_principale = $is_first_line ? 1 : 0;

            $stmt->bind_param(
                "sssdddsddsssddi",
                $ticket_ref,
                $item['reference'],
                $item['designation'],
                $item['quantite'],
                $item['prix'],
                $ligne_apres_remise,
                $mode_paiement,
                $paiement_especes,
                $paiement_cb,
                $client_nom,
                $client_tel,
                $vendeur,
                $remise_pct,
                $remise_montant,
                $vente_principale
            );

            $stmt->execute();

            $update = $conn->prepare("UPDATE stock_articles SET quantite = GREATEST(quantite - ?, 0) WHERE reference = ?");
            $update->bind_param("is", $item['quantite'], $item['reference']);
            $update->execute();
        }
        $is_first_line = false;

        /* 💳 Paiement mixte */
        if ($mode_paiement === 'Mixte') {
            if (round($paiement_especes + $paiement_cb, 2) !== round($total, 2)) {
                $msg = "❌ Paiement mixte incorrect (Total : " . number_format($total,2,","," ") . " €)";
                $msg_type = "danger";
                return;
            }
        } else {
            $paiement_especes = null;
            $paiement_cb = null;
        }

        $_SESSION['panier'] = [];
        $msg = "✅ Vente enregistrée (Total après remise : " . number_format($total,2,","," ") . " €)";
        $msg_type = "success";
    }
}


/* === Ajout produit depuis le stock === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_article'])) {
    $ref = trim($_POST['produit_ref']);
    $qte = max(1, (int)$_POST['quantite']);
    if ($ref !== '') {
        $stmt = $conn->prepare("SELECT reference, ean, designation, prix_vente, quantite FROM stock_articles WHERE reference=? OR ean=? LIMIT 1");
        $stmt->bind_param("ss", $ref, $ref);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($prod = $res->fetch_assoc()) {
            if ((int)$prod['quantite'] >= $qte) {
                $_SESSION['panier'][] = [
                    'reference' => $prod['reference'],
                    'designation' => $prod['designation'],
                    'prix' => (float)$prod['prix_vente'],
                    'quantite' => $qte
                ];
                $msg = "🧾 Article ajouté : {$prod['designation']}";
                $msg_type = "info";
            } else {
                /* === Feature 2: si rupture (0 dispo) => demande identifiant + code admin === */
                if ((int)$prod['quantite'] === 0) {
                    $msg = "⚠️ Produit en rupture : validation administrateur requise pour ajouter 1 pièce au stock.";
                    $msg_type = "warning";
                    $show_admin_stock_modal = true;
                    $pending_ref = $prod['reference'];
                    $pending_designation = $prod['designation'];
                } else {
                    $msg = "⚠️ Stock insuffisant ({$prod['quantite']} dispo)";
                    $msg_type = "warning";
                }
            }
        } else {
            $msg = "❌ Produit introuvable, veuillez l’ajouter manuellement.";
            $msg_type = "danger";
            $show_modal = true;
        }
    }
}

/* === Ajout manuel === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_manuel'])) {
    $manual_designation_raw = trim((string)($_POST['manual_designation'] ?? ''));
    $manual_designation_html = htmlspecialchars($manual_designation_raw, ENT_QUOTES, 'UTF-8');
    $manual_prix = (float)($_POST['manual_prix'] ?? 0);
    $manual_qte = max(1, (int)($_POST['manual_quantite'] ?? 1));

    $_SESSION['panier'][] = [
        'reference' => 'MAN-' . strtoupper(substr(md5(uniqid()), 0, 6)), // inchangé pour le panier
        'designation' => $manual_designation_html,
        'prix' => $manual_prix,
        'quantite' => $manual_qte
    ];

    /* === Feature 1: si l'article n'existe pas en DB => ajout au stock avec quantité 0 === */
    if ($manual_designation_raw !== '') {
        $auto_ref = pos_make_auto_ref($manual_designation_raw);
        $chk = $conn->prepare("SELECT reference FROM stock_articles WHERE reference = ? OR designation = ? LIMIT 1");
        $chk->bind_param("ss", $auto_ref, $manual_designation_raw);
        $chk->execute();
        $rchk = $chk->get_result();

        if (!$rchk->fetch_assoc()) {
            // ean NULL, quantite 0
            $ins = $conn->prepare("INSERT INTO stock_articles (reference, ean, designation, prix_vente, quantite) VALUES (?, NULL, ?, ?, 0)");
            $ins->bind_param("ssd", $auto_ref, $manual_designation_raw, $manual_prix);
            $ins->execute();
        }
    }

    $msg = "🆕 Article ajouté manuellement : " . $manual_designation_html;
    $msg_type = "info";
}

/* === KPIs affichage (après actions) === */
$vendeur_ui = $_SESSION['username'] ?? 'Inconnu';
$cart_count = count($_SESSION['panier']);
$sous_total = 0.0;
foreach ($_SESSION['panier'] as $it) {
    $sous_total += ((float)$it['prix']) * ((int)$it['quantite']);
}
?>
<!doctype html>
<html lang="fr">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>🛒 Point de Vente — R.E.Mobiles</title>

<!-- Google Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- ✅ STYLE GLOBAL CENTRALISÉ -->
<link rel="stylesheet" href="../assets/style.css">

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
<?php include '../header.php'; ?>

<div class="pos-wrap">

  <!-- Topbar plateforme -->
  <div class="pos-topbar">
    <div class="pos-brand">
      <div class="pos-logo"><i class="bi bi-cart-check fs-5"></i></div>
      <div class="pos-title">
        <strong>Point de Vente</strong>
        <span>R.E.Mobiles • Interface caisse</span>
      </div>
    </div>

    <div class="pos-chips">
      <div class="pos-chip"><i class="bi bi-person-circle"></i> Vendeur: <?= htmlspecialchars($vendeur_ui) ?></div>
      <div class="pos-chip"><i class="bi bi-bag"></i> Articles: <span id="topCount"><?= (int)$cart_count ?></span></div>
      <div class="pos-chip pos-chip-accent"><i class="bi bi-cash-stack"></i> Sous-total: <span id="topSubtotal"><?= number_format($sous_total, 2, ",", " ") ?></span> €</div>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg_type) ?> text-center mb-3" role="alert">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Colonne GAUCHE : Scan + Panier -->
    <div class="col-lg-7">

      <!-- SCAN / RECHERCHE -->
      <form method="POST" class="pos-panel pos-scan">
        <div class="pos-panel-header">
          <div class="pos-panel-title"><i class="bi bi-upc-scan"></i> Scan & Ajout produit</div>
          <div class="text-muted small">EAN / Réf / Désignation</div>
        </div>

        <div class="pos-panel-body">
          <div class="row g-3 align-items-end">
            <div class="col-md-7 position-relative">
              <label>Produit</label>
              <input type="text" name="produit_ref" id="produit_ref" class="form-control" placeholder="Scannez ou tapez ici..." autocomplete="off" autofocus required>
              <div id="resultsBox" class="autocomplete-results" style="display:none;"></div>
            </div>

            <div class="col-md-2">
              <label>Qté</label>
              <input type="number" name="quantite" value="1" min="1" class="form-control">
            </div>

            <div class="col-md-3 d-grid gap-2">
              <button type="submit" name="ajouter_article" class="btn btn-gradient">➕ Ajouter</button>
              <button type="button" data-bs-toggle="modal" data-bs-target="#manualModal" class="btn btn-outline-light">🆕 Hors stock</button>
            </div>
          </div>
        </div>
      </form>

      <!-- PANIER -->
      <div class="pos-panel mt-4">
        <div class="pos-panel-header">
          <div class="pos-panel-title"><i class="bi bi-bag-check"></i> Panier</div>
          <div class="text-muted small">Suppression rapide • Totaux auto</div>
        </div>

        <div class="pos-panel-body">
          <?php if (empty($_SESSION['panier'])): ?>
            <p class="text-muted mb-0">Aucun article ajouté.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover align-middle text-center fs-6">
                <thead>
                  <tr>
                    <th>#</th><th>Réf</th><th>Désignation</th><th>Qté</th><th>PU (€)</th><th>Total (€)</th><th>❌</th>
                  </tr>
                </thead>

                <tbody id="cartBody">
                  <?php $i=0; $sous=0; foreach($_SESSION['panier'] as $it): $t=$it['prix']*$it['quantite']; $sous+=$t; ?>
                    <tr>
                      <td><?= ++$i ?></td>
                      <td><?= htmlspecialchars($it['reference']) ?></td>
                      <td><?= htmlspecialchars($it['designation']) ?></td>
                      <td><?= (int)$it['quantite'] ?></td>
                      <td><?= number_format((float)$it['prix'],2,',',' ') ?></td>
                      <td class="fw-bold text-success"><?= number_format($t,2,',',' ') ?></td>
                      <td>
                        <!-- ✅ IMPORTANT: type="button" -->
                        <button type="button" data-index="<?= $i-1 ?>" class="btn btn-danger btn-sm del-btn">✖</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>

                <tfoot>
                  <tr>
                    <th colspan="5" class="text-end">Sous-total</th>
                    <th colspan="2" class="text-warning" id="sousTotalCell"><?= number_format($sous,2,',',' ') ?></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Colonne DROITE : Paiement + Ticket -->
    <div class="col-lg-5">

      <!-- CHECKOUT -->
      <form method="POST" class="pos-panel">
        <div class="pos-panel-header">
          <div class="pos-panel-title"><i class="bi bi-credit-card"></i> Paiement & Client</div>
            <div class="pos-chip pos-chip-accent">
              <i class="bi bi-cash-stack"></i>
              Total: <span id="topTotalFinal"><?= number_format($sous_total, 2, ",", " ") ?></span> €
            </div>

        </div>

        <div class="pos-panel-body">
          <div class="row g-3">
            <div class="col-md-12">
              <label>Mode de paiement</label>
              <select name="mode_paiement" id="mode_paiement" class="form-select" required>
                <option value="Espèces">Espèces</option>
                <option value="Carte Bancaire">Carte Bancaire</option>
                <option value="Virement">Virement</option>
                <option value="Mixte">Mixte (Espèces + CB)</option>
                <option value="Autre">Autre</option>
              </select>
            </div>
            <div id="paiementMixteBox" style="display:none;">
              <div class="row">
                <div class="col-md-6">
                  <label>Montant espèces (€)</label>
                  <input type="number" step="0.01" min="0" name="paiement_especes" class="form-control">
                </div>
                <div class="col-md-6">
                  <label>Montant CB (€)</label>
                  <input type="number" step="0.01" min="0" name="paiement_cb" class="form-control">
                </div>
              </div>
            </div>


            <div class="col-md-6">
              <label>Remise (%)</label>
              <input type="number" name="remise_pct" class="form-control" min="0" max="100" value="0">
            </div>

            <div class="col-md-6">
              <label>Remise (€)</label>
              <input type="number" name="remise_montant" step="1.00" class="form-control" value="0.00">
            </div>

            <div class="col-md-12 position-relative">
              <label>Nom client</label>
              <input type="text" id="client_nom" name="client_nom" class="form-control" placeholder="Nom du client..." autocomplete="off">
              <div id="clientBox" class="autocomplete-results" style="display:none;"></div>
            </div>

            <div class="col-md-12">
              <label>Téléphone client</label>
              <input type="text" name="client_tel" class="form-control" placeholder="+594 694 12 34 56">
            </div>
          </div>

          <div class="d-grid mt-4">
            <button type="submit" name="valider_vente" class="btn btn-gradient py-3 fw-bold fs-5">
              💾 Valider la vente
            </button>
          </div>
        </div>
      </form>

      <!-- TICKET -->
      <div class="pos-panel mt-4">
        <div class="pos-panel-header">
          <div class="pos-panel-title"><i class="bi bi-printer"></i> Ticket</div>
          <div class="text-muted small">Aperçu après validation</div>
        </div>
        <div class="pos-panel-body">
          <?php if ($ticket_ref): ?>
            <iframe src="../ticket_pos.php?ref=<?= urlencode($ticket_ref) ?>" class="ticket"></iframe>
          <?php else: ?>
            <div class="text-muted">
              Le ticket s’affichera ici après l’enregistrement de la vente.
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </div>
</div>

<!-- 🪟 Modale ajout manuel -->
<div class="modal fade" id="manualModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title text-danger"><i class="bi bi-pencil-square"></i> Ajouter un article hors stock</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="ajouter_manuel" value="1">
          <div class="mb-3">
            <label>Désignation</label>
            <input type="text" name="manual_designation" class="form-control" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Prix (€)</label>
              <input type="number" step="0.01" name="manual_prix" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Quantité</label>
              <input type="number" name="manual_quantite" class="form-control" value="1" min="1">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-gradient">✅ Ajouter</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 🛡️ Modale validation administrateur (Feature 2) -->
<div class="modal fade" id="adminStockModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title text-warning"><i class="bi bi-shield-lock"></i> Validation administrateur requise</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="admin_stock_override" value="1">
          <input type="hidden" name="override_ref" value="<?= htmlspecialchars($pending_ref) ?>">

          <div class="alert alert-warning mb-3">
            Produit en rupture : <strong><?= htmlspecialchars($pending_designation ?: $pending_ref) ?></strong><br>
            Pour valider l’ajout automatique de <strong>1 pièce</strong> au stock, un administrateur doit s’identifier.
          </div>

          <div class="mb-3">
            <label>Identifiant administrateur</label>
            <input type="text" name="admin_username" class="form-control" autocomplete="username" required>
          </div>
          <div class="mb-3">
            <label>Code administrateur</label>
            <input type="password" name="admin_code" class="form-control" autocomplete="current-password" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-gradient">✅ Valider</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if(isset($show_modal)): ?>
<script>
  new bootstrap.Modal(document.getElementById('manualModal')).show();
  
  
  
  
</script>

<?php endif; ?>

<?php if(isset($show_admin_stock_modal) && $show_admin_stock_modal): ?>
<script>
  new bootstrap.Modal(document.getElementById('adminStockModal')).show();
</script>
<?php endif; ?>

<script>
/* =========================
   SUPPRESSION PANIER (FIX)
   - URL explicite (pas "")
   - Vérifie la réponse JSON
   - Anti double-clic
   - Sous-total + count depuis serveur
   ========================= */

let __delBusy = false;

$(document).on("click",".del-btn",function(e){
  e.preventDefault();
  e.stopPropagation();

  if(__delBusy) return;
  __delBusy = true;

  const btn = $(this);
  const row = btn.closest("tr");
  const index = parseInt(btn.attr("data-index"), 10);

  // Disable tous les boutons pendant la requête
  $(".del-btn").prop("disabled", true);

  const delUrl = window.location.pathname + window.location.search; // ✅ robuste même avec <base href>

  $.ajax({
    url: delUrl,
    method: "POST",
    dataType: "json",
    data: { ajax_del: index },
    headers: { "X-Requested-With": "XMLHttpRequest" }
  })
  .done(function(resp){
    // Si serveur ne confirme pas => on recharge pour rester cohérent
    if(!resp || resp.success !== true){
      location.reload();
      return;
    }

    row.fadeOut(150, function(){
      row.remove();

      if ((resp.count || 0) === 0) {
        location.reload();
        return;
      }

      // Re-index UI (# + data-index)
      $("#cartBody tr").each(function(i){
        $(this).find("td:first").text(i + 1);
        $(this).find(".del-btn").attr("data-index", i);
      });

      const subtotal = Number(resp.subtotal || 0);
      const sousTxt = subtotal.toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });

      $("#sousTotalCell").text(sousTxt);
      $("#topSubtotal").text(sousTxt);
      $("#checkoutSubtotal").text(sousTxt);
      $("#topCount").text(resp.count);

      __delBusy = false;
      $(".del-btn").prop("disabled", false);
    });
  })
  .fail(function(){
    __delBusy = false;
    $(".del-btn").prop("disabled", false);
    alert("Erreur: suppression impossible. Recharge la page.");
  });
});

// Autocomplétion produit
$("#produit_ref").on("input",function(){
  const q = $(this).val().trim();
  const b = $("#resultsBox");

  if(q.length < 2){
    b.hide();
    return;
  }

  $.getJSON("../ajax_search_stock.php", { q }, d => {
    b.empty();
    if(!d.length){
      b.hide();
      return;
    }
    d.forEach(it => {
      const dispo = it.quantite > 0 ? '✅(' + it.quantite + ')' : '❌ Rupture';
      $("<div>").addClass("autocomplete-item")
        .html("<strong>"+it.reference+"</strong> — "+it.designation+"<br><small>"+it.prix_vente+" € "+dispo+"</small>")
        .on("click", () => { $("#produit_ref").val(it.reference); b.hide(); })
        .appendTo(b);
    });
    b.show();
  });
});
$(document).on("click", e => {
  if(!$(e.target).closest("#produit_ref,#resultsBox").length) $("#resultsBox").hide();
});

// Autocomplétion client
$("#client_nom").on("input",function(){
  const q = $(this).val().trim();
  const b = $("#clientBox");

  if(q.length < 2){
    b.hide();
    return;
  }

  fetch("../clients_autocomplete.php?q=" + encodeURIComponent(q))
    .then(r => r.json())
    .then(d => {
      b.empty();
      if(!d.length){
        b.hide();
        return;
      }
      d.forEach(c => {
        $("<div>").addClass("autocomplete-item")
          .text(c.nom + " (" + (c.telephone || '') + ")")
          .on("click", () => {
            $("#client_nom").val(c.nom);
            $("input[name='client_tel']").val(c.telephone || '');
            b.hide();
          })
          .appendTo(b);
      });
      b.show();
    });
});
$(document).on("click", e => {
  if(!$(e.target).closest("#client_nom,#clientBox").length) $("#clientBox").hide();
});


// === Paiement mixte UI ===
$("#mode_paiement").on("change", function () {
  if ($(this).val() === "Mixte") {
    $("#paiementMixteBox").slideDown(150);
  } else {
    $("#paiementMixteBox").slideUp(150);
    $("input[name='paiement_especes']").val('');
    $("input[name='paiement_cb']").val('');
  }
});
</script>
<script>
function getSousTotal() {
    const txt =
        $("#sousTotalCell").text() ||   // panier (source fiable)
        $("#topSubtotal").text() ||     // fallback
        "0";

    return parseFloat(
        txt.replace(/\s/g,'').replace(',','.')
    ) || 0;
}

function recalcTotalRemise() {
    const sousTotal = getSousTotal();

    const pct = parseFloat($("input[name='remise_pct']").val()) || 0;
    const eur = parseFloat($("input[name='remise_montant']").val()) || 0;

    let total = sousTotal;

    if (eur > 0) {
        total = Math.max(sousTotal - eur, 0);
    } else if (pct > 0) {
        total = sousTotal * (1 - pct / 100);
    }

    const txt = total.toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    $("#topTotalFinal").text(txt);
}

// écoute remise
$("input[name='remise_pct'], input[name='remise_montant']").on("input", function(){
    if (this.name === "remise_pct") {
        $("input[name='remise_montant']").val('');
    } else {
        $("input[name='remise_pct']").val('');
    }
    recalcTotalRemise();
});

// recalcul aussi après suppression panier
$(document).on("ajaxComplete", function(){
    recalcTotalRemise();
});
</script>

</body>
</html>
