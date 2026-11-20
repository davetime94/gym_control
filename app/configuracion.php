<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';
require_once __DIR__ . '/../funciones/matrix.php';

require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje="";
$mensaje_p="";
if($_SERVER['REQUEST_METHOD']=='POST'){

    if(isset($_POST['update_account'])){
        $telefono = trim($_POST['telefono']);
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $id = $user_data['id'];

        $stmt=$conn->prepare("UPDATE users_admin SET telefono = ?, email = ?, nombre = ?  where id = ?");
        $stmt->execute([$telefono, $email, $nombre, $id]);
        
        if($stmt->rowCount() > 0){
        $mensaje = "Perfil actualizado correctamente.";
        }else{
            $mensaje = "";
        }
        }


    if(isset($_POST['update_password'])){

        $current_password=$_POST['current_password'];
        $new_password_1=$_POST['new_password_1'];
        $new_password_2=$_POST['new_password_2'];

        //Sacamos la contraseña
        $stmt_p=$conn->prepare("SELECT* from users_admin where id = ?");
        $stmt_p->execute([$user_data['id']]);
        $r_p=$stmt_p->fetch(PDO::FETCH_ASSOC);

        //Validar si la contraseña es la misma 
        if(password_verify($current_password, $r_p['password'])){

    


            //validamos las dos claves
            if($new_password_1==$new_password_2){
                $new_passwordhash=password_hash($new_password_1, PASSWORD_DEFAULT);

                //checar que no se la misma 
                



                $stmt=$conn->prepare("update users_admin set password = ? where id = ?");
                $stmt->execute([$new_passwordhash, $user_data['id']]);
                $mensaje_p="Contraseña actualizada";

                 $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(
                SMTP_FROM_EMAIL,
                SMTP_FROM_NAME
            );
            $mail->addAddress($user_data['email']);
            $mail->isHTML(true);
            $mail->Subject = "Cambio de password!";
            $mail->Body = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
            <meta charset="UTF-8">
            </head>

            <body style="margin:0;padding:0;background:#f4f7fe;font-family:Arial,Helvetica,sans-serif;">

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fe;padding:40px 0;">
            <tr>
            <td align="center">

            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.08);">

                <tr>
                    <td style="background:#4361ee;padding:30px;text-align:center;">
                        <h1 style="color:#ffffff;margin:0;font-size:30px;">
                            Gym Management
                        </h1>

                        <p style="color:#dbe4ff;margin-top:10px;font-size:15px;">
                            Notificación de Seguridad
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:40px;">

                        <h2 style="color:#2b3674;margin-top:0;">
                            Hola '.$user_data['nombre'].',
                        </h2>

                        <p style="color:#555;font-size:16px;line-height:28px;">

                            Queremos informarte que la contraseña de tu cuenta fue
                            <strong>cambiada correctamente</strong>.

                        </p>

                        <div style="
                            background:#eef2ff;
                            border-left:5px solid #4361ee;
                            padding:18px;
                            border-radius:10px;
                            margin:30px 0;
                        ">

                            <strong style="color:#2b3674;">
                                Fecha:
                            </strong>

                            '.date("d/m/Y H:i").'

                            <br><br>

                            <strong style="color:#2b3674;">
                                Cuenta:
                            </strong>

                            '.$user_data['email'].'

                        </div>

                        <p style="color:#555;font-size:16px;line-height:28px;">

                            Si tú realizaste este cambio, no necesitas hacer nada más.

                        </p>

                        <p style="color:#555;font-size:16px;line-height:28px;">

                            <strong>¿No reconoces esta acción?</strong>

                            Cambia inmediatamente tu contraseña y comunícate con el
                            administrador del sistema para proteger tu cuenta.

                        </p>

                        <div style="text-align:center;margin-top:35px;">

                            <a href=""
                            style="
                                background:#4361ee;
                                color:#ffffff;
                                text-decoration:none;
                                padding:15px 35px;
                                border-radius:10px;
                                display:inline-block;
                                font-weight:bold;
                                font-size:15px;
                            ">
                            </a>

                        </div>

                    </td>
                </tr>

                <tr>
                    <td style="
                        background:#f8f9fb;
                        padding:25px;
                        text-align:center;
                        color:#999;
                        font-size:13px;
                    ">

                        Este es un correo automático.<br>
                        Por favor, no respondas a este mensaje.

                        <br><br>

                        © '.date("Y").' Gym Management.<br>
                        Todos los derechos reservados.

                    </td>
                </tr>

            </table>

            </td>
            </tr>
            </table>

            </body>
            </html>
            ';
            $mail->send();
           
        } catch(Exception $e) {
            $status_creating = 0;
            $message_creating = "Error al enviar correo";
        }

            }else{
                $mensaje_p="La nueva contraseña debe coincidir";
            }


        }else{
            $badge_class="Inactive";
            $mensaje_p="La contraseña ingresada es incorrecta";
        }


        }


}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones</title>
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

    .status-badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .status-active { background-color: rgba(5, 205, 153, 0.15); color: var(--success); }
    .status-pending { background-color: rgba(255, 140, 0, 0.15); color: var(--warning); }

    @media(prefers-color-scheme: dark){
        body { background-color: #0f172a; }
        .create-card { background: #1e293b; color: white; border-color: #2a3550; }
        .info-row { border-bottom-color: #2a3550; }
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

<!-- ACTUALIZAR INFOR BASICA -->
    <section class="content" style="flex-direction: row; display:flex; gap:10px; ">
            <div class="card create-card" style="flex-direction:column; width: fit-content; height:fit-content;"><br>
                <h2>Mi Perfil de Administrador</h2>

                <form method="POST"  >
                    <input type="hidden" name="action" value="update_profile">

                   <br> <div ><span class="system-badge"  >ID: <?php echo htmlspecialchars($user_data['id']) ;?></span></div><br>


                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($user_data['nombre']); ?>" placeholder="Tu nombre">
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" placeholder="correo@ejemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Télefono del propietario</label>
                        <input type="number" name="telefono" value="<?php echo htmlspecialchars($user_data['telefono']); ?>" maxlength="12">
                    </div>
                    <button type="submit" name="update_account"  class="btn-search">Guardar Perfil</button>
                </form><br>

                <span ><?php echo $mensaje; ?></span>
            </div>

<!-- ACTUALIZAR CONTRASEÑA -->

  <div class="card create-card" style="flex-direction:column; width: fit-content; height:fit-content;"><br>
                <h2>Actualizar contraseña</h2>

                <form method="POST"  >
                    <input type="hidden" name="action" value="update_profile">

                   <br> <div ><span class="system-badge"  >ID: <?php echo htmlspecialchars($user_data['id']) ;?></span></div><br>


                    <div class="form-group">
                        <label>Contraseña actual</label>
                        <input type="password" name="current_password"  required>
                    </div>

                    <div class="form-group">
                        <label>Contraseña actual</label>
                        <input type="password" name="new_password_1"  required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña actual</label>
                        <input type="password" name="new_password_2"  required>
                    </div>

                    <button type="submit" name="update_password"  class="btn-search">Guardar Perfil</button>
                </form><br>

                <span  class="badge <?= ($mensaje_p=="Contraseña actualizada"?'success':'warning') ?>"><?php echo $mensaje_p;  ?></span>
            </div>

    
    <div class="secciones-container card" style="height:fit-content;">
        
        <!-- ================= SECCIÓN 1: PANEL DE INFORMACIÓN ACTUAL ================= -->
        <div class="create-card">
            <h3>Información Actual de tu GYM</h3>
            
            <div class="info-row">
                <strong>ID de Registro:</strong>
                <span>#<?= $user_data['id'] ?></span>
            </div>
            <div class="info-row">
                <strong>GYM a posesión:</strong>
                <span><?= htmlspecialchars($user_data['gym_id']) ?></span>
            </div>
            <div class="info-row">
                <strong>Status:</strong>
                <span class="badge <?= ($user_data['status']=='1'?'success':'warning') ?>"><?php if($user_data['status']=='1'){echo "Activo";}else{echo "Otro";} ?></span>
            </div>


            <div class="info-row">
                <strong>Creado el:</strong>
                <span><?= htmlspecialchars($user_data['created_at']) ?></span>
            </div>
            <div class="info-row">
                <strong>Rol:</strong>
                <span><?= htmlspecialchars($user_data['rol']) ?></span>
            </div>


            <div class="info-row">
                <strong>Aprobado:</strong>
                <span class="badge <?= ($user_data['approved']=='Aprobado'?'success':'warning') ?>" > <?php echo htmlspecialchars($user_data['approved']) ;?></span>
            </div>
        </div>
    </div>


    </section>

</main>
    
</body>
<script src="../script.js" > 
    
</script>
</html>