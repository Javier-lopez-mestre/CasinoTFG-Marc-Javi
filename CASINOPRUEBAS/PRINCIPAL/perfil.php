<?php
session_start();
include("conexion.php");

if(!isset($_SESSION['id_usuario'])){
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

$sql = "SELECT * FROM usuarios WHERE id_usuario=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$fecha_registro = $usuario['fecha_registro'] ? 
    date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) : 'No disponible';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - HIGHT_STAKES</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

    <style>
        body{ 
            overflow-x: hidden;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        /* Fondo - EXACTO del principal */
        #casino-bg{
            position: fixed;
            inset: 0;
            background-image: url('img/casino-bg.jpg');
            background-size: cover;
            background-position: center;
            z-index: -2;
        }

        #casino-overlay{
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: -1;
        }

        /* Profile cards */
        .profile-card {
            background: rgba(17,24,39,0.98);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255,255,255,0.15);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.6);
        }

        /* Status badges */
        .status-activo { 
            background: linear-gradient(135deg, #10b981, #059669); 
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        .status-inactivo { 
            background: linear-gradient(135deg, #6b7280, #4b5563); 
            box-shadow: 0 4px 12px rgba(107,114,128,0.3);
        }
        .status-suspendido { 
            background: linear-gradient(135deg, #ef4444, #dc2626); 
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
        }

        /* Header */
        header{ position: relative; z-index: 99999; }

        /* Animaciones */
        .hover-scale { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .hover-scale:hover { transform: translateY(-2px) scale(1.01); }
    </style>
</head>

<body class="min-h-screen text-white">

<!-- FONDO -->
<div id="casino-bg"></div>
<div id="casino-overlay"></div>

<!-- HEADER CORREGIDO - VA A PRINCIPAL.PHP -->
<header class="w-full border-b border-white/20 bg-black/40 backdrop-blur-xl">
    <div class="max-w-4xl mx-auto px-4 py-4 flex flex-col lg:flex-row items-center justify-between gap-4">
        
        <!-- LOGO - VA A PRINCIPAL.PHP -->
        <div class="flex items-center gap-3 cursor-pointer hover:scale-105 transition-all duration-200" onclick="window.location.href='principal.php'">
            <img src="img/logocuadrado.png" class="w-14 h-14 rounded-2xl shadow-xl">
            <h1 class="text-2xl font-black tracking-tight">HIGHTSTAKES</h1>
        </div>

        <!-- SALDO + BOTONES -->
        <div class="flex flex-wrap items-center justify-center lg:justify-end gap-3">
            <!-- SALDO EN TIEMPO REAL -->
            <div id="casino-saldo-perfil" class="bg-gradient-to-r from-yellow-400 to-orange-500 text-black px-6 py-3 rounded-2xl font-bold text-lg shadow-xl border-2 border-yellow-300/50 hover:scale-105 transition-all duration-200">
                Saldo: $<span class="saldo-disponible"><?php echo number_format($usuario['saldo'], 2); ?></span>
            </div>

            <!-- VOLVER A PRINCIPAL.PHP (NO DESLOGEA) -->
            <a href="principal.php" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 px-6 py-3 rounded-2xl font-bold text-lg shadow-xl border border-green-400/50 hover:scale-105 transition-all duration-200 hover-scale">
                🎰 Principal
            </a>
            
            <!-- LOGOUT CON CONFIRM -->
            <a href="logout.php" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 px-6 py-3 rounded-2xl font-bold text-lg shadow-xl border border-red-400/50 hover:scale-105 transition-all duration-200 hover-scale" 
               onclick="return confirm('¿Estás seguro de cerrar sesión? Tu progreso se guardará.')">
                🚪 Logout
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="max-w-3xl mx-auto px-4 py-12">

    <!-- TÍTULO -->
    <div class="text-center mb-16">
        <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl border-3 border-white/20">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>
        
        <h1 class="text-4xl md:text-5xl font-black mb-6 bg-gradient-to-r from-blue-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent">
            MI PERFIL
        </h1>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 p-6 bg-black/40 backdrop-blur-xl rounded-2xl border border-white/20 mb-12">
            <div class="text-2xl font-black text-yellow-400">#<?php echo $usuario['id_usuario']; ?></div>
            <div class="status-<?php echo $usuario['estado']; ?> px-6 py-3 rounded-2xl text-white font-bold text-lg shadow-xl">
                <?php echo ucfirst($usuario['estado']); ?>
            </div>
        </div>
    </div>

    <!-- SALDOS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <div class="profile-card rounded-3xl p-8 text-center hover-scale shadow-xl border border-white/10">
            <div class="text-4xl mb-6">💰</div>
            <p class="text-lg text-zinc-300 font-bold uppercase tracking-wider mb-6 opacity-90">Saldo Disponible</p>
            <div class="text-5xl md:text-6xl font-black bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent drop-shadow-xl saldo-disponible">
                $<?php echo number_format($usuario['saldo'], 2); ?>
            </div>
        </div>
        
        <div class="profile-card rounded-3xl p-8 text-center hover-scale shadow-xl border border-white/10">
            <div class="text-4xl mb-6">🏆</div>
            <p class="text-lg text-zinc-300 font-bold uppercase tracking-wider mb-6 opacity-90">Total Ganado</p>
            <div class="text-5xl md:text-6xl font-black bg-gradient-to-r from-emerald-400 to-teal-500 bg-clip-text text-transparent drop-shadow-xl total-dinero">
                $<?php echo number_format($usuario['total_dinero'], 2); ?>
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-16">
        
        <!-- DATOS PERSONALES -->
        <div class="profile-card rounded-3xl p-8 lg:p-10 hover-scale shadow-xl col-span-1 lg:col-span-2 order-2">
            <h3 class="text-2xl font-black mb-8 text-center gradient-text bg-gradient-to-r from-blue-400 to-purple-400">
                👤 Datos Personales
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-lg">
                <div>
                    <div class="font-bold text-zinc-300 mb-2">Nombre</div>
                    <div class="font-semibold text-xl"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                </div>
                <div>
                    <div class="font-bold text-zinc-300 mb-2">Usuario</div>
                    <div class="font-mono bg-zinc-900 px-3 py-2 rounded-xl font-semibold"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></div>
                </div>
                <div>
                    <div class="font-bold text-zinc-300 mb-2">Email</div>
                    <div class="text-lg"><?php echo htmlspecialchars($usuario['email'] ?? 'No registrado'); ?></div>
                </div>
                <div>
                    <div class="font-bold text-zinc-300 mb-2">DNI</div>
                    <div class="font-mono text-lg"><?php echo htmlspecialchars($usuario['dni'] ?? 'No registrado'); ?></div>
                </div>
            </div>
        </div>

        <!-- ESTADÍSTICAS -->
        <div class="profile-card rounded-3xl p-8 hover-scale shadow-xl order-1">
            <h3 class="text-2xl font-black mb-8 text-center gradient-text bg-gradient-to-r from-emerald-400 to-teal-400">
                📊
            </h3>
            
            <div class="space-y-6 text-center">
                <div>
                    <div class="text-3xl font-black text-yellow-400 mb-2">#<?php echo $usuario['id_usuario']; ?></div>
                    <div class="text-sm text-zinc-500 uppercase tracking-wider">ID Usuario</div>
                </div>
                
                <div class="status-<?php echo $usuario['estado']; ?> px-4 py-2 rounded-xl mx-auto font-bold text-lg mb-2">
                    <?php echo ucfirst($usuario['estado']); ?>
                </div>
                <div class="text-xs text-zinc-500 uppercase tracking-wider">Estado</div>
                
                <div class="border-t border-white/20 pt-4">
                    <div class="text-sm text-zinc-400"><?php echo $fecha_registro; ?></div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wider">Registro</div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTONES CORREGIDOS - VAN A PRINCIPAL.PHP -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center pt-12 border-t border-white/20 max-w-2xl mx-auto">
        <!-- VOLVER A PRINCIPAL.PHP -->
        <a href="principal.php" class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 py-4 px-8 rounded-2xl font-bold text-lg text-center shadow-xl hover-scale transition-all duration-200">
            🎰 Volver al Principal
        </a>
        
        <!-- HISTORIAL -->
        <a href="historial.php" class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 py-4 px-8 rounded-2xl font-bold text-lg text-center shadow-xl hover:scale-105 transition-all duration-200">
            📈 Historial
        </a>
    </div>

</main>

<!-- SCRIPT ACTUALIZACIÓN -->
<script>
$(document).ready(function(){
    function actualizarSaldos(){
        $.getJSON('saldo.php')
            .done(function(data){
                const saldoFormateado = data.saldo.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const totalFormateado = data.total_dinero.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                $('.saldo-disponible').text(saldoFormateado);
                $('.total-dinero').text(totalFormateado);
            });
    }
    
    actualizarSaldos();
    setInterval(actualizarSaldos, 5000);
});
</script>

</body>
</html>
