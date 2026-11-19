<?php

include("conn.php");
include("components/security.php");

$conn->beginTransaction();

// 🔴 VENCIDOS
$stmt_vencidos = $conn->prepare("
    UPDATE members 
    SET status_licencia = 'Vencida'
    WHERE fecha_vencimiento < CURDATE()
    AND status_licencia != 'Cancelada'
");

$stmt_vencidos->execute();

// 🟢 ACTIVOS
$stmt_activos = $conn->prepare("
    UPDATE members 
    SET status_licencia = 'Activa'
    WHERE fecha_vencimiento >= CURDATE()
    AND status_licencia != 'Cancelada'
");

$stmt_activos->execute();

$conn->commit();

header("Location: clientes.php");
exit;
?>