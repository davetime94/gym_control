<?php
include("conn.php");

$hoy = date("Y-m-d");

$stmt = $conn->prepare("
    SELECT id, nombre_completo, telefono, tipo_licencia, fecha_vencimiento
    FROM members
    WHERE gym_id = ? AND fecha_vencimiento = ?
");

$stmt->execute([$_SESSION['gym_id'], $hoy]);

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>




<?php if(count($clientes) > 0): ?>

        <?php foreach($clientes as $c): ?>
            <div class="card">
                <strong><?php echo $c['nombre_completo']; ?></strong>
                ID: <?php echo $c['id']; ?><br>
                Tel: <?php echo $c['telefono']; ?><br>
                Vence hoy <br>
                <hr>
            </div>
        <?php endforeach; ?>

<?php else: ?>

    <p>✅ No hay clientes que venzan hoy</p>

<?php endif; ?>

