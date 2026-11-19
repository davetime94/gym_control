<?php
// 1. Incluimos la seguridad (esto ya trae la sesión, $gym_id, $user_id y conn.php)
include("components/security.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="recursos/icon.png" type="image/x-icon">
    <title>Perfil 360 del Miembro | GymSync</title>
    <link rel="stylesheet" href="style1.css">
    <style>
        /* Estilos complementarios para el layout 360 */
        .search-bar { width: 100%; padding: 15px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 20px;}
        .dashboard-grid { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; }
        .card-360 { flex: 1 1 300px; min-width: 300px; } /* Usando tu clase base de card pero adaptable */
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; font-size: 14px; color: #555; }
        .form-group input, .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .badge { padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 12px; color: white; display: inline-block;}
        .badge.Activa { background-color: #28a745; }
        .badge.Vencida { background-color: #dc3545; }
        .badge.Cancelada { background-color: #6c757d; }
        .btn-save { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%;}
    </style>
</head>

<body>
    <?php include("components/navbar.php"); ?>

    <main style="display: flex;">
        <section>
            <?php include("components/sidebar.php"); ?>
        </section>

        <section style="padding: 20px; width: 100%; box-sizing: border-box;">
           

        </section>
    </main>
</body>
</html>