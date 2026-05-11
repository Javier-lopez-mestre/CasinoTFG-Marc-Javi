<?php
session_start();
include("conexion.php");

if(!isset($_SESSION['id_usuario'])){
    header("Location:index.php");
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
<title>Casino Live Blackjack</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body{
    background:url("img/mesablackjack.png") center/cover no-repeat;
}

.deal-anim{animation:deal .3s ease;}
@keyframes deal{from{transform:translateY(-40px); opacity:0;} to{transform:translateY(0); opacity:1;}}

.score {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.9);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 14px;
    min-width: 40px;
    text-align: center;
    border: 2px solid;
}

.session-counter {
    background: linear-gradient(45deg, #10b981, #059669);
    box-shadow: 0 10px 30px rgba(16,185,129,0.4);
}

.stats-btn {
    background: rgba(0,0,0,0.7);
    border: 2px solid #fbbf24;
    transition: all 0.3s;
}
.stats-btn:hover {
    background: #fbbf24;
    color: black;
    box-shadow: 0 0 20px rgba(251,191,36,0.6);
}

.modal-overlay {
    background: rgba(0,0,0,0.9);
    backdrop-filter: blur(10px);
}

#dropZone.bet-active {
    border-color: #22c55e;
    box-shadow: 0 0 40px rgba(34,197,94,0.8);
}
#dropZone.bet-max {
    border-color: #ef4444;
    box-shadow: 0 0 40px rgba(239,68,68,0.8);
}

#gameResult.fade-out {
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
}

#gameOverScreen {
    background: rgba(0,0,0,0.95);
    backdrop-filter: blur(20px);
}

#startScreen {
    animation: modalSlideIn 0.5s ease-out;
}
@keyframes modalSlideIn {
    from { opacity: 0; transform: scale(0.8) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* NUEVO LAYOUT */
.game-container {
    min-height: calc(100vh - 5rem);
    display: flex;
    flex-direction: column;
    gap: 2rem;
    padding: 2rem 1rem;
}

.game-board {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 4rem;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

.dealer-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.player-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.center-section {
    flex: 0 0 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
}

.cards-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-height: 200px;
    justify-content: center;
}

.chips-container {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    max-width: 400px;
}

.bottom-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
    padding-top: 2rem;
    border-top: 3px solid rgba(255,255,255,0.2);
}

@media(max-width:1024px){
    .game-board { 
        flex-direction: column; 
        gap: 2rem; 
    }
    .dealer-section, .player-section {
        align-items: center;
    }
    .center-section { order: 3; }
    .bottom-section { order: 4; }
}

@media(max-width:768px){
    .header-buttons {flex-direction: column; gap: 0.5rem;}
    .chips-container { gap: 0.5rem; }
    .chip { width: 50px; height: 50px; font-size: 12px !important; }
}

.card {
    width: 90px;
    height: 130px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.5);
}
</style>
</head>

<body class="overflow-hidden text-white">

<!-- OVERLAY -->
<div class="fixed inset-0 bg-black/60"></div>

<!-- HEADER -->
<header class="fixed top-0 left-0 w-full h-20 bg-black/70 backdrop-blur-md px-4 z-50 flex flex-col sm:flex-row items-center justify-between gap-2 pt-2">
    <div class="flex items-center gap-2">
        <button onclick="goHome()" class="bg-black border border-white px-4 py-2 rounded-xl hover:bg-white hover:text-black transition font-bold">
            🏠 Principal
        </button>
        <div id="sessionCounter" class="session-counter px-4 py-2 rounded-2xl text-lg font-black hidden">
            ⏱️ <span id="sessionTime">00:00</span> | 💰 <span id="sessionBankroll">$0</span> | <span id="sessionBetLimit">Límite: $0</span>
        </div>
    </div>
    <div class="flex items-center gap-2 header-buttons">
        <button id="endSessionBtn" onclick="endSession()" 
                class="bg-red-500 hover:bg-red-400 px-4 py-2 rounded-xl font-bold text-lg transition shadow-lg hidden">
            TERMINAR SESIÓN
        </button>
        <button id="statsBtn" onclick="toggleStats()" 
                class="stats-btn px-3 py-2 rounded-xl font-bold text-sm transition shadow-lg hidden">
            📊 STATS
        </button>
    </div>
    <div class="bg-yellow-400 text-black px-5 py-2 rounded-2xl font-black text-lg shadow-2xl">
        💰 <span id="saldoHeader"><?= number_format($saldo,2) ?></span>$
    </div>
