<?php
require_once __DIR__ . '/session_helpers.php';
include("conexion.php");

init_session();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$idUsuario = (int) $_SESSION['id_usuario'];

function dinero($valor) {
    return number_format((float) $valor, 2);
}

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function columnaExiste($conexion, $tabla, $columna){
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
    Asegurar columna estado.
*/
if (!columnaExiste($conexion, "usuarios", "estado")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo'
    ");
}

/*
    Comprobar usuario.
*/
$stmt = $conexion->prepare("
    SELECT *
    FROM usuarios
    WHERE id_usuario = ?
    LIMIT 1
");

if (!$stmt) {
    die("Error cargando usuario: " . $conexion->error);
}

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$estadoUsuario = $usuario['estado'] ?? 'activo';

if ($estadoUsuario === 'suspendido' || $estadoUsuario === 'bloqueado') {
    session_destroy();
    header("Location: index.php?error=cuenta_bloqueada");
    exit();
}

if (!empty($usuario['nombre'])) {
    $nombreMostrado = $usuario['nombre'];
} elseif (!empty($usuario['usuario'])) {
    $nombreMostrado = $usuario['usuario'];
} elseif (!empty($usuario['nombre_usuario'])) {
    $nombreMostrado = $usuario['nombre_usuario'];
} else {
    $nombreMostrado = "Jugador";
}

/*
    Crear tabla historial_apuestas si no existe.
*/
$crearTabla = $conexion->query("
    CREATE TABLE IF NOT EXISTS historial_apuestas (
        id_historial INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        juego VARCHAR(50) NOT NULL,
        monto_apostado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        resultado ENUM('win','loss','tie') NOT NULL,
        multiplicador DECIMAL(8,2) NOT NULL DEFAULT 0.00,
        pago DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        ganancia_neta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        saldo_sesion_despues DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_usuario_fecha (id_usuario, fecha),
        INDEX idx_usuario_juego (id_usuario, juego)
    )
");

if (!$crearTabla) {
    die("Error creando tabla historial_apuestas: " . $conexion->error);
}

/*
    Sesión de juego activa.
*/
$haySesionActiva = isset($_SESSION['game_session'])
    && isset($_SESSION['game_session']['active'])
    && $_SESSION['game_session']['active'] === true;

$saldoSesion = 0;
$bankrollInicial = 0;
$apuestaActual = 0;

if ($haySesionActiva) {
    $saldoSesion = isset($_SESSION['game_session']['saldo_sesion_cents'])
        ? ((int) $_SESSION['game_session']['saldo_sesion_cents']) / 100
        : 0;

    $bankrollInicial = isset($_SESSION['game_session']['bankroll_inicial_cents'])
        ? ((int) $_SESSION['game_session']['bankroll_inicial_cents']) / 100
        : 0;

    $apuestaActual = isset($_SESSION['game_session']['current_bet_cents'])
        ? ((int) $_SESSION['game_session']['current_bet_cents']) / 100
        : 0;
}

/*
    Estadísticas generales.
*/
$stmt = $conexion->prepare("
    SELECT
        COUNT(*) AS total_apuestas,
        COALESCE(SUM(monto_apostado), 0) AS total_apostado,

        COALESCE(SUM(CASE WHEN resultado = 'win' THEN 1 ELSE 0 END), 0) AS ganadas,
        COALESCE(SUM(CASE WHEN resultado = 'loss' THEN 1 ELSE 0 END), 0) AS perdidas,
        COALESCE(SUM(CASE WHEN resultado = 'tie' THEN 1 ELSE 0 END), 0) AS empates,

        COALESCE(SUM(CASE WHEN ganancia_neta > 0 THEN ganancia_neta ELSE 0 END), 0) AS ganancias,
        COALESCE(SUM(CASE WHEN ganancia_neta < 0 THEN ABS(ganancia_neta) ELSE 0 END), 0) AS perdidas_dinero,
        COALESCE(SUM(ganancia_neta), 0) AS balance_neto
    FROM historial_apuestas
    WHERE id_usuario = ?
");

if (!$stmt) {
    die("Error preparando estadísticas generales: " . $conexion->error);
}

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$stats = $stmt->get_result()->fetch_assoc();

$totalApuestas = isset($stats['total_apuestas']) ? (int) $stats['total_apuestas'] : 0;
$totalApostado = isset($stats['total_apostado']) ? (float) $stats['total_apostado'] : 0;
$ganadas = isset($stats['ganadas']) ? (int) $stats['ganadas'] : 0;
$perdidas = isset($stats['perdidas']) ? (int) $stats['perdidas'] : 0;
$empates = isset($stats['empates']) ? (int) $stats['empates'] : 0;
$ganancias = isset($stats['ganancias']) ? (float) $stats['ganancias'] : 0;
$perdidasDinero = isset($stats['perdidas_dinero']) ? (float) $stats['perdidas_dinero'] : 0;
$balanceNeto = isset($stats['balance_neto']) ? (float) $stats['balance_neto'] : 0;

$porcentajeGanadas = $totalApuestas > 0 ? ($ganadas / $totalApuestas) * 100 : 0;
$porcentajePerdidas = $totalApuestas > 0 ? ($perdidas / $totalApuestas) * 100 : 0;
$porcentajeEmpates = $totalApuestas > 0 ? ($empates / $totalApuestas) * 100 : 0;

/*
    Estadísticas por juego.
*/
$stmt = $conexion->prepare("
    SELECT
        juego,
        COUNT(*) AS total_apuestas,
        COALESCE(SUM(monto_apostado), 0) AS total_apostado,
        COALESCE(SUM(CASE WHEN resultado = 'win' THEN 1 ELSE 0 END), 0) AS ganadas,
        COALESCE(SUM(CASE WHEN resultado = 'loss' THEN 1 ELSE 0 END), 0) AS perdidas,
        COALESCE(SUM(CASE WHEN resultado = 'tie' THEN 1 ELSE 0 END), 0) AS empates,
        COALESCE(SUM(ganancia_neta), 0) AS balance_neto
    FROM historial_apuestas
    WHERE id_usuario = ?
    GROUP BY juego
    ORDER BY total_apuestas DESC
");

if (!$stmt) {
    die("Error preparando estadísticas por juego: " . $conexion->error);
}

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$statsPorJuego = $stmt->get_result();

/*
    Historial reciente.
*/
$stmt = $conexion->prepare("
    SELECT
        id_historial,
        juego,
        monto_apostado,
        resultado,
        multiplicador,
        pago,
        ganancia_neta,
        saldo_sesion_despues,
        fecha
    FROM historial_apuestas
    WHERE id_usuario = ?
    ORDER BY fecha DESC
    LIMIT 100
");

if (!$stmt) {
    die("Error preparando historial: " . $conexion->error);
}

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$historial = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Historial - High Stakes</title>

<link rel="icon" type="image/png" href="img/logocuadrado.png">
<script src="https://cdn.tailwindcss.com"></script>

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
    background:
        radial-gradient(circle at top, rgba(250,204,21,0.14), transparent 35%),
        radial-gradient(circle at bottom left, rgba(37,99,235,0.18), transparent 35%),
        #020617;
}

.card-glow {
    box-shadow:
        0 25px 80px rgba(0,0,0,0.55),
        0 0 45px rgba(250,204,21,0.10);
}

.table-scroll::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-scroll::-webkit-scrollbar-thumb {
    background: #facc15;
    border-radius: 999px;
}

.table-scroll::-webkit-scrollbar-track {
    background: #18181b;
}

@media (max-width: 768px) {
    header .max-w-7xl,
    main.max-w-7xl {
        max-width: 100%;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    h1 {
        font-size: 1.8rem !important;
    }

    h2 {
        font-size: 1.6rem !important;
    }

    th,
    td {
        padding: 12px !important;
        white-space: nowrap;
    }

    table {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 1.55rem !important;
    }

    .p-6 {
        padding: 1rem !important;
    }

    .text-4xl {
        font-size: 2rem !important;
    }

    .text-3xl {
        font-size: 1.5rem !important;
    }
}
</style>
</head>

<body class="min-h-screen text-white">

<header class="bg-black/70 backdrop-blur-md border-b border-yellow-400/30 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-4">

        <div class="text-center md:text-left">
            <h1 class="text-3xl font-black text-yellow-400">
                📊 Historial y estadísticas
            </h1>

            <p class="text-zinc-400">
                Datos de juego de <?= escapar($nombreMostrado) ?>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <a href="principal.php"
               class="bg-yellow-400 hover:bg-yellow-300 text-black px-5 py-3 rounded-xl font-black transition text-center">
                🏠 Principal
            </a>

            <a href="perfil.php"
               class="bg-blue-600 hover:bg-blue-500 px-5 py-3 rounded-xl font-black transition text-center">
                👤 Perfil
            </a>

            <a href="logout.php"
               class="bg-red-600 hover:bg-red-500 px-5 py-3 rounded-xl font-black transition text-center">
                Cerrar sesión
            </a>
        </div>

    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

    <!-- SESIÓN ACTIVA -->
    <?php if ($haySesionActiva): ?>
        <section class="bg-emerald-900/50 border border-emerald-400 rounded-3xl p-6 card-glow">

            <div class="flex flex-col md:flex-row justify-between gap-4">

                <div>
                    <h2 class="text-2xl font-black text-emerald-300">
                        🎮 Sesión de juego activa
                    </h2>

                    <p class="text-emerald-100 mt-1">
                        Tienes dinero reservado en una sesión de juego.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">

                    <div class="bg-black/30 rounded-2xl p-4">
                        <p class="text-zinc-400 text-sm">
                            Bankroll inicial
                        </p>

                        <p class="text-2xl font-black">
                            $<?= dinero($bankrollInicial) ?>
                        </p>
                    </div>

                    <div class="bg-black/30 rounded-2xl p-4">
                        <p class="text-zinc-400 text-sm">
                            Saldo sesión
                        </p>

                        <p class="text-2xl font-black text-yellow-400">
                            $<?= dinero($saldoSesion) ?>
                        </p>
                    </div>

                    <div class="bg-black/30 rounded-2xl p-4">
                        <p class="text-zinc-400 text-sm">
                            Apuesta actual
                        </p>

                        <p class="text-2xl font-black">
                            $<?= dinero($apuestaActual) ?>
                        </p>
                    </div>

                </div>

            </div>

        </section>
    <?php endif; ?>

    <!-- ESTADÍSTICAS GENERALES -->
    <section>
        <h2 class="text-3xl font-black mb-4 text-yellow-400">
            📊 Estadísticas generales
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

            <div class="bg-zinc-900/90 rounded-2xl p-5 border border-zinc-700 card-glow">
                <p class="text-zinc-400 text-sm">
                    Ganadas
                </p>

                <p class="text-3xl font-black text-green-400">
                    <?= $ganadas ?>
                </p>

                <p class="text-zinc-500 text-xs">
                    <?= number_format($porcentajeGanadas, 1) ?>%
                </p>
            </div>

            <div class="bg-zinc-900/90 rounded-2xl p-5 border border-zinc-700 card-glow">
                <p class="text-zinc-400 text-sm">
                    Perdidas
                </p>

                <p class="text-3xl font-black text-red-500">
                    <?= $perdidas ?>
                </p>

                <p class="text-zinc-500 text-xs">
                    <?= number_format($porcentajePerdidas, 1) ?>%
                </p>
            </div>

            <div class="bg-zinc-900/90 rounded-2xl p-5 border border-zinc-700 card-glow">
                <p class="text-zinc-400 text-sm">
                    Empates
                </p>

                <p class="text-3xl font-black text-yellow-400">
                    <?= $empates ?>
                </p>

                <p class="text-zinc-500 text-xs">
                    <?= number_format($porcentajeEmpates, 1) ?>%
                </p>
            </div>

            <div class="bg-zinc-900/90 rounded-2xl p-5 border border-zinc-700 card-glow">
                <p class="text-zinc-400 text-sm">
                    Ganancias
                </p>

                <p class="text-3xl font-black text-green-400">
                    $<?= dinero($ganancias) ?>
                </p>
            </div>

            <div class="bg-zinc-900/90 rounded-2xl p-5 border border-zinc-700 card-glow">
                <p class="text-zinc-400 text-sm">
                    Pérdidas
                </p>

                <p class="text-3xl font-black text-red-500">
                    $<?= dinero($perdidasDinero) ?>
                </p>
            </div>

            <div class="bg-zinc-900/90 rounded-2xl p-5 border border-zinc-700 card-glow">
                <p class="text-zinc-400 text-sm">
                    Balance
                </p>

                <p class="text-3xl font-black <?= $balanceNeto >= 0 ? 'text-green-400' : 'text-red-500' ?>">
                    <?= $balanceNeto >= 0 ? '+' : '-' ?>$<?= dinero(abs($balanceNeto)) ?>
                </p>
            </div>

        </div>
    </section>

    <!-- ESTADÍSTICAS POR JUEGO -->
    <section>
        <h2 class="text-3xl font-black mb-4 text-yellow-400">
            🎰 Estadísticas por juego
        </h2>

        <div class="bg-zinc-900/90 border border-zinc-700 rounded-3xl overflow-hidden table-scroll overflow-x-auto card-glow">

            <table class="w-full min-w-[850px] text-left">

                <thead class="bg-black/60 text-yellow-400 uppercase text-sm">
                    <tr>
                        <th class="p-4">Juego</th>
                        <th class="p-4">Apuestas</th>
                        <th class="p-4">Total apostado</th>
                        <th class="p-4">Ganadas</th>
                        <th class="p-4">Perdidas</th>
                        <th class="p-4">Empates</th>
                        <th class="p-4">Balance</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($statsPorJuego && $statsPorJuego->num_rows > 0): ?>
                        <?php while ($juego = $statsPorJuego->fetch_assoc()): ?>
                            <?php $balanceJuego = (float) $juego['balance_neto']; ?>

                            <tr class="border-t border-zinc-800 hover:bg-white/5 transition">
                                <td class="p-4 font-black">
                                    <?= escapar($juego['juego']) ?>
                                </td>

                                <td class="p-4">
                                    <?=(int) $juego['total_apuestas'] ?>
                                </td>

                                <td class="p-4">
                                    $<?= dinero($juego['total_apostado']) ?>
                                </td>

                                <td class="p-4 text-green-400">
                                    <?= (int) $juego['ganadas'] ?>
                                </td>

                                <td class="p-4 text-red-500">
                                    <?= (int) $juego['perdidas'] ?>
                                </td>

                                <td class="p-4 text-yellow-400">
                                    <?= (int) $juego['empates'] ?>
                                </td>

                                <td class="p-4 font-black <?= $balanceJuego >= 0 ? 'text-green-400' : 'text-red-500' ?>">
                                    <?= $balanceJuego >= 0 ? '+' : '-' ?>$<?= dinero(abs($balanceJuego)) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-zinc-400">
                                Todavía no tienes apuestas registradas por juego.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

        </div>
    </section>

    <!-- HISTORIAL RECIENTE -->
    <section>
        <h2 class="text-3xl font-black mb-4 text-yellow-400">
            📜 Historial reciente de apuestas
        </h2>

        <div class="bg-zinc-900/90 border border-zinc-700 rounded-3xl overflow-hidden table-scroll overflow-x-auto card-glow">

            <table class="w-full min-w-[950px] text-left">

                <thead class="bg-black/60 text-yellow-400 uppercase text-sm">
                    <tr>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Juego</th>
                        <th class="p-4">Apostado</th>
                        <th class="p-4">Resultado</th>
                        <th class="p-4">Multiplicador</th>
                        <th class="p-4">Pago</th>
                        <th class="p-4">Ganancia neta</th>
                        <th class="p-4">Saldo sesión después</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($historial && $historial->num_rows > 0): ?>
                        <?php while ($fila = $historial->fetch_assoc()): ?>
                            <?php
                                $resultado = $fila['resultado'];
                                $gananciaFila = (float) $fila['ganancia_neta'];

                                if ($resultado === 'win') {
                                    $resultadoClase = 'bg-green-500/20 text-green-400 border-green-400';
                                    $resultadoTexto = 'Ganada';
                                } elseif ($resultado === 'loss') {
                                    $resultadoClase = 'bg-red-500/20 text-red-400 border-red-400';
                                    $resultadoTexto = 'Perdida';
                                } elseif ($resultado === 'tie') {
                                    $resultadoClase = 'bg-yellow-500/20 text-yellow-400 border-yellow-400';
                                    $resultadoTexto = 'Empate';
                                } else {
                                    $resultadoClase = 'bg-zinc-500/20 text-zinc-400 border-zinc-400';
                                    $resultadoTexto = ucfirst((string) $resultado);
                                }
                            ?>

                            <tr class="border-t border-zinc-800 hover:bg-white/5 transition">
                                <td class="p-4 text-zinc-300">
                                    <?= escapar($fila['fecha']) ?>
                                </td>

                                <td class="p-4 font-black">
                                    <?= escapar($fila['juego']) ?>
                                </td>

                                <td class="p-4">
                                    $<?= dinero($fila['monto_apostado']) ?>
                                </td>

                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full border text-sm font-black <?= $resultadoClase ?>">
                                        <?= escapar($resultadoTexto) ?>
                                    </span>
                                </td>

                                <td class="p-4">
                                    x<?= dinero($fila['multiplicador']) ?>
                                </td>

                                <td class="p-4">
                                    $<?= dinero($fila['pago']) ?>
                                </td>

                                <td class="p-4 font-black <?= $gananciaFila >= 0 ? 'text-green-400' : 'text-red-500' ?>">
                                    <?= $gananciaFila >= 0 ? '+' : '-' ?>$<?= dinero(abs($gananciaFila)) ?>
                                </td>

                                <td class="p-4 text-yellow-400 font-black">
                                    $<?= dinero($fila['saldo_sesion_despues']) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-zinc-400">
                                Todavía no hay historial de apuestas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

        </div>
    </section>

</main>

</body>
</html>
