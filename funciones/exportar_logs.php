<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';

// Validar que exista un gimnasio en sesión
$gym_id = $_SESSION['gym_id'] ?? 0;
if (!$gym_id) {
    die("Error: No se encontró una sesión válida de gimnasio.");
}

// 1. Recibir posibles filtros por GET
$busqueda       = trim($_GET['buscar'] ?? '');
$usuario        = trim($_GET['usuario'] ?? '');
$user_id_filtro = trim($_GET['user_id'] ?? '');
$accion         = trim($_GET['accion'] ?? '');
$fecha_desde    = trim($_GET['fecha_desde'] ?? '');
$fecha_hasta    = trim($_GET['fecha_hasta'] ?? '');

// 2. Construir la consulta SQL dinámica
$sql = "SELECT * FROM logs WHERE gym_id = ?";
$params = [$gym_id];

if ($busqueda !== '') {
    $sql .= " AND descripcion LIKE ?";
    $params[] = "%$busqueda%";
}
if ($usuario !== '') {
    $sql .= " AND quien LIKE ?";
    $params[] = "%$usuario%";
}
if ($user_id_filtro !== '') {
    $sql .= " AND user_id = ?";
    $params[] = $user_id_filtro;
}
if ($accion !== '') {
    $sql .= " AND tipo LIKE ?";
    $params[] = "%$accion%";
}
if ($fecha_desde !== '') {
    $sql .= " AND DATE(fecha_hora) >= ?";
    $params[] = $fecha_desde;
}
if ($fecha_hasta !== '') {
    $sql .= " AND DATE(fecha_hora) <= ?";
    $params[] = $fecha_hasta;
}

$sql .= " ORDER BY fecha_hora DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}

// 3. Generar archivo de descarga para Excel (.xls)
$gym_name_clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_SESSION['nombre'] ?? 'Gym');
$filename = "Activity_Logs_" . $gym_name_clean . "_" . date('Y-m-d_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// UTF-8 BOM para soporte total de tildes y caracteres especiales en Excel
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11pt; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4361ee; color: #ffffff; font-weight: bold; padding: 12px 10px; border: 1px solid #c7d2fe; text-align: left; }
        td { padding: 10px; border: 1px solid #e2e8f0; vertical-align: middle; }
        .center { text-align: center; }
        .badge-delete { background-color: #fee2e2; color: #dc2626; font-weight: bold; }
        .badge-update { background-color: #e0e7ff; color: #4338ca; font-weight: bold; }
        .badge-insert { background-color: #d1fae5; color: #047857; font-weight: bold; }
        .badge-default { background-color: #f1f5f9; color: #475569; }
    </style>
</head>
<body>

    <h2>Bitácora de Actividades - GYMULTIMATE</h2>
    <p>
        <strong>Gimnasio ID:</strong> #<?= htmlspecialchars($gym_id) ?> | 
        <strong>Exportado por:</strong> <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?> (<?= htmlspecialchars($_SESSION['rol'] ?? 'Rol') ?>) | 
        <strong>Fecha de Exportación:</strong> <?= date('d/m/Y H:i:s') ?> | 
        <strong>Total de Registros:</strong> <?= count($logs) ?>
    </p>

    <table border="1">
        <thead>
            <tr>
                <th style="width: 70px;" class="center"># ID</th>
                <th style="width: 160px;" class="center">Fecha y Hora</th>
                <th style="width: 180px;">Usuario / Responsable</th>
                <th style="width: 320px;">Descripción de la Acción</th>
                <th style="width: 90px;" class="center">User ID</th>
                <th style="width: 80px;" class="center">Gym ID</th>
                <th style="width: 140px;" class="center">Tipo de Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $row): 
                    $claseBadge = 'badge-default';
                    $tipoUpper = strtoupper($row['tipo'] ?? '');
                    if (strpos($tipoUpper, 'DELETE') !== false || strpos($tipoUpper, 'ELIMIN') !== false) {
                        $claseBadge = 'badge-delete';
                    } elseif (strpos($tipoUpper, 'UPDATE') !== false || strpos($tipoUpper, 'EDIT') !== false) {
                        $claseBadge = 'badge-update';
                    } elseif (strpos($tipoUpper, 'INSERT') !== false || strpos($tipoUpper, 'AÑAD') !== false) {
                        $claseBadge = 'badge-insert';
                    }
                ?>
                <tr>
                    <td class="center">#<?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td class="center"><?= htmlspecialchars($row['fecha_hora'] ?? '') ?></td>
                    <td><strong><?= htmlspecialchars($row['quien'] ?? 'Sistema') ?></strong></td>
                    <td><?= htmlspecialchars($row['descripcion'] ?? '') ?></td>
                    <td class="center">#<?= htmlspecialchars($row['user_id'] ?? '') ?></td>
                    <td class="center">#<?= htmlspecialchars($row['gym_id'] ?? '') ?></td>
                    <td class="center <?= $claseBadge ?>">
                        <?= htmlspecialchars($row['tipo'] ?? '') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="center" style="padding: 20px; color: #64748b;">
                        No se encontraron registros de logs para los criterios seleccionados.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
