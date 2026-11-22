<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';

// Obtener el total de personas y además poner en amarillo si es Free y hay más de 20
if (isset($_SESSION['gym_id'])) {



    //****CONTEO***** DE LOS MIEMBROS TOTALES
    $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM members WHERE gym_id = ? ");
    $stmt_total->execute([$_SESSION['gym_id']]);
    $resultado_total = $stmt_total->fetch(PDO::FETCH_ASSOC);

    //INFORMACIÓN DE LOS GIMNASIOS 
    $stmt_plan = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
    $stmt_plan->execute([$_SESSION['gym_id']]);
    $resultado_plan = $stmt_plan->fetch(PDO::FETCH_ASSOC) ?: [];
    $resultado_gym = $resultado_plan;  // LA VARIABLE ES $RESULTADO_GYM

    // INFORMACION DE LOS USUARIOS 
    $stmt_user_data = $conn->prepare("SELECT * FROM users_admin WHERE gym_id = ?");
    $stmt_user_data->execute([$_SESSION['gym_id']]);
    $user_data = $stmt_user_data->fetch(PDO::FETCH_ASSOC) ?: [];


    // SABE XD
    $plan_actual_color = "";

    //PARA OBTENER LOS LOGS
    $sql = "SELECT a.*, m.nombre_completo, g.nombre_gym 
            FROM access_logs a 
            LEFT JOIN members m ON a.member_id = m.id 
            LEFT JOIN gyms g ON a.gym_id = g.id
            WHERE a.gym_id = ?"; // Solo mostramos los de este Gym activo
                        
            $params = [$_SESSION['gym_id']];

            if($member_filtro) { $sql .= " AND a.member_id = ?"; $params[] = $member_filtro;}
            $sql .= " ORDER BY a.fecha_hora DESC LIMIT 50";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);




   
   
   
    // VARIABLES PARA LOS TICKETS    
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Owner') {
        $stmt_tickets = $conn->prepare("SELECT * FROM tickets");
        $stmt_tickets->execute();
        $resultado_tickets = $stmt_tickets->fetchAll(PDO::FETCH_ASSOC);

        $stmt_tickets_p = $conn->prepare("SELECT * FROM tickets WHERE status = 'Pendiente'");
        $stmt_tickets_p->execute();
        $resultado_tickets_pendiente = $stmt_tickets_p->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt_tickets = $conn->prepare("SELECT * FROM tickets WHERE gym_id = ?");
        $stmt_tickets->execute([$_SESSION['gym_id']]);
        $resultado_tickets = $stmt_tickets->fetchAll(PDO::FETCH_ASSOC);

        $stmt_tickets_p = $conn->prepare("SELECT * FROM tickets WHERE gym_id = ? AND status = 'Pendiente'");
        $stmt_tickets_p->execute([$_SESSION['gym_id']]);
        $resultado_tickets_pendiente = $stmt_tickets_p->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lógica para bloquear la cuenta al cumplir ciertos parametros 
    $cuenta_bloqueada = false;
    if ((isset($resultado_gym['status']) && $resultado_gym['status'] === "Denied") || 
        (isset($user_data['status']) && $user_data['status'] == 0) || 
        (isset($user_data['approved']) && $user_data['approved'] === "Rechazado")) {
        $cuenta_bloqueada = true;
    }

    $total_miembros = isset($resultado_total['total']) ? (int)$resultado_total['total'] : 0;
    $tipo_plan = isset($resultado_plan['plan_saas']) ? $resultado_plan['plan_saas'] : 'Free';

    if ($total_miembros > 20 && $tipo_plan === "Free") {
        $plan_actual_color = "Has excedido tus miembros gratis. Actualiza ahora y recibe información presionando aquí";
        $plan_saas_active = false;
    } else {
        $plan_saas_active = true;
    }

    // OBTENER FECHAS DE EXPIRACIÓN
    $quedan = 0;
    if (!empty($resultado_plan['expiracion'])) {
        try {
            $expiracion = new DateTime($resultado_plan['expiracion']);
            $hoy_dt = new DateTime();
            $queda = $hoy_dt->diff($expiracion);
            $quedan = $queda->days;
        } catch (Exception $e) {
            $quedan = 0;
        }
    }
}
?>
