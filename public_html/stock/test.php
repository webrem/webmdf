<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

ini_set('display_errors',1);
error_reporting(E_ALL);

try {
  $pdo = new PDO(
    "mysql:host=localhost;dbname=TA_BASE;charset=utf8",
    "TON_USER",
    "TON_MDP"
  );
  echo "✅ Connexion OK";
} catch (PDOException $e) {
  echo "❌ Erreur : " . $e->getMessage();
}