<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// ===========================================================
// 🔐 SCRAPER CONNECTÉ MULTI-PRODUITS (avec login + export CSV)
// ===========================================================

// === CHARGEUR .ENV ===
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        putenv("$name=$value");
    }
}

$LOGIN_URL   = getenv('LOGIN_URL') ?: 'https://lcd-phone.com/fr/connexion';
$USERNAME    = getenv('SCRAPER_USER');
$PASSWORD    = getenv('SCRAPER_PASS');
$EMAIL_FIELD = getenv('EMAIL_FIELD') ?: 'email';
$PASS_FIELD  = getenv('PASS_FIELD') ?: 'password';

// === FONCTIONS CURL ===
function curl_get($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function curl_post($url, $postFields, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// === LOGIN ===
function login_to_site($cookieFile) {
    global $LOGIN_URL, $USERNAME, $PASSWORD, $EMAIL_FIELD, $PASS_FIELD;
    if (!$USERNAME || !$PASSWORD) return false;
    $html = curl_get($LOGIN_URL, $cookieFile);
    // cherche un éventuel token CSRF
    if (preg_match('/name="([^"]*token[^"]*)"[^>]*value="([^"]+)"/i', $html, $m))
        $token = [$m[1], $m[2]];
    $post = [$EMAIL_FIELD => $USERNAME, $PASS_FIELD => $PASSWORD];
    if (!empty($token)) $post[$token[0]] = $token[1];
    curl_post($LOGIN_URL, $post, $cookieFile);
    return true;
}

// === EXTRACTION DES PRODUITS ===
function parse_category($html, $url) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML($html);
    $xp = new DOMXPath($dom);
    $fournisseur = parse_url($url, PHP_URL_HOST);

    $items = [];
    $nodes = $xp->query("//article[contains(@class,'product-miniature')]");
    foreach ($nodes as $n) {
        $designation = trim($xp->evaluate("string(.//h2|.//h3|.//a[contains(@class,'product-title')])", $n));
        $ref = trim($xp->evaluate("string(.//*[contains(text(),'Réf') or contains(text(),'Référence')])", $n));
        $ean = trim($xp->evaluate("string(.//*[contains(text(),'EAN')])", $n));
        $marque = trim($xp->evaluate("string(.//*[contains(text(),'Marque')]/following::span[1])", $n));
        $prixTxt = trim($xp->evaluate("string(.//*[contains(@class,'price')])", $n));
        preg_match('/([0-9]+[,.][0-9]+)/', $prixTxt, $m);
        $prix = isset($m[1]) ? str_replace(',', '.', $m[1]) : '—';

        $items[] = [
            'reference'   => $ref ?: '—',
            'designation' => $designation ?: '—',
            'marque'      => $marque ?: '—',
            'ean13'       => $ean ?: '—',
            'prix_ht'     => $prix,
            'fournisseur' => $fournisseur
        ];
    }

    // supprimer doublons
    $uniq = [];
    $final = [];
    foreach ($items as $i) {
        $key = $i['reference'] . $i['ean13'];
        if (isset($uniq[$key])) continue;
        $uniq[$key] = true;
        $final[] = $i;
    }
    return $final;
}

// === MAIN ===
$url = $_GET['url'] ?? '';
$cookieFile = sys_get_temp_dir() . '/lcd_' . md5($url . time()) . '.txt';
$items = [];

if ($url) {
    login_to_site($cookieFile);
    $html = curl_get($url, $cookieFile);
    $items = parse_category($html, $url);
}

if (isset($_GET['export']) && $items) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="produits_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['reference','designation','marque','ean13','prix_ht','fournisseur'], ';');
    foreach ($items as $r)
        fputcsv($out, [$r['reference'],$r['designation'],$r['marque'],$r['ean13'],$r['prix_ht'],$r['fournisseur']], ';');
    fclose($out);
    unlink($cookieFile);
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Scraper Connecté LCD-Phone</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <h3 class="mb-3">📦 Scraper Connecté LCD-Phone</h3>
    <form class="mb-4" method="get">
        <div class="input-group">
            <input type="url" class="form-control" name="url" placeholder="Collez ici l’URL d’une page catégorie" value="<?=htmlspecialchars($url)?>" required>
            <button class="btn btn-primary">Analyser</button>
            <?php if ($items): ?>
                <a href="?url=<?=urlencode($url)?>&export=1" class="btn btn-success">📤 Export CSV</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!$url): ?>
        <div class="alert alert-info">Saisissez une URL de catégorie, par ex. <br><code>https://lcd-phone.com/fr/3436-iphone-16-pro-max</code></div>
    <?php elseif (!$items): ?>
        <div class="alert alert-warning">Aucun produit détecté (peut-être besoin du login ou d'une autre page).</div>
    <?php else: ?>
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark">
                <tr>
                    <th>Référence</th>
                    <th>Désignation</th>
                    <th>Marque</th>
                    <th>EAN-13</th>
                    <th>Prix HT</th>
                    <th>Fournisseur</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                    <tr>
                        <td><?=htmlspecialchars($i['reference'])?></td>
                        <td><?=htmlspecialchars($i['designation'])?></td>
                        <td><?=htmlspecialchars($i['marque'])?></td>
                        <td><?=htmlspecialchars($i['ean13'])?></td>
                        <td><?=htmlspecialchars($i['prix_ht'])?></td>
                        <td><?=htmlspecialchars($i['fournisseur'])?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
