<?php
session_start();
include("conexion.php");

header('Content-Type: application/json');

// 🔐 usar id_usuario (NO usuario)
if(!isset($_SESSION['id_usuario'])){
    echo json_encode(['saldo' => 0, 'total_dinero' => 0]);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

$sql = "SELECT saldo, total_dinero FROM usuarios WHERE id_usuario=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    'saldo' => floatval($data['saldo'] ?? 0),
    'total_dinero' => floatval($data['total_dinero'] ?? 0)
]);
?>