CREATE DATABASE IF NOT EXISTS casino
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE casino;

-- =========================================================
-- TABLA USUARIOS
-- Si ya existe no se borra.
-- Si no existe, se crea una estructura mínima compatible.
-- =========================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(100) NOT NULL UNIQUE,
    usuario VARCHAR(100) NULL,
    nombre VARCHAR(100) DEFAULT 'Usuario Anónimo',
    dni VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    password_hash VARCHAR(255) NULL,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_dinero DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- TABLA HISTORIAL DE APUESTAS
-- Necesaria para perfil.php y para todos los juegos.
-- Incluye detalle para ruleta/minas/etc.
-- =========================================================

CREATE TABLE IF NOT EXISTS historial_apuestas (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    juego VARCHAR(50) NOT NULL,
    monto_apostado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    resultado ENUM('win','loss','tie') NOT NULL,
    multiplicador DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    pago DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ganancia_neta DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_sesion_despues DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    detalle LONGTEXT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario_fecha (id_usuario, fecha),
    INDEX idx_usuario_juego (id_usuario, juego),
    INDEX idx_juego_fecha (juego, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PROCEDIMIENTO PARA AGREGAR COLUMNAS SI NO EXISTEN
-- =========================================================

DROP PROCEDURE IF EXISTS add_column_if_not_exists;

DELIMITER $$

CREATE PROCEDURE add_column_if_not_exists(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `',
            p_table,
            '` ADD COLUMN `',
            p_column,
            '` ',
            p_definition
        );

        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- =========================================================
-- ACTUALIZAR ESTRUCTURA DE USUARIOS
-- =========================================================

CALL add_column_if_not_exists(
    'usuarios',
    'nombre_usuario',
    'VARCHAR(100) NULL'
);

CALL add_column_if_not_exists(
    'usuarios',
    'usuario',
    'VARCHAR(100) NULL'
);

CALL add_column_if_not_exists(
    'usuarios',
    'nombre',
    'VARCHAR(100) DEFAULT ''Usuario Anónimo'''
);

CALL add_column_if_not_exists(
    'usuarios',
    'dni',
    'VARCHAR(20) NULL'
);

CALL add_column_if_not_exists(
    'usuarios',
    'email',
    'VARCHAR(100) NULL'
);

CALL add_column_if_not_exists(
    'usuarios',
    'password_hash',
    'VARCHAR(255) NULL'
);

CALL add_column_if_not_exists(
    'usuarios',
    'saldo',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'usuarios',
    'total_dinero',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'usuarios',
    'fecha_registro',
    'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
);

CALL add_column_if_not_exists(
    'usuarios',
    'estado',
    'ENUM(''activo'', ''inactivo'', ''suspendido'') NOT NULL DEFAULT ''activo'''
);

-- =========================================================
-- ACTUALIZAR ESTRUCTURA DE HISTORIAL_APUESTAS
-- Esto es importante para la ruleta.
-- =========================================================

CALL add_column_if_not_exists(
    'historial_apuestas',
    'id_usuario',
    'INT NOT NULL'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'juego',
    'VARCHAR(50) NOT NULL'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'monto_apostado',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'resultado',
    'ENUM(''win'', ''loss'', ''tie'') NOT NULL DEFAULT ''loss'''
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'multiplicador',
    'DECIMAL(10,4) NOT NULL DEFAULT 0.0000'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'pago',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'ganancia_neta',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'saldo_sesion_despues',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'detalle',
    'LONGTEXT NULL'
);

CALL add_column_if_not_exists(
    'historial_apuestas',
    'fecha',
    'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
);

-- =========================================================
-- AJUSTAR TIPOS DE COLUMNAS IMPORTANTES
-- Para que la ruleta pueda guardar multiplicadores como 1.3750
-- =========================================================

ALTER TABLE historial_apuestas
MODIFY COLUMN monto_apostado DECIMAL(12,2) NOT NULL DEFAULT 0.00;

ALTER TABLE historial_apuestas
MODIFY COLUMN multiplicador DECIMAL(10,4) NOT NULL DEFAULT 0.0000;

ALTER TABLE historial_apuestas
MODIFY COLUMN pago DECIMAL(12,2) NOT NULL DEFAULT 0.00;

ALTER TABLE historial_apuestas
MODIFY COLUMN ganancia_neta DECIMAL(12,2) NOT NULL DEFAULT 0.00;

ALTER TABLE historial_apuestas
MODIFY COLUMN saldo_sesion_despues DECIMAL(12,2) NOT NULL DEFAULT 0.00;

-- =========================================================
-- ÍNDICES PARA HISTORIAL
-- Puede dar warning si ya existen, pero normalmente no rompe.
-- Si tu MySQL no permite repetir índices, ignora errores de duplicado.
-- =========================================================

SET @idx_usuario_fecha := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'historial_apuestas'
      AND INDEX_NAME = 'idx_usuario_fecha'
);

SET @sql_idx_usuario_fecha := IF(
    @idx_usuario_fecha = 0,
    'CREATE INDEX idx_usuario_fecha ON historial_apuestas (id_usuario, fecha)',
    'SELECT "idx_usuario_fecha ya existe"'
);

PREPARE stmt FROM @sql_idx_usuario_fecha;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_usuario_juego := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'historial_apuestas'
      AND INDEX_NAME = 'idx_usuario_juego'
);

