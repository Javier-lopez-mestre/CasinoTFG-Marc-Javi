<?php
require_once __DIR__ . '/session_helpers.php';
include("conexion.php");

init_session();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = (int) $_SESSION['id_usuario'];

function escaparPrincipal($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function dineroPrincipal($valor) {
    return number_format((float) $valor, 2);
}

function columnaExistePrincipal($conexion, $tabla, $columna) {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $tabla, $columna);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return ((int) ($row['total'] ?? 0)) > 0;
}

/*
    Asegurar columnas necesarias.
*/
if (!columnaExistePrincipal($conexion, "usuarios", "estado")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo'
    ");
}

if (!columnaExistePrincipal($conexion, "usuarios", "usuario")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN usuario VARCHAR(100) NULL
    ");
}

if (!columnaExistePrincipal($conexion, "usuarios", "nombre")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN nombre VARCHAR(100) DEFAULT 'Usuario Anónimo'
    ");
}

if (!columnaExistePrincipal($conexion, "usuarios", "email")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN email VARCHAR(100) NULL
    ");
}

/*
    Cargar usuario y comprobar si está bloqueado.
*/
$sql = "
    SELECT 
        id_usuario,
        nombre_usuario,
        usuario,
        nombre,
        email,
        saldo,
        estado
    FROM usuarios
    WHERE id_usuario = ?
    LIMIT 1
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error preparando usuario: " . $conexion->error);
}

$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$resultado = $stmt->get_result();
$datos_usuario = $resultado->fetch_assoc();

if (!$datos_usuario) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$estadoUsuario = $datos_usuario['estado'] ?? 'activo';

if ($estadoUsuario === 'suspendido' || $estadoUsuario === 'bloqueado') {
    session_destroy();
    header("Location: index.php?error=cuenta_bloqueada");
    exit();
}

if ($estadoUsuario === 'inactivo') {
    session_destroy();
    header("Location: index.php?error=cuenta_inactiva");
    exit();
}

$saldo = isset($datos_usuario['saldo']) ? (float) $datos_usuario['saldo'] : 0;

if (!empty($datos_usuario['nombre'])) {
    $nombreMostrado = $datos_usuario['nombre'];
} elseif (!empty($datos_usuario['usuario'])) {
    $nombreMostrado = $datos_usuario['usuario'];
} elseif (!empty($datos_usuario['nombre_usuario'])) {
    $nombreMostrado = $datos_usuario['nombre_usuario'];
} elseif (!empty($datos_usuario['email'])) {
    $nombreMostrado = $datos_usuario['email'];
} else {
    $nombreMostrado = "Jugador";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>HIGH STAKES</title>

<link rel="icon" type="image/png" href="img/logocuadrado.png">
<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<!-- bxSlider -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bxslider@4.2.17/dist/jquery.bxslider.min.css">
<script src="https://cdn.jsdelivr.net/npm/bxslider@4.2.17/dist/jquery.bxslider.min.js"></script>

<style>
* {
    box-sizing: border-box;
}

html,
body {
    max-width: 100%;
    overflow-x: hidden;
}

body {
    overflow-x: hidden;
    background: #020617;
}

img,
video {
    max-width: 100%;
}

/* Fondo */
#casino-bg {
    position: fixed;
    inset: 0;
    background-image: url('img/casino-bg.jpg');
    background-size: cover;
    background-position: center;
    z-index: -3;
    transform: scale(1.03);
}

#casino-overlay {
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at top, rgba(250, 204, 21, 0.20), transparent 32%),
        radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.18), transparent 30%),
        linear-gradient(to bottom, rgba(0,0,0,0.62), rgba(2,6,23,0.96));
    z-index: -2;
}

#casino-pattern {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
    background-size: 55px 55px;
    mask-image: linear-gradient(to bottom, rgba(0,0,0,0.55), transparent);
    z-index: -1;
}

/* bxSlider */
.bx-wrapper,
.bx-viewport {
    position: relative !important;
    z-index: 1 !important;
}

.bx-wrapper {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    margin-bottom: 0 !important;
}

.bx-wrapper img {
    width: 100%;
    border-radius: 24px;
}

/* Dropdowns por encima de todo */
#ingresar-container,
#perfil-container {
    position: relative;
    z-index: 999999 !important;
}

#ingresar-menu,
#perfil-menu {
    position: absolute !important;
    z-index: 99999999 !important;
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

