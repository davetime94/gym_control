<?php
// Obtenemos el nombre del archivo actual para la clase 'active'
$pagina_actual = basename($_SERVER['PHP_SELF']);



$stmtGym = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
$stmtGym->execute([$_SESSION['gym_id']]);
$gymData = $stmtGym->fetch(PDO::FETCH_ASSOC);


?>

<link rel="stylesheet" href="../styles/style1.css">
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

<!-- TERMINA EL IF DE PHP -->
<style>
    :root {
    --side-bg: #111c44;
    --side-hover: rgba(255, 255, 255, 0.08);
    --side-active: #4361ee;
    --text-muted: #a3aed0;
}

.sidebar-crm {
    height: 100%;
    background-color: var(--side-bg);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    padding: 20px;
    color: white;
}

.sidebar-header {
    padding: 20px 10px;
    margin-bottom: 30px;
}

.logo-text {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--side-active);
}

.logo-text span {
    color: white;
    font-weight: 300;
}

.menu-category {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 1.5px;
    font-weight: 700;
    margin: 25px 0 10px 15px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 12px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    margin-bottom: 4px;
}

.menu-item:hover {
    background-color: var(--side-hover);
    color: white;
    transform: translateX(5px);
}

.menu-item.active {
    background-color: var(--side-active);
    color: white;
    box-shadow: 0px 10px 20px rgba(67, 97, 238, 0.3);
}

.sidebar-footer {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.05);
    padding: 8px 15px;
    border-radius: 30px;
    margin-bottom: 15px;
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #00f2c3;
    border-radius: 50%;
    box-shadow: 0 0 10px #00f2c3;
}

.btn-exit {
    color: #ff5f5f;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: block;
    text-align: center;
}



@keyframes mover {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.08); }
    100% { transform: scale(1); }
}

.animation_alert{
animation: mover 0.8s ease-in-out infinite;}

</style>
