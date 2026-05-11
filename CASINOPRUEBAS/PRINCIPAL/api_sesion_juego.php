<?php
session_start();
include("conexion.php");

header("Content-Type: application/json");

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        "ok" => false,
        "message" => "No autenticado"
    ]);
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];
$action = $_GET['action'] ?? '';

function responder(array $data): void {
    echo json_encode($data);
    exit;
}

function leerJson(): array {
    return json_decode(file_get_contents("php://input"), true) ?? [];
}

function aCentavos($valor): int {
    return (int) round(((float) $valor) * 100);
}

function aDinero(int $centavos): float {
    return round($centavos / 100, 2);
}

function limpiarNombreJuego(string $juego): string {
    $juego = trim($juego);
    $juego = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $juego);
    $juego = substr($juego, 0, 50);

    return $juego !== '' ? $juego : 'desconocido';
}

function obtenerSaldoCuenta(mysqli $conexion, int $idUsuario): float {
    $stmt = $conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return (float) ($row['saldo'] ?? 0);
}

function haySesionJuegoActiva(): bool {
    return isset($_SESSION['game_session']) && $_SESSION['game_session']['active'] === true;
}

function sesionExpirada(): bool {
    if (!haySesionJuegoActiva()) {
        return false;
    }

    return time() >= (int) $_SESSION['game_session']['expires_at'];
}

function maximoApostableSesion(): int {
    if (!haySesionJuegoActiva()) {
        return 0;
    }

    /*
        Regla:
        - El bankroll inicial solo limita cuánto dinero se reserva del saldo real.
        - Si el jugador gana dentro de la sesión, esas ganancias aumentan
          el saldo de sesión.
        - Puede apostar hasta todo el saldo disponible de sesión.
    */
    return (int) $_SESSION['game_session']['saldo_sesion_cents'];
}

function estadoSesion(mysqli $conexion, int $idUsuario): array {
    if (!haySesionJuegoActiva()) {
        return [
            "ok" => true,
            "active" => false,
            "saldo_cuenta" => obtenerSaldoCuenta($conexion, $idUsuario),
            "redirect" => "principal.php"
        ];
    }

    $s = $_SESSION['game_session'];

    return [
        "ok" => true,
        "active" => true,
        "expired" => sesionExpirada(),
        "bankroll_inicial" => aDinero((int) $s['bankroll_inicial_cents']),
        "saldo_sesion" => aDinero((int) $s['saldo_sesion_cents']),
        "max_apuesta" => aDinero(maximoApostableSesion()),
        "apuesta_actual" => aDinero((int) $s['current_bet_cents']),
        "current_game" => $s['current_game'] ?? null,
        "expires_at" => (int) $s['expires_at'],
        "saldo_cuenta" => obtenerSaldoCuenta($conexion, $idUsuario)
    ];
}