SET @sql_idx_usuario_juego := IF(
    @idx_usuario_juego = 0,
    'CREATE INDEX idx_usuario_juego ON historial_apuestas (id_usuario, juego)',
    'SELECT "idx_usuario_juego ya existe"'
);

PREPARE stmt FROM @sql_idx_usuario_juego;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_juego_fecha := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'historial_apuestas'
      AND INDEX_NAME = 'idx_juego_fecha'
);

SET @sql_idx_juego_fecha := IF(
    @idx_juego_fecha = 0,
    'CREATE INDEX idx_juego_fecha ON historial_apuestas (juego, fecha)',
    'SELECT "idx_juego_fecha ya existe"'
);

PREPARE stmt FROM @sql_idx_juego_fecha;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- NORMALIZAR DATOS DE USUARIOS
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
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_balance_neto (balance_neto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- ACTUALIZAR ESTRUCTURA DE ESTADISTICAS_USUARIO
-- =========================================================

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'total_apostado',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'total_ganado',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'total_perdido',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'partidas_jugadas',
    'INT NOT NULL DEFAULT 0'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'partidas_ganadas',
    'INT NOT NULL DEFAULT 0'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'partidas_perdidas',
    'INT NOT NULL DEFAULT 0'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'partidas_empatadas',
    'INT NOT NULL DEFAULT 0'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'balance_neto',
    'DECIMAL(12,2) NOT NULL DEFAULT 0.00'
);

