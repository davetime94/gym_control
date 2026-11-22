<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../data/security.php';

// Métricas de auditoría en vivo para los widgets superiores
$stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM logs WHERE gym_id = ?");
$stmtTotal->execute([$_SESSION['gym_id']]);
$totalLogs = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmtHoy = $conn->prepare("SELECT COUNT(*) as hoy FROM logs WHERE gym_id = ? AND DATE(fecha_hora) = CURDATE()");
$stmtHoy->execute([$_SESSION['gym_id']]);
$logsHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC)['hoy'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed - Logs | GYMULTIMATE</title>
    <link rel="stylesheet" href="../styles/style1.css?v=<?= time(); ?>">
</head>
<body>

<?php include("../components/navbar.php"); ?>

<main>
    <section>
        <?php include("../components/sidebar.php"); ?>
    </section>

    <section class="content">
        <!-- Encabezado de la Página -->
        <div class="header-flex" style="margin-bottom: 25px;">
            <div>
                <h2 style="color: var(--text-main); font-weight: 800; margin-bottom: 5px;">Bitácora de Actividades (Activity Feed)</h2>
                <p style="color: var(--text-muted); margin: 0;">Historial y registro en tiempo real de movimientos del sistema</p>
            </div>
            <div>
                <span id="contador" class="cat-badge cat-primary" style="font-size: 0.85rem; padding: 8px 16px;"></span>
            </div>
        </div>

        <!-- 📊 WIDGETS DE AUDITORÍA -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-circle" style="background: #eef2ff; color: #4361ee;">📋</div>
                <div class="stat-content">
                    <p class="stat-label">Total Registros</p>
                    <h2 class="stat-value"><?= number_format($totalLogs) ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #e6fff9; color: #05cd99;">⚡</div>
                <div class="stat-content">
                    <p class="stat-label">Acciones Hoy</p>
                    <h2 class="stat-value"><?= number_format($logsHoy) ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #fff5e6; color: #ffb547;">👤</div>
                <div class="stat-content">
                    <p class="stat-label">Sesión Activa</p>
                    <h2 class="stat-value" style="font-size: 1.15rem;"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #f4f7fe; color: #2b3674;">🛡️</div>
                <div class="stat-content">
                    <p class="stat-label">Rol del Usuario</p>
                    <h2 class="stat-value" style="font-size: 1.15rem;"><?= htmlspecialchars($_SESSION['rol'] ?? 'Rol') ?></h2>
                </div>
            </div>
        </div>

        <!-- 🔍 FILTROS Y TABLA CENTRALIZADA -->
        <div class="card" style="flex-direction: column;">
            <?php include("../components/table_logs.php"); ?>
        </div>
    </section>

</main>
    
<script src="../script.js"></script>
</body>
</html>
