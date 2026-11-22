<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../data/security.php';

// Verificación obligatoria de rol (Asegurar que eres el dueño o Super Admin del SaaS)
check_role(['Owner', 'Super Admin']);

require_once __DIR__ . '/../data/matrix.php';

$mensaje_sistema = "";
$error_sistema = "";




// =========================================================================
// 1. LÓGICA DE PROCESAMIENTO (ACCIONES DEL SUPER ADMIN)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
// A. ACTUALIZAR INFO DEL GIMNASIO Y VERIFICARLO
    if (isset($_POST['update_gym_master'])) {
        try {
            $stmt = $conn->prepare("UPDATE gyms SET nombre_gym=?, rfc_o_tax_id=?, plan_saas=?, tax_status=?, domicilio_completo=?, telefono_contacto=?, email_corporativo=?, representante_legal=?, status=? WHERE id=?");
            $stmt->execute([
                trim($_POST['nombre_gym']), trim($_POST['rfc_o_tax_id']), $_POST['plan_saas'], $_POST['tax_status'],
                trim($_POST['domicilio_completo']), trim($_POST['telefono_contacto']), trim($_POST['email_corporativo']),
                trim($_POST['representante_legal']), $_POST['status'], $_POST['gym_id']
            ]);

                        //Meter el log
            $stmt_log = $conn->prepare("
                INSERT INTO activityfeed_customer_service
                (agente, gym_id, descripción, notas)
                VALUES (?,?,?,?)
            ");

            $stmt_log->execute([
                $_SESSION['user_id'],
                $_POST['gym_id'],
                'UPDATE_GYM',
                'Nombre Gym: ' . $_POST['nombre_gym'] .
                ' | RFC: ' . $_POST['rfc_o_tax_id'] .
                ' | Plan: ' . $_POST['plan_saas'] .
                ' | Tax Status: ' . $_POST['tax_status'] .
                ' | Domicilio: ' . $_POST['domicilio_completo'] .
                ' | Teléfono: ' . $_POST['telefono_contacto'] .
                ' | Email: ' . $_POST['email_corporativo'] .
                ' | Representante: ' . $_POST['representante_legal'] .
                ' | Status: ' . $_POST['status']
            ]);
            $mensaje_sistema = "Gimnasio #" . $_POST['gym_id'] . " actualizado y verificado correctamente.";
        } catch(PDOException $e) { $error_sistema = "Error al actualizar GYM: " . $e->getMessage(); }
    }

// B. ACTUALIZAR USUARIO ADMIN Y APROBARLO
    if (isset($_POST['update_user_admin'])) {
        try {
            $stmt = $conn->prepare("UPDATE users_admin SET nombre=?, email=?, rol=?, approved=?, status=? WHERE id=?");
            $stmt->execute([
                trim($_POST['nombre']), trim($_POST['email']), $_POST['rol'], $_POST['approved'], $_POST['status'], $_POST['user_id']
            ]);


            $mensaje_sistema = "Usuario Admin #" . $_POST['user_id'] . " actualizado correctamente.";
        } catch(PDOException $e) { $error_sistema = "Error al actualizar Usuario: " . $e->getMessage(); }
    }

// C. ENVIAR NOTIFICACIÓN DIRECTA DESDE EL PANEL
    if (isset($_POST['send_master_notif'])) {
        try {
            // Se manda global al gym
            $stmt = $conn->prepare("INSERT INTO notificaciones (user_id, gym_id, tipo, prioridad, titulo, mensaje) VALUES (?, ?, 'Aviso', 'Alta', ?, ?)");
            // Asumimos user_id 0 o el tuyo como emisor del sistema
            $stmt->execute([$_SESSION['id'] ?? 0, $_POST['gym_id'], trim($_POST['titulo']), trim($_POST['mensaje'])]);
            $mensaje_sistema = "Notificación enviada al gimnasio #" . $_POST['gym_id'];
        } catch(PDOException $e) { $error_sistema = "Error al notificar: " . $e->getMessage(); }
    }

//D. Actualizar datos del usuario
if (isset($_POST['update_user_master'])) {
    try {

        $stmt = $conn->prepare("
            UPDATE users_admin
            SET
                nombre = ?,
                email = ?,
                rol = ?,
                status = ?,
                approved = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            trim($_POST['nombre']),
            trim($_POST['email']),
            $_POST['rol'],
            $_POST['status'],
            $_POST['approved'],
            $_POST['id']
        ]);

        $mensaje_sistema = "Usuario #" . $_POST['id'] . " actualizado correctamente.";

    } catch (PDOException $e) {
        $error_sistema = "Error al actualizar usuario: " . $e->getMessage();
    }
}

