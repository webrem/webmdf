<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode([
        "status" => "error",
        "msg" => "Accès refusé"
    ]);
    exit;
}

if (empty($_POST['ref_vente']) || empty($_POST['mode_paiement'])) {
    echo json_encode([
        "status" => "error",
        "msg" => "Données manquantes"
    ]);
    exit;
}

$ref  = trim($_POST['ref_vente'] ?? '');
$mode = trim($_POST['mode_paiement'] ?? '');

$allowed = [
    'Espèces',
    'Carte Bancaire',
    'Virement',
    'Mixte',
    'Autre'
];

if ($ref === '' || $mode === '') {
    echo json_encode([
        "status" => "error",
        "msg" => "Données manquantes"
    ]);
    exit;
}

if (!in_array($mode, $allowed, true)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Mode de paiement non autorisé"
    ]);
    exit;
}


$conn = new mysqli(
    "localhost",
    "u498346438_calculrem",
    "Calculrem1",
    "u498346438_calculrem"
);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "msg" => "Erreur DB"
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

/* 🔁 Mise à jour VENTES */
$stmt1 = $conn->prepare(
    "UPDATE ventes SET mode_paiement = ? WHERE ref_vente = ?"
);
$stmt1->bind_param("ss", $mode, $ref);
$stmt1->execute();

/* 🔁 Mise à jour ACOMPTES */
$stmt2 = $conn->prepare(
    "UPDATE ventes_historique SET mode_paiement = ? WHERE ref_vente = ?"
);
$stmt2->bind_param("ss", $mode, $ref);
$stmt2->execute();

$stmt1->close();
$stmt2->close();

echo json_encode([
    "status" => "ok"
]);
