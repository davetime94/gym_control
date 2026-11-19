<?php

include("conn.php");

$stmt=$conn->prepare("SELECT SUM(*) as suma  FROM pagos where tipo = 'Ingreso' and fecha = curtime  ");


?>



<h3>Ingresos estimados</h3>
<div style="display: flex; flex-direction: row; align-items: center; ">
    <div class="card"t>
        <h4>Hoy</h4>

    </div>


</div>