</header>

<!-- MODALES -->
<div id="casinoAlert" class="hidden fixed top-24 left-1/2 -translate-x-1/2 z-[99999] bg-red-500 text-white px-6 py-3 rounded-2xl text-xl font-bold shadow-2xl"></div>

<div id="gameOverScreen" class="fixed inset-0 z-[99998] hidden flex items-center justify-center p-8">
    <div class="bg-red-900/95 border-8 border-red-500 rounded-3xl p-12 w-[95%] max-w-2xl text-center shadow-2xl max-h-[90vh] flex flex-col justify-center">
        <div class="text-6xl font-black mb-8 animate-bounce">💀</div>
        <h2 class="text-5xl font-black text-red-400 mb-6 tracking-wide">¡HAS PERDIDO!</h2>
        <p class="text-2xl text-red-200 mb-12 font-semibold">Te pasaste de 21 puntos</p>
        <button onclick="restartGame()" class="bg-green-500 hover:bg-green-400 px-12 py-6 rounded-3xl text-3xl font-bold transition-all duration-300 shadow-2xl w-full mx-auto transform hover:scale-105">
            🔄 JUGAR DE NUEVO
        </button>
    </div>
</div>

<div id="statsModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
    <div class="modal-overlay absolute inset-0" onclick="toggleStats()"></div>
    <div class="bg-zinc-900 border-4 border-yellow-500 rounded-3xl p-8 w-full max-w-md max-h-[80vh] overflow-y-auto relative shadow-2xl">
        <button onclick="toggleStats()" class="absolute top-4 right-4 text-3xl font-bold text-white hover:text-yellow-400">×</button>
        <h2 class="text-3xl font-black text-yellow-400 text-center mb-6">📊 ESTADÍSTICAS SESIÓN</h2>
        <div class="space-y-4 text-lg">
            <div class="grid grid-cols-2 gap-4 bg-zinc-800 p-4 rounded-2xl">
                <div class="text-center">
                    <div class="text-3xl font-black text-green-400" id="winsCount">0</div>
                    <div class="text-zinc-400">Ganadas</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-black text-red-500" id="lossesCount">0</div>
                    <div class="text-zinc-400">Perdidas</div>
                </div>
            </div>
            <div>
                <div class="text-xl font-black mb-2">Total Apostado:</div>
                <div class="text-3xl font-black text-yellow-400" id="totalBets">$0</div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-2xl font-black text-green-400" id="totalWins">$0</div>
                    <div class="text-zinc-400">Ganancias</div>
                </div>
                <div>
                    <div class="text-2xl font-black text-red-500" id="totalLosses">$0</div>
                    <div class="text-zinc-400">Pérdidas</div>
                </div>
            </div>
            <div class="text-center pt-4 border-t border-zinc-700">
                <div class="text-3xl font-black" id="netProfit">$0</div>
                <div class="text-zinc-400 text-sm">Balance Neto</div>
            </div>
            <div class="pt-4 space-y-2">
                <button onclick="resetStats()" class="w-full bg-orange-500 hover:bg-orange-400 py-3 rounded-xl font-bold text-lg transition shadow-lg">
                    🔄 Reiniciar Estadísticas
                </button>
            </div>
        </div>
    </div>
</div>

