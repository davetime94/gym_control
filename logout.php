<?php
// Iniciamos la sesión para poder acceder a ella
session_start();

// 1. Limpiamos todas las variables de sesión
$_SESSION = array();

// 2. Si se desea destruir la sesión completamente, también se debe borrar la cookie de sesión.
// Nota: ¡Esto destruirá la sesión y no solo los datos de la sesión!
if (ini_get("session_use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruimos la sesión.
session_destroy();

// 4. Redirigimos al usuario al login
header("Location: index.php");
exit;
?>