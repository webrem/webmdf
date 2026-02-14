<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// scraper.php

function fetch_html($url) {
    // On utilise cURL pour plus de contrôle
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // simuler un user agent banal pour ne pas être bloqué
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; scraper/1.0)");
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function parse_items($html) {
    $dom = new DOMDocument();
    // Supprimer les warnings dus au HTML mal formé
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $items = [];

    // Exemple : les produits dans <div class="product"> ou <article> ou <div class="product-item">
    // Inspecte le site cible pour trouver le bon sélecteur
    // Ici, dans l’exemple de lcd-phone.com, les produits sont listés dans des conteneurs — on identifie via le texte “#### Produit …”
    // On peut cibler les balises h5 ou éléments avec un certain classe

    // Par exemple : tous les <h5> dans une certaine section
    foreach ($xpath->query("//h5") as $h5) {
        $title = trim($h5->textContent);
        if ($title === "") continue;
        // On peut chercher le lien dans l’enfant <a>
        $link = "";
        $a = $h5->getElementsByTagName("a")->item(0);
        if ($a) {
            $link = $a->getAttribute("href");
            // s’il s’agit d’un lien relatif, on pourrait le compléter avec le domaine
        }
        $items[] = [
            "title" => $title,
            "link" => $link
        ];
    }

    return $items;
}

// URL cible
$url = isset($_GET['url']) ? $_GET['url'] : '';
if (!$url) {
    echo "Donne-moi une URL, ex: ?url=https://exemple.com";
    exit;
}

$html = fetch_html($url);
$items = parse_items($html);

// On affiche la page spéciale
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Scrap articles — <?= htmlspecialchars($url) ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin:20px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; }
    th { background: #f5f5f5; }
    .export-btn { margin: 15px 0; }
  </style>
</head>
<body>
  <h1>Articles récupérés depuis : <?= htmlspecialchars($url) ?></h1>
  <a class="export-btn" href="export.php?url=<?= urlencode($url) ?>">Exporter CSV</a>
  <table>
    <tr><th>Titre</th><th>Lien</th></tr>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['title']) ?></td>
        <td><?php if ($it['link']): ?><a href="<?= htmlspecialchars($it['link']) ?>" target="_blank"><?= htmlspecialchars($it['link']) ?></a><?php else: ?>—<?php endif ?></td>
      </tr>
    <?php endforeach ?>
  </table>
</body>
</html>
