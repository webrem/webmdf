<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

session_start();
if (isset($_POST['mainOeuvre']) || isset($_POST['prixAchat'])) {
    // Recalcul simple du prix pour le stocker côté session
    $prixAchats = $_POST['prixAchat'] ?? [];
    $total = 0;
    foreach ($prixAchats as $p) $total += floatval($p);
    $fraisEnvoi = 15.0;
    $tauxDouane = 0.25;
    $margeMagasin = 0.25;
    $mainOeuvre = floatval($_POST['mainOeuvre'] ?? 0);

    $totalAchat   = $total + $fraisEnvoi;
    $fraisDouane  = $totalAchat * $tauxDouane;
    $coutComplet  = $totalAchat + $fraisDouane;
    $prixMagasin  = $coutComplet * (1 + $margeMagasin);
    $prixFinal    = ($prixMagasin + $mainOeuvre);

    $_SESSION['dernier_prix_final'] = ceil($prixFinal / 5) * 5 - 0.01;
}
/* === ✅ Enregistrement automatique du client (si case cochée) === */
if (!empty($_POST['saveClient']) && $_POST['saveClient'] == '1') {
    $nom  = trim($_POST['clientNom'] ?? '');
    $tel  = trim($_POST['clientTel'] ?? '');
    if ($nom !== '' && $tel !== '') {
        // Vérifie si le client existe déjà
        $check = $conn->prepare("SELECT COUNT(*) FROM clients WHERE nom = ? OR telephone = ?");
        $check->bind_param("ss", $nom, $tel);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count == 0) {
            $insert = $conn->prepare("INSERT INTO clients (nom, telephone, date_creation) VALUES (?, ?, NOW())");
            $insert->bind_param("ss", $nom, $tel);
            $insert->execute();
            $insert->close();
        }
    }
}

?>
