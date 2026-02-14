<?php
/**
 * install.php — Audit + archive des pages PHP non utilisées (selon runtime_page_tracker)
 * Carlos / R.E.Mobiles
 *
 * ✅ Affiche: pages utilisées / non utilisées, nombre de hits, dernière visite
 * ✅ Archive (déplace) les pages non utilisées vers /_ARCHIVE_YYYYMMDD/
 *
 * ⚠️ Par sécurité:
 * - Mode "simulation" par défaut (aucun déplacement)
 * - Tu dois cocher une confirmation + cliquer "Archiver"
 */

// ============================
// 1) CONFIG À ADAPTER
// ============================

// Chemin vers le fichier log du runtime tracker (JSON par ligne).
// Exemple courant: /runtime_page_tracker.log ou /logs/runtime_page_tracker.jsonl
$LOG_FILE = $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.log';

// Dossiers à scanner (racine + /pages par exemple)
$SCAN_DIRS = [
  $_SERVER['DOCUMENT_ROOT'],
];

// Dossiers à EXCLURE du scan (sécurité)
$EXCLUDE_DIRS = [
  '/.git',
  '/.well-known',
  '/vendor',
  '/tcpdf',
  '/uploads',
  '/upload',
  '/assets',
  '/img',
  '/images',
  '/css',
  '/js',
  '/fonts',
  '/node_modules',
  '/cache',
  '/tmp',
  '/_archive',
];

// Fichiers à NE JAMAIS archiver (whitelist)
$KEEP_FILES = [
  '/index.php',
  '/login.php',
  '/logout.php',
  '/sync_time.php',
  '/runtime_page_tracker.php',
  '/install.php',
  '/config.php',
  '/db.php',
];

// Option: si tu veux garder tout ce qui est dans /pages/ (mettre true/false)
$KEEP_ALL_PAGES_DIR = false;

// ============================
// 2) HELPERS
// ============================

function normPath($p) {
  $p = str_replace('\\', '/', $p);
  $p = preg_replace('~//+~', '/', $p);
  return $p;
}

function relToDocRoot($abs) {
  $root = normPath($_SERVER['DOCUMENT_ROOT']);
  $abs  = normPath($abs);
  if (strpos($abs, $root) === 0) return '/' . ltrim(substr($abs, strlen($root)), '/');
  return $abs;
}

function isExcluded($relPath, $excludeDirs) {
  $relPath = normPath($relPath);
  foreach ($excludeDirs as $ex) {
    $ex = normPath($ex);
    if ($ex === '/') continue;
    if (strpos($relPath, $ex . '/') === 0 || $relPath === $ex) return true;
  }
  return false;
}

function safeMkdir($dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }
}

function html($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ============================
// 3) LIRE LES LOGS (JSONL)
// ============================

$used = []; // relPath => ['hits'=>int,'last'=>timestamp,'examples'=>[]]

$logOk = file_exists($LOG_FILE);

if ($logOk) {
  $fh = fopen($LOG_FILE, 'r');
  if ($fh) {
    while (($line = fgets($fh)) !== false) {
      $line = trim($line);
      if ($line === '') continue;

      $j = json_decode($line, true);
      if (!is_array($j)) continue;

      $script = $j['script'] ?? '';
      $uri    = $j['uri'] ?? '';
      $time   = $j['time'] ?? '';

      // On essaye d’en déduire le fichier PHP réel
      // Priorité: script (ex: ventes_historique.php)
      // On utilise aussi uri pour récupérer /pages/xxxx.php si présent
      $candidates = [];

      if ($script) $candidates[] = '/' . ltrim($script, '/');

      if ($uri) {
        $u = parse_url($uri);
        $path = $u['path'] ?? '';
        if ($path) $candidates[] = $path;
      }

      foreach ($candidates as $cand) {
        $cand = normPath($cand);

        // uniquement .php
        if (!preg_match('~\.php$~i', $cand)) continue;

        // Normaliser "/pages/x.php" vs "/x.php" si ton site log parfois double
        // On garde tel quel, car le scan retrouvera l’un ou l’autre.
        $key = $cand;

        if (!isset($used[$key])) {
          $used[$key] = [
            'hits' => 0,
            'last' => 0,
            'examples' => []
          ];
        }

        $used[$key]['hits']++;

        $ts = strtotime($time);
        if ($ts && $ts > $used[$key]['last']) $used[$key]['last'] = $ts;

        if (count($used[$key]['examples']) < 2) {
          $used[$key]['examples'][] = $uri;
        }
      }
    }
    fclose($fh);
  }
}

// ============================
// 4) SCAN DES FICHIERS PHP
// ============================

$allPhp = []; // relPath => absPath

$riiFlags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;

foreach ($SCAN_DIRS as $dir) {
  $dir = normPath($dir);
  if (!is_dir($dir)) continue;

  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, $riiFlags),
    RecursiveIteratorIterator::SELF_FIRST
  );

  foreach ($it as $file) {
    if (!$file->isFile()) continue;

    $abs = normPath($file->getPathname());
    if (!preg_match('~\.php$~i', $abs)) continue;

    $rel = relToDocRoot($abs);

    // Exclusions
    if (isExcluded($rel, $EXCLUDE_DIRS)) continue;

    $allPhp[$rel] = $abs;
  }
}