header {
    position: relative;
    z-index: 99999;
}

.game-card {
    position: relative;
    overflow: hidden;
    isolation: isolate;
}

.game-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 35%, rgba(0,0,0,0.82));
    z-index: 1;
    pointer-events: none;
}

.game-card::after {
    content: "";
    position: absolute;
    inset: -2px;
    border-radius: 24px;
    background: linear-gradient(135deg, rgba(250,204,21,0.65), transparent, rgba(59,130,246,0.45));
    opacity: 0;
    z-index: -1;
    transition: opacity 0.25s ease;
}

.game-card:hover::after {
    opacity: 1;
}

.game-card img {
    transition: transform 0.35s ease, filter 0.35s ease;
}

.game-card:hover img {
    transform: scale(1.08);
    filter: brightness(1.1) saturate(1.15);
}

.glow-card {
    box-shadow:
        0 25px 80px rgba(0,0,0,0.45),
        0 0 45px rgba(250,204,21,0.08);
}

.hero-title {
    text-shadow: 0 0 35px rgba(250,204,21,0.18);
}

/* ========================= */
/* RESPONSIVE GENERAL MÓVIL */
/* ========================= */

@media (max-width: 1024px) {

    header .max-w-7xl {
        align-items: stretch !important;
    }

    header .max-w-7xl > div {
        width: 100%;
        justify-content: center;
    }

    #casino-saldo {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 768px) {

    body {
        font-size: 15px;
    }

    header {
        position: relative !important;
    }

    header .max-w-7xl,
    main.max-w-7xl,
    footer.max-w-7xl {
        width: 100%;
        max-width: 100%;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    header .max-w-7xl {
        flex-direction: column !important;
        gap: 16px !important;
    }

    header a.flex {
        width: 100%;
        justify-content: center;
        text-align: center;
    }

    header a.flex img {
        width: 52px !important;
        height: 52px !important;
    }

    header .flex.flex-wrap {
        width: 100%;
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 10px !important;
    }

    h1 {
        font-size: 1.8rem !important;
        line-height: 1.1 !important;
    }

    h2 {
        font-size: 1.65rem !important;
        line-height: 1.15 !important;
    }

    h3 {
        font-size: 1.25rem !important;
    }

    p {
        word-break: break-word;
    }

    input,
    select,
    textarea,
    button,
    a {
        max-width: 100%;
    }

    input,
    select,
    textarea {
        font-size: 16px !important;
    }

    button,
    a {
        white-space: normal;
    }

    #casino-saldo {
        grid-column: 1 / -1;
        width: 100%;
        font-size: 1rem;
        padding: 12px !important;
    }

    #perfil-container,
    #ingresar-container,
    header form {
        width: 100%;
    }

    #perfil-container button,
    #ingresar-container button,
    header form button {
        width: 100%;
        justify-content: center;
        padding: 12px !important;
    }

    #perfil-menu,
    #ingresar-menu {
        position: fixed !important;
        left: 12px !important;
        right: 12px !important;
        top: 145px !important;
        width: auto !important;
        max-width: calc(100vw - 24px) !important;
        max-height: calc(100vh - 170px);
        overflow-y: auto;
    }

    main {
        padding-top: 20px !important;
    }

    main section {
        width: 100%;
    }

    .hero-title {
        text-align: center;
    }

    .slider img {
        height: 180px !important;
    }

    .game-card img {
        height: 220px !important;
    }

    .glow-card {
        border-radius: 22px !important;
    }
}

@media (max-width: 480px) {

    body {
        font-size: 14px;
    }

    header .flex.flex-wrap {
        grid-template-columns: 1fr;
    }

    #casino-saldo {
        grid-column: auto;
    }

    #perfil-menu,
    #ingresar-menu {
        top: 210px !important;
    }

    h1 {
        font-size: 1.55rem !important;
    }

    h2 {
        font-size: 1.45rem !important;
    }

    .text-6xl,
    .text-5xl,
    .text-4xl {
        font-size: 2rem !important;
        line-height: 1.15 !important;
    }

    .text-3xl {
        font-size: 1.55rem !important;
    }

    .text-2xl {
        font-size: 1.25rem !important;
    }

    .p-8 {
        padding: 1.25rem !important;
    }

    .p-6 {
        padding: 1rem !important;
    }

    .px-6 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }

    .py-5 {
        padding-top: 0.9rem !important;
        padding-bottom: 0.9rem !important;
    }

    .rounded-3xl {
        border-radius: 1.25rem !important;
    }

    .slider img {
        height: 155px !important;
    }

    .game-card img {
        height: 200px !important;
    }

    .game-card .text-2xl {
        font-size: 1.2rem !important;
    }
}
</style>

