<?php
// ==========================================
// EXPORT CSV APRÈS LOGIN AUTOMATIQUE
// ==========================================

if (file_exists(__DIR__.'/.env')) {
    $lines = file(__DIR__.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, '"\'');
        putenv("$name=$value");
    }
}

$LOGIN_URL   = getenv('LOGIN_URL');
$EMAIL_FIELD = getenv('EMAIL_FIELD') ?: 'email';
$PASS_FIELD  = getenv('PASS_FIELD') ?: 'password';
$USERNAME    = getenv('SCRAPER_USER');
$PASSWORD    = getenv('SCRAPER_PASS');

function curl_get($url,$cookieFile=null){/* idem que plus haut */}
function curl_post($url,$postFields,$cookieFile=null){/* idem que plus haut */}
function extract_csrf_token($html){
    libxml_use_internal_errors(true);
    $d=new DOMDocument();$d->loadHTML($html);
    $x=new DOMXPath($d);
    foreach($x->query("//input[@type='hidden']") as $n){
        $nName=$n->getAttribute('name');$nVal=$n->getAttribute('value');
        if(preg_match("/csrf|token/i",$nName)&&$nVal)return[$nName,$nVal];
    }return null;
}

$url=$_GET['url']??''; if(!$url) die("URL manquante");
$cookieFile=sys_get_temp_dir().'/cookies_'.md5($url.time()).'.txt';
$loginHtml=curl_get($LOGIN_URL,$cookieFile);
$csrf=extract_csrf_token($loginHtml);
$post=[$EMAIL_FIELD=>$USERNAME,$PASS_FIELD=>$PASSWORD];
if($csrf)$post[$csrf[0]]=$csrf[1];
curl_post($LOGIN_URL,$post,$cookieFile);
$html=curl_get($url,$cookieFile);
@unlink($cookieFile);

libxml_use_internal_errors(true);
$dom=new DOMDocument();$dom->loadHTML($html);
$xp=new DOMXPath($dom);
$fournisseur=parse_url($url,PHP_URL_HOST);
$nodes=$xp->query("//article | //div[contains(@class,'product')]");
$items=[];$i=0;
foreach($nodes as $n){
    $i++;
    $designation=trim($xp->evaluate("string(.//h2|.//h3|.//h5|.//a[contains(@class,'product-title')])",$n));
    $prixTxt=trim($xp->evaluate("string(.//*[contains(@class,'price')])",$n));
    preg_match('/([0-9]+[,.][0-9]{2})\s*€\s*HT/i',$prixTxt,$m);
    $prix=isset($m[1])?str_replace(',','.',$m[1]):'0';
    $ref=trim($xp->evaluate("string(.//*[contains(@class,'ref') or contains(@class,'sku')])",$n));
    if(!$ref)$ref='AUTO-'.$i;
    $items[]=['reference'=>$ref,'designation'=>$designation?:'Article inconnu','quantite'=>1,'prix_achat'=>$prix,'tva'=>getenv('DEFAULT_TVA')?:'20','imei'=>'','fournisseur'=>$fournisseur];
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export_articles.csv"');
$out=fopen('php://output','w');
fputcsv($out,['reference','designation','quantite','prix_achat','tva','imei','fournisseur']);
foreach($items as $it)fputcsv($out,[$it['reference'],$it['designation'],$it['quantite'],$it['prix_achat'],$it['tva'],$it['imei'],$it['fournisseur']]);
fclose($out);
exit;
