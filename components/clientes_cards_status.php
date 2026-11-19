

<link rel="stylesheet" href="style.css">
<?php

//Usuarios activos
    $stmt=$conn->prepare("SELECT count(*) as total FROM members where gym_id = ? and status_licencia = ?");
    $stmt->execute([
        $_SESSION['gym_id'],
        "Activa"
    ]);

    $resultado=$stmt->fetch(PDO::FETCH_ASSOC);
    $usuariosActivos=$resultado['total'];

    //usuarios Vencidos
    $stmt=$conn->prepare("SELECT count(*) as total FROM members where gym_id = ? and status_licencia = ?");
    $stmt->execute([
        $_SESSION['gym_id'],
        "Vencida"
    ]);

    $resultado=$stmt->fetch(PDO::FETCH_ASSOC);
    $usuariosVencidos=$resultado['total'];

    //usuarios Cancelados
    $stmt=$conn->prepare("SELECT count(*) as total FROM members where gym_id = ?  ");
    $stmt->execute([
        $_SESSION['gym_id']
    ]);

    $resultado=$stmt->fetch(PDO::FETCH_ASSOC);
    $usuariostotales=$resultado['total']
    
    



?>

<div style="display: flex; flex-direction: column;">
    <h3>Clientes</h3>
    <small>Resumen total</small>

    <div style="display: flex; flex-direction: row; height: fit-content;" class="card">   
        <div>
            <b><p >Clientes Totales</p></b>
            <h2><?php echo $usuariostotales ?></h2>
        </div>
    </div> 


    <div style="display: flex; flex-direction: row; height: fit-content;" class="card">
        <div>
            <img  height="50px" src="recursos/perfil-de-usuario.png" alt="">
        </div>

        <div>
            <b><p style="color: green;" >Clientes activos</p></b>
            <h2><?php echo $usuariosActivos ?></h2>
        </div>

    </div>

    <div style="display: flex; flex-direction: row; height: fit-content;" class="card">
        <div><img  height="50px" src="recursos/usuarios_vencidos.png" alt=""></div>

        <div>
            <b><p style="color: orange;" >Clientes Vencidos</p></b>
            <h2><?php echo $usuariosVencidos ?></h2>
        </div>
    </div>

 

</div> <!-- verticalizador -->