//E. Añadir un pago 
if (isset($_POST['registrar_pago_saas'])) {

    try {

        $stmt = $conn->prepare("
            INSERT INTO pagos_membresias_customer_service
            (
                ticket,
                id_agente,
                user_id,
                monto,
                metodo_pago,
                gym
            )
            VALUES
            (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($_POST['ticket']),
            $_POST['id_agente'],
            $_POST['user_id'],
            $_POST['monto'],
            $_POST['metodo_pago'],
            $_POST['gym']
        ]);

        $mensaje_sistema = "Pago registrado correctamente.";

    } catch (PDOException $e) {

        $error_sistema = "Error al registrar pago: " . $e->getMessage();

    }
}

//F. Añadir las notas
if (isset($_POST['add_note'])) {

    try {

        $stmt = $conn->prepare("
            INSERT INTO activityfeed_customer_service
            (
                agente,
                gym_id,
                descripción,
                notas
            )
            VALUES
            (?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['agente'],
            $_POST['gym_id'],
            trim($_POST['descripcion']),
            trim($_POST['notas'])
        ]);

        $mensaje_sistema = "Nota agregada correctamente.";

    } catch (PDOException $e) {

        $error_sistema = "Error al agregar nota: " . $e->getMessage();

    }
}
   
}

// =========================================================================
// 2. ENRUTAMIENTO DE VISTAS (DASHBOARD vs GESTIÓN)
// =========================================================================
$vista_actual = $_GET['view'] ?? 'dashboard';
$manage_gym_id = $_GET['gym_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torre de Control | Super Admin</title>
    <link rel="stylesheet" href="../styles/style1.css?v=<?= time(); ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="app-body">

<?php include("../components/navbar.php"); ?>

<main class="app-main" style="display: flex;">
<section class="app-sidebar" style="width: 260px;">
        <?php include("../components/sidebar.php"); ?>
    </section>

        <section class="app-content" style="flex: 1; height: calc(100vh - 90px); overflow-y: auto;">        <div class="admin-wrapper">
            
            <?php if($mensaje_sistema): ?>
                <div style="padding: 15px; background: rgba(5,205,153,0.15); color: #05cd99; border-radius: 12px; margin-bottom: 20px; font-weight: 700; display:flex; align-items:center; gap:10px;">
                    <i data-lucide="check-circle"></i> <?= $mensaje_sistema ?>
                </div>
            <?php endif; ?>
            <?php if($error_sistema): ?>
                <div style="padding: 15px; background: rgba(238,93,80,0.15); color: #ee5d50; border-radius: 12px; margin-bottom: 20px; font-weight: 700; display:flex; align-items:center; gap:10px;">
                    <i data-lucide="alert-triangle"></i> <?= $error_sistema ?>
                </div>
            <?php endif; ?>

            <!-- ========================================================================================= -->
            <!-- VISTA 1: DASHBOARD MAESTRO -->
            <!-- ========================================================================================= -->
            <?php if ($vista_actual === 'dashboard'): ?>
                
                <div class="mobile-stack" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 20px;">
                    <div>
                        <h1 class="page-title">SaaS Master Control</h1>
                        <p class="page-subtitle">Monitoreo global de gimnasios, auditorías y verificaciones.</p>
                    </div>
                </div>

                <?php
                // Consultas para KPIs
                $tot_gyms = $conn->query("SELECT COUNT(*) FROM gyms")->fetchColumn();
                $pend_gyms = $conn->query("SELECT COUNT(*) FROM gyms WHERE status = 'Pending'")->fetchColumn();
                $pend_users = $conn->query("SELECT COUNT(*) FROM users_admin WHERE approved = 'Pendiente'")->fetchColumn();
                $tot_pagos = $conn->query("SELECT SUM(monto) FROM pagos_membresias_customer_service")->fetchColumn();
                ?>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon st-blue"><i data-lucide="building"></i></div>
                        <div><p class="kpi-val"><?= $tot_gyms ?></p><p class="kpi-label">Gyms Totales</p></div>
                    </div>
                    <div class="kpi-card" style="<?= ($pend_gyms > 0) ? 'border-left: 5px solid #ffb81c;' : '' ?>">
                        <div class="kpi-icon st-yellow"><i data-lucide="alert-circle"></i></div>
                        <div><p class="kpi-val"><?= $pend_gyms ?></p><p class="kpi-label">Gyms Pendientes</p></div>
                    </div>
                    <div class="kpi-card" style="<?= ($pend_users > 0) ? 'border-left: 5px solid #ffb81c;' : '' ?>">
                        <div class="kpi-icon st-red"><i data-lucide="users"></i></div>
                        <div><p class="kpi-val"><?= $pend_users ?></p><p class="kpi-label">Owners x Aprobar</p></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon st-green"><i data-lucide="dollar-sign"></i></div>
                        <div><p class="kpi-val">$<?= number_format($tot_pagos, 2) ?></p><p class="kpi-label">Ingresos Globales</p></div>
                    </div>
                </div>

                <!-- TABLA JOIN: GYMS + USERS_ADMIN -->
<!-- ... existing code ... -->
                <div class="panel-card">

<!-- Este es el filtro del Manager -->
<div style="display:flex; gap:10px; margin-bottom:15px;">








</div>


                    <h3 class="panel-title"><i data-lucide="network" style="color: var(--primary);"></i> Ecosistema y Auditoría de Datos</h3>
                    <div class="table-responsive">
                        <table id="gymTable"class="master-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Gimnasio</th>
                                    <th>Status Gym 
                                        <div class="">    
                                            <select id="filterGym">
                                                <option value="">Todo</option>
                                                <option value="Activo">Activo</option>
                                                <option  value="Pending">Pendientes</option>
                                                <option value="Denegado">Denegados</option>
                                            </select>
                                        </div>
                                    </th>

                                    <th>Status user
                                        <div class="">
                                            <select id="filterUser">
                                                <option value="">Todo</option>
                                                <option value="Active">Active</option>
                                                <option value="Unactive">Unactive</option>
                                            </select>
                                        </div>
                                    </th>
                                    <th>User approved
                                        <div>
                                            <select id="filterApproved">
                                                <option value="">Todos</option>
                                                <option value="Aprobado">Aprobados</option>
                                                <option value="Pendiente">Pendientes</option>
                                                <option value="Rechazado">Rechazados</option>
                                            </select>
                                        </div>
                                    </th>
                                    <th>Personal Activo</th>
                                    <th>Diagnóstico de Datos
                                        <div>
                                            <select id="filterData">
                                                <option value="">Todos</option>
                                                <option value="Completo">Completos</option>
                                                <option value="Incompleto">Con faltantes</option>
                                            </select>
                                        </div>
                                    </th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // JOIN: Ahora contamos cuántos administradores tiene el gym en total (Managers/Receptionist)
$sql_join = "
SELECT
    g.id AS gym_id,
    g.nombre_gym,
    g.status AS gym_status,
    g.rfc_o_tax_id,
    g.domicilio_completo,

    u.id AS user_id,
    u.nombre AS user_nombre,
    u.email AS user_email,
    u.telefono AS user_telefono,
    u.rol AS user_rol,
    u.status AS user_status,
    u.created_at AS user_created_at,
    u.approved as user_approved,

    COUNT(u.id) AS staff_count

FROM gyms g
LEFT JOIN users_admin u
    ON u.gym_id = g.id

GROUP BY
    g.id,
    g.nombre_gym,
    g.status,
    g.rfc_o_tax_id,
    g.domicilio_completo,
    u.id,
    u.nombre,
    u.email,
    u.telefono,
    u.rol,
    u.status,
    u.created_at

ORDER BY
    (g.status = 'Pending') DESC,
    g.id DESC";
                                
                                $q_ecosistema = $conn->query($sql_join);
                                $ecosistema = $q_ecosistema ? $q_ecosistema->fetchAll(PDO::FETCH_ASSOC) : [];

                                foreach($ecosistema as $eco):
                                    // Diagnóstico actualizado: ¿Tiene Staff?
                                    $alertas = [];
                                    if(empty($eco['rfc_o_tax_id'])) $alertas[] = "Falta RFC";
                                    if(empty($eco['domicilio_completo'])) $alertas[] = "Sin Domicilio";
                                    if($eco['staff_count'] == 0) $alertas[] = "Gym sin Staff";
                                ?>
                                <tr>
                                    <td style="color: var(--text-muted);">#<?= $eco['gym_id'] ?></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:36px; height:36px; border-radius:10px; background:var(--bg-light); display:flex; align-items:center; justify-content:center; color:var(--primary); font-weight:900;">
                                                <?= strtoupper(substr($eco['nombre_gym'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($eco['nombre_gym']) ?>
                                        </div>
                                    </td>
                                    <td>
                                    
                                        <?php if($eco['gym_status'] == 'Active'): ?>
                                            <span class="badge st-green"><i data-lucide="check" width="14"></i> Activo</span>

                                        <?php elseif($eco['gym_status'] == 'Denied'): ?>
                                            <span class="badge st-red"><i data-lucide="check" width="14"></i> Denegado</span>


                                        <?php else: ?>
                                            <span class="badge st-yellow"><i data-lucide="clock" width="14"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge
                                        <?=  
                                            $eco['user_status']=='1'?'success':
                                            ($eco['user_status']=='0'?'danger':'') ?>" > 


                                            <?php if($eco['user_status']=='1'): ?> Active
                                            <?php elseif($eco['user_status']=='0'):?>Unactive
                                            <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge
                                        <?=  
                                            $eco['user_approved']=='Aprobado'?'success':
                                            ($eco['user_approved']=='Pendiente'?'warning':
                                            ($eco['user_approved']=='Rechazado'?'danger':'')) ?>" > 


                                            <?php echo $eco['user_approved'] ?> 
                                    </td>

                                    <td>
                                        <span class="badge st-blue"><?= $eco['staff_count'] ?> usuarios</span>
                                    </td>
                                    <td>
                                        <?php if(!empty($alertas)): ?>
                                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                                <?php foreach($alertas as $alerta): ?>
                                                    <span class="missing-pill"><?= $alerta ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge st-green" style="background:transparent; padding:0;"><i data-lucide="shield-check" width="16"></i> Completo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="?view=manage&gym_id=<?= $eco['gym_id'] ?>" class="btn-action btn-primary" style="padding: 8px 16px;">
                                            Gestionar <i data-lucide="arrow-right" width="16"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                        </tbody>
                    </table>
                </div>

            <!-- ========================================================================================= -->
            <!-- VISTA 2: PANEL QUIRÚRGICO (COMMAND CENTER DEL GYM) -->
            <!-- ========================================================================================= -->
            <?php elseif ($vista_actual === 'manage' && $manage_gym_id): 
                $stmt_gym = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
                $stmt_gym->execute([$manage_gym_id]);
                $gymInfo = $stmt_gym->fetch(PDO::FETCH_ASSOC);




                $stmt_users = $conn->prepare("SELECT * FROM users_admin WHERE gym_id = ?");
                $stmt_users->execute([$manage_gym_id]);
                $userInfo = $stmt_users->fetch(PDO::FETCH_ASSOC);
                
                if(!$gymInfo) die("Gimnasio no encontrado.");
            ?>
                
<!-- DIV DE HASTA ARRIBA, MESA DE -->
                <div class="mobile-stack" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 20px;">
                    <div>
                        <a href="?view=dashboard" class="btn-action btn-outline" style="margin-bottom: 15px;">
                            <i data-lucide="arrow-left" width="16"></i> Volver al Dashboard
                        </a>
                        <h1 class="page-title">Mesa de Control: <?= htmlspecialchars($gymInfo['nombre_gym']) ?></h1>
                        <p class="page-subtitle">ID del Negocio: #<?= $gymInfo['id'] ?> | Registrado el: <?= date('d M, Y', strtotime($gymInfo['created_at'])) ?></p>
                    </div>
                    <?php if($gymInfo['status'] == 'Pending'): ?>
                        <div class="badge st-yellow" style="font-size: 1rem; padding: 10px 20px;">⚠️ Auditoría Pendiente</div>
                    <?php else: ?>
                        <div class="badge st-green" style="font-size: 1rem; padding: 10px 20px;">✓ Verificado y Activo</div>
                    <?php endif; ?>
                </div>

                <!-- TABS DE NAVEGACIÓN -->
                <div class="tabs-nav">
                    <button class="tab-btn active" onclick="openTab(event, 'tab-info')"><i data-lucide="info" width="16" style="margin-right:5px; vertical-align:middle;"></i> Verificación del Gym</button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-info_admin')"><i data-lucide="shield" width="16" style="margin-right:5px; vertical-align:middle;"></i> Verificación usuario</button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-users')"><i data-lucide="users" width="16" style="margin-right:5px; vertical-align:middle;"></i> Admin Users</button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-pagos')"><i data-lucide="dollar-sign" width="16" style="margin-right:5px; vertical-align:middle;"></i> Finanzas SaaS</button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-logs')"><i data-lucide="activity" width="16" style="margin-right:5px; vertical-align:middle;"></i> Logs Operativos</button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-notif')"><i data-lucide="bell" width="16" style="margin-right:5px; vertical-align:middle;"></i> Alertas & Tickets</button>
                </div>

                <!-- TAB 1: INFO & VERIFICACIÓN -->
                <div id="tab-info" class="tab-content active">
                    <div class="panel-card">
                        <h3>Expediente del Negocio (Editar & Verificar)</h3>
                        <form method="POST">
                            <input type="hidden" name="gym_id" value="<?= $gymInfo['id'] ?>">
                            <div class="form-grid">
                                <div>
                                    <label>Nombre Comercial *</label>
                                    <input type="text" name="nombre_gym" value="<?= htmlspecialchars($gymInfo['nombre_gym']) ?>" required>
                                </div>
                                <div>
                                    <label>Plan SaaS</label>
                                    <select name="plan_saas">
                                        <option <?= $gymInfo['plan_saas']=='Free'?'selected':'' ?>>Free</option>
                                        <option <?= $gymInfo['plan_saas']=='Pro'?'selected':'' ?>>Pro</option>
                                        <option <?= $gymInfo['plan_saas']=='Enterprise'?'selected':'' ?>>Enterprise</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Status de Verificación</label>
                                    <select name="status" style="background: <?= $gymInfo['status']=='Pending'?'#fff5e6':'#e6fcf5' ?>; font-weight:bold;">
                                        <option value="Pending" <?= $gymInfo['status']=='Pending'?'selected':'' ?>>Pending (Bloqueado/Revisión)</option>
                                        <option value="Active" <?= $gymInfo['status']=='Active'?'selected':'' ?>>Active (Verificado y Aprobado)</option>
                                        <option  value="Denied" <?= $gymInfo['status']=='Denied'?'selected':'' ?>>Denied (Denegado o Cancelado)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr style="border:0; border-top:1px solid #e0e5f2; margin: 20px 0;">

                            <div class="form-grid">
                                <div><label>RFC / Tax ID</label><input type="text" name="rfc_o_tax_id" value="<?= htmlspecialchars($gymInfo['rfc_o_tax_id']??'') ?>"></div>
                                <div><label>Régimen Fiscal</label>
                                    <select name="tax_status">
                                        <option value="Moral" <?= ($gymInfo['tax_status']??'')=='Moral'?'selected':'' ?>>Persona Moral</option>
                                        <option value="Fisica" <?= ($gymInfo['tax_status']??'')=='Fisica'?'selected':'' ?>>Persona Física</option>
                                    </select>
                                </div>
                                <div><label>Teléfono Corporativo</label><input type="text" name="telefono_contacto" value="<?= htmlspecialchars($gymInfo['telefono_contacto']??'') ?>"></div>
                                <div><label>Email Corporativo</label><input type="email" name="email_corporativo" value="<?= htmlspecialchars($gymInfo['email_corporativo']??'') ?>"></div>
                            </div>

                            <div style="margin-top:20px;">
                                <label>Domicilio Completo</label>
                                <input type="text" name="domicilio_completo" value="<?= htmlspecialchars($gymInfo['domicilio_completo']??'') ?>">
                            </div>
                            <div style="margin-top:20px;">
                                <label>Representante Legal (Firma)</label>
                                <input type="text" name="representante_legal" value="<?= htmlspecialchars($gymInfo['representante_legal']??'') ?>">
                            </div>

                            <button type="submit" name="update_gym_master" class="btn-action btn-primary" style="margin-top:25px;">
                                <i data-lucide="save" width="16"></i> Guardar Cambios del Gimnasio
                            </button>
                        </form>
                    </div>
                </div>

                <!-- TAB 1.5 VERIFICACIÓN DEL  -->
                <div id="tab-info_admin" class="tab-content active">
                    <div class="panel-card">
                        <h3>Expediente del Cliente (Editar & Verificar)</h3>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $userInfo['id'] ?>">

                            <div class="form-grid">

                                <div>
                                    <label>Gym ID</label>
                                    <input type="number" name="gym_id" disabled  value="<?= $userInfo['gym_id'] ?>" required>
                                </div>

                                <div>
                                    <label>Nombre</label>
                                    <input type="text" name="nombre" value="<?= htmlspecialchars($userInfo['nombre']) ?>" required>
                                </div>

                                <div>
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($userInfo['email']) ?>" required>
                                </div>

                                <div style="display: none;" >
                                    <label>Password</label>
                                    <input hidden  type="password" name="password" disabled value="<?= htmlspecialchars($userInfo['password']) ?>" required>
                                </div>



                                <div>
                                    <label>Rol</label>
                                    <select name="rol">
                                        <option value="Manager" <?= $userInfo['rol'] == 'Manager' ? 'selected' : '' ?>>Manager</option>
                                        <option value="Receptionist" <?= $userInfo['rol'] == 'Receptionist' ? 'selected' : '' ?>>Receptionist</option>
                                    </select>
                                </div>

                                <div>
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="1" <?= $userInfo['status'] == 1 ? 'selected' : '' ?>>Activo</option>
                                        <option value="0" <?= $userInfo['status'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                                    </select>
                                </div>

                                <div>
                                    <label>Aprobación</label>
                                    <select name="approved">
                                        <option value="Pendiente" <?= $userInfo['approved'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="Aprobado" <?= $userInfo['approved'] == 'Aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                        <option value="Rechazado" <?= $userInfo['approved'] == 'Rechazado' ? 'selected' : '' ?>>Rechazado</option>
                                    </select>
                                </div>

                         
                            </div>

                            <button type="submit" name="update_user_master" class="btn-action btn-primary" style="margin-top:25px;">
                                <i data-lucide="save" width="16"></i>
                                Guardar Cambios del Usuario
                            </button>
                        </form>
                    </div>





                </div>
                <!-- TAB 2: USUARIOS ADMINISTRATIVOS -->
                <div id="tab-users" class="tab-content">
                    <?php
                    $stmt_u = $conn->prepare("SELECT * FROM users_admin WHERE gym_id = ?");
                    $stmt_u->execute([$manage_gym_id]);
                    $users = $stmt_u->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($users) > 0):
                        foreach($users as $u):
                    ?>
                        <div class="panel-card" style="border-left: 5px solid <?= $u['approved']=='Aprobado'?'#05cd99':'#ffb81c' ?>;">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <div class="form-grid" style="align-items: flex-end;">
                                    <div><label>Nombre del Usuario</label><input type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']) ?>"></div>
                                    <div><label>Email de Acceso</label><input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>"></div>
                                    <div><label>Rol en el Sistema</label>
                                        <select name="rol">
                                            <option <?= $u['rol']=='Owner'?'selected':'' ?>>Owner</option>
                                            <option <?= $u['rol']=='Manager'?'selected':'' ?>>Manager</option>
                                            <option <?= $u['rol']=='Receptionist'?'selected':'' ?>>Receptionist</option>
                                        </select>
                                    </div>
                                    <div><label>Aprobación SaaS</label>
                                        <select name="approved">
                                            <option value="Aprobado" <?= $u['approved']=='Aprobado'?'selected':'' ?>>Aprobado</option>
                                            <option value="Pendiente" <?= $u['approved']=='Pendiente'?'selected':'' ?>>Pendiente</option>
                                            <option value="Bloqueado" <?= $u['approved']=='Bloqueado'?'selected':'' ?>>Bloqueado</option>
                                        </select>
                                    </div>
                                    <div><label>Estado Acceso</label>
                                        <select name="status">
                                            <option value="1" <?= $u['status']==1?'selected':'' ?>>Activo (1)</option>
                                            <option value="0" <?= $u['status']==0?'selected':'' ?>>Suspendido (0)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <button type="submit" name="update_user_admin" class="btn-action btn-primary" style="width:100%; justify-content:center;">
                                            Actualizar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php 
                        endforeach;
                    else: echo "<div class='panel-card'><p>Este gimnasio no tiene usuarios administrativos creados.</p></div>";
                    endif;
                    ?>
                </div>

                <!-- TAB 3: FINANZAS Y PAGOS -->
                <div id="tab-pagos" class="tab-content">

                    <div class="panel-card">
                        <h3>Registrar Pago de Membresía (SaaS)</h3>

                        <form method="POST" enctype="multipart/form-data">

                            <input type="hidden" name="gym" value="<?= $manage_gym_id ?>">
                            <input type="hidden" name="user_id" value="<?= $userInfo['id'] ?>">
                            <input type="hidden" name="id_agente" value="<?= $_SESSION['user_id'] ?>">

                            <div class="form-grid">

                                <div>
                                    <label>Ticket</label>
                                    <input type="text" name="ticket" required>
                                </div>

                                <div>
                                    <label>Monto</label>
                                    <input type="number" name="monto" min="1" required>
                                </div>

                                <div>
                                    <label>Método de Pago</label>
                                    <select name="metodo_pago" required>
                                        <option value="Transferencia">Transferencia</option>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Tarjeta">Tarjeta</option>
                                        <option value="Cortesia">Cortesía</option>
                                    </select>
                                </div>

                                <div>
                                    <label>Agente</label>
                                    <input type="text"
                                        value="<?= htmlspecialchars($_SESSION['nombre']) ?>"
                                        disabled>
                                </div>

                                <div>
                                    <label>Comprobante (Opcional)</label>
                                    <input type="file" name="comprobante">
                                </div>

                            </div>

                            <button type="submit"
                                    name="registrar_pago_saas"
                                    class="btn-action btn-success"
                                    style="margin-top:20px;">
                                Registrar Pago
                            </button>

                        </form>
                    </div>


                    <div class="panel-card">
                        <h3>Registro de Pagos de Membresía (SaaS)</h3>

                        <table class="master-table">
                            <thead>
                                <tr>
                                    <th>ID Pago</th>
                                    <th>Ticket</th>
                                    <th>Agente que cobró</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $stmt_p = $conn->prepare("
                                    SELECT *
                                    FROM pagos_membresias_customer_service
                                    WHERE gym = ?
                                    ORDER BY created_at DESC
                                ");

                                $stmt_p->execute([$manage_gym_id]);

                                while($p = $stmt_p->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td>#<?= $p['id'] ?></td>

                                    <td>
                                        <strong><?= htmlspecialchars($p['ticket'] ?? '-') ?></strong>
                                    </td>

                                    <td>
                                        Agente ID: <?= $p['id_agente'] ?>
                                    </td>

                                    <td style="color:#05cd99;font-weight:800;">
                                        $<?= number_format($p['monto'],2) ?>
                                    </td>

                                    <td>
                                        <span class="badge st-blue">
                                            <?= $p['metodo_pago'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>
                    </div>

                </div>

                <!-- TAB 4: LOGS Y AUDITORÍA -->
                <div id="tab-logs" class="tab-content">
                    <div class="panel-card">
                        <h3>Notas - Activity feed</h3>
                        <h3>Agregar Nota</h3>

                        <form method="POST">

                            <input type="hidden" name="agente" value="<?= $_SESSION['user_id'] ?>">
                            <input type="hidden" name="gym_id" value="<?= $manage_gym_id ?>">

                            <div class="form-grid">

                                <div>
                                    <label>Descripción</label>
                                    <input type="text"
                                        name="descripcion"
                                        placeholder="Título o asunto de la nota"
                                        required>
                                </div>

                                <div style="grid-column:1/-1;">
                                    <label>Notas</label>
                                    <textarea
                                        name="notas"
                                        rows="6"
                                        placeholder="Escribe aquí los detalles..."
                                        required></textarea>
                                </div>


                            <button
                                type="submit"
                                name="add_note"
                                class="btn-action btn-primary"
                                style="margin-top:20px;">
                                Agregar Nota
                            </button>

                        </form>
                    </div>

                    <div class="panel-card" style="margin-top: 25px;">
                        <h3>Historial - Activity Feed</h3>
                        <div style="overflow-x: auto;">
                            <table class="master-table">
                                <thead>
                                    <tr>
                                        <th style="width: 140px;">Fecha</th>
                                        <th style="width: 130px;">Agente</th>
                                        <th>Acción / Descripción</th>
                                        <th>Notas Internas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt_l = $conn->prepare("SELECT * FROM activityfeed_customer_service WHERE gym_id = ? ORDER BY created_at DESC LIMIT 50");
                                    $stmt_l->execute([$manage_gym_id]);
                                    while($l = $stmt_l->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td style="white-space:nowrap; color: var(--text-muted); font-size: 0.82rem; font-weight: 600;">🕒 <?= date('d M y - H:i', strtotime($l['created_at'])) ?></td>
                                        <td><span class="system-badge">Agente #<?= htmlspecialchars($l['agente']) ?></span></td>
                                        <td><strong style="color: var(--text-main);"><?= htmlspecialchars($l['descripción']) ?></strong></td>
                                        <td style="font-size:0.85rem; color: var(--text-muted);"><?= htmlspecialchars($l['notas']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: NOTIFICACIONES Y TICKETS -->
                <div id="tab-notif" class="tab-content">
                    <div class="form-grid">
                        <div class="panel-card">
                            <h3>Disparar Notificación al Gym</h3>
                            <form method="POST">
                                <input type="hidden" name="gym_id" value="<?= $manage_gym_id ?>">
                                <label>Título de la Alerta</label>
                                <input type="text" name="titulo" required placeholder="Ej: Faltan documentos de verificación">
                                <label style="margin-top:15px;">Cuerpo del Mensaje</label>
                                <textarea name="mensaje" rows="4" required placeholder="Explícale al dueño qué necesita hacer..."></textarea>
                                <button type="submit" name="send_master_notif" class="btn-action btn-primary" style="margin-top:15px; width:100%; justify-content:center;">
                                    <i data-lucide="send" width="16"></i> Enviar Alerta Directa
                                </button>
                            </form>
                        </div>
                        
                        <div class="panel-card">
                            <h3>Tickets de Soporte Abiertos</h3>
                            <div style="max-height: 300px; overflow-y:auto;">
                                <?php
                                $stmt_t = $conn->prepare("SELECT * FROM tickets WHERE gym_id = ? ORDER BY id DESC");
                                $stmt_t->execute([$manage_gym_id]);
                                while($t = $stmt_t->fetch(PDO::FETCH_ASSOC)):
                                    $col = ($t['status']=='Pendiente') ? 'st-red' : 'st-green';
                                ?>
                                    <div style="border: 1px solid #e0e5f2; border-radius: 12px; padding: 15px; margin-bottom: 10px;">
                                        <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                            <strong><?= htmlspecialchars($t['titulo']) ?></strong>
                                            <span class="badge <?= $col ?>"><?= $t['status'] ?></span>
                                        </div>
                                        <p style="font-size:0.8rem; margin:0; color:#666;"><?= htmlspecialchars($t['description']) ?></p>
                                        <small style="color:#a3aed0; display:block; margin-top:10px;">WhatsApp: <?= $t['numero_whatsapp'] ?></small>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </section>
</main>
<script src="script.js" ></script>
<script>
    lucide.createIcons();

    // Lógica sencilla de pestañas para el Command Center
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        document.getElementById(tabName).style.display = "block";
        // Pequeño timeout para que la animación CSS corra
        setTimeout(() => document.getElementById(tabName).classList.add("active"), 10);
        evt.currentTarget.classList.add("active");
    }



document.addEventListener("DOMContentLoaded", ()=>{

    const rows = document.querySelectorAll("#gymTable tbody tr");

    const gymFilter = document.getElementById("filterGym");
    const userFilter = document.getElementById("filterUser");
    const approvedFilter = document.getElementById("filterApproved");
    const dataFilter = document.getElementById("filterData");

    function filtrar(){

        rows.forEach(row=>{

            const gymStatus = row.cells[2].innerText.trim();
            const userStatus = row.cells[3].innerText.trim();
            const approved = row.cells[4].innerText.trim();
            const diagnostico = row.cells[5].innerText.trim();

            let mostrar = true;

            if(gymFilter.value && gymStatus !== gymFilter.value)
                mostrar = false;

            if(userFilter.value && userStatus !== userFilter.value)
                mostrar = false;

            if(approvedFilter.value && approved !== approvedFilter.value)
                mostrar = false;

            if(dataFilter.value){

                if(dataFilter.value=="Completo"){
                    if(!diagnostico.includes("Completo"))
                        mostrar=false;
                }else{
                    if(diagnostico.includes("Completo"))
                        mostrar=false;
                }
            }

            row.style.display = mostrar ? "" : "none";

        });

    }

    gymFilter.onchange = filtrar;
    userFilter.onchange = filtrar;
    approvedFilter.onchange = filtrar;
    dataFilter.onchange = filtrar;

});
   filtrar();
</script>
</body>
</html>