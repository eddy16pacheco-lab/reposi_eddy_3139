/**
 * Aplicación Principal - Sistema de Gestión CBIT
 */

// Estado global de la aplicación
const AppState = {
    currentTab: 'dashboard',
    usuarios: [],
    solicitudes: [],
    inventario: [],
    mantenimientos: [],
    espacios: [],
    actividades: [],
    categorias: [],
    marcas: [],
    ubicaciones: [],
    equipos: []
};

// Inicialización de la aplicación
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Inicializar aplicación
 */
async function initializeApp() {
    setupNavigationListeners();
    setupModalListeners();
    await loadDashboard();
}

/**
 * Configurar listeners de navegación
 */
function setupNavigationListeners() {
    // Navegación principal
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            switchTab(tab);
        });
    });

    // Navegación de configuración
    document.querySelectorAll('[data-config-tab]').forEach(tab => {
        tab.addEventListener('click', function() {
            const configTab = this.getAttribute('data-config-tab');
            switchConfigTab(configTab);
        });
    });

    // Botones de agregar
    document.getElementById('add-usuario')?.addEventListener('click', () => showModal('usuario'));
    document.getElementById('add-solicitud')?.addEventListener('click', () => showModal('solicitud'));
    document.getElementById('add-inventario')?.addEventListener('click', () => showModal('inventario'));
    document.getElementById('add-mantenimiento')?.addEventListener('click', () => showModal('mantenimiento'));
}

/**
 * Configurar listeners de modales
 */
function setupModalListeners() {
    // Formulario de usuario
    document.getElementById('form-usuario')?.addEventListener('submit', handleUsuarioSubmit);
}

/**
 * Cambiar de pestaña
 */
