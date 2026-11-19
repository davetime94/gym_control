<?php

include("conn.php");
if(isset($_SESSION['gym_id'])){
   
header("location: index.php");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id = $_POST['id'];
    $huella = $_POST['huella'];
    
    $sql = "UPDATE members SET huella = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$huella, $id]);
    
    header("location: clientes.php");
}

?>