<div id="startScreen" class="fixed inset-0 bg-black/95 z-[99999] flex items-center justify-center">
    <div class="bg-zinc-900 border-4 border-yellow-400 rounded-3xl p-8 w-[90%] max-w-md text-center shadow-2xl animate-pulse">
        <div class="text-5xl font-black text-yellow-400 mb-6">🎰 CASINO LIVE</div>
        <p class="text-zinc-400 mb-2 text-lg">Saldo disponible:</p>
        <div class="text-4xl font-black mb-6 text-green-400"><?= number_format($saldo,2) ?>$</div>
        
        <input id="bankroll" type="number" placeholder="Dinero para la sesión" min="1" max="<?= $saldo ?>"
               class="w-full bg-zinc-800 rounded-xl p-4 mb-4 text-white outline-none border-2 border-zinc-700 focus:border-yellow-400 text-2xl text-center font-bold">
        
        <select id="time" class="w-full bg-zinc-800 rounded-xl p-4 mb-6 text-white border-2 border-zinc-700 text-xl font-bold">
            <option value="1800">⏱️ 30 minutos</option>
            <option value="3600">⏱️ 1 hora</option>
            <option value="7200">⏱️ 2 horas</option>
        </select>
        
        <button onclick="confirmSession()" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-400 hover:to-green-500 py-5 rounded-2xl text-2xl font-black transition-all duration-300 shadow-2xl border-4 border-green-400 transform hover:scale-105">
            🚀 COMENZAR SESIÓN
        </button>
        
        <p id="error" class="text-red-500 mt-4 font-bold text-lg hidden"></p>
    </div>
</div>

<!-- GAME TABLE -->
<main id="gameTable" class="relative z-10 h-screen pt-24 hidden">
    <div class="game-container">
        
        <!-- SCORES TOP -->
        <div class="flex justify-between px-8 text-xl font-bold">
            <div id="dealerScoreTop">🃏 CRUPIER: ?</div>
            <div id="playerScoreTop">👤 JUGADOR: ?</div>
        </div>

        <!-- GAME BOARD -->
        <div class="game-board">
            
            <!-- DEALER - IZQUIERDA -->
            <div class="dealer-section">
                <h2 class="text-2xl font-black mb-4 text-left">🃏 CRUPIER</h2>
                <div id="dealerCards" class="cards-container"></div>
            </div>

            <!-- CENTRO - REDONDEL + FICHAS -->
            <div class="center-section">
                <div id="gameResult" class="text-3xl font-black h-20 mb-4 text-center"></div>
                
                <!-- REDONDEL DE APUESTA -->
                <div id="dropZone" class="w-72 h-72 rounded-full border-8 border-dashed border-green-400 flex flex-col items-center justify-center text-center shadow-[0_0_40px_rgba(34,197,94,0.5)] transition-all duration-300 hover:scale-105 cursor-pointer p-8">
                    <div class="text-2xl font-black mb-4">APUESTA</div>
                    <div class="text-5xl font-black text-yellow-400 mb-4">
                        $<span id="betAmount">0</span>
                    </div>
                    <div id="betLimit" class="text-lg text-zinc-400 font-bold">Límite: $999</div>
                </div>

                <!-- FICHAS -->
                <div class="chips-container">
                    <div draggable="true" data-value="0.5" class="chip bg-gray-200 text-black w-20 h-20 rounded-full flex items-center justify-center font-black text-xl cursor-grab shadow-2xl border-4 border-white hover:scale-110 transition">$0.5</div>
                    <div draggable="true" data-value="1" class="chip bg-white text-black w-20 h-20 rounded-full flex items-center justify-center font-black text-xl cursor-grab shadow-2xl border-4 border-gray-300 hover:scale-110 transition">$1</div>
                    <div draggable="true" data-value="2" class="chip bg-yellow-300 text-black w-20 h-20 rounded-full flex items-center justify-center font-black text-xl cursor-grab shadow-2xl border-4 border-yellow-500 hover:scale-110 transition">$2</div>
                    <div draggable="true" data-value="5" class="chip bg-green-500 text-white w-20 h-20 rounded-full flex items-center justify-center font-bold text-xl cursor-grab shadow-2xl border-4 border-green-300 hover:scale-110 transition">$5</div>
                    <div draggable="true" data-value="10" class="chip bg-sky-400 text-white w-20 h-20 rounded-full flex items-center justify-center font-black text-xl cursor-grab shadow-2xl border-4 border-sky-200 hover:scale-110 transition">$10</div>
                    <div draggable="true" data-value="50" class="chip bg-pink-500 text-white w-20 h-20 rounded-full flex items-center justify-center font-black text-xl cursor-grab shadow-2xl border-4 border-pink-200 hover:scale-110 transition">$50</div>
                    <div draggable="true" data-value="100" class="chip bg-red-500 text-white w-20 h-20 rounded-full flex items-center justify-center font-black text-xl cursor-grab shadow-2xl border-4 border-red-200 hover:scale-110 transition">$100</div>
                </div>
            </div>

            <!-- JUGADOR - DERECHA -->
            <div class="player-section">
                <h2 class="text-2xl font-black mb-4 text-right">👤 JUGADOR</h2>
                <div id="playerCards" class="cards-container"></div>
            </div>

        </div>

        <!-- ABAJO - RESULTADO + BOTONES -->
        <div class="bottom-section">
            <div id="bottomResult" class="text-4xl font-black h-16 mb-4"></div>
            
            <div class="flex gap-4">
                <button id="dealBtn" onclick="deal()" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-400 hover:to-blue-500 px-16 py-6 rounded-3xl text-3xl font-black shadow-2xl transition-all duration-300 text-white disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                    🎲 REPARTIR
                </button>
                <button id="hitBtn" onclick="hit()" class="bg-gradient-to-r from-yellow-500 to-yellow-400 hover:from-yellow-400 hover:to-yellow-300 px-12 py-6 rounded-3xl text-2xl font-black shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all hidden">HIT ➕</button>
                <button id="standBtn" onclick="stand()" class="bg-gradient-to-r from-red-500 to-red-400 hover:from-red-400 hover:to-red-300 px-12 py-6 rounded-3xl text-2xl font-black shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all hidden">STAND 🛑</button>
            </div>
        </div>

    </div>
