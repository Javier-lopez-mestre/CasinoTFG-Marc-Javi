USE casino;

-- =========================================================
-- TABLA HISTORIAL DE APUESTAS
-- Necesaria para perfil.php
-- =========================================================

CREATE TABLE IF NOT EXISTS historial_apuestas (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    juego VARCHAR(50) NOT NULL,
    monto_apostado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    resultado ENUM('win','loss','tie') NOT NULL,
    multiplicador DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    pago DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ganancia_neta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    saldo_sesion_despues DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario_fecha (id_usuario, fecha),
    INDEX idx_usuario_juego (id_usuario, juego)
);

-- =========================================================
-- AGREGAR COLUMNA usuario SI NO EXISTE
-- =========================================================

SET @existe_usuario := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'usuario'
);

SET @sql_usuario := IF(
    @existe_usuario = 0,
    'ALTER TABLE usuarios ADD COLUMN usuario VARCHAR(100) NULL AFTER nombre_usuario',
    'SELECT "La columna usuario ya existe"'
);

PREPARE stmt FROM @sql_usuario;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- AGREGAR COLUMNA nombre SI NO EXISTE
-- =========================================================

SET @existe_nombre := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'nombre'
);

SET @sql_nombre := IF(
    @existe_nombre = 0,
    'ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(100) DEFAULT "Usuario Anónimo" AFTER usuario',
    'SELECT "La columna nombre ya existe"'
);

PREPARE stmt FROM @sql_nombre;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- AGREGAR COLUMNA dni SI NO EXISTE
-- =========================================================

SET @existe_dni := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'dni'
);

SET @sql_dni := IF(
    @existe_dni = 0,
    'ALTER TABLE usuarios ADD COLUMN dni VARCHAR(20) NULL AFTER nombre',
    'SELECT "La columna dni ya existe"'
);

PREPARE stmt FROM @sql_dni;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- AGREGAR COLUMNA email SI NO EXISTE
-- =========================================================

SET @existe_email := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'email'
);

SET @sql_email := IF(
    @existe_email = 0,
    'ALTER TABLE usuarios ADD COLUMN email VARCHAR(100) NULL AFTER dni',
    'SELECT "La columna email ya existe"'
);

PREPARE stmt FROM @sql_email;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- AGREGAR COLUMNA total_dinero SI NO EXISTE
-- =========================================================

SET @existe_total_dinero := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'total_dinero'
);

SET @sql_total_dinero := IF(
    @existe_total_dinero = 0,
    'ALTER TABLE usuarios ADD COLUMN total_dinero DECIMAL(12,2) DEFAULT 0.00 AFTER email',
    'SELECT "La columna total_dinero ya existe"'
);

PREPARE stmt FROM @sql_total_dinero;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- AGREGAR COLUMNA fecha_registro SI NO EXISTE
-- =========================================================

SET @existe_fecha_registro := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'fecha_registro'
);

SET @sql_fecha_registro := IF(
    @existe_fecha_registro = 0,
    'ALTER TABLE usuarios ADD COLUMN fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP AFTER total_dinero',
    'SELECT "La columna fecha_registro ya existe"'
);

PREPARE stmt FROM @sql_fecha_registro;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- AGREGAR COLUMNA estado SI NO EXISTE
-- =========================================================

SET @existe_estado := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'estado'
);

SET @sql_estado := IF(
    @existe_estado = 0,
    'ALTER TABLE usuarios ADD COLUMN estado ENUM("activo", "inactivo", "suspendido") DEFAULT "activo" AFTER fecha_registro',
    'SELECT "La columna estado ya existe"'
);

PREPARE stmt FROM @sql_estado;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- NORMALIZAR DATOS
-- =========================================================

UPDATE usuarios
SET usuario = nombre_usuario
WHERE (usuario IS NULL OR usuario = '')
  AND nombre_usuario IS NOT NULL;

UPDATE usuarios
SET nombre = 'Usuario Anónimo'
WHERE nombre IS NULL OR nombre = '';

UPDATE usuarios
SET estado = 'activo'
WHERE estado IS NULL OR estado = '';

UPDATE usuarios
SET total_dinero = saldo
WHERE total_dinero = 0
  AND saldo > 0;

-- =========================================================
-- TABLA ESTADISTICAS_USUARIO
-- Si no existe, la crea.
-- No toca claves foráneas para evitar errores.
-- =========================================================

CREATE TABLE IF NOT EXISTS estadisticas_usuario (
    id_usuario INT NOT NULL PRIMARY KEY,
    total_apostado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_ganado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_perdido DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    partidas_jugadas INT NOT NULL DEFAULT 0,
    partidas_ganadas INT NOT NULL DEFAULT 0,
    partidas_perdidas INT NOT NULL DEFAULT 0,
    partidas_empatadas INT NOT NULL DEFAULT 0,
    balance_neto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- CREAR ESTADÍSTICAS VACÍAS PARA USUARIOS EXISTENTES
-- =========================================================

INSERT INTO estadisticas_usuario (
    id_usuario,
    total_apostado,
    total_ganado,
    total_perdido,
    partidas_jugadas,
    partidas_ganadas,
    partidas_perdidas,
    partidas_empatadas,
    balance_neto
)
SELECT
    u.id_usuario,
    0.00,
    0.00,
    0.00,
    0,
    0,
    0,
    0,
    0.00
FROM usuarios u
WHERE NOT EXISTS (
    SELECT 1
    FROM estadisticas_usuario eu
    WHERE eu.id_usuario = u.id_usuario
);

-- =========================================================
-- VERIFICACIÓN
-- =========================================================

SELECT 
    id_usuario,
    nombre_usuario,
    usuario,
    nombre,
    dni,
    email,
    saldo,
    total_dinero,
    estado,
    fecha_registro
FROM usuarios;

SELECT *
FROM historial_apuestas
ORDER BY fecha DESC
LIMIT 20;

SELECT *
FROM estadisticas_usuario;

USE casino;

