
<?php

require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';



if($_SERVER['REQUEST_METHOD']==='POST'){




//Proceso para meter el log a los pagos
//ID auto generated
$member_id=$_POST['member_id'];
$gym_id=$_SESSION['gym_id'];
$ticket=$_POST['ticket'];
$monto=$_POST['monto'];
$concepto = $_POST['concepto'] . " Pago registrado de licencia " . " ticket: " . $ticket . " Cliente: " . $_POST['member_id'] . "Monto: " . $monto;
//Created at (Automatico)
$fecha_nueva_renovacion="";

$stmt_pago=$conn->prepare("INSERT into pagos (member_id, gym_id, ticket, monto, concepto,tipo) values (?,?,?,?,?,?)");
$stmt_pago->execute([
    $member_id,
    $gym_id,
    $ticket,
    $monto,
    $concepto,
    "Pago renovación",
]);

$mensaje_pago ="Pago realizado";


//Meter transacción al LOG de activity feed

$stmt_activityfeed=$conn->prepare("insert into logs (quien, descripcion, user_id, gym_id, tipo) values (?,?,?,?,?)");
$stmt_activityfeed->execute([
    $_SESSION['nombre'],
    $concepto ,
    "Muestreo",
    $_SESSION['gym_id'],
    "Pago"
]);

//extraer resultado de pagos

header("location: ../app/dashboard_pagos.php");

echo "<script>alert('Se registró el pago correctamente! ✅');</script>";






}

?>