<script>
$(document).ready(function(){

    function getSliderConfig() {
        const ancho = window.innerWidth;

        if (ancho < 640) {
            return {
                slideWidth: Math.max(260, ancho - 32),
                minSlides: 1,
                maxSlides: 1,
                moveSlides: 1,
                slideMargin: 10
            };
        }

        if (ancho < 1024) {
            return {
                slideWidth: 320,
                minSlides: 1,
                maxSlides: 2,
                moveSlides: 1,
                slideMargin: 16
            };
        }

        return {
            slideWidth: 350,
            minSlides: 1,
            maxSlides: 3,
            moveSlides: 1,
            slideMargin: 20
        };
    }

    const responsiveConfig = getSliderConfig();

    $('.slider').bxSlider(Object.assign({
        auto: true,
        pause: 3000,
        speed: 700,
        pager: false,
        controls: true,
        adaptiveHeight: true,
        touchEnabled: true
    }, responsiveConfig));

});
</script>

<script src="assets/app.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.startSaldoPolling) {
        startSaldoPolling(10000);
    }
});
</script>

</head>

<body class="min-h-screen text-white">

<!-- FONDO -->
<div id="casino-bg"></div>
<div id="casino-overlay"></div>
<div id="casino-pattern"></div>

<!-- HEADER -->
<header class="w-full border-b border-yellow-400/20 bg-black/55 backdrop-blur-xl sticky top-0">

    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col lg:flex-row items-center justify-between gap-4">

        <!-- LOGO -->
        <a href="principal.php" class="flex items-center gap-4 group w-full lg:w-auto justify-center lg:justify-start text-center lg:text-left">
            <div class="relative">
                <img src="img/logocuadrado.png" class="w-16 h-16 rounded-2xl shadow-2xl border border-yellow-400/30 group-hover:scale-105 transition">
                <div class="absolute -inset-1 bg-yellow-400/20 blur-xl rounded-2xl -z-10"></div>
            </div>

            <div>
                <h1 class="text-3xl sm:text-4xl font-black text-yellow-400 leading-none hero-title">
                    HIGH STAKES
                </h1>

                <p class="text-zinc-400 text-sm font-bold">
                    Casino escolar · Bienvenido, <?= escaparPrincipal($nombreMostrado) ?>
                </p>
            </div>
        </a>

        <!-- ACCIONES -->
        <div class="flex flex-wrap items-center justify-center lg:justify-end gap-3 w-full lg:w-auto">

            <!-- SALDO -->
            <div id="casino-saldo"
                 class="bg-yellow-400 text-black px-5 py-3 rounded-2xl font-black shadow-2xl border border-yellow-200">
                Saldo: $<?= dineroPrincipal($saldo) ?>
            </div>

            <!-- PERFIL -->
            <div id="perfil-container" class="relative">
                <button onclick="togglePerfil()"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 px-5 py-3 rounded-2xl font-black shadow-2xl transition-all duration-200 border border-blue-300/20">
                    Perfil
                </button>

                <!-- DROPDOWN PERFIL -->
                <div id="perfil-menu"
                     class="hidden absolute right-0 mt-3 w-72 bg-zinc-950/95 backdrop-blur-xl border border-yellow-400/20 rounded-3xl p-5 shadow-2xl">

                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mx-auto flex items-center justify-center mb-3 shadow-2xl border border-white/20">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                </path>
                            </svg>
                        </div>

                        <h3 class="font-black text-xl text-yellow-400">
                            <?= escaparPrincipal($nombreMostrado) ?>
                        </h3>

                        <p class="text-zinc-400 text-sm">
                            <?= escaparPrincipal($datos_usuario['email'] ?? '') ?>
                        </p>
                    </div>

                    <button onclick="window.location.href='perfil.php'"
                            class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-xl font-black mb-3 transition-all duration-200">
                        Ver / editar perfil →
                    </button>

                    <button onclick="window.location.href='historial.php'"
                            class="w-full bg-purple-600 hover:bg-purple-500 py-3 rounded-xl font-bold transition-all duration-200">
                        Historial de transacciones
                    </button>
                </div>
            </div>

            <!-- INGRESAR -->
            <div id="ingresar-container" class="relative">

                <button onclick="toggleIngresar()"
                        class="bg-green-500 hover:bg-green-400 px-5 py-3 rounded-2xl font-black shadow-2xl transition-all duration-200 border border-green-200/20">
                    Ingresar
                </button>

                <!-- DROPDOWN INGRESAR -->
                <div id="ingresar-menu"
                     class="hidden absolute right-0 mt-3 w-72 bg-zinc-950/95 backdrop-blur-xl border border-yellow-400/20 rounded-3xl p-5 shadow-2xl">

                    <p class="text-zinc-400 text-sm font-bold">
                        Saldo actual
                    </p>

                    <div class="text-4xl font-black text-yellow-400 mb-4">
                        $<?= dineroPrincipal($saldo) ?>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <button onclick="pagar(5)"
                                class="bg-green-600 hover:bg-green-500 py-3 rounded-xl font-black transition-all duration-200">
                            5€
                        </button>

                        <button onclick="pagar(10)"
                                class="bg-green-600 hover:bg-green-500 py-3 rounded-xl font-black transition-all duration-200">
                            10€
                        </button>

                        <button onclick="pagar(20)"
                                class="bg-green-600 hover:bg-green-500 py-3 rounded-xl font-black transition-all duration-200">
                            20€
                        </button>
                    </div>

                    <div class="border-t border-white/10 my-4"></div>

                    <label class="block text-zinc-400 text-sm font-bold mb-2">
                        Cantidad personalizada
                    </label>

                    <input id="montoCustom"
                           type="number"
                           min="1"
                           step="0.01"
                           class="w-full bg-zinc-900 border border-zinc-700 p-3 rounded-xl mb-3 focus:outline-none focus:ring-2 focus:ring-yellow-400 font-bold"
                           placeholder="Cantidad">

                    <button onclick="pagarCustom()"
                            class="w-full bg-yellow-400 text-black py-3 rounded-xl font-black hover:bg-yellow-300 transition-all duration-200">
                        Ingresar saldo
                    </button>

                </div>

            </div>

            <!-- CERRAR SESIÓN -->
            <form method="POST" action="logout.php">
                <button class="bg-red-600 hover:bg-red-500 px-5 py-3 rounded-2xl font-black shadow-2xl transition-all duration-200 border border-red-300/20">
                    Cerrar sesión
                </button>
            </form>

        </div>

    </div>

