<?php
require_once __DIR__ . '/session_helpers.php';
include("conexion.php");

init_session();

if(!isset($_SESSION['id_usuario'])){
    header("Location: index.php");
    exit();
}


$id_usuario = $_SESSION['id_usuario'];

$sql = "SELECT saldo FROM usuarios WHERE id_usuario=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$resultado = $stmt->get_result();
$datos_usuario = $resultado->fetch_assoc();

$saldo = $datos_usuario['saldo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>HIGHT_STAKES</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<!-- bxSlider -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bxslider@4.2.17/dist/jquery.bxslider.min.css">
<script src="https://cdn.jsdelivr.net/npm/bxslider@4.2.17/dist/jquery.bxslider.min.js"></script>

<style>

body{
    overflow-x: hidden;
}

/* Fondo */
#casino-bg{
    position: fixed;
    inset: 0;
    background-image: url('img/casino-bg.jpg');
    background-size: cover;
    background-position: center;
    z-index: -2;
}

#casino-overlay{
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: -1;
}

/* bxSlider stacking context (IMPORTANTE) */
.bx-wrapper,
.bx-viewport{
    position: relative !important;
    z-index: 1 !important;
}

.bx-wrapper{
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

.bx-wrapper img{
    width: 100%;
    border-radius: 20px;
}

/* 🔥 FIX REAL: dropdown por encima de TODO */
#ingresar-container,
#perfil-container{
    position: relative;
    z-index: 999999 !important;
}

#ingresar-menu,
#perfil-menu{
    position: absolute !important;
    z-index: 99999999 !important;
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* header encima del slider */
header{
    position: relative;
    z-index: 99999;
}

</style>

<script>

$(document).ready(function(){

    $('.slider').bxSlider({
        auto: true,
        pause: 3000,
        speed: 700,
        pager: false,
        controls: true,
        adaptiveHeight: true,
        touchEnabled: true,
        slideWidth: 350,
        minSlides: 1,
        maxSlides: 3,
        moveSlides: 1,
        slideMargin: 20
    });

});

// Funciones toggle/pago/saldo ahora viven en assets/app.js
// (principal mantiene solo inicialización del slider)

</script>

<script src="assets/app.js"></script>
<script>
  // Reducir polling para rendimiento
  document.addEventListener('DOMContentLoaded', () => {
    if (window.startSaldoPolling) startSaldoPolling(10000);
  });
</script>

</head>

<body class="min-h-screen text-white">

<!-- fondo -->
<div id="casino-bg"></div>
<div id="casino-overlay"></div>

