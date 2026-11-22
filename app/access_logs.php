<?php 
    require_once __DIR__ . '/../data/conn.php'; 
    require_once __DIR__ . '/../data/security.php';




    //EN ESTE PHP SOLO SE BUSCAN LOS RESULTADOS DEL HUD, POR LO QUE PUEDEN PERMANECER AQUÍ, LA LÓGICA DONDE SE EXTRAEN LOS DATOS HA SIDO MOVIDA A MATRIX
    // Filtros de búsqueda
        $gym_filtro = isset($_GET['gym_id']) ? $_GET['gym_id'] : null;
    $member_filtro = isset($_GET['member_id']) ? $_GET['member_id'] : null;

    // LÓGICA DE ESTADÍSTICAS (Resumen de Hoy)
    $hoy = date('Y-m-d');
    $stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM access_logs WHERE DATE(fecha_hora) = ? AND gym_id = ?");
    $stmtTotal->execute([$hoy, $_SESSION['gym_id']]);
    $totalHoy = $stmtTotal->fetchColumn();

    $stmtExitos = $conn->prepare("SELECT COUNT(*) FROM access_logs WHERE DATE(fecha_hora) = ? AND acceso_concedido = 1 AND gym_id = ?");
    $stmtExitos->execute([$hoy, $_SESSION['gym_id']]);
    $exitosHoy = $stmtExitos->fetchColumn();

    $denegadosHoy = $totalHoy - $exitosHoy;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Accesos | Horizon Gym</title>
    <link rel="stylesheet" href="../styles/style1.css?v=<?= time(); ?>">
</head>
<body>

    <?php include("../components/navbar.php"); ?>

    <main style="display: grid; grid-template-columns: 250px 1fr;">
        <section>
            <?php include("../components/sidebar.php"); ?>
        </section>


        <section style="padding: 30px;">
            <div style="margin-bottom: 25px;">
                <h2 style="color: var(--text-main); font-weight: 800; margin: 0;">Control de Accesos</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Monitoreo de entradas en tiempo real</p>
            </div>

            <!-- 📊 WIDGETS DE HOY -->
            <div class="stats-grid ">
                <div class="stat-card card center" >
                    <div class="stat-icon-container " >
                        <span style="color: var(--primary); font-size: 40px;">🕒</span>
                    </div>
                    <div class="stat-content ">
                        <p class="stat-label">Intentos Hoy</p>
                        <h2 class="stat-value"><?php echo $totalHoy; ?></h2>
                    </div>
                </div>

                <div class="stat-card card center">
                    <div class="stat-icon-container">
                        <span style="color: #05cd99; font-size: 40px;">✅</span>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Exitosos</p>
                        <h2 class="stat-value" style="color: #05cd99;"><?php echo $exitosHoy; ?></h2>
                    </div>
                </div>

                <div class="stat-card card center">
                    <div class="stat-icon-container" >
                        <span style="color: #ee5d50; font-size: 40px;">❌</span>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Denegados</p>
                        <h2 class="stat-value" style="color: #ee5d50;"><?php echo $denegadosHoy; ?></h2>
                    </div>
                </div>
            </div>

            <!-- 🔍 FILTROS -->
            <div class="card" style="margin-bottom: 20px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; display: block;">ID Miembro</label>
                        <input type="number" name="member_id" placeholder="Ej: 102" value="<?php echo $member_filtro; ?>" style="width: 100%; padding: 10px; border-radius: 12px; border: 1px solid #e0e5f2; background: #f4f7fe;">
                    </div>
                    <button type="submit" class="btn-login" style="margin: 0; width: 140px; padding: 10px;">Consultar</button>
                    <a href="access_logs.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.8rem; padding-bottom: 10px;">Limpiar</a>
                </form>
            </div>

            <!-- 📊 TABLA DE ACCESOS -->
            <div class="card">
                <h3 style="color: var(--text-main); font-size: 1.1rem; margin-bottom: 20px;">Historial Reciente</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID Log</th>
                            <th>Miembro</th>
                            <th>Gimnasio</th>
                            <th>Resultado</th>
                            <th>Fecha y Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php


                        while($row = $stmt->fetch()): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--text-muted);">#<?php echo $row['id']; ?></td>
                                <td>
                                    <div class="member-info">
                                        <span class="member-name"><?php echo $row['nombre_completo'] ?? 'Desconocido'; ?></span>
                                        <span class="member-id">ID: <?php echo $row['member_id']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">
                                        <?php echo $row['nombre_gym']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['acceso_concedido']): ?>
                                        <span class="status-pill bg-success-light">Concedido</span>
                                    <?php else: ?>
                                        <span class="status-pill bg-danger-light">Denegado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 700; font-size: 0.85rem;"><?php echo date('H:i:s', strtotime($row['fecha_hora'])); ?></span>
                                        <span style="color: var(--text-muted); font-size: 0.75rem;"><?php echo date('d/m/Y', strtotime($row['fecha_hora'])); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>