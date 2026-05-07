<?php
include("conexion.php");
session_start();

// 🔐 seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// =========================
// REGISTRO
// =========================
if(isset($_POST['registro'])){

    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];

    // 🔍 comprobar si existe
    $sql_check = "SELECT id_usuario FROM usuarios WHERE nombre_usuario=?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s",$usuario);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if($result->num_rows > 0){
        echo "<script>alert('El usuario ya existe');</script>";
    } else {

        // 🔐 hash seguro
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre_usuario,password_hash,saldo)
                VALUES (?, ?, 0.00)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss",$usuario,$hash);

        if($stmt->execute()){
            echo "<script>alert('Usuario creado correctamente');</script>";
        }else{
            echo "<script>alert('Error al crear usuario');</script>";
        }
    }
}

// =========================
// LOGIN
// =========================
if(isset($_POST['login'])){

    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE nombre_usuario=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s",$usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if($resultado->num_rows > 0){

        $user = $resultado->fetch_assoc();

        if(password_verify($password, $user['password_hash'])){

            // 🔐 regenerar sesión
            session_regenerate_id(true);

            // 🔥 sistema correcto (ID + nombre)
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