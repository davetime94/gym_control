<?php
require_once __DIR__ . '/../data/conn.php';
require_once __DIR__ . '/../components/security.php';
require_once __DIR__ . '/../funciones/matrix.php';
$mensaje = "";

// --- LÓGICA DE PROCESAMIENTO (Mantenida intacta) ---
if(isset($_POST['crear'])){
    $gym_id = $_SESSION['gym_id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $status = 1;
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try{
        $stmt = $conn->prepare("INSERT INTO users_admin (gym_id, nombre, email, password, rol, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$gym_id, $nombre, $email, $password_hash, $rol, $status]);

        $log = $conn->prepare("INSERT INTO logs (quien, descripcion, user_id, gym_id, tipo) VALUES (?, ?, ?, ?, ?)");
        $log->execute([$_SESSION['nombre'], "Creó usuario: $nombre ($rol)", $_SESSION['user_id'], $_SESSION['gym_id'], "USER_CREATE"]);
        $mensaje = "✅ Usuario creado correctamente";
    }catch(PDOException $e){
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}

if(isset($_POST['eliminar'])){
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users_admin WHERE id = ? AND gym_id = ?");
    $stmt->execute([$id, $_SESSION['gym_id']]);

    $log = $conn->prepare("INSERT INTO logs (quien, descripcion, user_id, gym_id, tipo) VALUES (?, ?, ?, ?, ?)");
    $log->execute([$_SESSION['nombre'], "Eliminó usuario ID: $id", $_SESSION['user_id'], $_SESSION['gym_id'], "USER_DELETE"]);
    $mensaje = "🗑️ Usuario eliminado";
}

if(isset($_POST['editar'])){
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $rol = $_POST['rol'];
    $status = $_POST['status'];

    try{
    if(!empty($_POST['password'])){
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users_admin SET nombre=?, email=?, password=?, rol=?, status=? WHERE id=? AND gym_id=?");
        $stmt->execute([$nombre, $email, $password_hash, $rol, $status, $id, $_SESSION['gym_id']]);
    }else{
        $stmt = $conn->prepare("UPDATE users_admin SET nombre=?, email=?, rol=?, status=? WHERE id=? AND gym_id=?");
        $stmt->execute([$nombre, $email, $rol, $status, $id, $_SESSION['gym_id']]);
    }} catch(PDOException $e){
        $mensaje="Error: " . $e ->getMessage();
    }

    $log = $conn->prepare("INSERT INTO logs (quien, descripcion, user_id, gym_id, tipo) VALUES (?, ?, ?, ?, ?)");
    $log->execute([$_SESSION['nombre'], "Editó usuario ID: $id", $_SESSION['user_id'], $_SESSION['gym_id'], "USER_UPDATE"]);
    $mensaje = "✏️ Usuario actualizado";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal | Horizon Gym</title>
    <link rel="stylesheet" href="../styles/style1.css">
    <style>
        :root {
            --primary: #4361ee;
            --success: #05cd99;
            --danger: #ee5d50;
            --text-main: #2b3674;
            --text-muted: #a3aed0;
            --bg-card: #ffffff;
        }

        body { background-color: #f4f7fe; font-family: 'Inter', sans-serif; color: var(--text-main); }
        main { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .content { padding: 40px; }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-flex h2 { font-weight: 800; font-size: 1.8rem; margin: 0; }

        /* Estilo Alertas */
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; animation: fadeIn 0.4s ease; }
        .alert-info { background: #eef2ff; color: var(--primary); border: 1px solid #4361ee30; }

        /* Tarjeta de Creación */
        .create-card { 
            background: var(--bg-card); 
            border-radius: 20px; 
            padding: 25px; 
            box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08); 
            margin-bottom: 30px;
        }
        .create-card h3 { margin-top: 0; font-size: 1.1rem; margin-bottom: 20px; color: var(--text-main); }
        .form-inline { display: flex; gap: 15px; flex-wrap: wrap; }
        
        /* Inputs Estilizados */
        input, select {
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid #e0e5f2;
            background: #f4f7fe;
            color: var(--text-main);
            font-size: 0.9rem;
            outline: none;
            transition: 0.3s;
        }
        input:focus, select:focus { border-color: var(--primary); background: #fff; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.1); }

        /* Tabla Estilizada */
        .table-card { 
            background: var(--bg-card); 
            border-radius: 20px; 
            padding: 20px; 
            box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08); 
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; border-bottom: 1px solid #f4f7fe; }
        td { padding: 12px 15px; border-bottom: 1px solid #f4f7fe; }
        
        /* Botones */
        .btn { padding: 10px 20px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 0.85rem; }
        .btn-create { background: var(--primary); color: white; }
        .btn-save { background: var(--success); color: white; }
        .btn-delete { background: #fff; color: var(--danger); border: 1px solid #ee5d5030; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .status-badge { padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<?php include("../components/navbar.php"); ?>

<main>
    <aside>
        <?php include("../components/sidebar.php"); ?>
    </aside>

    <section class="content">
        <?php if($resultado_plan['status']!='Active'): ?>

            <div class="card" > <?php include("../components/pendiente.php"); ?> </div>

            <?php elseif($resultado_plan['status']=='Active'): ?>


            <div class="header-flex">
                <h2>Administrar Personal</h2>
                <span style="color: var(--text-muted); font-weight: 600;">ID Gym: #<?php echo $_SESSION['gym_id']; ?></span>
            </div>

            <?php if($mensaje): ?>
                <div class="alert alert-info"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <!-- ➕ SECCIÓN CREAR USUARIO -->


            <!-- 📊 LISTADO DE PERSONAL -->

            <?php if($_SESSION['rol']=='Manager'): ?>
            <div class="create-card">
                <h3>Registrar nuevo colaborador</h3>
                <form method="POST" class="form-inline">
                    <input type="text" name="nombre" placeholder="Nombre completo" required style="flex: 2;">
                    <input type="email" name="email" placeholder="Correo electrónico" required style="flex: 2;">
                    <input type="text" name="password" placeholder="Contraseña temporal" required style="flex: 1;">
                    
                    <select name="rol" style="flex: 1;">
                        <option value="Receptionist">Recepcionista</option>
                        <option value="Manager">Manager</option>
                    </select>

                    <button name="crear" class="btn btn-create">Crear Usuario</button>
                </form>
            </div>
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre y Datos</th>
                                <th>Rol</th>
                                <th>Estatus del Sistema</th>
                                <th>Acciones / Seguridad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query filtrando para no mostrar al Owner actual en la lista editable (seguridad)
                            $stmt = $conn->prepare("SELECT * FROM users_admin WHERE gym_id = ? AND (rol = 'Manager' OR rol = 'Receptionist') ORDER BY id DESC");
                            $stmt->execute([$_SESSION['gym_id']]);

                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    
                                    <td style="font-weight: 700; color: var(--text-muted);">#<?php echo $row['id']; ?></td>
                                    
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <input type="text" name="nombre" value="<?php echo $row['nombre']; ?>" style="font-weight: 700; padding: 8px;">
                                            <input type="email" name="email" value="<?php echo $row['email']; ?>" style="font-size: 0.8rem; padding: 8px;">
                                        </div>
                                    </td>

                                    <td>
                                        <select name="rol">
                                            <option value="Receptionist" <?php if($row['rol']=="Receptionist") echo "selected"; ?>>Recepcionista</option>
                                            <option value="Manager" <?php if($row['rol']=="Manager") echo "selected"; ?>>Manager</option>
                                        </select>
                                    </td>

                                    <td>
                                        <select name="status">
                                            <option value="1" <?php if($row['status']==1) echo "selected"; ?>>🟢 Activo</option>
                                            <option value="0" <?php if($row['status']==0) echo "selected"; ?>>🔴 Inactivo</option>
                                        </select>
                                    </td>

                                    <td>
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <input type="text" name="password" placeholder="Nueva pass" style="width: 110px; font-size: 0.8rem; padding: 8px;">
                                            <button name="editar" class="btn btn-save" title="Guardar Cambios">Guardar</button>
                                            <button name="eliminar" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar este acceso?')">Eliminar</button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif($_SESSION['rol']!='Manager'): ?>
                                    <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre y Datos</th>
                                <th>Rol</th>
                                <th>Estatus del Sistema</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query filtrando para no mostrar al Owner actual en la lista editable (seguridad)
                            $stmt = $conn->prepare("SELECT * FROM users_admin WHERE gym_id = ? AND (rol = 'Manager' OR rol = 'Receptionist') ORDER BY id DESC");
                            $stmt->execute([$_SESSION['gym_id']]);

                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <form method="POST">
                                    <small type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    
                                    <td style="font-weight: 700; color: var(--text-muted);">#<?php echo $row['id']; ?></td>
                                    
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <p><?php echo htmlspecialchars($row['nombre']); ?></p>
                                            <p><?php echo htmlspecialchars($row['email']); ?></p>
                                        </div>
                                    </td>

                                    <td>
                                        <p><?php echo htmlspecialchars($row['rol']); ?></p>
                                    </td>

                                    <td>
                                        <span class="badge <?= ($row['status']=='1'?'success':'danger') ?>"><?php if($row['status']=="1"){ echo "Activo";}else{echo "Inactivo/pausado";} ?></span>
                                    </td>


                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </section>
</main>

</body>
</html>