
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
* {
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

body {
    min-height: 100dvh;
    background-color: #06140c;
    background-image:
        radial-gradient(circle at top, rgba(250, 204, 21, 0.18), transparent 28%),
        linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.75)),
        url("img/mesablackjack.png");
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    color: white;
}

button,
input,
select {
    font-family: inherit;
}

button {
    -webkit-tap-highlight-color: transparent;
}

.page-overlay {
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at top center, rgba(255, 215, 0, 0.15), transparent 35%),
        radial-gradient(circle at bottom center, rgba(34, 197, 94, 0.18), transparent 35%),
        rgba(0, 0, 0, 0.40);
    pointer-events: none;
    z-index: 0;
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
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

.stats-btn {
    background: rgba(0, 0, 0, 0.7);
    border: 2px solid #fbbf24;
    transition: all 0.3s;
}

.stats-btn:hover {
    background: #fbbf24;
    color: black;
    box-shadow: 0 0 20px rgba(251, 191, 36, 0.6);
}

.modal-overlay {
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
}

.main-header {
    position: sticky;
    top: 0;
    left: 0;
    width: 100%;
    min-height: 80px;
    z-index: 50;
    background: rgba(0, 0, 0, 0.72);
    border-bottom: 1px solid rgba(250, 204, 21, 0.25);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.45);
}

.game-container {
    min-height: calc(100dvh - 88px);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
}

.score-row {
    width: 100%;
    max-width: 1050px;
    margin: 0 auto;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 1rem;
    padding: 0.8rem 1.2rem !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
}

.game-board {
    flex: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px minmax(0, 1fr);
    align-items: start;
    gap: 1rem;
    max-width: 1180px;
    margin: 0 auto;
    width: 100%;
}

.dealer-section,
.player-section,
.center-section {
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: linear-gradient(
        180deg,
        rgba(10, 10, 10, 0.58),
        rgba(10, 10, 10, 0.28)
    );
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 1.5rem;
    padding: 1rem;
    box-shadow:
        0 18px 45px rgba(0, 0, 0, 0.45),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
}

.dealer-section {
    align-items: flex-start;
}

.player-section {
    align-items: flex-end;
}

.center-section {
    align-items: center;
    gap: 0.65rem;
    border-color: rgba(250, 204, 21, 0.32);
}

.dealer-section h2,
.player-section h2 {
    color: #facc15;
    text-shadow: 0 3px 15px rgba(250, 204, 21, 0.4);
    margin-bottom: 0.75rem;
}

.cards-container {
    width: 100%;
    min-height: 150px;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 0.7rem;
    align-items: center;
    justify-content: center;
    padding: 0.8rem;
    border-radius: 1.25rem;
    background: rgba(0, 0, 0, 0.28);
    border: 1px dashed rgba(255, 255, 255, 0.16);
}

.card {
    width: 84px;
    height: 122px;
    border-radius: 0.8rem;
    flex: 0 0 auto;
    transform-origin: bottom center;
    box-shadow:
        0 12px 25px rgba(0, 0, 0, 0.55),
        0 0 0 1px rgba(255, 255, 255, 0.18);
}

.card img {
    border-radius: 0.8rem;
}

.result-text,
.bottom-result {
    width: 100%;
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-shadow: 0 4px 18px rgba(0, 0, 0, 0.7);
    line-height: 1.15;
}

.drop-zone {
    position: relative;
    isolation: isolate;
    background:
        radial-gradient(circle, rgba(34, 197, 94, 0.18), rgba(0, 0, 0, 0.42)),
        rgba(0, 0, 0, 0.3);
    box-shadow:
        0 0 45px rgba(34, 197, 94, 0.45),
        inset 0 0 35px rgba(34, 197, 94, 0.16);
}

.drop-zone::before {
    content: "";
    position: absolute;
    inset: 12px;
    border-radius: inherit;
    border: 1px solid rgba(255, 255, 255, 0.13);
    pointer-events: none;
}