</main>

<script>
let saldo = <?= $saldo ?>;
let sessionActive = false;
let sessionBankroll = 0;
let sessionBetLimit = 0;
let sessionTimeLimit = 0;
let sessionTimer = null;
let sessionStartTime = 0;

let bet = 0;
let player = [];
let dealer = [];
let gameActive = false;
let gameFinished = false;

let stats = {
    wins: 0,
    losses: 0,
    totalBets: 0,
    totalWins: 0,
    totalLosses: 0
};

// ==================== SESSION MANAGEMENT ====================
function confirmSession() {
    let errorEl = document.getElementById("error");
    errorEl.classList.add("hidden");
    
    let bankrollInput = parseFloat(document.getElementById("bankroll").value);
    let timeInput = parseInt(document.getElementById("time").value);
    
    if(isNaN(bankrollInput) || bankrollInput <= 0) {
        showAlert("¡Ingresa un monto válido!");
        return;
    }
    
    if(bankrollInput > saldo) {
        showAlert("¡Saldo insuficiente!");
        return;
    }
    
    sessionBankroll = bankrollInput;
    sessionBetLimit = bankrollInput;
    sessionTimeLimit = timeInput * 1000;
    sessionActive = true;
    sessionStartTime = Date.now();
    
    resetStats();
    
    document.getElementById("startScreen").style.display = "none";
    document.getElementById("gameTable").classList.remove("hidden");
    document.getElementById("sessionCounter").classList.remove("hidden");
    document.getElementById("endSessionBtn").classList.remove("hidden");
    document.getElementById("statsBtn").classList.remove("hidden");
    
    sessionTimer = setInterval(updateSessionTimer, 1000);
    updateSessionDisplay();
    resetGameState();
}

