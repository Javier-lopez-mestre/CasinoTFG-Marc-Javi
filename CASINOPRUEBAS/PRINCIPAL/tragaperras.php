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

<title>Tragaperras</title>

<link rel="icon" type="image/png" href="img/logocuadrado.png">
<script src="https://cdn.tailwindcss.com"></script>

<style>
body {
    background: radial-gradient(circle at top, #3b0764, #020617 70%);
}

.session-counter {
    background: linear-gradient(45deg, #10b981, #059669);
    box-shadow: 0 10px 30px rgba(16,185,129,0.4);
}

.slot-machine {
    background: linear-gradient(180deg, #991b1b, #450a0a);
    box-shadow: 0 0 60px rgba(250,204,21,0.35);
}

.reel {
    background: linear-gradient(180deg, #ffffff, #d4d4d8);
    color: #111827;
    box-shadow: inset 0 0 20px rgba(0,0,0,0.35);
}

.reel.spinning {
    animation: reelSpin 0.12s linear infinite;
}

@keyframes reelSpin {
    0% {
        transform: translateY(-6px) scale(1.02);
        filter: blur(1px);
    }

    50% {
        transform: translateY(6px) scale(1.04);
        filter: blur(2px);
    }

    100% {
        transform: translateY(-6px) scale(1.02);
        filter: blur(1px);
    }
}

.glow-win {
    animation: glowWin 1s ease-in-out infinite alternate;
}

@keyframes glowWin {
    from {
        box-shadow: 0 0 30px rgba(34,197,94,0.5);
    }

    to {
        box-shadow: 0 0 80px rgba(34,197,94,1);
    }
}

.glow-loss {
    animation: glowLoss 1s ease-in-out infinite alternate;
}

@keyframes glowLoss {
    from {
        box-shadow: 0 0 30px rgba(239,68,68,0.5);
    }

    to {
        box-shadow: 0 0 80px rgba(239,68,68,1);
    }
}
</style>
</head>

<body class="min-h-screen text-white overflow-x-hidden">

<div class="fixed inset-0 bg-black/40"></div>

<!-- HEADER DE SESIÓN -->
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
        <div class="text-5xl font-black text-yellow-400 mb-6">🎰 TRAGAPERRAS</div>

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
<main id="gameTable" class="relative z-10 min-h-screen pt-32 px-4 pb-4 hidden">

    <section class="max-w-5xl mx-auto text-center">

        <h1 class="text-3xl md:text-4xl font-black text-yellow-400 mb-3 drop-shadow-2xl">
            🎰 TRAGAPERRAS
        </h1>

        <div id="slotMachine" class="slot-machine border-6 border-yellow-400 rounded-2xl p-2 md:p-4 max-w-xl mx-auto">

            <div class="grid grid-cols-3 gap-3 mb-4">
                <div id="reel1" class="reel rounded-2xl h-20 md:h-24 flex items-center justify-center text-4xl md:text-5xl font-black">
                    🍒
                </div>

                <div id="reel2" class="reel rounded-2xl h-20 md:h-24 flex items-center justify-center text-4xl md:text-5xl font-black">
                    🍋
                </div>

                <div id="reel3" class="reel rounded-2xl h-20 md:h-24 flex items-center justify-center text-4xl md:text-5xl font-black">
                    🔔
                </div>
            </div>

            <div id="resultText" class="h-10 text-lg md:text-xl font-black mb-4 text-yellow-400"></div>

            <div class="bg-black/50 rounded-2xl p-4 max-w-xs mx-auto mb-4">
                <label class="block text-zinc-300 font-bold mb-2 text-sm">
                    Apuesta
                </label>

                <input id="betInput" type="number" min="0.5" step="0.5" value="1"
                       class="w-full bg-zinc-900 border-2 border-yellow-400 rounded-xl p-2 text-center text-xl font-black outline-none text-white">

                <p id="availableText" class="text-zinc-400 mt-2 font-bold text-xs">
                    Disponible: $0
                </p>
            </div>

            <div class="flex flex-wrap justify-center gap-2 mb-4">
                <button onclick="setBet(0.5)" class="bg-white text-black px-3 py-2 rounded-lg font-bold hover:scale-105 transition text-sm">$0.5</button>
                <button onclick="setBet(1)" class="bg-yellow-300 text-black px-3 py-2 rounded-lg font-bold hover:scale-105 transition text-sm">$1</button>
                <button onclick="setBet(2)" class="bg-green-500 text-white px-3 py-2 rounded-lg font-bold hover:scale-105 transition text-sm">$2</button>
                <button onclick="setBet(5)" class="bg-blue-500 text-white px-3 py-2 rounded-lg font-bold hover:scale-105 transition text-sm">$5</button>
                <button onclick="setBet(10)" class="bg-red-500 text-white px-3 py-2 rounded-lg font-bold hover:scale-105 transition text-sm">$10</button>
                <button onclick="setMaxBet()" class="bg-purple-500 text-white px-3 py-2 rounded-lg font-bold hover:scale-105 transition text-sm">MAX</button>
            </div>

            <button id="spinBtn" onclick="spin()" class="bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-300 hover:to-orange-400 text-black px-8 py-3 rounded-2xl text-lg font-black shadow-2xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                GIRAR 🎰
            </button>

        </div>

        <div class="mt-8 bg-black/50 rounded-3xl p-6 max-w-3xl mx-auto border border-yellow-400/30">
            <h2 class="text-2xl font-black text-yellow-400 mb-4">Tabla de premios</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-left text-lg">
                <div>🍒 🍒 🍒 = x2</div>
                <div>🍋 🍋 🍋 = x3</div>
                <div>🔔 🔔 🔔 = x5</div>
                <div>💎 💎 💎 = x10</div>
                <div>7️⃣ 7️⃣ 7️⃣ = x20</div>
                <div>⭐ ⭐ ⭐ = x50</div>
            </div>
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

const symbols = ["🍒", "🍋", "🔔", "💎", "7️⃣", "⭐", "🍉", "🍇"];

const payoutTable = {
    "🍒": 2,
    "🍋": 3,
    "🔔": 5,
    "💎": 10,
    "7️⃣": 20,
    "⭐": 50
};

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
    document.getElementById("availableText").textContent = `Disponible: $${sessionBetLimit.toFixed(2)}`;
}

function updateSaldo() {
    document.getElementById("saldoHeader").textContent = saldo.toFixed(2);
}

// ==================== TRAGAPERRAS ====================
function randomSymbol() {
    return symbols[Math.floor(Math.random() * symbols.length)];
}

function setBet(amount) {
    if (spinning) {
        showAlert("⏳ Espera a que termine la tirada");
        return;
    }

    if (amount > sessionBetLimit) {
        showAlert(`Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
        amount = sessionBetLimit;    }

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

function calculateMultiplier(finalSymbols) {
    const [a, b, c] = finalSymbols;

    /*
        Tres símbolos iguales.
    */
    if (a === b && b === c) {
        return payoutTable[a] || 2;
    }

    /*
        No hay bonificación por dos símbolos - solo 3 iguales ganan.
    */
    return 0;
}

function setReels(finalSymbols) {
    document.getElementById("reel1").textContent = finalSymbols[0];
    document.getElementById("reel2").textContent = finalSymbols[1];
    document.getElementById("reel3").textContent = finalSymbols[2];
}

function startReelAnimation() {
    document.getElementById("reel1").classList.add("spinning");
    document.getElementById("reel2").classList.add("spinning");
    document.getElementById("reel3").classList.add("spinning");

    document.getElementById("slotMachine").classList.remove("glow-win", "glow-loss");
}

function stopReelAnimation() {
    document.getElementById("reel1").classList.remove("spinning");
    document.getElementById("reel2").classList.remove("spinning");
    document.getElementById("reel3").classList.remove("spinning");
}

function animateSpin(finalSymbols) {
    return new Promise(resolve => {
        startReelAnimation();

        let counter = 0;

        const interval = setInterval(() => {
            document.getElementById("reel1").textContent = randomSymbol();
            document.getElementById("reel2").textContent = randomSymbol();
            document.getElementById("reel3").textContent = randomSymbol();

            counter++;

            if (counter >= 20) {
                clearInterval(interval);

                setTimeout(() => {
                    document.getElementById("reel1").textContent = finalSymbols[0];
                }, 200);

                setTimeout(() => {
                    document.getElementById("reel2").textContent = finalSymbols[1];
                }, 500);

                setTimeout(() => {
                    document.getElementById("reel3").textContent = finalSymbols[2];
                    stopReelAnimation();
                    resolve();
                }, 800);
            }
        }, 80);
    });
}

function generateFinalSymbols() {
    /*
        - Total de victoria: ~20% 
        - Muy difícil conseguir 3 estrellas y 3 seises (1% cada uno)
    */
    const r = Math.random();

    if (r < 0.01) {
        return ["⭐", "⭐", "⭐"];
    }

    if (r < 0.02) {
        return ["7️⃣", "7️⃣", "7️⃣"];
    }

    if (r < 0.055) {
        return ["💎", "💎", "💎"];
    }

    if (r < 0.095) {
        return ["🔔", "🔔", "🔔"];
    }

    if (r < 0.145) {
        return ["🍋", "🍋", "🍋"];
    }

    if (r < 0.205) {
        return ["🍒", "🍒", "🍒"];
    }

    /*
        Resultado aleatorio normal (79.5% de probabilidad).
    */
    return [
        randomSymbol(),
        randomSymbol(),
        randomSymbol()
    ];
}

async function spin() {
    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    if (spinning) {
        showAlert("⏳ Espera a que termine la tirada");
        return;
    }

    const apuesta = getBetAmount();

    if (apuesta <= 0) {
        showAlert("Apuesta inválida");
        return;
    }

    if (apuesta > sessionBetLimit) {
        showAlert(`Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
        return;
    }

    spinning = true;

    document.getElementById("spinBtn").disabled = true;
    document.getElementById("resultText").textContent = "Girando...";
    document.getElementById("resultText").className = "h-16 text-3xl md:text-4xl font-black mb-6 text-yellow-400";

    try {
        /*
            1. Descontar apuesta del saldo de sesión.
        */
        const debitData = await gameSessionApi("debit", {
            amount: apuesta,
            game: "tragaperras"
        });

        if (!debitData.ok) {
            spinning = false;
            document.getElementById("spinBtn").disabled = false;

            showAlert(debitData.message || "No se pudo realizar la apuesta");

            if (typeof debitData.active !== "undefined") {
                applySessionState(debitData);
            }

            return;
        }

        applySessionState(debitData);

        /*
            2. Generar resultado y animar.
        */
        const finalSymbols = generateFinalSymbols();
        const multiplier = calculateMultiplier(finalSymbols);

        await animateSpin(finalSymbols);

        /*
            3. Liquidar apuesta.
        */
        let resultForServer = "loss";

        if (multiplier > 0) {
            resultForServer = "win";
        }

        const settleData = await gameSessionApi("settle", {
            result: resultForServer,
            multiplier: multiplier
        });

        if (!settleData.ok) {
            spinning = false;
            document.getElementById("spinBtn").disabled = false;

            showAlert(settleData.message || "Error liquidando apuesta");
            return;
        }

        applySessionState(settleData);

        /*
            4. Mostrar resultado.
        */
        if (resultForServer === "win") {
            const premio = apuesta * multiplier;

            document.getElementById("resultText").textContent =
                `🎉 Ganaste $${premio.toFixed(2)} x${multiplier}`;

            document.getElementById("resultText").className =
                "h-16 text-3xl md:text-4xl font-black mb-6 text-green-400 animate-pulse";

            document.getElementById("slotMachine").classList.add("glow-win");
            document.getElementById("slotMachine").classList.remove("glow-loss");

            showAlert(`🎉 Ganaste $${premio.toFixed(2)}`);

        } else {
            document.getElementById("resultText").textContent =
                "💀 Perdiste";

            document.getElementById("resultText").className =
                "h-16 text-3xl md:text-4xl font-black mb-6 text-red-500 animate-pulse";

            document.getElementById("slotMachine").classList.add("glow-loss");
            document.getElementById("slotMachine").classList.remove("glow-win");

            showAlert("💀 Perdiste");
        }

    } catch (error) {
        console.error(error);
        showAlert("Error en la tirada");
    }

    spinning = false;
    document.getElementById("spinBtn").disabled = false;

    /*
        Si el tiempo terminó durante la tirada,
        al finalizar liquidamos y cerramos sesión automáticamente.
    */
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
        Para devolver el saldo de sesión al saldo real, usa TERMINAR SESIÓN.
    */
    window.location.href = "principal.php";
}

document.addEventListener("DOMContentLoaded", function () {
    checkExistingGameSession();

    document.getElementById("betInput").addEventListener("input", function () {
        const apuesta = getBetAmount();

        if (apuesta > sessionBetLimit) {
            showAlert(`Máximo disponible: $${sessionBetLimit.toFixed(2)}`);
            this.value = sessionBetLimit.toFixed(2);
        }
    });
});
</script>

</body>
</html>