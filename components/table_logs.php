<!-- Filtros Superiores usando clase centralizada .filtros -->
<div class="filtros" style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; align-items: center;">
    <input type="text" id="buscador" placeholder="🔍 Buscar en descripción..." style="flex: 2; min-width: 200px;">
    <input type="text" id="filtroUsuario" placeholder="👤 Usuario..." style="flex: 1; min-width: 140px;">
    <input type="number" id="filtroUserID" placeholder="# ID" style="width: 95px;">
    
    <select id="filtroAccion" style="flex: 1; min-width: 160px;">
        <option value="">Todas las acciones</option>
        <option value="Añadió">Añadió (INSERT)</option>
        <option value="Eliminó">Eliminó (DELETE)</option>
        <option value="Editó">Editó (UPDATE)</option>
        <option value="UPDATE">UPDATE</option>
        <option value="DELETE">DELETE</option>
        <option value="INSERT">INSERT</option>
    </select>

    <input type="date" id="fechaDesde" title="Fecha inicial" style="width: 140px;">
    <input type="date" id="fechaHasta" title="Fecha final" style="width: 140px;">

    <button type="button" onclick="limpiarFiltros()" class="btn btn-outline" style="padding: 10px 16px;">
        🔄 Limpiar
    </button>

    <button type="button" onclick="exportarExcel()" class="btn btn-save" style="padding: 10px 18px;">
        📊 Exportar a Excel
    </button>
</div>

<!-- Tabla de Logs con .master-table centralizada -->
<div style="width: 100%; overflow-x: auto;">
    <table id="tablaLogs" class="master-table">
        <thead>
            <tr>
                <th style="width: 170px;">Fecha y Hora</th>
                <th>Usuario</th>
                <th>Descripción</th>
                <th style="text-align: center; width: 100px;">ID Usuario</th>
                <th style="text-align: center; width: 90px;">Gym ID</th>
                <th style="text-align: center; width: 130px;">Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stmt = $conn->prepare("SELECT * FROM logs WHERE gym_id = ? ORDER BY fecha_hora DESC");
            $stmt->execute([$_SESSION['gym_id']]);

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){ 
                // Categorías visuales centralizadas (.cat-badge)
                $badgeClass = "cat-badge cat-secondary";
                if(stripos($row['tipo'], 'DELETE') !== false || stripos($row['tipo'], 'Eliminó') !== false) {
                    $badgeClass = "cat-badge cat-danger";
                } elseif(stripos($row['tipo'], 'UPDATE') !== false || stripos($row['tipo'], 'Editó') !== false) {
                    $badgeClass = "cat-badge cat-primary";
                } elseif(stripos($row['tipo'], 'INSERT') !== false || stripos($row['tipo'], 'Añadió') !== false) {
                    $badgeClass = "cat-badge cat-success";
                }
            ?>
            <tr>
                <td class="fecha" data-fecha="<?php echo $row['fecha_hora']; ?>">
                    <span style="color: var(--text-muted); font-size: 0.82rem; font-weight: 600;">
                        🕒 <?php echo date('d/m/Y H:i:s', strtotime($row['fecha_hora'])); ?>
                    </span>
                </td>
                <td class="usuario">
                    <strong style="color: var(--text-main);"><?php echo htmlspecialchars($row['quien']); ?></strong>
                </td>
                <td class="descripcion" style="color: var(--text-main);">
                    <?php echo htmlspecialchars($row['descripcion']); ?>
                </td>
                <td class="userid" style="text-align: center;">
                    <span class="ticket-id" style="font-family: monospace; font-size: 0.9rem;">#<?php echo htmlspecialchars($row['user_id']); ?></span>
                </td>
                <td style="text-align: center;">
                    <span style="color: var(--text-muted); font-size: 0.85rem;">#<?php echo htmlspecialchars($row['gym_id']); ?></span>
                </td>
                <td style="text-align: center;">
                    <span class="<?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($row['tipo']); ?>
                    </span>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
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
            if (celdas.length < 6) continue;
            
            const txtFechaRaw = celdas[0].getAttribute('data-fecha') || celdas[0].textContent;
            const txtFecha = new Date(txtFechaRaw);
            const txtUsuario = celdas[1].textContent.toLowerCase();
            const txtDesc = celdas[2].textContent.toLowerCase();
            const txtUserID = celdas[3].textContent.replace('#', '').trim();
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
        if (contador) {
            contador.textContent = `${visibles} registros encontrados`;
        }
    }

    // Agregar eventos a todos los filtros
    ids.forEach(id => el[id].addEventListener('input', filtrarLogs));

    // Función para limpiar filtros
    window.limpiarFiltros = function() {
        ids.forEach(id => el[id].value = "");
        filtrarLogs();
    };

    // Función para exportar a Excel con filtros activos
    window.exportarExcel = function() {
        const params = new URLSearchParams();
        if (el.buscador && el.buscador.value) params.append('buscar', el.buscador.value.trim());
        if (el.filtroUsuario && el.filtroUsuario.value) params.append('usuario', el.filtroUsuario.value.trim());
        if (el.filtroUserID && el.filtroUserID.value) params.append('user_id', el.filtroUserID.value.trim());
        if (el.filtroAccion && el.filtroAccion.value) params.append('accion', el.filtroAccion.value.trim());
        if (el.fechaDesde && el.fechaDesde.value) params.append('fecha_desde', el.fechaDesde.value);
        if (el.fechaHasta && el.fechaHasta.value) params.append('fecha_hasta', el.fechaHasta.value);
        
        const q = params.toString();
        window.location.href = '../funciones/exportar_logs.php' + (q ? '?' + q : '');
    };

    filtrarLogs(); // Ejecución inicial
});
</script>
