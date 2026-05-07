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
    
    // Limitar a saldo máximo para evitar exploits (máx 20% ganancia por sesión)
    $stmt = $conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario=?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $initialSaldo = $user['saldo'];
    $maxSaldo = $initialSaldo * 1.2; // Máximo 20% de ganancia
    
    if($newSaldo > 0 && $newSaldo <= $maxSaldo) {
        $stmt = $conexion->prepare("UPDATE usuarios SET saldo=? WHERE id_usuario=?");
        $stmt->bind_param("di", $newSaldo, $id_usuario);
        $stmt->execute();
        
        // Registrar transacción
        $tipoTransaccion = $newSaldo > $initialSaldo ? 'ganancia' : 'apuesta';
        $monto = abs($newSaldo - $initialSaldo);
        
        $stmt = $conexion->prepare("INSERT INTO transacciones (id_usuario, tipo, monto) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $id_usuario, $tipoTransaccion, $monto);
        $stmt->execute();
    }
    
    echo json_encode(["ok" => true]);
    exit();
}

// Procesar tirada de tragaperras 3x3
$bet = floatval($data['bet'] ?? 0);

if($bet <= 0) {
    echo json_encode(["error" => "invalid bet"]);
    exit();
}

// SÍMBOLOS CON PESOS REALISTAS
$symbols = ['🍎', '🍊', '🍇', '⭐', '💎', '👑'];

// Pesos (frecuencia de aparición) - MÁS BALANACEADOS
$weights = [
    '🍎' => 40,   // Muy común, poco paga
    '🍊' => 35,   // Común
    '🍇' => 30,   // Común
    '⭐' => 20,   // Menos común
    '💎' => 12,   // Raro
    '👑' => 8     // Muy raro, paga mucho
];

// Crear array ponderado
$weightedSymbols = [];
foreach($weights as $symbol => $weight) {
    for($i = 0; $i < $weight; $i++) {
        $weightedSymbols[] = $symbol;
    }
}

// GENERAR MATRIZ 3x3 (9 posiciones)
$matrix = [];
for($i = 0; $i < 9; $i++) {
    $matrix[$i] = $weightedSymbols[array_rand($weightedSymbols)];
}

// DEFINIR LÍNEAS DE PAGO (9 líneas estándar)
$payLines = [
    [0, 1, 2],      // Línea superior
    [3, 4, 5],      // Línea media
    [6, 7, 8],      // Línea inferior
    [0, 4, 8],      // Diagonal \
    [2, 4, 6],      // Diagonal /
    [1, 4, 7],      // Vertical centro
    [0, 3, 6],      // Vertical izquierda
    [2, 5, 8],      // Vertical derecha
    [0, 1, 3, 4, 6, 7] // Combinación
];

// TABLA DE PAGOS REALISTA
$payTable = [
    '👑' => ['three' => 25, 'two' => 3],
    '💎' => ['three' => 20, 'two' => 2.5],
    '⭐' => ['three' => 15, 'two' => 2],
    '🍇' => ['three' => 10, 'two' => 1.5],
    '🍊' => ['three' => 5, 'two' => 1],
    '🍎' => ['three' => 3, 'two' => 0.5]
];

// PROCESAR LÍNEAS DE PAGO
$totalWin = 0;
$winMessage = '';
$wins = [];

foreach($payLines as $line) {
    // Contar símbolos iguales en esta línea desde izquierda
    $lineSymbols = [];
    foreach($line as $pos) {
        if($pos < 9) {
            $lineSymbols[] = $matrix[$pos];
        }
    }
    
    // Verificar coincidencias (mínimo 2 símbolos iguales)
    if(count($lineSymbols) >= 2) {
        $firstSymbol = $lineSymbols[0];
        $matchCount = 1;
        
        for($i = 1; $i < count($lineSymbols); $i++) {
            if($lineSymbols[$i] === $firstSymbol) {
                $matchCount++;
            } else {
                break;
            }
        }
        
        // Si hay coincidencia de 2 o 3
        if($matchCount >= 2 && isset($payTable[$firstSymbol])) {
            $payout = 0;
            if($matchCount === 3) {
                $payout = $bet * $payTable[$firstSymbol]['three'];
                $wins[] = "Tres " . $firstSymbol;
            } elseif($matchCount === 2) {
                $payout = $bet * $payTable[$firstSymbol]['two'];
                $wins[] = "Dos " . $firstSymbol;
            }
            
            if($payout > 0) {
                $totalWin += $payout;
            }
        }
    }
}

// Construir mensaje
if($totalWin > 0) {
    $winMessage = implode(', ', array_slice($wins, 0, 3));
    if(count($wins) > 3) {
        $winMessage .= " ¡y más!";
    }
} else {
    $winMessage = "Sin combinaciones";
}

// Registrar apuesta
$resultado = ($totalWin > 0) ? 'ganada' : 'perdida';
$stmt = $conexion->prepare("INSERT INTO apuestas (id_usuario, id_juego, monto_apuesta, resultado) VALUES (?, 3, ?, ?)");
$stmt->bind_param("ids", $id_usuario, $bet, $resultado);
$stmt->execute();

echo json_encode([
    "symbols" => $matrix,
    "totalWin" => $totalWin,
    "message" => $winMessage
]);
?>
