<?php
session_start();
include("conexion.php");

header("Content-Type: application/json");

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        "ok" => false,
        "message" => "No autenticado",
        "saldo" => 0,
        "total_dinero" => 0
    ]);
    exit();
}

$idUsuario = (int) $_SESSION['id_usuario'];

function columnaExiste(mysqli $conexion, string $tabla, string $columna): bool {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    $stmt->bind_param("ss", $tabla, $columna);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return ((int) ($row['total'] ?? 0)) > 0;
}

$tieneTotalDinero = columnaExiste($conexion, "usuarios", "total_dinero");

if ($tieneTotalDinero) {
    $stmt = $conexion->prepare("
        SELECT saldo, total_dinero
        FROM usuarios
        WHERE id_usuario = ?
    ");
} else {
    $stmt = $conexion->prepare("
        SELECT saldo
        FROM usuarios
        WHERE id_usuario = ?
    ");
}

if (!$stmt) {
    echo json_encode([
        "ok" => false,
        "message" => "Error preparando consulta: " . $conexion->error,
        "saldo" => 0,
        "total_dinero" => 0
    ]);
    exit();
}

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode([
        "ok" => false,
        "message" => "Usuario no encontrado",
        "saldo" => 0,
        "total_dinero" => 0
    ]);
    exit();
}

$saldo = isset($row['saldo']) ? (float) $row['saldo'] : 0;

$totalDinero = $tieneTotalDinero && isset($row['total_dinero'])
    ? (float) $row['total_dinero']
    : $saldo;

echo json_encode([
    "ok" => true,
    "saldo" => round($saldo, 2),
    "total_dinero" => round($totalDinero, 2)
]);
exit();