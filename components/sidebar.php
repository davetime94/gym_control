<?php
// Obtenemos el nombre del archivo actual para la clase 'active'
require_once __DIR__ . "/../data/matrix.php";

$pagina_actual = basename($_SERVER['PHP_SELF']);






?>

<link rel="stylesheet" href="../styles/style1.css">
<link rel="stylesheet" href="../styles/sidebar.css">

<aside class="sidebar-crm">
    <div class="sidebar-header">
        <div class="logo-container">
            <span class="logo-text">GymControl</span>
        </div>
    </div>
    <!-- Se comienza el ciclo if para ver si es owner  -->
    <?php if($_SESSION['rol']=='Owner'): ?> 

        <nav class="sidebar-menu">
        <p class="menu-category">Administración</p>
        <a href="manager.php" class="menu-item <?php echo ($pagina_actual == 'manager.php') ? 'active' : ''; ?>">
             Manager
        </a>
        <a href="manager_tickets.php" class="menu-item <?php echo ($pagina_actual == 'manager_tickets.php') ? 'active' : ''; ?>">
             Tickets
        </a>
    <?php elseif($_SESSION['rol']!='Owner' && $gymData['status']=='Active'): ?>
        <nav class="sidebar-menu">
            <p class="menu-category">Administración</p>
            <a href="clientes.php" class="menu-item <?php echo ($pagina_actual == 'clientes.php') ? 'active' : ''; ?>">
                Dasboard
            </a>
        
            <a href="administrar_clientes.php" class="menu-item <?php echo ($pagina_actual == 'administrar_clientes.php') ? 'active' : ''; ?>">
                Administrar clientes
            </a>

            <?php if($plan_saas_active  == True): ?>
            <a href="add_clientes_form.php" class="menu-item <?php echo ($pagina_actual == 'add_clientes_form.php') ? 'active' : ''; ?>">
                Añadir clientes
            </a>
            <?php endif; ?>

            <a href="administar_personal.php" class="menu-item <?php echo ($pagina_actual == 'administar_personal.php') ? 'active' : ''; ?>">
                Administrar Personal
            </a>
            <hr>
            <p class="menu-category">Soporte Técnico</p>

            <a href="soporte.php" class="menu-item <?php echo ($pagina_actual == 'soporte.php') ? 'active' : ''; ?>">
                Tickets & soporte
            </a>
  
            <a href="logs.php" class="menu-item <?php echo ($pagina_actual == 'logs.php') ? 'active' : ''; ?>">
                Activity Feed
            </a>

            <a href="dashboard_pagos.php" class="menu-item <?php echo ($pagina_actual == 'dashboard_pagos.php') ? 'active' : ''; ?>">
                Resumen de pagos
            </a>

            <a href="access_logs.php" class="menu-item <?php echo ($pagina_actual == 'access_logs.php') ? 'active' : ''; ?>">
                Registro de accesos
            </a>
            
            
            <hr><br>
            <p class="menu-category">Herramientas extra</p>
            <a href="../funciones/auditoria.php" class="menu-item">
                Auditoría
            </a>
            
            <a href="paneldeacceso.php" class="menu-item <?php echo ($pagina_actual == 'paneldeacceso.php') ? 'active' : ''; ?>">
                Panel de acceso
            </a>

            <p class="menu-category">Configuración</p>
            <a href="new_gym.php" class="menu-item <?php echo ($pagina_actual == 'new_gym.php') ? 'active' : ''; ?>">
                Ajustes del Gym
            </a>
            <a href="configuracion.php" class="menu-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                Ajustes del perfil
            </a>
            <a href="pagos_saas.php" class="menu-item <?php echo ($pagina_actual == 'pagos_saas.php') ? 'active' : ''; ?>">
                Mis pagos del plan
            </a>
            
        </nav>

        <div class="sidebar-footer">
            <div class="user-pill">
                <span class="status-dot"></span>
                <span class="user-role"><?php echo $_SESSION['rol']; ?></span>
            </div>
            
            <div>
                <hr></br>
                <a class="menu-item" href="../terminos.html">Terminos y condiciones</a>
                <a class="menu-item" href="../privacidad.html">Aviso de privacidad</a>

            </div>

        <div class="whatsapp" >
    <?php endif; ?> 
    

</aside>

