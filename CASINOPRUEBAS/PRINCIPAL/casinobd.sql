-- =========================
-- CREAR BASE DE DATOS
-- =========================
CREATE DATABASE casino;
USE casino;

-- =========================
-- TABLA USUARIOS (CORREGIDA)
-- =========================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    saldo DECIMAL(12,2) DEFAULT 0.00,
    bloqueado BOOLEAN DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- TABLA JUEGOS
-- =========================
CREATE TABLE juegos (
    id_juego INT AUTO_INCREMENT PRIMARY KEY,
    nombre_juego VARCHAR(50) NOT NULL,
    tipo VARCHAR(50)
);

-- =========================
-- TABLA TRANSACCIONES (MEJORADA PARA STRIPE)
-- =========================
CREATE TABLE transacciones (
    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('deposito','retiro','apuesta','ganancia') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    stripe_session_id VARCHAR(255) NULL,
    estado ENUM('pendiente','completado','fallido') DEFAULT 'completado',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =========================
-- TABLA APUESTAS
-- =========================
CREATE TABLE apuestas (
    id_apuesta INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_juego INT NOT NULL,
    monto_apuesta DECIMAL(12,2) NOT NULL,
    resultado ENUM('ganada','perdida'),
    ganancia DECIMAL(12,2) DEFAULT 0,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_juego) REFERENCES juegos(id_juego)
);

-- =========================
-- TABLA ESTADISTICAS
-- =========================
CREATE TABLE estadisticas_usuario (
    id_usuario INT PRIMARY KEY,
    total_apostado DECIMAL(12,2) DEFAULT 0,
    total_ganado DECIMAL(12,2) DEFAULT 0,
    total_perdido DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =========================
-- INSERTAR JUEGOS
-- =========================
INSERT INTO juegos (nombre_juego, tipo) VALUES
('Blackjack', 'Cartas'),
('Ruleta', 'Mesa'),
('Slots', 'Tragaperras'),
('Poker', 'Cartas');

-- =========================
-- USUARIOS (ACTUALIZADO A password_hash REAL)
-- =========================
INSERT INTO usuarios (nombre_usuario, password_hash, saldo) VALUES
('admin', '$2y$10$examplehashplaceholder1', 10000.00),
('jugador1', '$2y$10$examplehashplaceholder2', 500.00),
('jugador2', '$2y$10$examplehashplaceholder3', 300.00);

INSERT INTO usuarios (nombre_usuario, password_hash, saldo)
VALUES (
    'testplayer',
    '$2y$10$e0NRW8v9g9q2n9Yh9x8z6Oa9qQmQp7p2q5mQxw1QmZ8f7vQ9m6G6K',
    500.00
);
-- =========================
-- ESTADISTICAS
-- =========================
INSERT INTO estadisticas_usuario (id_usuario) VALUES
(1),
(2),
(3);

-- =========================
-- USUARIO MYSQL WEB
-- =========================
CREATE USER 'casino_user'@'localhost' IDENTIFIED BY 'superlocal';

GRANT ALL PRIVILEGES ON casino.* TO 'casino_user'@'localhost';

FLUSH PRIVILEGES;