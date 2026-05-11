<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

/*
    Datos del usuario que quieres crear o actualizar.
*/
$nombreUsuario = "mcachiner1";
$passwordPlano = "123456";
$saldoInicial = 10000.00;

$nombre = "Mcachiner 1";
$dni = "00000000M";
$email = "mcachiner1@local.test";
$estado = "activo";

/*
    Contraseña segura.
*/
$passwordHash = password_hash($passwordPlano, PASSWORD_DEFAULT);

/*
    Obtener columnas reales de la tabla usuarios.
*/
$columnas = [];

$res = $conexion->query("SHOW COLUMNS FROM usuarios");

if (!$res) {
    die("Error leyendo columnas de usuarios: " . $conexion->error);
}

while ($row = $res->fetch_assoc()) {
    $columnas[] = $row['Field'];
}

function tieneColumna($columnas, $columna) {
    return in_array($columna, $columnas, true);
}

function valorSQL($conexion, $valor) {
    if (is_null($valor)) {
        return "NULL";
    }

    return "'" . $conexion->real_escape_string((string) $valor) . "'";
}

if (!tieneColumna($columnas, "id_usuario")) {
    die("La tabla usuarios no tiene id_usuario.");
}

if (!tieneColumna($columnas, "nombre_usuario") && !tieneColumna($columnas, "usuario")) {
    die("La tabla usuarios necesita al menos nombre_usuario o usuario.");
}

/*
    Buscar si el usuario ya existe.
*/
$condiciones = [];

if (tieneColumna($columnas, "nombre_usuario")) {
    $condiciones[] = "nombre_usuario = '" . $conexion->real_escape_string($nombreUsuario) . "'";
}

if (tieneColumna($columnas, "usuario")) {
    $condiciones[] = "usuario = '" . $conexion->real_escape_string($nombreUsuario) . "'";
}

$sqlBuscar = "
    SELECT id_usuario 
    FROM usuarios 
    WHERE " . implode(" OR ", $condiciones) . "
    LIMIT 1
";

$resultadoBuscar = $conexion->query($sqlBuscar);

if (!$resultadoBuscar) {
    die("Error buscando usuario: " . $conexion->error . "<br><br>SQL:<br>" . $sqlBuscar);
}

$existe = $resultadoBuscar->fetch_assoc();

/*
    Preparar datos según columnas existentes.
*/
$datos = [];

if (tieneColumna($columnas, "nombre_usuario")) {
    $datos["nombre_usuario"] = $nombreUsuario;
}

if (tieneColumna($columnas, "usuario")) {
    $datos["usuario"] = $nombreUsuario;
}

if (tieneColumna($columnas, "nombre")) {
    $datos["nombre"] = $nombre;
}

if (tieneColumna($columnas, "dni")) {
    $datos["dni"] = $dni;
}

if (tieneColumna($columnas, "email")) {
    $datos["email"] = $email;
}

/*
    Columnas posibles para contraseña.
    Si tu login usa password_hash, aquí se rellena.
*/
if (tieneColumna($columnas, "password_hash")) {
    $datos["password_hash"] = $passwordHash;
}

if (tieneColumna($columnas, "password")) {
    $datos["password"] = $passwordHash;
}

if (tieneColumna($columnas, "contrasena")) {
    $datos["contrasena"] = $passwordHash;
}

if (tieneColumna($columnas, "clave")) {
    $datos["clave"] = $passwordHash;
}

if (tieneColumna($columnas, "saldo")) {
    $datos["saldo"] = $saldoInicial;
}

if (tieneColumna($columnas, "total_dinero")) {
    $datos["total_dinero"] = $saldoInicial;
}

if (tieneColumna($columnas, "estado")) {
    $datos["estado"] = $estado;
}

if (tieneColumna($columnas, "fecha_registro")) {
    $datos["fecha_registro"] = date("Y-m-d H:i:s");
}

/*
    Si existe, actualizar.
*/
if ($existe) {
    $idUsuario = (int) $existe['id_usuario'];

    $updates = [];

    foreach ($datos as $columna => $valor) {
        $updates[] = "`" . $columna . "` = " . valorSQL($conexion, $valor);
    }

    if (empty($updates)) {
        die("No hay columnas para actualizar.");
    }

    $sqlUpdate = "
        UPDATE usuarios
        SET " . implode(", ", $updates) . "
        WHERE id_usuario = $idUsuario
    ";

    if (!$conexion->query($sqlUpdate)) {
        die("Error actualizando usuario: " . $conexion->error . "<br><br>SQL:<br>" . $sqlUpdate);
    }

    echo "<h2>Usuario mcachiner1 actualizado correctamente.</h2>";
    echo "<p><b>ID:</b> " . $idUsuario . "</p>";
    echo "<p><b>Usuario:</b> mcachiner1</p>";
    echo "<p><b>Contraseña:</b> 123456</p>";
    echo "<p><b>Saldo:</b> $" . number_format($saldoInicial, 2) . "</p>";
    echo "<p style='color:red'><b>IMPORTANTE:</b> Borra este archivo ahora.</p>";
    exit;
}

/*
    Si no existe, insertar.
*/
$cols = [];
$vals = [];

foreach ($datos as $columna => $valor) {
    $cols[] = "`" . $columna . "`";
    $vals[] = valorSQL($conexion, $valor);
}

$sqlInsert = "
    INSERT INTO usuarios (" . implode(", ", $cols) . ")
    VALUES (" . implode(", ", $vals) . ")
";

if (!$conexion->query($sqlInsert)) {
    die("Error creando usuario: " . $conexion->error . "<br><br>SQL:<br>" . $sqlInsert);
}

$idNuevo = $conexion->insert_id;

echo "<h2>Usuario mcachiner1 creado correctamente.</h2>";
echo "<p><b>ID:</b> " . $idNuevo . "</p>";
echo "<p><b>Usuario:</b> mcachiner1</p>";
echo "<p><b>Contraseña:</b> 123456</p>";
echo "<p><b>Saldo:</b> $" . number_format($saldoInicial, 2) . "</p>";
echo "<p style='color:red'><b>IMPORTANTE:</b> Borra este archivo ahora.</p>";