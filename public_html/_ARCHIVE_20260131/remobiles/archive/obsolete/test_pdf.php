<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

require_once(__DIR__ . '/tcpdf/tcpdf.php');

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Write(0, 'Test PDF depuis R.E.Mobiles ✅');
$pdf->Output('test_rem.pdf', 'I');