function switchTab(tabName) {
    // Actualizar navegación
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

    // Actualizar contenido
    document.querySelectorAll('.main-content > .tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(tabName).classList.add('active');

    // Actualizar título
    const titles = {
        'dashboard': 'Dashboard',
        'solicitudes': 'Gestión de Solicitudes',
        'usuarios': 'Gestión de Usuarios',
        'inventario': 'Gestión de Inventario',
        'mantenimiento': 'Gestión de Mantenimiento',
        'configuracion': 'Configuración del Sistema'
    };
    document.getElementById('page-title').textContent = titles[tabName];

    // Cargar datos según la pestaña
    AppState.currentTab = tabName;
    loadTabData(tabName);
}

/**
 * Cambiar pestaña de configuración
 */
function switchConfigTab(configTab) {
    // Actualizar pestañas
    document.querySelectorAll('[data-config-tab]').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-config-tab="${configTab}"]`).classList.add('active');

    // Actualizar contenido
    document.querySelectorAll('#configuracion .tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`config-${configTab}`).classList.add('active');

    // Cargar datos
    loadConfigData(configTab);
}

/**
 * Cargar datos según la pestaña
 */
async function loadTabData(tabName) {
    switch(tabName) {
        case 'dashboard':
            await loadDashboard();
            break;
        case 'solicitudes':
            await loadSolicitudes();
            break;
        case 'usuarios':
            await loadUsuarios();
            break;
        case 'inventario':
            await loadInventario();
            break;
        case 'mantenimiento':
            await loadMantenimiento();
            break;
        case 'configuracion':
            await loadConfigData('espacios');
            break;
    }
}

/**
 * Cargar datos de configuración
 */
async function loadConfigData(configTab) {
    switch(configTab) {
        case 'espacios':
            await loadEspacios();
            break;
        case 'actividades':
            await loadActividades();
            break;
        case 'categorias':
            await loadCategorias();
            break;
        case 'marcas':
            await loadMarcas();
            break;
        case 'ubicaciones':
            await loadUbicaciones();
            break;
        case 'equipos':
            await loadEquipos();
            break;
    }
}

// ========== DASHBOARD ==========

/**
 * Cargar dashboard
 */
async function loadDashboard() {
    try {
        const stats = await APIClient.getDashboardStats();
        
        // Actualizar contadores
        document.getElementById('solicitudes-hoy').textContent = stats.solicitudes_hoy || 0;
        document.getElementById('usuarios-activos').textContent = stats.usuarios_activos || 0;
        document.getElementById('solicitudes-pendientes').textContent = stats.solicitudes_pendientes || 0;
        document.getElementById('equipos-operativos').textContent = stats.equipos_operativos || 0;
        document.getElementById('mantenimientos-pendientes').textContent = stats.mantenimientos_pendientes || 0;
        
        // Renderizar próximas solicitudes
        renderProximasSolicitudes(stats.proximas_solicitudes || []);
        
    } catch (error) {
        console.error('Error al cargar dashboard:', error);
        NotificationManager.error('Error al cargar el dashboard');
    }
}

/**
 * Renderizar próximas solicitudes
 */
function renderProximasSolicitudes(solicitudes) {
    const tbody = document.getElementById('proximas-solicitudes');
    
    if (solicitudes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">No hay solicitudes próximas</td></tr>';
        return;
    }
    
    tbody.innerHTML = solicitudes.map(sol => `
        <tr>
            <td>${DataFormatter.formatDateTime(sol.fecha)}</td>
            <td>${sol.nombre} ${sol.apellido}</td>
            <td>${sol.espacio || 'N/A'}</td>
            <td>${sol.actividad || 'N/A'}</td>
            <td><span class="badge ${DataFormatter.getEstadoBadgeClass(sol.estado)}">${sol.estado}</span></td>
        </tr>
    `).join('');
}

// ========== USUARIOS ==========

/**
 * Cargar usuarios
 */
async function loadUsuarios() {
    try {
        const usuarios = await APIClient.getUsuarios();
        AppState.usuarios = usuarios;
        renderUsuarios(usuarios);
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        NotificationManager.error('Error al cargar usuarios');
    }
}

/**
 * Renderizar tabla de usuarios
 */
function renderUsuarios(usuarios) {
    const tbody = document.getElementById('usuarios-table');
    
    if (usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No hay usuarios registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = usuarios.map(user => `
        <tr>
            <td>${user.id_usuario}</td>
            <td>${user.nombre} ${user.apellido}</td>
            <td>${user.correo}</td>
            <td>${user.roles}</td>
            <td><span class="badge ${DataFormatter.getEstadoBadgeClass(user.estado)}">${user.estado}</span></td>
            <td>
                <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="editUsuario(${user.id_usuario})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem; margin-left: 5px;" onclick="deleteUsuario(${user.id_usuario})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Manejar envío de formulario de usuario
 */
async function handleUsuarioSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        await APIClient.createUsuario(data);
        NotificationManager.success('Usuario creado exitosamente');
        closeModal('usuario');
        e.target.reset();
        await loadUsuarios();
        await loadDashboard();
    } catch (error) {
        console.error('Error al crear usuario:', error);
        NotificationManager.error('Error al crear usuario: ' + error.message);
    }
}

/**
 * Eliminar usuario
 */
async function deleteUsuario(id) {
    if (!confirm('¿Está seguro de que desea eliminar este usuario?')) {
        return;
    }
    
    try {
        await APIClient.deleteUsuario(id);
        NotificationManager.success('Usuario eliminado exitosamente');
        await loadUsuarios();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar usuario:', error);
        NotificationManager.error('Error al eliminar usuario: ' + error.message);
    }
}

// ========== SOLICITUDES ==========

/**
 * Cargar solicitudes
 */
async function loadSolicitudes() {
    try {
        const solicitudes = await APIClient.getSolicitudes();
        AppState.solicitudes = solicitudes;
        renderSolicitudes(solicitudes);
    } catch (error) {
        console.error('Error al cargar solicitudes:', error);
        NotificationManager.error('Error al cargar solicitudes');
    }
}

