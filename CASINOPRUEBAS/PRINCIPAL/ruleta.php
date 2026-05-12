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

<link rel="icon" type="image/png" href="img/logocuadrado.png">

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
    background:
        radial-gradient(circle at top, rgba(250, 204, 21, 0.16), transparent 30%),
        radial-gradient(circle at center, rgba(127, 29, 29, 0.95), transparent 56%),
        linear-gradient(180deg, #020617, #111827 48%, #020617);
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
    z-index: 0;
    pointer-events: none;
    background:
        radial-gradient(circle at top, rgba(250, 204, 21, 0.12), transparent 35%),
        rgba(0, 0, 0, 0.35);
}

/* HEADER */

.casino-header {
    position: sticky;
    top: 0;
    z-index: 60;
    width: 100%;
    background: rgba(0, 0, 0, 0.78);
    border-bottom: 1px solid rgba(250, 204, 21, 0.25);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(14px);
}

.header-inner {
    width: 100%;
    max-width: 1280px;
    min-height: 76px;
    margin: 0 auto;
    padding: 0.75rem 1rem;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 0.75rem;
    align-items: center;
}

.header-logo {
    height: 60px;
    width: auto;
    max-width: 100px;
    object-fit: contain;
    filter: drop-shadow(0 2px 8px rgba(250, 204, 21, 0.3));
}

