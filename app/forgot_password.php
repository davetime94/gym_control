
<?php 
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../data/conn.php';

require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$message_creating = "";
$status_creating = "";
$status_user = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);


    $stmt = $conn->prepare("
        SELECT *
        FROM users_admin
        WHERE email = ?
        AND telefono = ?
        LIMIT 1
    ");

    $stmt->execute([
        $email,
        $telefono
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $status_creating=0;
        $status_user = "Información incorrecta";

    } else {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password_temporal = "";

        for ($i = 0; $i < 12; $i++) {
            $password_temporal .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }

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
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Recuperacion de password";
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

    <!-- Encabezado -->
    <tr>
        <td style="background:#4361ee;padding:35px;text-align:center;">

            <h1 style="margin:0;color:#ffffff;font-size:30px;">
                Gym Management
            </h1>

            <p style="margin-top:10px;color:#dbe4ff;font-size:15px;">
                Recuperación de Contraseña
            </p>

        </td>
    </tr>

    <!-- Contenido -->
    <tr>
        <td style="padding:40px;">

            <h2 style="margin-top:0;color:#2b3674;">
                Hola '.$user['nombre'].',
            </h2>

            <p style="font-size:16px;color:#555;line-height:28px;">

                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.

            </p>

            <p style="font-size:16px;color:#555;line-height:28px;">

                Como medida de seguridad, se ha generado una contraseña temporal que podrás utilizar para iniciar sesión.

            </p>

            <div style="
                background:#eef2ff;
                border-left:5px solid #4361ee;
                padding:25px;
                border-radius:10px;
                margin:30px 0;
                text-align:center;
            ">

                <p style="margin:0;color:#2b3674;font-size:15px;">
                    Tu contraseña temporal es:
                </p>

                <h1 style="
                    margin:20px 0 10px;
                    color:#4361ee;
                    font-size:34px;
                    letter-spacing:3px;
                ">
                    '.$password_temporal.'
                </h1>

            </div>

            <p style="font-size:16px;color:#555;line-height:28px;">

                <strong>Importante:</strong>

                Después de iniciar sesión, cambia esta contraseña por una nueva que sea segura y fácil de recordar únicamente para ti.

            </p>

            <div style="
                background:#fff8e6;
                border-left:5px solid #f59e0b;
                padding:18px;
                border-radius:10px;
                margin-top:30px;
            ">

                <strong style="color:#b45309;">
                    Recomendaciones de seguridad
                </strong>

                <ul style="color:#555;line-height:28px;padding-left:20px;margin-bottom:0;">

                    <li>No compartas esta contraseña con nadie.</li>

                    <li>Utiliza una contraseña diferente a las que usas en otros servicios.</li>

                    <li>Si no solicitaste este cambio, comunícate inmediatamente con el administrador.</li>

                </ul>

            </div>

            <div style="text-align:center;margin-top:40px;">

                <a href="https://tusitio.com"
                style="
                    display:inline-block;
                    background:#4361ee;
                    color:#ffffff;
                    text-decoration:none;
                    padding:15px 35px;
                    border-radius:10px;
                    font-weight:bold;
                    font-size:15px;
                ">
                    Iniciar sesión
                </a>

            </div>

        </td>
    </tr>

    <!-- Pie -->
    <tr>
        <td style="
            background:#f8fafc;
            padding:25px;
            text-align:center;
            color:#999999;
            font-size:13px;
        ">

            Este es un correo automático enviado por
            <strong>Gym Management</strong>.

            <br><br>

            Si no solicitaste la recuperación de tu contraseña,
            ponte en contacto con el administrador lo antes posible.

            <br><br>

            © '.date("Y").' Gym Management. Todos los derechos reservados.

        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>';
            $mail->send();
            $password_hash = password_hash(
                $password_temporal,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare("
                UPDATE users_admin
                SET password = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $password_hash,
                $user['id']
            ]);
            $status_creating = 1;
            $message_creating = "Correo enviado correctamente";
        } catch(Exception $e) {
            $status_creating = 0;
            $message_creating = "Error al enviar correo";
        }
    }
}



?>
<!DOCTYPE html>
<html lang="es">



<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Recuperar contraseña</title>

    <link rel="stylesheet" href="../styles/style1.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

<main style="display:flex;justify-content:center;align-items:center;height:100vh;">

    <div class="login-container">

        <div class="login-box">

            <div class="login-header">

                <div class="logo">
                    Gym <span>Management</span>
                </div>

                <h1>Recuperar contraseña</h1>

                <p>
                    Ingresa el correo y teléfono registrados para generar una contraseña temporal.
                </p>

            </div>

            <?php if(!empty($status_conexion)){ ?>
                <div class="badge <?= $status_conexion == 'Conexión establecida con éxito' ? 'success' : 'danger'; ?>" style="margin-bottom:15px;">
                    <?= $status_conexion ?>
                </div>
            <?php } ?>



            <form method="POST">

                <div class="input-group">
                    <label>Correo electrónico</label>

                    <input
                        type="email"
                        name="email"
                        required
                        placeholder="ejemplo@correo.com">
                </div>

                <div class="input-group">
                    <label>Número telefónico</label>

                    <input
                        type="number"
                        name="telefono"
                        required
                        placeholder="3312345678">
                </div>

                <button type="submit" class="btn-login">
                    Enviar contraseña temporal
                </button>

            </form>

            <div class="login-footer">

                <a href="index.php">
                    ← Regresar al inicio de sesión
                </a>

            </div>

        </div>

    </div>

</main>

</body>
</html>