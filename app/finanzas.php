<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';

$mensaje = "";

/* =========================
   🧠 REGISTRAR MOVIMIENTO
=========================*/
if(isset($_POST['registrar'])){

    $tipo = $_POST['tipo'];
    $monto = floatval($_POST['monto']);
    $concepto = $_POST['concepto'];
    $ticket = $_POST['ticket'];
    $nota = $_POST['nota'];

    $gym_id = $_SESSION['gym_id'];
    $user_id = $_SESSION['user_id'];
    $nombre = $_SESSION['nombre'];

    if(empty($ticket)){
        $ticket = "T-" . time();
    }

    try{

        $conn->beginTransaction();

        if($tipo === 'egreso'){
            $monto = -abs($monto);
        }elseif($tipo === 'ingreso'){
            $monto = abs($monto);
        }elseif($tipo === 'correccion'){
            $monto = -$monto;
        }

        $stmt = $conn->prepare("
            INSERT INTO pagos (gym_id, ticket, monto, concepto)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$gym_id,$ticket,$monto,$concepto]);

        $descripcion = "$nombre registró $tipo de $$monto | Ticket: $ticket | $concepto | Nota: $nota";

        $log = $conn->prepare("
            INSERT INTO logs (quien, descripcion, user_id, gym_id, tipo)
            VALUES (?, ?, ?, ?, ?)
        ");

        $log->execute([
            $nombre,
            $descripcion,
            $user_id,
            $gym_id,
            "Caja"
        ]);

        $conn->commit();
        $mensaje = "✅ Movimiento registrado";

    }catch(Exception $e){
        $conn->rollBack();
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}

/* =========================
   📊 CONSULTA
=========================*/
$stmt = $conn->prepare("SELECT * FROM pagos WHERE gym_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['gym_id']]);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 💰 BALANCE */
$balance = 0;
foreach($movimientos as $m){
    $balance += $m['monto'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../styles/style1.css">
<title>Caja</title>

<style>
input, select{
    padding:8px;
    border-radius:8px;
    border:1px solid #ddd;
}

.filtros{
    display:flex;
    flex-direction: row;
    gap:10px;
    margin-bottom:15px;
    background-color: white;
    padding: 10px 0px;
    border-radius: 15px;
    justify-content: center;
    align-items: center;
    box-shadow: 1px 1px 10px 1px rgba(0,0,0,.1);
}

table{
    width:100%;
    border-collapse:collapse;
}

td, th{
    padding:10px;
    border-bottom:1px solid #eee;
}

tr:hover{
    background:#f9fafb;
}

.pos{ color:#16a34a; font-weight:bold; }
.neg{ color:#dc2626; font-weight:bold; }

.ticket{
    display:none;
    background:#111;
    color:#0f0;
    padding:15px;
    margin-top:10px;
    font-family:monospace;
}

.a_10{
    background-color: var(--hover);
}
</style>

<script>
function toggleTicket(id){
    const el = document.getElementById("ticket-"+id);
    el.style.display = el.style.display === "block" ? "none" : "block";
}

/* 🔥 FILTROS PRO */
function filtrar(){
    const texto = document.getElementById("buscador").value.toLowerCase();
    const ticket = document.getElementById("f_ticket").value.toLowerCase();
    const tipo = document.getElementById("f_tipo").value;
    const min = parseFloat(document.getElementById("f_min").value) || -999999;
    const max = parseFloat(document.getElementById("f_max").value) || 999999;
    const fechaIni = document.getElementById("f_inicio").value;
    const fechaFin = document.getElementById("f_fin").value;

    const filas = document.querySelectorAll("#tabla tbody tr.data");

    filas.forEach(fila=>{
        const contenido = fila.innerText.toLowerCase();
        const monto = parseFloat(fila.dataset.monto);
        const tipoFila = fila.dataset.tipo;
        const fecha = fila.dataset.fecha;
        const ticketFila = fila.dataset.ticket.toLowerCase();

        let mostrar = true;

        if(!contenido.includes(texto)) mostrar = false;
        if(ticket && !ticketFila.includes(ticket)) mostrar = false;
        if(tipo && tipoFila !== tipo) mostrar = false;
        if(monto < min || monto > max) mostrar = false;

        if(fechaIni && fecha < fechaIni) mostrar = false;
        if(fechaFin && fecha > fechaFin) mostrar = false;

        fila.style.display = mostrar ? "" : "none";
    });
}
</script>

</head>
<body>

<?php include("../components/navbar.php"); ?>

<main>
<?php include("../components/sidebar.php"); ?>

<div class="content">

<h2>💸 Caja del gimnasio</h2>

<?php if($mensaje) echo "<p>$mensaje</p>"; ?>

<!-- 💰 BALANCE -->
<div class="card">
    <h3>Balance actual:</h3>
    <h1 class="<?php echo $balance >= 0 ? 'pos' : 'neg'; ?>">
        $<?php echo number_format($balance,2); ?>
    </h1>
</div>

<!-- ➕ FORM -->
<div class="card">
<form method="POST">
<h3>Registrar nuevo movimiento</h3><br>
    <select name="tipo">
        <option value="ingreso">Ingreso 💰</option>
        <option value="egreso">Gasto ❌</option>
        <option value="correccion">Corrección 🔁</option>
    </select>

    <input type="text" name="concepto" placeholder="Concepto" required>
    <input type="text" name="ticket" placeholder="Ticket">
    <input type="number" step="0.01" name="monto" placeholder="Monto" required>
    <input type="text" name="nota" placeholder="Nota">

    <button name="registrar">Registrar</button>

</form>
</div>

<!-- 🔥 FILTROS -->
<div class=" filtros"><br>
    <h3>Filtros </h3>

<input type="text" id="buscador" placeholder="Buscar..." onkeyup="filtrar()">
<input type="text" id="f_ticket" placeholder="Ticket..." onkeyup="filtrar()">

<select id="f_tipo" onchange="filtrar()">
    <option value="">Todos</option>
    <option value="Ingreso">Ingreso</option>
    <option value="Egreso">Egreso</option>
</select>

<input type="number" id="f_min" placeholder="Min $" onkeyup="filtrar()">
<input type="number" id="f_max" placeholder="Max $" onkeyup="filtrar()">

<input type="date" id="f_inicio" onchange="filtrar()">
<input type="date" id="f_fin" onchange="filtrar()">

</div>

<!-- 📊 TABLA -->
<div class="card">
<h3>Movimientos</h3>

<table id="tabla">
<thead>
<tr>
<th>ID</th>
<th>Concepto</th>
<th>Ticket</th>
<th>Tipo</th>
<th>Monto</th>
<th>Fecha</th>
<th>Acción</th>
</tr>
</thead>

<tbody>
<?php foreach($movimientos as $m): 
    $tipo = $m['monto'] >= 0 ? "Ingreso" : "Egreso";
?>
<tr class="data"
    data-monto="<?php echo $m['monto']; ?>"
    data-tipo="<?php echo $tipo; ?>"
    data-fecha="<?php echo date('Y-m-d', strtotime($m['created_at'])); ?>"
    data-ticket="<?php echo $m['ticket']; ?>"
>
    <td><?php echo $m['id']; ?></td>
    <td><?php echo $m['concepto']; ?></td>
    <td><?php echo $m['ticket']; ?></td>

    <td class="<?php echo $m['monto'] >= 0 ? 'pos':'neg'; ?>">
        <?php echo $tipo; ?>
    </td>

    <td class="<?php echo $m['monto'] >= 0 ? 'pos':'neg'; ?>">
        $<?php echo number_format($m['monto'],2); ?>
    </td>

    <td><?php echo $m['created_at']; ?></td>

    <td>
        <button type="button" onclick="toggleTicket(<?php echo $m['id']; ?>)">🧾</button>
    </td>
</tr>

<tr>
<td colspan="7">
<div class="ticket" id="ticket-<?php echo $m['id']; ?>">
==============================<br>
🏋️ GYM RECEIPT<br>
==============================<br>
Ticket: <?php echo $m['ticket']; ?><br>
Concepto: <?php echo $m['concepto']; ?><br>
Monto: $<?php echo number_format($m['monto'],2); ?><br>
Fecha: <?php echo $m['created_at']; ?><br>
------------------------------<br>
No eliminable | usar corrección<br>
==============================
</div>
</td>
</tr>

<?php endforeach; ?>
</tbody>
</table>

</div>

</div>
</main>
</body>
</html>