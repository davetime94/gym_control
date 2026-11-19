<?php

include("conn.php");
include("components/security.php");


//Obtener el total de personas y además poner en amarillo si es Free y hay más de 20
//Este proceso esta hecho para que se puedan almacenar las desiciones de las licencias 
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM members WHERE gym_id = ? ");
$stmt_total->execute([$_SESSION['gym_id']]);
$resultado_total=$stmt_total->fetch(PDO::FETCH_ASSOC);

$stmt_plan=$conn->prepare("SELECT * FROM gyms where id = ?");
$stmt_plan->execute([$_SESSION['gym_id']]);
$resultado_plan=$stmt_plan->fetch(PDO::FETCH_ASSOC);

//GYM

$stmt_gym_result=$conn->prepare("SELECT * FROM gyms where id = ?");
$stmt_gym_result->execute([$_SESSION['gym_id']]);
$resultado_gym=$stmt_gym_result->fetch(PDO::FETCH_ASSOC);

//USER
$stmt_user_data=$conn->prepare("SELECT * FROM users_admin where gym_id = ?");
$stmt_user_data->execute([$_SESSION['gym_id']]);
$user_data=$stmt_user_data->fetch(PDO::FETCH_ASSOC);



$plan_actual_color="";

//VARIABLES PARA LOS TICKETS    
$stmt_tickets=$conn->prepare("SELECT * FROM tickets");
$stmt_tickets->execute();
$resultado_tickets=$stmt_tickets->fetchAll(PDO::FETCH_ASSOC);

//VARIABLES PARA LOS TICKETS     pendientes
$stmt_tickets=$conn->prepare("SELECT * FROM tickets where status = 'Pendiente'");
$stmt_tickets->execute();
$resultado_tickets_pendiente=$stmt_tickets->fetchAll(PDO::FETCH_ASSOC);


//status de datos

//Estatus Inactivo o pendiente 
if($resultado_gym['status']="Denied" || $user_data['status']==0 || $user_data['approved']=="Rechazado"){
    $cuenta_bloqueada=True;
}


if($resultado_total['total'] > 20 && $resultado_plan['plan_saas']==="Free"){
    $plan_actual_color="Has exedido tus miembros gratis. Actualiza ahora y recibe información presionando aquí";
    $plan_saas_active  =False;

}else{
        $plan_saas_active  =True;

}

//OBTENER FECHAS DE EXPITACI´N
$expiracion= new DateTime($resultado_plan['expiracion']);
$hoy = new DateTime();
$queda = $hoy->diff($expiracion);
$quedan = $queda->days;





?>