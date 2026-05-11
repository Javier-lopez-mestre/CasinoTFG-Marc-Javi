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

<title>Ruleta</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>
body {
    background: radial-gradient(circle at top, #7f1d1d, #020617 70%);
}

.session-counter {
    background: linear-gradient(45deg, #10b981, #059669);
    box-shadow: 0 10px 30px rgba(16,185,129,0.4);
}

.chip {
    width: 70px;
    height: 70px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    cursor: grab;
    border: 4px solid rgba(255,255,255,0.75);
    box-shadow: 0 10px 25px rgba(0,0,0,0.45);
    user-select: none;
    transition: all 0.15s ease;
}

.chip:hover {
    transform: scale(1.08);
}

.chip:active {
    cursor: grabbing;
}

.roulette-wheel {
    width: 360px;
    height: 360px;
    border-radius: 50%;
    border: 16px solid #facc15;
    background:
        conic-gradient(
            #22c55e 0deg 13.33deg,
            #111827 13.33deg 26.66deg,
            #f8fafc 26.66deg 40deg,
            #111827 40deg 53.33deg,
            #f8fafc 53.33deg 66.66deg,
            #111827 66.66deg 80deg,
            #f8fafc 80deg 93.33deg,
            #111827 93.33deg 106.66deg,
            #f8fafc 106.66deg 120deg,
            #111827 120deg 133.33deg,
            #f8fafc 133.33deg 146.66deg,
            #111827 146.66deg 160deg,
            #f8fafc 160deg 173.33deg,
            #111827 173.33deg 186.66deg,
            #f8fafc 186.66deg 200deg,
            #111827 200deg 213.33deg,
            #f8fafc 213.33deg 226.66deg,
            #111827 226.66deg 240deg,
            #f8fafc 240deg 253.33deg,
            #111827 253.33deg 266.66deg,
            #f8fafc 266.66deg 280deg,
            #111827 280deg 293.33deg,
            #f8fafc 293.33deg 306.66deg,
            #111827 306.66deg 320deg,
            #f8fafc 320deg 333.33deg,
            #111827 333.33deg 346.66deg,
            #f8fafc 346.66deg 360deg
        );
    box-shadow: 0 0 80px rgba(250,204,21,0.45);
    position: relative;
    margin: 0 auto;
    transition: transform 4s cubic-bezier(.12,.8,.18,1);
}

.roulette-wheel::after {
    content: "";
    position: absolute;
    inset: 105px;
    border-radius: 50%;
    background: radial-gradient(circle, #7f1d1d, #111827);
    border: 8px solid #facc15;
    z-index: 3;
}

.roulette-center {
    position: absolute;
    inset: 120px;
    border-radius: 50%;
    background: #111827;
    border: 6px solid #facc15;
    z-index: 8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 46px;
}

.wheel-number {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 34px;
    height: 34px;
    margin-left: -17px;
    margin-top: -17px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 900;
    z-index: 5;
    transform:
        rotate(var(--angle))
        translateY(-142px)
        rotate(calc(-1 * var(--angle)));
}

.roulette-pointer {
    width: 0;
    height: 0;
    border-left: 24px solid transparent;
    border-right: 24px solid transparent;
    border-top: 48px solid #facc15;
    margin: 0 auto -20px auto;
    position: relative;
    z-index: 20;
    filter: drop-shadow(0 0 10px rgba(250,204,21,0.8));
}

.drop-zone {
    transition: all 0.15s ease;
}

.drop-zone.drag-over {
    outline: 4px dashed #facc15;
    transform: scale(1.04);
    filter: brightness(1.2);
}

.number-cell {
    min-height: 78px;
    transition: all 0.15s ease;
}

.number-cell:hover {
    transform: scale(1.05);
    filter: brightness(1.15);
}

.amount-badge {
    min-height: 24px;
}

.glow-win {
    animation: glowWin 1s ease-in-out infinite alternate;
}

@keyframes glowWin {
    from {
        box-shadow: 0 0 30px rgba(34,197,94,0.45);
    }

    to {
        box-shadow: 0 0 90px rgba(34,197,94,1);
    }
}

.glow-loss {
    animation: glowLoss 1s ease-in-out infinite alternate;
}

@keyframes glowLoss {
    from {
        box-shadow: 0 0 30px rgba(239,68,68,0.45);
    }

    to {
        box-shadow: 0 0 90px rgba(239,68,68,1);
    }
}

@media(max-width:768px) {
    .roulette-wheel {
        width: 280px;
        height: 280px;
    }

    .roulette-wheel::after {
        inset: 82px;
    }

    .roulette-center {
        inset: 95px;
        font-size: 34px;
    }

    .wheel-number {
        width: 28px;
        height: 28px;
        margin-left: -14px;
        margin-top: -14px;
        font-size: 10px;
        transform:
            rotate(var(--angle))
            translateY(-110px)
            rotate(calc(-1 * var(--angle)));
    }

    .chip {
        width: 55px;
        height: 55px;
        font-size: 13px;
    }
}
</style>
</head>

<body class="min-h-screen text-white overflow-x-hidden">

<div class="fixed inset-0 bg-black/40"></div>

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

<div id="casinoAlert" class="hidden fixed top-24 left-1/2 -translate-x-1/2 z-[99999] bg-red-500 text-white px-6 py-3 rounded-2xl text-xl font-bold shadow-2xl text-center"></div>

<div id="startScreen" class="fixed inset-0 bg-black/95 z-[99999] flex items-center justify-center">
    <div class="bg-zinc-900 border-4 border-yellow-400 rounded-3xl p-8 w-[90%] max-w-md text-center shadow-2xl">
        <div class="text-5xl font-black text-yellow-400 mb-6">🎡 RULETA</div>

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

<main id="gameTable" class="relative z-10 min-h-screen pt-28 px-4 pb-10 hidden">

    <section class="max-w-7xl mx-auto">

        <h1 class="text-center text-5xl md:text-7xl font-black text-yellow-400 mb-8 drop-shadow-2xl">
            🎡 RULETA 0 - 26
        </h1>

        <div class="grid grid-cols-1 xl:grid-cols-[420px_1fr] gap-8">

            <aside class="bg-zinc-900/90 border border-yellow-400/30 rounded-3xl p-6 h-fit shadow-2xl">

                <h2 class="text-3xl font-black text-yellow-400 mb-6 text-center">
                    Fichas
                </h2>

                <div class="flex flex-wrap justify-center gap-3 mb-6">
                    <div draggable="true" data-value="0.5" class="chip bg-white text-black">$0.5</div>
                    <div draggable="true" data-value="1" class="chip bg-yellow-300 text-black">$1</div>
                    <div draggable="true" data-value="2" class="chip bg-green-500 text-white">$2</div>
                    <div draggable="true" data-value="5" class="chip bg-blue-600 text-white">$5</div>
                    <div draggable="true" data-value="10" class="chip bg-red-600 text-white">$10</div>
                    <div draggable="true" data-value="25" class="chip bg-purple-600 text-white">$25</div>
                </div>

                <div class="bg-black/40 rounded-2xl p-5 space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Total apostado:</span>
                        <span class="font-black text-yellow-400">$<span id="totalBetText">0.00</span></span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-zinc-400">Disponible sesión:</span>
                        <span class="font-black text-green-400">$<span id="availableText">0.00</span></span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-zinc-400">Apuestas:</span>
                        <span class="font-black"><span id="betsCountText">0</span></span>
                    </div>
                </div>

                <button onclick="clearBets()" id="clearBetsBtn" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white py-4 rounded-2xl text-xl font-black transition mb-4">
                    LIMPIAR APUESTAS
                </button>

                <button id="spinBtn" onclick="spinRoulette()" class="w-full bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-300 hover:to-orange-400 text-black py-5 rounded-2xl text-2xl font-black shadow-2xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                    GIRAR RULETA 🎡
                </button>

                <div id="resultText" class="min-h-20 text-center text-2xl font-black text-yellow-400 mt-6"></div>

                <div class="mt-8 text-sm text-zinc-400 leading-relaxed bg-black/30 p-4 rounded-2xl">
                    <p><b class="text-yellow-400">Arrastra fichas</b> al tablero para apostar.</p>
                    <p><b class="text-yellow-400">Número exacto:</b> paga x26.</p>
                    <p><b class="text-yellow-400">Par / Impar:</b> paga x2.</p>
                    <p><b class="text-yellow-400">Blanco / Negro:</b> paga x2.</p>
                    <p class="mt-2">El número 0 es verde. Solo gana si apuestas al 0.</p>
                </div>

            </aside>

            <section id="rouletteWrapper" class="bg-zinc-900/80 border border-yellow-400/30 rounded-3xl p-6 md:p-8 shadow-2xl">

                <div class="mb-10">
                    <div class="roulette-pointer"></div>

                    <div id="rouletteWheel" class="roulette-wheel">
                        <div class="roulette-center">🎡</div>
                    </div>
                </div>

                <div class="text-center mb-8">
                    <div class="text-zinc-400 font-bold">Resultado</div>
                    <div id="lastNumber" class="text-6xl font-black text-yellow-400">-</div>
                    <div id="lastColor" class="text-2xl font-black text-zinc-300 mt-2">-</div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <button data-bet-key="par" data-type="par" class="drop-zone bet-zone bg-blue-600 hover:bg-blue-500 rounded-2xl p-5 text-2xl font-black transition">
                        PAR x2
                        <div id="amount-par" class="amount-badge text-sm text-yellow-300 mt-2">0.00$</div>
                    </button>

                    <button data-bet-key="impar" data-type="impar" class="drop-zone bet-zone bg-purple-600 hover:bg-purple-500 rounded-2xl p-5 text-2xl font-black transition">
                        IMPAR x2
                        <div id="amount-impar" class="amount-badge text-sm text-yellow-300 mt-2">0.00$</div>
                    </button>

                    <button data-bet-key="blanco" data-type="color" data-color="blanco" class="drop-zone bet-zone bg-white text-black hover:bg-zinc-200 rounded-2xl p-5 text-2xl font-black transition">
                        BLANCO x2
                        <div id="amount-blanco" class="amount-badge text-sm text-yellow-700 mt-2">0.00$</div>
                    </button>

                    <button data-bet-key="negro" data-type="color" data-color="negro" class="drop-zone bet-zone bg-black border-2 border-white hover:bg-zinc-800 rounded-2xl p-5 text-2xl font-black transition">
                        NEGRO x2
                        <div id="amount-negro" class="amount-badge text-sm text-yellow-300 mt-2">0.00$</div>
                    </button>
                </div>

                <div>
                    <h2 class="text-3xl font-black text-yellow-400 text-center mb-5">
                        Tablero 0 - 26
                    </h2>

                    <div id="numberGrid" class="grid grid-cols-3 sm:grid-cols-6 md:grid-cols-9 gap-3"></div>
                </div>

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

let spinning = false;
let wheelRotation = 0;

let draggedChipValue = 0;
let bets = {};

const whiteNumbers = [1, 4, 6, 9, 11, 14, 16, 19, 21, 24, 26, 2, 7];

function isWhiteNumber(number) {
    return whiteNumbers.includes(number);
}

function getNumberColor(number) {
    if (number === 0) {
        return "verde";
    }

    return isWhiteNumber(number) ? "blanco" : "negro";
}

function getNumberClass(number) {
    if (number === 0) {
        return "bg-green-500 text-white";
    }

    if (getNumberColor(number) === "blanco") {
        return "bg-white text-black";
    }

    return "bg-black text-white border-2 border-white";
}

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

    } catch (error) {
        console.error(error);
        showAlert("Error iniciando sesión");
    }
}

async function endSession(auto = false) {
    if (spinning && !auto) {
        showAlert("⏳ Espera a que termine la tirada");
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
            showAlert("⏰ Tiempo agotado. Termina la tirada actual.");
        }

        if (!spinning) {
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
    document.getElementById("availableText").textContent = sessionBetLimit.toFixed(2);
}

function updateSaldo() {
    document.getElementById("saldoHeader").textContent = saldo.toFixed(2);
}

// ==================== TABLERO Y RULETA ====================
function buildWheelNumbers() {
    const wheel = document.getElementById("rouletteWheel");

    for (let i = 0; i <= 26; i++) {
        const label = document.createElement("div");
        const angle = (360 / 27) * i;

        label.className = "wheel-number " + getNumberClass(i);
        label.style.setProperty("--angle", `${angle}deg`);
        label.textContent = i;

        wheel.appendChild(label);
    }
}

function buildNumberGrid() {
    const grid = document.getElementById("numberGrid");
    grid.innerHTML = "";

    for (let i = 0; i <= 26; i++) {
        const btn = document.createElement("button");

        btn.type = "button";
        btn.dataset.betKey = `number_${i}`;
        btn.dataset.type = "number";
        btn.dataset.value = i;

        btn.className = "drop-zone number-cell rounded-2xl p-4 text-2xl font-black transition " + getNumberClass(i);

        btn.innerHTML = `
            <div>${i}</div>
            <div id="amount-number_${i}" class="amount-badge text-sm mt-2 ${i === 0 || getNumberColor(i) === "negro" ? "text-yellow-300" : "text-yellow-700"}">0.00$</div>
        `;

        grid.appendChild(btn);
    }
}

function setupDragAndDrop() {
    document.querySelectorAll(".chip").forEach(chip => {
        chip.addEventListener("dragstart", function (event) {
            draggedChipValue = Number(this.dataset.value || 0);
            event.dataTransfer.setData("text/plain", draggedChipValue);
        });
    });

    document.querySelectorAll(".drop-zone").forEach(zone => {
        zone.addEventListener("dragover", function (event) {
            event.preventDefault();
            this.classList.add("drag-over");
        });

        zone.addEventListener("dragleave", function () {
            this.classList.remove("drag-over");
        });

        zone.addEventListener("drop", function (event) {
            event.preventDefault();
            this.classList.remove("drag-over");

            if (spinning) {
                showAlert("⏳ Espera a que termine la tirada");
                return;
            }

            const value = Number(event.dataTransfer.getData("text/plain") || draggedChipValue || 0);

            if (value <= 0) {
                return;
            }

            addBetToZone(this, value);
        });
    });
}

function addBetToZone(zone, amount) {
    const totalBefore = getTotalBet();

    if (totalBefore + amount > sessionBetLimit) {
        showAlert(`No puedes superar el disponible: $${sessionBetLimit.toFixed(2)}`);
        return;
    }

    const key = zone.dataset.betKey;
    const type = zone.dataset.type;

    let betValue = null;

    if (type === "number") {
        betValue = Number(zone.dataset.value);
    } else if (type === "color") {
        betValue = zone.dataset.color;
    } else {
        betValue = type;
    }

    if (!bets[key]) {
        bets[key] = {
            type: type,
            value: betValue,
            amount: 0
        };
    }

    bets[key].amount += amount;
    bets[key].amount = Math.round(bets[key].amount * 100) / 100;

    updateBetsDisplay();
}

function getTotalBet() {
    let total = 0;

    Object.values(bets).forEach(bet => {
        total += Number(bet.amount || 0);
    });

    return Math.round(total * 100) / 100;
}

function updateBetsDisplay() {
    const total = getTotalBet();
    const count = Object.keys(bets).length;

    document.getElementById("totalBetText").textContent = total.toFixed(2);
    document.getElementById("betsCountText").textContent = count;

    document.querySelectorAll(".amount-badge").forEach(el => {
        el.textContent = "0.00$";
    });

    Object.keys(bets).forEach(key => {
        const amountEl = document.getElementById(`amount-${key}`);

        if (amountEl) {
            amountEl.textContent = bets[key].amount.toFixed(2) + "$";
        }
    });
}

function clearBets() {
    if (spinning) {
        showAlert("⏳ Espera a que termine la tirada");
        return;
    }

    bets = {};
    updateBetsDisplay();
}

// ==================== RESULTADO ====================
function getRandomRouletteNumber() {
    return Math.floor(Math.random() * 27);
}

function calculateRoulettePayout(resultNumber) {
    let totalPayout = 0;
    let winningBets = [];

    const resultColor = getNumberColor(resultNumber);

    Object.keys(bets).forEach(key => {
        const bet = bets[key];
        const amount = Number(bet.amount || 0);

        let won = false;
        let multiplier = 0;

        if (bet.type === "number") {
            if (Number(bet.value) === resultNumber) {
                won = true;
                multiplier = 26;
            }
        }

        if (bet.type === "par") {
            if (resultNumber !== 0 && resultNumber % 2 === 0) {
                won = true;
                multiplier = 2;
            }
        }

        if (bet.type === "impar") {
            if (resultNumber !== 0 && resultNumber % 2 !== 0) {
                won = true;
                multiplier = 2;
            }
        }

        if (bet.type === "color") {
            if (resultNumber !== 0 && bet.value === resultColor) {
                won = true;
                multiplier = 2;
            }
        }

        if (won) {
            const payout = amount * multiplier;

            totalPayout += payout;

            winningBets.push({
                key: key,
                type: bet.type,
                value: bet.value,
                amount: amount,
                multiplier: multiplier,
                payout: payout
            });
        }
    });

    return {
        totalPayout: Math.round(totalPayout * 100) / 100,
        winningBets: winningBets
    };
}

function animateWheel(resultNumber) {
    return new Promise(resolve => {
        const wheel = document.getElementById("rouletteWheel");

        const degreesPerNumber = 360 / 27;
        const targetAngle = resultNumber * degreesPerNumber;

        wheelRotation += 360 * 6 + (360 - targetAngle);

        wheel.style.transform = `rotate(${wheelRotation}deg)`;

        setTimeout(resolve, 4200);
    });
}

function paintLastNumber(resultNumber) {
    const last = document.getElementById("lastNumber");
    const color = getNumberColor(resultNumber);

    last.textContent = resultNumber;

    if (resultNumber === 0) {
        last.className = "text-6xl font-black text-green-400";
        document.getElementById("lastColor").textContent = "VERDE";
        document.getElementById("lastColor").className = "text-2xl font-black text-green-400 mt-2";
    } else if (color === "blanco") {
        last.className = "text-6xl font-black text-white";
        document.getElementById("lastColor").textContent = "BLANCO";
        document.getElementById("lastColor").className = "text-2xl font-black text-white mt-2";
    } else {
        last.className = "text-6xl font-black text-zinc-900 bg-white rounded-2xl inline-block px-5";
        document.getElementById("lastColor").textContent = "NEGRO";
        document.getElementById("lastColor").className = "text-2xl font-black text-zinc-200 mt-2";
    }
}

async function spinRoulette() {
    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    if (spinning) {
        showAlert("⏳ Espera a que termine la tirada");
        return;
    }

    const totalBet = getTotalBet();

    if (totalBet <= 0) {
        showAlert("Arrastra al menos una ficha al tablero");
        return;
    }

    if (totalBet > sessionBetLimit) {
        showAlert(`Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
        return;
    }

    spinning = true;

    document.getElementById("spinBtn").disabled = true;
    document.getElementById("clearBetsBtn").disabled = true;
    document.getElementById("resultText").textContent = "Girando...";
    document.getElementById("resultText").className = "min-h-20 text-center text-2xl font-black text-yellow-400 mt-6";

    document.getElementById("rouletteWrapper").classList.remove("glow-win", "glow-loss");

    const betsSnapshot = JSON.parse(JSON.stringify(bets));

    try {
        const debitData = await gameSessionApi("debit", {
            amount: totalBet,
            game: "ruleta"
        });

        if (!debitData.ok) {
            spinning = false;
            document.getElementById("spinBtn").disabled = false;
            document.getElementById("clearBetsBtn").disabled = false;

            showAlert(debitData.message || "No se pudo realizar la apuesta");

            if (typeof debitData.active !== "undefined") {
                applySessionState(debitData);
            }

            return;
        }

        applySessionState(debitData);

        const resultNumber = getRandomRouletteNumber();
        const resultColor = getNumberColor(resultNumber);

        await animateWheel(resultNumber);

        paintLastNumber(resultNumber);

        const payoutData = calculateRoulettePayout(resultNumber);

        const totalPayout = payoutData.totalPayout;
        const winningBets = payoutData.winningBets;

        let resultForServer = "loss";
        let multiplierForServer = 0;

        if (totalPayout > 0) {
            resultForServer = "win";
            multiplierForServer = totalPayout / totalBet;
        }

        multiplierForServer = Math.round(multiplierForServer * 10000) / 10000;

        const settleData = await gameSessionApi("settle", {
            result: resultForServer,
            multiplier: multiplierForServer,
            detalle: {
                tipo: "ruleta",
                numero_resultado: resultNumber,
                color_resultado: resultColor,
                                apuestas: betsSnapshot,
                apuestas_ganadoras: winningBets,
                total_apostado: totalBet,
                total_pago: totalPayout
            }
        });

        if (!settleData.ok) {
            spinning = false;
            document.getElementById("spinBtn").disabled = false;
            document.getElementById("clearBetsBtn").disabled = false;

            showAlert(settleData.message || "Error guardando resultado");
            return;
        }

        applySessionState(settleData);

        const colorTexto = resultNumber === 0 ? "VERDE" : resultColor.toUpperCase();

        if (totalPayout > 0) {
            document.getElementById("resultText").textContent =
                `🎉 Salió ${resultNumber} ${colorTexto}. Ganaste $${totalPayout.toFixed(2)}`;

            document.getElementById("resultText").className =
                "min-h-20 text-center text-2xl font-black text-green-400 mt-6 animate-pulse";

            document.getElementById("rouletteWrapper").classList.add("glow-win");
            document.getElementById("rouletteWrapper").classList.remove("glow-loss");

            showAlert(`🎉 Ganaste $${totalPayout.toFixed(2)}`);
        } else {
            document.getElementById("resultText").textContent =
                `💀 Salió ${resultNumber} ${colorTexto}. Perdiste $${totalBet.toFixed(2)}`;

            document.getElementById("resultText").className =
                "min-h-20 text-center text-2xl font-black text-red-500 mt-6 animate-pulse";

            document.getElementById("rouletteWrapper").classList.add("glow-loss");
            document.getElementById("rouletteWrapper").classList.remove("glow-win");

            showAlert("💀 Perdiste");
        }

        clearBets();

    } catch (error) {
        console.error(error);
        showAlert("Error en la ruleta");
    }

    spinning = false;
    document.getElementById("spinBtn").disabled = false;
    document.getElementById("clearBetsBtn").disabled = false;

    if (sessionExpiredAlertShown) {
        setTimeout(() => {
            endSession(true);
        }, 1000);
    }
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
    if (spinning) {
        showAlert("⏳ Espera a que termine la tirada");
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
    buildWheelNumbers();
    buildNumberGrid();
    setupDragAndDrop();
    updateBetsDisplay();
    checkExistingGameSession();
});
</script>

</body>
</html>