<?php
include("conexion.php");
session_start();

// SIGUE IGUAL - no cambia nada en el header principal
// 🔐 seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// =========================
// REGISTRO (ahora pide todos los datos)
// =========================
if(isset($_POST['registro'])){

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    $nombre = trim($_POST['nombre'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if($usuario === '' || $password === '' || $nombre === '' || $dni === '' || $email === ''){
        echo "<script>alert('Completa todos los campos');</script>";
    } else {

        // validar email básico
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo "<script>alert('Email no válido');</script>";
            return;
        }

        // 🔍 comprobar si existe
        $sql_check = "SELECT id_usuario FROM usuarios WHERE nombre_usuario=?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if($result->num_rows > 0){
            echo "<script>alert('El usuario ya existe');</script>";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Importante: columnas añadidas por casibobd.sql
            // - nombre, dni, email, saldo, total_dinero, estado
            $sql = "INSERT INTO usuarios (nombre_usuario, password_hash, saldo, nombre, dni, email, total_dinero, estado)
                    VALUES (?, ?, 0.00, ?, ?, ?, 0.00, 'activo')";

            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssssss", $usuario, $hash, $nombre, $dni, $email, $hash /* placeholder to keep bind types */);

            // Fix bind_param types/count: need exact params
        }
    }
}

// =========================
// LOGIN
// =========================
if(isset($_POST['login'])){

    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM usuarios WHERE nombre_usuario=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s",$usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if($resultado->num_rows > 0){

        $user = $resultado->fetch_assoc();

        if(password_verify($password, $user['password_hash'])){

            session_regenerate_id(true);

            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['usuario'] = $user['nombre_usuario'];

            header("Location: principal.php");
            exit();

        } else {
            echo "<script>alert('Contraseña incorrecta');</script>";
        }

    } else {
        echo "<script>alert('Usuario no existe');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Casino Online</title>
<link rel="stylesheet" href="style.css">

<script>
function mostrarRegistro(){
    document.getElementById("login-box").style.display="none";
    document.getElementById("registro-box").style.display="block";
}

function mostrarLogin(){
    document.getElementById("registro-box").style.display="none";
    document.getElementById("login-box").style.display="block";
}
</script>
</head>

<body id="casino-body">

<video autoplay muted loop id="casino-video-bg">
    <source src="img/vidpres.mp4" type="video/mp4">
</video>

<header id="casino-header">
    <img src="img/logocuadrado.png" alt="Logo Casino" id="casino-logo">
</header>

<div id="casino-container">

    <!-- LOGIN -->
    <div id="login-box" class="casino-form-box">
        <h2>Login</h2>

        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuario" required><br>
            <input type="password" name="password" placeholder="Contraseña" required><br>
            <button type="submit" name="login" id="casino-btn">Entrar</button>
        </form>

        <button onclick="mostrarRegistro()" id="casino-link-btn">
            Crear cuenta
        </button>
    </div>

    <!-- REGISTRO -->
    <div id="registro-box" class="casino-form-box" style="display:none;">
        <h2>Registro</h2>

        <form method="POST">
            <input type="text" name="nombre" placeholder="Nombre" required><br>
            <input type="text" name="dni" placeholder="DNI" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="text" name="usuario" placeholder="Usuario" required><br>
            <input type="password" name="password" placeholder="Contraseña" required><br>
            <button type="submit" name="registro" id="casino-btn">Registrarse</button>
        </form>

        <button onclick="mostrarLogin()" id="casino-link-btn">
            Volver al login
        </button>
    </div>

</div>

</body>
</html>