function endSession() {
    if(gameActive || gameFinished) {
        showAlert("⏳ Termina la mano primero");
        return;
    }
    
    sessionActive = false;
    clearInterval(sessionTimer);
    
    document.getElementById("startScreen").style.display = "flex";
    document.getElementById("gameTable").classList.add("hidden");
    document.getElementById("sessionCounter").classList.add("hidden");
    document.getElementById("endSessionBtn").classList.add("hidden");
    document.getElementById("statsBtn").classList.add("hidden");
    
    resetGameState();
}

function updateSessionTimer() {
    let elapsed = Date.now() - sessionStartTime;
    let remaining = Math.max(0, sessionTimeLimit - elapsed);
    
    if(remaining <= 0) {
        endSession();
        showAlert("⏰ ¡Sesión finalizada por tiempo!");
        return;
    }
    
    let minutes = Math.floor(remaining / 60000);
    let seconds = Math.floor((remaining % 60000) / 1000);
    document.getElementById("sessionTime").textContent = 
        `${minutes.toString().padStart(2,'0')}:${seconds.toString().padStart(2,'0')}`;
}

function updateSessionDisplay() {
    document.getElementById("sessionBankroll").textContent = `$${sessionBankroll.toFixed(2)}`;
    document.getElementById("sessionBetLimit").textContent = `Límite: $${sessionBetLimit.toFixed(2)}`;
}

// ==================== BET SYSTEM ====================
function updateBetDisplay() {
    document.getElementById("betAmount").textContent = bet.toFixed(2);
    document.getElementById("betLimit").textContent = `Límite: $${sessionBetLimit.toFixed(2)}`;
    
    const dropZone = document.getElementById("dropZone");
    dropZone.classList.remove("bet-active", "bet-max");
    
    if(bet > 0) dropZone.classList.add("bet-active");
    if(bet >= sessionBetLimit) dropZone.classList.add("bet-max");
}

function resetBet() {
    bet = 0;
    updateBetDisplay();
}

// ==================== DRAG & DROP ====================
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".chip").forEach(chip => {
        chip.addEventListener("dragstart", e => {
            e.dataTransfer.setData("value", chip.dataset.value);
        });
    });
    
    const dropZone = document.getElementById("dropZone");
    dropZone.addEventListener("dragover", e => e.preventDefault());
    dropZone.addEventListener("drop", handleDrop);
});

function handleDrop(e) {
    e.preventDefault();
    
    if(!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }
    
    if(gameActive || gameFinished) {
        showAlert("⏳ Termina la mano actual");
        return;
    }
    
    let value = parseFloat(e.dataTransfer.getData("value"));
    let newBet = bet + value;
    
    if(newBet > sessionBetLimit) {
        showAlert(`💰 Máximo $${sessionBetLimit.toFixed(2)}`);
        return;
    }
    
    bet = newBet;
    updateBetDisplay();
}

// ==================== STATS ====================
function updateStats(result, betAmount, winAmount = 0) {
    stats.totalBets += betAmount;
    if(result === 'win') {
        stats.wins++;
        stats.totalWins += winAmount;
    } else if(result === 'loss') {
        stats.losses++;
        stats.totalLosses += betAmount;
    }
    updateStatsDisplay();
}

function updateStatsDisplay() {
    document.getElementById("winsCount").textContent = stats.wins;
    document.getElementById("lossesCount").textContent = stats.losses;
    document.getElementById("totalBets").textContent = `$${stats.totalBets.toFixed(2)}`;
    document.getElementById("totalWins").textContent = `$${stats.totalWins.toFixed(2)}`;
    document.getElementById("totalLosses").textContent = `$${stats.totalLosses.toFixed(2)}`;
    
    let net = stats.totalWins - stats.totalLosses;
    document.getElementById("netProfit").textContent = `$${net.toFixed(2)}`;
    document.getElementById("netProfit").className = `text-3xl font-black ${net >= 0 ? 'text-green-400' : 'text-red-500'}`;
}

