<?php
include("conn.php");
include("components/security.php");
include("matrix.php");

$resultado = null;
$mensaje = "";
$color = "";
$valor = null;
$clase_status = "";
$icono_status = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $numero_buscador = trim($_POST['numero_buscador']);
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT * FROM members WHERE id = ? AND gym_id = ?");
    $stmt->execute([$numero_buscador, $_SESSION['gym_id']]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if($resultado){
        if($resultado['status_licencia'] == 'Activa'){
            $mensaje = "ACCESO PERMITIDO";
            $color = "#00e676"; // Verde vibrante / Neón
            $clase_status = "status-approved";
            $icono_status = "✓";
            $valor = 1;
            
        } else {
            $mensaje = "ACCESO DENEGADO";
            $color = "#ff3860"; // Rojo carmesí vibrante
            $clase_status = "status-denied";
            $icono_status = "✕";
            $valor = 0;
        }

        // Registrar en logs generales
        $stmtLog = $conn->prepare("INSERT INTO logs (quien, descripcion, user_id, gym_id, tipo) VALUES (?, ?, ?, ?, ?)");
        $stmtLog->execute([
            "Sistema de Acceso",
            "Intento de entrada: " . $resultado['nombre_completo'] . " (" . $mensaje . ")",
            $_SESSION['user_id'],
            $_SESSION['gym_id'],
            "Acceso"
        ]);

        // Registrar en access_logs
        $stmt_log_acceso = $conn->prepare("INSERT INTO access_logs (gym_id, member_id, acceso_concedido) VALUES (?,?,?)");
        $stmt_log_acceso->execute([
            $_SESSION['gym_id'],
            $resultado['id'],
            $valor           
        ]);

        $conn->commit();
    } else {
        $mensaje = "CLIENTE NO ENCONTRADO";
        $color = "#cbd5e1"; // Gris plata de alta visibilidad
        $clase_status = "status-unknown";
        $icono_status = "?";
        $conn->rollBack();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style1.css">
    <title>Control de Acceso | Horizon Gym</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #070c1e;
            --card-dark: #0f172a;
            --primary: #4361ee;
            
            /* Colores de alta visibilidad */
            --green-glow: #00e676;
            --green-bg: rgba(0, 230, 118, 0.12);
            --green-border: rgba(0, 230, 118, 0.5);
            
            --red-glow: #ff3860;
            --red-bg: rgba(255, 56, 96, 0.12);
            --red-border: rgba(255, 56, 96, 0.5);
            
            --gray-glow: #cbd5e1;
            --gray-bg: rgba(203, 213, 225, 0.10);
            --gray-border: rgba(203, 213, 225, 0.4);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', 'Segoe UI', sans-serif;
            background: radial-gradient(circle at center, #111c44 0%, #070c1e 100%);
            color: #ffffff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Iluminación de fondo ambiente según el estado */
        .ambient-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.25;
            pointer-events: none;
            transition: all 0.5s ease;
            z-index: 0;
        }

        body.is-approved .ambient-glow {
            background: var(--green-glow);
            opacity: 0.45;
        }
        body.is-denied .ambient-glow {
            background: var(--red-glow);
            opacity: 0.45;
        }
        body.is-unknown .ambient-glow {
            background: var(--gray-glow);
            opacity: 0.25;
        }

        /* HEADER Y RELOJ EN VIVO */
        .top-bar {
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .brand-logo span {
            color: var(--primary);
        }

        .live-clock {
            font-size: 1.1rem;
            font-weight: 600;
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 18px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .scanner-status-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(67, 97, 238, 0.15);
            color: #818cf8;
            border: 1px solid rgba(67, 97, 238, 0.3);
        }

        .scanner-status-pill .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #818cf8;
            box-shadow: 0 0 10px #818cf8;
            animation: pulse-dot 1.5s infinite;
        }

        @keyframes pulse-dot {
            0% { transform: scale(0.95); opacity: 0.6; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.6; }
        }

        /* BUSCADOR Y FORMULARIO DE SCANNER */
        .search-container {
            z-index: 10;
            margin-bottom: 30px;
        }

        .search-container form {
            position: relative;
        }

        .search-container input {
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 18px 30px;
            padding-left: 55px;
            border-radius: 20px;
            font-size: 1.25rem;
            font-weight: 600;
            width: 420px;
            text-align: center;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .search-container input::placeholder {
            color: #64748b;
            font-weight: 500;
        }

        .search-container input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 30px rgba(67, 97, 238, 0.4);
            background: rgba(15, 23, 42, 0.95);
        }

        .scan-icon-input {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.3rem;
            opacity: 0.6;
            pointer-events: none;
        }

        /* CONTENEDOR PRINCIPAL DE RESULTADO */
        .main-wrapper {
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 580px;
            padding: 0 20px;
        }

        /* CARD DE ACCESO DE ALTA VISIBILIDAD */
        .access-card {
            width: 100%;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 36px;
            padding: 45px 35px 35px 35px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            border: 2px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* ESTADO: ACCESO PERMITIDO (VERDE VIBRANTE) */
        .access-card.status-approved {
            border-color: var(--green-border);
            background: linear-gradient(180deg, rgba(0, 230, 118, 0.18) 0%, rgba(15, 23, 42, 0.95) 35%);
            box-shadow: 0 20px 80px rgba(0, 230, 118, 0.35), inset 0 0 30px rgba(0, 230, 118, 0.15);
        }

        /* ESTADO: ACCESO DENEGADO (ROJO CARMESÍ) */
        .access-card.status-denied {
            border-color: var(--red-border);
            background: linear-gradient(180deg, rgba(255, 56, 96, 0.18) 0%, rgba(15, 23, 42, 0.95) 35%);
            box-shadow: 0 20px 80px rgba(255, 56, 96, 0.35), inset 0 0 30px rgba(255, 56, 96, 0.15);
        }

        /* ESTADO: NO ENCONTRADO (GRIS PIZARRA) */
        .access-card.status-unknown {
            border-color: var(--gray-border);
            background: linear-gradient(180deg, rgba(203, 213, 225, 0.15) 0%, rgba(15, 23, 42, 0.95) 35%);
            box-shadow: 0 20px 80px rgba(203, 213, 225, 0.2);
        }

        /* ÍCONO DE ESTADO GIGANTE */
        .status-badge-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: 900;
            transition: all 0.3s ease;
            position: relative;
        }

        .status-approved .status-badge-circle {
            background: #00e676;
            color: #042f1a;
            box-shadow: 0 0 40px #00e676, 0 0 80px rgba(0, 230, 118, 0.5);
            animation: bounce-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .status-denied .status-badge-circle {
            background: #ff3860;
            color: #ffffff;
            box-shadow: 0 0 40px #ff3860, 0 0 80px rgba(255, 56, 96, 0.5);
            animation: shake 0.4s ease-in-out;
        }

        .status-unknown .status-badge-circle {
            background: #64748b;
            color: #ffffff;
            box-shadow: 0 0 30px rgba(148, 163, 184, 0.6);
            animation: pop-in 0.4s ease;
        }

        @keyframes bounce-in {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-12px); }
            40% { transform: translateX(12px); }
            60% { transform: translateX(-8px); }
            80% { transform: translateX(8px); }
        }

        @keyframes pop-in {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* MENSAJE DE ESTADO DE ALTA VISIBILIDAD */
        .status-msg-banner {
            font-size: 2.4rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }

        .status-approved .status-msg-banner {
            color: #00e676;
            text-shadow: 0 0 25px rgba(0, 230, 118, 0.7);
        }

        .status-denied .status-msg-banner {
            color: #ff3860;
            text-shadow: 0 0 25px rgba(255, 56, 96, 0.7);
        }

        .status-unknown .status-msg-banner {
            color: #cbd5e1;
            text-shadow: 0 0 20px rgba(203, 213, 225, 0.4);
        }

        /* DATOS DEL SOCIO */
        .member-name-large {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .member-id-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 25px;
        }

        /* CAJA DE VIGENCIA DESTACADA */
        .expiry-box-enhanced {
            background: rgba(0, 0, 0, 0.35);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 10px;
        }

        .status-approved .expiry-box-enhanced {
            border-color: rgba(0, 230, 118, 0.4);
            background: rgba(0, 230, 118, 0.1);
        }

        .status-denied .expiry-box-enhanced {
            border-color: rgba(255, 56, 96, 0.4);
            background: rgba(255, 56, 96, 0.1);
        }

        .expiry-box-enhanced .label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            font-weight: 700;
            text-align: left;
        }

        .expiry-box-enhanced .expiry-date-text {
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            text-align: left;
            margin-top: 2px;
        }

        .status-tag {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-approved .status-tag {
            background: #00e676;
            color: #042f1a;
        }

        .status-denied .status-tag {
            background: #ff3860;
            color: #ffffff;
        }

        /* ESTADO STANDBY / DE ESPERA (SIN ESCANEO AÚN) */
        .idle-card {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(15px);
            border-radius: 36px;
            padding: 55px 35px;
            text-align: center;
            border: 2px dashed rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: pulse-border 3s infinite alternate;
        }

        @keyframes pulse-border {
            0% { border-color: rgba(67, 97, 238, 0.2); }
            100% { border-color: rgba(67, 97, 238, 0.6); }
        }

        .idle-icon-wrapper {
            width: 100px;
            height: 100px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary);
            position: relative;
        }

        .idle-icon-wrapper::after {
            content: '';
            position: absolute;
            top: 20%;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #818cf8;
            box-shadow: 0 0 12px #818cf8;
            animation: scan-line 2s infinite ease-in-out;
        }

        @keyframes scan-line {
            0% { top: 20%; }
            50% { top: 75%; }
            100% { top: 20%; }
        }

        .idle-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 10px;
        }

        .idle-subtitle {
            font-size: 1.05rem;
            color: #94a3b8;
            max-width: 360px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* BARRA DE DESCONEXIÓN / AUTO-RESET */
        .countdown-bar-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
        }

        .countdown-bar {
            height: 100%;
            width: 100%;
            background: var(--primary);
            transform-origin: left;
            animation: shrinkBar 9s linear forwards;
        }

        .status-approved .countdown-bar { background: #00e676; }
        .status-denied .countdown-bar { background: #ff3860; }
        .status-unknown .countdown-bar { background: #cbd5e1; }

        @keyframes shrinkBar {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        /* ANIMACIONES */
        .animate-card {
            animation: cardEnter 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(20px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body class="<?php echo $resultado ? ($resultado['status_licencia'] == 'Activa' ? 'is-approved' : 'is-denied') : ($mensaje ? 'is-unknown' : ''); ?>">

    <!-- LUZ DE FONDO AMBIENTAL -->
    <div class="ambient-glow"></div>

    <!-- BARRA SUPERIOR -->
    <header class="top-bar">
        <div class="brand-logo">
            ⚡ HORIZON <span>GYM</span>
        </div>
        
        <div class="live-clock" id="clock">--:--:--</div>

        <div class="scanner-status-pill">
            <span class="dot"></span>
            Escáner Listo
        </div>
    </header>

    <?php if(isset($resultado_plan['status']) && $resultado_plan['status'] != 'Active'): ?>

        <div class="main-wrapper">
            <div class="card"><?php include("components/pendiente.php"); ?></div>
        </div>

    <?php else: ?>

        <!-- FORMULARIO DE BÚSQUEDA / ESCÁNER -->
        <div class="search-container">
            <form onsubmit="reproducir()" method="POST">
                <span class="scan-icon-input">🔍</span>
                <input type="text" name="numero_buscador" placeholder="Escanear Gafete / Código..." required autofocus autocomplete="off">
            </form>
        </div>

        <div class="main-wrapper">

            <?php if($resultado || $mensaje): ?>

                <!-- TARJETA DE RESULTADO DE ACCESO -->
                <div class="access-card <?php echo $clase_status; ?> animate-card">
                    
                    <div class="status-badge-circle">
                        <?php echo $icono_status; ?>
                    </div>

                    <div class="status-msg-banner">
                        <?php echo $mensaje; ?>
                    </div>

                    <?php if($resultado): ?>
                        <div class="member-name-large">
                            <?php echo htmlspecialchars($resultado['nombre_completo']); ?>
                        </div>

                        <div class="member-id-badge">
                            ID Miembro: #<?php echo htmlspecialchars($resultado['id']); ?>
                        </div>

                        <div class="expiry-box-enhanced">
                            <div>
                                <div class="label">Vigencia de Membresía</div>
                                <div class="expiry-date-text">
                                    <?php echo date('d / M / Y', strtotime($resultado['fecha_vencimiento'])); ?>
                                </div>
                            </div>
                            <div class="status-tag">
                                <?php echo htmlspecialchars($resultado['status_licencia']); ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="member-name-large" style="color: #cbd5e1;">
                            Socio No Identificado
                        </div>
                        <p style="color: #94a3b8; font-size: 1.05rem; margin-top: 10px;">
                            El número de gafete escaneado no corresponde a ningún socio registrado.
                        </p>
                    <?php endif; ?>

                    <!-- BARRA DE TIEMPO REGRESIVO PARA AUTO-LIMPIEZA -->
                    <div class="countdown-bar-container">
                        <div class="countdown-bar"></div>
                    </div>
                </div>

            <?php else: ?>

                <!-- TARJETA DE ESPERA (STANDBY) -->
                <div class="idle-card animate-card">
                    <div class="idle-icon-wrapper">
                        💳
                    </div>
                    <h2 class="idle-title">Panel de Control de Acceso</h2>
                    <p class="idle-subtitle">
                        Pase la tarjeta o escanee el código de barras en el lector para validar la entrada.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

    <!-- AUDIO NOTIFICACIONES -->
    <audio id="approved">
        <source src="recursos/audios/correct_sound.mp3" type="audio/mp3">
    </audio>
    <audio id="denied">
        <source src="recursos/audios/Denied.mp3" type="audio/mp3">
    </audio>

    <script>
        // RELOJ EN VIVO
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const clockEl = document.getElementById('clock');
            if(clockEl) {
                clockEl.textContent = `${hours}:${minutes}:${seconds}`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // AUDIOS DE FEEDBACK
        const denied = document.getElementById("denied");
        const approved = document.getElementById("approved");

        <?php if($valor === 1): ?>
            if(approved) approved.play().catch(() => {});
        <?php elseif($valor === 0): ?>
            if(denied) denied.play().catch(() => {});
        <?php endif; ?>

        // AUTO-FOCUS CONTINUO EN CAMPO DE BÚSQUEDA
        const searchInput = document.querySelector('input[name="numero_buscador"]');
        if(searchInput) {
            document.addEventListener('click', () => {
                searchInput.focus();
            });
        }

        // AUTO-LIMPIAR Y REINICIAR DESPUÉS DE 9 SEGUNDOS
        <?php if($mensaje): ?>
        setTimeout(() => {
            window.location.href = window.location.pathname;
        }, 9000);
        <?php endif; ?>
    </script>
</body>
</html>