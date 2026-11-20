<?php
/**
 * Configuración Global del Sistema Gym Management
 * Centraliza credenciales de DB, SMTP y Pasarela de Pago.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookies de sesión seguras si aún no se ha iniciado la sesión
    session_set_cookie_params([
        'lifetime' => 28800,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// =========================================================================
// 1. CONFIGURACIÓN DE BASE DE DATOS
// =========================================================================
define('DB_HOST', getenv('DB_HOST') ?: 'sql111.infinityfree.com');
define('DB_NAME', getenv('DB_NAME') ?: 'if0_41365813_gym2');
define('DB_USER', getenv('DB_USER') ?: 'if0_41365813');
define('DB_PASS', getenv('DB_PASS') ?: 'U7mEHWKO9Q');
define('DB_CHARSET', 'utf8mb4');

// =========================================================================
// 2. CONFIGURACIÓN SMTP (PHPMailer)
// =========================================================================
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USER', getenv('SMTP_USER') ?: 'davetime94@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'ojakelyfhpvznizp');
define('SMTP_PORT', 465);
define('SMTP_FROM_EMAIL', getenv('SMTP_USER') ?: 'davetime94@gmail.com');
define('SMTP_FROM_NAME', 'Gym Management');

// =========================================================================
// 3. CONFIGURACIÓN MERCADO PAGO
// =========================================================================
define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: 'APP_USR-281507556656393-072620-3ec77877620f57eebdf84bdc03840823-1618351255');
define('MP_PUBLIC_KEY', getenv('MP_PUBLIC_KEY') ?: 'APP_USR-d04d1ea6-5ec6-4eb4-899f-2675a474629b');
define('MP_PLAN_ID', getenv('MP_PLAN_ID') ?: '715062c9671a42dfaf849191ff649485');

?>
