<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location:index.php");
    exit();
}

$id = (int) $_SESSION['id_usuario'];

$stmt = $conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();
$saldo = (float) ($row['saldo'] ?? 0);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Casino Live Blackjack</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body {
    background: url("img/mesablackjack.png") center/cover no-repeat;
}

.deal-anim {
    animation: deal .3s ease;
}

@keyframes deal {
    from {
        transform: translateY(-40px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
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

.dealer-section,
.player-section {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.dealer-section {
    align-items: flex-start;
}

.player-section {
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

.card {
    width: 90px;
    height: 130px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.5);
}

@media(max-width:1024px) {
    .game-board {
        flex-direction: column;
        gap: 2rem;
    }

    .dealer-section,
    .player-section {
        align-items: center;
    }

    .center-section {
        order: 3;
    }

    .bottom-section {
        order: 4;
    }
}

@media(max-width:768px) {
    .header-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }

    .chips-container {
        gap: 0.5rem;
    }

    .chip {
        width: 50px;
        height: 50px;
        font-size: 12px !important;
    }
}
</style>
</head>

<body class="overflow-hidden text-white">

<div class="fixed inset-0 bg-black/60"></div>

<header class="fixed top-0 left-0 w-full h-20 bg-black/70 backdrop-blur-md px-4 z-50 flex flex-col sm:flex-row items-center justify-between gap-2 pt-2">
    <div class="flex items-center gap-2">
        <button onclick="goHome()" class="bg-black border border-white px-4 py-2 rounded-xl hover:bg-white hover:text-black transition font-bold">
            🏠 Principal
        </button>

        <div id="sessionCounter" class="session-counter px-4 py-2 rounded-2xl text-lg font-black hidden">
            ⏱️ <span id="sessionTime">00:00</span> |
            💰 Sesión: <span id="sessionBankroll">$0</span> |
            <span id="sessionBetLimit">Disponible: $0</span>
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
        💰 Cuenta: <span id="saldoHeader"><?= number_format($saldo, 2) ?></span>$
    </div>
</header>

<div id="casinoAlert" class="hidden fixed top-24 left-1/2 -translate-x-1/2 z-[99999] bg-red-500 text-white px-6 py-3 rounded-2xl text-xl font-bold shadow-2xl"></div>

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

        <p class="text-zinc-400 mb-2 text-lg">Saldo disponible en cuenta:</p>
        <div class="text-4xl font-black mb-6 text-green-400"><?= number_format($saldo, 2) ?>$</div>
        
        <input id="bankroll" type="number" placeholder="Dinero para la sesión" min="1" max="<?= htmlspecialchars((string) $saldo) ?>"
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

<main id="gameTable" class="relative z-10 h-screen pt-24 hidden">
    <div class="game-container">
        <div class="flex justify-between px-8 text-xl font-bold">
            <div id="dealerScoreTop">🃏 CRUPIER: ?</div>
            <div id="playerScoreTop">👤 JUGADOR: ?</div>
        </div>

        <div class="game-board">
            <div class="dealer-section">
                <h2 class="text-2xl font-black mb-4 text-left">🃏 CRUPIER</h2>
                <div id="dealerCards" class="cards-container"></div>
            </div>

            <div class="center-section">
                <div id="gameResult" class="text-3xl font-black h-20 mb-4 text-center"></div>
                
                <div id="dropZone" class="w-72 h-72 rounded-full border-8 border-dashed border-green-400 flex flex-col items-center justify-center text-center shadow-[0_0_40px_rgba(34,197,94,0.5)] transition-all duration-300 hover:scale-105 cursor-pointer p-8">
                    <div class="text-2xl font-black mb-4">APUESTA</div>

                    <div class="text-5xl font-black text-yellow-400 mb-4">
                        $<span id="betAmount">0</span>
                    </div>

                    <div id="betLimit" class="text-lg text-zinc-400 font-bold">Disponible: $0</div>
                </div>

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

            <div class="player-section">
                <h2 class="text-2xl font-black mb-4 text-right">👤 JUGADOR</h2>
                <div id="playerCards" class="cards-container"></div>
            </div>
        </div>

        <div class="bottom-section">
            <div id="bottomResult" class="text-4xl font-black h-16 mb-4"></div>
            
            <div class="flex gap-4">
                <button id="dealBtn" onclick="deal()" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-400 hover:to-blue-500 px-16 py-6 rounded-3xl text-3xl font-black shadow-2xl transition-all duration-300 text-white disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                    🎲 REPARTIR
                </button>

                <button id="hitBtn" onclick="hit()" class="bg-gradient-to-r from-yellow-500 to-yellow-400 hover:from-yellow-400 hover:to-yellow-300 px-12 py-6 rounded-3xl text-2xl font-black shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all hidden">
                    HIT ➕
                </button>

                <button id="standBtn" onclick="stand()" class="bg-gradient-to-r from-red-500 to-red-400 hover:from-red-400 hover:to-red-300 px-12 py-6 rounded-3xl text-2xl font-black shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all hidden">
                    STAND 🛑
                </button>
            </div>
        </div>
    </div>
</main>

<script>
let saldo = <?= (float) $saldo ?>;

let sessionActive = false;
let sessionBankroll = 0;
let sessionBetLimit = 0;
let sessionTimer = null;
let sessionExpiresAt = 0;
let sessionExpiredAlertShown = false;

let bet = 0;
let player = [];
let dealer = [];
let gameActive = false;
let gameFinished = false;

let stats = {
    wins: 0,
    losses: 0,
    ties: 0,
    totalBets: 0,
    totalWins: 0,
    totalLosses: 0
};

async function gameSessionApi(action, data = {}) {
    const response = await fetch(`api_sesion_juego.php?action=${action}`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    });

    return await response.json();
}

function applySessionState(data) {
    if (!data.ok) {
        showAlert(data.message || "Error de sesión");
        return;
    }

    saldo = Number(data.saldo_cuenta || 0);
    updateSaldo();

    if (!data.active) {
        sessionActive = false;
        sessionBankroll = 0;
        sessionBetLimit = 0;
        sessionExpiresAt = 0;
        sessionExpiredAlertShown = false;

        document.getElementById("startScreen").style.display = "flex";
        document.getElementById("gameTable").classList.add("hidden");
        document.getElementById("sessionCounter").classList.add("hidden");
        document.getElementById("endSessionBtn").classList.add("hidden");
        document.getElementById("statsBtn").classList.add("hidden");

        clearInterval(sessionTimer);
        sessionTimer = null;

        resetGameState();
        return;
    }

    sessionActive = true;
    sessionBankroll = Number(data.saldo_sesion || 0);
    sessionBetLimit = Number(data.max_apuesta || data.saldo_sesion || 0);
    sessionExpiresAt = Number(data.expires_at || 0);

    document.getElementById("startScreen").style.display = "none";
    document.getElementById("gameTable").classList.remove("hidden");
    document.getElementById("sessionCounter").classList.remove("hidden");
    document.getElementById("endSessionBtn").classList.remove("hidden");
    document.getElementById("statsBtn").classList.remove("hidden");

    if (!sessionTimer) {
        sessionTimer = setInterval(updateSessionTimer, 1000);
    }

    updateSessionDisplay();
    updateBetDisplay();
    updateSessionTimer();
}

async function checkExistingGameSession() {
    try {
        const data = await gameSessionApi("status");
        applySessionState(data);

        if (data.active) {
            resetGameState();
        }
    } catch (error) {
        console.error(error);
        showAlert("Error consultando sesión de juego");
    }
}

async function confirmSession() {
    const bankrollInput = parseFloat(document.getElementById("bankroll").value);
    const timeInput = parseInt(document.getElementById("time").value);

    if (isNaN(bankrollInput) || bankrollInput <= 0) {
        showAlert("¡Ingresa un monto válido!");
        return;
    }

    if (bankrollInput > saldo) {
        showAlert("¡Saldo insuficiente!");
        return;
    }

    try {
        const data = await gameSessionApi("start", {
            bankroll: bankrollInput,
            time: timeInput
        });

        if (!data.ok) {
            showAlert(data.message || "No se pudo iniciar la sesión");
            return;
        }

        resetStats();
        applySessionState(data);
        resetGameState();
    } catch (error) {
        console.error(error);
        showAlert("Error iniciando sesión de juego");
    }
}

async function endSession(auto = false) {
    if ((gameActive || gameFinished) && !auto) {
        showAlert("⏳ Termina la mano primero");
        return;
    }

    try {
        const data = await gameSessionApi("close");

        if (!data.ok) {
            showAlert(data.message || "No se pudo cerrar la sesión");
            return;
        }

        saldo = Number(data.saldo_cuenta || 0);
        updateSaldo();

        showAlert(auto ? "⏰ Sesión finalizada por tiempo. Volviendo..." : "✅ Sesión finalizada. Volviendo...");

        setTimeout(() => {
            window.location.href = data.redirect || "principal.php";
        }, 1200);
    } catch (error) {
        console.error(error);
        showAlert("Error cerrando sesión de juego");
    }
}

function updateSessionTimer() {
    if (!sessionActive || !sessionExpiresAt) {
        return;
    }

    const now = Math.floor(Date.now() / 1000);
    const remaining = Math.max(0, sessionExpiresAt - now);

    if (remaining <= 0) {
        document.getElementById("sessionTime").textContent = "00:00";

        if (!sessionExpiredAlertShown) {
            sessionExpiredAlertShown = true;
            showAlert("⏰ Tiempo agotado. Termina la mano actual.");
        }

        if (!gameActive && !gameFinished) {
            endSession(true);
        }

        return;
    }

        const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;

    document.getElementById("sessionTime").textContent =
        `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
}

function updateSessionDisplay() {
    document.getElementById("sessionBankroll").textContent = `$${sessionBankroll.toFixed(2)}`;
    document.getElementById("sessionBetLimit").textContent = `Disponible: $${sessionBetLimit.toFixed(2)}`;
}

function updateBetDisplay() {
    document.getElementById("betAmount").textContent = bet.toFixed(2);
    document.getElementById("betLimit").textContent = `Disponible: $${sessionBetLimit.toFixed(2)}`;

    const dropZone = document.getElementById("dropZone");
    dropZone.classList.remove("bet-active", "bet-max");

    if (bet > 0) {
        dropZone.classList.add("bet-active");
    }

    if (sessionBetLimit > 0 && bet >= sessionBetLimit) {
        dropZone.classList.add("bet-max");
    }
}

function resetBet() {
    bet = 0;
    updateBetDisplay();
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".chip").forEach(chip => {
        chip.addEventListener("dragstart", e => {
            e.dataTransfer.setData("value", chip.dataset.value);
        });
    });

    const dropZone = document.getElementById("dropZone");
    dropZone.addEventListener("dragover", e => e.preventDefault());
    dropZone.addEventListener("drop", handleDrop);

    checkExistingGameSession();
});

function handleDrop(e) {
    e.preventDefault();

    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    if (gameActive || gameFinished) {
        showAlert("⏳ Termina la mano actual");
        return;
    }

    const value = parseFloat(e.dataTransfer.getData("value"));

    if (isNaN(value) || value <= 0) {
        showAlert("Ficha inválida");
        return;
    }

    const newBet = bet + value;

    if (newBet > sessionBetLimit) {
        showAlert(`💰 Máximo disponible $${sessionBetLimit.toFixed(2)}`);
        return;
    }

    bet = newBet;
    updateBetDisplay();
}

function updateStats(result, betAmount, winAmount = 0) {
    stats.totalBets += betAmount;

    if (result === "win") {
        stats.wins++;
        stats.totalWins += winAmount;
    } else if (result === "loss") {
        stats.losses++;
        stats.totalLosses += betAmount;
    } else if (result === "tie") {
        stats.ties++;
    }

    updateStatsDisplay();
}

function updateStatsDisplay() {
    document.getElementById("winsCount").textContent = stats.wins;
    document.getElementById("lossesCount").textContent = stats.losses;
    document.getElementById("totalBets").textContent = `$${stats.totalBets.toFixed(2)}`;
    document.getElementById("totalWins").textContent = `$${stats.totalWins.toFixed(2)}`;
    document.getElementById("totalLosses").textContent = `$${stats.totalLosses.toFixed(2)}`;

    const net = stats.totalWins - stats.totalLosses;

    document.getElementById("netProfit").textContent = `$${net.toFixed(2)}`;
    document.getElementById("netProfit").className =
        `text-3xl font-black ${net >= 0 ? "text-green-400" : "text-red-500"}`;
}

function toggleStats() {
    document.getElementById("statsModal").classList.toggle("hidden");
}

function resetStats() {
    stats = {
        wins: 0,
        losses: 0,
        ties: 0,
        totalBets: 0,
        totalWins: 0,
        totalLosses: 0
    };

    updateStatsDisplay();
}

function getCardValue(card) {
    const value = card.split("-")[0];

    if (value === "A") {
        return 11;
    }

    if (["J", "Q", "K"].includes(value)) {
        return 10;
    }

    return parseInt(value);
}

function getHandValue(hand) {
    let value = 0;
    let aces = 0;

    for (const card of hand) {
        const cardValue = getCardValue(card);

        if (cardValue === 11) {
            aces++;
        }

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
    const values = ["2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K", "A"];
    const suits = ["corazones", "diamantes", "trebol", "picas"];

    return `${values[Math.floor(Math.random() * values.length)]}-${suits[Math.floor(Math.random() * suits.length)]}.png`;
}

function createCard(src) {
    return `
        <div class="card deal-anim">
            <img src="img/cards/${src}" class="w-full h-full object-cover rounded-lg">
        </div>
    `;
}

function updateScores() {
    const playerValue = getHandValue(player);
    const dealerValue = getHandValue(dealer);

    document.getElementById("playerScoreTop").textContent = `👤 JUGADOR: ${playerValue}`;
    document.getElementById("dealerScoreTop").textContent = `🃏 CRUPIER: ${dealerValue}`;
}

function renderCards() {
    document.getElementById("playerCards").innerHTML = player.map(c => createCard(c)).join("");
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

async function deal() {
    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    if (bet <= 0) {
        showAlert("❌ Debes apostar primero");
        return;
    }

    if (bet > sessionBetLimit) {
        showAlert(`❌ Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
        return;
    }

    if (gameActive || gameFinished) {
        showAlert("⏳ Termina la mano actual");
        return;
    }

    try {
        const data = await gameSessionApi("debit", {
            amount: bet,
            game: "blackjack"
        });
        if (!data.ok) {
            showAlert(data.message || "No se pudo realizar la apuesta");

            if (typeof data.active !== "undefined") {
                applySessionState(data);
            }

            return;
        }

        applySessionState(data);

    } catch (error) {
        console.error(error);
        showAlert("Error realizando la apuesta");
        return;
    }

    gameActive = true;
    gameFinished = false;

    player = [card(), card()];
    dealer = [card(), card()];

    document.getElementById("dealBtn").disabled = true;

    document.getElementById("hitBtn").disabled = false;
    document.getElementById("hitBtn").classList.remove("hidden");

    document.getElementById("standBtn").disabled = false;
    document.getElementById("standBtn").classList.remove("hidden");

    renderCards();

    setTimeout(() => {
        if (isBlackjack(player)) {
            dealerPlay();
        }
    }, 1000);
}

function hit() {
    if (!gameActive) {
        return;
    }

    player.push(card());
    renderCards();

    if (getHandValue(player) > 21) {
        finishGame();
    }
}

function stand() {
    if (!gameActive) {
        return;
    }

    dealerPlay();
}

function dealerPlay() {
    while (getHandValue(dealer) < 17) {
        dealer.push(card());
        renderCards();
    }

    setTimeout(() => finishGame(), 800);
}

async function finishGame() {
    if (!gameActive) {
        return;
    }

    gameActive = false;
    gameFinished = true;

    document.getElementById("hitBtn").disabled = true;
    document.getElementById("standBtn").disabled = true;
    document.getElementById("dealBtn").disabled = true;

    const playerValue = getHandValue(player);
    const dealerValue = getHandValue(dealer);

    let finalResult = "";
    let resultForServer = "loss";
    let multiplier = 0;

    if (playerValue > 21) {
        finalResult = `💀 PASASTE (${playerValue})`;
        resultForServer = "loss";
        multiplier = 0;

    } else if (dealerValue > 21) {
        finalResult = `🎉 DEALER PASÓ (${dealerValue})!`;
        resultForServer = "win";
        multiplier = isBlackjack(player) ? 2.5 : 2;

    } else if (playerValue > dealerValue) {
        finalResult = `🎉 ¡VICTORIA! ${playerValue} vs ${dealerValue}`;
        resultForServer = "win";
        multiplier = isBlackjack(player) ? 2.5 : 2;

    } else if (playerValue < dealerValue) {
        finalResult = `💀 DERROTA ${playerValue} vs ${dealerValue}`;
        resultForServer = "loss";
        multiplier = 0;

    } else {
        finalResult = `🤝 EMPATE ${playerValue}`;
        resultForServer = "tie";
        multiplier = 1;
    }

    try {
        const data = await gameSessionApi("settle", {
            result: resultForServer,
            multiplier: multiplier
        });

        if (!data.ok) {
            showAlert(data.message || "Error liquidando apuesta");
            return;
        }

        applySessionState(data);

    } catch (error) {
        console.error(error);
        showAlert("Error liquidando apuesta");
        return;
    }

    if (resultForServer === "win") {
        const won = bet * multiplier;

        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "text-3xl font-black h-20 mb-4 text-green-400 animate-pulse";

        document.getElementById("bottomResult").innerHTML = "¡GANASTE!";
        document.getElementById("bottomResult").className = "text-4xl font-black h-16 mb-4 text-green-400 animate-pulse";

        updateStats("win", bet, won);

    } else if (resultForServer === "loss") {
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "text-3xl font-black h-20 mb-4 text-red-500 animate-pulse";

        document.getElementById("bottomResult").innerHTML = "¡PERDISTE!";
        document.getElementById("bottomResult").className = "text-4xl font-black h-16 mb-4 text-red-500 animate-pulse";

        updateStats("loss", bet);

    } else {
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "text-3xl font-black h-20 mb-4 text-yellow-400 animate-pulse";

        document.getElementById("bottomResult").innerHTML = "¡EMPATE!";
        document.getElementById("bottomResult").className = "text-4xl font-black h-16 mb-4 text-yellow-400 animate-pulse";

        updateStats("tie", bet);
    }

    setTimeout(() => {
        resetGameState();

        if (sessionExpiredAlertShown) {
            endSession(true);
            return;
        }

        showAlert("✅ ¡Listo para nueva apuesta!");
    }, 3000);
}

function showAlert(msg) {
    const alertBox = document.getElementById("casinoAlert");

    alertBox.innerText = msg;
    alertBox.classList.remove("hidden");

    setTimeout(() => {
        alertBox.classList.add("hidden");
    }, 3000);
}

function updateSaldo() {
    document.getElementById("saldoHeader").textContent = saldo.toFixed(2);
}

function goHome() {
    if (gameActive || gameFinished) {
        showAlert("⏳ Termina la mano primero");
        return;
    }

    /*
        Ir a principal NO cierra sesión.
        La sesión sirve para todos los juegos.
        Para devolver el saldo de sesión al saldo real, usa TERMINAR SESIÓN.
    */
    window.location.href = "principal.php";
}
</script>

</body>
</html>