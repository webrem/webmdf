<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Redirection immédiate vers /pages/stock_add.php
header("Location: ../stock/stock_add.php");
exit;
