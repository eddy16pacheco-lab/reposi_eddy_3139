// ============================================
// SISTEMA DE AUTENTICACIÓN Y USUARIOS
// ============================================

// Registrar nuevo usuario
function registerUser(userData) {
    const data = getStorageData();
    
    // Validar campos obligatorios
    if (!userData.nombre_usuario || !userData.contraseña_usuario || !userData.correo || !userData.nombre || !userData.apellido) {
        return { success: false, message: 'Todos los campos son obligatorios' };
    }
    
    // Validar formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(userData.correo)) {
        return { success: false, message: 'El correo electrónico no es válido' };
    }
    
    // Verificar si el usuario ya existe
    const userExists = data.users.some(user => 
        user.nombre_usuario === userData.nombre_usuario || 
        user.correo === userData.correo
    );
    
    if (userExists) {
        return { success: false, message: 'El nombre de usuario o correo ya está registrado' };
    }
    
    // Agregar nuevo usuario
    const newUser = {
        id: data.nextUserId,
        ...userData
    };
    
    data.users.push(newUser);
    data.nextUserId++;
    saveStorageData(data);
    
    return { success: true, message: 'Usuario registrado exitosamente', user: newUser };
}

// Iniciar sesión
function loginUser(usernameOrEmail, password) {
    const data = getStorageData();
    
    if (!usernameOrEmail || !password) {
        return { success: false, message: 'Usuario y contraseña son requeridos' };
    }
    
    // Buscar usuario por nombre de usuario o correo
    const user = data.users.find(user => 
        (user.nombre_usuario === usernameOrEmail || user.correo === usernameOrEmail) &&
        user.contraseña_usuario === password
    );
    
    if (user) {
        // Guardar usuario actual en sessionStorage
        sessionStorage.setItem('currentUser', JSON.stringify(user));
        return { success: true, user: user };
    } else {
        return { success: false, message: 'Usuario o contraseña incorrectos' };
    }
}

// Obtener usuario actual
function getCurrentUser() {
    const user = sessionStorage.getItem('currentUser');
    return user ? JSON.parse(user) : null;
}

// Cerrar sesión
function logout() {
    sessionStorage.removeItem('currentUser');
}

// Actualizar perfil de usuario
function updateUserProfile(userId, newData) {
    const data = getStorageData();
    const userIndex = data.users.findIndex(user => user.id === userId);
    
    if (userIndex === -1) {
        return { success: false, message: 'Usuario no encontrado' };
    }
    
    // Validar que el nuevo nombre de usuario o correo no estén en uso
    const duplicateUser = data.users.find(user => 
        user.id !== userId && 
        (user.nombre_usuario === newData.nombre_usuario || user.correo === newData.correo)
    );
    
    if (duplicateUser) {
        return { success: false, message: 'El nombre de usuario o correo ya está en uso' };
    }
    
    // Actualizar datos del usuario
    data.users[userIndex] = { ...data.users[userIndex], ...newData };
    saveStorageData(data);
    
    // Actualizar usuario en sesión si es el mismo
    const currentUser = getCurrentUser();
    if (currentUser && currentUser.id === userId) {
        sessionStorage.setItem('currentUser', JSON.stringify(data.users[userIndex]));
    }
    
    return { success: true, message: 'Perfil actualizado exitosamente', user: data.users[userIndex] };
}

// Eliminar usuario
function deleteUser(userId) {
    const data = getStorageData();
    const currentUser = getCurrentUser();
    
    // No permitir eliminarse a sí mismo
    if (currentUser && userId === currentUser.id) {
        return { success: false, message: 'No puedes eliminar tu propio usuario' };
    }
    
    // Filtrar usuarios
    const initialLength = data.users.length;
    data.users = data.users.filter(user => user.id !== userId);
    
    if (data.users.length === initialLength) {
        return { success: false, message: 'Usuario no encontrado' };
    }
    
    saveStorageData(data);
    return { success: true, message: 'Usuario eliminado exitosamente' };
}

// Obtener todos los usuarios
function getAllUsers() {
    const data = getStorageData();
    return data.users;
}

// Verificar si el usuario actual es administrador
function isAdmin() {
    const user = getCurrentUser();
    return user && user.rol === 'Administrador';
}