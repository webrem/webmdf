<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Redirection immédiate vers /pages/index.php
header("Location: ../pages/index.php");
exit;
