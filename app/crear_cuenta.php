<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../data/conn.php';

// Importar clases de PHPMailer
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Si el usuario ya está logueado, lo mandamos a su panel
if (isset($_SESSION['id']) || isset($_SESSION['user_id'])) {
    header("Location: clientes.php");
    exit();
}

$error_mensaje = "";
$exito_mensaje = "";

// =========================================================================================
// LÓGICA DE PROCESAMIENTO (Maneja tanto el registro inicial como la verificación del PIN)
// =========================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ---------------------------------------------------------
    // ACCIÓN 1: PROCESAR EL PIN DE VERIFICACIÓN (Paso 2)
    // ---------------------------------------------------------
    if (isset($_POST['verificar_pin']) && isset($_SESSION['registro_temp'])) {
        $codigo_ingresado = trim($_POST['codigo_auth']);
        $datos_temporales = $_SESSION['registro_temp'];

        if ($codigo_ingresado === $datos_temporales['codigo_seguridad']) {
            // CÓDIGO CORRECTO: PROCEDEMOS A INSERTAR EN LA BASE DE DATOS
            try {
                $conn->beginTransaction();

                // 1. CREAR EL GIMNASIO PRIMERO
                $nombre_gym_defecto = "Gym de " . $datos_temporales['nombre'];
                $stmtGym = $conn->prepare("INSERT INTO gyms (nombre_gym, plan_saas, telefono_contacto) VALUES (?, ?, ?)");
                $stmtGym->execute([$nombre_gym_defecto, 'Free', $datos_temporales['telefono']]);
                $nuevo_gym_id = $conn->lastInsertId();

                // 2. CREAR EL USUARIO ADMINISTRADOR ASOCIADO
                $pass_hash = password_hash($datos_temporales['password'], PASSWORD_DEFAULT);
                $sqlUser = "INSERT INTO users_admin (gym_id, nombre, email, password, rol, status) VALUES (?, ?, ?, ?, ?, ?)";
                $stmtUser = $conn->prepare($sqlUser);
                
                // Asignamos el rol de Manager
                $stmtUser->execute([
                    $nuevo_gym_id,
                    $datos_temporales['nombre'],
                    $datos_temporales['email'],
                    $pass_hash,
                    'Manager',
                    1
                ]);

                $conn->commit();

                // ------------------------------------------------------------------
                // ENVIAR CORREO DE BIENVENIDA (Envuelto en su propio try/catch)
                // ------------------------------------------------------------------
                try {
                    $mailBienvenida = new PHPMailer(true);

                    $mailBienvenida->CharSet = 'UTF-8';
                    $mailBienvenida->Encoding = 'base64';

                    $mailBienvenida->isSMTP();
                    $mailBienvenida->Host = SMTP_HOST;
                    $mailBienvenida->SMTPAuth = true;
                    $mailBienvenida->Username = SMTP_USER;
                    $mailBienvenida->Password = SMTP_PASS;
                    $mailBienvenida->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mailBienvenida->Port = SMTP_PORT;

                    $mailBienvenida->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mailBienvenida->addAddress($datos_temporales['email']);
                    
                    $mailBienvenida->isHTML(true);
                    $mailBienvenida->Subject = "¡Bienvenido! Tu cuenta ha sido creada"; // Asunto corregido
                    
                    $mailBienvenida->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #e0e5f2; border-radius: 15px; padding: 30px; background-color: #ffffff; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
                            <h2 style='color: #4361ee; text-align: center; margin-top: 0; font-size: 26px; letter-spacing: -0.5px;'>Gym Control</h2>
                            <div style='text-align: center; margin: 25px 0;'>
                                <div style='background-color: #e0fcf1; color: #05cd99; width: 70px; height: 70px; line-height: 70px; border-radius: 50%; font-size: 35px; display: inline-block;'>
                                    🎉
                                </div>
                            </div>
                            <h3 style='color: #2b3674; text-align: center; font-size: 22px; margin-bottom: 15px;'>¡Felicidades, " . htmlspecialchars($datos_temporales['nombre']) . "!</h3>
                            <p style='color: #2b3674; font-size: 15px; line-height: 1.6; text-align: center; margin-bottom: 25px;'>
                                Tu cuenta y el perfil de tu gimnasio han sido creados exitosamente. Estamos muy emocionados de tenerte con nosotros y listos para ayudarte a llevar tu gestión al siguiente nivel.
                            </p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='https://tudominio.com/index.php' style='background-color: #4361ee; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; display: inline-block;'>
                                    Ir a mi Dashboard
                                </a>
                            </div>
                            <p style='color: #a3aed0; font-size: 12px; text-align: center; margin-bottom: 0; border-top: 1px solid #e0e5f2; padding-top: 20px; line-height: 1.5;'>
                                ¿Necesitas ayuda para arrancar? Responde a este correo y nuestro equipo de soporte te guiará en tus primeros pasos. <br><br>
                                © " . date('Y') . " Gym Control
                            </p>
                        </div>
                    ";

                    // ¡Esta es la línea que faltaba!
                    $mailBienvenida->send();

                } catch (Exception $e) {
                    // Si el correo de bienvenida falla (ej. error de servidor), no hacemos nada.
                    // El usuario YA fue creado en la base de datos, así que debe seguir su camino al dashboard.
                    error_log("Error enviando correo de bienvenida a: " . $datos_temporales['email']);
                }

                // Limpiamos la sesión temporal
                unset($_SESSION['registro_temp']);

                // REDIRECCIÓN A ÉXITO
                header("Location: index.php");
                exit();


            } catch (PDOException $e) {
                $conn->rollBack();
                $error_mensaje = "Error al crear la cuenta: " . $e->getMessage();
            }
        } else {
            // CÓDIGO INCORRECTO
            $_SESSION['registro_temp']['intentos'] += 1;
            
            if ($_SESSION['registro_temp']['intentos'] >= 3) {
                unset($_SESSION['registro_temp']); // Destruir la sesión si falla mucho
                $error_mensaje = "Demasiados intentos fallidos. Por seguridad, debes registrarte de nuevo.";
            } else {
                $error_mensaje = "Código incorrecto. Intento " . $_SESSION['registro_temp']['intentos'] . " de 3.";
            }
        }
    }

    // ---------------------------------------------------------
    // ACCIÓN 2: PROCESAR EL FORMULARIO DE REGISTRO INICIAL (Paso 1)
    // ---------------------------------------------------------
    elseif (isset($_POST['registrar_usuario'])) {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $pass1 = $_POST['pass1'];
        $pass2 = $_POST['pass2'];

        if ($pass1 !== $pass2) {
            $error_mensaje = "Las contraseñas no coinciden.";
        } else {
            try {
                // VALIDACIÓN MEJORADA: No crear cuenta si nombre, email o teléfono ya existen.
                // Asegurándonos de pasar los 3 parámetros correctamente al execute()
                $checkUser = $conn->prepare("SELECT id FROM users_admin WHERE email = ? OR telefono = ? OR nombre = ?");
                $checkUser->execute([$email, $telefono, $nombre]);
                
                if ($checkUser->fetch()) {
                    $error_mensaje = "Los datos ingresados (Nombre, Correo o Teléfono) ya están vinculados a una cuenta existente.";
                } else {
                    // Generar código
                    $codigo_verificacion = sprintf("%06d", mt_rand(1, 999999));

                    // Enviar Correo con PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        // REEMPLAZA ESTOS DATOS POR LOS DE TU SERVIDOR SMTP
                        $mail->isSMTP();
                        $mail->Host       = SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = SMTP_USER;
                        $mail->Password   = SMTP_PASS;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = SMTP_PORT;

                        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                        $mail->addAddress($email, $nombre);

                        $mail->isHTML(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->Subject = 'Código de Verificación - Gym Control';
                        
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
                        
                        $mail->AltBody = "Hola $nombre, Tu código de verificación de un solo uso es: $codigo_verificacion";
                        $mail->send();

                        // Guardar en sesión temporal y recargar página para mostrar el paso 2
                        $_SESSION['registro_temp'] = [
                            'nombre' => $nombre,
                            'email' => $email,
                            'telefono' => $telefono,
                            'password' => $pass1,
                            'codigo_seguridad' => $codigo_verificacion,
                            'intentos' => 0
                        ];

                        header("Location: crear_cuenta.php");
                        exit();
                        
                    } catch (Exception $e) {
                        $error_mensaje = "No se pudo enviar el correo de verificación. Por favor verifica que el correo sea válido.";
                    }
                }
            } catch (PDOException $e) {
                $error_mensaje = "Error de conexión: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | Gym Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --white: #ffffff;
            --text-muted: #a3aed0;
            --danger: #ee5d50;
            --bg-gradient: linear-gradient(135deg, #0b1437 0%, #111c44 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            height: 100vh; width: 100%;
            background: var(--bg-gradient);
            display: flex; justify-content: center; align-items: center;
            position: relative; overflow: hidden;
        }

        body::before {
            content: ""; position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('recursos/pexels-fire-flintq8-1049543416-20418606.jpg');
            background-position: center; background-size: cover;
            opacity: 0.15; z-index: -1;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px) saturate(160%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%; max-width: 500px; padding: 40px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.6s ease-out;
        }
        
        .verify-mode { max-width: 420px; text-align: center; } /* Contenedor más estrecho para el paso 2 */

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .header { text-align: center; margin-bottom: 30px; }
        .logo-text { font-size: 1.5rem; font-weight: 800; color: var(--white); letter-spacing: -1px; }
        .logo-text span { color: var(--primary); }
        h2 { color: var(--white); font-size: 1.6rem; margin-top: 10px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        .input-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 15px; }
        
        label { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; margin-left: 5px; }
        input {
            width: 100%; padding: 12px 15px; border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05); color: var(--white);
            font-size: 0.9rem; outline: none; transition: 0.3s;
        }
        input:focus { border-color: var(--primary); background: rgba(255, 255, 255, 0.1); box-shadow: 0 0 15px rgba(67, 97, 238, 0.2); }

        /* Estilo especial para el input del PIN */
        input.pin-input {
            padding: 15px; border-radius: 15px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            text-align: center; letter-spacing: 5px; font-weight: 800; font-size: 1.5rem;
        }
        input.pin-input:focus { border-style: solid; }

        .btn-action {
            background: var(--primary); color: white; border: none;
            padding: 15px; border-radius: 15px; font-weight: 700;
            font-size: 1rem; cursor: pointer; transition: 0.3s;
            margin-top: 10px; width: 100%;
        }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); filter: brightness(1.1); }

        .footer { margin-top: 25px; text-align: center; }
        a { text-decoration: none; color: var(--primary); font-weight: 600; font-size: 0.85rem; }
        .terms { font-size: 0.7rem; color: var(--text-muted); margin-top: 15px; line-height: 1.4; text-align: center; }
        
        .alert { background: rgba(238,93,80,0.15); color: var(--danger); padding: 12px; border-radius: 10px; border: 1px solid rgba(238,93,80,0.3); font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; text-align: center; }
        
        /* Ocultar elementos lógicos */
        .hidden { display: none; }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['registro_temp'])): ?>
        
        <!-- ========================================================================= -->
        <!-- PASO 2: PANTALLA DE VERIFICACIÓN DE PIN -->
        <!-- ========================================================================= -->
        <div class="auth-container verify-mode">
            <div style="font-size: 50px; margin-bottom: 10px;">✉️</div>
            <h2>Verifica tu Correo</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 30px; line-height: 1.5;">
                Hemos enviado un código de 6 dígitos a<br>
                <strong style="color:var(--white);"><?= htmlspecialchars($_SESSION['registro_temp']['email']) ?></strong>.
            </p>

            <?php if($error_mensaje): ?>
                <div class="alert"><?= $error_mensaje ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Este campo oculto le dice al PHP que se está enviando el PIN -->
                <input type="hidden" name="verificar_pin" value="1">
                
                <input type="text" name="codigo_auth" class="pin-input" placeholder="000000" maxlength="6" autocomplete="off" required autofocus>
                
                <button type="submit" class="btn-action" style="margin-top: 25px;">Validar y Crear Cuenta</button>
            </form>

            <div style="margin-top: 30px;">
                <p style="color: var(--text-muted); margin-bottom: 5px; font-size: 0.8rem;">¿Te equivocaste de datos?</p>
                <!-- Al mandar reset=1 limpiamos la sesión (necesita ser capturado arriba, ver nota*) -->
                <a href="?reset=1">Regresar y corregir formulario</a>
            </div>
        </div>
        
        <?php 
        // *Nota: Pequeño script para limpiar la sesión si el usuario quiere volver
        if(isset($_GET['reset']) && $_GET['reset'] == '1') {
            unset($_SESSION['registro_temp']);
            header("Location: crear_cuenta.php");
            exit();
        }
        ?>

    <?php else: ?>

        <!-- ========================================================================= -->
        <!-- PASO 1: PANTALLA DE FORMULARIO DE REGISTRO -->
        <!-- ========================================================================= -->
        <div class="auth-container">
            <div class="header">
                <div class="logo-text">Gym<span>Control</span></div>
                <h2>Únete a nosotros</h2>
            </div>

            <?php if($error_mensaje): ?>
                <div class="alert"><?= $error_mensaje ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Este campo oculto le dice al PHP que se está enviando el registro inicial -->
                <input type="hidden" name="registrar_usuario" value="1">

                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" placeholder="Ej. Luis Hernandez" required>
                    </div>

                    <div class="input-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" placeholder="luis@emailejemplo.com" required>
                    </div>

                    <div class="input-group">
                        <label>Teléfono (Opcional)</label>
                        <input type="tel" name="telefono" placeholder="33 1234 5678">
                    </div>

                    <div class="input-group">
                        <label>Contraseña</label>
                        <input type="password" name="pass1" placeholder="••••••••" required>
                    </div>

                    <div class="input-group">
                        <label>Confirmar</label>
                        <input type="password" name="pass2" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-action">Continuar al Paso 2</button>

                <p class="terms">
                    Al continuar, verificaremos tu correo electrónico para garantizar la seguridad de tu futura cuenta.
                </p>
            </form>

            <div class="footer">
                <p style="color: var(--text-muted); font-size: 0.85rem;">
                    ¿Ya tienes cuenta? <a href="index.php">Inicia Sesión</a>
                </p>
            </div>
        </div>

    <?php endif; ?>

</body>
</html>