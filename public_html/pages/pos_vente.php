<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Redirection immédiate vers /pages/pos_vente.php
header("Location: ../pos/pos_vente.php");
exit;
