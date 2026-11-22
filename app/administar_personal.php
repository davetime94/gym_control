<?php
    require_once __DIR__ . '/../data/conn.php';
    require_once __DIR__ . '/../data/matrix.php';

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
            $mensaje = "Error: " . $e->getMessage();
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
    <link rel="stylesheet" href="../styles/style1.css?v=<?= time(); ?>">
</head>
<body>

<?php include("../components/navbar.php"); ?>

<main>
    <aside>
        <?php include("../components/sidebar.php"); ?>
    </aside>

    <section class="content_section">
        <?php if($resultado_plan['status']!='Active'): ?>

            <div class="card" > <?php include("../components/pendiente.php"); ?> </div>

            <?php elseif($resultado_plan['status']=='Active'): ?>

            <div class="card" style="display:flex; flex-direction: column;">
                <div class="header-flex">
                    <h2>Administrar Personal</h2>
                    <span style="color: var(--text-muted); font-weight: 600;">ID Gym: #<?php echo $_SESSION['gym_id']; ?></span>
                </div>

                <?php if($mensaje): ?>
                    <div class="alert alert-info"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <?php if($_SESSION['rol']=='Manager'): ?>
                
                    <div class="card" style="display:flex; flex-direction:column; gap:10px;">
                        <div>
                            <h3>Registrar nuevo colaborador</h3>
                        </div>
                        <div>
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
                    </div>

                    <div class="card">
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

                                while($row = $stmt_personal->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <form id="form-user-<?php echo $row['id']; ?>" method="POST"></form>
                                    <input type="hidden" form="form-user-<?php echo $row['id']; ?>" name="id" value="<?php echo $row['id']; ?>">
                                    
                                    <td style="font-weight: 700; color: var(--text-muted);">#<?php echo $row['id']; ?></td>
                                    
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <input type="text" form="form-user-<?php echo $row['id']; ?>" name="nombre" value="<?php echo $row['nombre']; ?>" style="font-weight: 700; padding: 8px;">
                                            <input type="email" form="form-user-<?php echo $row['id']; ?>" name="email" value="<?php echo $row['email']; ?>" style="font-size: 0.8rem; padding: 8px;">
                                        </div>
                                    </td>

                                    <td>
                                        <select form="form-user-<?php echo $row['id']; ?>" name="rol">
                                            <option value="Receptionist" <?php if($row['rol']=="Receptionist") echo "selected"; ?>>Recepcionista</option>
                                            <option value="Manager" <?php if($row['rol']=="Manager") echo "selected"; ?>>Manager</option>
                                        </select>
                                    </td>

                                    <td>
                                        <select form="form-user-<?php echo $row['id']; ?>" name="status">
                                            <option value="1" <?php if($row['status']==1) echo "selected"; ?>>🟢 Activo</option>
                                            <option value="0" <?php if($row['status']==0) echo "selected"; ?>>🔴 Inactivo</option>
                                        </select>
                                    </td>

                                    <td>
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <input type="text" form="form-user-<?php echo $row['id']; ?>" name="password" placeholder="Nueva pass" style="width: 110px; font-size: 0.8rem; padding: 8px;">
                                            <button form="form-user-<?php echo $row['id']; ?>" name="editar" class="btn btn-save" title="Guardar Cambios">Guardar</button>
                                            <button form="form-user-<?php echo $row['id']; ?>" name="eliminar" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar este acceso?')">Eliminar</button>
                                        </div>
                                    </td>
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
            </div>

        <?php endif; ?>

    </section>
</main>

</body>
</html>