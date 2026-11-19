<?php
include("conn.php");

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recibir y limpiar datos del formulario
    $gym_id = $_POST['gym_id'];
    $nombre_completo = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $status_licencia = $_POST['status_licencia'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $foto_path = trim($_POST['foto_path']);


    try {
        // 2. Verificar si el email ya existe en este gimnasio (evitar duplicados)
        $check = $conn->prepare("SELECT id FROM members WHERE email = ? AND gym_id = ?");
        $check->execute([$email, $gym_id]);

        if ($check->fetch()) {
            echo "<script>alert(' ⚠️ El correo ya está registrado en este gimnasio.')</script>";
        } else {
            // 3. Insertar el nuevo miembro
            $sql = "INSERT INTO members (
                        gym_id,
                        nombre_completo,
                        email,
                        telefono,
                        status_licencia,
                        fecha_vencimiento,
                        foto_path,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $gym_id,
                $nombre_completo,
                $email,
                $telefono,
                $status_licencia,
                $fecha_vencimiento,
                $foto_path,
            ]);
            
            $nuevo_id = $conn->lastInsertId();

            // 4. Registrar en la Bitácora (Logs)
            $log = $conn->prepare("
                INSERT INTO logs (quien, descripcion, user_id, gym_id, tipo)
                VALUES (?, ?, ?, ?, ?)
            ");

            $log->execute([
                $_SESSION['nombre'],
                "Registró nuevo miembro: $nombre_completo (ID: $nuevo_id)",
                $_SESSION['user_id'],
                $gym_id,
                "MEMBER_CREATE"
            ]);


            $mensaje = "✅ Miembro guardado con éxito (ID: $nuevo_id)";

            //Función para meter la contraseña
            $numero_al_azar=mt_rand(1200,120000);
            $stmt=$conn->prepare("update members set password =? where id=?");
            $stmt->execute([password_hash($numero_al_azar, PASSWORD_DEFAULT), $nuevo_id]);


            echo "<script>alert('password creado $numero_al_azar'); window.location.href = 'clientes.php';</script>";

            
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
            $mail->Subject = "Creación de nuevo password";
            $mail->Body = '<h1>Felicidades se ha creado un nuevo usuario tu password es : '.$numero_al_azar.'</h1>';            
            $mail->send();



        }
    } catch (PDOException $e) {
        $mensaje = "❌ Error en la base de datos: " . $e->getMessage();
    }
}

// Lógica de visualización que ya tenías
$stmt_gym = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
$stmt_gym->execute([$_SESSION['gym_id']]);
$resultado_gym = $stmt_gym->fetch(PDO::FETCH_ASSOC);

$fecha_actual = date('Y-m-d');
$fecha_30_dias = date('Y-m-d', strtotime($fecha_actual . ' + 30 days'));
?>
<br>
<div class="card"style="margin: 10px; width: fit-content;">
<h1 style="color: red;">Usuario no agregado</h1>
</div>
<br>
<?php include("error.html");  ?>
