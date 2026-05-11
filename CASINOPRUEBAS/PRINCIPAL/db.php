<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): mysqli
{
    static $conn = null;
    static $initialized = false;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $conn = new mysqli($GLOBALS['DB_HOST'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS'], $GLOBALS['DB_NAME']);

    if ($conn->connect_error) {
        // Evitar filtrar detalles internos
        http_response_code(500);
        die('Error de conexión a la base de datos');
    }

    $conn->set_charset('utf8mb4');

    // Puedes desactivar autocommit y controlar transacciones en llamadas concretas si lo necesitas.

    return $conn;
}

?>