// ============================
// 5) CLASSER UTILISÉ / NON UTILISÉ
// ============================

$rows = [];
$usedCount = 0;
$unusedCount = 0;

foreach ($allPhp as $rel => $abs) {
  $keep = in_array($rel, $KEEP_FILES, true);

  if ($KEEP_ALL_PAGES_DIR && strpos($rel, '/pages/') === 0) {
    $keep = true;
  }

  $isUsed = isset($used[$rel]);

  // cas fréquent: les logs ont /pages/x.php mais le fichier est /x.php (ou inverse)
  // On tente un match par basename si pas trouvé exactement
  if (!$isUsed) {
    $base = basename($rel);
    $alt1 = '/' . $base;
    $alt2 = '/pages/' . $base;
    if (isset($used[$alt1]) || isset($used[$alt2])) {
      $isUsed = true;
      // On fusionne les stats affichées
      $stats = $used[$alt1] ?? $used[$alt2];
    } else {
      $stats = null;
    }
  } else {
    $stats = $used[$rel];
  }

  if ($keep) $isUsed = true; // si keep, on force "utilisé" pour éviter archivage

  if ($isUsed) $usedCount++;
  else $unusedCount++;

  $rows[] = [
    'rel' => $rel,
    'abs' => $abs,
    'used' => $isUsed,
    'keep' => $keep,
    'hits' => $stats['hits'] ?? ($used[$rel]['hits'] ?? 0),
    'last' => $stats['last'] ?? ($used[$rel]['last'] ?? 0),
  ];
}

// Tri: non utilisées en haut
usort($rows, function($a,$b){
  if ($a['used'] !== $b['used']) return $a['used'] ? 1 : -1;
  return strcmp($a['rel'], $b['rel']);
});

// ============================
// 6) ACTION ARCHIVE
// ============================

$skipFiles = $_POST['skip'] ?? [];
$doArchive = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_archive']) && $_POST['do_archive'] === '1');
$confirm   = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'YES');

$archiveReport = [];
$archiveDirRel = '/_ARCHIVE_' . date('Ymd');
$archiveDirAbs = normPath($_SERVER['DOCUMENT_ROOT'] . $archiveDirRel);

if ($doArchive) {
  if (!$confirm) {
    $archiveReport[] = ['type'=>'error', 'msg'=>"Confirmation manquante: coche la case."];
  } elseif (!$logOk) {
    $archiveReport[] = ['type'=>'error', 'msg'=>"Log introuvable: " . $LOG_FILE];
  } else {
    safeMkdir($archiveDirAbs);

    // Bloquer l’exécution dans l’archive (optionnel mais recommandé)
    $ht = $archiveDirAbs . '/.htaccess';
    if (!file_exists($ht)) {
      @file_put_contents($ht, "php_flag engine off\nOptions -Indexes\nDeny from all\n");
    }

    foreach ($rows as $r) {
      if ($r['used']) continue; // on archive seulement non utilisées
      if (in_array($r['rel'], $skipFiles, true)) continue;

      $srcAbs = $r['abs'];
      $srcRel = $r['rel'];

      // chemin destination en conservant la structure
      $dstAbs = normPath($archiveDirAbs . $srcRel);
      $dstDir = dirname($dstAbs);

      safeMkdir($dstDir);

      if (!file_exists($srcAbs)) {
        $archiveReport[] = ['type'=>'warn', 'msg'=>"Introuvable: $srcRel"];
        continue;
      }

      // Déplacement
      if (@rename($srcAbs, $dstAbs)) {
        $archiveReport[] = ['type'=>'ok', 'msg'=>"Archivé: $srcRel → $archiveDirRel$srcRel"];
      } else {
        $archiveReport[] = ['type'=>'error', 'msg'=>"Échec déplacement (permissions?): $srcRel"];
      }
    }

    $archiveReport[] = ['type'=>'ok', 'msg'=>"Terminé. Archive: $archiveDirRel"];
  }
}

// ============================
// 7) HTML
// ============================

$total = count($rows);

