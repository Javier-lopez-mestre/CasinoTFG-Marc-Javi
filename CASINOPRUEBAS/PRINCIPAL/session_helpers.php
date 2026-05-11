<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function init_session(): void
{
    // Solo aplicar ini settings antes de session_start
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');

    // setcookie secure/samesite ya se puede haber definido en config.php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function require_login(): void
{
    init_session();

    if (!isset($_SESSION['id_usuario'])) {
        header('Location: index.php');
        exit();
    }
}

?>