function toggleStats() {
    document.getElementById("statsModal").classList.toggle("hidden");
}

function resetStats() {
    stats = {wins: 0, losses: 0, totalBets: 0, totalWins: 0, totalLosses: 0};
    updateStatsDisplay();
}

// ==================== BLACKJACK LOGIC ====================
function getCardValue(card) {
    const value = card.split('-')[0];
    if (value === 'A') return 11;
    if (['J', 'Q', 'K'].includes(value)) return 10;
    return parseInt(value);
}

function getHandValue(hand) {
    let value = 0;
    let aces = 0;
    for (let card of hand) {
        let cardValue = getCardValue(card);
        if (cardValue === 11) aces++;
        value += cardValue;
    }
    while (value > 21 && aces > 0) {
        value -= 10;
        aces--;
    }
    return value;
}

function isBlackjack(hand) {
    return hand.length === 2 && getHandValue(hand) === 21;
}

function card() {
    let values = ["2","3","4","5","6","7","8","9","10","J","Q","K","A"];
    let suits = ["corazones","diamantes","trebol","picas"];
    return `${values[Math.floor(Math.random()*values.length)]}-${suits[Math.floor(Math.random()*suits.length)]}.png`;
}

function createCard(src) {
    return `
        <div class="card deal-anim">
            <img src="img/cards/${src}" class="w-full h-full object-cover rounded-lg">
        </div>
    `;
}

// ==================== GAME RENDER ====================
function updateScores() {
    let playerValue = getHandValue(player);
    let dealerValue = getHandValue(dealer);
    
    document.getElementById("playerScoreTop").textContent = `👤 JUGADOR: ${playerValue}`;
    document.getElementById("dealerScoreTop").textContent = `🃏 CRUPIER: ${dealerValue}`;
}

function renderCards() {
    // JUGADOR
    document.getElementById("playerCards").innerHTML = player.map(c => createCard(c)).join("");
    
    // DEALER 
    document.getElementById("dealerCards").innerHTML = dealer.map(c => createCard(c)).join("");
    
    updateScores();
}

function resetGameState() {
    player = [];
    dealer = [];
    gameActive = false;
    gameFinished = false;
    bet = 0;
    
    document.getElementById("playerCards").innerHTML = "";
    document.getElementById("dealerCards").innerHTML = "";
    document.getElementById("gameResult").innerHTML = "";
    document.getElementById("bottomResult").innerHTML = "";
    
    document.getElementById("dealBtn").disabled = !sessionActive;
    document.getElementById("hitBtn").disabled = true;
    document.getElementById("hitBtn").classList.add("hidden");
    document.getElementById("standBtn").disabled = true;
    document.getElementById("standBtn").classList.add("hidden");
    
    updateBetDisplay();
    updateSaldo();
    document.getElementById("playerScoreTop").textContent = "👤 JUGADOR: ?";
    document.getElementById("dealerScoreTop").textContent = "🃏 CRUPIER: ?";
}

// ==================== MAIN GAME FUNCTIONS ====================
function deal() {
    if(!sessionActive || bet === 0 || bet > sessionBetLimit || gameActive || gameFinished) {
        showAlert("❌ Verifica apuesta y estado del juego");
        return;
    }
    
    gameActive = true;
    gameFinished = false;
    saldo -= bet;
    
    player = [card(), card()];
    dealer = [card(), card()];
    
    document.getElementById("dealBtn").disabled = true;
    document.getElementById("hitBtn").disabled = false;
    document.getElementById("hitBtn").classList.remove("hidden");
    document.getElementById("standBtn").disabled = false;
    document.getElementById("standBtn").classList.remove("hidden");
    
    renderCards();
    updateSaldo();
    
    setTimeout(() => {
        if (isBlackjack(player)) {
            dealerPlay();
            return;
        }
    }, 1000);
}

