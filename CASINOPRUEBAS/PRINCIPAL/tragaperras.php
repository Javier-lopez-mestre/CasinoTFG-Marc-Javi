<?php
session_start();
include("conexion.php");

if(!isset($_SESSION['id_usuario'])){
    header("Location: index.php");
    exit();
}

$id = $_SESSION['id_usuario'];

$stmt = $conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$saldo = $stmt->get_result()->fetch_assoc()['saldo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tragaperras Pro</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Arial Black', Arial, sans-serif;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: white;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

/* BOTÓN VOLVER */
#backBtn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 9999;
    padding: 12px 16px;
    border-radius: 8px;
    border: 2px solid #ffd700;
    background: #222;
    color: #ffd700;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    transition: 0.3s;
}

#backBtn:hover {
    background: #ffd700;
    color: #222;
}

/* CONTENEDOR PRINCIPAL */
.container {
    width: 90%;
    max-width: 600px;
    background: radial-gradient(circle at 30% 30%, #2a2a4e, #1a1a2e);
    border: 3px solid #ffd700;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 0 50px rgba(255, 215, 0, 0.5), 0 0 100px rgba(255, 215, 0, 0.2);
    animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 50px rgba(255, 215, 0, 0.5), 0 0 100px rgba(255, 215, 0, 0.2); }
    50% { box-shadow: 0 0 80px rgba(255, 215, 0, 0.8), 0 0 120px rgba(255, 215, 0, 0.4); }
}

/* HEADER */
.header {
    text-align: center;
    margin-bottom: 30px;
}

.header h1 {
    font-size: 48px;
    color: #ffd700;
    text-shadow: 0 0 10px #ffd700, 0 0 20px #ff6b00;
    margin-bottom: 10px;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.header p {
    color: #aaa;
    font-size: 14px;
}

/* INFO SALDO */
.info-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 25px;
}

.info-box {
    background: rgba(255, 215, 0, 0.1);
    border: 2px solid #ffd700;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    border-radius: 8px;
}

.info-box label {
    display: block;
    color: #aaa;
    font-size: 12px;
    margin-bottom: 5px;
    text-transform: uppercase;
    font-weight: bold;
}

.info-box .value {
    font-size: 28px;
    color: #ffd700;
    font-weight: bold;
}

/* RODILLOS */
.slot-machine {
    background: linear-gradient(145deg, #0a0a0a, #1a1a2e);
    border: 3px solid #ff6b00;
    border-radius: 15px;
    padding: 20px;
    margin: 25px 0;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.8), 0 0 20px rgba(255, 107, 0, 0.3);
}

.reels-container {
    display: flex;
    justify-content: space-around;
    gap: 10px;
    margin-bottom: 20px;
}

.reel {
    width: 100%;
    aspect-ratio: 1;
    background: linear-gradient(90deg, #000, #1a1a1a, #000);
    border: 2px solid #ffd700;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 45px;
    font-weight: bold;
    position: relative;
    overflow: hidden;
    box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.9);
    min-width: 50px;
}

.reel.spinning {
    animation: spin 0.1s linear;
}

@keyframes spin {
    0% { transform: translateY(-20px); }
    100% { transform: translateY(20px); }
}

.reel-symbol {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease-out;
}

/* BOTÓN JUGAR */
.controls {
    text-align: center;
}

.bet-amount {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.chip-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 2px solid #ffd700;
    background: radial-gradient(circle, #ff6b00, #cc5500);
    color: white;
    font-weight: bold;
    cursor: pointer;
    font-size: 14px;
    transition: 0.3s;
    box-shadow: 0 4px 10px rgba(255, 107, 0, 0.3);
}

.chip-btn:hover:not(:disabled) {
    transform: scale(1.1);
    box-shadow: 0 6px 15px rgba(255, 107, 0, 0.6);
}

.chip-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.custom-bet {
    display: flex;
    gap: 5px;
    justify-content: center;
    margin-bottom: 15px;
}

.custom-bet input {
    width: 80px;
    padding: 8px;
    border: 2px solid #ffd700;
    border-radius: 5px;
    background: #1a1a2e;
    color: #ffd700;
    font-weight: bold;
    text-align: center;
}

.custom-bet button {
    padding: 8px 15px;
    background: #ffd700;
    color: #000;
    border: none;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

.custom-bet button:hover {
    background: #ffed4e;
}

#spinBtn {
    width: 100%;
    padding: 15px;
    font-size: 20px;
    font-weight: bold;
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #000;
    border: 3px solid #000;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
}

#spinBtn:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(255, 215, 0, 0.6);
}

