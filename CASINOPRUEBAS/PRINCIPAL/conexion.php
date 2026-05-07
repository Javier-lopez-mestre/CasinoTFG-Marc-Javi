<?php
// Datos de conexión
$host = "localhost";
$usuario = "casino_user";   // usuario que creamos para la web
$password = "superlocal";    // contraseña del usuario
$bd = "casino";             // nombre de la base de datos

// Crear la conexión
$conexion = new mysqli($host, $usuario, $password, $bd);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

// Opcional: establecer codificación UTF-8
$conexion->set_charset("utf8");
?>