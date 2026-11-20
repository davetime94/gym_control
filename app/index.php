<?php
require_once __DIR__ . '/../data/conn.php';
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Owner') {
        header("Location: manager.php");
    } else {
        header("Location: clientes.php");
    }
    exit();
}
header("Location: ../index.php");
exit();
