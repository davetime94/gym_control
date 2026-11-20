<?php
// Importar clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Requerir archivos de PHPMailer (Ajusta la ruta si las tienes dentro de una carpeta específica)
// require 'vendor/autoload.php'; // Usa esta línea si instalaste por Composer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

session_start();
require_once __DIR__ . '/config.php';
include("conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    // Validar contraseñas
    if ($pass1 !== $pass2) {
        header("Location: app/crear_cuenta.php?error=Las contraseñas no coinciden");
        exit();
    }

    try {
        $checkEmail = $conn->prepare("SELECT id FROM users_admin WHERE email = ? or telefono = ? or nombre = ? ");
        $checkEmail->execute([$email,$telefono,$nombre]);
        if ($checkEmail->fetch()) {
            header("Location: app/crear_cuenta.php?error=Este correo electrónico ya está registrado.");
            exit();
        }

        $codigo_verificacion = sprintf("%06d", mt_rand(1, 999999));

        // Enviar Correo Electrónico con PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP (REEMPLAZA ESTOS DATOS POR LOS TUYOS)
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email, $nombre);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Código de Verificación - Gym Control';
            
            // Diseño HTML premium para el correo
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #e0e5f2; border-radius: 15px; padding: 30px; background-color: #ffffff;'>
                    <h2 style='color: #4361ee; text-align: center; margin-top: 0;'>Gym Control</h2>
                    <p style='color: #2b3674; font-size: 16px;'>Hola <strong>$nombre</strong>,</p>
                    <p style='color: #2b3674; font-size: 15px; line-height: 1.5;'>Estás a un paso de crear tu cuenta. Tu código de verificación de un solo uso es:</p>
                    <div style='background-color: #f4f7fe; padding: 20px; text-align: center; font-size: 32px; font-weight: 800; letter-spacing: 8px; border-radius: 12px; color: #4361ee; margin: 25px 0;'>
                        $codigo_verificacion
                    </div>
                    <p style='color: #a3aed0; font-size: 13px; text-align: center; margin-bottom: 0;'>Cópialo y pégalo en la ventana de registro.</p>
                </div>
            ";
            
            // Respaldo en texto plano (por si su cliente de correo bloquea el HTML)
            $mail->AltBody = "Hola $nombre, Tu código de verificación de un solo uso es: $codigo_verificacion";

            $mail->send();
            
        } catch (Exception $e) {
            // Si el correo falla, lo devolvemos con error para no dejarlo trabado en una pantalla vacía
            header("Location: app/crear_cuenta.php?error=No se pudo enviar el correo de verificación. Por favor revisa tu cuenta.");
            exit();
        }

        $_SESSION['registro_temp'] = [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'password' => $pass1, // Aún sin hashear por si necesita reenviar (se hashea al insertar)
            'codigo_seguridad' => $codigo_verificacion,
            'intentos' => 0
        ];

        // Redirigir a la vista donde pondrá el código
        header("Location: verificar.php");
        exit();

    } catch (PDOException $e) {
        header("Location: app/crear_cuenta.php?error=Error en el servidor: " . $e->getMessage());
        exit();
    }
} else {
    header("Location: app/crear_cuenta.php");
    exit();
}