<div>         
<h3>Buscar clientes</h3>     
<div class="card" style="width: fit-content;  display: flex; height: fit-content; max-width:fit-content; align-items: center; flex-direction: row; gap:10px;">
<div><img src="../recursos/buscar.png" height="40px" alt=""> </div>

<div>
    <form  method="post" style="flex-direction: column;">
        <select name="id_cliente_a_buscar" >
            <?php 
        $stmt = $conn->prepare("SELECT * FROM members WHERE gym_id = ? ");
        $stmt->execute([$_SESSION['gym_id']]);

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            echo "<option value='{$row['id']}'>{$row['nombre_completo']}</option>";
        };?>
        </select>
        <button class="btn-search" type="submit">Buscar</button>
    </form>
</div>
</div>

<?php 



$id_buscado="Esperando...";
$nombre_buscado="Esperando...";
$estatus_buscado="Esperando...";
$email_buscado="Esperando...";
$numero_buscado="Esperando...";
$inscrito_buscado="Esperando...";
$mensaje="";

if($_SERVER['REQUEST_METHOD']==='POST'){

    // ✅ Validar que exista y no esté vacío
    if(!empty($_POST['id_cliente_a_buscar'])){

        $id_cliente_a_buscar = trim($_POST['id_cliente_a_buscar']);

        $stmt = $conn->prepare("SELECT * FROM members WHERE id = ? and gym_id = ? ");
        $stmt->execute([$id_cliente_a_buscar, $_SESSION['gym_id']]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // ✅ Si encuentra datos
        if($row){
            $id_buscado = $row['id'];
            $nombre_buscado = $row['nombre_completo'];
            $estatus_buscado = $row['status_licencia'];
            $email_buscado = $row['email'];
            $numero_buscado = $row['telefono'];
            $inscrito_buscado = $row['created_at'];
        }else{
            $mensaje = "❌ Cliente no encontrado";
        }

    }else{
        $mensaje = "⚠️ Ingresa un ID válido";
    }
}
?>


<div  class="card">
<h3>Información encontrada</h3><br>
<div class="horizontador" > <!-- Card para poner horizontal -->
    <div>
        <!--     <img src="../recursos/ChatGPT Image Feb 4, 2026, 10_41_11 AM.png" class="perfil_foto" > -->
    </div>
    <div>
        <h4><?php  echo $nombre_buscado ?></h4>
        <h5><span>ID: <?php echo $id_buscado ?></span></h5>
        <p>Inscrito en: <?php echo $inscrito_buscado ?> </p>
        <p class="status_licencia_color" >Estatus: <?php echo $estatus_buscado ?> </p>
        <p>Email:  <?php echo $email_buscado ?> </p>
        <p>Número de teléfono: <?php echo $numero_buscado  ?></p>
    </div>
</div>
</div>
</div>
<script>
    
</script>