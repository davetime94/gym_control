<?php 
require_once __DIR__ . '/config.php';
include("conn.php");
include("components/security.php");

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoGmail($destinatario, $asunto, $cuerpo) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();                                            
        $mail->Host       = SMTP_HOST;                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         
        $mail->Port       = SMTP_PORT;                                    

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($destinatario);                           

        $mail->isHTML(true);                                        
        $mail->CharSet = 'UTF-8';                                   
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;                                   

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error enviando correo en correspondencia.php: " . $e->getMessage());
        return false;
    }
}
?>