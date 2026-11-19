
<?php
include("matrix.php");

/**LOGICA PARA CAMBIAR EL ESTATUS*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if(isset($_POST['update_ticket_status'])){

    $new_status=$_POST['new_status'];
    $id_id=$_POST['id_id'];

    $stmt=$conn->prepare("UPDATE tickets set status = ? where id = ?  ");
    $stmt->execute([$new_status, $id_id]);



    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="recursos/icon.png" type="image/x-icon">
    <title>Mis Tickets</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet" href="tables_style.css">
</head>

<body>

<?php include("components/navbar.php"); ?>

<main >



<section>
    <?php include("components/sidebar.php"); ?>
</section>
<section style="padding: 10px; margin: 10px;" >


<div class="card" style="flex-direction: column;">
    <h2 class="badge warning">  Tickets pendientes</h2>
    <br>






    
    <!-- /////////////////////////////////////////////////TABLA DE DATOS///////////////////////////////////////////////// -->
    <table class="master-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Titulo</th>
                <th>Descripcion</th>
                <th>Gym</th>
                <th>Whatsapp</th>
                <th>Notas</th>
                <th>|</th>
                <th>Status</th>
                <th>Creado en</th>

            </tr>
        </thead>
        <tbody>
        <?php foreach($resultado_tickets_pendiente as $row): ?>
            <tr>
                <td style="color: var(--text-muted);"><?= htmlspecialchars($row['id'])?></td>
                <td style="color: var(--primary)"><b><?= htmlspecialchars($row['titulo'])?></b></td>
                <td><?= htmlspecialchars($row['description'])?></td>
                <td style="color: var(--primary)"><b><?= htmlspecialchars($row['gym_id'])?></b></td>
                <td style="color: var(--primary)"> <?= htmlspecialchars($row['numero_whatsapp'])?> </td>
                <td><?= htmlspecialchars($row['notas'])?></td>
                <td>|</td>
<!-- FORM PARA CAMBIAR EL ESTATUS -->
                <td style="border-radius:10px; border:none;"   >
                    <form method="POST" >
                    <input type="text" hidden value="<?= htmlspecialchars($row['id']) ?>" name="id_id">
                        <select class="badge 
                            <?= ($row['status']=='Completado'?'success':( $row['status']=='Pendiente'?'warning':'')) ?> " name="new_status">
                            <option class="badge " value="Completado" <?= ($row['status']=='Completado'?'selected':'')?>>Completado</option>
                            <option class="badge " value="Pendiente" <?=  ($row['status']=='Pendiente'?'selected':'')?>> Pendiente</option>
                        </select>
                        <button type="submit" class="btn-search ani" style="width: fit-content; " name="update_ticket_status" >Actualizar</button>
                    </form>
                </td>
<!-- ///////////////////////////7 -->



                <td><?= htmlspecialchars($row['created_at'])?></td>

            </tr>
            <?php endforeach; ?>
        </tbody>

    </table>

</div>

<div class="card" style="flex-direction: column;">
    <h2>Tickets</h2>
    <br>






    
    <!-- /////////////////////////////////////////////////TABLA DE DATOS///////////////////////////////////////////////// -->
    <table class="master-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Titulo</th>
                <th>Descripcion</th>
                <th>Gym</th>
                <th>Whatsapp</th>
                <th>Notas</th>
                <th>|</th>
                <th>Status</th>
                <th>Creado en</th>

            </tr>
        </thead>
        <tbody>
        <?php foreach($resultado_tickets as $row): ?>
            <tr>
                <td style="color: var(--text-muted);"><?= htmlspecialchars($row['id'])?></td>
                <td style="color: var(--primary)"><b><?= htmlspecialchars($row['titulo'])?></b></td>
                <td><?= htmlspecialchars($row['description'])?></td>
                <td style="color: var(--primary)"><b><?= htmlspecialchars($row['gym_id'])?></b></td>
                <td style="color: var(--primary)"> <?= htmlspecialchars($row['numero_whatsapp'])?> </td>
                <td><?= htmlspecialchars($row['notas'])?></td>
                <td>|</td>
<!-- FORM PARA CAMBIAR EL ESTATUS -->
                <td style="border-radius:10px; border:none;"   >
                    <form method="POST" >
                    <input type="text" hidden value="<?= htmlspecialchars($row['id']) ?>" name="id_id">
                        <select class="badge 
                            <?= ($row['status']=='Completado'?'success':( $row['status']=='Pendiente'?'warning':'')) ?> " name="new_status">
                            <option class="badge " value="Completado" <?= ($row['status']=='Completado'?'selected':'')?>>Completado</option>
                            <option class="badge " value="Pendiente" <?=  ($row['status']=='Pendiente'?'selected':'')?>> Pendiente</option>
                        </select>
                        <button type="submit" class="btn-search ani" style="width: fit-content; " name="update_ticket_status" >Actualizar</button>
                    </form>
                </td>
<!-- ///////////////////////////7 -->



                <td><?= htmlspecialchars($row['created_at'])?></td>

            </tr>
            <?php endforeach; ?>
        </tbody>

    </table>

</div>

</section>


</main>
    
</body>