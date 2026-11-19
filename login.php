<?php
require_once __DIR__ . '/config.php';
include("conn.php");

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje="";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Buscar usuario
    $stmt = $conn->prepare("SELECT * FROM users_admin WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Correo o contraseña incorrectos.");
    }

    // ¿La cuenta está bloqueada?
    if (!empty($user['bloqueado_hasta'])) {

        $bloqueadoHasta = strtotime($user['bloqueado_hasta']);

        if ($bloqueadoHasta > time()) {

            $minutos = ceil(($bloqueadoHasta - time()) / 60);

            die("Tu cuenta está bloqueada. Intenta nuevamente en $minutos minuto(s).");

        } else {

            // Ya terminó el bloqueo, reiniciar contador
            $stmt = $conn->prepare("
                UPDATE users_admin
                SET intentos_fallidos = 0,
                    bloqueado_hasta = NULL
                WHERE id = ?
            ");

            $stmt->execute([$user['id']]);

            // Actualizar el arreglo
            $user['intentos_fallidos'] = 0;
            $user['bloqueado_hasta'] = null;
        }
    }

    // Verificar contraseña
    if (password_verify($password, $user['password'])) {

        // Reiniciar contador
        $stmt = $conn->prepare("
            UPDATE users_admin
            SET intentos_fallidos = 0,
                bloqueado_hasta = NULL
            WHERE id = ?
        ");

        $stmt->execute([$user['id']]);

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['gym_id'] = $user['gym_id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['status'] = $user['status'];

$mail = new PHPMailer(true);

$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';

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

$mail->Subject = "Inicio de sesión detectado";
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
                Notificación de Inicio de Sesión
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

                Hemos detectado un <strong>inicio de sesión exitoso</strong>
                en tu cuenta de <strong>Gym Management</strong>.

            </p>

            <div style="
                background:#ecfdf5;
                border-left:5px solid #22c55e;
                padding:18px;
                border-radius:10px;
                margin:30px 0;
            ">

                <h3 style="margin-top:0;color:#15803d;">
                    ✔ Inicio de sesión exitoso
                </h3>

                <strong>Fecha y hora:</strong><br>
                '.date("d/m/Y H:i:s").'<br><br>

                <strong>Cuenta:</strong><br>
                '.$user['email'].'<br><br>

                <strong>Dirección IP:</strong><br>
                '.$_SERVER['REMOTE_ADDR'].'

            </div>

            <p style="font-size:16px;color:#555;line-height:28px;">

                Si reconoces esta actividad, puedes ignorar este mensaje y continuar utilizando la plataforma con normalidad.

            </p>

            <p style="font-size:16px;color:#555;line-height:28px;">

                <strong>¿No fuiste tú?</strong><br>

                Si no reconoces este inicio de sesión, cambia inmediatamente tu contraseña y comunícate con el administrador del sistema para proteger tu cuenta.

            </p>

            <div style="text-align:center;margin-top:35px;">

                <a href=""
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
                    Ir a Gym Management
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

            Este es un correo automático generado por el sistema de seguridad de
            <strong>Gym Management</strong>.

            <br><br>

            Si tienes alguna duda o detectas actividad sospechosa,
            ponte en contacto con el administrador.

            <br><br>

            © '.date("Y").' Gym Management. Todos los derechos reservados.

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


        header("Location: clientes.php");
        exit();

    } else {

        // Aumentar intentos
        $intentos = $user['intentos_fallidos'] + 1;

        if ($intentos >= 5) {

            $stmt = $conn->prepare("
                UPDATE users_admin
                SET intentos_fallidos = ?,
                    bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
                WHERE id = ?
            ");

            $stmt->execute([$intentos, $user['id']]);

            die("Has superado los 5 intentos permitidos. Tu cuenta ha sido bloqueada durante 5 minutos.");

        } else {

            $stmt = $conn->prepare("
                UPDATE users_admin
                SET intentos_fallidos = ?
                WHERE id = ?
            ");

            $stmt->execute([$intentos, $user['id']]);

            $restantes = 5 - $intentos;

        }

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>

<a href="index.php">Regresa al inicio</a>
    
</body>
</html>