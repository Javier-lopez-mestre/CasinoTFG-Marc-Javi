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
$saldo = isset($row['saldo']) ? (float) $row['saldo'] : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Minas Casino</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>
body {
    background: radial-gradient(circle at top, #064e3b, #020617 70%);
}

.session-counter {
    background: linear-gradient(45deg, #10b981, #059669);
    box-shadow: 0 10px 30px rgba(16,185,129,0.4);
}

.mine-board {
    display: grid;
    gap: 6px;
    width: 100%;
    max-width: 720px;
    margin: 0 auto;
}

.mine-cell {
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    background: linear-gradient(180deg, #16a34a, #065f46);
    border: 2px solid rgba(255,255,255,0.18);
    box-shadow: 0 6px 0 rgba(0,0,0,0.35);
    cursor: pointer;
    transition: all 0.12s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    user-select: none;
}

.mine-cell:hover {
    transform: translateY(-2px) scale(1.03);
    filter: brightness(1.15);
}

.mine-cell.revealed-safe {
    background: linear-gradient(180deg, #facc15, #ca8a04);
    color: black;
    box-shadow: 0 0 25px rgba(250,204,21,0.45);
    cursor: default;
}

.mine-cell.revealed-mine {
    background: linear-gradient(180deg, #ef4444, #7f1d1d);
    color: white;
    box-shadow: 0 0 35px rgba(239,68,68,0.8);
    cursor: default;
}

.mine-cell.disabled {
    pointer-events: none;
}

.glow-win {
    animation: glowWin 1s ease-in-out infinite alternate;
}

@keyframes glowWin {
    from {
        box-shadow: 0 0 25px rgba(34,197,94,0.45);
    }

    to {
        box-shadow: 0 0 70px rgba(34,197,94,1);
    }
}

.glow-loss {
    animation: glowLoss 1s ease-in-out infinite alternate;
}

@keyframes glowLoss {
    from {
        box-shadow: 0 0 25px rgba(239,68,68,0.45);
    }

    to {
        box-shadow: 0 0 70px rgba(239,68,68,1);
    }
}

@media(max-width:768px) {
    .mine-board {
        gap: 4px;
    }

    .mine-cell {
        border-radius: 8px;
        font-size: 12px;
    }
}
</style>
</head>

<body class="min-h-screen text-white overflow-x-hidden">

<div class="fixed inset-0 bg-black/40"></div>

<!-- HEADER -->
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
    </div>

    <div class="bg-yellow-400 text-black px-5 py-2 rounded-2xl font-black text-lg shadow-2xl">
        💰 Cuenta: <span id="saldoHeader"><?= number_format($saldo, 2) ?></span>$
    </div>
</header>

<!-- ALERTA -->
<div id="casinoAlert" class="hidden fixed top-24 left-1/2 -translate-x-1/2 z-[99999] bg-red-500 text-white px-6 py-3 rounded-2xl text-xl font-bold shadow-2xl text-center"></div>

<!-- PANTALLA INICIO SESIÓN -->
<div id="startScreen" class="fixed inset-0 bg-black/95 z-[99999] flex items-center justify-center">
    <div class="bg-zinc-900 border-4 border-yellow-400 rounded-3xl p-8 w-[90%] max-w-md text-center shadow-2xl">
        <div class="text-5xl font-black text-yellow-400 mb-6">💣 MINAS</div>

        <p class="text-zinc-400 mb-2 text-lg">Saldo disponible en cuenta:</p>

        <div class="text-4xl font-black mb-6 text-green-400">
            <?= number_format($saldo, 2) ?>$
        </div>
        
        <input id="bankroll" type="number" placeholder="Dinero para la sesión" min="1" max="<?= htmlspecialchars((string) $saldo) ?>"
               class="w-full bg-zinc-800 rounded-xl p-4 mb-4 text-white outline-none border-2 border-zinc-700 focus:border-yellow-400 text-2xl text-center font-bold">
        
        <select id="time" class="w-full bg-zinc-800 rounded-xl p-4 mb-6 text-white border-2 border-zinc-700 text-xl font-bold">
            <option value="1800">⏱️ 30 minutos</option>
            <option value="3600">⏱️ 1 hora</option>
            <option value="7200">⏱️ 2 horas</option>
        </select>
        
        <button onclick="confirmSession()" class="w-full bg-green-500 hover:bg-green-400 py-5 rounded-2xl text-2xl font-black transition shadow-2xl">
            🚀 COMENZAR SESIÓN
        </button>
    </div>
</div>

<!-- JUEGO -->
<main id="gameTable" class="relative z-10 min-h-screen pt-28 px-4 pb-10 hidden">

    <section class="max-w-7xl mx-auto">

        <h1 class="text-center text-5xl md:text-7xl font-black text-yellow-400 mb-8 drop-shadow-2xl">
            💣 MINAS
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-[380px_1fr] gap-8">

            <!-- PANEL IZQUIERDO -->
            <aside class="bg-zinc-900/90 border border-yellow-400/30 rounded-3xl p-6 h-fit shadow-2xl">

                <h2 class="text-3xl font-black text-yellow-400 mb-6 text-center">
                    Configuración
                </h2>

                <div class="space-y-5">

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Tamaño del tablero
                        </label>

                        <select id="boardSizeSelect" class="w-full bg-zinc-800 border-2 border-zinc-700 rounded-xl p-4 text-white font-black outline-none focus:border-yellow-400">
                            <option value="6">6 x 6</option>
                            <option value="8">8 x 8</option>
                            <option value="12">12 x 12</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Bombas
                        </label>

                        <select id="bombsSelect" class="w-full bg-zinc-800 border-2 border-zinc-700 rounded-xl p-4 text-white font-black outline-none focus:border-yellow-400">
                            <option value="1">1 bomba</option>
                            <option value="2">2 bombas</option>
                            <option value="3">3 bombas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Apuesta
                        </label>

                        <input id="betInput" type="number" min="0.5" step="0.5" value="1"
                               class="w-full bg-zinc-800 border-2 border-yellow-400 rounded-xl p-4 text-white text-center text-3xl font-black outline-none">

                        <p id="availableText" class="text-zinc-400 mt-2 font-bold text-center">
                            Disponible: $0
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 justify-center">
                        <button onclick="setBet(0.5)" class="bg-white text-black px-4 py-2 rounded-xl font-black hover:scale-105 transition">$0.5</button>
                        <button onclick="setBet(1)" class="bg-yellow-300 text-black px-4 py-2 rounded-xl font-black hover:scale-105 transition">$1</button>
                        <button onclick="setBet(2)" class="bg-green-500 text-white px-4 py-2 rounded-xl font-black hover:scale-105 transition">$2</button>
                        <button onclick="setBet(5)" class="bg-blue-500 text-white px-4 py-2 rounded-xl font-black hover:scale-105 transition">$5</button>
                        <button onclick="setBet(10)" class="bg-red-500 text-white px-4 py-2 rounded-xl font-black hover:scale-105 transition">$10</button>
                        <button onclick="setMaxBet()" class="bg-purple-500 text-white px-4 py-2 rounded-xl font-black hover:scale-105 transition">MAX</button>
                    </div>

                    <button id="startGameBtn" onclick="startMinesGame()" class="w-full bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-300 hover:to-orange-400 text-black py-5 rounded-2xl text-2xl font-black shadow-2xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                        EMPEZAR PARTIDA 💣
                    </button>

                    <button id="cashoutBtn" onclick="cashOut()" class="w-full bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400 text-white py-5 rounded-2xl text-2xl font-black shadow-2xl transition disabled:opacity-50 disabled:cursor-not-allowed hidden">
                        RETIRAR 💰
                    </button>

                    <div id="resultText" class="min-h-16 text-center text-2xl font-black text-yellow-400"></div>

                </div>

                <div class="mt-8 bg-black/40 rounded-2xl p-5 space-y-3">
                    <h3 class="text-xl font-black text-yellow-400 text-center">
                        Estado de partida
                    </h3>

                    <div class="flex justify-between">
                        <span class="text-zinc-400">Apuesta:</span>
                        <span class="font-black">$<span id="currentBetText">0.00</span></span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-zinc-400">Multiplicador:</span>
                        <span class="font-black text-green-400">x<span id="multiplierText">1.00</span></span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-zinc-400">Cobro actual:</span>
                        <span class="font-black text-yellow-400">$<span id="cashoutText">0.00</span></span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-zinc-400">Casillas seguras:</span>
                        <span class="font-black"><span id="safeFoundText">0</span>/<span id="safeTotalText">0</span></span>
                    </div>
                </div>

                <div class="mt-6 text-sm text-zinc-400 leading-relaxed">
                    <p>
                        Cada casilla segura aumenta el multiplicador. Si pulsas una bomba, pierdes la apuesta.
                    </p>
                    <p class="mt-2">
                        Puedes retirar cuando tengas al menos una casilla segura descubierta.
                    </p>
                </div>

            </aside>

            <!-- TABLERO -->
            <section id="boardWrapper" class="bg-zinc-900/80 border border-green-400/30 rounded-3xl p-4 md:p-8 shadow-2xl">
                <div id="mineBoard" class="mine-board"></div>
            </section>

        </div>

    </section>

</main>

<script>
let saldo = <?= (float) $saldo ?>;

let sessionActive = false;
let sessionBankroll = 0;
let sessionBetLimit = 0;
let sessionTimer = null;
let sessionExpiresAt = 0;
let sessionExpiredAlertShown = false;

let gameActive = false;
let gameFinished = false;

let boardSize = 6;
let totalCells = 36;
let bombsCount = 1;
let mines = [];
let revealed = [];
let safeFound = 0;
let safeTotal = 0;
let currentBet = 0;
let currentMultiplier = 1;

// ==================== API SESIÓN ====================
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

// ==================== SESIÓN ====================
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

        clearInterval(sessionTimer);
        sessionTimer = null;

        resetMinesGame(false);
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

    if (!sessionTimer) {
        sessionTimer = setInterval(updateSessionTimer, 1000);
    }

    updateSessionDisplay();
    updateSessionTimer();
}

async function checkExistingGameSession() {
    try {
        const data = await gameSessionApi("status");
        applySessionState(data);
        renderEmptyBoard();
    } catch (error) {
        console.error(error);
        showAlert("Error consultando sesión de juego");
    }
}

async function confirmSession() {
    const bankrollInput = parseFloat(document.getElementById("bankroll").value);
    const timeInput = parseInt(document.getElementById("time").value);

    if (isNaN(bankrollInput) || bankrollInput <= 0) {
        showAlert("Ingresa un monto válido");
        return;
    }

    if (bankrollInput > saldo) {
        showAlert("Saldo insuficiente");
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

        applySessionState(data);
        renderEmptyBoard();

    } catch (error) {
        console.error(error);
        showAlert("Error iniciando sesión");
    }
}

async function endSession(auto = false) {
    if (gameActive && !auto) {
        showAlert("⏳ Termina la partida o retira primero");
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

        showAlert(auto ? "⏰ Sesión finalizada. Volviendo..." : "✅ Sesión finalizada. Volviendo...");

        setTimeout(() => {
            window.location.href = data.redirect || "principal.php";
        }, 1200);

    } catch (error) {
        console.error(error);
        showAlert("Error cerrando sesión");
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
            showAlert("⏰ Tiempo agotado. Termina la partida actual.");
        }

        if (!gameActive) {
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
    document.getElementById("availableText").textContent = `Disponible: $${sessionBetLimit.toFixed(2)}`;
}

function updateSaldo() {
    document.getElementById("saldoHeader").textContent = saldo.toFixed(2);
}

// ==================== CONFIG APUESTA ====================
function setBet(amount) {
    if (gameActive) {
        showAlert("⏳ Termina la partida actual");
        return;
    }

    if (amount > sessionBetLimit) {
        showAlert(`Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
        amount = sessionBetLimit;
    }

    document.getElementById("betInput").value = Number(amount).toFixed(2);
}

function setMaxBet() {
    if (sessionBetLimit <= 0) {
        showAlert("No tienes saldo disponible en la sesión");
        return;
    }

    setBet(sessionBetLimit);
}

function getBetAmount() {
    const bet = parseFloat(document.getElementById("betInput").value);

    if (isNaN(bet)) {
        return 0;
    }

    return Math.round(bet * 100) / 100;
}

function lockConfig(locked) {
    document.getElementById("boardSizeSelect").disabled = locked;
    document.getElementById("bombsSelect").disabled = locked;
    document.getElementById("betInput").disabled = locked;
    document.getElementById("startGameBtn").disabled = locked;
}

// ==================== LÓGICA MINAS ====================
function getSelectedConfig() {
    boardSize = parseInt(document.getElementById("boardSizeSelect").value);
    bombsCount = parseInt(document.getElementById("bombsSelect").value);

    totalCells = boardSize * boardSize;
    safeTotal = totalCells - bombsCount;
}

function renderEmptyBoard() {
    getSelectedConfig();

    const board = document.getElementById("mineBoard");
    board.innerHTML = "";
    board.style.gridTemplateColumns = `repeat(${boardSize}, minmax(0, 1fr))`;

    for (let i = 0; i < totalCells; i++) {
        const cell = document.createElement("button");
        cell.type = "button";
        cell.className = "mine-cell";
        cell.dataset.index = i;
        cell.innerHTML = "❔";
        cell.onclick = () => clickCell(i);

        board.appendChild(cell);
    }

    updateGamePanel();
}

function generateMines() {
    mines = [];

    while (mines.length < bombsCount) {
        const randomIndex = Math.floor(Math.random() * totalCells);

        if (!mines.includes(randomIndex)) {
            mines.push(randomIndex);
        }
    }
}

function calculateMultiplier() {
    /*
        Multiplicador progresivo aproximado.
        Cuanto más grande el tablero y más bombas, mayor riesgo.
        Fórmula sencilla para juego local.
    */
    if (safeFound <= 0) {
        return 1;
    }

    const riskFactor = bombsCount / totalCells;
    const baseIncrease = 0.04 + (riskFactor * 4);
    const sizeBonus = boardSize === 6 ? 1 : boardSize === 8 ? 1.15 : 1.35;

    const multiplier = 1 + (safeFound * baseIncrease * sizeBonus);

    return Math.max(1.01, Math.round(multiplier * 100) / 100);
}

function updateGamePanel() {
    currentMultiplier = calculateMultiplier();

    const cashout = currentBet * currentMultiplier;

    document.getElementById("currentBetText").textContent = currentBet.toFixed(2);
    document.getElementById("multiplierText").textContent = currentMultiplier.toFixed(2);
    document.getElementById("cashoutText").textContent = cashout.toFixed(2);
    document.getElementById("safeFoundText").textContent = safeFound;
    document.getElementById("safeTotalText").textContent = safeTotal;

    if (gameActive && safeFound > 0) {
        document.getElementById("cashoutBtn").classList.remove("hidden");
        document.getElementById("cashoutBtn").disabled = false;
    } else {
        document.getElementById("cashoutBtn").classList.add("hidden");
        document.getElementById("cashoutBtn").disabled = true;
    }
}

async function startMinesGame() {
    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    if (gameActive) {
        showAlert("⏳ Ya hay una partida activa");
        return;
    }

    getSelectedConfig();

    currentBet = getBetAmount();

    if (currentBet <= 0) {
        showAlert("Apuesta inválida");
        return;
    }

    if (currentBet > sessionBetLimit) {
        showAlert(`Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
        return;
    }

    try {
        const debitData = await gameSessionApi("debit", {
            amount: currentBet,
            game: "minas"
        });

        if (!debitData.ok) {
            showAlert(debitData.message || "No se pudo realizar la apuesta");

            if (typeof debitData.active !== "undefined") {
                applySessionState(debitData);
            }

            return;
        }

        applySessionState(debitData);

    } catch (error) {
        console.error(error);
        showAlert("Error realizando apuesta");
        return;
    }

    gameActive = true;
    gameFinished = false;

    mines = [];
    revealed = [];
    safeFound = 0;
    currentMultiplier = 1;

    generateMines();
    renderEmptyBoard();
    lockConfig(true);

    document.getElementById("boardWrapper").classList.remove("glow-win", "glow-loss");
    document.getElementById("resultText").textContent = "Partida iniciada. Evita las bombas.";
    document.getElementById("resultText").className = "min-h-16 text-center text-2xl font-black text-yellow-400";

    updateGamePanel();
}

async function clickCell(index) {
    if (!gameActive) {
        showAlert("Primero empieza una partida");
        return;
    }

    if (revealed.includes(index)) {
        return;
    }

    const cell = document.querySelector(`.mine-cell[data-index="${index}"]`);

    if (!cell) {
        return;
    }

    revealed.push(index);

    if (mines.includes(index)) {
        cell.classList.add("revealed-mine");
        cell.innerHTML = "💣";

        await loseGame();
        return;
    }

    safeFound++;
    cell.classList.add("revealed-safe");
    cell.innerHTML = "💎";

    updateGamePanel();

    if (safeFound >= safeTotal) {
        await cashOut(true);
    }
}

function revealAllMines() {
    mines.forEach(index => {
        const cell = document.querySelector(`.mine-cell[data-index="${index}"]`);

        if (cell) {
            cell.classList.add("revealed-mine");
            cell.innerHTML = "💣";
        }
    });
}

function disableBoard() {
    document.querySelectorAll(".mine-cell").forEach(cell => {
        cell.classList.add("disabled");
    });
}

async function loseGame() {
    if (!gameActive) {
        return;
    }

    gameActive = false;
    gameFinished = true;

    revealAllMines();
    disableBoard();
    lockConfig(false);

    document.getElementById("cashoutBtn").classList.add("hidden");
    document.getElementById("cashoutBtn").disabled = true;

    document.getElementById("boardWrapper").classList.add("glow-loss");
    document.getElementById("boardWrapper").classList.remove("glow-win");

    document.getElementById("resultText").textContent = `💀 Bomba. Perdiste $${currentBet.toFixed(2)}`;
    document.getElementById("resultText").className = "min-h-16 text-center text-2xl font-black text-red-500 animate-pulse";

    try {
        const settleData = await gameSessionApi("settle", {
            result: "loss",
            multiplier: 0
        });

        if (!settleData.ok) {
            showAlert(settleData.message || "Error liquidando apuesta");
            return;
        }

        applySessionState(settleData);

    } catch (error) {
        console.error(error);
        showAlert("Error liquidando apuesta");
        return;
    }

    showAlert("💣 Perdiste la apuesta");

    finishRoundCleanup();
}

async function cashOut(autoComplete = false) {
    if (!gameActive) {
        return;
    }

    if (safeFound <= 0 && !autoComplete) {
        showAlert("Descubre al menos una casilla segura antes de retirar");
        return;
    }

    gameActive = false;
    gameFinished = true;

    currentMultiplier = calculateMultiplier();
    const payout = currentBet * currentMultiplier;

    revealAllMines();
    disableBoard();
    lockConfig(false);

    document.getElementById("cashoutBtn").classList.add("hidden");
    document.getElementById("cashoutBtn").disabled = true;

    document.getElementById("boardWrapper").classList.add("glow-win");
    document.getElementById("boardWrapper").classList.remove("glow-loss");

    const msg = autoComplete
        ? `🏆 Completaste el tablero. Cobras $${payout.toFixed(2)}`
        : `💰 Retiraste $${payout.toFixed(2)} x${currentMultiplier.toFixed(2)}`;

        document.getElementById("resultText").className = "min-h-16 text-center text-2xl font-black text-green-400 animate-pulse";

    try {
        const settleData = await gameSessionApi("settle", {
            result: "win",
            multiplier: currentMultiplier
        });

        if (!settleData.ok) {
            showAlert(settleData.message || "Error liquidando apuesta");
            return;
        }

        applySessionState(settleData);

    } catch (error) {
        console.error(error);
        showAlert("Error liquidando apuesta");
        return;
    }

    showAlert(`💰 Ganaste $${payout.toFixed(2)}`);

    finishRoundCleanup();
}

function finishRoundCleanup() {
    setTimeout(() => {
        /*
            No reiniciamos el tablero automáticamente para que el jugador
            pueda ver dónde estaban las bombas.
            Solo desbloqueamos la configuración y dejamos iniciar otra partida.
        */
        document.getElementById("startGameBtn").disabled = false;

        if (sessionExpiredAlertShown) {
            endSession(true);
        }
    }, 1200);
}

function resetMinesGame(render = true) {
    gameActive = false;
    gameFinished = false;

    mines = [];
    revealed = [];
    safeFound = 0;
    safeTotal = 0;
    currentBet = 0;
    currentMultiplier = 1;

    lockConfig(false);

    document.getElementById("cashoutBtn").classList.add("hidden");
    document.getElementById("cashoutBtn").disabled = true;

    document.getElementById("startGameBtn").disabled = false;

    document.getElementById("boardWrapper").classList.remove("glow-win", "glow-loss");

    document.getElementById("resultText").textContent = "";
    document.getElementById("resultText").className = "min-h-16 text-center text-2xl font-black text-yellow-400";

    if (render) {
        renderEmptyBoard();
    }

    updateGamePanel();
}

// ==================== UTILIDADES ====================
function showAlert(msg) {
    const alertBox = document.getElementById("casinoAlert");

    alertBox.innerText = msg;
    alertBox.classList.remove("hidden");

    setTimeout(() => {
        alertBox.classList.add("hidden");
    }, 3000);
}

function goHome() {
    if (gameActive) {
        showAlert("⏳ Termina la partida o retira primero");
        return;
    }

    /*
        Ir a principal NO cierra la sesión.
        La sesión sirve para todos los juegos.
        Para devolver el saldo de sesión al saldo real,
        usa TERMINAR SESIÓN.
    */
    window.location.href = "principal.php";
}

document.addEventListener("DOMContentLoaded", function () {
    checkExistingGameSession();

    document.getElementById("boardSizeSelect").addEventListener("change", function () {
        if (!gameActive) {
            renderEmptyBoard();
        }
    });

    document.getElementById("bombsSelect").addEventListener("change", function () {
        if (!gameActive) {
            renderEmptyBoard();
        }
    });

    document.getElementById("betInput").addEventListener("input", function () {
        if (gameActive) {
            return;
        }

        const apuesta = getBetAmount();

        if (apuesta > sessionBetLimit) {
            showAlert(`Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
            this.value = sessionBetLimit.toFixed(2);
        }

        updateGamePanel();
    });
});
</script>

</body>
</html>