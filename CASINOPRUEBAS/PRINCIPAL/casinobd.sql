USE casino;

-- =========================
-- AGREGAR NUEVOS CAMPOS A USUARIOS
-- =========================
ALTER TABLE usuarios 
ADD COLUMN nombre VARCHAR(100) DEFAULT 'Usuario Anónimo' AFTER nombre_usuario,
ADD COLUMN dni VARCHAR(20) NULL AFTER nombre,
ADD COLUMN email VARCHAR(100) NULL AFTER dni,
ADD COLUMN total_dinero DECIMAL(12,2) DEFAULT 0.00 AFTER email,
ADD COLUMN fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP AFTER total_dinero,
ADD COLUMN estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo' AFTER fecha_registro;

-- =========================
-- AGREGAR id_usuario a estadisticas_usuario (por si no existe)
-- =========================
ALTER TABLE estadisticas_usuario 
MODIFY COLUMN id_usuario INT PRIMARY KEY,
ADD FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE;

-- =========================
-- POBLAR DATOS DE PRUEBA para los usuarios existentes
-- =========================
UPDATE usuarios SET 
    nombre = 'Administrador Maestro',
    dni = '00000000T',
    email = 'admin@highstakes.com',
    usuario = nombre_usuario,  -- Copia nombre_usuario al nuevo campo
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

-- =========================
-- ACTUALIZAR estadisticas con total_dinero
-- =========================
UPDATE estadisticas_usuario eu
JOIN usuarios u ON eu.id_usuario = u.id_usuario
SET eu.total_ganado = u.total_dinero;

-- =========================
-- VERIFICAR RESULTADO
-- =========================
SELECT 
    id_usuario,
    nombre,
    nombre_usuario AS 'usuario_antiguo',
    usuario,
    dni,
    email,
    saldo,
    total_dinero,
    estado,
    fecha_registro
FROM usuarios;