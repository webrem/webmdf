<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Redirection immédiate vers /pages/dashboard.php
header("Location: ../pages/dashboard.php");
exit;
