<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
$role = $_SESSION['role'] ?? 'user';
$username = $_SESSION['username'] ?? '';
require_once __DIR__ . '/sync_time.php';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>🧊 Liquid Glass Widgets — R.E.Mobiles</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: radial-gradient(circle at top left, #080808, #151515);
  color: #fff;
  min-height: 100vh;
  overflow-x: hidden;
}

/* === HEADER === */
header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  background: rgba(15,15,15,0.7);
  backdrop-filter: blur(15px);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  box-shadow: 0 0 20px rgba(13,202,240,0.15);
}
header h1 {
  font-size: 1.5rem;
  color: #0dcaf0;
  margin: 0;
  font-weight: 700;
  letter-spacing: 1px;
}
header a {
  color: #0dcaf0;
  text-decoration: none;
  font-weight: 600;
}
header a:hover { text-decoration: underline; }

/* === CONTAINER GLASS === */
.glass-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  padding: 3rem;
}

.glass-card {
  position: relative;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 1.5rem;
  backdrop-filter: blur(15px);
  box-shadow: 0 0 25px rgba(13,202,240,0.2);
  transition: all 0.4s ease;
  overflow: hidden;
  cursor: pointer;
}
.glass-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 0 40px rgba(13,202,240,0.35);
  border-color: rgba(13,202,240,0.4);
}

/* === Animation liquide === */
.glass-card::before {
  content: "";
  position: absolute;
  top: -100%;
  left: -100%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle at center, rgba(13,202,240,0.2), transparent 60%);
  animation: liquidMove 6s infinite linear;
  transform: rotate(45deg);
}
@keyframes liquidMove {
  0% { transform: translate(-50%, -50%) rotate(0deg); }
  100% { transform: translate(50%, 50%) rotate(360deg); }
}

/* === TITRE & CONTENU === */
.glass-card i {
  font-size: 2.5rem;
  color: #0dcaf0;
  margin-bottom: 0.8rem;
}
.glass-card h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.5rem;
}
.glass-card p {
  color: #aaa;
  font-size: 0.9rem;
  margin-bottom: 0;
}

/* === Effet pulse (indicateur actif) === */
.glass-card.active::after {
  content: "";
  position: absolute;
  bottom: 10px;
  right: 10px;
  width: 12px;
  height: 12px;
  background: #0dcaf0;
  border-radius: 50%;
  animation: pulse 1.5s infinite;
}
@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  100% { transform: scale(2); opacity: 0; }
}

/* === Responsive === */
@media (max-width: 768px) {
  .glass-container {
    padding: 1.5rem;
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<header>
  <h1>🧊 Liquid Glass Widgets</h1>
  <a href="dashboard.php"><i class="bi bi-arrow-left"></i> Retour</a>
</header>

<div class="glass-container">
  <div class="glass-card active">
    <i class="bi bi-bar-chart-line-fill"></i>
    <h3>Performance mensuelle</h3>
    <p>Suivi des ventes et réparations en temps réel.</p>
  </div>

  <div class="glass-card">
    <i class="bi bi-cpu-fill"></i>
    <h3>Analyse technique</h3>
    <p>Données système et modules de diagnostic.</p>
  </div>

  <div class="glass-card">
    <i class="bi bi-palette2"></i>
    <h3>Design futuriste</h3>
    <p>Effets de verre liquide et transparence dynamique.</p>
  </div>

  <div class="glass-card">
    <i class="bi bi-camera-reels-fill"></i>
    <h3>Animations & Widgets</h3>
    <p>Visualisation 3D et transitions glassmorphiques.</p>
  </div>

  <div class="glass-card">
    <i class="bi bi-tools"></i>
    <h3>Configuration</h3>
    <p>Contrôle des couleurs, vitesse, intensité et flou.</p>
  </div>
</div>

</body>
</html>
