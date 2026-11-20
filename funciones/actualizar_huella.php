<?php
require_once __DIR__ . '/../data/conn.php';

if(!isset($_SESSION['gym_id'])){
    header("location: ../app/index.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id = $_POST['id'];
    $huella = $_POST['huella'];
    
    $sql = "UPDATE members SET huella = ? WHERE id = ? AND gym_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$huella, $id, $_SESSION['gym_id']]);
    
    header("location: ../app/clientes.php");
    exit;
}
?>