<!-- HEADER CON BOTÓN PERFIL -->
<header class="w-full border-b border-white/10 bg-black/30 backdrop-blur-md">

    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col lg:flex-row items-center justify-between gap-4">

        <div class="flex items-center gap-3">
            <img src="img/logocuadrado.png" class="w-16 h-16 rounded-2xl shadow-2xl">
            <h1 class="text-2xl sm:text-3xl font-black">HIGHTSTAKES</h1>
        </div>

        <div class="flex flex-wrap items-center gap-4">

            <div id="casino-saldo"
                 class="bg-yellow-400 text-black px-5 py-2 rounded-2xl font-bold shadow-lg">

                Saldo: $<?php echo number_format($saldo,2); ?>

            </div>

            <!-- BOTÓN PERFIL NUEVO -->
            <div id="perfil-container" class="relative">
                <button onclick="togglePerfil()"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 px-5 py-2 rounded-2xl font-bold shadow-lg transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Perfil
                </button>

                <!-- DROPDOWN PERFIL -->
                <div id="perfil-menu"
                     class="hidden absolute right-0 mt-3 w-64 bg-zinc-900 border border-white/10 rounded-2xl p-5 shadow-2xl">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mx-auto flex items-center justify-center mb-2 shadow-2xl">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="font-black text-xl">Mi Perfil</h3>
                    </div>
                    <button onclick="window.location.href='perfil.php'"
                            class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-xl font-bold mb-3 transition-all duration-200">
                        Ver perfil completo →
                    </button>
                    <button onclick="window.location.href='historial.php'"
                            class="w-full bg-purple-600 hover:bg-purple-500 py-2 rounded-xl text-sm transition-all duration-200">
                        Historial de transacciones
                    </button>
                </div>
            </div>

            <!-- INGRESAR -->
            <div id="ingresar-container">

                <button onclick="toggleIngresar()"
                        class="bg-green-500 hover:bg-green-400 px-5 py-2 rounded-2xl font-bold shadow-lg transition-all duration-200">

                    Ingresar

                </button>

                <!-- DROPDOWN (FORZADO ENCIMA DE TODO) -->
                <div id="ingresar-menu"
                     class="hidden absolute right-0 mt-3 w-64 bg-zinc-900 border border-white/10 rounded-2xl p-5 shadow-2xl">

                    <p class="text-zinc-400 text-sm">Saldo actual</p>

                    <div class="text-3xl font-black text-yellow-400 mb-4">
                        $<?php echo number_format($saldo,2); ?>
                    </div>

                    <button onclick="pagar(5)" class="w-full bg-green-600 hover:bg-green-500 py-2 rounded-xl mb-2 transition-all duration-200">5€</button>
                    <button onclick="pagar(10)" class="w-full bg-green-600 hover:bg-green-500 py-2 rounded-xl mb-2 transition-all duration-200">10€</button>
                    <button onclick="pagar(20)" class="w-full bg-green-600 hover:bg-green-500 py-2 rounded-xl transition-all duration-200">20€</button>

                    <div class="border-t border-white/10 my-4"></div>

                    <input id="montoCustom"
                           type="number"
                           class="w-full bg-zinc-800 p-2 rounded mb-3 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                           placeholder="Cantidad">

                    <button onclick="pagarCustom()"
                            class="w-full bg-yellow-400 text-black py-2 rounded-xl font-black hover:bg-yellow-300 transition-all duration-200">

                        Ingresar

                    </button>

                </div>

            </div>

            <!-- CERRAR SESIÓN -->
            <form method="POST" action="logout.php" class="ml-2">
                <button class="bg-red-500 hover:bg-red-400 px-5 py-2 rounded-2xl font-bold shadow-lg transition-all duration-200">
                    Cerrar sesión
                </button>
            </form>

        </div>

    </div>

</header>

<!-- MAIN (TODO INTACTO) -->
<main class="max-w-7xl mx-auto px-4 py-8">

    <!-- CARRUSEL -->
    <section class="w-full flex justify-center items-center py-6 sm:py-10">

        <div class="w-full max-w-sm sm:max-w-2xl lg:max-w-5xl px-2 sm:px-6">

            <ul class="slider">

                <li><img src="img/frog.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-2xl"></li>
                <li><img src="img/777.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-2xl"></li>
                <li><img src="img/pulislot.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-2xl"></li>
                <li><img src="img/slingo.png" class="w-full h-44 sm:h-60 lg:h-72 object-cover rounded-2xl"></li>

            </ul>

        </div>

    </section>

    <!-- JUEGOS (NO TOCADO) -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        <div onclick="window.location.href='juego1.php'" class="cursor-pointer bg-white/10 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-105 transition-all duration-200">
            <img src="img/pigbank.png" class="w-full h-56 object-cover">
            <div class="p-5 text-center font-black">BIGBANK</div>
        </div>

        <div onclick="window.location.href='tragaperras.php'" class="cursor-pointer bg-white/10 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-105 transition-all duration-200">
            <img src="img/777.png" class="w-full h-56 object-cover">
            <div class="p-5 text-center font-black">🎰 TRAGAPERRAS</div>
        </div>

        <div onclick="window.location.href='minas.php'" class="cursor-pointer bg-white/10 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-105 transition-all duration-200">
            <img src="img/777.png" class="w-full h-56 object-cover">
            <div class="p-5 text-center font-black">Juego 3</div>
        </div>

        <div onclick="window.location.href='juego4.php'" class="cursor-pointer bg-white/10 backdrop-blur-lg rounded-3xl overflow-hidden shadow-2xl hover:scale-105 transition-all duration-200">
            <img src="img/frog.png" class="w-full h-56 object-cover">
            <div class="p-5 text-center font-black">Juego 4</div>
        </div>

    </section>

</main>

</body>
</html>