.drop-zone:hover {
    transform: translateY(-2px) scale(1.02);
}

#dropZone.bet-active {
    border-color: #22c55e;
    box-shadow:
        0 0 55px rgba(34, 197, 94, 0.85),
        inset 0 0 35px rgba(34, 197, 94, 0.2);
}

#dropZone.bet-max {
    border-color: #ef4444;
    box-shadow:
        0 0 55px rgba(239, 68, 68, 0.82),
        inset 0 0 35px rgba(239, 68, 68, 0.18);
}

.chips-container {
    width: 100%;
    max-width: 460px;
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    justify-content: center;
    padding: 0.75rem;
    border-radius: 1.25rem;
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.10);
}

.chip {
    touch-action: manipulation;
    user-select: none;
    box-shadow:
        0 10px 25px rgba(0, 0, 0, 0.45),
        inset 0 3px 7px rgba(255, 255, 255, 0.35),
        inset 0 -5px 8px rgba(0, 0, 0, 0.25);
}

.chip:active {
    transform: scale(0.94);
}

.bottom-section {
    width: 100%;
    max-width: 460px;
    margin: -0.2rem auto 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.45rem;
    padding-top: 0;
}

.action-buttons {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-casino {
    border: 0;
    color: white;
    font-weight: 900;
    border-radius: 1.25rem;
    transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.45);
}

.btn-casino:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.02);
    filter: brightness(1.08);
}

.btn-casino:active:not(:disabled) {
    transform: scale(0.97);
}

