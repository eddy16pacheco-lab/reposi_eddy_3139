// ============================================
// APLICACIÓN PRINCIPAL
// ============================================

// Variables globales
let isRegisteringSale = true;
let selectedPaymentMethod = "Efectivo";
let selectedRole = "Empleado";

// Inicializar aplicación
function initApp() {
    setupEventListeners();
    
    // Verificar si hay usuario logueado
    const currentUser = getCurrentUser();
    if (currentUser) {
        showApp();
    } else {
        showLoginPage();
    }
}

// Configurar event listeners
function setupEventListeners() {
    // ====================
    // LOGIN Y REGISTRO
    // ====================
    
    // Botón de login
    document.getElementById('loginButton').addEventListener('click', handleLogin);
    
    // Link para registro
    document.getElementById('registerLink').addEventListener('click', (e) => {
        e.preventDefault();
        showRegisterPage();
    });
    
    // Botón cancelar registro
    document.getElementById('cancelRegBtn').addEventListener('click', showLoginPage);
    
    // Botón crear cuenta
    document.getElementById('saveRegBtn').addEventListener('click', handleRegister);
    
    // Enter en formulario de login
    document.getElementById('loginPassword').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleLogin();
        }
    });
    
    // Enter en formulario de registro
    document.getElementById('regPassword').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleRegister();
        }
    });
    
    // Selector de rol
    document.querySelectorAll('.role-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            selectedRole = this.dataset.role;
        });
    });
    
    // ====================
    // APLICACIÓN PRINCIPAL
    // ====================
    
    // Navegación por pestañas
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            changeTab(this.dataset.tab);
        });
    });
    
    // Botones de acción en la página de inicio
    document.getElementById('newSaleBtn').addEventListener('click', () => {
        isRegisteringSale = true;
        setupTransactionForm(true);
    });
    
    document.getElementById('newExpenseBtn').addEventListener('click', () => {
        isRegisteringSale = false;
        setupTransactionForm(false);
    });
    
    // Botón ver todas las transacciones
    document.getElementById('viewAllBtn').addEventListener('click', () => {
        showMessage('Todas las Transacciones', 'Esta funcionalidad está en desarrollo.');
    });
    
    // ====================
    // FORMULARIO DE TRANSACCIÓN
    // ====================
    
    // Selección de método de pago
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
            this.classList.add('active');
            selectedPaymentMethod = this.dataset.method;
        });
    });
    
    // Botones del formulario de transacción
    document.getElementById('cancelTransactionBtn').addEventListener('click', () => changeTab('home'));
    document.getElementById('saveTransactionBtn').addEventListener('click', handleSaveTransaction);
    
    // Enter en monto
    document.getElementById('amountInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleSaveTransaction();
        }
    });
    
    // ====================
    // PÁGINA DE RESUMEN
    // ====================
    
    // Botón exportar
    document.getElementById('exportBtn').addEventListener('click', () => {
        exportData();
        showMessage('Exportación', 'Los datos han sido exportados exitosamente.');
    });
    
    // ====================
    // PÁGINA DE AJUSTES
    // ====================
    
    // Botón editar perfil
    document.getElementById('editProfileBtn').addEventListener('click', () => {
        showMessage('Editar Perfil', 'Esta funcionalidad está en desarrollo.');
    });
    
    // Botón gestionar usuarios
    document.getElementById('manageUsersBtn').addEventListener('click', () => {
        if (isAdmin()) {
            // Crear página de usuarios si no existe
            if (!document.getElementById('usersPage')) {
                createUsersPage();
            }
            changeTab('users');
        } else {
            showMessage('Permiso Denegado', 'Solo los administradores pueden acceder a esta sección.');
        }
    });
    
    // Botón cerrar sesión
    document.getElementById('logoutBtn').addEventListener('click', () => {
        logout();
        showLoginPage();
        // Limpiar formularios
        document.getElementById('loginUsername').value = '';
        document.getElementById('loginPassword').value = '';
    });
}

// Manejar login
function handleLogin() {
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!username || !password) {
        showMessage('Error', 'Por favor completa todos los campos');
        return;
    }
    
    const result = loginUser(username, password);
    
    if (result.success) {
        showApp();
        showMessage('Bienvenido', `¡Hola ${result.user.nombre}! Has iniciado sesión exitosamente.`);
    } else {
        showMessage('Error', result.message);
    }
}

// Manejar registro
function handleRegister() {
    const userData = {
        nombre_usuario: document.getElementById('regUsername').value.trim(),
        contraseña_usuario: document.getElementById('regPassword').value,
        correo: document.getElementById('regEmail').value.trim(),
        nombre: document.getElementById('regName').value.trim(),
        apellido: document.getElementById('regLastName').value.trim(),
        fecha_nacimiento: document.getElementById('regBirthDate').value,
        rol: selectedRole
    };
    
    const result = registerUser(userData);
    
    if (result.success) {
        showMessage('Éxito', result.message, () => {
            // Auto-login después del registro
            const loginResult = loginUser(userData.nombre_usuario, userData.contraseña_usuario);
            if (loginResult.success) {
                showApp();
            } else {
                showLoginPage();
            }
        });
    } else {
        showMessage('Error', result.message);
    }
}

// Manejar guardar transacción
function handleSaveTransaction() {
    const amount = parseFloat(document.getElementById('amountInput').value);
    const description = document.getElementById('descriptionInput').value.trim();
    
    if (!amount || amount <= 0) {
        showMessage('Error', 'Por favor ingresa un monto válido mayor a 0');
        return;
    }
    
    const transactionData = {
        monto: amount,
        descripcion: description || (isRegisteringSale ? 'Venta registrada' : 'Gasto registrado'),
        tipo: isRegisteringSale ? 'Venta' : 'Gasto',
        forma_pago: selectedPaymentMethod
    };
    
    const result = registerTransaction(transactionData);
    
    if (result.success) {
        showMessage('Éxito', result.message, () => {
            changeTab('home');
            updateDailyStats();
            loadRecentTransactions();
        });
    } else {
        showMessage('Error', result.message);
    }
}

// Crear página de usuarios dinámicamente
function createUsersPage() {
    const appContainer = document.getElementById('appContainer');
    
    const usersPage = document.createElement('div');
    usersPage.id = 'usersPage';
    usersPage.className = 'page hidden';
    
    usersPage.innerHTML = `
        <div class="recent-section">
            <div class="section-title">
                Gestión de Usuarios
                <button style="font-size: 14px; background: #2ecc71; color: white; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer;" id="addUserBtn">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
            </div>
            <div id="usersList">
                <!-- Lista de usuarios se cargará aquí -->
            </div>
        </div>
    `;
    
    appContainer.appendChild(usersPage);
    
    // Event listener para botón agregar usuario
    document.getElementById('addUserBtn').addEventListener('click', () => {
        showRegisterPage();
    });
}

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', initApp);