<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Redirection immédiate vers /pages/import_stock.php
header("Location: ../stock/import_stock.php");
exit;
