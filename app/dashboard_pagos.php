<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';

// 1. OBTENCIÓN DE DATOS PARA FILTROS
$buscar = $_GET['buscar'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$monto_min = $_GET['monto_min'] ?? '';
$monto_max = $_GET['monto_max'] ?? '';

$query = "SELECT p.*, m.nombre_completo 
          FROM pagos p
          LEFT JOIN members m ON p.member_id = m.id
          WHERE p.gym_id = ?";

$params = [$_SESSION['gym_id']];

if($buscar){
    $query .= " AND (m.nombre_completo LIKE ? OR p.ticket LIKE ? OR p.concepto LIKE ?)";
    $params[] = "%$buscar%"; $params[] = "%$buscar%"; $params[] = "%$buscar%";
}
if($fecha_inicio){ $query .= " AND DATE(p.created_at) >= ?"; $params[] = $fecha_inicio; }
if($fecha_fin){ $query .= " AND DATE(p.created_at) <= ?"; $params[] = $fecha_fin; }
if($monto_min){ $query .= " AND p.monto >= ?"; $params[] = $monto_min; }
if($monto_max){ $query .= " AND p.monto <= ?"; $params[] = $monto_max; }

$query .= " ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. LÓGICA DE WIDGETS (ESTADÍSTICAS GLOBALES DEL GYM)
$hoy = date('Y-m-d');
$mes = date('Y-m');
$anio = date('Y');

$ingHoy = 0; $ingMes = 0; $ingAnio = 0; $ingTotal = 0;

$stmtStats = $conn->prepare("SELECT monto, created_at FROM pagos WHERE gym_id = ?");
$stmtStats->execute([$_SESSION['gym_id']]);
$ventasStats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

foreach($ventasStats as $v) {
    $m = (float)$v['monto'];
    $f = $v['created_at'];
    $ingTotal += $m;
    if(strpos($f, $anio) === 0) $ingAnio += $m;
    if(strpos($f, $mes) === 0) $ingMes += $m;
    if(strpos($f, $hoy) === 0) $ingHoy += $m;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos | Horizon Gym</title>
    <link rel="stylesheet" href="../styles/style1.css">
    <style>
        :root {
            --primary: #4361ee;
            --success: #05cd99;
            --warning: #ffb547;
            --text-main: #2b3674;
            --text-muted: #a3aed0;
            --white: #ffffff;
        }

        body { font-family: 'Inter', sans-serif; background: #f4f7fe; margin: 0; }
        main { display: grid; grid-template-columns: 250px 1fr; }
        .content-area { padding: 30px; }

        /* WIDGETS */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .stat-card { 
            background: var(--white); 
            padding: 20px; 
            border-radius: 20px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08); 
        }
        .icon-circle { 
            width: 50px; height: 50px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem; 
        }
        .stat-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin: 0; }
        .stat-value { font-size: 1.4rem; color: var(--text-main); font-weight: 700; margin: 0; }

        /* FILTROS & TABLA */
        .card { background: var(--white); border-radius: 20px; padding: 25px; box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08); }
        .filtros { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 25px; align-items: center; }
        .filtros input { padding: 10px 15px; border-radius: 12px; border: 1px solid #e0e5f2; background: #f4f7fe; outline: none; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 10px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0px 5px 15px rgba(67, 97, 238, 0.3); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid #f4f7fe; }
        td { padding: 15px; border-bottom: 1px solid #f4f7fe; color: var(--text-main); font-size: 0.9rem; }
        .monto-pago { color: var(--success); font-weight: 700; }
        .ticket-id { font-weight: 700; color: var(--primary); }

        @media (prefers-color-scheme: dark) {
            input{background-color: gray;}
            
        }
    </style>
</head>
<body>

<?php include("../components/navbar.php"); ?>

<main>
    <section><?php include("../components/sidebar.php"); ?></section>


    <section class="content-area">
        <h2 style="color: var(--text-main); font-weight: 800; margin-bottom: 5px;">Dashboard de Pagos</h2>
        <p style="color: var(--text-muted); margin-bottom: 25px;">Control de ingresos y transacciones</p>

        <!-- 📊 WIDGETS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-circle" style="background: #e6fff9; color: #05cd99;">$</div>
                <div class="stat-content">
                    <p class="stat-label">Hoy</p>
                    <h2 class="stat-value">$<?php echo number_format($ingHoy, 2); ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #eef2ff; color: #4361ee;">📅</div>
                <div class="stat-content">
                    <p class="stat-label">Mes actual</p>
                    <h2 class="stat-value">$<?php echo number_format($ingMes, 2); ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #fff5e6; color: #ffb547;">💹</div>
                <div class="stat-content">
                    <p class="stat-label">Año <?php echo $anio; ?></p>
                    <h2 class="stat-value">$<?php echo number_format($ingAnio, 2); ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #f4f7fe; color: #2b3674;">🏦</div>
                <div class="stat-content">
                    <p class="stat-label">Histórico</p>
                    <h2 class="stat-value">$<?php echo number_format($ingTotal, 2); ?></h2>
                </div>
            </div>
        </div>

        <!-- 🔍 FILTROS Y TABLA -->
        <div class="card" style="flex-direction: column;">
            <form method="GET" class="filtros">
                <input type="text" name="buscar" placeholder="Cliente, ticket o concepto..." value="<?php echo $buscar; ?>" style="flex: 1;">
                <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                <input type="number" step="0.01" name="monto_min" placeholder="Min $" value="<?php echo $monto_min; ?>" style="width: 100px;">
                <input type="number" step="0.01" name="monto_max" placeholder="Max $" value="<?php echo $monto_max; ?>" style="width: 100px;">
                <button class="btn-filter">Aplicar Filtros</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Ticket</th>
                        <th>Monto</th>
                        <th>Concepto</th>
                        <th>Fecha de Pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($pagos) > 0): ?>
                        <?php foreach($pagos as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td style="font-weight: 600;"><?php echo $p['nombre_completo'] ?: 'Consumidor Final'; ?></td>
                            <td class="ticket-id"><?php echo $p['ticket']; ?></td>
                            <td class="monto-pago">$<?php echo number_format($p['monto'], 2); ?></td>
                            <td><?php echo $p['concepto']; ?></td>
                            <td style="color: var(--text-muted);"><?php echo $p['created_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No se encontraron pagos con los filtros aplicados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

</body>
</html>