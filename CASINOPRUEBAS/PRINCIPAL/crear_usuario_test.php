<?php
include("conexion.php");

$usuario = "superlocal1";
$password = "superlocal";

// generar hash real compatible con TU PHP
$hash = password_hash($password, PASSWORD_DEFAULT);

// borrar si existe
$conexion->query("DELETE FROM usuarios WHERE nombre_usuario='testplayer'");

// insertar nuevo usuario válido
$stmt = $conexion->prepare("INSERT INTO usuarios (nombre_usuario, password_hash, saldo) VALUES (?, ?, 500)");
$stmt->bind_param("ss", $usuario, $hash);

if($stmt->execute()){
    echo "Usuario creado correctamente <br>";
    echo "Usuario: testplayer <br>";
    echo "Contraseña: 123456";
} else {
    echo "Error creando usuario";
}