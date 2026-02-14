<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /../login.php");
    exit;
}

$role = $_SESSION['role'] ?? 'user';
$username = $_SESSION['username'] ?? '';
require_once __DIR__ . '/../sync_time.php';

/* valeurs par défaut */
$total_repairs = 0;
$repairs_in_progress = 0;
$repairs_completed_today = 0;
$ventes_mois = 0;

/* Connexion MySQL */
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u498346438_calculrem;charset=utf8mb4",
        "u498346438_calculrem",
        "Calculrem1",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    try { $pdo->exec("SET time_zone = '-03:00'"); } catch (Throwable $e) {}
} catch (PDOException $e) {
    die("Erreur MySQL");
}

/* Statistiques */
try {
    $total_repairs = $pdo->query("
        SELECT COUNT(*) FROM devices
        WHERE MONTH(created_at)=MONTH(CURDATE())
        AND YEAR(created_at)=YEAR(CURDATE())
    ")->fetchColumn() ?? 0;

    $repairs_in_progress = $pdo->query("
        SELECT COUNT(*) FROM devices
        WHERE status IN ('En Cours','Attente Pcs','Attente Pièces')
    ")->fetchColumn() ?? 0;

    $repairs_completed_today = $pdo->query("
        SELECT COUNT(*) FROM devices
        WHERE status='Terminé'
        AND DATE(updated_at)=CURDATE()
    ")->fetchColumn() ?? 0;

    $ventes_mois = $pdo->query("
        SELECT COUNT(*) FROM ventes
        WHERE MONTH(date_vente)=MONTH(CURDATE())
        AND YEAR(date_vente)=YEAR(CURDATE())
    ")->fetchColumn() ?? 0;

} catch (PDOException $e) {
    $total_repairs = $repairs_in_progress = $repairs_completed_today = $ventes_mois = 0;
}
/* ==========================
   CAISSE OUVERTE (PEU IMPORTE LA DATE)
   ========================== */
$caisseJour = null;

try {
    $stmt = $pdo->prepare("
        SELECT id, date_caisse, heure_ouverture, heure_fermeture
        FROM caisse_jour
        WHERE heure_fermeture IS NULL
        ORDER BY date_caisse ASC
        LIMIT 1
    ");
    $stmt->execute();
    $caisseJour = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $caisseJour = null;
}


$heureActuelle = (int)date('H');



?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - R.E.Mobiles</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="/../assets/css/dashboard.css">
</head>

<body>

<div class="container py-4 pb-5">

<?php include '../header.php'; ?>


<?php if ($caisseJour): ?>

    <?php if ($caisseJour['heure_ouverture'] && empty($caisseJour['heure_fermeture'])): ?>
        <div class="alert alert-success text-center fw-bold mb-4">
            🟢 Caisse ouverte aujourd’hui
        </div>

        <?php if ($heureActuelle >= 19): ?>
            <div class="alert alert-warning text-center fw-bold mb-3">
                ⚠️ Il est <?= date('H:i') ?> — pensez à fermer la caisse
            </div>
        <?php endif; ?>

        <div class="text-center mb-4">
            <a href="/../fermeture_caisse.php"
               class="btn btn-danger btn-lg px-4">
                🔒 Fermer la caisse
            </a>
        </div>

    <?php else: ?>
        <div class="alert alert-secondary text-center fw-bold mb-3">
    🔴 Caisse déjà fermée aujourd’hui
</div>

<div class="text-center mb-4">
    <a href="/../choix_ouverture_caisse.php"
       class="btn btn-success btn-lg px-4">
        🔓 Ouvrir la caisse
    </a>
</div>
   
<div class="text-center mb-4">
     <a target="_blank"
   href="/../print_confirmation_fermeture.php?id=<?= $caisseJour['id'] ?>"
   class="btn btn-success btn-lg px-4 mt-3">
   ✅ Imprimer confirmation de fermeture
</a>
</div>

    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-warning text-center fw-bold mb-3">
    ⚠️ Aucune caisse ouverte aujourd’hui
</div>

<div class="text-center mb-4">
    <a href="/../choix_ouverture_caisse.php"
       class="btn btn-success btn-lg px-4">
        🔓 Ouvrir la caisse
    </a>
</div>

<?php endif; ?>



<h2 class="text-center fw-bold my-4">📊 Tableau de bord</h2>

<div class="role-banner text-center mb-4">
  👤 Connecté en tant que <strong><?= strtoupper($role) ?></strong>
</div>

<!-- ======================
     STATS
     ====================== -->
<div class="stats-grid">

  <div class="stat-card">
    <i class="bi bi-calendar-check text-info"></i>
    <div class="stat-value"><?= number_format($total_repairs) ?></div>
    <div class="stat-title">Réparations enregistrées ce mois</div>
  </div>

  <div class="stat-card">
    <i class="bi bi-clock-history text-warning"></i>
    <div class="stat-value"><?= number_format($repairs_in_progress) ?></div>
    <div class="stat-title">Réparations en cours / attente pièces</div>
  </div>

  <div class="stat-card">
    <i class="bi bi-check-circle-fill text-success"></i>
    <div class="stat-value"><?= number_format($repairs_completed_today) ?></div>
    <div class="stat-title">Réparations terminées aujourd'hui</div>
  </div>

  <div class="stat-card">
    <i class="bi bi-cart-check-fill text-warning"></i>
    <div class="stat-value"><?= number_format($ventes_mois) ?></div>
    <div class="stat-title">Ventes réalisées ce mois</div>
  </div>

</div>

</div>

<!-- ======================
     MENU BAS DE PAGE
     ====================== -->
<nav class="dashboard-bottom-nav">
  <a href="/../index.php">
    <i class="bi bi-cash-stack"></i>
    <span>Calcul</span>
  </a>
    <a href="/../pages/chat.php">
      <i class="bi bi-chat-dots"></i>
      <span>Chat</span>
    </a>

  <a href="/../devices_list.php">
    <i class="bi bi-phone"></i>
    <span>Réparations</span>
  </a>

  <a href="/../device_register.php">
    <i class="bi bi-plus-circle"></i>
    <span>Ajouter</span>
  </a>

  <a href="/../clients.php">
    <i class="bi bi-people"></i>
    <span>Clients</span>
  </a>

  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <a href="/../stock/dashboard_stock.php">
      <i class="bi bi-box-seam"></i>
      <span>Dashboard</span>
    </a>
  <?php endif; ?>
    <a href="/../admin/create_labels.php">
    <i class="bi bi-upc-scan"></i>
    <span>Étiquettes</span>
  </a>
  <?php if ($_SESSION['role'] === 'admin'): ?>
<a href="/../admin/admin_notes.php">
  <i class="bi bi-journal-text"></i>
  <span>Notes</span>
</a>
<?php endif; ?>
<?php if ($_SESSION['role'] === 'admin'): ?>
<a href="caisse_historique_mensuel.php">
  <i class="bi bi-journal-text"></i>
  <span>Historique Caisse</span>
</a>
<?php endif; ?>
  <a href="/../logout.php" class="text-danger">
    <i class="bi bi-door-open"></i>
    <span>Quitter</span>
  </a>
</nav>




</body>
</html>
