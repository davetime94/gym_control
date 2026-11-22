<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../data/security.php';
require_once __DIR__ . '/../data/matrix.php'; // Aquí ya viene el $_SESSION['gym_id'] y asume que $conn es PDO

$hoy =  date('Y-m-d');



$miembro = null;
$accesos = [];
$pagos = [];
$error_msg = "";

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $gym_id = $_SESSION['gym_id'];
    
    // Búsqueda restringida al gym actual
    $sql_member = "SELECT * FROM members WHERE gym_id = ? AND (id = ? OR nombre_completo LIKE ? OR email LIKE ?) LIMIT 1";
    $stmt = $conn->prepare($sql_member);
    
    $param_like = "%" . $busqueda . "%";
    // Ejecutamos pasando los parámetros en orden
    $stmt->execute([$gym_id, $busqueda, $param_like, $param_like]);
    $miembro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($miembro) {
        $member_id = $miembro['id'];
        
        // Historial de Accesos
        $sql_accesos = "SELECT * FROM access_logs WHERE member_id = ? AND gym_id = ? ORDER BY fecha_hora DESC LIMIT 5";
        $stmt_acc = $conn->prepare($sql_accesos);
        $stmt_acc->execute([$member_id, $gym_id]);
        $accesos = $stmt_acc->fetchAll(PDO::FETCH_ASSOC);
        
        // Historial de Pagos
        $sql_pagos = "SELECT * FROM pagos WHERE member_id = ? AND gym_id = ? ORDER BY created_at DESC LIMIT 5";
        $stmt_pagos = $conn->prepare($sql_pagos);
        $stmt_pagos->execute([$member_id, $gym_id]);
        $pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        $error_msg = "No se encontró ningún miembro en este gimnasio con ese criterio.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Miembro</title>
    <link rel="stylesheet" href="../styles/style1.css?v=<?= time(); ?>">
    <style>
        .a_6 {
            background-color: var(--hover);
            color: var(--white);
        }
    </style>
</head>
<body>

<?php include("../components/navbar.php"); ?>

<main>
    <section>
        <?php include("../components/sidebar.php"); ?>
    </section>

    <section style="padding: 20px; width: 100%; overflow-y: auto;">
        
        <!-- Buscador usando tu clase panel-card y input-group -->
        <div class="panel-card" style="border-radius: 14px; padding:10px">
            <h3>Consultar Miembro</h3>
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="input-group" style="flex: 1; margin-bottom: 0;">
                    <label>Buscar por ID, Nombre o Email</label>
                    <input type="text" name="buscar" value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" required>
                </div>
                <button type="submit" class="btn-search" style="margin-top: 0; width: auto; padding: 12px 25px;">Buscar</button>
            </form>
        </div>

        <?php if ($error_msg): ?>
            <div class="danger alert-error" style="padding: 15px; border-radius: 10px;">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        
        <?php if ($miembro): ?><br>

            <div class="card" style="justify-content: space-around;">            
                <!-- Columna Izquierda: Identificación -->
                <div class="panel-card profile-photo-container card" style="width:fit-content; flex-direction:column;">
         
                    <h2 style="color: var(--text-main); margin-bottom: 5px;"><?php echo htmlspecialchars($miembro['nombre_completo']); ?></h2>
                    <p style="color: var(--text-muted); margin-bottom: 15px;">ID: #<?php echo $miembro['id']; ?></p>
                    
                    <span class="badge-status status-<?php echo $miembro['status_licencia']; ?>">
                        <?php echo htmlspecialchars($miembro['status_licencia']); ?>
                    </span>
                </div>

                <!-- Columna Derecha: Datos Generales -->
                <div class="panel-card card " style="width:fit-content;">
                    <div class="data-grid">
                        <h3>Información general</h3>
                        <div class="data-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($miembro['email'] ?: 'No registrado'); ?></p>
                        </div>
                        <div class="data-item">
                            <label>Teléfono</label>
                            <p><?php echo htmlspecialchars($miembro['telefono'] ?: 'No registrado'); ?></p>
                        </div>
                        <div class="data-item">
                            <label>Miembro desde</label>
                            <p><?php echo htmlspecialchars($miembro['created_at'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="data-item">
                            <label>Fecha de Vencimiento</label><br><br>

                            <span class="system-badge" ><?php echo htmlspecialchars($miembro['fecha_vencimiento'] ?: 'N/A'); ?></span>
                        </div>
                    </div>
                </div>


                <!-- Tablas de Historial usando tu clase master-table -->
                <div class="panel-card card" style="flex-direction: column; width:fit-content;">
                    <?php if (count($accesos) > 0): ?>
                        <table class="master-table ">

                            <thead>
                                <h3>Últimos Accesos</h3><br>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Estatus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accesos as $acceso): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y h:i A', strtotime($acceso['fecha_hora'])); ?></td>
                                        <td>
                                            <?php if($acceso['acceso_concedido']): ?>
                                                <span class="success badge">Permitido</span>
                                            <?php else: ?>
                                                <span class="danger badge">Denegado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">No hay registros recientes.</p>
                    <?php endif; ?>
                </div>

                <div class="panel-card card" style="flex-direction: column; width:fit-content;">
                    <h3>Últimos Pagos</h3>
                    <?php if (count($pagos) > 0): ?>
                        <table class="master-table">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pago['ticket']); ?></td>
                                        <td style="font-weight: 700;">$<?php echo number_format($pago['monto'], 2); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($pago['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">No hay pagos recientes.</p>
                    <?php endif; ?>
                </div>

                <!-- Columna Izquierda: Identificación -->
                <div class="card" style="width:400px; flex-direction:column;">
         
                    <h2 style="color: var(--text-main); margin-bottom: 5px;">Huella dactilar </h2>
                    <span class="system-badge" >
                        <?php echo htmlspecialchars($miembro['huella'] ?: 'N/A'); ?>
                    </span><br>
                    
                    <span class="badge-status <?= ($miembro['huella']=='Null'?'warning':($miembro['huella']==null?'danger':'success')) ?>">
                        <?php  if($miembro['huella']=='Null'||$miembro['huella']==Null){
                            echo "Sin huella digital";}
                            else{echo "Con huella activa";}?>
                    </span>



                </div>
           
            </div>

                <h3>Tabla de clientes</h3><br>

            <div class="card" >
                <?php include("../components/tabla_de_clientes.php"); ?>
            </div> 

        <?php elseif($miembro==null):?>
            <br>
            <div class="card" style="flex-direction: column;">

                <h3>Localiza a tus miembros</h3>
                <p>Encuenta la información más relevante</p>
                <img src="../recursos/Editing body text-amico.png"  width="400px" alt="">
            </div>
        <?php endif;?>

    </section>
</main>
   
<script src="../script.js"></script>
</body>
</html>