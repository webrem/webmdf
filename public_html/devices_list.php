<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// Redirection immédiate vers /pages/devices_list.php
header("Location: ../pages/devices_list.php");
exit;
