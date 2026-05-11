<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';
require 'vendor/autoload.php';

if (empty($STRIPE_SECRET_KEY) || empty($STRIPE_WEBHOOK_SECRET)) {
    http_response_code(500);
    exit('Stripe not configured');
}

\Stripe\Stripe::setApiKey($STRIPE_SECRET_KEY);
$endpoint_secret = $STRIPE_WEBHOOK_SECRET;

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (Exception $e) {
    http_response_code(400);
    exit('Webhook error');
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $usuario = $session->metadata->usuario ?? null;
    $monto = isset($session->metadata->monto) ? (float)$session->metadata->monto : 0.0;

    if (!$usuario || $monto <= 0) {
        http_response_code(200);
        exit('Invalid metadata');
    }

    // Buscar usuario
    $sql = "SELECT id_usuario FROM usuarios WHERE nombre_usuario=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $usuario);
    $stmt->execute();

    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;

    if ($user) {
        $id_usuario = (int)$user['id_usuario'];

        // Ideal: idempotencia por session/payment id.
        $sql = "UPDATE usuarios SET saldo = saldo + ? WHERE id_usuario=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('di', $monto, $id_usuario);
        $stmt->execute();

        $sql = "INSERT INTO transacciones (id_usuario, tipo, monto)
                VALUES (?, 'deposito', ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('id', $id_usuario, $monto);
        $stmt->execute();
    }
}

http_response_code(200);
echo 'OK';

