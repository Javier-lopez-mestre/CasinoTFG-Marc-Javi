<?php
session_start();
include("conexion.php");

header("Content-Type: application/json");

if(!isset($_SESSION['id_usuario'])){
    echo json_encode(["error" => "no session"]);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$data = json_decode(file_get_contents("php://input"), true);

// Si es para guardar saldo
if(isset($data['saveSaldo'])) {
    $newSaldo = floatval($data['newSaldo']);
    
    // Limitar a saldo máximo para evitar exploits
    $stmt = $conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario=?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $maxSaldo = $user['saldo'] + 10000; // Máximo que puede ganar en una sesión
    
    if($newSaldo > 0 && $newSaldo <= $maxSaldo) {
        $stmt = $conexion->prepare("UPDATE usuarios SET saldo=? WHERE id_usuario=?");
        $stmt->bind_param("di", $newSaldo, $id_usuario);
        $stmt->execute();
        
        // Registrar transacción
        $tipoTransaccion = $newSaldo > $user['saldo'] ? 'ganancia' : 'apuesta';
        $monto = abs($newSaldo - $user['saldo']);
        
        $stmt = $conexion->prepare("INSERT INTO transacciones (id_usuario, tipo, monto) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $id_usuario, $tipoTransaccion, $monto);
        $stmt->execute();
    }
    
    echo json_encode(["ok" => true]);
    exit();
}

// Procesar tirada de tragaperras
$bet = floatval($data['bet'] ?? 0);

if($bet <= 0) {
    echo json_encode(["error" => "invalid bet"]);
    exit();
}

// Símbolos del juego
$symbols = ['🍎', '🍊', '🍋', '🍇', '💎', '👑', '7️⃣', '⭐'];

// Probabilidades ponderadas (multiplicador de aparición)
$weightedSymbols = [];
$weights = [
    '🍎' => 15,  // Muy común
    '🍊' => 14,
    '🍋' => 13,
    '🍇' => 12,
    '⭐' => 8,
    '💎' => 5,
    '👑' => 4,
    '7️⃣' => 2   // Raro pero alto pago
];

foreach($weights as $symbol => $weight) {
    for($i = 0; $i < $weight; $i++) {
        $weightedSymbols[] = $symbol;
    }
}

// Generar tirada
$finalSymbols = [
    $weightedSymbols[array_rand($weightedSymbols)],
    $weightedSymbols[array_rand($weightedSymbols)],
    $weightedSymbols[array_rand($weightedSymbols)]
];

// Aumentar probabilidad de ganancias ocasionales (20% de probabilidad de ganancia)
$randomChance = mt_rand(1, 100);
if($randomChance <= 20) {
    // Forzar al menos una ganancia
    if($randomChance <= 10) {
        // Doble (más común)
        $matchSymbol = $weightedSymbols[array_rand($weightedSymbols)];
        $finalSymbols[0] = $matchSymbol;
        $finalSymbols[1] = $matchSymbol;
    } else {
        // Triple (raro)
        $matchSymbol = $symbols[array_rand($symbols)];
        $finalSymbols[0] = $matchSymbol;
        $finalSymbols[1] = $matchSymbol;
        $finalSymbols[2] = $matchSymbol;
    }
}

// Calcular ganancia
$multipliers = [
    '7️⃣' => ['triple' => 100, 'double' => 20],
    '👑' => ['triple' => 80, 'double' => 15],
    '💎' => ['triple' => 60, 'double' => 12],
    '⭐' => ['triple' => 50, 'double' => 10],
    '🍇' => ['triple' => 40, 'double' => 8],
    '🍋' => ['triple' => 30, 'double' => 6],
    '🍊' => ['triple' => 20, 'double' => 4],
    '🍎' => ['triple' => 10, 'double' => 2]
];

$winAmount = 0;
$bonus = 0;

if($finalSymbols[0] === $finalSymbols[1] && $finalSymbols[1] === $finalSymbols[2]) {
    // TRIPLE
    $symbol = $finalSymbols[0];
    $multiplier = $multipliers[$symbol]['triple'] ?? 1;
    $winAmount = $bet * $multiplier;
    
    // Bonificación por triple (20% extra)
    $bonus = $winAmount * 0.2;
    
} elseif($finalSymbols[0] === $finalSymbols[1] || $finalSymbols[1] === $finalSymbols[2]) {
    // DOBLE
    $symbol = ($finalSymbols[0] === $finalSymbols[1]) ? $finalSymbols[0] : $finalSymbols[2];
    $multiplier = $multipliers[$symbol]['double'] ?? 1;
    $winAmount = $bet * $multiplier;
}

// Jackpot especial (cada 500 giros aproximadamente)
$jackpotRoll = mt_rand(1, 500);
if($jackpotRoll === 1 && $finalSymbols[0] === $finalSymbols[1] && $finalSymbols[1] === $finalSymbols[2]) {
    $bonus += $bet * 100; // 100x apuesta extra
}

// Registrar apuesta
$resultado = ($winAmount > 0) ? 'ganada' : 'perdida';
$stmt = $conexion->prepare("INSERT INTO apuestas (id_usuario, id_juego, monto_apuesta, resultado) VALUES (?, 3, ?, ?)");
$stmt->bind_param("ids", $id_usuario, $bet, $resultado);
$stmt->execute();

echo json_encode([
    "symbols" => $finalSymbols,
    "win" => $winAmount,
    "bonus" => $bonus,
    "total_win" => $winAmount + $bonus
]);
?>