CALL add_column_if_not_exists(
    'estadisticas_usuario',
    'actualizado_en',
    'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
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
-- RECALCULAR ESTADÍSTICAS DESDE HISTORIAL
-- =========================================================

UPDATE estadisticas_usuario eu
JOIN (
    SELECT
        id_usuario,
        COALESCE(SUM(monto_apostado), 0) AS total_apostado,

        COALESCE(SUM(
            CASE
                WHEN ganancia_neta > 0 THEN ganancia_neta
                ELSE 0
            END
        ), 0) AS total_ganado,

        COALESCE(SUM(
            CASE
                WHEN ganancia_neta < 0 THEN ABS(ganancia_neta)
                ELSE 0
            END
        ), 0) AS total_perdido,

        COUNT(*) AS partidas_jugadas,

        COALESCE(SUM(
            CASE
                WHEN resultado = 'win' THEN 1
                ELSE 0
            END
        ), 0) AS partidas_ganadas,

        COALESCE(SUM(
            CASE
                WHEN resultado = 'loss' THEN 1
                ELSE 0
            END
        ), 0) AS partidas_perdidas,

        COALESCE(SUM(
            CASE
                WHEN resultado = 'tie' THEN 1
                ELSE 0
            END
        ), 0) AS partidas_empatadas,

        COALESCE(SUM(ganancia_neta), 0) AS balance_neto

    FROM historial_apuestas
    GROUP BY id_usuario
) h ON eu.id_usuario = h.id_usuario
SET
    eu.total_apostado = h.total_apostado,
    eu.total_ganado = h.total_ganado,
    eu.total_perdido = h.total_perdido,
    eu.partidas_jugadas = h.partidas_jugadas,
    eu.partidas_ganadas = h.partidas_ganadas,
    eu.partidas_perdidas = h.partidas_perdidas,
    eu.partidas_empatadas = h.partidas_empatadas,
    eu.balance_neto = h.balance_neto;

-- =========================================================
-- TRIGGER PARA ACTUALIZAR ESTADÍSTICAS AUTOMÁTICAMENTE
-- Cada vez que se inserta una apuesta.
-- =========================================================

DROP TRIGGER IF EXISTS trg_historial_apuestas_after_insert;

DELIMITER $$

CREATE TRIGGER trg_historial_apuestas_after_insert
AFTER INSERT ON historial_apuestas
FOR EACH ROW
BEGIN
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
    VALUES (
        NEW.id_usuario,
        NEW.monto_apostado,

        CASE
            WHEN NEW.ganancia_neta > 0 THEN NEW.ganancia_neta
            ELSE 0
        END,

        CASE
            WHEN NEW.ganancia_neta < 0 THEN ABS(NEW.ganancia_neta)
            ELSE 0
        END,

        1,

        CASE
            WHEN NEW.resultado = 'win' THEN 1
            ELSE 0
        END,

        CASE
            WHEN NEW.resultado = 'loss' THEN 1
            ELSE 0
        END,

        CASE
                    WHEN NEW.resultado = 'tie' THEN 1
            ELSE 0
        END,

        NEW.ganancia_neta
    )
    ON DUPLICATE KEY UPDATE
        total_apostado = total_apostado + NEW.monto_apostado,

        total_ganado = total_ganado + CASE
            WHEN NEW.ganancia_neta > 0 THEN NEW.ganancia_neta
            ELSE 0
        END,

        total_perdido = total_perdido + CASE
            WHEN NEW.ganancia_neta < 0 THEN ABS(NEW.ganancia_neta)
            ELSE 0
        END,

        partidas_jugadas = partidas_jugadas + 1,

        partidas_ganadas = partidas_ganadas + CASE
            WHEN NEW.resultado = 'win' THEN 1
            ELSE 0
        END,

        partidas_perdidas = partidas_perdidas + CASE
            WHEN NEW.resultado = 'loss' THEN 1
            ELSE 0
        END,

        partidas_empatadas = partidas_empatadas + CASE
            WHEN NEW.resultado = 'tie' THEN 1
            ELSE 0
        END,

        balance_neto = balance_neto + NEW.ganancia_neta;
END$$

DELIMITER ;

-- =========================================================
-- VISTA PARA PERFIL / CONSULTA RÁPIDA
-- =========================================================

CREATE OR REPLACE VIEW vista_estadisticas_perfil AS
SELECT
    u.id_usuario,

    COALESCE(
        NULLIF(u.nombre, ''),
        NULLIF(u.nombre_usuario, ''),
        NULLIF(u.usuario, ''),
        'Jugador'
    ) AS nombre_mostrado,

    COALESCE(u.saldo, 0) AS saldo,

    COUNT(h.id_historial) AS total_apuestas,

    COALESCE(SUM(h.monto_apostado), 0) AS total_apostado,

    COALESCE(SUM(
        CASE
            WHEN h.resultado = 'win' THEN 1
            ELSE 0
        END
    ), 0) AS ganadas,

    COALESCE(SUM(
        CASE
            WHEN h.resultado = 'loss' THEN 1
            ELSE 0
        END
    ), 0) AS perdidas,

    COALESCE(SUM(
        CASE
            WHEN h.resultado = 'tie' THEN 1
            ELSE 0
        END
    ), 0) AS empates,

    COALESCE(SUM(
        CASE
            WHEN h.ganancia_neta > 0 THEN h.ganancia_neta
            ELSE 0
        END
    ), 0) AS ganancias,

    COALESCE(SUM(
        CASE
            WHEN h.ganancia_neta < 0 THEN ABS(h.ganancia_neta)
            ELSE 0
        END
    ), 0) AS perdidas_dinero,

    COALESCE(SUM(h.ganancia_neta), 0) AS balance_neto

FROM usuarios u
LEFT JOIN historial_apuestas h
    ON u.id_usuario = h.id_usuario
GROUP BY
    u.id_usuario,
    u.nombre,
    u.nombre_usuario,
    u.usuario,
    u.saldo;

-- =========================================================
-- FOREIGN KEYS OPCIONALES
-- Las dejo comentadas para evitar errores en XAMPP/MySQL local.
-- El proyecto funciona sin ellas.
-- =========================================================

-- ALTER TABLE historial_apuestas
-- ADD CONSTRAINT fk_historial_usuario
-- FOREIGN KEY (id_usuario)
-- REFERENCES usuarios(id_usuario)
-- ON DELETE CASCADE;

-- ALTER TABLE estadisticas_usuario
-- ADD CONSTRAINT fk_estadisticas_usuario
-- FOREIGN KEY (id_usuario)
-- REFERENCES usuarios(id_usuario)
-- ON DELETE CASCADE;

-- =========================================================
-- DATOS DE PRUEBA OPCIONALES
-- No crean usuarios nuevos, solo actualizan si existen.
-- =========================================================

UPDATE usuarios SET
    usuario = nombre_usuario
WHERE usuario IS NULL OR usuario = '';

UPDATE usuarios SET
    nombre = 'Administrador Maestro',
    dni = '00000000T',
    email = 'admin@highstakes.com',
    usuario = nombre_usuario,
    total_dinero = 25000.00,
    estado = 'activo'
WHERE id_usuario = 1;

UPDATE usuarios SET
    nombre = 'Jugador Premium 1',
    dni = '12345678A',
    email = 'jugador1@highstakes.com',
    usuario = nombre_usuario,
    total_dinero = 1250.50,
    estado = 'activo'
WHERE id_usuario = 2;

UPDATE usuarios SET
    nombre = 'Jugador Premium 2',
    dni = '87654321Z',
    email = 'jugador2@highstakes.com',
    usuario = nombre_usuario,
    total_dinero = 850.75,
    estado = 'activo'
WHERE id_usuario = 3;

UPDATE usuarios SET
    nombre = 'Test Player',
    dni = '11111111X',
    email = 'test@highstakes.com',
    usuario = nombre_usuario,
    total_dinero = 750.25,
    estado = 'activo'
WHERE nombre_usuario = 'testplayer';

UPDATE usuarios SET
    nombre = 'Mcachiner 1',
    dni = '00000000M',
    email = 'mcachiner1@local.test',
    usuario = 'mcachiner1',
    total_dinero = saldo,
    estado = 'activo'
WHERE nombre_usuario = 'mcachiner1'
   OR usuario = 'mcachiner1';

-- =========================================================
-- CONSULTAS DE VERIFICACIÓN
-- =========================================================

SELECT
    'usuarios' AS tabla,
    COUNT(*) AS registros
FROM usuarios;

SELECT
    'historial_apuestas' AS tabla,
    COUNT(*) AS registros
FROM historial_apuestas;

SELECT
    'estadisticas_usuario' AS tabla,
    COUNT(*) AS registros
FROM estadisticas_usuario;

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
FROM usuarios
ORDER BY id_usuario ASC;

SELECT
    id_historial,
    id_usuario,
    juego,
    monto_apostado,
    resultado,
    multiplicador,
    pago,
    ganancia_neta,
    saldo_sesion_despues,
    detalle,
    fecha
FROM historial_apuestas
ORDER BY fecha DESC
LIMIT 20;

SELECT
    id_usuario,
    total_apostado,
    total_ganado,
    total_perdido,
    partidas_jugadas,
    partidas_ganadas,
    partidas_perdidas,
    partidas_empatadas,
    balance_neto,
    actualizado_en
FROM estadisticas_usuario
ORDER BY id_usuario ASC;

SELECT *
FROM vista_estadisticas_perfil
ORDER BY id_usuario ASC;

-- =========================================================
-- FIN DEL ARCHIVO
-- =========================================================