#spinBtn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* RESULTADO */
.result {
    background: rgba(255, 215, 0, 0.1);
    border: 2px solid #ffd700;
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
    text-align: center;
    display: none;
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.result.visible {
    display: block;
}

.result.win {
    border-color: #00ff00;
    background: rgba(0, 255, 0, 0.1);
}

.result.lose {
    border-color: #ff4444;
    background: rgba(255, 68, 68, 0.1);
}

.result.jackpot {
    border-color: #ff6b00;
    background: rgba(255, 107, 0, 0.2);
    animation: jackpotFlash 0.5s infinite;
}

@keyframes jackpotFlash {
    0%, 100% { box-shadow: 0 0 20px rgba(255, 107, 0, 0.5); }
    50% { box-shadow: 0 0 40px rgba(255, 107, 0, 1); }
}

.result h3 {
    font-size: 24px;
    margin-bottom: 10px;
}

.result .amount {
    font-size: 32px;
    font-weight: bold;
    color: #ffd700;
}

/* POPUP CONFIGURACIÓN */
#popup {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.popup-box {
    background: radial-gradient(circle at 30% 30%, #2a2a4e, #1a1a2e);
    border: 3px solid #ffd700;
    border-radius: 15px;
    padding: 30px;
    width: 90%;
    max-width: 350px;
    box-shadow: 0 0 50px rgba(255, 215, 0, 0.4);
}

.popup-box h3 {
    color: #ffd700;
    margin-bottom: 20px;
    text-align: center;
    font-size: 24px;
}

.popup-box input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: 2px solid #ffd700;
    border-radius: 8px;
    background: #1a1a2e;
    color: #ffd700;
    font-weight: bold;
    font-size: 16px;
}

.popup-box button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #000;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

.popup-box button:hover {
    transform: scale(1.02);
}

.saldo-real {
    text-align: center;
    color: #aaa;
    font-size: 13px;
    margin-bottom: 15px;
    padding: 10px;
    background: rgba(255, 215, 0, 0.05);
    border-radius: 5px;
}

/* RESPONSIVO */
@media (max-width: 480px) {
    .container {
        padding: 15px;
        max-width: 95%;
    }
    
    .header h1 {
        font-size: 36px;
    }
    
    .reel {
        font-size: 32px;
    }
    
    .reels-container {
        gap: 4px !important;
    }
    
    #spinBtn {
        padding: 12px;
        font-size: 16px;
    }
}
</style>
</head>

<body>

<button id="backBtn" onclick="exitGame()">🏠 Volver al Casino</button>

<!-- POPUP INICIAL -->
<div id="popup">
    <div class="popup-box">
        <h3>🎰 Tragaperras</h3>
        <p class="saldo-real">Saldo disponible: <strong><?= $saldo ?>€</strong></p>
        <input type="number" id="sessionBudget" placeholder="Presupuesto sesión (€)" min="1" max="<?= $saldo ?>">
        <button onclick="startSession()">Empezar a Jugar</button>
    </div>
</div>

