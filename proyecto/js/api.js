// ============================================
// API PARA CONEXIÓN CON PHP/MYSQL
// ============================================

const API_BASE_URL = 'php/';

// Configuración común para fetch
const fetchConfig = {
    headers: {
        'Content-Type': 'application/json',
    },
    credentials: 'include' // Para mantener sesiones
};

// Función genérica para llamadas API
async function callAPI(endpoint, method = 'GET', data = null) {
    const config = {
        ...fetchConfig,
        method: method
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        config.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(API_BASE_URL + endpoint, config);
        const result = await response.json();
        
        if (!result.success && result.message === 'No autorizado. Inicia sesión primero.') {
            // Redirigir a login si no está autorizado
            logout();
            showLoginPage();
            showMessage('Sesión Expirada', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
            throw new Error('Sesión expirada');
        }
        
        return result;
    } catch (error) {
        console.error('Error en API call:', error);
        return {
            success: false,
            message: 'Error de conexión con el servidor'
        };
    }
}

// ============================================
// FUNCIONES DE AUTENTICACIÓN
// ============================================

async function loginUserAPI(username, password) {
    return await callAPI('login.php', 'POST', {
        username: username,
        password: password
    });
}

async function registerUserAPI(userData) {
    return await callAPI('register.php', 'POST', userData);
}

async function logoutAPI() {
    return await callAPI('logout.php', 'POST');
}

// ============================================
// FUNCIONES DE TRANSACCIONES
// ============================================

async function saveTransactionAPI(transactionData) {
    return await callAPI('save-transaction.php', 'POST', transactionData);
}

async function getTransactionsAPI(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString ? `get-transactions.php?${queryString}` : 'get-transactions.php';
    return await callAPI(endpoint);
}

// ============================================
// FUNCIONES DE ESTADÍSTICAS
// ============================================

async function getStatsAPI(period = 'daily', date = null) {
    const params = { period: period };
    if (date) params.date = date;
    
    const queryString = new URLSearchParams(params).toString();
    return await callAPI(`get-stats.php?${queryString}`);
}

// ============================================
// FUNCIONES DE USUARIOS
// ============================================

async function getUsersAPI() {
    return await callAPI('get-users.php');
}

// ============================================
// FUNCIONES DE SINCronización
// ============================================

// Función para verificar conexión con el servidor
async function checkServerConnection() {
    try {
        const response = await fetch(API_BASE_URL + 'config.php');
        return response.ok;
    } catch (error) {
        console.warn('Servidor no disponible, usando modo offline');
        return false;
    }
}

// Función para sincronizar datos si hubiera modo offline
let isOnline = true;

async function syncData() {
    // Aquí puedes implementar lógica de sincronización
    // si decides agregar modo offline
    console.log('Sincronizando datos con servidor...');
}