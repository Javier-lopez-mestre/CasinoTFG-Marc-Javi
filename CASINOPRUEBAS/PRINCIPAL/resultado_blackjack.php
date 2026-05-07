<?php
session_start();
include("conexion.php");

header("Content-Type: application/json");

if(!isset($_SESSION['id_usuario'])){
    echo json_encode(["error"=>"no session"]);
    exit();
}

$id = $_SESSION['id_usuario'];

$data = json_decode(file_get_contents("php://input"), true);
$saldo = floatval($data['saldo']);

$stmt = $conexion->prepare("UPDATE usuarios SET saldo=? WHERE id_usuario=?");
$stmt->bind_param("di",$saldo,$id);
$stmt->execute();

echo json_encode(["ok"=>true]);