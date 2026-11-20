<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';


$mensaje = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $gym_id = $_POST['gym_id'];
    $nombre = $_POST['nombre_completo'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $huella = $_POST['huella_digital_id'];
    $codigo = $_POST['codigo_gafete'];
    $status = $_POST['status_licencia'];
    $foto = $_POST['foto_path'];
    $tipo = $_POST['tipo_licencia'];
    $SS = $_POST['SS'] ?? null;
    $fecha_vencimiento = $_POST['fecha_de_vencimiento'] ?? null;

    $fecha = date("Y-m-d");

    //Generando las cifradas

 

    try{

        $conn->beginTransaction();

        // ✅ INSERT CLIENTE
        $stmt = $conn->prepare("
            INSERT INTO members 
            (gym_id, nombre_completo, email, telefono, huella_digital_id, codigo_gafete, status_licencia, foto_path, created_at, tipo_licencia, SS, fecha_vencimiento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $gym_id,
            $nombre,
            $email,
            $telefono,
            $huella,
            $codigo,
            $status,
            $foto,
            $fecha,
            $tipo,
            $SS,
            $fecha_vencimiento
        ]);

        // 🧾 LOG
        $stmtLog = $conn->prepare("
            INSERT INTO logs (quien, descripcion, user_id, gym_id)
            VALUES (?, ?, ?, ?)
        ");

        $stmtLog->execute([
            $_SESSION['nombre'],
            "Añadió un nuevo cliente: $nombre",
            $_SESSION['user_id'],
            $_SESSION['gym_id']
        ]);

        $conn->commit();

        $mensaje = "✅ Cliente agregado correctamente";

    }catch(Exception $e){

        $conn->rollBack();
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir cliente</title>
    <link rel="stylesheet" href="../styles/style1.css">
</head>
<style>
    body{
        width: 100%;
    }
        .a_3{
        background-color: var(--hover);
        color: black;
    }

    form{
        border-radius: 10px;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        padding: 20px;
        height: fit-content;
        box-shadow: 1px 1px 10px 1px rgba(0,0,0,0.2);
        width: 95%;

    }
    input{
        background-color: ghostwhite;
        padding: 10px;
        margin: 2px;
        box-shadow: 1px 1px 10px 1px rgba(0,0,0,0.1);

    }
 

    @media (prefers-color-scheme: dark){

    }
</style>
<body>

<?php include("../components/navbar.php");  ?>

<main>
<?php include("../components/sidebar.php");  ?>


<div style="padding: 5px; margin:20px; ">
<section>
    <div style="display: flex; flex-direction: row; align-items: center; gap: 20px; margin: 10px;" >
        <div>
            <img src="../recursos/icono_addmember.png" height="50px" alt="">
        </div>

        <div>
            <h3>Añadir nuevo cliente</h3>
            <p>Añade a tus nuevos usuarios con toda la información que requieres</p>
            <small>ten mayor control</small>
            <br>
        </div>    
    </div>

    <?php if($mensaje): ?>
        <p><?php echo $mensaje;  ?></p>
    <?php endif; ?>

    <?php include("add_clientes_form.php"); ?>



</section>

</div>
</main>
</body>
</html>