</header>

<!-- MAIN -->
<main class="max-w-7xl mx-auto px-4 py-8 space-y-10">

    <!-- HERO -->
    <section class="bg-black/45 backdrop-blur-xl border border-yellow-400/20 rounded-[2rem] p-6 md:p-10 glow-card">

        <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-8 items-center">

            <div>
                <p class="text-yellow-400 font-black uppercase tracking-widest mb-3">
                    🎲 Casino High Stakes
                </p>

                <h2 class="text-4xl md:text-6xl font-black leading-tight mb-5 hero-title">
                    Elige tu juego y
                    <span class="text-yellow-400 block">
                        empieza la partida
                    </span>
                </h2>

                <p class="text-zinc-300 text-lg max-w-2xl">
                    Gestiona tu saldo, consulta tu perfil y juega a tus modos favoritos desde el panel principal.
                </p>

                <div class="flex flex-col sm:flex-row flex-wrap gap-3 mt-7">
                    <a href="perfil.php"
                       class="bg-yellow-400 hover:bg-yellow-300 text-black px-6 py-3 rounded-2xl font-black transition text-center">
                        Ver mi perfil
                    </a>

                    <a href="historial.php"
                       class="bg-white/10 hover:bg-white/20 border border-white/10 px-6 py-3 rounded-2xl font-black transition text-center">
                        Historial
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-yellow-400 text-black rounded-3xl p-5 shadow-2xl">
                    <p class="font-black text-sm uppercase">
                        Saldo
                    </p>

                    <p class="text-4xl font-black mt-2">
                        $<?= dineroPrincipal($saldo) ?>
                    </p>
                </div>

                <div class="bg-blue-600 rounded-3xl p-5 shadow-2xl">
                    <p class="font-black text-sm uppercase text-blue-100">
                        Estado
                    </p>

                    <p class="text-3xl font-black mt-2">
                        Activo
                    </p>
                </div>

                <div class="sm:col-span-2 bg-zinc-900/85 border border-zinc-700 rounded-3xl p-5">
                    <p class="text-zinc-400 font-bold">
                        Usuario conectado
                    </p>

                    <p class="text-2xl font-black text-yellow-400 mt-1 break-words">
                        <?= escaparPrincipal($nombreMostrado) ?>
                    </p>
                </div>
            </div>

        </div>

    </section>

    <!-- CARRUSEL -->
    <section class="w-full">

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
            <div>
                <h2 class="text-3xl font-black text-yellow-400">
                    🔥 Destacados
                </h2>

                <p class="text-zinc-400">
                    Juegos y promociones destacadas.
                </p>
            </div>
        </div>

        <div class="w-full px-0 sm:px-6">

            <ul class="slider">

                <li>
                    <a href="blackjack.php" class="block cursor-pointer hover:opacity-80 transition-opacity duration-200">
                        <img src="img/blackjack.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-3xl border border-yellow-400/20">
                    </a>
                </li>

                <li>
                    <a href="tragaperras.php" class="block cursor-pointer hover:opacity-80 transition-opacity duration-200">
                        <img src="img/777.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-3xl border border-yellow-400/20">
                    </a>
                </li>

                <li>
                    <a href="minas.php" class="block cursor-pointer hover:opacity-80 transition-opacity duration-200">
                        <img src="img/mines.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-3xl border border-yellow-400/20">
                    </a>
                </li>
                <li>
                    <a href="ruleta.php" class="block cursor-pointer hover:opacity-80 transition-opacity duration-200">
                        <img src="img/ruleta.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-3xl border border-yellow-400/20">
                    </a>
                </li>

            </ul>

        </div>

    </section>

    <!-- JUEGOS -->
    <section>

        <div class="mb-6">
            <h2 class="text-3xl font-black text-yellow-400">
                🎰 Juegos disponibles
            </h2>

            <p class="text-zinc-400">
                Selecciona un juego para comenzar.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <div onclick="window.location.href='blackjack.php'"
                 class="game-card cursor-pointer bg-zinc-900/85 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-[1.03] transition-all duration-300 border border-white/10">

                <img src="img/blackjack.png" class="w-full h-64 object-cover">

                <div class="absolute bottom-0 left-0 right-0 p-5 z-10">
                    <p class="text-yellow-400 font-black text-sm uppercase tracking-widest">
                        Cartas
                    </p>

                    <div class="text-2xl font-black">
                        BLACKJACK
                    </div>
                </div>
            </div>

            <div onclick="window.location.href='tragaperras.php'"
                 class="game-card cursor-pointer bg-zinc-900/85 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-[1.03] transition-all duration-300 border border-white/10">

                <img src="img/777.png" class="w-full h-64 object-cover">

                <div class="absolute bottom-0 left-0 right-0 p-5 z-10">
                    <p class="text-yellow-400 font-black text-sm uppercase tracking-widest">
                        Slots
                    </p>

                    <div class="text-2xl font-black">
                        SLOT MACHINE
                    </div>
                </div>
            </div>

            <div onclick="window.location.href='minas.php'"
                 class="game-card cursor-pointer bg-zinc-900/85 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-[1.03] transition-all duration-300 border border-white/10">

                <img src="img/mines.png" class="w-full h-64 object-cover">

                <div class="absolute bottom-0 left-0 right-0 p-5 z-10">
                    <p class="text-yellow-400 font-black text-sm uppercase tracking-widest">
                        Riesgo
                    </p>

                    <div class="text-2xl font-black">
                        MINES V3
                    </div>
                </div>
            </div>

            <div onclick="window.location.href='ruleta.php'"
                 class="game-card cursor-pointer bg-zinc-900/85 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-[1.03] transition-all duration-300 border border-white/10">

                <img src="img/ruleta.png" class="w-full h-64 object-cover">

                <div class="absolute bottom-0 left-0 right-0 p-5 z-10">
                    <p class="text-yellow-400 font-black text-sm uppercase tracking-widest">
                        Ruleta
                    </p>

                    <div class="text-2xl font-black">
                        FIRE BLAZE
                    </div>
                </div>
            </div>

        </div>

    </section>

</main>

<footer class="max-w-7xl mx-auto px-4 pb-8">
    <div class="bg-black/45 border border-white/10 rounded-3xl p-5 text-center text-zinc-400">
        <p class="font-bold">
            HIGH STAKES · Proyecto educativo
        </p>
    </div>
</footer>

</body>
</html>