function cerrarSesionJuego(mysqli $conexion, int $idUsuario): array {
    if (!haySesionJuegoActiva()) {
        return [
            "ok" => true,
            "closed" => true,
            "active" => false,
            "redirect" => "principal.php",
            "saldo_cuenta" => obtenerSaldoCuenta($conexion, $idUsuario)
        ];
    }

    if ((int) $_SESSION['game_session']['current_bet_cents'] > 0) {
        return [
            "ok" => false,
            "message" => "No puedes cerrar la sesión mientras hay una apuesta activa"
        ];
    }

    $saldoSesionCents = (int) $_SESSION['game_session']['saldo_sesion_cents'];
    $saldoSesion = aDinero($saldoSesionCents);

    $conexion->begin_transaction();

    try {
        /*
            Al cerrar sesión:
            todo el saldo de sesión vuelve al saldo real del jugador.
        */
        $stmt = $conexion->prepare("
            UPDATE usuarios 
            SET saldo = saldo + ? 
            WHERE id_usuario = ?
        ");
        $stmt->bind_param("di", $saldoSesion, $idUsuario);
        $stmt->execute();

        $conexion->commit();

        unset($_SESSION['game_session']);

        return [
            "ok" => true,
            "closed" => true,
            "active" => false,
            "redirect" => "principal.php",
            "saldo_cuenta" => obtenerSaldoCuenta($conexion, $idUsuario)
        ];

    } catch (Throwable $e) {
        $conexion->rollback();

        return [
            "ok" => false,
            "message" => "Error cerrando la sesión de juego"
        ];
    }
}

switch ($action) {
    case "status": {
        if (haySesionJuegoActiva() && sesionExpirada()) {
            if ((int) $_SESSION['game_session']['current_bet_cents'] <= 0) {
                responder(cerrarSesionJuego($conexion, $idUsuario));
            }
        }

        responder(estadoSesion($conexion, $idUsuario));
    }

    case "start": {
        $data = leerJson();

        $bankroll = isset($data['bankroll']) ? (float) $data['bankroll'] : 0;
        $time = isset($data['time']) ? (int) $data['time'] : 0;

        if ($bankroll <= 0) {
            responder([
                "ok" => false,
                "message" => "Monto inválido"
            ]);
        }

        if ($time <= 0) {
            responder([
                "ok" => false,
                "message" => "Tiempo inválido"
            ]);
        }

        /*
            Si ya hay sesión activa, se reutiliza para todos los juegos.
        */
        if (haySesionJuegoActiva() && !sesionExpirada()) {
            responder(estadoSesion($conexion, $idUsuario));
        }

        /*
            Si había una sesión expirada, se cierra antes de abrir otra.
        */
        if (haySesionJuegoActiva() && sesionExpirada()) {
            if ((int) $_SESSION['game_session']['current_bet_cents'] > 0) {
                responder([
                    "ok" => false,
                    "message" => "Hay una apuesta pendiente en una sesión expirada"
                ]);
            }

            cerrarSesionJuego($conexion, $idUsuario);
        }

        $bankrollCents = aCentavos($bankroll);

        $conexion->begin_transaction();

        try {
            $stmt = $conexion->prepare("
                SELECT saldo 
                FROM usuarios 
                WHERE id_usuario = ? 
                FOR UPDATE
            ");
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();

            $row = $stmt->get_result()->fetch_assoc();

            if (!$row) {
                throw new Exception("Usuario no encontrado");
            }

            $saldoActual = (float) $row['saldo'];
            $saldoActualCents = aCentavos($saldoActual);

            if ($bankrollCents > $saldoActualCents) {
                $conexion->rollback();

                responder([
                    "ok" => false,
                    "message" => "Saldo insuficiente"
                ]);
            }

            /*
                Se reserva el dinero:
                baja del saldo real y queda dentro de la sesión.
            */
            $nuevoSaldo = aDinero($saldoActualCents - $bankrollCents);

            $stmt = $conexion->prepare("
                UPDATE usuarios 
                SET saldo = ? 
                WHERE id_usuario = ?
            ");
            $stmt->bind_param("di", $nuevoSaldo, $idUsuario);
            $stmt->execute();

            $conexion->commit();

            $_SESSION['game_session'] = [
                "active" => true,
                "bankroll_inicial_cents" => $bankrollCents,
                "saldo_sesion_cents" => $bankrollCents,
                "current_bet_cents" => 0,
                "current_game" => null,
                "expires_at" => time() + $time
            ];

            responder(estadoSesion($conexion, $idUsuario));

        } catch (Throwable $e) {
            $conexion->rollback();

            responder([
                "ok" => false,
                "message" => "Error iniciando la sesión de juego"
            ]);
        }
    }

    case "debit": {
        /*
            Debita una apuesta del saldo de sesión.
            Todos los juegos deben llamar a este endpoint antes de iniciar jugada.
        */
        if (!haySesionJuegoActiva()) {
            responder([
                "ok" => false,
                "message" => "No hay sesión de juego activa"
            ]);
        }

        if (sesionExpirada()) {
            if ((int) $_SESSION['game_session']['current_bet_cents'] <= 0) {
                responder(cerrarSesionJuego($conexion, $idUsuario));
            }

            responder([
                "ok" => false,
                "message" => "La sesión expiró"
            ]);
        }

        if ((int) $_SESSION['game_session']['current_bet_cents'] > 0) {
            responder([
                "ok" => false,
                "message" => "Ya hay una apuesta activa"
            ]);
        }

        $data = leerJson();

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $amountCents = aCentavos($amount);

        $game = isset($data['game'])
            ? limpiarNombreJuego((string) $data['game'])
            : 'desconocido';

        if ($amountCents <= 0) {
            responder([
                "ok" => false,
                "message" => "Apuesta inválida"
            ]);
        }

        $maxApuestaCents = maximoApostableSesion();

        if ($amountCents > $maxApuestaCents) {
            responder([
                "ok" => false,
                "message" => "No puedes apostar más que tu saldo disponible de sesión"
            ]);
        }

        $_SESSION['game_session']['saldo_sesion_cents'] -= $amountCents;
        $_SESSION['game_session']['current_bet_cents'] = $amountCents;
        $_SESSION['game_session']['current_game'] = $game;

        responder(estadoSesion($conexion, $idUsuario));
    }

    case "settle": {
        /*
            Liquida una apuesta activa y guarda historial.

            result:
            - win  => paga apuesta * multiplier
            - loss => no paga nada
            - tie  => devuelve la apuesta
        */
        if (!haySesionJuegoActiva()) {
            responder([
                "ok" => false,
                "message" => "No hay sesión de juego activa"
            ]);
        }

        $currentBetCents = (int) $_SESSION['game_session']['current_bet_cents'];

        if ($currentBetCents <= 0) {
            responder([
                "ok" => false,
                "message" => "No hay apuesta activa para liquidar"
            ]);
        }

        $data = leerJson();

        $result = $data['result'] ?? '';
        $multiplier = isset($data['multiplier']) ? (float) $data['multiplier'] : 2;

        if (!in_array($result, ["win", "loss", "tie"], true)) {
            responder([
                "ok" => false,
                "message" => "Resultado inválido"
            ]);
        }

        if ($multiplier < 0 || $multiplier > 100) {
            responder([
                "ok" => false,
                "message" => "Multiplicador inválido"
            ]);
        }

        $payoutCents = 0;

        if ($result === "win") {
            $payoutCents = (int) round($currentBetCents * $multiplier);
        } elseif ($result === "tie") {
            $payoutCents = $currentBetCents;
        } else {
            $payoutCents = 0;
        }

        /*
            Actualizamos saldo de sesión con el pago.
        */
        $_SESSION['game_session']['saldo_sesion_cents'] += $payoutCents;

        $juego = $_SESSION['game_session']['current_game'] ?? 'desconocido';

        $montoApostado = aDinero($currentBetCents);
        $pago = aDinero($payoutCents);
        $gananciaNeta = aDinero($payoutCents - $currentBetCents);
        $saldoSesionDespues = aDinero((int) $_SESSION['game_session']['saldo_sesion_cents']);

        /*
            Guardamos historial:
            - pago = dinero total recibido por la jugada.
            - ganancia_neta = pago - monto apostado.
        */
        $stmt = $conexion->prepare("
            INSERT INTO historial_apuestas (
                id_usuario,
                juego,
                monto_apostado,
                resultado,
                multiplicador,
                pago,
                ganancia_neta,
                saldo_sesion_despues
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            responder([
                "ok" => false,
                "message" => "Error preparando historial"
            ]);
        }

        $stmt->bind_param(
            "isdsdddd",
            $idUsuario,
            $juego,
            $montoApostado,
            $result,
            $multiplier,
            $pago,
            $gananciaNeta,
            $saldoSesionDespues
        );

        $stmt->execute();

        $_SESSION['game_session']['current_bet_cents'] = 0;
        $_SESSION['game_session']['current_game'] = null;

        responder(estadoSesion($conexion, $idUsuario));
    }

    case "close": {
        responder(cerrarSesionJuego($conexion, $idUsuario));
    }

    default: {
        responder([
            "ok" => false,
            "message" => "Acción inválida"
        ]);
    }
}