<?php
require_once __DIR__ . '/session_helpers.php';
include("conexion.php");

init_session();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$idUsuario = (int) $_SESSION['id_usuario'];

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
if (!columnaExiste($conexion, "usuarios", "usuario")) {
    $conexion->query("ALTER TABLE usuarios ADD COLUMN usuario VARCHAR(100) NULL");
}

if (!columnaExiste($conexion, "usuarios", "nombre")) {
    $conexion->query("ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(100) DEFAULT 'Usuario Anónimo'");
}

if (!columnaExiste($conexion, "usuarios", "dni")) {
    $conexion->query("ALTER TABLE usuarios ADD COLUMN dni VARCHAR(20) NULL");
}

if (!columnaExiste($conexion, "usuarios", "email")) {
    $conexion->query("ALTER TABLE usuarios ADD COLUMN email VARCHAR(100) NULL");
}

if (!columnaExiste($conexion, "usuarios", "password_hash")) {
    $conexion->query("ALTER TABLE usuarios ADD COLUMN password_hash VARCHAR(255) NULL");
}

if (!columnaExiste($conexion, "usuarios", "estado")) {
    $conexion->query("
        ALTER TABLE usuarios 
        ADD COLUMN estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo'
    ");
}

/*
    Guardar cambios del perfil.
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_perfil'])) {
    $nuevoNombreUsuario = isset($_POST['nombre_usuario']) ? trim($_POST['nombre_usuario']) : '';
    $nuevoUsuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $nuevoNombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $nuevoDni = isset($_POST['dni']) ? trim($_POST['dni']) : '';
    $nuevoEmail = isset($_POST['email']) ? trim($_POST['email']) : '';

    $nuevaPassword = isset($_POST['nueva_password']) ? trim($_POST['nueva_password']) : '';
    $repetirPassword = isset($_POST['repetir_password']) ? trim($_POST['repetir_password']) : '';

    if (
        $nuevoNombreUsuario === '' ||
        $nuevoUsuario === '' ||
        $nuevoNombre === '' ||
        $nuevoDni === '' ||
        $nuevoEmail === ''
    ) {
        $_SESSION['perfil_error'] = "Debes rellenar todos los campos.";
        header("Location: perfil.php");
        exit();
    }

    if (!filter_var($nuevoEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['perfil_error'] = "El email no tiene un formato válido.";
        header("Location: perfil.php");
        exit();
    }

    if ($nuevaPassword !== '' || $repetirPassword !== '') {
        if (strlen($nuevaPassword) < 6) {
            $_SESSION['perfil_error'] = "La contraseña debe tener al menos 6 caracteres.";
            header("Location: perfil.php");
            exit();
        }

        if ($nuevaPassword !== $repetirPassword) {
            $_SESSION['perfil_error'] = "Las contraseñas no coinciden.";
            header("Location: perfil.php");
            exit();
        }
    }

    /*
        Comprobar duplicados en otros usuarios.
    */
    $stmt = $conexion->prepare("
        SELECT id_usuario
        FROM usuarios
        WHERE id_usuario <> ?
          AND (
                nombre_usuario = ?
             OR usuario = ?
             OR email = ?
             OR dni = ?
          )
        LIMIT 1
    ");

    if (!$stmt) {
        die("Error comprobando duplicados: " . $conexion->error);
    }

    $stmt->bind_param(
        "issss",
        $idUsuario,
        $nuevoNombreUsuario,
        $nuevoUsuario,
        $nuevoEmail,
        $nuevoDni
    );

    $stmt->execute();
    $duplicado = $stmt->get_result()->fetch_assoc();

    if ($duplicado) {
        $_SESSION['perfil_error'] = "Ya existe otro usuario con esos datos.";
        header("Location: perfil.php");
        exit();
    }

    /*
        Actualizar datos.
    */
    $stmt = $conexion->prepare("
        UPDATE usuarios
        SET
            nombre_usuario = ?,
            usuario = ?,
            nombre = ?,
            dni = ?,
            email = ?
        WHERE id_usuario = ?
    ");

    if (!$stmt) {
        die("Error actualizando perfil: " . $conexion->error);
    }

    $stmt->bind_param(
        "sssssi",
        $nuevoNombreUsuario,
        $nuevoUsuario,
        $nuevoNombre,
        $nuevoDni,
        $nuevoEmail,
        $idUsuario
    );

    $stmt->execute();

    /*
        Cambiar contraseña si se indicó.
    */
    if ($nuevaPassword !== '') {
        $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("
            UPDATE usuarios
            SET password_hash = ?
            WHERE id_usuario = ?
        ");

        if (!$stmt) {
            die("Error actualizando contraseña: " . $conexion->error);
        }

        $stmt->bind_param("si", $passwordHash, $idUsuario);
        $stmt->execute();
    }

    $_SESSION['nombre_usuario'] = $nuevoNombreUsuario;
    $_SESSION['perfil_ok'] = "Perfil actualizado correctamente.";

    header("Location: perfil.php");
    exit();
}

/*
    Cargar usuario.
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mi perfil - High Stakes</title>

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

    input {
        font-size: 16px !important;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 1.55rem !important;
    }

    .p-8 {
        padding: 1.25rem !important;
    }

    .p-6 {
        padding: 1rem !important;
    }
}
</style>
</head>

<body class="min-h-screen text-white">

<header class="bg-black/70 backdrop-blur-md border-b border-yellow-400/30 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-4">

        <div class="text-center md:text-left">
            <h1 class="text-3xl font-black text-yellow-400">
                👤 Mi perfil
            </h1>

            <p class="text-zinc-400">
                Edita tus datos, <?= escapar($nombreMostrado) ?>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <a href="principal.php"
               class="bg-yellow-400 hover:bg-yellow-300 text-black px-5 py-3 rounded-xl font-black transition text-center">
                🏠 Principal
            </a>

            <a href="historial.php"
               class="bg-blue-600 hover:bg-blue-500 px-5 py-3 rounded-xl font-black transition text-center">
                📊 Historial
            </a>

            <a href="logout.php"
               class="bg-red-600 hover:bg-red-500 px-5 py-3 rounded-xl font-black transition text-center">
                Cerrar sesión
            </a>
        </div>

    </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-8 space-y-8">

    <?php if (isset($_SESSION['perfil_ok'])): ?>
        <div class="bg-green-500/20 border border-green-400 text-green-300 p-4 rounded-2xl font-black">
            <?= escapar($_SESSION['perfil_ok']) ?>
        </div>
        <?php unset($_SESSION['perfil_ok']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['perfil_error'])): ?>
        <div class="bg-red-500/20 border border-red-400 text-red-300 p-4 rounded-2xl font-black">
            <?= escapar($_SESSION['perfil_error']) ?>
        </div>
        <?php unset($_SESSION['perfil_error']); ?>
    <?php endif; ?>

    <section class="bg-zinc-900/90 border border-yellow-400/30 rounded-3xl p-6 md:p-8 card-glow">

        <div class="mb-6">
            <h2 class="text-3xl font-black text-yellow-400">
                ✏️ Editar datos personales
            </h2>

            <p class="text-zinc-400">
                Todos los campos son obligatorios. La contraseña solo cambia si escribes una nueva.
            </p>
        </div>

        <form method="POST" class="space-y-6">

            <input type="hidden" name="editar_perfil" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-zinc-300 font-bold mb-2">
                        Nombre de usuario
                    </label>

                    <input type="text"
                           name="nombre_usuario"
                           required
                           value="<?= escapar($usuario['nombre_usuario'] ?? '') ?>"
                           class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                </div>

                <div>
                    <label class="block text-zinc-300 font-bold mb-2">
                        Alias visible
                    </label>

                    <input type="text"
                           name="usuario"
                           required
                           value="<?= escapar($usuario['usuario'] ?? '') ?>"
                           class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                </div>

                <div>
                    <label class="block text-zinc-300 font-bold mb-2">
                        Nombre completo
                    </label>

                    <input type="text"
                           name="nombre"
                           required
                           value="<?= escapar($usuario['nombre'] ?? '') ?>"
                           class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                </div>

                <div>
                    <label class="block text-zinc-300 font-bold mb-2">
                        DNI
                    </label>

                    <input type="text"
                           name="dni"
                           required
                           value="<?= escapar($usuario['dni'] ?? '') ?>"
                           class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-zinc-300 font-bold mb-2">
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           required
                           value="<?= escapar($usuario['email'] ?? '') ?>"
                           class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                </div>

            </div>

            <div class="bg-black/40 border border-zinc-700 rounded-2xl p-5">

                <h3 class="text-xl font-black text-yellow-400 mb-2">
                    Cambiar contraseña
                </h3>

                <p class="text-zinc-400 text-sm mb-4">
                    Déjalo vacío si no quieres cambiarla.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Nueva contraseña
                        </label>

                        <input type="password"
                               name="nueva_password"
                               minlength="6"
                               placeholder="Mínimo 6 caracteres"
                               class="w-full bg-zinc-900 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Repetir contraseña
                        </label>

                        <input type="password"
                               name="repetir_password"
                               minlength="6"
                               placeholder="Repite la contraseña"
                               class="w-full bg-zinc-900 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                </div>

            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        class="bg-yellow-400 hover:bg-yellow-300 text-black px-6 py-4 rounded-xl font-black transition">
                    Guardar cambios
                </button>

                <a href="principal.php"
                   class="bg-zinc-700 hover:bg-zinc-600 px-6 py-4 rounded-xl font-black transition text-center">
                    Volver al principal
                </a>
            </div>

        </form>

    </section>

</main>

</body>
</html>