?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Audit / Archive pages PHP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,Segoe UI,Arial;margin:20px;background:#0b1220;color:#e8eefc}
    .card{background:#111a2e;border:1px solid #22345e;border-radius:12px;padding:16px;margin-bottom:14px}
    .ok{color:#35d07f}
    .warn{color:#ffd166}
    .err{color:#ff5c77}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #22345e;vertical-align:top}
    th{background:#0f1930;text-align:left}
    .badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}
    .b-used{background:#143a2a;color:#7dffb2;border:1px solid #256a48}
    .b-unused{background:#3a1a1f;color:#ff9aaa;border:1px solid #7a2a36}
    .b-keep{background:#2c2a14;color:#ffe27d;border:1px solid #6a5f25}
    .small{opacity:.85;font-size:12px}
    input[type="text"]{width:100%;padding:10px;border-radius:8px;border:1px solid #22345e;background:#0f1930;color:#e8eefc}
    button{padding:10px 14px;border-radius:10px;border:0;background:#2b6cff;color:#fff;font-weight:800;cursor:pointer}
    button.danger{background:#ff3b5c}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    .row > .card{flex:1;min-width:260px}
    a{color:#7db2ff}
  </style>
</head>
<body>

<div class="card">
  <h2 style="margin:0 0 8px">Audit pages PHP utilisées / non utilisées</h2>
  <div class="small">
    Log: <b><?=html($LOG_FILE)?></b> —
    <?= $logOk ? "<span class='ok'>OK</span>" : "<span class='err'>INTROUVABLE</span>" ?>
  </div>
</div>

<div class="row">
  <div class="card">
    <div><b>Total pages PHP scannées:</b> <?= (int)$total ?></div>
    <div><b>Utilisées (ou protégées KEEP):</b> <?= (int)$usedCount ?></div>
    <div><b>Non utilisées (candidates archive):</b> <?= (int)$unusedCount ?></div>
  </div>

  <div class="card">
    <form method="post">
      <h3 style="margin-top:0">Archiver les non utilisées</h3>

      <p class="small">
        Ça déplace les fichiers non utilisés vers <b><?=html($archiveDirRel)?></b>.
        Par défaut, coche la confirmation pour autoriser l’action.
      </p>

      <label style="display:block;margin:8px 0">
        <input type="checkbox" name="confirm" value="YES">
        Je confirme l’archivage (déplacement réel des fichiers).
      </label>

      <input type="hidden" name="do_archive" value="1">
      <button class="danger" type="submit">Archiver maintenant</button>

      <p class="small" style="margin-top:10px">
        Astuce: fais d’abord une sauvegarde ou un zip de ton site.
      </p>
    </form>
  </div>
</div>

<?php if (!empty($archiveReport)): ?>
  <div class="card">
    <h3 style="margin-top:0">Résultat</h3>
    <ul>
      <?php foreach ($archiveReport as $r): ?>
        <?php
          $cls = ($r['type']==='ok'?'ok':($r['type']==='warn'?'warn':'err'));
        ?>
        <li class="<?= $cls ?>"><?= html($r['msg']) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0">Liste des pages</h3>
  <p class="small">Non utilisées en haut. “KEEP” = jamais archivé.</p>

  <table>
    <thead>
      <tr>
        <th>Fichier</th>
        <th>Statut</th>
        <th>Hits</th>
        <th>Dernière visite</th>
        <th>Épargner</th>
      </tr>
    </thead>
    
    <tbody>
<?php foreach ($rows as $r): ?>
  <tr class="row-clickable" data-used="<?= $r['used'] ? '1' : '0' ?>">
    
    <!-- FICHIER -->
    <td>
      <a href="<?= html($r['rel']) ?>"
         target="_blank"
         rel="noopener"
         class="file-link">
        <b><?= html($r['rel']) ?></b>
      </a>
      <div class="small"><?= html($r['abs']) ?></div>
    </td>

    <!-- STATUT -->
    <td>
      <?php if ($r['keep']): ?>
        <span class="badge b-keep">KEEP</span>
      <?php elseif ($r['used']): ?>
        <span class="badge b-used">UTILISÉE</span>
      <?php else: ?>
        <span class="badge b-unused">NON UTILISÉE</span>
      <?php endif; ?>
    </td>

    <!-- HITS -->
    <td><?= (int)$r['hits'] ?></td>

    <!-- DERNIÈRE VISITE -->
    <td>
      <?= !empty($r['last']) ? html(date('Y-m-d H:i:s', (int)$r['last'])) : '-' ?>
    </td>

    <!-- ÉPARGNER -->
    <td style="text-align:center">
      <?php if (!$r['used']): ?>
        <input
          type="checkbox"
          class="spare-checkbox"
          name="skip[]"
          value="<?= html($r['rel']) ?>"
        >
      <?php else: ?>
        —
      <?php endif; ?>
    </td>

  </tr>
<?php endforeach; ?>
</tbody>

  </table>
</div>

<div class="card small">
  <b>Si tu vois “Log introuvable”</b>, c’est que ton tracker écrit dans un autre fichier.
  Ouvre <code>runtime_page_tracker.php</code> et cherche où il fait <code>file_put_contents</code>,
  puis remplace la variable <code>$LOG_FILE</code> en haut de ce script.
</div>


<script>
document.querySelectorAll('.row-clickable').forEach(row => {

  // si page utilisée → on ne fait rien
  if (row.dataset.used === '1') return;

  row.addEventListener('click', function (e) {

    // si on clique sur le lien → ouvrir la page, NE PAS cocher
    if (e.target.closest('a')) return;

    const checkbox = row.querySelector('.spare-checkbox');
    if (!checkbox) return;

    checkbox.checked = !checkbox.checked;
  });

});
</script>

</body>
</html>