.header-left,
.header-center,
.header-right {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.header-left {
    justify-content: flex-start;
}

.header-center {
    justify-content: center;
}

.header-right {
    justify-content: flex-end;
}

.header-btn {
    background: rgba(0, 0, 0, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.75);
    color: white;
    padding: 0.65rem 1rem;
    border-radius: 0.95rem;
    font-weight: 900;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.header-btn:hover {
    background: white;
    color: black;
}

.end-session-btn {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 0.65rem 1rem;
    border-radius: 0.95rem;
    font-weight: 900;
    box-shadow: 0 12px 26px rgba(239, 68, 68, 0.35);
    transition: all 0.2s ease;
    white-space: nowrap;
}

.end-session-btn:hover {
    filter: brightness(1.12);
    transform: translateY(-1px);
}

.session-counter {
    background: linear-gradient(45deg, #10b981, #059669);
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
    padding: 0.65rem 1rem;
    border-radius: 1rem;
    font-weight: 900;
    font-size: 0.95rem;
    line-height: 1.25;
    text-align: center;
    white-space: nowrap;
}

.balance-pill {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    color: black;
    padding: 0.65rem 1rem;
    border-radius: 1rem;
    font-weight: 900;
    font-size: 1rem;
    box-shadow: 0 14px 32px rgba(250, 204, 21, 0.28);
    white-space: nowrap;
}

/* GENERAL */

#casinoAlert {
    max-width: calc(100vw - 1.25rem);
}

.start-card {
    max-height: calc(100dvh - 2rem);
    overflow-y: auto;
}

.game-main {
    position: relative;
    z-index: 10;
    min-height: calc(100dvh - 76px);
    padding: 1.25rem 1rem 2rem;
}

.game-shell {
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
}

.game-title {
    text-align: center;
    color: #facc15;
    font-weight: 900;
    font-size: clamp(2rem, 5vw, 4.5rem);
    line-height: 1;
    margin-bottom: 1.25rem;
    text-shadow: 0 8px 28px rgba(250, 204, 21, 0.35);
}

.game-layout {
    display: grid;
    grid-template-columns: 390px minmax(0, 1fr);
    gap: 1.25rem;
    align-items: start;
}

.control-panel,
.roulette-panel {
    background: linear-gradient(180deg, rgba(24, 24, 27, 0.92), rgba(24, 24, 27, 0.72));
    border: 1px solid rgba(250, 204, 21, 0.28);
    border-radius: 1.5rem;
    box-shadow:
        0 20px 50px rgba(0, 0, 0, 0.45),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
}

.control-panel {
    padding: 1.25rem;
    position: sticky;
    top: 92px;
}

.roulette-panel {
    position: relative;
    overflow: hidden;
    padding: 1.35rem;
}

.panel-title {
    font-size: 1.75rem;
    font-weight: 900;
    color: #facc15;
    text-align: center;
    margin-bottom: 1rem;
}

.info-box {
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    display: grid;
    gap: 0.65rem;
    margin-bottom: 1rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

.board-open-btn,
.clear-btn,
.spin-btn {
    width: 100%;
    border-radius: 1rem;
    font-weight: 900;
    transition: all 0.2s ease;
}

.board-open-btn {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    padding: 1rem;
    font-size: 1.15rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 14px 30px rgba(34, 197, 94, 0.26);
}

.clear-btn {
    background: #3f3f46;
    color: white;
    padding: 0.9rem 1rem;
    font-size: 1rem;
    margin-bottom: 0.75rem;
}

.spin-btn {
    background: linear-gradient(135deg, #facc15, #f97316);
    color: black;
    padding: 1rem;
    font-size: 1.35rem;
    box-shadow: 0 16px 35px rgba(250, 204, 21, 0.35);
}

.board-open-btn:hover:not(:disabled),
.clear-btn:hover:not(:disabled),
.spin-btn:hover:not(:disabled) {
    filter: brightness(1.08);
    transform: translateY(-1px);
}

.spin-btn:disabled,
.clear-btn:disabled,
.board-open-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.result-box {
    min-height: 58px;
    text-align: center;
    font-size: 1.25rem;
    font-weight: 900;
    color: #facc15;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rules-box {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: #d4d4d8;
    line-height: 1.55;
    background: rgba(0, 0, 0, 0.28);
    border-radius: 1rem;
    padding: 1rem;
}

/* RULETA */

.roulette-stage {
    margin-bottom: 1.25rem;
}

.roulette-wheel {
    width: 350px;
    height: 350px;
    border-radius: 50%;
    border: 15px solid #facc15;
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
    box-shadow: 0 0 80px rgba(250, 204, 21, 0.45);
    position: relative;
    margin: 0 auto;
    transition: transform 4s cubic-bezier(.12, .8, .18, 1);
}

.roulette-wheel::after {
    content: "";
    position: absolute;
    inset: 102px;
    border-radius: 50%;
    background: radial-gradient(circle, #7f1d1d, #111827);
    border: 8px solid #facc15;
    z-index: 3;
}

.roulette-center {
    position: absolute;
    inset: 116px;
    border-radius: 50%;
    background: #111827;
    border: 6px solid #facc15;
    z-index: 8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 44px;
}

.wheel-number {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 32px;
    height: 32px;
    margin-left: -16px;
    margin-top: -16px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 900;
    z-index: 5;
    transform:
        rotate(var(--angle))
        translateY(-138px)
        rotate(calc(-1 * var(--angle)));
}

.roulette-pointer {
    width: 0;
    height: 0;
    border-left: 22px solid transparent;
    border-right: 22px solid transparent;
    border-top: 44px solid #facc15;
    margin: 0 auto -18px auto;
    position: relative;
    z-index: 20;
    filter: drop-shadow(0 0 10px rgba(250, 204, 21, 0.8));
}

.last-result {
    text-align: center;
    margin-bottom: 1.25rem;
}

.last-number {
    font-size: 3.7rem;
    line-height: 1;
    font-weight: 900;
    color: #facc15;
}

.board-hint {
    text-align: center;
    color: #d4d4d8;
    font-weight: 700;
    background: rgba(0, 0, 0, 0.28);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1rem;
    padding: 1rem;
}

/* MODAL TABLERO */

.board-modal {
    position: fixed;
    inset: 0;
    z-index: 9990;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.72);
    backdrop-filter: blur(8px);
}

.board-modal.active {
    display: flex;
}

.board-card {
    width: min(1100px, 100%);
    max-height: min(86dvh, 860px);
    overflow-y: auto;
    background: linear-gradient(180deg, rgba(24, 24, 27, 0.97), rgba(39, 39, 42, 0.94));
    border: 2px solid rgba(250, 204, 21, 0.5);
    border-radius: 1.5rem;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.65);
    padding: 1rem;
}

.board-card-header {
    position: sticky;
    top: -1rem;
    z-index: 5;
    background: linear-gradient(180deg, rgba(24, 24, 27, 1), rgba(24, 24, 27, 0.92));
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0 1rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid rgba(250, 204, 21, 0.2);
}

.board-title {
    font-size: 1.8rem;
    font-weight: 900;
    color: #facc15;
}

.close-board-btn {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    background: #ef4444;
    color: white;
    font-size: 1.8rem;
    line-height: 1;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.modal-chips-box {
    background: rgba(0, 0, 0, 0.32);
    border: 1px solid rgba(250, 204, 21, 0.18);
    border-radius: 1rem;
    padding: 0.85rem;
    margin-bottom: 1rem;
}

.modal-chips-title {
    color: #facc15;
    font-weight: 900;
    text-align: center;
    margin-bottom: 0.65rem;
    font-size: 1.05rem;
}

.chips-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.65rem;
}

.modal-chips-row {
    margin-bottom: 0;
}

.chip {
    width: 66px;
    height: 66px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    cursor: grab;
    border: 4px solid rgba(255, 255, 255, 0.75);
    box-shadow:
        0 10px 25px rgba(0, 0, 0, 0.45),
        inset 0 3px 7px rgba(255, 255, 255, 0.32),
        inset 0 -5px 8px rgba(0, 0, 0, 0.25);
    user-select: none;
    transition: all 0.15s ease;
    touch-action: manipulation;
}

.chip:hover {
    transform: scale(1.08);
}

.chip:active,
.chip.selected-chip {
    transform: scale(0.96);
    outline: 4px solid #facc15;
    outline-offset: 2px;
}

.bet-options {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.bet-zone {
    min-height: 86px;
    border-radius: 1rem;
    padding: 1rem;
    font-size: 1.35rem;
    font-weight: 900;
    transition: all 0.15s ease;
}

.drop-zone {
    transition: all 0.15s ease;
}

.drop-zone.drag-over,
.drop-zone.tap-ready {
    outline: 4px dashed #facc15;
    outline-offset: 3px;
    transform: scale(1.035);
    filter: brightness(1.18);
}

.number-title {
    font-size: 1.9rem;
    font-weight: 900;
    color: #facc15;
    text-align: center;
    margin-bottom: 1rem;
}

.number-grid {
    display: grid;
    grid-template-columns: repeat(9, minmax(0, 1fr));
    gap: 0.65rem;
}

.number-cell {
    min-height: 70px;
    transition: all 0.15s ease;
}

.number-cell:hover {
    transform: scale(1.04);
    filter: brightness(1.12);
}

.amount-badge {
    min-height: 22px;
}

.glow-win {
    animation: glowWin 1s ease-in-out infinite alternate;
}

@keyframes glowWin {
    from {
        box-shadow: 0 0 30px rgba(34, 197, 94, 0.45);
    }

    to {
        box-shadow: 0 0 90px rgba(34, 197, 94, 1);
    }
}

.glow-loss {
    animation: glowLoss 1s ease-in-out infinite alternate;
}

@keyframes glowLoss {
    from {
        box-shadow: 0 0 30px rgba(239, 68, 68, 0.45);
    }

    to {
        box-shadow: 0 0 90px rgba(239, 68, 68, 1);
    }
}

/* RESPONSIVE */

@media(max-width: 1100px) {
    .header-inner {
        grid-template-columns: 1fr;
        min-height: auto;
    }

    .header-left,
    .header-center,
    .header-right {
        justify-content: center;
        flex-wrap: wrap;
    }

    .session-counter {
        white-space: normal;
    }

    .game-layout {
        grid-template-columns: 1fr;
    }

    .control-panel {
        position: static;
        order: 1;
    }

    .roulette-panel {
        order: 2;
    }
}

@media(max-width: 768px) {
    .header-inner {
        padding: 0.55rem;
        gap: 0.45rem;
    }

    .header-btn,
    .end-session-btn {
        padding: 0.48rem 0.7rem;
        font-size: 0.82rem;
        border-radius: 0.75rem;
    }

    .session-counter {
        width: 100%;
        font-size: 0.76rem;
        padding: 0.48rem 0.65rem;
        border-radius: 0.85rem;
        line-height: 1.35;
    }

    .balance-pill {
        font-size: 0.88rem;
        padding: 0.48rem 0.85rem;
        border-radius: 0.85rem;
    }

    #casinoAlert {
        top: 0.75rem !important;
        width: calc(100vw - 1.5rem);
        font-size: 0.95rem !important;
        padding: 0.8rem 1rem !important;
    }

    .game-main {
        padding: 0.75rem 0.55rem 1.25rem;
    }

    .game-title {
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }

    .control-panel,
    .roulette-panel {
        border-radius: 1.05rem;
        padding: 0.75rem;
    }

    .panel-title {
        font-size: 1.25rem;
        margin-bottom: 0.65rem;
    }

    .info-box {
        padding: 0.7rem;
        gap: 0.45rem;
        margin-bottom: 0.65rem;
        font-size: 0.86rem;
    }

    .board-open-btn {
        padding: 0.8rem;
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }

    .clear-btn {
        padding: 0.68rem;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .spin-btn {
        padding: 0.82rem;
        font-size: 1rem;
        border-radius: 0.85rem;
    }

    .result-box {
        min-height: 42px;
        font-size: 0.98rem;
        margin-top: 0.65rem;
    }

    .rules-box {
        display: none;
    }

    .roulette-stage {
        margin-bottom: 0.8rem;
    }

    .roulette-wheel {
        width: 245px;
        height: 245px;
        border-width: 10px;
    }

    .roulette-wheel::after {
        inset: 72px;
        border-width: 6px;
    }

    .roulette-center {
        inset: 84px;
        font-size: 30px;
        border-width: 5px;
    }

    .wheel-number {
        width: 24px;
        height: 24px;
        margin-left: -12px;
        margin-top: -12px;
        font-size: 9px;
        transform:
            rotate(var(--angle))
            translateY(-96px)
            rotate(calc(-1 * var(--angle)));
    }

    .roulette-pointer {
        border-left-width: 17px;
        border-right-width: 17px;
        border-top-width: 34px;
        margin-bottom: -14px;
    }

    .last-result {
        margin-bottom: 0.8rem;
    }

    .last-number {
        font-size: 2.6rem;
    }

    #lastColor {
        font-size: 1rem !important;
    }

    .board-hint {
        font-size: 0.85rem;
        padding: 0.75rem;
    }

    .board-modal {
        padding: 0.55rem;
        align-items: flex-end;
    }

    .board-card {
        max-height: 84dvh;
        border-radius: 1.05rem 1.05rem 0 0;
        padding: 0.75rem;
    }

    .board-card-header {
        top: -0.75rem;
        padding: 0.65rem 0 0.8rem;
    }

    .board-title {
        font-size: 1.15rem;
    }

    .close-board-btn {
        width: 38px;
        height: 38px;
        font-size: 1.45rem;
    }

    .modal-chips-box {
        padding: 0.65rem;
        margin-bottom: 0.75rem;
    }

    .modal-chips-title {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .chips-row {
        gap: 0.4rem;
    }

    .chip {
        width: 42px;
        height: 42px;
        font-size: 0.58rem;
        border-width: 3px;
    }

    .bet-options {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
        margin-bottom: 0.9rem;
    }

    .bet-zone {
        min-height: 62px;
        padding: 0.65rem;
        font-size: 0.95rem;
        border-radius: 0.85rem;
    }

    .number-title {
        font-size: 1.15rem;
        margin-bottom: 0.65rem;
    }

    .number-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 0.4rem;
    }

    .number-cell {
        min-height: 48px;
        padding: 0.35rem !important;
        font-size: 0.95rem !important;
        border-radius: 0.7rem !important;
    }

    .amount-badge {
        font-size: 0.65rem !important;
        margin-top: 0.15rem !important;
        min-height: 15px;
    }

    .start-card {
        padding: 1.25rem !important;
        border-radius: 1.25rem !important;
    }
}

@media(max-width: 430px) {
    .roulette-wheel {
        width: 218px;
        height: 218px;
    }

    .roulette-wheel::after {
        inset: 63px;
    }

    .roulette-center {
        inset: 74px;
        font-size: 27px;
    }

    .wheel-number {
        width: 21px;
        height: 21px;
        margin-left: -10.5px;
        margin-top: -10.5px;
        font-size: 8px;
        transform:
            rotate(var(--angle))
            translateY(-85px)
            rotate(calc(-1 * var(--angle)));
    }

    .chip {
        width: 40px;
        height: 40px;
        font-size: 0.55rem;
    }

    .number-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    .number-cell {
        min-height: 45px;
    }
}
</style>
</head>

<body class="text-white">

<div class="page-overlay"></div>

<header class="casino-header">
    <div class="header-inner">

        <div class="header-left">
            <img src="img/logocuadrado.png" alt="Logo" class="header-logo">
            <button onclick="goHome()" class="header-btn">
                🏠 Principal
            </button>
        </div>

        <div class="header-center">
            <div id="sessionCounter" class="session-counter hidden">
                ⏱️ <span id="sessionTime">00:00</span> |
                💰 Sesión: <span id="sessionBankroll">$0</span> |
                <span id="sessionBetLimit">Disponible: $0</span>
            </div>

            <button id="endSessionBtn" onclick="endSession()" class="end-session-btn hidden">
                TERMINAR SESIÓN
            </button>
        </div>

        <div class="header-right">
            <div class="balance-pill">
                💰 Cuenta: <span id="saldoHeader"><?= number_format($saldo, 2) ?></span>$
            </div>
        </div>

    </div>
</header>

<div id="casinoAlert" class="hidden fixed top-24 left-1/2 -translate-x-1/2 z-[99999] bg-red-500 text-white px-6 py-3 rounded-2xl text-xl font-bold shadow-2xl text-center"></div>

<div id="startScreen" class="fixed inset-0 bg-black/95 z-[99999] flex items-center justify-center p-4">
    <div class="start-card bg-zinc-900 border-4 border-yellow-400 rounded-3xl p-8 w-full max-w-md text-center shadow-2xl">
        <div class="text-4xl sm:text-5xl font-black text-yellow-400 mb-6">🎡 RULETA</div>

        <p class="text-zinc-400 mb-2 text-lg">Saldo disponible en cuenta:</p>

        <div class="text-3xl sm:text-4xl font-black mb-6 text-green-400">
            <?= number_format($saldo, 2) ?>$
        </div>

        <input id="bankroll" type="number" placeholder="Dinero para la sesión" min="1" max="<?= htmlspecialchars((string) $saldo) ?>"
               class="w-full bg-zinc-800 rounded-xl p-4 mb-4 text-white outline-none border-2 border-zinc-700 focus:border-yellow-400 text-xl sm:text-2xl text-center font-bold">

        <select id="time" class="w-full bg-zinc-800 rounded-xl p-4 mb-6 text-white border-2 border-zinc-700 text-lg sm:text-xl font-bold">
            <option value="1800">⏱️ 30 minutos</option>
            <option value="3600">⏱️ 1 hora</option>
            <option value="7200">⏱️ 2 horas</option>
        </select>

        <button onclick="confirmSession()" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-400 hover:to-green-500 py-4 sm:py-5 rounded-2xl text-xl sm:text-2xl font-black transition-all duration-300 shadow-2xl border-4 border-green-400">
            🚀 COMENZAR SESIÓN
        </button>
    </div>
</div>

<main id="gameTable" class="game-main hidden">

    <section class="game-shell">

        <h1 class="game-title">
            🎡 RULETA 0 - 26
        </h1>

        <div class="game-layout">

            <aside class="control-panel">

                <h2 class="panel-title">
                    Control de apuesta
                </h2>

                <div class="info-box">
                    <div class="info-row">
                        <span class="text-zinc-400">Total apostado:</span>
                        <span class="font-black text-yellow-400">$<span id="totalBetText">0.00</span></span>
                    </div>

                    <div class="info-row">
                        <span class="text-zinc-400">Disponible sesión:</span>
                        <span class="font-black text-green-400">$<span id="availableText">0.00</span></span>
                    </div>

                    <div class="info-row">
                        <span class="text-zinc-400">Apuestas:</span>
                        <span class="font-black"><span id="betsCountText">0</span></span>
                    </div>
                </div>

                <button type="button" onclick="openBetBoard()" id="openBoardBtn" class="board-open-btn">
                    🎯 ABRIR TABLERO DE APUESTAS
                </button>

                <button type="button" onclick="clearBets()" id="clearBetsBtn" class="clear-btn">
                    LIMPIAR APUESTAS
                </button>

                <button type="button" id="spinBtn" onclick="spinRoulette()" class="spin-btn disabled:opacity-50 disabled:cursor-not-allowed">
                    GIRAR RULETA 🎡
                </button>

                <div id="resultText" class="result-box"></div>

                <div class="rules-box">
                    <p><b class="text-yellow-400">Pulsa:</b> “Abrir tablero de apuestas”.</p>
                    <p><b class="text-yellow-400">Después:</b> selecciona una ficha y toca una casilla.</p>
                    <p><b class="text-yellow-400">Número exacto:</b> paga x26.</p>
                    <p><b class="text-yellow-400">Par / Impar:</b> paga x2.</p>
                    <p><b class="text-yellow-400">Blanco / Negro:</b> paga x2.</p>
                    <p class="mt-2">El número 0 es verde. Solo gana si apuestas al 0.</p>
                </div>

            </aside>

            <section id="rouletteWrapper" class="roulette-panel">

                <div class="roulette-stage">
                    <div class="roulette-pointer"></div>

                    <div id="rouletteWheel" class="roulette-wheel">
                        <div class="roulette-center">🎡</div>
                    </div>
                </div>

                <div class="last-result">
                    <div class="text-zinc-400 font-bold">Resultado</div>
                    <div id="lastNumber" class="last-number">-</div>
                    <div id="lastColor" class="text-2xl font-black text-zinc-300 mt-2">-</div>
                </div>

                <div class="board-hint">
                    Pulsa <b class="text-yellow-400">“Abrir tablero de apuestas”</b> para apostar sobre números, colores, par o impar.
                </div>

            </section>

        </div>

    </section>

</main>

<div id="betBoardModal" class="board-modal" onclick="closeBetBoardOnBackdrop(event)">
    <div class="board-card" onclick="event.stopPropagation()">

        <div class="board-card-header">
            <div>
                <div class="board-title">🎯 Tablero de apuestas</div>
                <div class="text-sm text-zinc-400 font-bold">
                    Selecciona una ficha y luego toca una casilla. También puedes arrastrar.
                </div>
            </div>

            <button type="button" onclick="closeBetBoard()" class="close-board-btn">
                ×
            </button>
        </div>

        <div class="modal-chips-box">
            <div class="modal-chips-title">
                Selecciona una ficha
            </div>

            <div class="chips-row modal-chips-row">
                <div draggable="true" data-value="0.5" class="chip bg-white text-black">$0.5</div>
                <div draggable="true" data-value="1" class="chip bg-yellow-300 text-black">$1</div>
                <div draggable="true" data-value="2" class="chip bg-green-500 text-white">$2</div>
                <div draggable="true" data-value="5" class="chip bg-blue-600 text-white">$5</div>
                <div draggable="true" data-value="10" class="chip bg-red-600 text-white">$10</div>
                <div draggable="true" data-value="25" class="chip bg-purple-600 text-white">$25</div>
            </div>
        </div>

        <div class="bet-options">
            <button type="button" data-bet-key="par" data-type="par" class="drop-zone bet-zone bg-blue-600 hover:bg-blue-500">
                PAR x2
                <div id="amount-par" class="amount-badge text-sm text-yellow-300 mt-2">0.00$</div>
            </button>

            <button type="button" data-bet-key="impar" data-type="impar" class="drop-zone bet-zone bg-purple-600 hover:bg-purple-500">
                IMPAR x2
                <div id="amount-impar" class="amount-badge text-sm text-yellow-300 mt-2">0.00$</div>
            </button>

            <button type="button" data-bet-key="blanco" data-type="color" data-color="blanco" class="drop-zone bet-zone bg-white text-black hover:bg-zinc-200">
                BLANCO x2
                <div id="amount-blanco" class="amount-badge text-sm text-yellow-700 mt-2">0.00$</div>
            </button>

            <button type="button" data-bet-key="negro" data-type="color" data-color="negro" class="drop-zone bet-zone bg-black border-2 border-white hover:bg-zinc-800">
                NEGRO x2
                <div id="amount-negro" class="amount-badge text-sm text-yellow-300 mt-2">0.00$</div>
            </button>
        </div>

        <div>
            <h2 class="number-title">
                Tablero 0 - 26
            </h2>

            <div id="numberGrid" class="number-grid"></div>
        </div>

    </div>
</div>

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
let selectedChipValue = 0;
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

/* ==================== MODAL TABLERO ==================== */

function openBetBoard() {
    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    if (spinning) {
        showAlert("⏳ Espera a que termine la tirada");
        return;
    }

    document.getElementById("betBoardModal").classList.add("active");
}

function closeBetBoard() {
    document.getElementById("betBoardModal").classList.remove("active");
}

function closeBetBoardOnBackdrop(event) {
    if (event.target.id === "betBoardModal") {
        closeBetBoard();
    }
}

/* ==================== API SESIÓN ==================== */

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

/* ==================== SESIÓN ==================== */

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

        closeBetBoard();
        updateBetsDisplay();
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
    updateBetsDisplay();
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

        bets = {};
        selectedChipValue = 0;
        draggedChipValue = 0;

        applySessionState(data);
        updateBetsDisplay();

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

        setTimeout(function () {
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
        minutes.toString().padStart(2, "0") + ":" + seconds.toString().padStart(2, "0");
}

function updateSessionDisplay() {
    document.getElementById("sessionBankroll").textContent = "$" + sessionBankroll.toFixed(2);
    document.getElementById("sessionBetLimit").textContent = "Disponible: $" + sessionBetLimit.toFixed(2);
    document.getElementById("availableText").textContent = sessionBetLimit.toFixed(2);
}

function updateSaldo() {
    document.getElementById("saldoHeader").textContent = saldo.toFixed(2);
}

/* ==================== TABLERO Y RULETA ==================== */

function buildWheelNumbers() {
    const wheel = document.getElementById("rouletteWheel");

    for (let i = 0; i <= 26; i++) {
        const label = document.createElement("div");
        const angle = (360 / 27) * i;

        label.className = "wheel-number " + getNumberClass(i);
        label.style.setProperty("--angle", angle + "deg");
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
        btn.dataset.betKey = "number_" + i;
        btn.dataset.type = "number";
        btn.dataset.value = i;

        btn.className = "drop-zone number-cell rounded-2xl p-4 text-2xl font-black transition " + getNumberClass(i);

        const badgeColor = i === 0 || getNumberColor(i) === "negro" ? "text-yellow-300" : "text-yellow-700";

        btn.innerHTML =
            '<div>' + i + '</div>' +
            '<div id="amount-number_' + i + '" class="amount-badge text-sm mt-2 ' + badgeColor + '">0.00$</div>';

        grid.appendChild(btn);
    }
}

function setupDragAndDrop() {
    document.querySelectorAll(".chip").forEach(function (chip) {
        chip.addEventListener("dragstart", function (event) {
            draggedChipValue = Number(this.dataset.value || 0);
            event.dataTransfer.setData("text/plain", draggedChipValue);
        });

        chip.addEventListener("click", function () {
            if (spinning) {
                showAlert("⏳ Espera a que termine la tirada");
                return;
            }

            selectedChipValue = Number(this.dataset.value || 0);

            document.querySelectorAll(".chip").forEach(function (c) {
                c.classList.remove("selected-chip");
            });

            this.classList.add("selected-chip");

            document.querySelectorAll(".drop-zone").forEach(function (zone) {
                zone.classList.add("tap-ready");
            });

            showAlert("Ficha seleccionada: $" + selectedChipValue);
        });
    });

    document.querySelectorAll(".drop-zone").forEach(function (zone) {
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

        zone.addEventListener("click", function () {
            if (spinning) {
                showAlert("⏳ Espera a que termine la tirada");
                return;
            }

            if (selectedChipValue <= 0) {
                showAlert("Selecciona una ficha primero");
                return;
            }

            addBetToZone(this, selectedChipValue);
        });
    });
}

function addBetToZone(zone, amount) {
    if (!sessionActive) {
        showAlert("🎮 Inicia sesión primero");
        return;
    }

    const totalBefore = getTotalBet();

    if (totalBefore + amount > sessionBetLimit) {
        showAlert("No puedes superar el disponible: $" + sessionBetLimit.toFixed(2));
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

    Object.values(bets).forEach(function (bet) {
        total += Number(bet.amount || 0);
    });

    return Math.round(total * 100) / 100;
}

function updateBetsDisplay() {
    const total = getTotalBet();
    const count = Object.keys(bets).length;

    document.getElementById("totalBetText").textContent = total.toFixed(2);
    document.getElementById("betsCountText").textContent = count;

    if (document.getElementById("availableText")) {
        document.getElementById("availableText").textContent = sessionBetLimit.toFixed(2);
    }

    document.querySelectorAll(".amount-badge").forEach(function (el) {
        el.textContent = "0.00$";
    });

    Object.keys(bets).forEach(function (key) {
        const amountEl = document.getElementById("amount-" + key);

        if (amountEl) {
            amountEl.textContent = bets[key].amount.toFixed(2) + "$";
        }
    });
}

function clearBets(force = false) {
    if (spinning && !force) {
        showAlert("⏳ Espera a que termine la tirada");
        return;
    }

    bets = {};
    updateBetsDisplay();
}

/* ==================== RESULTADO ==================== */

function getRandomRouletteNumber() {
    return Math.floor(Math.random() * 27);
}

function calculateRoulettePayout(resultNumber) {
    let totalPayout = 0;
    let winningBets = [];

    const resultColor = getNumberColor(resultNumber);

    Object.keys(bets).forEach(function (key) {
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
    return new Promise(function (resolve) {
        const wheel = document.getElementById("rouletteWheel");

        const degreesPerNumber = 360 / 27;
        const targetAngle = resultNumber * degreesPerNumber;

        wheelRotation += 360 * 6 + (360 - targetAngle);
        wheel.style.transform = "rotate(" + wheelRotation + "deg)";

        setTimeout(resolve, 4200);
    });
}

function paintLastNumber(resultNumber) {
    const last = document.getElementById("lastNumber");
    const color = getNumberColor(resultNumber);
    const lastColor = document.getElementById("lastColor");

    last.textContent = resultNumber;

    if (resultNumber === 0) {
        last.className = "last-number text-green-400";
        lastColor.textContent = "VERDE";
        lastColor.className = "text-2xl font-black text-green-400 mt-2";
    } else if (color === "blanco") {
        last.className = "last-number text-white";
        lastColor.textContent = "BLANCO";
        lastColor.className = "text-2xl font-black text-white mt-2";
    } else {
        last.className = "last-number text-zinc-900 bg-white rounded-2xl inline-block px-5";
        lastColor.textContent = "NEGRO";
        lastColor.className = "text-2xl font-black text-zinc-200 mt-2";
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
        showAlert("Selecciona una ficha y apuesta en el tablero");
        return;
    }

    if (totalBet > sessionBetLimit) {
        showAlert("Máximo disponible: $" + sessionBetLimit.toFixed(2));
        return;
    }

    closeBetBoard();

    spinning = true;

    document.getElementById("spinBtn").disabled = true;
    document.getElementById("clearBetsBtn").disabled = true;
    document.getElementById("openBoardBtn").disabled = true;

    document.getElementById("resultText").textContent = "Girando...";
    document.getElementById("resultText").className = "result-box text-yellow-400";

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
            document.getElementById("openBoardBtn").disabled = false;

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
            document.getElementById("openBoardBtn").disabled = false;

            showAlert(settleData.message || "Error guardando resultado");
            return;
        }

        applySessionState(settleData);

        const colorTexto = resultNumber === 0 ? "VERDE" : resultColor.toUpperCase();

        if (totalPayout > 0) {
            document.getElementById("resultText").textContent =
                "🎉 Salió " + resultNumber + " " + colorTexto + ". Ganaste $" + totalPayout.toFixed(2);

            document.getElementById("resultText").className = "result-box text-green-400 animate-pulse";

            document.getElementById("rouletteWrapper").classList.add("glow-win");
            document.getElementById("rouletteWrapper").classList.remove("glow-loss");

            showAlert("🎉 Ganaste $" + totalPayout.toFixed(2));
        } else {
            document.getElementById("resultText").textContent =
                "💀 Salió " + resultNumber + " " + colorTexto + ". Perdiste $" + totalBet.toFixed(2);

            document.getElementById("resultText").className = "result-box text-red-500 animate-pulse";

            document.getElementById("rouletteWrapper").classList.add("glow-loss");
            document.getElementById("rouletteWrapper").classList.remove("glow-win");

            showAlert("💀 Perdiste");
        }

        clearBets(true);

    } catch (error) {
        console.error(error);
        showAlert("Error en la ruleta");
    }

    spinning = false;

    document.getElementById("spinBtn").disabled = false;
    document.getElementById("clearBetsBtn").disabled = false;
    document.getElementById("openBoardBtn").disabled = false;

    if (sessionExpiredAlertShown) {
        setTimeout(function () {
            endSession(true);
        }, 1000);
    }
}

/* ==================== UTILIDADES ==================== */

function showAlert(msg) {
    const alertBox = document.getElementById("casinoAlert");

    alertBox.innerText = msg;
    alertBox.classList.remove("hidden");

    setTimeout(function () {
        alertBox.classList.add("hidden");
    }, 3000);
}

function goHome() {
    if (spinning) {
        showAlert("⏳ Espera a que termine la tirada");
        return;
    }

    window.location.href = "principal.php";
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeBetBoard();
    }
});

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