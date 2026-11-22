

<link rel="stylesheet" href="../styles/style1.css">


    <div style="flex-direction: column;" >

        <div>
            <h3>Mis clientes</h3>
            <i><b class="muted" style="color: var(--text-muted);"><?= $resultado_total['total'] ?> clientes en total</b></i>
        </div>
        <br>

        <div style="display: flex; flex-direction: row; gap:10px;" class="card">
        

            <div style="display: flex; flex-direction: row; height: fit-content; " class="">
                <div>
                    <img  height="50px" src="../recursos/perfil-de-usuario.png" alt="">
                </div>

                <div>
                    <b><p style="color: green;" >Clientes activos</p></b>
                    <h2><?php echo $resultado_memberStatus['active'] ?></h2>
                </div>

            </div>
        

            <div style="display: flex; flex-direction: row; height: fit-content;" class="">
                <div><img  height="50px" src="../recursos/usuarios_vencidos.png" alt=""></div>

                <div>
                    <b><p style="color: orange;" >Clientes Vencidos</p></b>
                    <h2><?php $resultado_memberStatus['expired'] ?></h2>
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
