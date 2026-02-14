<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// export_articles.php
// ------------------------------------------
// EXPORT DES ARTICLES EN CSV
// ------------------------------------------

function fetch_html($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; scraper/1.0)");
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function parse_items($url) {
    $html = fetch_html($url);
    if (!$html) return [];

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $fournisseur = parse_url($url, PHP_URL_HOST);

    $items = [];
    $nodes = $xpath->query("//article | //div[contains(@class,'product')]");
    $i = 0;

    foreach ($nodes as $n) {
        $designation = trim($xpath->evaluate("string(.//h2 | .//h3 | .//h5)", $n));
        $prix = trim($xpath->evaluate("string(.//*[contains(@class,'price')])", $n));
        $ref  = trim($xpath->evaluate("string(.//*[contains(@class,'product-reference')])", $n));

        if ($designation === "" && $prix === "") continue;

        $items[] = [
            'reference'   => $ref ?: 'AUTO-' . (++$i),
            'designation' => $designation ?: 'Article inconnu',
            'quantite'    => 1,
            'prix_achat'  => $prix ?: '0',
            'tva'         => '8.5',
            'imei'        => '—',
            'fournisseur' => $fournisseur
        ];
    }

    return $items;
}

// ----- EXPORT -----
$url = $_GET['url'] ?? '';
if (!$url) die("⚠️ URL manquante");

$items = parse_items($url);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export_articles.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['reference', 'designation', 'quantite', 'prix_achat', 'tva', 'imei', 'fournisseur']);

foreach ($items as $it) {
    fputcsv($output, [
        $it['reference'],
        $it['designation'],
        $it['quantite'],
        $it['prix_achat'],
        $it['tva'],
        $it['imei'],
        $it['fournisseur']
    ]);
}
fclose($output);
exit;
