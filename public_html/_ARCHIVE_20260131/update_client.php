<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

$conn = new mysqli("localhost","u498346438_calculrem","Calculrem1","u498346438_calculrem");
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '-03:00'"); // ⏰ Correction fuseau horaire
$conn->set_charset("utf8mb4");

$id=(int)$_POST['id'];
$nom=trim($_POST['nom']); $soc=trim($_POST['societe']);
$tel=trim($_POST['telephone']); $email=trim($_POST['email']);
$adr=trim($_POST['adresse']); $type=trim($_POST['type_client']);
$rem=(int)$_POST['remise_pct'];

if($id>0 && $nom && $tel){
  $stmt=$conn->prepare("UPDATE clients SET nom=?,societe=?,telephone=?,email=?,adresse=?,type_client=?,remise_pct=? WHERE id=?");
  $stmt->bind_param("ssssssii",$nom,$soc,$tel,$email,$adr,$type,$rem,$id);
  $stmt->execute();
  echo "✅ Client mis à jour avec succès.";
}else echo "⚠️ Données invalides.";
