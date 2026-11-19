<div class="content_information">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <div>
            <h1 style="color: var(--text-main); font-weight: 700;">Bitácora de Actividades (Logs)</h1><br>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Sesión de: <b><?php echo $_SESSION['nombre']; ?></b> | <span class="system-badge"><?php echo $_SESSION['rol']; ?></span>
            </p>
        </div>
        <span id="contador" style="color: var(--primary); font-weight: 700; font-size: 0.9rem;"></span>
    </div>

    <!-- 🔥 FILTROS PRO -->
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin:20px 0; align-items: center;">
        <input type="text" id="buscador" placeholder="Buscar en descripción..." style="flex: 1; min-width: 200px;">

        <input type="text" id="filtroUsuario" placeholder="Nombre usuario..." style="width: 180px;">

        <input type="number" id="filtroUserID" placeholder="User ID" style="width: 100px;">

        <select id="filtroAccion" style="width: 180px;">
            <option value="">Todas las acciones</option>
            <option value="Añadió">Añadió</option>
            <option value="Eliminó">Eliminó</option>
            <option value="Editó">Editó</option>
            <option value="UPDATE">UPDATE</option>
            <option value="DELETE">DELETE</option>
            <option value="INSERT">INSERT</option>
        </select>

        <div style="display: flex; gap: 8px; align-items: center;">
            <small style="color: var(--text-muted);">Desde:</small>
            <input type="date" id="fechaDesde">
        </div>

        <div style="display: flex; gap: 8px; align-items: center;">
            <small style="color: var(--text-muted);">Hasta:</small>
            <input type="date" id="fechaHasta">
        </div>

        <button onclick="limpiarFiltros()" class="btn-search" style="width: auto; padding: 10px 20px; margin-top:0; background: #ee5d50;">
            Limpiar
        </button>
    </div>

    <div class="card" style="width: 100%; overflow-x: auto;">
        <table id="tablaLogs" style="width:100%;">
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Usuario</th>
                    <th>Descripción</th>
                    <th>ID Usuario</th>
                    <th>Gym ID</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stmt = $conn->prepare("SELECT * FROM logs WHERE gym_id = ? ORDER BY fecha_hora DESC");
                $stmt->execute([$_SESSION['gym_id']]);

                while($row = $stmt->fetch(PDO::FETCH_ASSOC)){ 
                    // Color para el tipo de acción
                    $tipoColor = "#a3aed0"; // Default
                    if(stripos($row['tipo'], 'DELETE') !== false || stripos($row['tipo'], 'Eliminó') !== false) $tipoColor = "#ee5d50";
                    if(stripos($row['tipo'], 'UPDATE') !== false || stripos($row['tipo'], 'Editó') !== false) $tipoColor = "#4361ee";
                    if(stripos($row['tipo'], 'INSERT') !== false || stripos($row['tipo'], 'Añadió') !== false) $tipoColor = "#05cd99";

                    echo "<tr>
                        <td class='fecha' style='color: var(--text-muted); font-size: 0.8rem;'>{$row['fecha_hora']}</td>
                        <td class='usuario' style='font-weight: 600; color: var(--text-main);'>{$row['quien']}</td>
                        <td class='descripcion'>{$row['descripcion']}</td>
                        <td class='userid' style='font-weight: 700;'>#{$row['user_id']}</td>
                        <td>{$row['gym_id']}</td>
                        <td>
                            <span class='badge' style='background: {$tipoColor}15; color: {$tipoColor};'>
                                {$row['tipo']}
                            </span>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referencias de los inputs
    const ids = ['buscador', 'filtroUsuario', 'filtroUserID', 'fechaDesde', 'fechaHasta', 'filtroAccion'];
    const el = {};
    ids.forEach(id => el[id] = document.getElementById(id));
    
    const tabla = document.getElementById('tablaLogs').getElementsByTagName('tbody')[0];
    const filas = tabla.getElementsByTagName('tr');
    const contador = document.getElementById('contador');

    function filtrarLogs() {
        let visibles = 0;
        const busqueda = el.buscador.value.toLowerCase();
        const userBusqueda = el.filtroUsuario.value.toLowerCase();
        const userIdBusqueda = el.filtroUserID.value;
        const accionBusqueda = el.filtroAccion.value;
        
        const fDesde = el.fechaDesde.value ? new Date(el.fechaDesde.value) : null;
        const fHasta = el.fechaHasta.value ? new Date(el.fechaHasta.value) : null;

        for (let i = 0; i < filas.length; i++) {
            const celdas = filas[i].getElementsByTagName('td');
            
            const txtFechaRaw = celdas[0].textContent;
            const txtFecha = new Date(txtFechaRaw);
            const txtUsuario = celdas[1].textContent.toLowerCase();
            const txtDesc = celdas[2].textContent.toLowerCase();
            const txtUserID = celdas[3].textContent.replace('#', '');
            const txtAccion = celdas[5].textContent.trim();

            // Lógica de coincidencia
            const mDesc = txtDesc.includes(busqueda);
            const mUser = txtUsuario.includes(userBusqueda);
            const mUserID = !userIdBusqueda || txtUserID === userIdBusqueda;
            const mAccion = !accionBusqueda || txtAccion.includes(accionBusqueda);
            
            let mFecha = true;
            if (fDesde) {
                const d = new Date(fDesde);
                d.setHours(0,0,0,0);
                if (txtFecha < d) mFecha = false;
            }
            if (fHasta) {
                const h = new Date(fHasta);
                h.setHours(23,59,59,999);
                if (txtFecha > h) mFecha = false;
            }

            if (mDesc && mUser && mUserID && mAccion && mFecha) {
                filas[i].style.display = "";
                visibles++;
            } else {
                filas[i].style.display = "none";
            }
        }
        contador.textContent = `${visibles} registros encontrados`;
    }

    // Agregar eventos a todos los filtros
    ids.forEach(id => el[id].addEventListener('input', filtrarLogs));

    // Función para limpiar
    window.limpiarFiltros = function() {
        ids.forEach(id => el[id].value = "");
        filtrarLogs();
    };

    filtrarLogs(); // Ejecución inicial
});
</script>