<style>
    .badge {
        border-radius: 20px;
        padding: 4px 10px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }
</style>
<div style="max-height: 800px; overflow-y: auto; width: 100%;" class="content_information">

    <!-- 🔍 SECCIÓN DE FILTROS -->
    <div style="margin-bottom:25px; display:flex; gap:12px; flex-wrap:wrap; align-items: center; padding: 10px;">
        <input type="text" id="buscador" placeholder="Buscar por nombre o email...">

        <select id="filtroStatus">
            <option value="">Estatus: Todos</option>
            <option value="Activa">Activa</option>
            <option value="Vencida">Vencida</option>
            <option value="Cancelada">Cancelada</option>
        </select>

        <input type="number" id="filtroGym" placeholder="Gym ID" style="width:100px;">

        <div style="display: flex; gap: 5px; align-items: center;">
            <small style="color: var(--text-muted);">Desde:</small>
            <input type="date" id="fechaDesde">
        </div>
        
        <div style="display: flex; gap: 5px; align-items: center;">
            <small style="color: var(--text-muted);">Hasta:</small>
            <input type="date" id="fechaHasta">
        </div>

        <button onclick="limpiarFiltros()" class="btn-search" style="width: auto; padding: 10px 20px; margin-top:0;">
            Limpiar Filtros
        </button>

        <span id="contador" style="margin-left:auto; color: var(--text-main); font-weight:700; font-size: 0.9rem;"></span>
    </div>

    <!-- 📊 TABLA DE MIEMBROS -->
    <div class="card" style="width: 100%; overflow-x: auto;">
        <table id="tablaMiembros">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gym ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Huella</th>
                    <th>Status</th>
                    <th>Registro</th>
                    <th>Vencimiento</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $gym_id = $_SESSION['gym_id'];
                $stmt = $conn->prepare("SELECT * FROM members WHERE gym_id = ?");
                $stmt->execute([$gym_id]);
                $miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php foreach ($miembros as $row): ?>
                    <?php 
                        $statusColor = "#05cd99"; // Verde (Activa)
                        if ($row['status_licencia'] === "Vencida") {
                            $statusColor = "#f86e38"; // Naranja
                        } elseif ($row['status_licencia'] === "Cancelada") {
                            $statusColor = "#ee5d50"; // Rojo
                        }

                        $huellaVal = $row['huella'] ?? $row['huella_digital_id'] ?? null;
                        $tieneHuella = ($huellaVal !== null && $huellaVal !== '');
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--text-main);">#<?= htmlspecialchars($row['id']) ?></td>
                        <td class="gym"><?= htmlspecialchars($row['gym_id']) ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['telefono']) ?></td>
                        <td>
                            <span >
                            <span  style="width:fit-content;" ><?= htmlspecialchars($row['huella'] ?? 'Sin huella')?></span>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: <?= $statusColor ?>20; color: <?= $statusColor ?>;">
                                <?= htmlspecialchars($row['status_licencia']) ?>
                            </span>
                        </td>
                        <td class="fecha"><?= htmlspecialchars($row['created_at']) ?></td>
                        <td><?= htmlspecialchars($row['fecha_vencimiento']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['buscador', 'filtroStatus', 'filtroLicencia', 'filtroGym', 'fechaDesde', 'fechaHasta'];
    const refs = {};
    inputs.forEach(id => refs[id] = document.getElementById(id));
    
    const tabla = document.getElementById('tablaMiembros').getElementsByTagName('tbody')[0];
    const filas = tabla.getElementsByTagName('tr');
    const contador = document.getElementById('contador');

    function filtrar() {
        let visibles = 0;
        const busqueda = refs.buscador.value.toLowerCase();
        
        for (let i = 0; i < filas.length; i++) {
            const celdas = filas[i].getElementsByTagName('td');
            if (!celdas || celdas.length < 9) continue;
            
            const txtNombre = celdas[2].textContent.toLowerCase();
            const txtEmail = celdas[3].textContent.toLowerCase();
            const txtGym = celdas[1].textContent;
            const txtStatus = celdas[6].textContent.trim();
            const fechaReg = new Date(celdas[7].textContent);

            const matchTexto = txtNombre.includes(busqueda) || txtEmail.includes(busqueda);
            const matchStatus = !refs.filtroStatus.value || txtStatus === refs.filtroStatus.value;
            const matchGym = !refs.filtroGym.value || txtGym === refs.filtroGym.value;
            
            let matchFecha = true;
            if (refs.fechaDesde.value && fechaReg < new Date(refs.fechaDesde.value)) matchFecha = false;
            if (refs.fechaHasta.value && fechaReg > new Date(refs.fechaHasta.value)) matchFecha = false;

            if (matchTexto && matchStatus && matchGym && matchFecha) {
                filas[i].style.display = "";
                visibles++;
            } else {
                filas[i].style.display = "none";
            }
        }
        contador.textContent = `Mostrando: ${visibles} miembros`;
    }

    // Eventos
    inputs.forEach(id => refs[id].addEventListener('input', filtrar));

    window.limpiarFiltros = function() {
        inputs.forEach(id => refs[id].value = "");
        filtrar();
    };

    filtrar(); // Inicializar
});
</script>