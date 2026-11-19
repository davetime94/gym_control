<?php
// 1. Configuración e inicio seguro de sesión
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true, // Previene acceso a cookies de sesión mediante JavaScript (Mitigación de XSS)
        'samesite' => 'Lax' // Protección contra ataques Cross-Site Request Forgery (CSRF)
    ]);

    ini_set('session.use_strict_mode', '1'); // Evita fijación de sesión
    ini_set('session.use_only_cookies', '1'); // Forzar uso exclusivo de cookies para IDs de sesión

    session_start();
}

// 2. Conexión segura y optimizada con PDO
try {
    $db_host = "localhost";
    $db_name = "gym3";
    $db_user = "root";
    $db_pass = "";
    $db_charset = "utf8mb4";

    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Utiliza sentencias preparadas nativas del motor (Mayor seguridad contra SQL Injection)
    ];

    $conn = new PDO($dsn, $db_user, $db_pass, $options);
    $pdo = &$conn; // Alias de referencia para compatibilidad total en todo el sistema
    $status_conexion = "Conexión establecida con éxito";
} catch (PDOException $e) {
    // Registro interno del error en logs del servidor sin exponer datos sensibles al cliente
    error_log("Error de conexión PDO: " . $e->getMessage());
    die("Error crítico: No se pudo conectar a la base de datos.");
}

// 3. Obtención y sanitización segura de la dirección IP del cliente
$raw_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ip = filter_var($raw_ip, FILTER_VALIDATE_IP) ? $raw_ip : '127.0.0.1';


///Variable para crear funcion 



?>