<?php

//PERSONAL DE CADA GIMNASIO
$stmt_personal = $conn->prepare("SELECT * FROM users_admin WHERE gym_id = ? AND (rol = 'Manager' OR rol = 'Receptionist') ORDER BY id DESC");
$stmt_personal->execute([$_SESSION['gym_id']]);





?>