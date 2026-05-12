<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

include("../conexion.php");

$ADMIN_USER = "hightstakes";
$ADMIN_PASS = "superlocal";

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function dinero($valor) {
    return number_format((float) $valor, 2);
}

function tablaExiste($conexion, $tabla) {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");
    $stmt->bind_param("s", $tabla);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return ((int) ($row['total'] ?? 0)) > 0;
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
if (tablaExiste($conexion, "usuarios")) {
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

    if (!columnaExiste($conexion, "usuarios", "total_dinero")) {
        $conexion->query("ALTER TABLE usuarios ADD COLUMN total_dinero DECIMAL(12,2) NOT NULL DEFAULT 0.00");
    }

    if (!columnaExiste($conexion, "usuarios", "fecha_registro")) {
        $conexion->query("ALTER TABLE usuarios ADD COLUMN fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    if (!columnaExiste($conexion, "usuarios", "estado")) {
        $conexion->query("
            ALTER TABLE usuarios 
            ADD COLUMN estado ENUM('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo'
        ");
    }

    if (!columnaExiste($conexion, "usuarios", "password_hash")) {
        $conexion->query("ALTER TABLE usuarios ADD COLUMN password_hash VARCHAR(255) NULL");
    }

    $conexion->query("
        UPDATE usuarios
        SET usuario = nombre_usuario
        WHERE (usuario IS NULL OR usuario = '')
          AND nombre_usuario IS NOT NULL
    ");

    $conexion->query("
        UPDATE usuarios
        SET nombre = 'Usuario Anónimo'
        WHERE nombre IS NULL OR nombre = ''
    ");

    $conexion->query("
        UPDATE usuarios
        SET estado = 'activo'
        WHERE estado IS NULL OR estado = ''
    ");
}

if (tablaExiste($conexion, "historial_apuestas")) {
    if (!columnaExiste($conexion, "historial_apuestas", "detalle")) {
        $conexion->query("ALTER TABLE historial_apuestas ADD COLUMN detalle LONGTEXT NULL");
    }
}

/*
    Login admin.
*/
$errorLogin = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";

    if ($usuario === $ADMIN_USER && $password === $ADMIN_PASS) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user'] = $ADMIN_USER;

        header("Location: /admin");
        exit();
    }

    $errorLogin = "Usuario o contraseña incorrectos.";
}

/*
    Logout.
*/
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged']);
    unset($_SESSION['admin_user']);

    header("Location: /admin");
    exit();
}

$adminLogged = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;

/*
    Pantalla login.
*/
if (!$adminLogged):
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - High Stakes</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body {
    background: radial-gradient(circle at top, #1e293b, #020617 70%);
}
</style>
</head>

<body class="min-h-screen text-white flex items-center justify-center px-4">

<div class="w-full max-w-md bg-zinc-900/95 border border-yellow-400/40 rounded-3xl p-8 shadow-2xl">

    <div class="text-center mb-8">
        <h1 class="text-5xl font-black text-yellow-400 mb-2">ADMIN</h1>
        <p class="text-zinc-400">Panel interno High Stakes</p>
    </div>

    <?php if ($errorLogin !== ""): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 p-4 rounded-xl mb-5 font-bold">
            <?= escapar($errorLogin) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <input type="hidden" name="admin_login" value="1">

        <div>
            <label class="block text-zinc-300 font-bold mb-2">Usuario administrador</label>
            <input type="text" name="usuario" autocomplete="off"
                   class="w-full bg-zinc-800 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold"
                   placeholder="hightstakes">
        </div>

        <div>
            <label class="block text-zinc-300 font-bold mb-2">Contraseña</label>
            <input type="password" name="password"
                   class="w-full bg-zinc-800 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold"
                   placeholder="superlocal">
        </div>

        <button type="submit"
                class="w-full bg-yellow-400 hover:bg-yellow-300 text-black py-4 rounded-xl font-black text-xl transition">
            Entrar al panel
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="../principal.php" class="text-zinc-400 hover:text-yellow-400 font-bold">
            Volver al casino
        </a>
    </div>

</div>

</body>
</html>
<?php
exit();
endif;

/*
    Guardar edición usuario.
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_usuario'])) {
    $idUsuarioEditar = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;

    $nombreUsuario = isset($_POST['nombre_usuario']) ? trim($_POST['nombre_usuario']) : "";
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : "";
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : "";
    $dni = isset($_POST['dni']) ? trim($_POST['dni']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $saldo = isset($_POST['saldo']) ? (float) $_POST['saldo'] : 0;
    $totalDinero = isset($_POST['total_dinero']) ? (float) $_POST['total_dinero'] : 0;
    $fechaRegistro = isset($_POST['fecha_registro']) ? trim($_POST['fecha_registro']) : "";
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : "activo";
    $nuevaPassword = isset($_POST['nueva_password']) ? trim($_POST['nueva_password']) : "";

    if ($idUsuarioEditar <= 0) {
        $_SESSION['admin_flash_error'] = "Usuario inválido.";
        header("Location: /admin");
        exit();
    }

    if ($nombreUsuario === "") {
        $_SESSION['admin_flash_error'] = "El campo nombre_usuario no puede estar vacío.";
        header("Location: /admin?usuario=" . $idUsuarioEditar);
        exit();
    }

    if ($usuario === "") {
        $usuario = $nombreUsuario;
    }

    if ($nombre === "") {
        $nombre = "Usuario Anónimo";
    }

    if ($fechaRegistro === "") {
        $fechaRegistro = date("Y-m-d H:i:s");
    }

    if (!in_array($estado, ['activo', 'inactivo', 'suspendido'], true)) {
        $estado = "activo";
    }

    /*
        Comprobar nombre_usuario duplicado.
    */
    $stmt = $conexion->prepare("
        SELECT id_usuario
        FROM usuarios
        WHERE nombre_usuario = ?
          AND id_usuario <> ?
        LIMIT 1
    ");
    $stmt->bind_param("si", $nombreUsuario, $idUsuarioEditar);
    $stmt->execute();
    $duplicadoNombreUsuario = $stmt->get_result()->fetch_assoc();

    if ($duplicadoNombreUsuario) {
        $_SESSION['admin_flash_error'] = "Ya existe otro usuario con ese nombre_usuario.";
        header("Location: /admin?usuario=" . $idUsuarioEditar);
        exit();
    }

    /*
        Comprobar usuario duplicado.
    */
    if ($usuario !== "") {
        $stmt = $conexion->prepare("
            SELECT id_usuario
            FROM usuarios
            WHERE usuario = ?
              AND id_usuario <> ?
            LIMIT 1
        ");
        $stmt->bind_param("si", $usuario, $idUsuarioEditar);
        $stmt->execute();
        $duplicadoUsuario = $stmt->get_result()->fetch_assoc();

        if ($duplicadoUsuario) {
            $_SESSION['admin_flash_error'] = "Ya existe otro usuario con ese usuario.";
            header("Location: /admin?usuario=" . $idUsuarioEditar);
            exit();
        }
    }

    $stmt = $conexion->prepare("
        UPDATE usuarios
        SET 
            nombre_usuario = ?,
            usuario = ?,
            nombre = ?,
            dni = ?,
            email = ?,
            saldo = ?,
            total_dinero = ?,
            fecha_registro = ?,
            estado = ?
        WHERE id_usuario = ?
    ");

    $stmt->bind_param(
        "sssssddssi",
        $nombreUsuario,
        $usuario,
        $nombre,
        $dni,
        $email,
        $saldo,
        $totalDinero,
        $fechaRegistro,
        $estado,
        $idUsuarioEditar
    );

    $stmt->execute();

    if ($nuevaPassword !== "") {
        $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("
            UPDATE usuarios
            SET password_hash = ?
            WHERE id_usuario = ?
        ");
        $stmt->bind_param("si", $passwordHash, $idUsuarioEditar);
        $stmt->execute();
    }

    $_SESSION['admin_flash_ok'] = "Usuario actualizado correctamente.";

    header("Location: /admin?usuario=" . $idUsuarioEditar);
    exit();
}

/*
    Bloquear / activar usuario.
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_usuario'])) {
    $accion = $_POST['accion_usuario'];
    $idUsuarioAccion = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;

    if ($idUsuarioAccion > 0) {
        if ($accion === "bloquear") {
            $stmt = $conexion->prepare("
                UPDATE usuarios
                SET estado = 'suspendido'
                WHERE id_usuario = ?
            ");
            $stmt->bind_param("i", $idUsuarioAccion);
            $stmt->execute();

            $_SESSION['admin_flash_ok'] = "Usuario bloqueado correctamente.";
        }

        if ($accion === "activar") {
            $stmt = $conexion->prepare("
                UPDATE usuarios
                SET estado = 'activo'
                WHERE id_usuario = ?
            ");
            $stmt->bind_param("i", $idUsuarioAccion);
            $stmt->execute();

            $_SESSION['admin_flash_ok'] = "Usuario activado correctamente.";
        }
    }

    if (isset($_POST['volver_detalle']) && $_POST['volver_detalle'] === '1') {
        header("Location: /admin?usuario=" . $idUsuarioAccion);
        exit();
    }

    header("Location: /admin");
    exit();
}

/*
    Datos globales.
*/
$idSeleccionado = isset($_GET['usuario']) ? (int) $_GET['usuario'] : 0;

$resumenGlobal = [
    'total_usuarios' => 0,
    'usuarios_activos' => 0,
    'usuarios_suspendidos' => 0,
    'saldo_total' => 0
];

if (tablaExiste($conexion, "usuarios")) {
    $q = $conexion->query("
        SELECT
            COUNT(*) AS total_usuarios,
            COALESCE(SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END), 0) AS usuarios_activos,
            COALESCE(SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END), 0) AS usuarios_suspendidos,
            COALESCE(SUM(saldo), 0) AS saldo_total
        FROM usuarios
    ");

    if ($q) {
        $resumenGlobal = $q->fetch_assoc();
    }
}

$resumenHistorial = [
    'total_apuestas' => 0,
    'total_apostado' => 0,
    'total_pagado' => 0,
    'balance_global' => 0
];

if (tablaExiste($conexion, "historial_apuestas")) {
    $q = $conexion->query("
        SELECT
            COUNT(*) AS total_apuestas,
            COALESCE(SUM(monto_apostado), 0) AS total_apostado,
            COALESCE(SUM(pago), 0) AS total_pagado,
            COALESCE(SUM(ganancia_neta), 0) AS balance_global
        FROM historial_apuestas
    ");

    if ($q) {
        $resumenHistorial = $q->fetch_assoc();
    }
}

/*
    Cargar usuario seleccionado.
*/
$usuarioSeleccionado = null;
$estadisticasUsuario = [
    'total_apuestas' => 0,
    'total_apostado' => 0,
    'total_pagado' => 0,
    'balance_neto' => 0,
    'ganadas' => 0,
    'perdidas' => 0,
    'empates' => 0
];
$historialUsuario = null;

if ($idSeleccionado > 0) {
    $stmt = $conexion->prepare("
        SELECT *
        FROM usuarios
        WHERE id_usuario = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $idSeleccionado);
    $stmt->execute();
    $usuarioSeleccionado = $stmt->get_result()->fetch_assoc();

    if ($usuarioSeleccionado && tablaExiste($conexion, "historial_apuestas")) {
        $stmt = $conexion->prepare("
            SELECT
                COUNT(*) AS total_apuestas,
                COALESCE(SUM(monto_apostado), 0) AS total_apostado,
                COALESCE(SUM(pago), 0) AS total_pagado,
                COALESCE(SUM(ganancia_neta), 0) AS balance_neto,
                COALESCE(SUM(CASE WHEN resultado = 'win' THEN 1 ELSE 0 END), 0) AS ganadas,
                                COALESCE(SUM(CASE WHEN resultado = 'loss' THEN 1 ELSE 0 END), 0) AS perdidas,
                COALESCE(SUM(CASE WHEN resultado = 'tie' THEN 1 ELSE 0 END), 0) AS empates
            FROM historial_apuestas
            WHERE id_usuario = ?
        ");
        $stmt->bind_param("i", $idSeleccionado);
        $stmt->execute();
        $estadisticasUsuario = $stmt->get_result()->fetch_assoc();

        $stmt = $conexion->prepare("
            SELECT *
            FROM historial_apuestas
            WHERE id_usuario = ?
            ORDER BY fecha DESC
            LIMIT 300
        ");
        $stmt->bind_param("i", $idSeleccionado);
        $stmt->execute();
        $historialUsuario = $stmt->get_result();
    }
}

/*
    Tabla principal usuarios.
*/
$usuarios = null;

if ($idSeleccionado <= 0 && tablaExiste($conexion, "usuarios")) {
    $usuarios = $conexion->query("
        SELECT *
        FROM usuarios
        ORDER BY id_usuario ASC
    ");
}

/*
    Últimas transacciones globales.
*/
$ultimasTransacciones = null;

if ($idSeleccionado <= 0 && tablaExiste($conexion, "historial_apuestas")) {
    $ultimasTransacciones = $conexion->query("
        SELECT 
            h.*,
            u.nombre_usuario
        FROM historial_apuestas h
        LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
        ORDER BY h.fecha DESC
        LIMIT 50
    ");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Panel Admin - High Stakes</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>
body {
    background: radial-gradient(circle at top, #1e293b, #020617 70%);
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
</style>
</head>

<body class="min-h-screen text-white">

<header class="bg-black/70 backdrop-blur-md border-b border-yellow-400/30 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-4">

        <div>
            <h1 class="text-4xl font-black text-yellow-400">
                Panel Admin
            </h1>

            <p class="text-zinc-400">
                Sesión iniciada como <?= escapar($_SESSION['admin_user']) ?>
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <?php if ($idSeleccionado > 0): ?>
                <a href="/admin" class="bg-zinc-700 hover:bg-zinc-600 px-5 py-3 rounded-xl font-black transition">
                    ← Volver al panel
                </a>

                <button onclick="history.back()" class="bg-blue-600 hover:bg-blue-500 px-5 py-3 rounded-xl font-black transition">
                    ← Atrás
                </button>
            <?php endif; ?>

            <a href="../principal.php" class="bg-yellow-400 hover:bg-yellow-300 text-black px-5 py-3 rounded-xl font-black transition">
                Casino
            </a>

            <a href="/admin?logout=1" class="bg-red-600 hover:bg-red-500 px-5 py-3 rounded-xl font-black transition">
                Cerrar admin
            </a>
        </div>

    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

<?php if (isset($_SESSION['admin_flash_ok'])): ?>
    <div class="bg-green-500/20 border border-green-400 text-green-300 p-4 rounded-2xl font-black">
        <?= escapar($_SESSION['admin_flash_ok']) ?>
    </div>
    <?php unset($_SESSION['admin_flash_ok']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['admin_flash_error'])): ?>
    <div class="bg-red-500/20 border border-red-400 text-red-300 p-4 rounded-2xl font-black">
        <?= escapar($_SESSION['admin_flash_error']) ?>
    </div>
    <?php unset($_SESSION['admin_flash_error']); ?>
<?php endif; ?>

<?php if ($idSeleccionado > 0): ?>

    <?php if (!$usuarioSeleccionado): ?>

        <section class="bg-zinc-900/90 border border-red-400/30 rounded-3xl p-8 text-center">
            <h2 class="text-4xl font-black text-red-400 mb-4">
                Usuario no encontrado
            </h2>

            <p class="text-zinc-400 mb-6">
                No existe ningún usuario con ese ID.
            </p>

            <a href="/admin" class="bg-yellow-400 hover:bg-yellow-300 text-black px-6 py-3 rounded-xl font-black inline-block">
                ← Volver al panel
            </a>
        </section>

    <?php else: ?>

        <?php
            $estadoSeleccionado = $usuarioSeleccionado['estado'] ?? 'activo';
            $gananciaBalance = isset($estadisticasUsuario['balance_neto']) ? (float) $estadisticasUsuario['balance_neto'] : 0;
        ?>

        <!-- BOTONES SUPERIORES DETALLE -->
        <section class="flex flex-wrap gap-3">
            <a href="/admin" class="bg-zinc-700 hover:bg-zinc-600 px-5 py-3 rounded-xl font-black transition">
                ← Volver a usuarios
            </a>

            <button onclick="history.back()" class="bg-blue-600 hover:bg-blue-500 px-5 py-3 rounded-xl font-black transition">
                ← Atrás
            </button>

            <?php if ($estadoSeleccionado === 'suspendido'): ?>
                <form method="POST" onsubmit="return confirm('¿Activar este usuario?');">
                    <input type="hidden" name="accion_usuario" value="activar">
                    <input type="hidden" name="id_usuario" value="<?= (int) $usuarioSeleccionado['id_usuario'] ?>">
                    <input type="hidden" name="volver_detalle" value="1">

                    <button type="submit" class="bg-green-600 hover:bg-green-500 px-5 py-3 rounded-xl font-black transition">
                        Activar usuario
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" onsubmit="return confirm('¿Bloquear este usuario?');">
                    <input type="hidden" name="accion_usuario" value="bloquear">
                    <input type="hidden" name="id_usuario" value="<?= (int) $usuarioSeleccionado['id_usuario'] ?>">
                    <input type="hidden" name="volver_detalle" value="1">

                    <button type="submit" class="bg-red-600 hover:bg-red-500 px-5 py-3 rounded-xl font-black transition">
                        Bloquear usuario
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <!-- EDITAR USUARIO -->
        <section class="bg-zinc-900/90 border border-yellow-400/30 rounded-3xl p-6">

            <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-4xl font-black text-yellow-400">
                        Editar usuario #<?= (int) $usuarioSeleccionado['id_usuario'] ?>
                    </h2>

                    <p class="text-zinc-400">
                        Modifica todos los datos principales del usuario.
                    </p>
                </div>

                <div>
                    <?php if ($estadoSeleccionado === 'suspendido'): ?>
                        <span class="px-4 py-2 rounded-full bg-red-500/20 text-red-400 border border-red-400 font-black inline-block">
                            Bloqueado
                        </span>
                    <?php elseif ($estadoSeleccionado === 'inactivo'): ?>
                        <span class="px-4 py-2 rounded-full bg-zinc-500/20 text-zinc-300 border border-zinc-400 font-black inline-block">
                            Inactivo
                        </span>
                    <?php else: ?>
                        <span class="px-4 py-2 rounded-full bg-green-500/20 text-green-400 border border-green-400 font-black inline-block">
                            Activo
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" class="space-y-6">

                <input type="hidden" name="guardar_usuario" value="1">
                <input type="hidden" name="id_usuario" value="<?= (int) $usuarioSeleccionado['id_usuario'] ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Nombre usuario
                        </label>

                        <input type="text" name="nombre_usuario"
                               value="<?= escapar($usuarioSeleccionado['nombre_usuario'] ?? '') ?>"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Usuario
                        </label>

                        <input type="text" name="usuario"
                               value="<?= escapar($usuarioSeleccionado['usuario'] ?? '') ?>"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Nombre completo
                        </label>

                        <input type="text" name="nombre"
                               value="<?= escapar($usuarioSeleccionado['nombre'] ?? '') ?>"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            DNI
                        </label>

                        <input type="text" name="dni"
                               value="<?= escapar($usuarioSeleccionado['dni'] ?? '') ?>"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Email
                        </label>

                        <input type="email" name="email"
                               value="<?= escapar($usuarioSeleccionado['email'] ?? '') ?>"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Estado
                        </label>

                        <select name="estado"
                                class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                            <option value="activo" <?= ($estadoSeleccionado === 'activo') ? 'selected' : '' ?>>
                                activo
                            </option>

                            <option value="inactivo" <?= ($estadoSeleccionado === 'inactivo') ? 'selected' : '' ?>>
                                inactivo
                            </option>

                            <option value="suspendido" <?= ($estadoSeleccionado === 'suspendido') ? 'selected' : '' ?>>
                                suspendido
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Saldo cuenta
                        </label>

                        <input type="number" step="0.01" name="saldo"
                               value="<?= escapar($usuarioSeleccionado['saldo'] ?? '0.00') ?>"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Total dinero
                        </label>

                        <input type="number" step="0.01" name="total_dinero"
                               value="<?= escapar($usuarioSeleccionado['total_dinero'] ?? '0.00') ?>"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-zinc-300 font-bold mb-2">
                            Fecha registro
                        </label>

                        <input type="text" name="fecha_registro"
                               value="<?= escapar($usuarioSeleccionado['fecha_registro'] ?? '') ?>"
                               placeholder="YYYY-MM-DD HH:MM:SS"
                               class="w-full bg-black/40 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">
                    </div>

                </div>

                <div class="bg-black/40 border border-zinc-700 rounded-2xl p-5">
                    <label class="block text-zinc-300 font-bold mb-2">
                        Nueva contraseña
                    </label>

                    <input type="password" name="nueva_password"
                           placeholder="Déjalo vacío para no cambiarla"
                           class="w-full bg-zinc-900 border-2 border-zinc-700 focus:border-yellow-400 rounded-xl p-4 outline-none text-white font-bold">

                    <p class="text-zinc-500 text-sm mt-2">
                        Si escribes una contraseña nueva, se guardará en password_hash.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">

                    <button type="submit"
                            class="bg-yellow-400 hover:bg-yellow-300 text-black px-6 py-3 rounded-xl font-black transition">
                        Guardar cambios
                    </button>

                    <a href="/admin" class="bg-zinc-700 hover:bg-zinc-600 px-6 py-3 rounded-xl font-black transition">
                        ← Volver a usuarios
                    </a>

                    <button type="button" onclick="history.back()" class="bg-blue-600 hover:bg-blue-500 px-6 py-3 rounded-xl font-black transition">
                        ← Atrás
                    </button>

                </div>

            </form>

        </section>

        <!-- ESTADÍSTICAS -->
        <section class="bg-zinc-900/90 border border-zinc-700 rounded-3xl p-6">

            <h2 class="text-3xl font-black text-yellow-400 mb-5">
                Estadísticas del usuario
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div class="bg-black/40 rounded-2xl p-4">
                    <p class="text-zinc-400 font-bold">Apuestas</p>
                    <p class="text-3xl font-black text-purple-400">
                        <?= (int) ($estadisticasUsuario['total_apuestas'] ?? 0) ?>
                    </p>
                </div>

                <div class="bg-black/40 rounded-2xl p-4">
                    <p class="text-zinc-400 font-bold">Total apostado</p>
                    <p class="text-3xl font-black text-yellow-400">
                        $<?= dinero($estadisticasUsuario['total_apostado'] ?? 0) ?>
                    </p>
                </div>

                <div class="bg-black/40 rounded-2xl p-4">
                    <p class="text-zinc-400 font-bold">Total pagado</p>
                    <p class="text-3xl font-black text-green-400">
                        $<?= dinero($estadisticasUsuario['total_pagado'] ?? 0) ?>
                    </p>
                </div>

                <div class="bg-black/40 rounded-2xl p-4">
                    <p class="text-zinc-400 font-bold">Balance</p>
                    <p class="text-3xl font-black <?= $gananciaBalance >= 0 ? 'text-green-400' : 'text-red-400' ?>">
                        <?= $gananciaBalance >= 0 ? '+' : '-' ?>$<?= dinero(abs($gananciaBalance)) ?>
                    </p>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">

                <div class="bg-black/40 rounded-2xl p-4">
                    <p class="text-zinc-400 font-bold">Ganadas</p>
                    <p class="text-3xl font-black text-green-400">
                        <?= (int) ($estadisticasUsuario['ganadas'] ?? 0) ?>
                    </p>
                </div>

                <div class="bg-black/40 rounded-2xl p-4">
                    <p class="text-zinc-400 font-bold">Perdidas</p>
                    <p class="text-3xl font-black text-red-400">
                        <?= (int) ($estadisticasUsuario['perdidas'] ?? 0) ?>
                    </p>
                </div>

                <div class="bg-black/40 rounded-2xl p-4">
                    <p class="text-zinc-400 font-bold">Empates</p>
                    <p class="text-3xl font-black text-yellow-400">
                        <?= (int) ($estadisticasUsuario['empates'] ?? 0) ?>
                    </p>
                </div>

            </div>

        </section>

        <!-- TRANSACCIONES DEL USUARIO SIN COLUMNA DETALLE -->
        <section class="bg-zinc-900/90 border border-zinc-700 rounded-3xl overflow-hidden">

            <div class="p-5 border-b border-zinc-700 flex flex-col md:flex-row justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-black text-yellow-400">
                        Transacciones del usuario
                    </h2>

                    <p class="text-zinc-400">
                        Últimas 300 transacciones registradas.
                    </p>
                </div>

                <a href="/admin" class="bg-zinc-700 hover:bg-zinc-600 px-5 py-3 rounded-xl font-black transition text-center">
                    ← Volver
                </a>
            </div>

            <div class="overflow-x-auto table-scroll max-h-[700px]">

                <table class="w-full min-w-[1050px] text-left">

                    <thead class="bg-black/60 text-yellow-400 uppercase text-sm sticky top-0">
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
                        <?php if ($historialUsuario && $historialUsuario->num_rows > 0): ?>
                            <?php while ($h = $historialUsuario->fetch_assoc()): ?>
                                <?php
                                    $resultado = $h['resultado'] ?? '';
                                    $ganancia = isset($h['ganancia_neta']) ? (float) $h['ganancia_neta'] : 0;

                                    if ($resultado === 'win') {
                                        $claseResultado = 'bg-green-500/20 text-green-400 border-green-400';
                                        $textoResultado = 'Ganada';
                                    } elseif ($resultado === 'loss') {
                                        $claseResultado = 'bg-red-500/20 text-red-400 border-red-400';
                                        $textoResultado = 'Perdida';
                                    } elseif ($resultado === 'tie') {
                                        $claseResultado = 'bg-yellow-500/20 text-yellow-400 border-yellow-400';
                                        $textoResultado = 'Empate';
                                    } else {
                                        $claseResultado = 'bg-zinc-500/20 text-zinc-300 border-zinc-400';
                                        $textoResultado = $resultado;
                                    }
                                ?>

                                <tr class="border-t border-zinc-800 hover:bg-white/5 transition">

                                    <td class="p-4 text-zinc-300">
                                        <?= escapar($h['fecha'] ?? '') ?>
                                    </td>

                                    <td class="p-4 font-black">
                                        <?= escapar($h['juego'] ?? '') ?>
                                    </td>

                                    <td class="p-4">
                                        $<?= dinero($h['monto_apostado'] ?? 0) ?>
                                    </td>

                                    <td class="p-4">
                                        <span class="px-3 py-1 rounded-full border font-black text-sm <?= $claseResultado ?>">
                                            <?= escapar($textoResultado) ?>
                                        </span>
                                    </td>

                                    <td class="p-4">
                                        x<?= escapar($h['multiplicador'] ?? '0') ?>
                                    </td>

                                    <td class="p-4 text-green-400 font-black">
                                        $<?= dinero($h['pago'] ?? 0) ?>
                                    </td>

                                    <td class="p-4 font-black <?= $ganancia >= 0 ? 'text-green-400' : 'text-red-400' ?>">
                                        <?= $ganancia >= 0 ? '+' : '-' ?>$<?= dinero(abs($ganancia)) ?>
                                    </td>

                                    <td class="p-4">
                                        $<?= dinero($h['saldo_sesion_despues'] ?? 0) ?>
                                    </td>

                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-zinc-400">
                                    Este usuario todavía no tiene transacciones.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>

            </div>

        </section>

        <section>
            <a href="/admin" class="bg-yellow-400 hover:bg-yellow-300 text-black px-6 py-3 rounded-xl font-black inline-block">
                ← Volver al panel de usuarios
            </a>
        </section>

    <?php endif; ?>

<?php else: ?>

    <!-- RESUMEN GLOBAL -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-5">

        <div class="bg-zinc-900/90 border border-yellow-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Usuarios</p>
            <p class="text-4xl font-black text-yellow-400">
                <?= (int) ($resumenGlobal['total_usuarios'] ?? 0) ?>
            </p>
        </div>

        <div class="bg-zinc-900/90 border border-green-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Activos</p>
            <p class="text-4xl font-black text-green-400">
                <?= (int) ($resumenGlobal['usuarios_activos'] ?? 0) ?>
            </p>
        </div>

        <div class="bg-zinc-900/90 border border-red-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Bloqueados</p>
            <p class="text-4xl font-black text-red-400">
                <?= (int) ($resumenGlobal['usuarios_suspendidos'] ?? 0) ?>
            </p>
        </div>

        <div class="bg-zinc-900/90 border border-blue-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Saldo total</p>
            <p class="text-4xl font-black text-blue-400">
                $<?= dinero($resumenGlobal['saldo_total'] ?? 0) ?>
            </p>
        </div>

    </section>

    <section class="grid grid-cols-1 md:grid-cols-4 gap-5">

        <div class="bg-zinc-900/90 border border-purple-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Apuestas totales</p>
            <p class="text-4xl font-black text-purple-400">
                <?= (int) ($resumenHistorial['total_apuestas'] ?? 0) ?>
            </p>
        </div>

        <div class="bg-zinc-900/90 border border-yellow-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Total apostado</p>
            <p class="text-4xl font-black text-yellow-400">
                $<?= dinero($resumenHistorial['total_apostado'] ?? 0) ?>
            </p>
        </div>

        <div class="bg-zinc-900/90 border border-green-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Total pagado</p>
            <p class="text-4xl font-black text-green-400">
                $<?= dinero($resumenHistorial['total_pagado'] ?? 0) ?>
            </p>
        </div>

        <div class="bg-zinc-900/90 border border-red-400/30 rounded-2xl p-5">
            <p class="text-zinc-400 font-bold">Balance global</p>

            <?php $balanceGlobal = isset($resumenHistorial['balance_global']) ? (float) $resumenHistorial['balance_global'] : 0; ?>

            <p class="text-4xl font-black <?= $balanceGlobal >= 0 ? 'text-green-400' : 'text-red-400' ?>">
                <?= $balanceGlobal >= 0 ? '+' : '-' ?>$<?= dinero(abs($balanceGlobal)) ?>
            </p>
        </div>

    </section>

    <!-- TABLA PRINCIPAL USUARIOS -->
    <section class="bg-zinc-900/90 border border-zinc-700 rounded-3xl overflow-hidden">

        <div class="p-5 border-b border-zinc-700 flex flex-col md:flex-row justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-yellow-400">
                    Usuarios registrados
                </h2>

                <p class="text-zinc-400">
                    Pulsa “Ver usuario” para abrir la ficha completa y editar sus datos.
                </p>
            </div>

            <button onclick="location.reload()" class="bg-blue-600 hover:bg-blue-500 px-5 py-3 rounded-xl font-black transition">
                Recargar
            </button>
        </div>

        <div class="overflow-x-auto table-scroll max-h-[760px]">

            <table class="w-full min-w-[1000px] text-left">

                <thead class="bg-black/60 text-yellow-400 uppercase text-sm sticky top-0">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Nombre usuario</th>
                        <th class="p-4">Usuario</th>
                        <th class="p-4">Nombre</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Saldo</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Acciones</th>
                        <th class="p-4">Ficha</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                        <?php while ($u = $usuarios->fetch_assoc()): ?>
                            <?php
                                $idU = (int) ($u['id_usuario'] ?? 0);
                                $estadoU = $u['estado'] ?? 'activo';
                            ?>

                            <tr class="border-t border-zinc-800 hover:bg-white/5 transition">

                                <td class="p-4 font-black text-zinc-300">
                                    <?= $idU ?>
                                </td>

                                <td class="p-4 font-black">
                                    <?= escapar($u['nombre_usuario'] ?? '') ?>
                                </td>

                                <td class="p-4">
                                    <?= escapar($u['usuario'] ?? '') ?>
                                </td>

                                <td class="p-4">
                                    <?= escapar($u['nombre'] ?? '') ?>
                                </td>

                                <td class="p-4">
                                    <?= escapar($u['email'] ?? '') ?>
                                </td>

                                <td class="p-4 text-yellow-400 font-black">
                                    $<?= dinero($u['saldo'] ?? 0) ?>
                                </td>

                                <td class="p-4">
                                    <?php if ($estadoU === 'activo'): ?>
                                        <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-400 font-black text-sm">
                                            Activo
                                        </span>
                                    <?php elseif ($estadoU === 'suspendido'): ?>
                                        <span class="px-3 py-1 rounded-full bg-red-500/20 text-red-400 border border-red-400 font-black text-sm">
                                            Bloqueado
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full bg-zinc-500/20 text-zinc-300 border border-zinc-400 font-black text-sm">
                                            <?= escapar($estadoU) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4">
                                    <?php if ($estadoU === 'suspendido'): ?>
                                        <form method="POST" onsubmit="return confirm('¿Activar este usuario?');">
                                            <input type="hidden" name="accion_usuario" value="activar">
                                            <input type="hidden" name="id_usuario" value="<?= $idU ?>">

                                            <button type="submit" class="bg-green-600 hover:bg-green-500 px-4 py-2 rounded-xl font-black transition">
                                                Activar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('¿Bloquear este usuario?');">
                                            <input type="hidden" name="accion_usuario" value="bloquear">
                                            <input type="hidden" name="id_usuario" value="<?= $idU ?>">

                                            <button type="submit" class="bg-red-600 hover:bg-red-500 px-4 py-2 rounded-xl font-black transition">
                                                Bloquear
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4">
                                    <a href="/admin?usuario=<?= $idU ?>" class="bg-yellow-400 hover:bg-yellow-300 text-black px-4 py-2 rounded-xl font-black transition inline-block">
                                        Ver usuario
                                    </a>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="p-8 text-center text-zinc-400">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

        </div>

    </section>

    <!-- ÚLTIMAS TRANSACCIONES GLOBALES -->
    <section class="bg-zinc-900/90 border border-zinc-700 rounded-3xl overflow-hidden">

        <div class="p-5 border-b border-zinc-700">
            <h2 class="text-3xl font-black text-yellow-400">
                Últimas transacciones globales
            </h2>

            <p class="text-zinc-400">
                Últimas 50 apuestas registradas de todos los usuarios.
            </p>
        </div>

        <div class="overflow-x-auto table-scroll max-h-[650px]">

            <table class="w-full min-w-[1150px] text-left">

                <thead class="bg-black/60 text-yellow-400 uppercase text-sm sticky top-0">
                    <tr>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Usuario</th>
                        <th class="p-4">Juego</th>
                        <th class="p-4">Apostado</th>
                        <th class="p-4">Resultado</th>
                        <th class="p-4">Multiplicador</th>
                        <th class="p-4">Pago</th>
                        <th class="p-4">Ganancia neta</th>
                        <th class="p-4">Detalle</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($ultimasTransacciones && $ultimasTransacciones->num_rows > 0): ?>
                        <?php while ($h = $ultimasTransacciones->fetch_assoc()): ?>
                            <?php
                                $resultado = $h['resultado'] ?? '';
                                $ganancia = isset($h['ganancia_neta']) ? (float) $h['ganancia_neta'] : 0;

                                if ($resultado === 'win') {
                                    $claseResultado = 'bg-green-500/20 text-green-400 border-green-400';
                                    $textoResultado = 'Ganada';
                                } elseif ($resultado === 'loss') {
                                    $claseResultado = 'bg-red-500/20 text-red-400 border-red-400';
                                    $textoResultado = 'Perdida';
                                } elseif ($resultado === 'tie') {
                                    $claseResultado = 'bg-yellow-500/20 text-yellow-400 border-yellow-400';
                                    $textoResultado = 'Empate';
                                } else {
                                    $claseResultado = 'bg-zinc-500/20 text-zinc-300 border-zinc-400';
                                    $textoResultado = $resultado;
                                }
                            ?>

                            <tr class="border-t border-zinc-800 hover:bg-white/5 transition">

                                <td class="p-4 text-zinc-300">
                                    <?= escapar($h['fecha'] ?? '') ?>
                                </td>

                                <td class="p-4 font-black">
                                    <?= escapar($h['nombre_usuario'] ?? '') ?>
                                    <span class="text-zinc-500 text-sm">
                                        #<?= (int) ($h['id_usuario'] ?? 0) ?>
                                    </span>
                                </td>

                                <td class="p-4 font-black">
                                    <?= escapar($h['juego'] ?? '') ?>
                                </td>

                                <td class="p-4">
                                    $<?= dinero($h['monto_apostado'] ?? 0) ?>
                                </td>

                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full border font-black text-sm <?= $claseResultado ?>">
                                        <?= escapar($textoResultado) ?>
                                    </span>
                                </td>

                                <td class="p-4">
                                    x<?= escapar($h['multiplicador'] ?? '0') ?>
                                </td>

                                <td class="p-4 text-green-400 font-black">
                                    $<?= dinero($h['pago'] ?? 0) ?>
                                </td>

                                <td class="p-4 font-black <?= $ganancia >= 0 ? 'text-green-400' : 'text-red-400' ?>">
                                    <?= $ganancia >= 0 ? '+' : '-' ?>$<?= dinero(abs($ganancia)) ?>
                                </td>

                                <td class="p-4 text-xs text-zinc-400 max-w-[350px]">
                                    <details>
                                        <summary class="cursor-pointer text-yellow-400 font-bold">
                                            Ver detalle
                                        </summary>

                                        <pre class="whitespace-pre-wrap break-words bg-black/50 p-3 rounded-xl mt-2"><?= escapar($h['detalle'] ?? '') ?></pre>
                                    </details>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="p-8 text-center text-zinc-400">
                                Todavía no hay transacciones registradas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

        </div>

    </section>

<?php endif; ?>

</main>

</body>
</html>