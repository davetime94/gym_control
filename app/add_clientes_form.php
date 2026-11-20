

    
<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';
require_once __DIR__ . '/../funciones/matrix.php';

$fecha_actual = date('Y-m-d');
$fecha_30_dias = date('Y-m-d', strtotime($fecha_actual . ' + 30 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Cliente</title>
    <link rel="stylesheet" href="../styles/style1.css">
</head>
<body>

    <?php include("../components/navbar.php");?>

    <main>

    <section>
     <?php include("../components/sidebar.php");?>
    </section>

    <?php if($resultado_gym['status']!='Active'): ?>

        <div class="card" style="margin: 20px; "> <?php include("../components/pendiente.php"); ?> </div>
    
    <?php elseif($plan_saas_active == True ):  ?>
    <section>
    <div class="form-container">
        <div class="form-header">
            <h2><?php echo $resultado_gym['nombre_gym'] ?></h2>
            <p>Registro de nuevo miembro al sistema</p>
        </div>

        <form action="../funciones/añadir_usuario.php" method="POST">
            <div class="member-form-grid">
                
                <!-- Gym ID Selector (Oculto o Solo lectura si ya lo sabemos) -->
                <div class="input-block">
                    <label>ID del Gimnasio</label>
                    <select name="gym_id" required>
                        <option value="<?php echo $_SESSION['gym_id']; ?>"><?php echo $_SESSION['gym_id']; ?> (Actual)</option>
                    </select>
                </div>

                <!-- Nombre -->
                <div class="input-block">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre_completo" placeholder="Ej: Luis Hernandez" required>
                </div>

                <!-- Email -->
                <div class="input-block">
                    <label>Email</label>
                    <input type="email" placeholder="user@example.com" name="email">
                </div>

                <!-- Teléfono -->
                <div class="input-block">
                    <label>Teléfono</label>
                    <input type="text" placeholder="Ej: 3312345678" name="telefono">
                </div>


                <!-- Status y Tipo -->
                <div class="input-block">
                    <label>Status de Licencia</label>
                    <select name="status_licencia">
                        <option value="Activa">Activa</option>
                        <option value="Vencida" selected>Vencida</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>



                <!-- Foto y SS -->
                <div class="input-block">
                    <label>URL de Foto</label>
                    <input type="text" name="foto_path" placeholder="https://ruta-de-foto.jpg">
                </div>



                <!-- Fecha Vencimiento -->
                <div class="input-block full-row">
                    <label>Fecha de Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" value="<?php echo $fecha_30_dias; ?>">
                </div>

                <!-- Información segura -->
                <div class="full-row">
                    <div class="security-info-card">
                        <h3>🔒 Información Protegida</h3>
                        <small>Los datos están encriptados bajo protocolos de seguridad de grado bancario.</small>
                    </div>
                </div>
            </div>

            <button class="btn-save-member" type="submit">Guardar Miembro</button>
        </form>
    </div>
    </section>

    <?php elseif($plan_saas_active == False): ?>
        <div class="card" style="flex-direction: column;">
            <h2>Tu licencia ha expirado, por favor, renuevala siguiendo los pasos</h2>
            <ol class="card" style="padding: 30px; gap:10px;flex-direction: column;">
                <li class="li_personalizado">Primero ingresa al link de Mercado Pago</li><br>
                <li>Realiza tu suscripción, recuerda que la puedes cancelar cuando quieras</li><br>
                <li>Presiona el botón de Enviar request</li><br>
                <li>Listo! En unos monutos tendrás tu cuenta reactivada!</li>
            </ol>


            <a href="https://www.mercadopago.com.mx/subscriptions/checkout?preapproval_plan_id=715062c9671a42dfaf849191ff649485" target="_blank">
                <button  class="btn-search " style="width: 500px; box-shadow: 1px 1px 10px 1px rgba(0,0,0,0.4);">
                    <h1>¿Quieres renovar tu licencia?</h1>
                    <h3>Abre link de pago 🔒 </h3><br>
                    <p>Paga bimestral solo $299 mxn y obten 14 días de regalo!</p>
                </button>
            </a>
        </div>
    
    <?php endif; ?>
</main>

    <script src="../script.js"></script>
</body>
</html>