<!-- JUEGO -->
<div class="container" style="display: none;" id="gameContainer">
    <div class="header">
        <h1>🎰 TRAGAPERRAS</h1>
        <p>¡Gira los rodillos y gana increíbles premios!</p>
    </div>

    <div class="info-container">
        <div class="info-box">
            <label>Créditos</label>
            <div class="value" id="credits">0€</div>
        </div>
        <div class="info-box">
            <label>Apuesta</label>
            <div class="value" id="bet">0€</div>
        </div>
    </div>

    <div class="slot-machine">
        <div class="reels-container" style="display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(3, 1fr); gap: 8px;">
            <div class="reel" id="reel0"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel1"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel2"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel3"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel4"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel5"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel6"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel7"><div class="reel-symbol">🍎</div></div>
            <div class="reel" id="reel8"><div class="reel-symbol">🍎</div></div>
        </div>
    </div>

    <div class="controls">
        <div class="bet-amount">
            <button class="chip-btn" onclick="setBet(0.2)">0.2€</button>
            <button class="chip-btn" onclick="setBet(0.3)">0.3€</button>
            <button class="chip-btn" onclick="setBet(0.5)">0.5€</button>
            <button class="chip-btn" onclick="setBet(0.6)">0.6€</button>
            <button class="chip-btn" onclick="setBet(1)">1€</button>
            <button class="chip-btn" onclick="setBet(2)">2€</button>
            <button class="chip-btn" onclick="setBet(5)">5€</button>
            <button class="chip-btn" onclick="setBet(10)">10€</button>
        </div>

        <div class="custom-bet">
            <input type="number" id="customBetInput" placeholder="Cantidad" min="0.2" step="0.1">
            <button onclick="setCustomBet()">Fijar</button>
        </div>

        <button id="spinBtn" onclick="spin()" disabled>GIRAR - Presiona para jugar</button>
    </div>

    <div class="result" id="result">
        <h3 id="resultTitle">Resultado</h3>
        <p id="resultMessage"></p>
        <div class="amount" id="resultAmount">+0€</div>
    </div>
</div>

<script>
// SÍMBOLOS DEL JUEGO - TRAGAPERRAS 3x3 REALISTA
const symbols = ['🍎', '🍊', '🍇', '⭐', '💎', '👑', '7️⃣'];
const symbolNames = {    '🍎': 'Manzana',    '�': 'Naranja',
    '🍇': 'Uva',
    '⭐': 'Estrella',
    '💎': 'Diamante',
    '👑': 'Corona',
    '7️⃣': 'Siete'
};

// TABLA DE PAGOS (solo 3 iguales)
const payTable = {
    '👑': 15,    // Corona - Mayor pago
    '💎': 12,    // Diamante
    '⭐': 9,     // Estrella
    '7️⃣': 7,     // Siete
    '🍇': 5,     // Uva
    '🍊': 3,     // Naranja
    '🍎': 2      // Manzana
};

// 9 LÍNEAS DE PAGO (como tragaperras real 3x3)
const payLines = [
    [0, 1, 2],      // Fila horizontal superior
    [3, 4, 5],      // Fila horizontal media
    [6, 7, 8],      // Fila horizontal inferior
    [0, 4, 8],      // Diagonal principal \
    [2, 4, 6]       // Diagonal inversa /
];

let credits = 0;
let currentBet = 0;
let isSpinning = false;
let sessionActive = false;
const realSaldo = <?= $saldo ?>;

// INICIAR SESIÓN
function startSession() {
    const budget = parseFloat(document.getElementById('sessionBudget').value);
    
    if (!budget || budget <= 0 || budget > realSaldo) {
        alert('Por favor, ingresa un presupuesto válido');
        return;
    }
    
    credits = budget;
    sessionActive = true;
    updateDisplay();
    
    document.getElementById('popup').style.display = 'none';
    document.getElementById('gameContainer').style.display = 'block';
    
    // Agregar listener para tecla Enter
    document.addEventListener('keypress', function(event) {
        if (event.key === 'Enter' && sessionActive && !isSpinning) {
            spin();
        }
    });
}

// Listener para Enter en el input inicial
document.getElementById('sessionBudget').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        startSession();
    }
});

// ACTUALIZAR DISPLAY
function updateDisplay() {
    document.getElementById('credits').textContent = credits.toFixed(2) + '€';
    document.getElementById('bet').textContent = currentBet.toFixed(2) + '€';
    
    const spinBtn = document.getElementById('spinBtn');
    spinBtn.disabled = !sessionActive || currentBet <= 0 || currentBet > credits;
}

