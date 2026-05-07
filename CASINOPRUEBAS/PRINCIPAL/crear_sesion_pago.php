<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config_stripe.php';

session_start();
header('Content-Type: application/json');

// 🔐 comprobar sesión
if(!isset($_SESSION['id_usuario'])){
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

// 📥 leer JSON
$input = json_decode(file_get_contents("php://input"), true);

if(!$input || !isset($input['monto'])){
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit();
}

$monto = floatval($input['monto']);

// 🔐 validación
if($monto < 1 || $monto > 1000){
    http_response_code(400);
    echo json_encode(["error" => "Monto inválido"]);
    exit();
}

try {

    // 🔥 clave Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Recarga saldo casino',
                ],
                'unit_amount' => intval($monto * 100),
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',

        // 🔥 datos para luego actualizar saldo
        'metadata' => [
            'id_usuario' => $_SESSION['id_usuario'],
            'monto' => $monto
        ],

        'success_url' => 'http://localhost:8080/exito.php',
        'cancel_url' => 'http://localhost:8080/cancelado.php',
    ]);

    echo json_encode([
        "url" => $session->url
    ]);

} catch(Exception $e) {

    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}