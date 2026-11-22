<?php
require_once __DIR__ . "/conn.php";

$limite_tiempo = 28800; 
$tiempo_transcurrido = 0;

// Helper para determinar ruta de redirección según el contexto actual
$is_in_app = (strpos($_SERVER['PHP_SELF'], '/app/') !== false);
$is_in_funciones = (strpos($_SERVER['PHP_SELF'], '/funciones/') !== false);

$login_url = $is_in_app ? 'index.php' : ($is_in_funciones ? '../app/index.php' : 'app/index.php');
$logout_url = $is_in_app ? '../funciones/logout.php' : ($is_in_funciones ? 'logout.php' : 'funciones/logout.php');
$dashboard_url = $is_in_app ? 'clientes.php' : ($is_in_funciones ? '../app/clientes.php' : 'app/clientes.php');

// 1. Validar primero que exista sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $login_url);
    exit();
}

// 2. Validar duración de sesión
if (isset($_SESSION['sesion_creada'])) {
    $tiempo_transcurrido = time() - $_SESSION['sesion_creada'];
    if ($tiempo_transcurrido > $limite_tiempo) {
        session_unset();
        session_destroy();
        header("Location: " . $logout_url);
        exit();
    }
} else {
    $_SESSION['sesion_creada'] = time();
}

// 3. Validar si el usuario está bloqueado
$stmt = $conn->prepare("SELECT bloqueado_hasta FROM users_admin WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$bloqueo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($bloqueo && !empty($bloqueo['bloqueado_hasta'])) {
    if (strtotime($bloqueo['bloqueado_hasta']) > time()) {
        session_unset();
        session_destroy();
        header("Location: " . $logout_url);
        exit();
    }
}

// 4. Variables de sesión seguras
$user_id = $_SESSION['user_id'];
$gym_id = isset($_SESSION['gym_id']) ? $_SESSION['gym_id'] : 0;
$nombre_session = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
$status = isset($_SESSION['status']) ? $_SESSION['status'] : '';

// 5. Obtener datos del gimnasio de forma segura
$resultado_gym = [];
if ($gym_id > 0) {
    $stmt_gym_result = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
    $stmt_gym_result->execute([$gym_id]);
    $resultado_gym = $stmt_gym_result->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Tiempo restante de sesión
$tiempo_restante = ($limite_tiempo - $tiempo_transcurrido) / 3600;
$redondeado = round($tiempo_restante, 2);
$tiempo_restante = $redondeado . " Horas";

$hoy = date('Y-m-d');
if (!empty($resultado_gym['expiracion']) && $resultado_gym['expiracion'] <= $hoy) {
    $stmt_update_status = $conn->prepare("UPDATE gyms SET plan_saas = 'Free' WHERE id = ?");
    $stmt_update_status->execute([$gym_id]);
    $resultado_gym['plan_saas'] = 'Free';
}

// Helper para verificar roles permitidos en páginas protegidas
if (!function_exists('check_role')) {
    function check_role($allowed_roles = []) {
        global $dashboard_url;
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], (array)$allowed_roles)) {
            header("Location: " . $dashboard_url);
            exit();
        }
    }
}
?>
