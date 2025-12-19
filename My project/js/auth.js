// Sistema de Autenticación para Control Obra

// Base de datos simulada de usuarios (en un caso real, esto estaría en el backend)
const usersDB = JSON.parse(localStorage.getItem('controlObraUsers')) || [];

// Función para registrar un nuevo usuario
function registerUser(userData) {
    // Verificar si el usuario ya existe
    const existingUser = usersDB.find(user => user.email === userData.email);
    
    if (existingUser) {
        alert('Este email ya está registrado. Por favor, usa otro o inicia sesión.');
        return false;
    }
    
    // Encriptar contraseña (simulada - en producción usar bcrypt)
    userData.password = btoa(userData.password); // Solo para demostración
    
    // Agregar usuario a la base de datos
    usersDB.push(userData);
    
    // Guardar en localStorage
    localStorage.setItem('controlObraUsers', JSON.stringify(usersDB));
    
    return true;
}

// Función para iniciar sesión
function loginUser(email, password) {
    // Buscar usuario en la base de datos
    const user = usersDB.find(user => user.email === email);
    
    if (!user) {
        alert('Usuario no encontrado. Por favor, regístrate primero.');
        return false;
    }
    
    // Verificar contraseña (desencriptar la simulación)
    const encryptedPassword = btoa(password);
    
    if (user.password !== encryptedPassword) {
        alert('Contraseña incorrecta. Por favor, intenta de nuevo.');
        return false;
    }
    
    // Crear sesión de usuario (sin datos sensibles)
    const userSession = {
        name: user.name,
        lastname: user.lastname,
        email: user.email,
        phone: user.phone,
        company: user.company,
        userType: user.userType,
        registrationDate: user.registrationDate
    };
    
    // Guardar sesión actual
    localStorage.setItem('currentUser', JSON.stringify(userSession));
    
    return true;
}

// Función para cerrar sesión
function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'index.html';
}

// Función para verificar si hay usuario logueado
function isLoggedIn() {
    return localStorage.getItem('currentUser') !== null;
}

// Función para obtener datos del usuario actual
function getCurrentUser() {
    return JSON.parse(localStorage.getItem('currentUser'));
}

// Función para actualizar datos del usuario
function updateUserProfile(updatedData) {
    const user = getCurrentUser();
    const userIndex = usersDB.findIndex(u => u.email === user.email);
    
    if (userIndex !== -1) {
        // Actualizar en la base de datos
        usersDB[userIndex] = { ...usersDB[userIndex], ...updatedData };
        localStorage.setItem('controlObraUsers', JSON.stringify(usersDB));
        
        // Actualizar sesión actual
        const updatedSession = { ...user, ...updatedData };
        localStorage.setItem('currentUser', JSON.stringify(updatedSession));
        
        return true;
    }
    
    return false;
}

// Función para cambiar contraseña
function changePassword(currentPassword, newPassword) {
    const user = getCurrentUser();
    const userIndex = usersDB.findIndex(u => u.email === user.email);
    
    if (userIndex !== -1) {
        const encryptedCurrent = btoa(currentPassword);
        
        if (usersDB[userIndex].password === encryptedCurrent) {
            usersDB[userIndex].password = btoa(newPassword);
            localStorage.setItem('controlObraUsers', JSON.stringify(usersDB));
            return true;
        }
    }
    
    return false;
}

// Función para recuperar contraseña (simulada)
function requestPasswordReset(email) {
    const user = usersDB.find(u => u.email === email);
    
    if (user) {
        // En producción, aquí enviarías un email con un enlace de recuperación
        alert('Se ha enviado un enlace de recuperación a tu email.');
        return true;
    }
    
    alert('No se encontró una cuenta con ese email.');
    return false;
}

// Proteger rutas que requieren autenticación
function requireAuth() {
    if (!isLoggedIn()) {
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

// Función para verificar permisos de usuario
function hasPermission(requiredRole) {
    const user = getCurrentUser();
    
    if (!user) return false;
    
    // En este ejemplo simple, todos los usuarios tienen acceso
    // En una aplicación real, aquí habría lógica de roles
    return true;
}

// Exportar funciones para uso global
window.auth = {
    registerUser,
    loginUser,
    logout,
    isLoggedIn,
    getCurrentUser,
    updateUserProfile,
    changePassword,
    requestPasswordReset,
    requireAuth,
    hasPermission
};