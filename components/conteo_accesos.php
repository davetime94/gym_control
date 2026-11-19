<?php
include("conn.php");


$stmt = $conn->prepare("
    SELECT 
        SUM(acceso_concedido = 1) AS aprobados,
        SUM(acceso_concedido = 0) AS denegados
    FROM access_logs
    WHERE gym_id = ?
    AND fecha_hora >= CURDATE()
    AND fecha_hora < CURDATE() + INTERVAL 1 DAY
");

$stmt->execute([$_SESSION['gym_id']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// evitar null
$aprobados = $data['aprobados'] ?? 0;
$denegados = $data['denegados'] ?? 0;
$total = $aprobados + $denegados;
?>





<div class="card">
    <!-- APROBADOS -->
    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
        <img src="recursos/icono_addmember.png" height="50px">
        <div>
            <p style="color:green;">Permitidos</p>
            <h4><?php echo $aprobados; ?></h4>
        </div>
    </div>

    <!-- DENEGADOS -->
    <div style="display:flex; align-items:center; gap:15px;">
        <img src="recursos/usuarios_vencidos.png" height="50px">
        <div>
            <p style="color:red;">Denegados</p>
            <h4><?php echo $denegados; ?></h4>
        </div>
    </div>



