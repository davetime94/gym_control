<?php
include("conn.php");


if($_SESSION['rol']=='Owner'){
    header("location: manager.php");
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="recursos/icon.png" type="image/x-icon">
    <title>Mis clientes</title>
    <link rel="stylesheet" href="style1.css">
</head>

<body>

<?php include("components/navbar.php"); ?>

<main >



<section>
    <?php include("components/sidebar.php"); ?>
</section>
<section style="padding: 10px; margin: 10px;" >


    <?php if($gymData['status']!='Active'): ?>

        <div class="card"><?php include("components/pendiente.php");?></div>
   
    <?php elseif($gymData['status']=='Active'): ?>

            <h1>Mis clientes</h1>


        <div style="display: flex;flex-direction: row; max-width: fit-content;  gap: 5px; "  > 
           
            <div class="card">
                <?php include ("components/clientes_cards_status.php"); ?>
            </div>
            
            
            
            <div class="card" style="height: fit-content;flex-direction:column;  " >
                <?php include("simple_renovar_pago.php");  ?>
            </div>

            <div class="card" style="height: fit-content;  " >
                <?php include("components/editarclientes.php");  ?>
            </div>
            

        </div>
       
        <?php include("components/tabla_de_clientes.php"); ?></div>
        <?php endif; ?>






</section>


</main>
    
</body>
<script src="script.js">

    
//EXPANDIR EL CARD DE LA RENOVACIÓN DE PAGOS---
function expandir(){
    const card_cobro = document.getElementById("card_cobro");

    card_cobro.style.width="600px";
    card_cobro.style.backgroundColor="Red";
}


</script>
</html>