.btn-deal {
    width: 100%;
    padding: 0.95rem 1.5rem;
    font-size: 1.55rem;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    border: 3px solid rgba(147, 197, 253, 0.75);
    box-shadow:
        0 16px 35px rgba(37, 99, 235, 0.38),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

.btn-hit,
.btn-stand {
    flex: 1;
    min-width: 135px;
    padding: 0.9rem 1rem;
    font-size: 1.25rem;
}

.btn-hit {
    background: linear-gradient(135deg, #eab308, #f59e0b);
    color: #111827;
    border: 3px solid rgba(254, 240, 138, 0.75);
}

.btn-stand {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: 3px solid rgba(252, 165, 165, 0.75);
}

.start-card {
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
}

@media(max-width: 1100px) {
    .game-board {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .center-section {
        order: 1;
    }

    .dealer-section {
        order: 2;
        align-items: center;
    }

    .player-section {
        order: 3;
        align-items: center;
    }

    .cards-container {
        min-height: 120px;
    }
}

@media(max-width: 768px) {
    body {
        background-attachment: scroll;
    }

    .main-header {
        min-height: auto;
    }

    .header-inner {
        flex-direction: column;
        height: auto !important;
        padding: 0.55rem 0.35rem;
        gap: 0.45rem;
    }

    .header-left,
    .header-buttons,
    .header-balance {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    .header-left button,
    .header-buttons button {
        padding: 0.48rem 0.7rem;
        font-size: 0.82rem;
        border-radius: 0.75rem;
    }

    .session-counter {
        width: 100%;
        text-align: center;
        font-size: 0.76rem;
        line-height: 1.35;
        padding: 0.48rem 0.65rem;
        border-radius: 0.9rem;
    }

    .header-balance > div {
        font-size: 0.88rem !important;
        padding: 0.48rem 0.85rem !important;
        border-radius: 0.9rem !important;
    }

    .game-container {
        min-height: auto;
        padding: 0.55rem;
        gap: 0.55rem;
    }

    .score-row {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.25rem;
        font-size: 0.86rem !important;
        padding: 0.5rem !important;
    }

    .dealer-section,
    .player-section,
    .center-section {
        padding: 0.55rem;
        border-radius: 1rem;
    }

    .center-section {
        gap: 0.42rem;
    }

    .dealer-section h2,
    .player-section h2 {
        font-size: 1rem !important;
        margin-bottom: 0.35rem;
    }

    .result-text {
        min-height: 28px;
        font-size: 1rem !important;
        margin-bottom: 0 !important;
    }

    .bottom-result {
        min-height: 24px;
        font-size: 1rem !important;
        margin-bottom: 0 !important;
    }

    #dropZone {
        width: min(58vw, 180px) !important;
        height: min(58vw, 180px) !important;
        padding: 0.7rem !important;
        border-width: 4px !important;
    }

    #dropZone .text-2xl {
        font-size: 0.95rem !important;
        margin-bottom: 0.35rem !important;
    }

    #dropZone .text-5xl {
        font-size: 1.65rem !important;
        margin-bottom: 0.35rem !important;
    }

    #dropZone #betLimit {
        font-size: 0.75rem !important;
    }

    .bottom-section {
        max-width: 100%;
        gap: 0.35rem;
        margin-top: -0.15rem;
        padding-top: 0;
    }

    .action-buttons {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 0.45rem;
        width: 100%;
    }

    .btn-deal {
        width: 100%;
        padding: 0.75rem 1rem !important;
        font-size: 1.1rem !important;
        border-radius: 0.9rem !important;
    }

    .btn-hit,
    .btn-stand {
        flex: 1;
        width: auto !important;
        min-width: 0 !important;
        padding: 0.75rem 0.45rem !important;
        font-size: 0.92rem !important;
        border-radius: 0.85rem !important;
        white-space: nowrap;
    }

    .chips-container {
        margin-top: 0.1rem;
        gap: 0.35rem;
        max-width: 100%;
        padding: 0.45rem;
    }

    .chip {
        width: 44px !important;
        height: 44px !important;
        font-size: 0.62rem !important;
        border-width: 3px !important;
    }

    .card {
        width: 50px;
        height: 74px;
    }

    .cards-container {
        min-height: 86px;
        gap: 0.35rem;
        padding: 0.45rem;
    }

    #casinoAlert {
        top: 0.75rem !important;
        font-size:```php
        0.95rem !important;
        padding: 0.8rem 1rem !important;
        width: calc(100vw - 1.5rem);
    }
}

@media(max-width: 430px) {
    .game-container {
        padding: 0.45rem;
    }

    #dropZone {
        width: 160px !important;
        height: 160px !important;
    }

    #dropZone .text-2xl {
        font-size: 0.9rem !important;
    }

    #dropZone .text-5xl {
        font-size: 1.55rem !important;
    }

    .btn-deal {
        padding: 0.65rem 0.85rem !important;
        font-size: 0.98rem !important;
    }

    .btn-hit,
    .btn-stand {
        padding: 0.65rem 0.35rem !important;
        font-size: 0.82rem !important;
    }

    .chip {
        width: 39px !important;
        height: 39px !important;
        font-size: 0.55rem !important;
    }

    .card {
        width: 46px;
        height: 68px;
    }

    .result-text {
        font-size: 0.95rem !important;
    }

    .bottom-result {
        font-size: 0.95rem !important;
    }

    .score-row {
        font-size: 0.8rem !important;
    }
}
</style>
</head>

<body class="text-white">

<div class="page-overlay"></div>

<header class="main-header bg-black/70 backdrop-blur-md px-4">
    <div class="header-inner max-w-7xl mx-auto min-h-20 flex flex-col sm:flex-row items-center justify-between gap-2 py-2">

        <div class="header-left flex items-center gap-2">
            <button onclick="goHome()" class="bg-black border border-white px-4 py-2 rounded-xl hover:bg-white hover:text-black transition font-bold">
                🏠 Principal
            </button>

            <div id="sessionCounter" class="session-counter px-4 py-2 rounded-2xl text-base lg:text-lg font-black hidden">
                ⏱️ <span id="sessionTime">00:00</span> |
                💰 Sesión: <span id="sessionBankroll">$0</span> |
                <span id="sessionBetLimit">Disponible: $0</span>
            </div>
        </div>

        <div class="flex items-center gap-2 header-buttons">
            <button id="endSessionBtn" onclick="endSession()"
                    class="bg-red-500 hover:bg-red-400 px-4 py-2 rounded-xl font-bold text-base lg:text-lg transition shadow-lg hidden">
                TERMINAR SESIÓN
            </button>

            <button id="statsBtn" onclick="toggleStats()"
                    class="stats-btn px-3 py-2 rounded-xl font-bold text-sm transition shadow-lg hidden">
                📊 STATS
            </button>
        </div>

        <div class="header-balance flex justify-center">
            <div class="bg-yellow-400 text-black px-5 py-2 rounded-2xl font-black text-base lg:text-lg shadow-2xl">
                💰 Cuenta: <span id="saldoHeader"><?= number_format($saldo, 2) ?></span>$
            </div>
        </div>

    </div>
</header>

<div id="casinoAlert" class="hidden fixed top-24 left-1/2 -translate-x-1/2 z-[99999] bg-red-500 text-white px-6 py-3 rounded-2xl text-lg sm:text-xl font-bold shadow-2xl max-w-[calc(100vw-1rem)] text-center"></div>

<div id="statsModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
    <div class="modal-overlay absolute inset-0" onclick="toggleStats()"></div>

    <div class="bg-zinc-900 border-4 border-yellow-500 rounded-3xl p-5 sm:p-8 w-full max-w-md max-h-[80vh] overflow-y-auto relative shadow-2xl">
        <button onclick="toggleStats()" class="absolute top-4 right-4 text-3xl font-bold text-white hover:text-yellow-400">×</button>

        <h2 class="text-2xl sm:text-3xl font-black text-yellow-400 text-center mb-6">📊 ESTADÍSTICAS SESIÓN</h2>

        <div class="space-y-4 text-base sm:text-lg">
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

<div id="startScreen" class="fixed inset-0 bg-black/95 z-[99999] flex items-center justify-center p-4">
    <div class="start-card bg-zinc-900 border-4 border-yellow-400 rounded-3xl p-5 sm:p-8 w-full max-w-md text-center shadow-2xl">
        <div class="text-4xl sm:text-5xl font-black text-yellow-400 mb-6">🎰 CASINO LIVE</div>

        <p class="text-zinc-400 mb-2 text-lg">Saldo disponible en cuenta:</p>
        <div class="text-3xl sm:text-4xl font-black mb-6 text-green-400"><?= number_format($saldo, 2) ?>$</div>

        <input id="bankroll" type="number" placeholder="Dinero para la sesión" min="1" max="<?= htmlspecialchars((string) $saldo) ?>"
               class="w-full bg-zinc-800 rounded-xl p-4 mb-4 text-white outline-none border-2 border-zinc-700 focus:border-yellow-400 text-xl sm:text-2xl text-center font-bold">

        <select id="time" class="w-full bg-zinc-800 rounded-xl p-4 mb-6 text-white border-2 border-zinc-700 text-lg sm:text-xl font-bold">
            <option value="1800">⏱️ 30 minutos</option>
            <option value="3600">⏱️ 1 hora</option>
            <option value="7200">⏱️ 2 horas</option>
        </select>

        <button onclick="confirmSession()" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-400 hover:to-green-500 py-4 sm:py-5 rounded-2xl text-xl sm:text-2xl font-black transition-all duration-300 shadow-2xl border-4 border-green-400 transform hover:scale-105">
            🚀 COMENZAR SESIÓN
        </button>

        <p id="error" class="text-red-500 mt-4 font-bold text-lg hidden"></p>
    </div>
</div>

<main id="gameTable" class="relative z-10 hidden">
    <div class="game-container">

        <div class="score-row flex justify-between px-8 text-xl font-bold">
            <div id="dealerScoreTop">🃏 CRUPIER: ?</div>
            <div id="playerScoreTop">👤 JUGADOR: ?</div>
        </div>

        <div class="game-board">

            <div class="dealer-section">
                <h2 class="text-2xl font-black mb-4 text-center lg:text-left">🃏 CRUPIER</h2>
                <div id="dealerCards" class="cards-container"></div>
            </div>

            <div class="center-section">
                <div id="gameResult" class="result-text text-3xl font-black text-center"></div>

                <div id="dropZone" class="drop-zone w-72 h-72 rounded-full border-8 border-dashed border-green-400 flex flex-col items-center justify-center text-center transition-all duration-300 cursor-pointer p-8">
                    <div class="text-2xl font-black mb-4">APUESTA</div>

                    <div class="text-5xl font-black text-yellow-400 mb-4">
                        $<span id="betAmount">0</span>
                    </div>

                    <div id="betLimit" class="text-lg text-zinc-300 font-bold">Disponible: $0</div>
                </div>

                <div class="bottom-section">
                    <div id="bottomResult" class="bottom-result text-4xl font-black text-center"></div>

                    <div class="action-buttons">
                        <button id="dealBtn" onclick="deal()" class="btn-casino btn-deal disabled:opacity-50 disabled:cursor-not-allowed">
                            🎲 REPARTIR
                        </button>

                        <button id="hitBtn" onclick="hit()" class="btn-casino btn-hit disabled:opacity-50 disabled:cursor-not-allowed hidden">
                            HIT ➕
                        </button>

                        <button id="standBtn" onclick="stand()" class="btn-casino btn-stand disabled:opacity-50 disabled:cursor-not-allowed hidden">
                            STAND 🛑
                        </button>
                    </div>
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
                <h2 class="text-2xl font-black mb-4 text-center lg:text-right">👤 JUGADOR</h2>
                <div id="playerCards" class="cards-container"></div>
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
    const response = await fetch("api_sesion_juego.php?action=" + encodeURIComponent(action), {
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

        if (!gameActive && !gameFinished)        {
            endSession(true);
        }

        return;
    }

    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;

    document.getElementById("sessionTime").textContent =
        minutes.toString().padStart(2, "0") + ":" + seconds.toString().padStart(2, "0");
}

function updateSessionDisplay() {
    document.getElementById("sessionBankroll").textContent = "$" + sessionBankroll.toFixed(2);
    document.getElementById("sessionBetLimit").textContent = "Disponible: $" + sessionBetLimit.toFixed(2);
}

function updateBetDisplay() {
    document.getElementById("betAmount").textContent = bet.toFixed(2);
    document.getElementById("betLimit").textContent = "Disponible: $" + sessionBetLimit.toFixed(2);

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
    document.querySelectorAll(".chip").forEach(function (chip) {
        chip.addEventListener("dragstart", function (e) {
            e.dataTransfer.setData("value", chip.dataset.value);
        });

        chip.addEventListener("click", function () {
            addChipValue(parseFloat(chip.dataset.value));
        });
    });

    const dropZone = document.getElementById("dropZone");
    dropZone.addEventListener("dragover", function (e) {
        e.preventDefault();
    });
    dropZone.addEventListener("drop", handleDrop);

    checkExistingGameSession();
});

function addChipValue(value) {
    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    if (gameActive || gameFinished) {
        showAlert("⏳ Termina la mano actual");
        return;
    }

    if (isNaN(value) || value <= 0) {
        showAlert("Ficha inválida");
        return;
    }

    const newBet = bet + value;

    if (newBet > sessionBetLimit) {
        showAlert("💰 Máximo disponible $" + sessionBetLimit.toFixed(2));
        return;
    }

    bet = newBet;
    updateBetDisplay();
}

function handleDrop(e) {
    e.preventDefault();

    const value = parseFloat(e.dataTransfer.getData("value"));
    addChipValue(value);
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
    document.getElementById("totalBets").textContent = "$" + stats.totalBets.toFixed(2);
    document.getElementById("totalWins").textContent = "$" + stats.totalWins.toFixed(2);
    document.getElementById("totalLosses").textContent = "$" + stats.totalLosses.toFixed(2);

    const net = stats.totalWins - stats.totalLosses;

    document.getElementById("netProfit").textContent = "$" + net.toFixed(2);
    document.getElementById("netProfit").className =
        "text-3xl font-black " + (net >= 0 ? "text-green-400" : "text-red-500");
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

    return values[Math.floor(Math.random() * values.length)] + "-" +
        suits[Math.floor(Math.random() * suits.length)] + ".png";
}

function createCard(src) {
    return '<div class="card deal-anim">' +
        '<img src="img/cards/' + src + '" class="w-full h-full object-cover rounded-lg" alt="Carta de blackjack">' +
        '</div>';
}

function updateScores() {
    const playerValue = getHandValue(player);
    const dealerValue = getHandValue(dealer);

    document.getElementById("playerScoreTop").textContent = "👤 JUGADOR: " + playerValue;
    document.getElementById("dealerScoreTop").textContent = "🃏 CRUPIER: " + dealerValue;
}

function renderCards() {
    document.getElementById("playerCards").innerHTML = player.map(function (c) {
        return createCard(c);
    }).join("");

    document.getElementById("dealerCards").innerHTML = dealer.map(function (c) {
        return createCard(c);
    }).join("");

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

    document.getElementById("gameResult").className = "result-text text-3xl font-black text-center";
    document.getElementById("bottomResult").className = "bottom-result text-4xl font-black text-center";

    document.getElementById("dealBtn").disabled = !sessionActive;
    document.getElementById("dealBtn").classList.remove("hidden");

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
        showAlert("❌ Máximo disponible: $" + sessionBetLimit.toFixed(2));
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
    document.getElementById("dealBtn").classList.add("hidden");

    document.getElementById("hitBtn").disabled = false;
    document.getElementById("hitBtn").classList.remove("hidden");

    document.getElementById("standBtn").disabled = false;
    document.getElementById("standBtn").classList.remove("hidden");

    renderCards();

    setTimeout(function () {
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

    setTimeout(function () {
        finishGame();
    }, 800);
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
        finalResult = "💀 PASASTE (" + playerValue + ")";
        resultForServer = "loss";
        multiplier = 0;

    } else if (dealerValue > 21) {
        finalResult = "🎉 DEALER PASÓ (" + dealerValue + ")!";
        resultForServer = "win";
        multiplier = isBlackjack(player) ? 2.5 : 2;

    } else if (playerValue > dealerValue) {
        finalResult = "🎉 ¡VICTORIA! " + playerValue + " vs " + dealerValue;
        resultForServer = "win";
        multiplier = isBlackjack(player) ? 2.5 : 2;

    } else if (playerValue < dealerValue) {
        finalResult = "💀 DERROTA " + playerValue + " vs " + dealerValue;
        resultForServer = "loss";
        multiplier = 0;

    } else {
        finalResult = "🤝 EMPATE " + playerValue;
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
        document.getElementById("gameResult").className = "result-text text-3xl font-black text-green-400 animate-pulse text-center";

        document.getElementById("bottomResult").innerHTML = "¡GANASTE!";
        document.getElementById("bottomResult").className = "bottom-result text-4xl font-black text-green-400 animate-pulse text-center";

        updateStats("win", bet, won);

    } else if (resultForServer === "loss") {
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "result-text text-3xl font-black text-red-500 animate-pulse text-center";

        document.getElementById("bottomResult").innerHTML = "¡PERDISTE!";
        document.getElementById("bottomResult").className = "bottom-result text-4xl font-black text-red-500 animate-pulse text-center";

        updateStats("loss", bet);

    } else {
        document.getElementById("gameResult").innerHTML = finalResult;
        document.getElementById("gameResult").className = "result-text text-3xl font-black text-yellow-400 animate-pulse text-center";

        document.getElementById("bottomResult").innerHTML = "¡EMPATE!";
        document.getElementById("bottomResult").className = "bottom-result text-4xl font-black text-yellow-400 animate-pulse text-center";

        updateStats("tie", bet);
    }

    setTimeout(function () {
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

    setTimeout(function () {
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

    window.location.href = "principal.php";
}
</script>

</body>
</html>