/**
 * Renderizar tabla de solicitudes
 */
function renderSolicitudes(solicitudes) {
    const tbody = document.getElementById('solicitudes-table');
    
    if (solicitudes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No hay solicitudes registradas</td></tr>';
        return;
    }
    
    tbody.innerHTML = solicitudes.map(sol => `
        <tr>
            <td>${sol.id_solicitud}</td>
            <td>${DataFormatter.formatDateTime(sol.fecha)}</td>
            <td>${sol.nombre_persona} ${sol.apellido}</td>
            <td>${sol.espacio || 'N/A'}</td>
            <td>${sol.actividad || 'N/A'}</td>
            <td><span class="badge ${DataFormatter.getEstadoBadgeClass(sol.estado)}">${sol.estado}</span></td>
            <td>
                <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="viewSolicitud(${sol.id_solicitud})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem; margin-left: 5px;" onclick="deleteSolicitud(${sol.id_solicitud})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Eliminar solicitud
 */
async function deleteSolicitud(id) {
    if (!confirm('¿Está seguro de que desea eliminar esta solicitud?')) {
        return;
    }
    
    try {
        await APIClient.deleteSolicitud(id);
        NotificationManager.success('Solicitud eliminada exitosamente');
        await loadSolicitudes();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar solicitud:', error);
        NotificationManager.error('Error al eliminar solicitud: ' + error.message);
    }
}

// ========== INVENTARIO ==========

/**
 * Cargar inventario
 */
async function loadInventario() {
    try {
        const inventario = await APIClient.getInventario();
        AppState.inventario = inventario;
        renderInventario(inventario);
    } catch (error) {
        console.error('Error al cargar inventario:', error);
        NotificationManager.error('Error al cargar inventario');
    }
}

/**
 * Renderizar tabla de inventario
 */
function renderInventario(inventario) {
    const tbody = document.getElementById('inventario-table');
    
    if (inventario.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999;">No hay items en el inventario</td></tr>';
        return;
    }
    
    tbody.innerHTML = inventario.map(item => `
        <tr>
            <td>${item.id_inventario}</td>
            <td>${item.serial}</td>
            <td>${item.modelo || 'N/A'}</td>
            <td>${item.categoria || 'N/A'}</td>
            <td>${item.marca || 'N/A'}</td>
            <td>${item.ubicacion || 'N/A'}</td>
            <td><span class="badge ${DataFormatter.getEstadoBadgeClass(item.estado)}">${item.estado}</span></td>
            <td>
                <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="editInventario(${item.id_inventario})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem; margin-left: 5px;" onclick="deleteInventario(${item.id_inventario})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Eliminar item de inventario
 */
async function deleteInventario(id) {
    if (!confirm('¿Está seguro de que desea eliminar este item del inventario?')) {
        return;
    }
    
    try {
        await APIClient.deleteInventarioItem(id);
        NotificationManager.success('Item eliminado exitosamente');
        await loadInventario();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar item:', error);
        NotificationManager.error('Error al eliminar item: ' + error.message);
    }
}

// ========== MANTENIMIENTO ==========

/**
 * Cargar mantenimientos
 */
async function loadMantenimiento() {
    try {
        const mantenimientos = await APIClient.getMantenimientos();
        AppState.mantenimientos = mantenimientos;
        renderMantenimiento(mantenimientos);
    } catch (error) {
        console.error('Error al cargar mantenimientos:', error);
        NotificationManager.error('Error al cargar mantenimientos');
    }
}

/**
 * Renderizar tabla de mantenimientos
 */
function renderMantenimiento(mantenimientos) {
    const tbody = document.getElementById('mantenimiento-table');
    
    if (mantenimientos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No hay mantenimientos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = mantenimientos.map(mant => `
        <tr>
            <td>${mant.id_mantenimiento}</td>
            <td>${DataFormatter.formatDateTime(mant.fecha_reporte)}</td>
            <td>${mant.serial} - ${mant.modelo || 'N/A'}</td>
            <td>${mant.tipo}</td>
            <td>${mant.nombre_persona} ${mant.apellido}</td>
            <td><span class="badge ${mant.fecha_resolucion ? 'badge-success' : 'badge-warning'}">${mant.fecha_resolucion ? 'Resuelto' : 'Pendiente'}</span></td>
            <td>
                <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="viewMantenimiento(${mant.id_mantenimiento})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem; margin-left: 5px;" onclick="deleteMantenimiento(${mant.id_mantenimiento})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Eliminar mantenimiento
 */
async function deleteMantenimiento(id) {
    if (!confirm('¿Está seguro de que desea eliminar este mantenimiento?')) {
        return;
    }
    
    try {
        await APIClient.deleteMantenimiento(id);
        NotificationManager.success('Mantenimiento eliminado exitosamente');
        await loadMantenimiento();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar mantenimiento:', error);
        NotificationManager.error('Error al eliminar mantenimiento: ' + error.message);
    }
}

// ========== CONFIGURACIÓN ==========

/**
 * Cargar espacios
 */
async function loadEspacios() {
    try {
        const espacios = await APIClient.getEspacios();
        AppState.espacios = espacios;
        renderConfigTable('espacios', espacios, ['id_espacio', 'nombre']);
    } catch (error) {
        console.error('Error al cargar espacios:', error);
        NotificationManager.error('Error al cargar espacios');
    }
}

/**
 * Cargar actividades
 */
async function loadActividades() {
    try {
        const actividades = await APIClient.getActividades();
        AppState.actividades = actividades;
        renderConfigTable('actividades', actividades, ['id_actividad', 'nombre']);
    } catch (error) {
        console.error('Error al cargar actividades:', error);
        NotificationManager.error('Error al cargar actividades');
    }
}

/**
 * Renderizar tabla de configuración genérica
 */
function renderConfigTable(tableName, data, fields) {
    const tbody = document.getElementById(`${tableName}-table`);
    
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: #999;">No hay registros</td></tr>`;
        return;
    }
    
    tbody.innerHTML = data.map(item => `
        <tr>
            <td>${item[fields[0]]}</td>
            <td>${item[fields[1]]}</td>
            <td>
                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="deleteConfigItem('${tableName}', ${item[fields[0]]})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// ========== MODALES ==========

/**
 * Mostrar modal
 */
function showModal(modalName) {
    const modal = document.getElementById(`modal-${modalName}`);
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * Cerrar modal
 */
function closeModal(modalName) {
    const modal = document.getElementById(`modal-${modalName}`);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Mostrar modal de agregar item de configuración
 */
function showAddModal(tipo) {
    const nombre = prompt(`Ingrese el nombre del ${tipo}:`);
    if (nombre) {
        addConfigItem(tipo, nombre);
    }
}

/**
 * Agregar item de configuración
 */
async function addConfigItem(tipo, nombre) {
    try {
        const tipoPlural = tipo + 's';
        const createMethod = `create${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
        
        await APIClient[createMethod]({ nombre });
        NotificationManager.success(`${tipo.charAt(0).toUpperCase() + tipo.slice(1)} creado exitosamente`);
        
        // Recargar datos
        switch(tipo) {
            case 'espacio':
                await loadEspacios();
                break;
            case 'actividad':
                await loadActividades();
                break;
        }
    } catch (error) {
        console.error(`Error al crear ${tipo}:`, error);
        NotificationManager.error(`Error al crear ${tipo}: ` + error.message);
    }
}

// Funciones placeholder para editar/ver
function editUsuario(id) { NotificationManager.info('Función de edición en desarrollo'); }
function viewSolicitud(id) { NotificationManager.info('Función de visualización en desarrollo'); }
function editInventario(id) { NotificationManager.info('Función de edición en desarrollo'); }
function viewMantenimiento(id) { NotificationManager.info('Función de visualización en desarrollo'); }
