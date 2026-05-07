<?php
session_start();
include("conexion.php");

// 🔐 ahora usamos id_usuario (CORREGIDO)
if(!isset($_SESSION['id_usuario'])){
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener saldo del usuario
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
<title>Casino Online - Principal</title>
<link rel="stylesheet" href="style.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bxslider@4.2.17/dist/jquery.bxslider.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bxslider@4.2.17/dist/jquery.bxslider.min.js"></script>

<style>
#ingresar-container {
    position: relative;
    display: inline-block;
}

#ingresar-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 45px;
    background: #1a1a1a;
    padding: 15px;
    border-radius: 10px;
    width: 220px;
    box-shadow: 0 0 10px rgba(0,0,0,0.5);
    z-index: 999;
}

#ingresar-menu button {
    width: 100%;
    margin: 5px 0;
    padding: 8px;
    cursor: pointer;
}

#ingresar-menu input {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    margin-bottom: 5px;
}
</style>

<script>
$(document).ready(function(){
    $(".slider").bxSlider({
        mode: 'horizontal',
        auto: true,
        pause: 3000,
        speed: 800,
        pager: false,
        controls: true,
        slideMargin: 10,
        autoHover: true,
        responsive: true,
        minSlides: 1,
        maxSlides: 4,
        moveSlides: 1,
        slideWidth: 250
    });
});

// 🔽 Mostrar dropdown
function toggleIngresar() {
    let menu = document.getElementById("ingresar-menu");
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

// 🔽 Cerrar si haces clic fuera
document.addEventListener("click", function(e) {
    let container = document.getElementById("ingresar-container");
    if (container && !container.contains(e.target)) {
        document.getElementById("ingresar-menu").style.display = "none";
    }
});

// 🔽 Stripe pago
function pagar(monto = 10) {
    if(monto <= 0){
        alert("Monto inválido");
        return;
    }

    fetch('crear_sesion_pago.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ monto: monto })
    })
    .then(res => res.json())
    .then(data => {
        if(data.url){
            window.location.href = data.url;
        } else {
            alert("Error al iniciar el pago");
        }
    })
    .catch(() => alert("Error de conexión"));
}

// 🔽 Pago personalizado
function pagarCustom() {
    let monto = parseFloat(document.getElementById("montoCustom").value);

    if(isNaN(monto) || monto < 1){
        alert("Mínimo 1€");
        return;
    }

    if(monto > 1000){
        alert("Máximo 1000€");
        return;
    }

    pagar(monto);
}

// 🔽 Actualizar saldo
function actualizarSaldo() {
    fetch('saldo.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('casino-saldo').textContent =
                'Saldo: $' + parseFloat(data.saldo).toFixed(2);
        });
}
setInterval(actualizarSaldo, 5000);
</script>
</head>

<body id="casino-body">

<video autoplay muted loop id="casino-video-bg">
    <source src="img/vidpres.mp4" type="video/mp4">
</video>

<header id="casino-header">
    <img src="img/logocuadrado.png" alt="Logo Casino" id="casino-logo">

    <div id="casino-header-right">
        <span id="casino-saldo">
            Saldo: $<?php echo number_format($saldo,2); ?>
        </span>

        <!-- INGRESAR -->
        <div id="ingresar-container">
            <button id="casino-ingresar-btn" onclick="toggleIngresar()">Ingresar</button>

            <div id="ingresar-menu">
                <strong>Saldo actual:</strong>
                <p>$<?php echo number_format($saldo,2); ?></p>

                <hr>

                <button onclick="pagar(5)">Ingresar 5€</button>
                <button onclick="pagar(10)">Ingresar 10€</button>
                <button onclick="pagar(20)">Ingresar 20€</button>

                <hr>

                <input type="number" id="montoCustom" placeholder="Cantidad personalizada" min="1" max="1000">
                <button onclick="pagarCustom()">Ingresar</button>
            </div>
        </div>

        <form method="POST" action="logout.php" style="display:inline;">
            <button type="submit" id="casino-logout-btn">Cerrar sesión</button>
        </form>
    </div>
</header>

<main id="casino-principal">

    <!-- Carrusel -->
    <div id="casino-slots-carousel-wrapper">
        <ul class="slider">
            <li><img src="img/frog.png" alt="Slot 1"></li>
            <li><img src="img/777.png" alt="Slot 2"></li>
            <li><img src="img/pulislot.png" alt="Slot 3"></li>
            <li><img src="img/slingo.png" alt="Slot 4"></li>
        </ul>
    </div>

    <!-- Juegos -->
    <div id="casino-juegos-grid">
        <div class="casino-juego-card" onclick="window.location.href='juego1.php'">
            <img src="img/pigbank.png">
            <span>BIGBANK</span>
        </div>
        <div class="casino-juego-card" onclick="window.location.href='tragaperras.php'">
            <img src="img/777.png">
            <span>🎰 TRAGAPERRAS</span>
        </div>
        <div class="casino-juego-card" onclick="window.location.href='juego3.php'">
            <img src="img/777.png">
            <span>Juego 3</span>
        </div>
        <div class="casino-juego-card" onclick="window.location.href='juego4.php'">
            <img src="img/frog.png">
            <span>Juego 4</span>
        </div>
    </div>

</main>

</body>
</html>