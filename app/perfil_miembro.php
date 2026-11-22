<?php
// 1. Incluimos la seguridad (esto ya trae la sesión, $gym_id, $user_id y conn.php)
require_once __DIR__ . "/../components/security.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../recursos/icon.png" type="image/x-icon">
    <title>Perfil 360 del Miembro | GymSync</title>
    <link rel="stylesheet" href="../styles/style1.css?v=<?= time(); ?>">
</head>

<body>
    <?php include("../components/navbar.php"); ?>

    <main style="display: flex;">
        <section>
            <?php include("../components/sidebar.php"); ?>
        </section>

        <section style="padding: 20px; width: 100%; box-sizing: border-box;">
           

        </section>
    </main>
</body>
</html>
