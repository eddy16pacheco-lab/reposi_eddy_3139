/**
 * Aplicación Principal - Sistema de Gestión CBIT
 * Versión Completa con Login y Formularios
 */

// Estado global de la aplicación
const AppState = {
    currentTab: 'dashboard',
    currentUser: null,
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
    checkAuth();
});

/**
 * Verificar autenticación
 */
async function checkAuth() {
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php`);
        const result = await response.json();
        
        if (result.logged_in) {
            AppState.currentUser = result.user;
            initializeApp();
        } else {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Error al verificar autenticación:', error);
        window.location.href = 'login.html';
    }
}

/**
 * Inicializar aplicación
 */
async function initializeApp() {
    displayUserInfo();
    setupNavigationListeners();
    setupModalListeners();
    setupFormListeners();
    await loadDashboard();
}

/**
 * Mostrar información del usuario
 */
function displayUserInfo() {
    const user = AppState.currentUser;
    document.getElementById('user-name').textContent = `${user.nombre} ${user.apellido}`;
    document.getElementById('user-email').textContent = user.email;
    
    // Avatar con iniciales
    const initials = (user.nombre.charAt(0) + user.apellido.charAt(0)).toUpperCase();
    document.getElementById('user-avatar').textContent = initials;
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

    // Botón de logout
    document.getElementById('logout-btn').addEventListener('click', logout);

    // Botones de agregar
    document.getElementById('add-usuario')?.addEventListener('click', () => showModal('usuario'));
    document.getElementById('add-solicitud')?.addEventListener('click', () => openSolicitudModal());
    document.getElementById('add-inventario')?.addEventListener('click', () => openInventarioModal());
    document.getElementById('add-mantenimiento')?.addEventListener('click', () => openMantenimientoModal());
    document.getElementById('add-equipo')?.addEventListener('click', () => openEquipoModal());
}

/**
 * Configurar listeners de modales
 */
function setupModalListeners() {
    // Cerrar modales al hacer clic fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                const modalId = this.id.replace('modal-', '');
                closeModal(modalId);
            }
        });
    });
}

/**
 * Configurar listeners de formularios
 */
function setupFormListeners() {
    document.getElementById('form-usuario')?.addEventListener('submit', handleUsuarioSubmit);
    document.getElementById('form-solicitud')?.addEventListener('submit', handleSolicitudSubmit);
    document.getElementById('form-inventario')?.addEventListener('submit', handleInventarioSubmit);
    document.getElementById('form-mantenimiento')?.addEventListener('submit', handleMantenimientoSubmit);
    document.getElementById('form-equipo')?.addEventListener('submit', handleEquipoSubmit);
}

/**
 * Logout
 */
async function logout() {
    try {
        await fetch(`${API_BASE_URL}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });
        
        localStorage.removeItem('cbit_user');
        sessionStorage.removeItem('cbit_user');
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        window.location.href = 'login.html';
    }
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
            await loadEquiposCatalogo();
            break;
    }
}

// ========== DASHBOARD ==========

async function loadDashboard() {
    try {
        const stats = await APIClient.getDashboardStats();
        
        document.getElementById('solicitudes-hoy').textContent = stats.solicitudes_hoy || 0;
        document.getElementById('usuarios-activos').textContent = stats.usuarios_activos || 0;
        document.getElementById('solicitudes-pendientes').textContent = stats.solicitudes_pendientes || 0;
        document.getElementById('equipos-operativos').textContent = stats.equipos_operativos || 0;
        document.getElementById('mantenimientos-pendientes').textContent = stats.mantenimientos_pendientes || 0;
        
        renderProximasSolicitudes(stats.proximas_solicitudes || []);
    } catch (error) {
        console.error('Error al cargar dashboard:', error);
        NotificationManager.error('Error al cargar el dashboard');
    }
}

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