function hit() {
    if(!gameActive) return;
    
    player.push(card());
    renderCards();
    
    if (getHandValue(player) > 21) {
        finishGame();
    }
}

function stand() {
    if(!gameActive) return;
    
    dealerPlay();
}

function dealerPlay() {
    while (getHandValue(dealer) < 17) {
        dealer.push(card());
        renderCards();
    }
    setTimeout(() => finishGame(), 800);
}

function finishGame() {
    gameActive = false;
    gameFinished = true;
    
    document.getElementById("hitBtn").disabled = true;
    document.getElementById("standBtn").disabled = true;
    document.getElementById("dealBtn").disabled = true;
    
    let playerValue = getHandValue(player);
    let dealerValue = getHandValue(dealer);
    
    let finalResult = '';
    let isWin = false;
    
    if (playerValue > 21) {
        finalResult = `💀 PASASTE (${playerValue})`;
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "text-3xl font-black h-20 mb-4 text-red-500 animate-pulse";
        document.getElementById("bottomResult").innerHTML = "¡PERDISTE!";
        document.getElementById("bottomResult").className = "text-4xl font-black h-16 mb-4 text-red-500 animate-pulse";
    } else if (dealerValue > 21) {
        finalResult = `🎉 DEALER PASÓ (${dealerValue})!`;
        isWin = true;
    } else if (playerValue > dealerValue) {
        finalResult = `🎉 ¡VICTORIA! ${playerValue} vs ${dealerValue}`;
        isWin = true;
    } else if (playerValue < dealerValue) {
        finalResult = `💀 DERROTA ${playerValue} vs ${dealerValue}`;
    } else {
        finalResult = `🤝 EMPATE ${playerValue}`;
        saldo += bet;
        isWin = 'tie';
    }
    
    if (isWin === true) {
        let won = bet * 2;
        saldo += won;
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "text-3xl font-black h-20 mb-4 text-green-400 animate-pulse";
        document.getElementById("bottomResult").innerHTML = "¡GANASTE!";
        document.getElementById("bottomResult").className = "text-4xl font-black h-16 mb-4 text-green-400 animate-pulse";
        updateStats('win', bet, won);
    } else if (isWin !== 'tie') {
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "text-3xl font-black h-20 mb-4 text-red-500 animate-pulse";
        document.getElementById("bottomResult").innerHTML = "¡PERDISTE!";
        document.getElementById("bottomResult").className = "text-4xl font-black h-16 mb-4 text-red-500 animate-pulse";
        updateStats('loss', bet);
    } else {
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "text-3xl font-black h-20 mb-4 text-yellow-400 animate-pulse";
        document.getElementById("bottomResult").innerHTML = "¡EMPATE!";
        document.getElementById("bottomResult").className = "text-4xl font-black h-16 mb-4 text-yellow-400 animate-pulse";
    }
    
    updateSaldo();
    
    setTimeout(() => {
        resetGameState();
        showAlert("✅ ¡Listo para nueva apuesta!");
    }, 3000);
}

function restartGame() {
    resetGameState();
    document.getElementById("gameOverScreen").classList.add("hidden");
}

function showAlert(msg) {
    let alertBox = document.getElementById("casinoAlert");
    alertBox.innerText = msg;
    alertBox.classList.remove("hidden");
    setTimeout(() => alertBox.classList.add("hidden"), 3000);
}

function updateSaldo() {
    document.getElementById("saldoHeader").textContent = saldo.toFixed(2);
}

function goHome() {
    if(gameActive || gameFinished) {
        showAlert("⏳ Termina la mano primero");
        return;
    }
    saveSaldo();
    window.location.href = "principal.php";
}

function saveSaldo() {
    fetch("resultado_blackjack.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({saldo: saldo})
    }).catch(err => console.log("Save error:", err));
}

window.addEventListener("beforeunload", saveSaldo);
</script>

</body>
</html>