// FIJAR APUESTA
function setBet(amount) {
    if (credits >= amount) {
        currentBet = amount;
        updateDisplay();
    } else {
        alert('No tienes suficientes créditos');
    }
}

// APUESTA PERSONALIZADA
function setCustomBet() {
    const amount = parseFloat(document.getElementById('customBetInput').value);
    if (amount && amount > 0 && amount <= credits) {
        currentBet = amount;
        updateDisplay();
    } else {
        alert('Ingresa una apuesta válida');
    }
}

// GIRAR
async function spin() {
    if (isSpinning || currentBet > credits) return;
    
    isSpinning = true;
    document.getElementById('spinBtn').disabled = true;
    
    // Descontar apuesta
    credits -= currentBet;
    updateDisplay();
    
    // ANIMAR RODILLOS 3x3
    const reels = [];
    for(let i = 0; i < 9; i++) {
        reels.push(document.getElementById('reel' + i));
    }
    
    // Animaciones con tiempos diferentes
    const spins = [];
    for(let i = 0; i < 9; i++) {
        spins.push(Math.floor(Math.random() * (15 + i*2)) + 15);
    }
    
    // Solicitar resultado al servidor
    const response = await fetch('resultado_tragaperras.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bet: currentBet })
    });
    
    const result = await response.json();
    const finalSymbols = result.symbols;
    
    // Animar cada posición
    for (let i = 0; i < 9; i++) {
        animateSpin(reels[i], spins[i], finalSymbols[i]);
    }
    
    // Esperar a que terminen las animaciones
    await new Promise(resolve => setTimeout(resolve, 2500));
    
    // Procesar resultado
    processResult(finalSymbols, result);
    
    isSpinning = false;
    document.getElementById('spinBtn').disabled = false;
}

// ANIMAR RODILLO
function animateSpin(reel, spinCount, finalSymbol) {
    const symbol = reel.querySelector('.reel-symbol');
    reel.classList.add('spinning');
    
    let current = 0;
    const interval = setInterval(() => {
        symbol.textContent = symbols[Math.floor(Math.random() * symbols.length)];
        current++;
        
        if (current >= spinCount) {
            clearInterval(interval);
            symbol.textContent = finalSymbol;
            reel.classList.remove('spinning');
        }
    }, 50);
}

// PROCESAR RESULTADO
function processResult(symbols, result) {
    let totalWin = result.totalWin || 0;
    let resultType = totalWin > 0 ? 'win' : 'lose';
    let message = result.message || 'Intenta de nuevo';
    
    // CALCULAR GANANCIA
    if (resultType !== 'lose') {
        credits += totalWin;
    }
    
    // MOSTRAR RESULTADO
    const resultDiv = document.getElementById('result');
    document.getElementById('resultTitle').textContent = resultType === 'lose' ? '😢 Perdiste' : '🎉 ¡Ganaste!';
    document.getElementById('resultMessage').textContent = message;
    document.getElementById('resultAmount').textContent = (resultType === 'lose' ? '-' : '+') + totalWin.toFixed(2) + '€';
    
    resultDiv.className = 'result visible ' + resultType;
    
    // Limpiar resultado después de 3 segundos
    setTimeout(() => {
        resultDiv.classList.remove('visible');
    }, 3000);
    
    updateDisplay();
    
    // Guardar cambio de saldo
    saveSaldo();
}

// GUARDAR SALDO
async function saveSaldo() {
    await fetch('resultado_tragaperras.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ saveSaldo: true, newSaldo: credits })
    });
}

// SALIR DEL JUEGO
function exitGame() {
    if (!sessionActive) {
        window.location.href = 'principal.php';
        return;
    }
    
    if (confirm('¿Salir del juego? Se guardará tu saldo actual.')) {
        saveSaldo();
        setTimeout(() => {
            window.location.href = 'principal.php';
        }, 500);
    }
}
</script>

</body>
</html>
