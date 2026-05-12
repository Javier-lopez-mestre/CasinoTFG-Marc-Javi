<?php
session_start();
include("conexion.php");

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function columnaExiste($conexion, $tabla, $columna) {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    $stmt->bind_param("ss", $tabla, $columna);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return ((int) ($row['total'] ?? 0)) > 0;
}

/*
    Asegurar columnas necesarias para el login.
*/
if (!columnaExiste($conexion, "usuarios", "usuario")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN usuario VARCHAR(100) NULL
    ");
}

if (!columnaExiste($conexion, "usuarios", "email")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN email VARCHAR(100) NULL
    ");
}

if (!columnaExiste($conexion, "usuarios", "password_hash")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN password_hash VARCHAR(255) NULL
    ");
}

if (!columnaExiste($conexion, "usuarios", "estado")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo'
    ");
}

/*
    Si ya está logueado, comprobar que no esté bloqueado.
*/
if (isset($_SESSION['id_usuario'])) {
    $idSesion = (int) $_SESSION['id_usuario'];

    $stmt = $conexion->prepare("
        SELECT estado
        FROM usuarios
        WHERE id_usuario = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $idSesion);
        $stmt->execute();

        $usuarioSesion = $stmt->get_result()->fetch_assoc();

        if (!$usuarioSesion) {
            session_destroy();
            header("Location: index.php");
            exit();
        }

        $estadoSesion = $usuarioSesion['estado'] ?? 'activo';

        if ($estadoSesion === 'suspendido' || $estadoSesion === 'bloqueado') {
            session_destroy();
            header("Location: index.php?error=cuenta_bloqueada");
            exit();
        }

        header("Location: principal.php");
        exit();
    }
}

$error = "";
$mensaje = "";

if (isset($_GET['registro']) && $_GET['registro'] === 'ok') {
    $mensaje = "Cuenta creada correctamente. Ya puedes iniciar sesión.";
}

if (isset($_GET['registro']) && $_GET['registro'] === 'existe') {
    $mensaje = "Ese usuario ya existe. Inicia sesión con tu cuenta.";
}

if (isset($_GET['error']) && $_GET['error'] === 'cuenta_bloqueada') {
    $error = "Usuario bloqueado. Contacta con el administrador.";
}

