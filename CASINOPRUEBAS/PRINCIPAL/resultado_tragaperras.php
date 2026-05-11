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
    
    // Guardar saldo sin límites de ganancia máxima
    $stmt = $conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario=?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if($newSaldo > 0) {
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
$symbols = ['🍎', '🍊', '🍇', '⭐', '💎', '👑', '7️⃣'];

// Pesos (frecuencia de aparición)
$weights = [
    '�' => 45,   // Muy común, poco paga
    '🍊' => 45,   // Muy común
    '🍇' => 35,   // Común
    '7️⃣' => 30,   // Común
    '⭐' => 15,   // Menos común
    '💎' => 8,    // Raro
    '👑' => 5     // Muy raro, paga mucho
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

// DEFINIR LÍNEAS DE PAGO (solo filas horizontales + diagonales en X)
$payLines = [
    [0, 1, 2],      // Fila horizontal superior
    [3, 4, 5],      // Fila horizontal media
    [6, 7, 8],      // Fila horizontal inferior
    [0, 4, 8],      // Diagonal principal \
    [2, 4, 6]       // Diagonal inversa /
];

// TABLA DE PAGOS
$payTable = [
    '👑' => ['three' => 15, 'two' => 0],
    '💎' => ['three' => 12, 'two' => 0],
    '⭐' => ['three' => 9, 'two' => 0],
    '7️⃣' => ['three' => 7, 'two' => 0],
    '🍇' => ['three' => 5, 'two' => 0],
    '🍊' => ['three' => 3, 'two' => 0],
    '🍎' => ['three' => 2, 'two' => 0]
];

// PROCESAR LÍNEAS DE PAGO
$totalWin = 0;
$winMessage = '';
$wins = [];

foreach($payLines as $line) {
    // Obtener símbolos en esta línea (3 posiciones cada línea)
    $lineSymbols = [];
    foreach($line as $pos) {
        if($pos < 9) {
            $lineSymbols[] = $matrix[$pos];
        }
    }
    
    // Verificar si TODOS los 3 símbolos son iguales
    if(count($lineSymbols) === 3 && $lineSymbols[0] === $lineSymbols[1] && $lineSymbols[1] === $lineSymbols[2]) {
        $firstSymbol = $lineSymbols[0];
        
        // Solo pagar si hay 3 iguales (no se paga por 2)
        if(isset($payTable[$firstSymbol])) {
            $payout = $bet * $payTable[$firstSymbol]['three'];
            $wins[] = "Tres " . $firstSymbol;
            
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
