<?php
    require_once __DIR__ . '/../data/conn.php';

    //Para obtener los datos de la cuenta y del gym 
$stmtUser = $conn->prepare("SELECT * FROM users_admin WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

$stmtGym = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
$stmtGym->execute([$_SESSION['gym_id']]);
$gymData = $stmtGym->fetch(PDO::FETCH_ASSOC);



// Consulta para el encabezado del Gym
$stmt=$conn->prepare("SELECT * FROM gyms where id = ? ");
$stmt->execute([$_SESSION['gym_id']]);
$resultado_gym=$stmt->fetch(PDO::FETCH_ASSOC);

// Lógica para preseleccionar fecha de vencimiento (30 días después de hoy)
$fecha_actual = date('Y-m-d');
$fecha_30_dias = date('Y-m-d', strtotime($fecha_actual. ' + 30 days'));


    //proceso para levantar el ticket de ya pagado 
    if(isset($_POST['pago_realizado_request'])){
        $titulo="Pago realizado";
        $description=" Gym Id: "  .  $_SESSION['gym_id'] .   "ID: " . $_SESSION['id'] .   " Nombre: " . $_SESSION['nombre'].   " Estatus: " . $resultado_gym['plan_saas'] . " Good Standing: " . $resultado_gym['good_standing'] . " Email: " . $userData['email'];
        $status="Pendiente";
        $gym_id = $resultado_gym['id'];
        $emisor=$_SESSION['id'];
        $numero_whatsapp=$_POST['numero_whatsapp'];
        $notas="";
    
        $stmt_pago_realizado_request=$conn->prepare("INSERT INTO tickets (titulo, description, status, gym_id, emisor, numero_whatsapp, notas ) values(?,?,?,?,?,?,?)");
        $stmt_pago_realizado_request->execute([$titulo, $description, $status, $gym_id, $emisor, $numero_whatsapp, $notas]);


    }


    ?>