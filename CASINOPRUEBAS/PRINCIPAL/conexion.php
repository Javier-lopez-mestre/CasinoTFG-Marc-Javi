<?php
// Legacy wrapper para compatibilidad: muchos archivos usan $conexion.
// Ahora delega en db.php.

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$conexion = db();
?>


