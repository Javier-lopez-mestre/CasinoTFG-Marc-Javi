<?php
session_start();
include("conexion.php");

$data=json_decode(file_get_contents("php://input"),true);

$id=$_SESSION['id_usuario'];
$final=floatval($data['bankroll']);

$stmt=$conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$saldo=$stmt->get_result()->fetch_assoc()['saldo'];

$entrada=$_SESSION['entrada_juego'] ?? 0;

$nuevo=$saldo-$entrada+$final;

$stmt=$conexion->prepare("UPDATE usuarios SET saldo=? WHERE id_usuario=?");
$stmt->bind_param("di",$nuevo,$id);
$stmt->execute();

unset($_SESSION['entrada_juego']);

echo json_encode(["ok"=>true]);