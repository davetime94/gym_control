

<link rel="stylesheet" href="../styles/style1.css">
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

    <div style="flex-direction: column;" >

        <div>
            <h3>Mis clientes</h3>
        </div>
        <br>

        <div style="display: flex; flex-direction: row; gap:10px;" class="card">
        

            <div style="display: flex; flex-direction: row; height: fit-content; " class="">
                <div>
                    <img  height="50px" src="../recursos/perfil-de-usuario.png" alt="">
                </div>

                <div>
                    <b><p style="color: green;" >Clientes activos</p></b>
                    <h2><?php echo $usuariosActivos ?></h2>
                </div>

            </div>
        

            <div style="display: flex; flex-direction: row; height: fit-content;" class="">
                <div><img  height="50px" src="../recursos/usuarios_vencidos.png" alt=""></div>

                <div>
                    <b><p style="color: orange;" >Clientes Vencidos</p></b>
                    <h2><?php echo $usuariosVencidos ?></h2>
                </div>
            </div>
            
        </div>
        
        
        <div style="gap:10px;">
            <div>
                <h3>Conteo de accesos</h3>
            </div>
            <br>
            <div>
                <?php include("../components/conteo_accesos.php"); ?>
            </div>
        </div>
    </div>