function renderUsuarios(usuarios) {
    const tbody = document.getElementById('usuarios-table');
    
    if (usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">No hay usuarios registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = usuarios.map(user => `
        <tr>
            <td>${user.nombre} ${user.apellido}</td>
            <td>${user.correo}</td>
            <td>${user.rol}</td>
            <td><span class="badge ${DataFormatter.getEstadoBadgeClass(user.estado)}">${user.estado}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-secondary btn-sm" onclick="editUsuario(${user.id_usuario})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUsuario(${user.id_usuario})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function handleUsuarioSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    delete data.id;
    
    try {
        if (id) {
            await APIClient.updateUsuario(id, data);
            NotificationManager.success('Usuario actualizado exitosamente');
        } else {
            await APIClient.createUsuario(data);
            NotificationManager.success('Usuario creado exitosamente');
        }
        
        closeModal('usuario');
        e.target.reset();
        await loadUsuarios();
        await loadDashboard();
    } catch (error) {
        console.error('Error al guardar usuario:', error);
        NotificationManager.error('Error al guardar usuario: ' + error.message);
    }
}

async function editUsuario(id) {
    try {
        const usuario = AppState.usuarios.find(u => u.id_usuario == id);
        if (!usuario) return;
        
        document.getElementById('modal-usuario-title').textContent = 'Editar Usuario';
        document.getElementById('usuario-id').value = id;
        
        const form = document.getElementById('form-usuario');
        form.elements['nombre'].value = usuario.nombre;
        form.elements['apellido'].value = usuario.apellido;
        form.elements['cedula'].value = usuario.cedula || '';
        form.elements['telefono'].value = usuario.telefono || '';
        form.elements['nombre_usuario'].value = usuario.nombre_usuario;
        form.elements['correo'].value = usuario.correo;
        form.elements['rol'].value = usuario.rol;
        form.elements['estado'].value = usuario.estado;
        form.elements['contrasena'].removeAttribute('required');
        
        showModal('usuario');
    } catch (error) {
        console.error('Error al editar usuario:', error);
        NotificationManager.error('Error al cargar datos del usuario');
    }
}

async function deleteUsuario(id) {
    if (!confirm('¿Está seguro de que desea eliminar este usuario?')) return;
    
    try {
        await APIClient.deleteUsuario(id);
        NotificationManager.success('Usuario eliminado exitosamente');
        await loadUsuarios();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar usuario:', error);
        NotificationManager.error('Error al eliminar usuario');
    }
}

// ========== SOLICITUDES ==========

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

function renderSolicitudes(solicitudes) {
    const tbody = document.getElementById('solicitudes-table');
    
    if (solicitudes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No hay solicitudes registradas</td></tr>';
        return;
    }
    
    tbody.innerHTML = solicitudes.map(sol => `
        <tr>
            <td>${DataFormatter.formatDateTime(sol.fecha)}</td>
            <td>${sol.nombre_persona} ${sol.apellido}</td>
            <td>${sol.espacio || 'N/A'}</td>
            <td>${sol.actividad || 'N/A'}</td>
            <td><span class="badge ${DataFormatter.getEstadoBadgeClass(sol.estado)}">${sol.estado}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-secondary btn-sm" onclick="editSolicitud(${sol.id_solicitud})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteSolicitud(${sol.id_solicitud})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function openSolicitudModal() {
    await loadUsuariosSelect();
    await loadEspaciosSelect();
    await loadActividadesSelect();
    showModal('solicitud');
}

async function loadUsuariosSelect() {
    const usuarios = await APIClient.getUsuarios();
    const select = document.getElementById('solicitud-usuario');
    select.innerHTML = '<option value="">Seleccione un usuario</option>' +
        usuarios.map(u => `<option value="${u.id_usuario}">${u.nombre} ${u.apellido}</option>`).join('');
}

async function loadEspaciosSelect() {
    const espacios = await APIClient.getEspacios();
    const select = document.getElementById('solicitud-espacio');
    select.innerHTML = '<option value="">Seleccione un espacio</option>' +
        espacios.map(e => `<option value="${e.id_espacio}">${e.nombre}</option>`).join('');
}

async function loadActividadesSelect() {
    const actividades = await APIClient.getActividades();
    const select = document.getElementById('solicitud-actividad');
    select.innerHTML = '<option value="">Seleccione una actividad</option>' +
        actividades.map(a => `<option value="${a.id_actividad}">${a.nombre}</option>`).join('');
}

async function handleSolicitudSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        await APIClient.createSolicitud(data);
        NotificationManager.success('Solicitud creada exitosamente');
        closeModal('solicitud');
        e.target.reset();
        await loadSolicitudes();
        await loadDashboard();
    } catch (error) {
        console.error('Error al crear solicitud:', error);
        NotificationManager.error('Error al crear solicitud');
    }
}

function editSolicitud(id) {
    NotificationManager.info('Función de edición en desarrollo');
}

async function deleteSolicitud(id) {
    if (!confirm('¿Está seguro de que desea eliminar esta solicitud?')) return;
    
    try {
        await APIClient.deleteSolicitud(id);
        NotificationManager.success('Solicitud eliminada exitosamente');
        await loadSolicitudes();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar solicitud:', error);
        NotificationManager.error('Error al eliminar solicitud');
    }
}

// ========== INVENTARIO ==========

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

function renderInventario(inventario) {
    const tbody = document.getElementById('inventario-table');
    
    if (inventario.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No hay items en el inventario</td></tr>';
        return;
    }
    
    tbody.innerHTML = inventario.map(item => `
        <tr>
            <td>${item.serial}</td>
            <td>${item.modelo || 'N/A'}</td>
            <td>${item.categoria || 'N/A'}</td>
            <td>${item.marca || 'N/A'}</td>
            <td>${item.ubicacion || 'N/A'}</td>
            <td><span class="badge ${DataFormatter.getEstadoBadgeClass(item.estado)}">${item.estado}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-secondary btn-sm" onclick="editInventario(${item.id_inventario})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteInventario(${item.id_inventario})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function openInventarioModal() {
    await loadEquiposSelect();
    await loadUbicacionesSelect();
    showModal('inventario');
}

async function loadEquiposSelect() {
    const equipos = await APIClient.getEquipos();
    const select = document.getElementById('inventario-equipo');
    select.innerHTML = '<option value="">Seleccione un equipo</option>' +
        equipos.map(e => `<option value="${e.id_equipos}">${e.modelo} (${e.categoria})</option>`).join('');
}

async function loadUbicacionesSelect() {
    const ubicaciones = await APIClient.getUbicaciones();
    const select = document.getElementById('inventario-ubicacion');
    select.innerHTML = '<option value="">Seleccione una ubicación</option>' +
        ubicaciones.map(u => `<option value="${u.id_ubicacion_fisica}">${u.nombre}</option>`).join('');
}

async function handleInventarioSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        await APIClient.createInventarioItem(data);
        NotificationManager.success('Item de inventario creado exitosamente');
        closeModal('inventario');
        e.target.reset();
        await loadInventario();
        await loadDashboard();
    } catch (error) {
        console.error('Error al crear item:', error);
        NotificationManager.error('Error al crear item de inventario');
    }
}

function editInventario(id) {
    NotificationManager.info('Función de edición en desarrollo');
}

async function deleteInventario(id) {
    if (!confirm('¿Está seguro de que desea eliminar este item del inventario?')) return;
    
    try {
        await APIClient.deleteInventarioItem(id);
        NotificationManager.success('Item eliminado exitosamente');
        await loadInventario();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar item:', error);
        NotificationManager.error('Error al eliminar item');
    }
}

// ========== MANTENIMIENTO ==========

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

function renderMantenimiento(mantenimientos) {
    const tbody = document.getElementById('mantenimiento-table');
    
    if (mantenimientos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No hay mantenimientos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = mantenimientos.map(mant => `
        <tr>
            <td>${DataFormatter.formatDateTime(mant.fecha_reporte)}</td>
            <td>${mant.serial} - ${mant.modelo || 'N/A'}</td>
            <td>${mant.tipo}</td>
            <td>${mant.nombre_persona} ${mant.apellido}</td>
            <td><span class="badge ${mant.fecha_resolucion ? 'badge-success' : 'badge-warning'}">${mant.fecha_resolucion ? 'Resuelto' : 'Pendiente'}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-secondary btn-sm" onclick="editMantenimiento(${mant.id_mantenimiento})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMantenimiento(${mant.id_mantenimiento})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function openMantenimientoModal() {
    await loadInventarioSelect();
    await loadUsuariosSelectMant();
    showModal('mantenimiento');
}

async function loadInventarioSelect() {
    const inventario = await APIClient.getInventario();
    const select = document.getElementById('mantenimiento-inventario');
    select.innerHTML = '<option value="">Seleccione un equipo</option>' +
        inventario.map(i => `<option value="${i.id_inventario}">${i.serial} - ${i.modelo}</option>`).join('');
}

async function loadUsuariosSelectMant() {
    const usuarios = await APIClient.getUsuarios();
    const select = document.getElementById('mantenimiento-usuario');
    select.innerHTML = '<option value="">Seleccione un usuario</option>' +
        usuarios.map(u => `<option value="${u.id_usuario}">${u.nombre} ${u.apellido}</option>`).join('');
}

async function handleMantenimientoSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        await APIClient.createMantenimiento(data);
        NotificationManager.success('Mantenimiento registrado exitosamente');
        closeModal('mantenimiento');
        e.target.reset();
        await loadMantenimiento();
        await loadDashboard();
    } catch (error) {
        console.error('Error al crear mantenimiento:', error);
        NotificationManager.error('Error al registrar mantenimiento');
    }
}

function editMantenimiento(id) {
    NotificationManager.info('Función de edición en desarrollo');
}

async function deleteMantenimiento(id) {
    if (!confirm('¿Está seguro de que desea eliminar este mantenimiento?')) return;
    
    try {
        await APIClient.deleteMantenimiento(id);
        NotificationManager.success('Mantenimiento eliminado exitosamente');
        await loadMantenimiento();
        await loadDashboard();
    } catch (error) {
        console.error('Error al eliminar mantenimiento:', error);
        NotificationManager.error('Error al eliminar mantenimiento');
    }
}

// ========== CONFIGURACIÓN ==========

async function loadEspacios() {
    try {
        const espacios = await APIClient.getEspacios();
        AppState.espacios = espacios;
        renderConfigTable('espacios', espacios, 'nombre');
    } catch (error) {
        console.error('Error al cargar espacios:', error);
        NotificationManager.error('Error al cargar espacios');
    }
}

async function loadActividades() {
    try {
        const actividades = await APIClient.getActividades();
        AppState.actividades = actividades;
        renderConfigTable('actividades', actividades, 'nombre');
    } catch (error) {
        console.error('Error al cargar actividades:', error);
        NotificationManager.error('Error al cargar actividades');
    }
}

async function loadCategorias() {
    try {
        const categorias = await APIClient.getCategorias();
        AppState.categorias = categorias;
        renderConfigTable('categorias', categorias, 'nombre');
    } catch (error) {
        console.error('Error al cargar categorías:', error);
        NotificationManager.error('Error al cargar categorías');
    }
}

async function loadMarcas() {
    try {
        const marcas = await APIClient.getMarcas();
        AppState.marcas = marcas;
        renderConfigTable('marcas', marcas, 'nombre');
    } catch (error) {
        console.error('Error al cargar marcas:', error);
        NotificationManager.error('Error al cargar marcas');
    }
}

async function loadUbicaciones() {
    try {
        const ubicaciones = await APIClient.getUbicaciones();
        AppState.ubicaciones = ubicaciones;
        renderConfigTable('ubicaciones', ubicaciones, 'nombre');
    } catch (error) {
        console.error('Error al cargar ubicaciones:', error);
        NotificationManager.error('Error al cargar ubicaciones');
    }
}

async function loadEquiposCatalogo() {
    try {
        const equipos = await APIClient.getEquipos();
        AppState.equipos = equipos;
        renderEquiposTable(equipos);
    } catch (error) {
        console.error('Error al cargar equipos:', error);
        NotificationManager.error('Error al cargar equipos');
    }
}

function renderConfigTable(tableName, data, field) {
    const tbody = document.getElementById(`${tableName}-table`);
    
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2" style="text-align: center; color: #999;">No hay registros</td></tr>`;
        return;
    }
    
    const idField = tableName === 'ubicaciones' ? 'id_ubicacion_fisica' : `id_${tableName.slice(0, -1)}`;
    
    tbody.innerHTML = data.map(item => `
        <tr>
            <td>${item[field]}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteConfigItem('${tableName}', ${item[idField]})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderEquiposTable(equipos) {
    const tbody = document.getElementById('equipos-table');
    
    if (equipos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #999;">No hay equipos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = equipos.map(eq => `
        <tr>
            <td>${eq.modelo}</td>
            <td>${eq.categoria}</td>
            <td>${eq.marca}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteEquipo(${eq.id_equipos})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function openEquipoModal() {
    await loadCategoriasSelect();
    await loadMarcasSelect();
    showModal('equipo');
}

async function loadCategoriasSelect() {
    const categorias = await APIClient.getCategorias();
    const select = document.getElementById('equipo-categoria');
    select.innerHTML = '<option value="">Seleccione una categoría</option>' +
        categorias.map(c => `<option value="${c.id_categoria}">${c.nombre}</option>`).join('');
}

async function loadMarcasSelect() {
    const marcas = await APIClient.getMarcas();
    const select = document.getElementById('equipo-marca');
    select.innerHTML = '<option value="">Seleccione una marca</option>' +
        marcas.map(m => `<option value="${m.id_marca}">${m.nombre}</option>`).join('');
}

async function handleEquipoSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        await APIClient.createEquipo(data);
        NotificationManager.success('Equipo creado exitosamente');
        closeModal('equipo');
        e.target.reset();
        await loadEquiposCatalogo();
    } catch (error) {
        console.error('Error al crear equipo:', error);
        NotificationManager.error('Error al crear equipo');
    }
}

async function deleteEquipo(id) {
    if (!confirm('¿Está seguro de que desea eliminar este equipo?')) return;
    
    try {
        await APIClient.deleteEquipo(id);
        NotificationManager.success('Equipo eliminado exitosamente');
        await loadEquiposCatalogo();
    } catch (error) {
        console.error('Error al eliminar equipo:', error);
        NotificationManager.error('Error al eliminar equipo');
    }
}

async function showAddModal(tipo) {
    const nombre = prompt(`Ingrese el nombre del ${tipo}:`);
    if (!nombre) return;
    
    try {
        const createMethod = `create${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
        await APIClient[createMethod]({ nombre });
        NotificationManager.success(`${tipo.charAt(0).toUpperCase() + tipo.slice(1)} creado exitosamente`);
        
        switch(tipo) {
            case 'espacio':
                await loadEspacios();
                break;
            case 'actividad':
                await loadActividades();
                break;
            case 'categoria':
                await loadCategorias();
                break;
            case 'marca':
                await loadMarcas();
                break;
            case 'ubicacion':
                await APIClient.createUbicacion({ nombre });
                await loadUbicaciones();
                break;
        }
    } catch (error) {
        console.error(`Error al crear ${tipo}:`, error);
        NotificationManager.error(`Error al crear ${tipo}`);
    }
}

async function deleteConfigItem(tipo, id) {
    if (!confirm(`¿Está seguro de que desea eliminar este ${tipo.slice(0, -1)}?`)) return;
    
    try {
        const deleteMethod = `delete${tipo.charAt(0).toUpperCase() + tipo.slice(1, -1)}`;
        await APIClient[deleteMethod](id);
        NotificationManager.success(`${tipo.slice(0, -1)} eliminado exitosamente`);
        
        switch(tipo) {
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
        }
    } catch (error) {
        console.error(`Error al eliminar ${tipo.slice(0, -1)}:`, error);
        NotificationManager.error(`Error al eliminar ${tipo.slice(0, -1)}`);
    }
}

// ========== MODALES ==========

function showModal(modalName) {
    const modal = document.getElementById(`modal-${modalName}`);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalName) {
    const modal = document.getElementById(`modal-${modalName}`);
    if (modal) {
        modal.classList.remove('active');
        // Resetear formulario
        const form = modal.querySelector('form');
        if (form) form.reset();
        // Resetear título si es modal de usuario
        if (modalName === 'usuario') {
            document.getElementById('modal-usuario-title').textContent = 'Nuevo Usuario';
            document.getElementById('usuario-id').value = '';
            form.elements['contrasena'].setAttribute('required', 'required');
        }
    }
}
