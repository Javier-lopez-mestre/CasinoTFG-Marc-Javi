<?php
require 'vendor/autoload.php';
include("conexion.php");

// 🔐 CLAVES (mejor aquí o en config)
\Stripe\Stripe::setApiKey('XTKQMZeBsl0emWXUxBJY9ZXOPbrOmZF63wxSOFEmy1LhDpiNRirrDYO7qzM04LPEL0K0WXUb4tuGc0gMudLPP00JQAvsutG');

$endpoint_secret = 'TU_WEBHOOK_SECRET';

$payload = file_get_contents("php://input");
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch(Exception $e) {
    http_response_code(400);
    exit("Webhook error");
}

// =========================
// PAGO COMPLETADO
// =========================
if ($event->type === 'checkout.session.completed') {

    $session = $event->data->object;

    $usuario = $session->metadata->usuario ?? null;
    $monto = isset($session->metadata->monto)
        ? floatval($session->metadata->monto)
        : 0;

    if(!$usuario || $monto <= 0){
        http_response_code(200);
        exit("Invalid metadata");
    }

    // 🔍 buscar usuario
    $sql = "SELECT id_usuario FROM usuarios WHERE nombre_usuario=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();

    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if($user){

        $id_usuario = $user['id_usuario'];

        // 💰 sumar saldo
        $sql = "UPDATE usuarios SET saldo = saldo + ? WHERE id_usuario=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("di", $monto, $id_usuario);
        $stmt->execute();

        // 📊 transacción
        $sql = "INSERT INTO transacciones (id_usuario, tipo, monto)
                VALUES (?, 'deposito', ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("id", $id_usuario, $monto);
        $stmt->execute();
    }
}

http_response_code(200);
echo "OK";