<?php 
// Contar notificaciones no leídas para el usuario actual

require_once __DIR__ . "/../data/matrix.php";


$stmtGym = $conn->prepare("SELECT * FROM gyms WHERE id = ?");
$stmtGym->execute([$_SESSION['gym_id']]);
$gymData = $stmtGym->fetch(PDO::FETCH_ASSOC);


$stmtuser = $conn->prepare("SELECT * FROM users_admin WHERE gym_id = ?");
$stmtuser->execute([$_SESSION['gym_id']]);
$uData = $stmtuser->fetch(PDO::FETCH_ASSOC);

//Sacar cantidad de notificaciones
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE gym_id = ? AND leido = 'no'");
$stmt_count->execute([$_SESSION['gym_id']]);
$notif_data = $stmt_count->fetch(PDO::FETCH_ASSOC);
$total_no_leidas = $notif_data['total'];



$hoy =  Date('Y-m-d');


?>


<link rel="stylesheet" href="../styles/style1.css">
<?php if($_SESSION['rol']=='Owner'): ?>

   <!-- SOLO ESTO SE VE SU SOY OWNER  -->
<nav class="agent-nav" >

<div class="nav-left">
    
    <div class="nav-logo">
        <div class="logo-circle">G</div>

        <div class="logo-text">
            <h2>Gym Owner |  <h7 style="color:white;"> Panel administrativo</h7> </h2>
            <span>Bienvenido owner</span>
        </div>
    </div>
</div>

 <div class="nav-right">
        <div class="agent-info">
            <div class="agent-text">
                <span style="color:white;" class="agent-name"><?php echo $_SESSION['nombre']; ?></span>
                <span class="badge st-blue" style="width: fit-content; color:ghostwhite ;" ><p>Tiempo restante: <?php echo $tiempo_restante ?></p></span>
            </div>
            <div class="agent-avatar">
                <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
            </div>


            
        </div>
        <a href="../funciones/logout.php" class="btn-logout-icon" title="Cerrar Sesión">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </a>
    </div>

</nav>

<?php elseif($_SESSION['rol']=='Manager' || $_SESSION['rol']=='Receptionist'):  ?>

<nav class="agent-nav">

    <div class="nav-logo">
        <div class="logo-circle">G</div>

        <div class="logo-text">
            <h2><?php echo $_SESSION['rol']; ?></h2>
            <span>Administrative Panel</span><br>
        </div>
    </div>

    <div class="nav-center" >

        <div class="info-card">
            <span class="label">Gym</span>
            <strong><?php echo $gymData['nombre_gym']; ?></strong>
        </div>

        <div class="info-card">
            <span class="label">Plan</span>
            <span class="system-badge"><?php echo $gymData['plan_saas']; ?></span>
        </div>

        <div class="info-card">
            <span class="label">Gym Status</span>
            <span class="badge
            <?= 
            $gymData['status']=='Active'?'success':
           ( $gymData['status']=='Pending'?'warning':
           ( $gymData['status']=='Denied'?'danger':''))
            
            ?>
            ">
                <?php echo $gymData['status']; ?>
            </span>
        </div>

        <div class="info-card">
            <span class="label">User Status</span>
            <span class="badge 
            <?= 
            $uData['status']=='1'?'success':
           ($uData['status']=='0'?'danger':'')
            ?>
            
            ">
                <?php if( $uData['status']=='1'):?> Active 
                <?php elseif($uData['status']=='0'): ?>Inactivo 
                <?php endif; ?>
            </span>
        </div>

        <div class="info-card">
            <span class="label">Approval</span>
            <span class="badge
            
            <?=
            $uData['approved']=='Aprobado'?'success':
            ($uData['approved']=='Pendiente'?'warning':
            ($uData['approved']=='Rechazado'?'danger':''))
            ?>
            
            ">
                <?php echo $uData['approved'];  ?>
            </span>
        </div>

        <div class="info-card">
            <span class="label">Tiempo restante</span>
            <span class="badge
            
            <?=
            $redondeado>4?'success':
            ($redondeado<3.9?'warning':
            ($redondeado<1?'danger':''))
            ?>
            
            ">
                <?php echo $tiempo_restante;  ?>
            </span>
        </div>
        
        <div class="info-card">
            <span class="label">Expiración</span>
            <span class="badge <?= ($gymData['expiracion'] >= $hoy ? 'success' : ($gymData['expiracion']<=$hoy ? 'danger':'')) ?>">
                <a href="suscripcion.php"><?php echo $gymData['expiracion']; ?></a>
            </span>       
        </div>
        

       
 
    </div>

    <div class="nav-right">

    

        <div class="agent-info">

            <div class="agent-text">
                <span class="agent-name" style="color: whitesmoke;">
                    David Gutierrez
                </span>

                <span class="agent-role" style="color: white;">
                    <?php echo $uData['rol'] ?>
                </span>
                
          
            </div>
                
            <a href="configuracion.php">
            <div class="agent-avatar">C</div>
            </a>
        </div>

        <a href="../funciones/logout.php" class="logout-btn">

            <svg width="20" height="20" fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                </path>

            </svg>

        </a>

    </div>

