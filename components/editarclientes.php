<?php
include("conn.php");

$cliente = null;
$mensaje = "";

/* 🧾 FUNCIÓN PARA LOGS */
function logAction($conn, $descripcion, $tipo){
    $stmt = $conn->prepare("
        INSERT INTO logs (quien, descripcion, user_id, gym_id, tipo)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['nombre'],
        $descripcion,
        $_SESSION['user_id'],
        $_SESSION['gym_id'],
        $tipo
    ]);
}

/* 🔍 BUSCAR */
if(isset($_POST['buscar'])){
    $id = $_POST['id'];

    $stmt = $conn->prepare("SELECT * FROM members WHERE id = ? AND gym_id = ?");
    $stmt->execute([$id, $_SESSION['gym_id']]);

    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$cliente){
        $mensaje = "❌ Cliente no encontrado";
    }
}

/* 💾 ACTUALIZAR */
if(isset($_POST['guardar'])){

    $id = $_POST['id'];
        $huella=$_POST['huella'];


    $stmt = $conn->prepare("
        UPDATE members SET
        nombre_completo = ?,
        email = ?,
        telefono = ?,
        status_licencia = ?,
        foto_path = ?,
        fecha_vencimiento = ?,
        huella = ?
        WHERE id = ? AND gym_id = ?
    ");

    $stmt->execute([
        $_POST['nombre_completo'],
        $_POST['email'],
        $_POST['telefono'],
        $_POST['status_licencia'],
        $_POST['foto_path'],
        $_POST['fecha_vencimiento'], 
        $huella,
        $id,
        $_SESSION['gym_id']
    ]);

    logAction($conn, "Editó cliente ID: $id", "UPDATE");

    $mensaje = "✅ Cliente actualizado";

    // recargar datos
    $stmt = $conn->prepare("SELECT * FROM members WHERE id = ? AND gym_id = ?");
    $stmt->execute([$id, $_SESSION['gym_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

}

/* 🗑 ELIMINAR */
if(isset($_POST['eliminar'])){

    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM members WHERE id = ? AND gym_id = ?");
    $stmt->execute([$id, $_SESSION['gym_id']]);

    logAction($conn, "Eliminó cliente ID: $id", "DELETE");

    $mensaje = "🗑 Cliente eliminado";
    $cliente = null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family:'Segoe UI', sans-serif;
}

/* CONTENEDOR */
.card{
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
}

/* INPUTS */
.form-group{
    display:flex;
    flex-direction:column;
    margin-bottom:10px;
}

label{
    font-size:12px;
    margin-bottom:3px;
    color:#64748b;
}

input, select{
    padding:8px;
    border-radius:8px;
    border:1px solid #e2e8f0;
    font-size:13px;
}

/* BOTONES */
.btn{
    padding:8px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:13px;
}

.btn-buscar{ background:#3b82f6; color:white; }
.btn-guardar{ background:#22c55e; color:white; width:100%; margin-top:10px; }
.btn-eliminar{ background:#ef4444; color:white; width:100%; margin-top:5px; }

/* MENSAJE */
.msg{
    font-size:13px;
    margin-bottom:10px;
}
</style>

</head>
<body>

<div style="display:flex; flex-direction:row; gap: 10px; margin:0; " >

    <div class="card" style="display: flex; flex-direction: column;">

        <div class="msg"><?php echo $mensaje; ?></div>

        <h3>Editar cliente</h3>

        <!-- 🔍 BUSCAR -->
        <form method="POST" >
            <div class="form-group">
                <select name="id" >
                <?php 
                $stmt = $conn->prepare("SELECT * FROM members WHERE gym_id = ? ");
                $stmt->execute([$_SESSION['gym_id']]);

                while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                echo "<option value='{$row['id']}'>{$row['id']} - {$row['nombre_completo']}</option>";
                };?>
                </select>
            </div>
            <button class="btn btn-buscar" name="buscar">Buscar</button>
        </form>

        <?php if($cliente): ?>
        <form method="POST" style="display:flex; flex-direction: column; gap:10px;" onsubmit="return confirm('¿Guardar cambios?')">
            <input  type="hidden" name="id" value="<?php echo $cliente['id']; ?>">

            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre_completo" value="<?php echo $cliente['nombre_completo']; ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $cliente['email']; ?>">
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?php echo $cliente['telefono']; ?>">
            </div>
    </div><!-- CARD -->

    <div class="card" style="display: flex; flex-direction: column;">
            <div class="form-group">
                <label>Fecha de vencimiento</label>
                <input type="date" name="fecha_vencimiento" value="<?php echo date('Y-m-d', strtotime($cliente['fecha_vencimiento'])); ?>">
            </div>





            <div  class="form-group">
                <label style="display: none;">Status</label>
                <select hidden name="status_licencia">
                    <option <?php if($cliente['status_licencia']=="Activa") echo "selected"; ?>>Activa</option>
                    <option <?php if($cliente['status_licencia']=="Vencida") echo "selected"; ?>>Vencida</option>
                    <option <?php if($cliente['status_licencia']=="Cancelada") echo "selected"; ?>>Cancelada</option>
                </select>
            </div>




            <div class="form-group">
                <label>Foto URL</label>
                <input type="text" name="foto_path" value="<?php echo $cliente['foto_path']; ?>">
            </div>

            <div class="form-group">
                <label>Huella</label>
                <input type="text" name="huella" value="<?php echo $cliente['huella']; ?>">
            </div>

            <div>
                <button class="btn btn-guardar" name="guardar">Guardar cambios</button>
            </div>

        </form>
        <!-- 🗑 ELIMINAR -->
        <form method="POST" onsubmit="return confirm('⚠️ Esto eliminará el cliente. ¿Continuar?')">
            <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
            <button class="btn btn-eliminar" name="eliminar">Eliminar cliente</button>
        </form>
    </div>
    <?php endif; ?>

</div>
</div>

</body>
</html>