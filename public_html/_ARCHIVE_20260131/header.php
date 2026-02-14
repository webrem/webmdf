<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/sync_time.php';

if (empty($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

$role     = $_SESSION['role'] ?? 'user';
$username = $_SESSION['username'] ?? '';

$initial = 'U';
if (!empty($username)) {
  if (function_exists('mb_substr')) {
    $initial = mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8');
  } else {
    $initial = strtoupper(substr($username, 0, 1));
  }
}

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

function is_active(string $needle, string $currentUri): string {
  return (strpos($currentUri, $needle) !== false) ? ' is-active' : '';
}
?>

<style>
/* ============================
   R.E.Mobiles Header — PRO UI
   ============================ */
:root{
  --rem-bg: #07070a;
  --rem-panel: rgba(12, 12, 15, .72);
  --rem-panel-strong: rgba(15, 15, 19, .92);

  --rem-text: rgba(255,255,255,.92);
  --rem-muted: rgba(255,255,255,.68);

  --rem-border: rgba(255,255,255,.08);
  --rem-border-accent: rgba(220, 53, 69, .35);

  --rem-red: #dc3545;
  --rem-blue: #0d6efd;
  --rem-yellow: #ffc107;
  --rem-green: #28a745;
  --rem-cyan: #0dcaf0;

  --rem-radius: 16px;
  --rem-radius-sm: 12px;

  --rem-shadow-soft: 0 10px 30px rgba(0,0,0,.45);
  --rem-shadow: 0 18px 70px rgba(0,0,0,.55);

  --rem-font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
}

body.nav-open{
  overflow: hidden;
  touch-action: none;
}

/* ===== Topbar ===== */
.rem-topbar{
  position: sticky;
  top: 0;
  z-index: 10000;
  height: 64px;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;

  color: var(--rem-text);
  background: var(--rem-panel);
  border-bottom: 1px solid var(--rem-border-accent);
  box-shadow: var(--rem-shadow-soft), 0 0 32px rgba(220, 53, 69, .14);

  -webkit-backdrop-filter: blur(16px);
  backdrop-filter: blur(16px);
  font-family: var(--rem-font);
}

.rem-topbar::after{
  content:"";
  position:absolute;
  left:0; right:0; bottom:-1px;
  height:1px;
  background: linear-gradient(90deg, transparent, rgba(220,53,69,.65), rgba(13,202,240,.45), transparent);
  opacity:.85;
  pointer-events:none;
}

/* Menu button */
.menu-toggle{
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(220,53,69,.35);
  border-radius: 14px;
  background: rgba(220,53,69,.10);
  color: var(--rem-red);
  cursor: pointer;
  transition: transform .15s ease, background .15s ease, border-color .15s ease;
}

.menu-toggle:hover{
  transform: translateY(-1px);
  background: rgba(220,53,69,.16);
  border-color: rgba(220,53,69,.55);
}

.menu-toggle:active{ transform: translateY(0); }

.menu-toggle:focus-visible{
  outline: 2px solid rgba(13,202,240,.9);
  outline-offset: 2px;
}

.menu-icon{
  width: 18px;
  height: 2px;
  border-radius: 3px;
  background: currentColor;
  position: relative;
  box-shadow: 0 6px 0 currentColor, 0 -6px 0 currentColor;
}

/* Brand */
.brand-zone{
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: inherit;
  padding: 6px 10px;
  border-radius: var(--rem-radius);
  transition: background .15s ease;
  min-width: 0;
}

.brand-zone:hover{ background: rgba(255,255,255,.03); }
.brand-zone:focus-visible{
  outline: 2px solid rgba(13,202,240,.9);
  outline-offset: 2px;
}

.brand-logo{
  height: 44px;
  width: 44px;
  object-fit: cover;
  border-radius: 14px;
  border: 1px solid rgba(220,53,69,.25);
  box-shadow: 0 0 0 1px rgba(0,0,0,.2) inset, 0 10px 25px rgba(0,0,0,.35);
}

.brand-text{ display:flex; flex-direction:column; line-height:1.05; min-width:0; }
.brand-title{
  margin: 0;
  font-size: 1.05rem;
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--rem-red);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.brand-subtitle{
  margin: 0;
  font-size: .78rem;
  color: var(--rem-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Right side */
.rem-right{
  display:flex;
  align-items:center;
  gap:10px;
}

.clock-zone{
  text-align: right;
  padding: 6px 10px;
  border-radius: 14px;
  border: 1px solid rgba(13,202,240,.22);
  background: rgba(13,202,240,.08);
  box-shadow: 0 0 0 1px rgba(0,0,0,.25) inset;
  min-width: 140px;
}

.clock-time{
  font-size: 1.18rem;
  font-weight: 900;
  color: rgba(13,202,240,.95);
  letter-spacing: .02em;
}
.clock-date{
  font-size: .78rem;
  color: rgba(255,255,255,.72);
}

.user-chip{
  display:flex;
  align-items:center;
  gap:10px;
  padding: 6px 10px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.10);
  background: rgba(255,255,255,.03);
  max-width: 240px;
}

.user-avatar{
  width: 34px;
  height: 34px;
  border-radius: 999px;
  display:grid;
  place-items:center;
  font-weight: 900;
  background: rgba(220,53,69,.18);
  border: 1px solid rgba(220,53,69,.35);
  color: rgba(255,255,255,.92);
}

.user-meta{ display:flex; flex-direction:column; line-height:1.05; min-width:0; }
.user-name{
  font-weight: 900;
  font-size: .9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.user-role{
  font-size: .75rem;
  color: rgba(255,255,255,.65);
}

/* ===== Sidebar ===== */
.nav-links{
  position: fixed;
  top: 64px;
  left: -320px;
  width: 292px;
  height: calc(100vh - 64px);
  background: var(--rem-panel-strong);
  border-right: 1px solid var(--rem-border-accent);
  box-shadow: 14px 0 55px rgba(0,0,0,.65);
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: left .28s ease;
  z-index: 9999;
  overflow-y: auto;

  -webkit-backdrop-filter: blur(16px);
  backdrop-filter: blur(16px);
}

.nav-links.show{ left: 0; }

.nav-overlay{
  position: fixed;
  top: 64px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,.55);
  opacity: 0;
  pointer-events: none;
  transition: opacity .25s ease;
  z-index: 9998;
  cursor: pointer;
}

.nav-links.show + .nav-overlay{
  opacity: 1;
  pointer-events: auto;
}

.nav-user{
  padding: 12px;
  border-radius: var(--rem-radius);
  border: 1px solid rgba(220,53,69,.20);
  background: linear-gradient(135deg, rgba(220,53,69,.12), rgba(13,202,240,.06));
  box-shadow: 0 0 0 1px rgba(0,0,0,.25) inset;
}

.nav-user-row{
  display:flex;
  align-items:center;
  gap: 10px;
}

.badge-user{
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font-size: .95rem;
  font-weight: 900;
}

.nav-user-sub{
  margin-top: 2px;
  color: rgba(255,255,255,.68);
  font-size: .78rem;
}

.nav-section{
  margin-top: 6px;
  padding-top: 6px;
  border-top: 1px solid rgba(255,255,255,.06);
}

.nav-section-label{
  margin: 10px 6px 6px;
  font-size: .72rem;
  color: rgba(255,255,255,.62);
  letter-spacing: .14em;
  text-transform: uppercase;
}

/* Nav links */
.nav-btn{
  --nav-accent: var(--rem-red);
  display:flex;
  align-items:center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.08);
  background: rgba(255,255,255,.03);
  color: rgba(255,255,255,.92);
  text-decoration: none;
  font-weight: 850;
  font-size: .92rem;
  position: relative;
  transition: transform .14s ease, background .14s ease, border-color .14s ease, box-shadow .14s ease;
}

.nav-btn::before{
  content:"";
  position:absolute;
  left: 10px;
  width: 3px;
  height: 58%;
  border-radius: 999px;
  background: var(--nav-accent);
  opacity: .70;
}

.nav-btn .nav-icon{
  width: 18px;
  height: 18px;
  flex: 0 0 18px;
  opacity: .95;
  margin-left: 10px; /* pour laisser respirer la barre d'accent */
}

.nav-btn:hover{
  transform: translateX(3px);
}

.nav-btn:focus-visible{
  outline: 2px solid rgba(13,202,240,.9);
  outline-offset: 2px;
}

/* Accent presets + gradients */
.btn-blue{
  --nav-accent: var(--rem-blue);
  border-color: rgba(13,110,253,.18);
  background: linear-gradient(90deg, rgba(13,110,253,.10), rgba(255,255,255,.03));
}
.btn-blue:hover{
  border-color: rgba(13,110,253,.32);
  background: linear-gradient(90deg, rgba(13,110,253,.14), rgba(255,255,255,.04));
}

.btn-yellow{
  --nav-accent: var(--rem-yellow);
  border-color: rgba(255,193,7,.22);
  background: linear-gradient(90deg, rgba(255,193,7,.10), rgba(255,255,255,.03));
}
.btn-yellow:hover{
  border-color: rgba(255,193,7,.35);
  background: linear-gradient(90deg, rgba(255,193,7,.14), rgba(255,255,255,.04));
}

.btn-green{
  --nav-accent: var(--rem-green);
  border-color: rgba(40,167,69,.20);
  background: linear-gradient(90deg, rgba(40,167,69,.10), rgba(255,255,255,.03));
}
.btn-green:hover{
  border-color: rgba(40,167,69,.34);
  background: linear-gradient(90deg, rgba(40,167,69,.14), rgba(255,255,255,.04));
}

.btn-red{
  --nav-accent: var(--rem-red);
  border-color: rgba(220,53,69,.22);
  background: linear-gradient(90deg, rgba(220,53,69,.10), rgba(255,255,255,.03));
}
.btn-red:hover{
  border-color: rgba(220,53,69,.36);
  background: linear-gradient(90deg, rgba(220,53,69,.14), rgba(255,255,255,.04));
}

/* Active page */
.nav-btn.is-active{
  box-shadow: 0 0 0 1px rgba(255,255,255,.05) inset, 0 12px 32px rgba(0,0,0,.25);
}
.nav-btn.btn-blue.is-active{
  border-color: rgba(13,110,253,.48);
  background: linear-gradient(90deg, rgba(13,110,253,.20), rgba(255,255,255,.04));
  box-shadow: 0 0 0 1px rgba(13,110,253,.14) inset, 0 12px 32px rgba(13,110,253,.10);
}
.nav-btn.btn-yellow.is-active{
  border-color: rgba(255,193,7,.55);
  background: linear-gradient(90deg, rgba(255,193,7,.20), rgba(255,255,255,.04));
  box-shadow: 0 0 0 1px rgba(255,193,7,.14) inset, 0 12px 32px rgba(255,193,7,.10);
}
.nav-btn.btn-green.is-active{
  border-color: rgba(40,167,69,.52);
  background: linear-gradient(90deg, rgba(40,167,69,.20), rgba(255,255,255,.04));
  box-shadow: 0 0 0 1px rgba(40,167,69,.14) inset, 0 12px 32px rgba(40,167,69,.10);
}
.nav-btn.btn-red.is-active{
  border-color: rgba(220,53,69,.55);
  background: linear-gradient(90deg, rgba(220,53,69,.20), rgba(255,255,255,.04));
  box-shadow: 0 0 0 1px rgba(220,53,69,.14) inset, 0 12px 32px rgba(220,53,69,.10);
}

.nav-divider{
  height: 1px;
  background: rgba(255,255,255,.08);
  margin: 10px 4px;
}

.nav-footer{
  margin-top: auto;
  padding-top: 10px;
}

/* Scrollbar */
.nav-links::-webkit-scrollbar{ width: 10px; }
.nav-links::-webkit-scrollbar-thumb{
  background: rgba(255,255,255,.10);
  border-radius: 999px;
  border: 2px solid rgba(0,0,0,.35);
}
.nav-links::-webkit-scrollbar-thumb:hover{ background: rgba(255,255,255,.16); }

/* Responsive */
@media (max-width: 560px){
  .clock-zone{ min-width: 0; padding: 6px 8px; }
  .clock-time{ font-size: 1.05rem; }
  .user-chip{ display:none; }
  .brand-subtitle{ display:none; }
  .nav-links{ width: 86vw; max-width: 320px; }
}

/* Reduce motion */
@media (prefers-reduced-motion: reduce){
  .nav-links, .nav-overlay, .nav-btn, .menu-toggle, .brand-zone{ transition: none !important; }
}
</style>

<!-- SVG ICONS (sans librairie externe) -->
<svg width="0" height="0" style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true" focusable="false">
  <symbol id="i-dashboard" viewBox="0 0 24 24"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></symbol>
  <symbol id="i-pos" viewBox="0 0 24 24"><path fill="currentColor" d="M7 4h10v2H7V4zm-2 4h14v12H5V8zm2 2v8h10v-8H7zm2 1h6v2H9v-2zm0 3h4v2H9v-2z"/></symbol>
  <symbol id="i-calc" viewBox="0 0 24 24"><path fill="currentColor" d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v4h10V4H7zm0 6v10h10V10H7zm2 2h2v2H9v-2zm4 0h2v2h-2v-2zM9 16h2v2H9v-2zm4 0h2v2h-2v-2z"/></symbol>
  <symbol id="i-file" viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6zm0 2.5L19.5 9H14V4.5zM7 13h10v2H7v-2zm0 4h10v2H7v-2z"/></symbol>
  <symbol id="i-orders" viewBox="0 0 24 24"><path fill="currentColor" d="M7 4h14v14H7V4zM5 6H3v16h16v-2H5V6zm4 2v2h10V8H9zm0 4v2h10v-2H9zm0 4v2h6v-2H9z"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path fill="currentColor" d="M11 5h2v14h-2V5zm-6 6h14v2H5v-2z"/></symbol>
  <symbol id="i-tools" viewBox="0 0 24 24"><path fill="currentColor" d="M22 19l-6.3-6.3a5.5 5.5 0 0 1-7.6-7.6l3.1 3.1 2.1-2.1-3.1-3.1a5.5 5.5 0 0 1 7.6 7.6L23 17v2h-1zM2 21l6-6 2 2-6 6H2v-2z"/></symbol>
  <symbol id="i-users" viewBox="0 0 24 24"><path fill="currentColor" d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm-10 9c0-2.7 5.3-4 6-4s6 1.3 6 4v2H6v-2z"/></symbol>
  <symbol id="i-stock" viewBox="0 0 24 24"><path fill="currentColor" d="M3 19h18v2H3v-2zM7 10h3v7H7v-7zm5-4h3v11h-3V6zm5 7h3v4h-3v-4z"/></symbol>
  <symbol id="i-video" viewBox="0 0 24 24"><path fill="currentColor" d="M3 6h13a2 2 0 0 1 2 2v2l3-2v10l-3-2v2a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/></symbol>
  <symbol id="i-history" viewBox="0 0 24 24"><path fill="currentColor" d="M13 3a9 9 0 1 1-8.95 10H1l3.5-3.5L8 13H6.05A7 7 0 1 0 13 5v2l-4-3 4-3v2zM12 8h2v6l4 2-1 1.7-5-2.7V8z"/></symbol>
  <symbol id="i-logout" viewBox="0 0 24 24"><path fill="currentColor" d="M10 17v2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6v2H4v10h6zm11-5-4-4v3H9v2h8v3l4-4z"/></symbol>
  <symbol id="i-clients" viewBox="0 0 24 24"><path fill="currentColor" d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm-12 9c0-3 6-4.5 8-4.5S20 17 20 20v2H4v-2z"/></symbol>
</svg>

<header class="rem-topbar">
  <button class="menu-toggle" id="menu-toggle" aria-controls="nav-links" aria-expanded="false" aria-label="Ouvrir / fermer le menu">
    <span class="menu-icon" aria-hidden="true"></span>
  </button>

  <a class="brand-zone" href="../dashboard.php" aria-label="R.E.Mobiles — Dashboard">
    <img src="logo.png" class="brand-logo" alt="R.E.Mobiles">
    <div class="brand-text">
      <div class="brand-title">R.E.Mobiles</div>
      <div class="brand-subtitle">Panel de gestion</div>
    </div>
  </a>

  <div class="rem-right">
    <div class="clock-zone" aria-label="Horloge">
      <div id="clock-time" class="clock-time">00:00:00</div>
      <div id="clock-date" class="clock-date">--</div>
    </div>

    <div class="user-chip" title="<?= htmlspecialchars($username) ?> — <?= htmlspecialchars($role) ?>">
      <div class="user-avatar" aria-hidden="true"><?= htmlspecialchars($initial ?: 'U') ?></div>
      <div class="user-meta">
        <div class="user-name"><?= htmlspecialchars($username) ?></div>
        <div class="user-role"><?= htmlspecialchars($role) ?></div>
      </div>
    </div>
  </div>
</header>

<!-- MENU LATERAL -->
<aside class="nav-links" id="nav-links" aria-label="Menu principal">
  <div class="nav-user">
    <div class="nav-user-row">
      <div class="user-avatar" aria-hidden="true"><?= htmlspecialchars($initial ?: 'U') ?></div>
      <div style="min-width:0">
        <div class="badge-user"><?= htmlspecialchars($username) ?></div>
        <div class="nav-user-sub"><?= htmlspecialchars($role) ?></div>
      </div>
    </div>
  </div>

  <div class="nav-section">
    <div class="nav-section-label">Navigation</div>

    <a href="../pages/dashboard.php" class="nav-btn btn-blue<?= is_active('dashboard.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-dashboard"></use></svg>
      Dashboard
    </a>

    <a href="../pos/pos_vente.php" class="nav-btn btn-yellow<?= is_active('pos_vente.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-pos"></use></svg>
      Point de vente
    </a>

    <a href="../pages/index.php" class="nav-btn btn-blue<?= is_active('index.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calc"></use></svg>
      Calcul
    </a>

    <a href="../admin.php" class="nav-btn btn-blue<?= is_active('admin.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file"></use></svg>
      Devis / Factures
    </a>

    <a href="../commandes.php" class="nav-btn btn-yellow<?= is_active('commandes.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-orders"></use></svg>
      Commandes
    </a>

    <a href="../device_register.php" class="nav-btn btn-green<?= is_active('device_register.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"></use></svg>
      Ajouter appareil
    </a>

    <a href="../devices_list.php" class="nav-btn btn-green<?= is_active('devices_list.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-tools"></use></svg>
      Réparations
    </a>

    <a href="../clients.php" class="nav-btn btn-blue<?= is_active('clients.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-clients"></use></svg>
      Clients
    </a>
  </div>

  <?php if ($role === 'admin'): ?>
    <div class="nav-section">
      <div class="nav-section-label">Administration</div>

      <a href="/stock/stock.php" class="nav-btn btn-blue<?= is_active('/stock/stock.php', $currentUri) ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-stock"></use></svg>
        Stock
      </a>

      <a href="/stock/stock_add.php" class="nav-btn btn-green<?= is_active('/stock/stock_add.php', $currentUri) ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"></use></svg>
        Ajouter
      </a>

      <a href="/stock/stock_import.php" class="nav-btn btn-yellow<?= is_active('/stock/stock_import.php', $currentUri) ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file"></use></svg>
        Import
      </a>

      <a href="../upload_video.php" class="nav-btn btn-blue<?= is_active('upload_video.php', $currentUri) ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-video"></use></svg>
        Vidéos
      </a>

      <a href="../user_manage.php" class="nav-btn btn-red<?= is_active('user_manage.php', $currentUri) ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"></use></svg>
        Utilisateurs
      </a>
    </div>
  <?php endif; ?>

  <div class="nav-footer">
    <div class="nav-divider"></div>

    <a href="../ventes_historique.php" class="nav-btn btn-blue<?= is_active('ventes_historique.php', $currentUri) ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-history"></use></svg>
      Historique
    </a>

    <a href="../logout.php" class="nav-btn btn-red">
      <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-logout"></use></svg>
      Déconnexion
    </a>
  </div>
</aside>

<div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>

<script>
/*
  Header script “safe” :
  - Toggle menu (avec fallback si ton /assets/js/app.js le fait déjà)
  - Overlay click + Escape pour fermer
  - Lock scroll quand menu ouvert
  - Horloge basée sur l’heure serveur PHP (approx)
*/
(function () {
  var nav = document.getElementById('nav-links');
  var overlay = document.getElementById('nav-overlay');
  var toggle = document.getElementById('menu-toggle');
  if (!nav || !toggle) return;

  var clockTime = document.getElementById('clock-time');
  var clockDate = document.getElementById('clock-date');

  var serverEpochMs = <?= (int) round(microtime(true) * 1000) ?>;
  var clientEpochMs = Date.now();
  var offsetMs = serverEpochMs - clientEpochMs;

  function pad2(n){ return String(n).padStart(2, '0'); }

  function updateClock(){
    if (!clockTime || !clockDate) return;
    var now = new Date(Date.now() + offsetMs);

    clockTime.textContent =
      pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());

    try{
      clockDate.textContent = new Intl.DateTimeFormat('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: '2-digit'
      }).format(now);
    } catch(e){
      clockDate.textContent = now.toLocaleDateString();
    }
  }

  function syncState(){
    var open = nav.classList.contains('show');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('nav-open', open);
  }

  function setOpen(open){
    nav.classList.toggle('show', !!open);
    syncState();
  }

  // Observe si un autre script (ex: /assets/js/app.js) gère déjà l’ouverture
  try{
    var mo = new MutationObserver(syncState);
    mo.observe(nav, { attributes: true, attributeFilter: ['class'] });
  } catch(e){}

  // Toggle avec fallback anti “double toggle”
  toggle.addEventListener('click', function () {
    var before = nav.classList.contains('show');

    // Laisse la chance à un script existant de gérer le clic d'abord
    setTimeout(function(){
      var after = nav.classList.contains('show');
      if (after === before) setOpen(!before);   // fallback si rien n’a bougé
      else syncState();                          // sinon on aligne aria/body
    }, 0);
  });

  if (overlay) {
    overlay.addEventListener('click', function () { setOpen(false); });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
  });

  nav.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a') : null;
    if (!a) return;
    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) setOpen(false);
  });

  updateClock();
  setInterval(updateClock, 1000);
  syncState();
})();
</script>

<!-- Tu peux garder ton app.js (si d'autres pages en dépendent) -->
<script src="/assets/js/app.js" defer></script>