</nav>

<div class="whatsapp">
    <a href="https://wa.me/+523319059794" target="__blank" ><p>Atencion al cliente</p></a>
</div>


<div class="whatsapp auditoria">
    <a href="../funciones/auditoria.php" ><p>Actualizar</p></a>
</div>


<?php endif; ?>
<style>
    a{
        color: white;
        text-decoration: none;
    }

.whatsapp{
    width: fit-content;
    position: fixed;
    height: fit-content;
    padding: 10px;
    box-shadow: var(--shadow);
    border-radius: 20px;
    z-index: 100;
    right: 10px;
    bottom: 10px;
    background:var(--primary);
    color: white;
   cursor: pointer;
    box-shadow: 2px 2px 10px 3px #4362ee71 ;

}
.auditoria{
    right: 180px;
    bottom: 10px;
}
.whatsapp:hover{
    transform: scale(.96);
    transition: .4s;
    box-shadow: 1px 1px 10px 3px rgba(0,0,0,.40);
}



    .agent-nav{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;

    padding:16px 25px;

    background:linear-gradient(
        135deg,
        #0f172a,
        #1e293b
    );

    border-bottom:1px solid rgba(255,255,255,.08);

    box-shadow:
    0 10px 30px rgba(0,0,0,.25);

    color:white;
}

/* LOGO */

.nav-logo{
    display:flex;
    align-items:center;
    gap:15px;
}

.logo-circle{
    width:50px;
    height:50px;

    border-radius:15px;

    display:flex;
    justify-content:center;
    align-items:center;

    font-weight:700;
    font-size:22px;

    background:linear-gradient(
        135deg,
        #3b82f6,
        #06b6d4
    );
}

.logo-text h2{
    margin:0;
    font-size:20px;
}

.logo-text span{
    font-size:12px;
    opacity:.7;
}

/* CENTRO */

.nav-center{
    display:flex;
    gap:12px;
    flex-direction: row;
    scrollbar-width: auto;
}

.info-card{
    min-width:140px;

    display:flex;
    flex-direction:column;
    gap:4px;

    padding:12px 15px;

    border-radius:14px;

    background:
    rgba(255,255,255,.05);

    border:1px solid rgba(255,255,255,.08);

    backdrop-filter:blur(8px);
}

.label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:1px;
    opacity:.95;
    color: white;
}

.info-card strong{
    font-size:14px;
    color:white;
}

/* STATUS BADGES */

.badge{
    width:fit-content;

    padding:6px 12px;

    border-radius:999px;

    font-size:12px;
    font-weight:700;
}

.success{
    background:#22c55e20;
    color:#4ade80;
    border:1px solid #22c55e;
}

.warning{
    background:#f59e0b20;
    color:#fbbf24;
    border:1px solid #f59e0b;
}

.danger{
    background:#ef444420;
    color:#f87171;
    border:1px solid #ef4444;
}

/* DERECHA */

.nav-right{
    display:flex;
    align-items:center;
    gap:15px;

}

.agent-info{
    display:flex;
    align-items:center;
    gap:12px;
}

.agent-text{
    display:flex;
    flex-direction:column;
    text-align:right;
    
    
}

.agent-name{
    font-weight:700;
    color: white;
}

.agent-role{
    font-size:12px;
    opacity:.9;
}

.agent-avatar{
    width:48px;
    height:48px;

    border-radius:50%;

    display:flex;
    justify-content:center;
    align-items:center;

    font-weight:700;

    background:linear-gradient(
        135deg,
        #2563eb,
        #7c3aed
    );

    box-shadow:
    0 0 18px rgba(59,130,246,.35);
}

/* SOPORTE */

.support-btn{
    text-decoration:none;
    color:white;

    padding:10px 16px;

    border-radius:12px;

    background:linear-gradient(
        135deg,
        #22c55e,
        #16a34a
    );

    font-weight:600;

    transition:.25s;
}



/* LOGOUT */

.logout-btn{
    width:45px;
    height:45px;

    display:flex;
    justify-content:center;
    align-items:center;

    border-radius:12px;

    background:#ef4444;
    color:white;

    transition:.25s;
}

.logout-btn:hover{
    background:#dc2626;
    transform:translateY(-2px);
}

/* RESPONSIVE */

@media (max-width:1200px){

    .agent-nav{
        flex-direction:column;
        align-items:stretch;
    }

    .nav-center{
        justify-content:center;
    }

    .nav-right{
        justify-content:space-between;
    }
}

@media (max-width:768px){

    .nav-center{
        flex-direction:column;
    }

    .info-card{
        width:100%;
    }

    .nav-right{
        flex-wrap:wrap;
    }

    .agent-info{
        width:100%;
        justify-content:space-between;
    }
}
</style>