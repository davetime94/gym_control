<?php 
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/security.php';
?>

<h2>Renovar pago</h2> <br>

<form method='post' action='../funciones/log_renovar_pago.php' style='flex-direction: column; '>
    <div class="card" style="flex-direction: column;">
        <label>Ingresa ID de miembro</label><br>

        <select name="member_id">
            <?php 
            $stmt_buscar_mis_members = $conn->prepare("SELECT * FROM members WHERE gym_id = ?");
            $stmt_buscar_mis_members->execute([$gym_id]);
            while($row = $stmt_buscar_mis_members->fetch(PDO::FETCH_ASSOC)){
                echo "<option value='{$row['id']}'>{$row['id']} - " . htmlspecialchars($row['nombre_completo']) . "</option>";
            }        
            ?>
        </select><br>
    </div>

    <div class="card" style="gap:3px;">
        <label>Ingrese ticket de pago <br> <input type='text' placeholder='EJ: T-1556' name='ticket' required maxlength='50'></label><br>
        <label>Monto de venta <br> <input type='number' required name='monto' placeholder='Ej: $399.00'></label><br>
        <label>Concepto o notas <br> <input type='text' required name='concepto' placeholder='Pago de mensualidad + nombre cliente'></label><br><br>
    </div>
    <button class="btn-search" type='submit'>Ingresar</button>
</form>
