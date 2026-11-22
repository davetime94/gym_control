<?php include_once("../data/matrix.php"); ?>
<div >
    <!-- APROBADOS -->
    <div style="display:flex; align-items:center; gap:15px;" class="card">
        <img src="../recursos/icono_addmember.png" height="50px">
        <div>
            <p style="color:green;">Permitidos</p>
            <h4><?php echo $AcessCounts['1']; ?></h4>
        </div>
    </div>

    <!-- DENEGADOS -->
    <div style="display:flex; align-items:center; gap:15px;" class="card">
        <img src="../recursos/usuarios_vencidos.png" height="50px">
        <div>
            <p style="color:red;">Denegados</p>
            <h4><?php echo $AcessCounts['0']; ?></h4>
        </div>
    </div>
</div>


