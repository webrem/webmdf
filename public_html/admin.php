<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Redirection immédiate vers /pages/admin.php
header("Location: ../pages/admin.php");
exit;
