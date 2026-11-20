<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';
require_once __DIR__ . '/../funciones/matrix.php';
$gym_id_sesion = $_SESSION['gym_id'];


$mensaje_cliente = "";
$error_cliente = "";

// --- LÓGICA DE PROCESAMIENTO: ACTUALIZAR CONFIGURACIÓN DEL GYM (PDO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_gym'])) {
        $nombre_gym         = trim($_POST['nombre_gym']);
        $rfc_o_tax_id       = trim($_POST['rfc_o_tax_id']);
        $tax_status         = $_POST['tax_status'];
        $domicilio_completo = trim($_POST['domicilio_completo']);
        $telefono_contacto  = trim($_POST['telefono_contacto']);
        $email_corporativo  = trim($_POST['email_corporativo']);
        $representante_legal= trim($_POST['representante_legal']);
        $notas_regulacion   = trim($_POST['notas_regulacion']);
        
        // Regla de Negocio Crítica: Pasar status a Pending tras cualquier cambio

        

        if (!empty($nombre_gym)) {
            try {
                $sql_update = "UPDATE gyms SET 
                                nombre_gym = ?, 
                                rfc_o_tax_id = ?, 
                                tax_status = ?, 
                                domicilio_completo = ?, 
                                telefono_contacto = ?, 
                                email_corporativo = ?, 
                                representante_legal = ?, 
                                notas_regulacion = ? 
                            WHERE id = ?";
                
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([
                    $nombre_gym, 
                    $rfc_o_tax_id, 
                    $tax_status, 
                    $domicilio_completo, 
                    $telefono_contacto, 
                    $email_corporativo, 
                    $representante_legal, 
                    $notas_regulacion, 
                    $gym_id_sesion
                ]);

    $stmt_log = $conn->prepare("
        INSERT INTO activityfeed_customer_service
        (agente, gym_id, descripción, notas)
        VALUES (?,?,?,?)
    ");

    $stmt_log->execute([
        $_SESSION['user_id'],
        $_SESSION['gym_id'],
        'UPDATE_GYM_PROFILE',
        'Nombre Gym: ' . $_POST['nombre_gym'] .
        ' | RFC: ' . $_POST['rfc_o_tax_id'] .
        ' | Régimen Fiscal: ' . $_POST['tax_status'] .
        ' | Domicilio: ' . $_POST['domicilio_completo'] .
        ' | Teléfono: ' . $_POST['telefono_contacto'] .
        ' | Email Corporativo: ' . $_POST['email_corporativo'] .
        ' | Representante Legal: ' . $_POST['representante_legal'] .
        ' | Notas Regulación: ' . $_POST['notas_regulacion']
    ]);


                $mensaje_cliente = "Información actualizada.";
            } catch (PDOException $e) {
                $error_cliente = "Error en la base de datos: " . $e->getMessage();
            }
        } else {
            $error_cliente = "El nombre del gimnasio no puede quedar vacío.";
        }
}

    $titulo = "Actualización de información";
    $descripcion = "Cambio de información realizado, se requiere KYB o KYC";
    $whatsapp = $_SESSION['email'];
    $status="Pendiente";
    $gym_id = $_SESSION['gym_id'];
    $notas="";


    try {
        $stmt = $conn->prepare("
            INSERT INTO tickets (
            titulo, 
            description,
            status,
            gym_id,
            numero_whatsapp,
            notas) 
            VALUES 
            (?, ?, ?,?,?,?)
        ");
        $stmt->execute([$titulo, $descripcion, $status, $gym_id,  $whatsapp,  $notas]);
        
        $mensaje = "✅ Ticket creado correctamente. Nuestro equipo lo revisará pronto.";
        // Recargamos para bloquear el formulario
        $puedeCrear = false; 
    } catch (PDOException $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }




// --- CONSULTA DE INFORMACIoN ACTUAL DEL GYM (PDO) ---
try {
    $stmt_select = $conn->prepare("SELECT * FROM gyms WHERE id = ? LIMIT 1");
    $stmt_select->execute([$gym_id_sesion]);
    $gym_data = $stmt_select->fetch(PDO::FETCH_ASSOC);

    if (!$gym_data) {
        die("Error crítico: No se encontró la información de este gimnasio en el sistema.");
    }
} catch (PDOException $e) {
    die("Error de conexión o consulta: " . $e->getMessage());
}



// Protección de sesión (Utilizando tus variables nativas)
$stmt_status_data=$conn->prepare("SELECT * FROM status_informacion_gym where gym_id = ?");
$stmt_status_data->execute([$_SESSION['gym_id']]);
$datastatus_result=$stmt_status_data->fetch(PDO::FETCH_ASSOC);





?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../recursos/icon.png" type="image/x-icon">
    <title>Configuración de mi GYM | GymControl</title>
    <link rel="stylesheet" href="../styles/style1.css">
    <link rel="stylesheet" href="../styles/tables_style.css">

    
</head>
<style>
    :root {
        --primary: #4361ee;
        --success: #05cd99;
        --danger: #ee5d50;
        --warning: #ff8c00;
        --text-main: #2b3674;
        --text-muted: #a3aed0;
        --bg-card: #ffffff;
    }

    body { background-color: #f4f7fe; font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; }
    main { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
    .content { padding: 40px; }

    .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .header-flex h2 { font-weight: 800; font-size: 1.8rem; margin: 0; }

    .secciones-container { display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 30px; align-items: start; }

    .create-card { 
        background: var(--bg-card); 
        border-radius: 20px; 
        padding: 25px; 
        box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08); 
        margin-bottom: 30px;
    }
    .create-card h3 { margin-top: 0; font-size: 1.1rem; margin-bottom: 20px; color: var(--text-main); font-weight: 800; }
    
    .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f4f7fe; font-size: 0.9rem; }
    .info-row strong { color: var(--text-main); }
    .info-row span { color: #444; max-width: 60%; text-align: right; }

    input, select, textarea {
        padding: 12px 16px;
        border-radius: 14px;
        border: 1px solid #e0e5f2;
        background: #f4f7fe;
        color: var(--text-main);
        font-size: 0.9rem;
        outline: none;
        transition: 0.3s;
        width: 100%;
        box-sizing: border-box;
        margin-top: 5px;
        margin-bottom: 15px;
        font-family: inherit;
    }
    input:focus, select:focus, textarea:focus { border-color: var(--primary); background: #fff; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.1); }

    .btn { padding: 12px 20px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 0.85rem; width: 100%; }
    .btn-create { background: var(--primary); color: white; }
    .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

    .status-badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .status-active { background-color: rgba(5, 205, 153, 0.15); color: var(--success); }
    .status-pending { background-color: rgba(255, 140, 0, 0.15); color: var(--warning); }

    @media(prefers-color-scheme: dark){
        body { background-color: #0f172a; }
        .create-card { background: #1e293b; color: white; border-color: #2a3550; }
        .info-row { border-bottom-color: #2a3550; justify-content: center;align-items: center;}
        .info-row strong { color: white; }
        .info-row span { color: #cbd5e0; }
        input, select, textarea { background: #0f172a; border-color: #2a3550; color: white; }
        .create-card h3 { color: white; }
    }
</style>
<body>

<?php include("../components/navbar.php"); ?>

<main>

<section>
    <?php include("../components/sidebar.php"); ?>
</section>

<!-- BLOQUEO EN CASO DE RECHAZADO TOTAL (DESDE MATRIX)-->

<section class="content">
    <div class="header-flex">
        <h2>Panel de Regulación Comercial</h2>
        <div>
            <!-- Badge de Estado Dinámico -->
            <?php if (strtolower($gym_data['status']) == 'active'): ?>
                <span class="status-badge status-active">✓ Activo / Verificado</span>
            <?php else: ?>
                <span class="status-badge status-pending">⏳ Pendiente de Auditoría</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cajas de respuesta estética -->
    <?php if($mensaje_cliente): ?>
        <div class="success badge " >
            <?= $mensaje_cliente ?>
        </div><br>
    <?php endif; ?>
    <?php if($error_cliente): ?>
        <div style="padding: 12px; background: rgba(238, 93, 80, 0.15); color: #ee5d50; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem;">
            ⚠️ <?= $error_cliente ?>
        </div>
    <?php endif; ?>


    <?php if($gym_data['status']!='Active'): ?>
    <div class="card" style="flex-direction: row; gap: 50px;">
 

        <div>
            <h1>Ingresa la información de tu gimasio</h1><br>
            <p>En este paso estamos haciendo la verificaión de tu gimasio, datos cómo la presencia del gym y tu información de contacto están siendo revisadas en este momento.</p><br>
            <p>Te avisaremos cuando tengamos el resultado, este proceso toma aproximadamente de 24 a 72 horas hábiles. </p>
        </div>

       <div>
            <img src="../recursos/Editing body text-amico.png" style="height: 250px;" alt="">
        </div>
    </div>
    <?php endif; ?>

    <div class="secciones-container">
        
        <!-- ================= SECCIÓN 1: PANEL DE INFORMACIÓN ACTUAL ================= -->
        <div class="create-card">
            <h3>Información Actual de tu GYM</h3>
            
            <div class="info-row">
                <strong>ID de Registro:</strong>
                <span>#<?= $gym_data['id'] ?></span>
            </div>
            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>RFC / Tax ID:</strong>
                </div>
                <span><?= htmlspecialchars($gym_data['rfc_o_tax_id'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Plan del SaaS:</strong>
                </div>
                <span style="font-weight: bold; color: var(--primary);"><?= htmlspecialchars($gym_data['plan_saas']) ?></span>
            </div>

            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Régimen Fiscal:</strong>
                </div>
                <span><?= htmlspecialchars($gym_data['tax_status'] ?? 'No especificado') ?></span>
            </div>

            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Domicilio Completo:</strong>
                </div>
                <span><?= htmlspecialchars($gym_data['domicilio_completo'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Teléfono de Contacto:</strong>
                </div>
                <span><?= htmlspecialchars($gym_data['telefono_contacto'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Email Corporativo:</strong>
                </div>
                <span><?= htmlspecialchars($gym_data['email_corporativo'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Representante Legal:</strong>
                </div>
                <span><?= htmlspecialchars($gym_data['representante_legal'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-row">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Última Auditoría Interna:</strong>
                </div>
                <span><?= $gym_data['ultima_auditoria'] !== '0000-00-00' ? date('d/m/Y', strtotime($gym_data['ultima_auditoria'])) : 'Sin auditar' ?></span>
            </div>

            <div class="info-row" style="border: none;">
                <div style="flex-direction: column; display:flex; gap:4px;">
                    <strong>Fecha de Alta del SaaS:</strong>
                </div>
                <span><?= date('d M, Y H:i A', strtotime($gym_data['created_at'])) ?></span>
            </div>
        </div>

        <!-- ================= SECCIÓN 2: FORMULARIO DE EDICIÓN MODULAR ================= -->
        <?php if($_SESSION['rol']=='Manager'): ?>
            <div class="create-card">
                <h3>Editar Configuración del Negocio</h3>
                <form method="POST">
                    
                    <label style="font-weight: 700; font-size: 0.85rem;">Nombre Comercial del Gimnasio</label>
                    <input type="text" name="nombre_gym" value="<?= htmlspecialchars($gym_data['nombre_gym']) ?>" required placeholder="Ej: SuperHeroes Gym">

                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 700; font-size: 0.85rem;">RFC / Tax ID</label>
                            <input type="text" name="rfc_o_tax_id" value="<?= htmlspecialchars($gym_data['rfc_o_tax_id'] ?? '') ?>" placeholder="Ej: XAX135IO">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 700; font-size: 0.85rem;">Régimen Fiscal</label>
                            <select name="tax_status">
                                <option value="Moral" <?= ($gym_data['tax_status'] == 'Moral') ? 'selected' : '' ?>>Persona Moral</option>
                                <option value="Fisica" <?= ($gym_data['tax_status'] == 'Fisica') ? 'selected' : '' ?>>Persona Física</option>
                            </select>
                        </div>
                    </div>

                    <label style="font-weight: 700; font-size: 0.85rem;">Domicilio Completo</label>
                    <input type="text" name="domicilio_completo" value="<?= htmlspecialchars($gym_data['domicilio_completo'] ?? '') ?>" placeholder="Dirección, Colonia, Código Postal y Municipio">

                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 700; font-size: 0.85rem;">Teléfono de Contacto</label>
                            <input type="text" name="telefono_contacto" value="<?= htmlspecialchars($gym_data['telefono_contacto'] ?? '') ?>" placeholder="Ej: 3319059794">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-weight: 700; font-size: 0.85rem;">Email Corporativo</label>
                            <input type="email" name="email_corporativo" value="<?= htmlspecialchars($gym_data['email_corporativo'] ?? '') ?>" placeholder="Ej: contacto@gym.com">
                        </div>
                    </div>

                    <label style="font-weight: 700; font-size: 0.85rem;">Representante Legal</label>
                    <input type="text" name="representante_legal" value="<?= htmlspecialchars($gym_data['representante_legal'] ?? '') ?>" placeholder="Nombre completo del apoderado">

                    <label style="font-weight: 700; font-size: 0.85rem;">Notas de Regulación / Observaciones Internas</label>
                    <textarea name="notas_regulacion" rows="3" placeholder="Añadir notas complementarias sobre licencias o uso de suelo..."><?= htmlspecialchars($gym_data['notas_regulacion'] ?? '') ?></textarea>

                    <button type="submit" name="actualizar_gym" class="btn btn-create" style="margin-top: 10px;">
                        💾 Guardar Cambios y Solicitar Verificación
                    </button>
                </form>
            </div>
        <?php else:  ?>
        <?php endif;  ?>
    </div>
</section>

</main>

<script src="script.js"></script>
</body>
</html>