$identificador = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identificador = isset($_POST["identificador"]) ? trim($_POST["identificador"]) : "";
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

    if ($identificador === "" || $password === "") {
        $error = "Debes rellenar usuario/email y contraseña.";
    } else {
        /*
            Permite entrar con:
            - nombre_usuario
            - usuario
            - email
        */
        $stmt = $conexion->prepare("
            SELECT *
            FROM usuarios
            WHERE nombre_usuario = ?
               OR usuario = ?
               OR email = ?
            LIMIT 1
        ");

        if (!$stmt) {
            die("Error preparando login: " . $conexion->error);
        }

        $stmt->bind_param("sss", $identificador, $identificador, $identificador);
        $stmt->execute();

        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario) {
            $error = "Usuario o contraseña incorrectos.";
        } else {
            $estado = $usuario['estado'] ?? 'activo';

            if ($estado === 'suspendido' || $estado === 'bloqueado') {
                $error = "Usuario bloqueado. Contacta con el administrador.";
            } elseif ($estado === 'inactivo') {
                $error = "Usuario inactivo.";
            } else {
                $loginCorrecto = false;

                /*
                    Login moderno con password_hash.
                */
                if (!empty($usuario['password_hash'])) {
                    if (password_verify($password, $usuario['password_hash'])) {
                        $loginCorrecto = true;
                    }
                }

                /*
                    Compatibilidad con posibles contraseñas antiguas.
                */
                if (!$loginCorrecto && isset($usuario['password']) && $usuario['password'] !== '') {
                    if ($password === $usuario['password']) {
                        $loginCorrecto = true;
                    }
                }

                if (!$loginCorrecto && isset($usuario['contrasena']) && $usuario['contrasena'] !== '') {
                    if ($password === $usuario['contrasena']) {
                        $loginCorrecto = true;
                    }
                }

                if (!$loginCorrecto) {
                    $error = "Usuario o contraseña incorrectos.";
                } else {
                    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
                    $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'] ?? '';

                    header("Location: principal.php");
                    exit();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - High Stakes</title>

<link rel="icon" type="image/png" href="img/logocuadrado.png">
<script src="https://cdn.tailwindcss.com"></script>

<style>
body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.video-bg {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -3;
}

.video-overlay {
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at top, rgba(250,204,21,0.18), transparent 35%),
        linear-gradient(to bottom, rgba(0,0,0,0.50), rgba(0,0,0,0.92));
    z-index: -2;
}

.glass-card {
    background: rgba(24, 24, 27, 0.84);
    backdrop-filter: blur(18px);
    border: 1px solid rgba(250,204,21,0.35);
    box-shadow:
        0 25px 80px rgba(0,0,0,0.65),
        0 0 60px rgba(250,204,21,0.12);
}

.input-casino {
    background: rgba(0,0,0,0.35);
    border: 2px solid rgba(113,113,122,0.75);
    transition: all 0.2s ease;
}

.input-casino:focus {
    border-color: #facc15;
    box-shadow: 0 0 25px rgba(250,204,21,0.25);
}

.gold-text {
    background: linear-gradient(90deg, #facc15, #f97316, #facc15);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.float-card {
    animation: floatCard 3.2s ease-in-out infinite;
}

@keyframes floatCard {
    0%, 100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }
}
</style>
</head>

<body class="min-h-screen text-white overflow-x-hidden">

<!-- VIDEO DE FONDO -->
<video class="video-bg" autoplay muted loop playsinline>
    <source src="videos/fondo.mp4" type="video/mp4">
</video>

<div class="video-overlay"></div>

<!-- HEADER -->
<header class="fixed top-0 left-0 w-full z-50 bg-black/55 backdrop-blur-md border-b border-yellow-400/20">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">

        <a href="index.php" class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-yellow-400 text-black flex items-center justify-center text-2xl font-black shadow-xl">
                ♠
            </div>

            <div>
                <h1 class="text-2xl md:text-3xl font-black text-yellow-400 leading-none">
                    HIGH STAKES
                </h1>
                <p class="text-xs text-zinc-400 font-bold">
                    Casino escolar
                </p>
            </div>
        </a>

        <nav class="flex items-center gap-3">
            <a href="registro.php" class="bg-yellow-400 hover:bg-yellow-300 text-black px-4 py-2 rounded-xl font-black transition">
                Registrarse
                        </a>
        </nav>

    </div>
</header>

<main class="relative z-10 min-h-screen pt-28 pb-10 px-4 flex items-center justify-center">

    <section class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-8 items-center">

        <!-- TEXTO IZQUIERDA -->
        <div class="hidden lg:block">

            <div class="float-card inline-flex items-center justify-center w-24 h-24 rounded-full bg-yellow-400 text-black text-5xl font-black shadow-2xl mb-6">
                👑
            </div>

            <h2 class="text-6xl font-black mb-5 leading-tight">
                Bienvenido a
                <span class="gold-text block">
                    High Stakes
                </span>
            </h2>

            <p class="text-xl text-zinc-300 leading-relaxed max-w-xl">
                Inicia sesión para acceder al panel principal, ver tu saldo,
                gestionar tu sesión de juego y consultar tu historial.
            </p>

            <div class="grid grid-cols-3 gap-4 mt-8 max-w-xl">
                <div class="bg-black/45 border border-yellow-400/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🃏</div>
                    <p class="font-black text-yellow-400">Blackjack</p>
                </div>

                <div class="bg-black/45 border border-yellow-400/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🎰</div>
                    <p class="font-black text-yellow-400">Slots</p>
                </div>

                <div class="bg-black/45 border border-yellow-400/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🎡</div>
                    <p class="font-black text-yellow-400">Ruleta</p>
                </div>
            </div>

        </div>

        <!-- FORMULARIO LOGIN -->
        <div class="glass-card rounded-3xl p-6 md:p-8">

            <div class="text-center mb-7">
                <div class="text-5xl mb-3">🔐</div>

                <h2 class="text-4xl md:text-5xl font-black text-yellow-400">
                    Iniciar sesión
                </h2>

                <p class="text-zinc-400 mt-2">
                    Entra con tu usuario, alias o email.
                </p>
            </div>

            <?php if ($mensaje !== ""): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 p-4 rounded-2xl mb-5 font-bold text-center">
                    <?= escapar($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 p-4 rounded-2xl mb-5 font-bold text-center">
                    <?= escapar($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">

                <div>
                    <label class="block text-zinc-300 font-bold mb-2">
                        Usuario, alias o email
                    </label>

                    <input type="text"
                           name="identificador"
                           required
                           autocomplete="username"
                           value="<?= escapar($identificador) ?>"
                           placeholder="Ej: jugador123"
                           class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                </div>

                <div>
                    <label class="block text-zinc-300 font-bold mb-2">
                        Contraseña
                    </label>

                    <input type="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="Tu contraseña"
                           class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-300 hover:to-orange-400 text-black py-5 rounded-2xl text-2xl font-black shadow-2xl transition transform hover:scale-[1.02]">
                    Entrar
                </button>

            </form>

            <div class="mt-6 text-center">
                <p class="text-zinc-400">
                    ¿Todavía no tienes cuenta?
                </p>

                <a href="registro.php"
                   class="inline-block mt-2 text-yellow-400 hover:text-yellow-300 font-black text-lg">
                    Crear cuenta nueva
                </a>
            </div>

        </div>

    </section>

</main>

</body>
</html>