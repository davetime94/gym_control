<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';

$mensaje = "";
$gym_id = $_SESSION['gym_id'];
$user_id = $_SESSION['user_id'];

// 1. VERIFICAR SI TIENE TICKETS PENDIENTES
// Solo permitimos crear si TODOS están como 'Resuelto'
$stmtCheck = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE gym_id = ? AND status != 'Completado'  ");
$stmtCheck->execute([$gym_id]);
$ticketsAbiertos = $stmtCheck->fetchColumn();

$puedeCrear = ($ticketsAbiertos == 0);

// 2. PROCESAR CREACIÓN DE TICKET
if (isset($_POST['crear_ticket']) && $puedeCrear) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['description']);
    $whatsapp = trim($_POST['numero_whatsapp']);
    $status="Pendiente";
    $gym_id = $_SESSION['gym_id'];
    $notas="";


    try {
        $stmt = $conn->prepare("
            INSERT INTO tickets (
            titulo, 
            description,
            status,
            gym_id,
            numero_whatsapp,
            notas) 
            VALUES 
            (?, ?, ?,?,?,?)
        ");
        $stmt->execute([$titulo, $descripcion, $status, $gym_id,  $whatsapp,  $notas]);
        
        $mensaje = "✅ Ticket creado correctamente. Nuestro equipo lo revisará pronto.";
        // Recargamos para bloquear el formulario
        $puedeCrear = false; 
    } catch (PDOException $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Soporte Técnico | Horizon Gym</title>
    <style>
        :root {
            --primary: #4361ee;
            --success: #05cd99;
            --warning: #ffb547;
            --text-main: #2b3674;
            --text-muted: #a3aed0;
        }

        body { background: #f4f7fe; font-family: 'Inter', sans-serif; color: var(--text-main); }
        main { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .container { padding: 40px; }

        .grid-layout { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }

        /* Estilo de Tarjetas de Ticket */
        .ticket-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(224, 229, 242, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .bg-pendiente { background: #fff5e6; color: #ffb547; }
        .bg-resuelto { background: #e6fff9; color: #05cd99; }

        /* Formulario */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.1);
            height: fit-content;
            position: sticky;
            top: 40px;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; }
        
        input, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #e0e5f2;
            background: #f4f7fe;
            outline: none;
            box-sizing: border-box;
        }

        .btn-crear {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-crear:disabled {
            background: #e0e5f2;
            cursor: not-allowed;
            color: var(--text-muted);
        }

        .lock-msg {
            background: #fff5f5;
            color: #ee5d50;
            padding: 15px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-top: 15px;
            border: 1px solid #ee5d5020;
        }
    </style>
</head>
<body>

<?php include("../components/navbar.php"); ?>

<main>
    <aside><?php include("../components/sidebar.php"); ?></aside>

    <section class="container">
        <div class="header" style="margin-bottom: 30px;">
            <h2 style="font-weight: 800; font-size: 1.8rem; margin: 0;">Soporte y Tickets</h2>
            <p style="color: var(--text-muted);">Gestiona tus solicitudes de ayuda</p>
        </div>

        <?php if($mensaje) echo "<p style='padding:15px; background:white; border-radius:15px; color:var(--primary); font-weight:700;'>$mensaje</p>"; ?><br>

        <div class="grid-layout">
            
            <!-- 📜 LISTADO DE TICKETS -->
            <div class="tickets-list">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px;">Mis Tickets Recientes</h3>
                
                <?php
                $stmt = $conn->prepare("SELECT * FROM tickets WHERE gym_id = ? ORDER BY created_at DESC");
                $stmt->execute([$gym_id]);
                $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($tickets) > 0):
                    foreach ($tickets as $t):
                        $claseStatus = ($t['status'] == 'Completado') ? 'bg-resuelto' : 'bg-pendiente';
                ?>
                    <div class="ticket-card">
                        <div>
                            <span class="status-badge <?php echo $claseStatus; ?>"><?php echo $t['status']; ?></span>
                            <h4 style="margin: 10px 0 5px 0; font-size: 1rem;"><?php echo $t['titulo']; ?></h4>
                            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);"><?php echo $t['description']; ?></p>
                            <small style="color: var(--text-muted); display: block; margin-top: 10px;">
                                📅 <?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?>
                            </small>
                        </div>
                        <div style="text-align: right;">
                            <a href="https://wa.me/<?php echo $t['numero_whatsapp']; ?>" target="_blank" style="text-decoration:none; font-size: 1.2rem;">💬</a>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                else:
                    echo "<p style='color:var(--text-muted);'>No tienes tickets registrados aún.</p>";
                endif; 
                ?>
            </div>

            <!-- ➕ FORMULARIO DE CREACIÓN -->
            <div class="form-card">
                <h3>Nuevo Ticket</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 20px;">
                    Solo puedes tener un ticket activo a la vez.
                </p>
                <br>

                <form method="POST">
                    <div class="form-group">
                        <label>Asunto / Título</label>
                        <input type="text" name="titulo" placeholder="Ej: Problema con el acceso" required <?php echo !$puedeCrear ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label>Descripción detallada</label>
                        <textarea name="description" rows="4" placeholder="Explica lo que sucede..." required <?php echo !$puedeCrear ? 'disabled' : ''; ?>></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tu WhatsApp</label>
                        <input type="text" name="numero_whatsapp" value="<?php echo $userData['telefono'] ?? ''; ?>" placeholder="52..." required <?php echo !$puedeCrear ? 'disabled' : ''; ?>>
                    </div>

                    <button type="submit" name="crear_ticket" class="btn-crear" <?php echo !$puedeCrear ? 'disabled' : ''; ?>>
                        Enviar Solicitud
                    </button>

                    <?php if (!$puedeCrear): ?>
                        <div class="lock-msg">
                            <b>Formulario bloqueado:</b> Tienes un ticket pendiente de resolución. Por favor, espera a que el equipo de soporte lo resuelva para crear uno nuevo.
                        </div>
                    <?php endif; ?>
                </form>
            </div>

        </div>
    </section>
</main>

</body>
</html>