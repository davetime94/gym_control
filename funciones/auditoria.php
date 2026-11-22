<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../data/security.php';

$gym_id = $_SESSION['gym_id'] ?? 0;

if ($gym_id > 0) {
    $conn->beginTransaction();

    // 🔴 VENCIDOS
    $stmt_vencidos = $conn->prepare("
        UPDATE members 
        SET status_licencia = 'Vencida'
        WHERE gym_id = ?
        AND fecha_vencimiento < CURDATE()
        AND status_licencia != 'Cancelada'
    ");
    $stmt_vencidos->execute([$gym_id]);

    // 🟢 ACTIVOS
    $stmt_activos = $conn->prepare("
        UPDATE members 
        SET status_licencia = 'Activa'
        WHERE gym_id = ?
        AND fecha_vencimiento >= CURDATE()
        AND status_licencia != 'Cancelada'
    ");
    $stmt_activos->execute([$gym_id]);

    $conn->commit();
}

header("Location: ../app/clientes.php");
exit;
?>
