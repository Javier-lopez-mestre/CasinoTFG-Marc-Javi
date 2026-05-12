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
    Asegurar columnas necesarias.
*/
if (!columnaExiste($conexion, "usuarios", "usuario")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN usuario VARCHAR(100) NULL
    ");
}

if (!columnaExiste($conexion, "usuarios", "nombre")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN nombre VARCHAR(100) DEFAULT 'Usuario Anónimo'
    ");
}

if (!columnaExiste($conexion, "usuarios", "dni")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN dni VARCHAR(20) NULL
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

if (!columnaExiste($conexion, "usuarios", "saldo")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN saldo DECIMAL(12,2) NOT NULL DEFAULT 0.00
    ");
}

if (!columnaExiste($conexion, "usuarios", "total_dinero")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN total_dinero DECIMAL(12,2) NOT NULL DEFAULT 0.00
    ");
}

if (!columnaExiste($conexion, "usuarios", "fecha_registro")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ");
}

if (!columnaExiste($conexion, "usuarios", "estado")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo'
    ");
}

$error = "";

$nombreUsuario = "";
$usuario = "";
$nombre = "";
$dni = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombreUsuario = isset($_POST["nombre_usuario"]) ? trim($_POST["nombre_usuario"]) : "";
    $usuario = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $dni = isset($_POST["dni"]) ? trim($_POST["dni"]) : "";
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";
    $password2 = isset($_POST["password2"]) ? trim($_POST["password2"]) : "";

    /*
        Todos los campos son obligatorios.
    */
    if (
        $nombreUsuario === "" ||
        $usuario === "" ||
        $nombre === "" ||
        $dni === "" ||
        $email === "" ||
        $password === "" ||
        $password2 === ""
    ) {
        $error = "Debes rellenar todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no tiene un formato válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $password2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        /*
            Si ya existe usuario, email o DNI, enviamos al login.
        */
        $stmt = $conexion->prepare("
            SELECT id_usuario
            FROM usuarios
            WHERE nombre_usuario = ?
               OR usuario = ?
               OR email = ?
               OR dni = ?
            LIMIT 1
        ");

        $stmt->bind_param("ssss", $nombreUsuario, $usuario, $email, $dni);
        $stmt->execute();

        $existe = $stmt->get_result()->fetch_assoc();

        if ($existe) {
            header("Location: index.php?registro=existe");
            exit();
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        /*
            Saldo inicial.
            Puedes cambiar 1000.00 por 0.00 si no quieres saldo inicial.
        */
        $saldoInicial = 1000.00;
        $totalDinero = $saldoInicial;
        $estado = "activo";

        $stmt = $conexion->prepare("
            INSERT INTO usuarios (
                nombre_usuario,
                usuario,
                nombre,
                dni,
                email,
                password_hash,
                saldo,
                total_dinero,
                estado,
                fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssssssdds",
            $nombreUsuario,
            $usuario,
            $nombre,
            $dni,
            $email,
            $passwordHash,
            $saldoInicial,
            $totalDinero,
            $estado
        );

        if ($stmt->execute()) {
            header("Location: index.php?registro=ok");
            exit();
        } else {
            $error = "Error al registrar el usuario.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registro - High Stakes</title>

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
        linear-gradient(to bottom, rgba(0,0,0,0.55), rgba(0,0,0,0.9));
    z-index: -2;
}

.glass-card {
    background: rgba(24, 24, 27, 0.82);
    backdrop-filter: blur(18px);
    border: 1px solid rgba(250,204,21,0.35);
    box-shadow:
        0 25px 80px rgba(0,0,0,0.6),
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

.float-chip {
    animation: floatChip 3s ease-in-out infinite;
}

@keyframes floatChip {
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
<!-- Cambia la ruta si tu vídeo se llama distinto -->
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
            <a href="index.php" class="bg-white/10 hover:bg-white/20 border border-white/20 px-4 py-2 rounded-xl font-bold transition">
                Iniciar sesión
            </a>

            <a href="principal.php" class="hidden sm:inline-block bg-yellow-400 hover:bg-yellow-300 text-black px-4 py-2 rounded-xl font-black transition">
                Inicio
            </a>
        </nav>

    </div>
</header>

<main class="relative z-10 min-h-screen pt-28 pb-10 px-4 flex items-center justify-center">

    <section class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-[1fr_1.15fr] gap-8 items-center">

        <!-- TEXTO IZQUIERDA -->
        <div class="hidden lg:block">

            <div class="float-chip inline-flex items-center justify-center w-24 h-24 rounded-full bg-yellow-400 text-black text-5xl font-black shadow-2xl mb-6">
                🎰
            </div>

            <h2 class="text-6xl font-black mb-5 leading-tight">
                Crea tu cuenta en
                <span class="gold-text block">
                    High Stakes
                </span>
            </h2>

            <p class="text-xl text-zinc-300 leading-relaxed max-w-xl">
                Regístrate para acceder al panel principal, consultar tu saldo,
                jugar con sesiones controladas y ver tu historial de partidas.
            </p>

            <div class="grid grid-cols-3 gap-4 mt-8 max-w-xl">
                <div class="bg-black/45 border border-yellow-400/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🃏</div>
                    <p class="font-black text-yellow-400">Blackjack</p>
                </div>

                <div class="bg-black/45 border border-yellow-400/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">💣</div>
                    <p class="font-black text-yellow-400">Minas</p>
                </div>

                <div class="bg-black/45 border border-yellow-400/20 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🎡</div>
                    <p class="font-black text-yellow-400">Ruleta</p>
                </div>
            </div>

        </div>

        <!-- FORMULARIO -->
        <div class="glass-card rounded-3xl p-6 md:p-8">

            <div class="text-center mb-7">
                <div class="text-5xl mb-3">👑</div>

                <h2 class="text-4xl md:text-5xl font-black text-yellow-400">
                    Registro
                </h2>

                <p class="text-zinc-400 mt-2">
                    Todos los campos son obligatorios.
                </p>
            </div>

            <?php if ($error !== ""): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 p-4 rounded-2xl mb-5 font-bold text-center">
                    <?= escapar($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Nombre de usuario
                        </label>

                        <input type="text"
                               name="nombre_usuario"
                               required
                               autocomplete="off"
                               value="<?= escapar($nombreUsuario) ?>"
                               placeholder="Ej: jugador123"
                               class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Alias visible
                        </label>

                        <input type="text"
                               name="usuario"
                               required
                               autocomplete="off"
                               value="<?= escapar($usuario) ?>"
                               placeholder="Ej: HighRoller"
                               class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Nombre completo
                        </label>

                        <input type="text"
                               name="nombre"
                               required
                               autocomplete="name"
                               value="<?= escapar($nombre) ?>"
                               placeholder="Ej: Marc García"
                               class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            DNI
                        </label>

                        <input type="text"
                               name="dni"
                               required
                               autocomplete="off"
                               value="<?= escapar($dni) ?>"
                               placeholder="Ej: 12345678A"
                               class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Email
                        </label>

                        <input type="email"
                               name="email"
                               required
                               autocomplete="email"
                               value="<?= escapar($email) ?>"
                               placeholder="ejemplo@email.com"
                               class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Contraseña
                        </label>

                        <input type="password"
                               name="password"
                               required
                               minlength="6"
                               autocomplete="new-password"
                               placeholder="Mínimo 6 caracteres"
                               class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-zinc-300 font-bold mb-2">
                            Repetir contraseña
                        </label>

                        <input type="password"
                               name="password2"
                               required
                               minlength="6"
                               autocomplete="new-password"
                               placeholder="Repite la contraseña"
                               class="input-casino w-full rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                </div>

                <div class="bg-black/35 border border-yellow-400/20 rounded-2xl p-4 text-sm text-zinc-400">
                    <p>
                        Al registrarte aceptas usar esta plataforma como proyecto educativo.
                        Si ya existe un usuario con el mismo nombre, email o DNI, se te enviará al login.
                    </p>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-300 hover:to-orange-400 text-black py-5 rounded-2xl text-2xl font-black shadow-2xl transition transform hover:scale-[1.02]">
                    Crear cuenta
                </button>

            </form>

            <div class="mt-6 text-center">
                <p class="text-zinc-400">
                    ¿Ya tienes cuenta?
                </p>

                <a href="index.php"
                   class="inline-block mt-2 text-yellow-400 hover:text-yellow-300 font-black text-lg">
                    Inicia sesión aquí
                </a>
            </div>

        </div>

    </section>

</main>

</body>
</html>