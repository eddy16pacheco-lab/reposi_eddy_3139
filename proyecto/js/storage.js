// ============================================
// SISTEMA DE ALMACENAMIENTO LOCAL
// ============================================

const STORAGE_KEY = 'ventaControlSystem';

// Inicializar datos en localStorage si no existen
function initializeStorage() {
    if (!localStorage.getItem(STORAGE_KEY)) {
        const initialData = {
            users: [
                {
                    id: 1,
                    nombre_usuario: 'admin',
                    contraseña_usuario: 'admin123',
                    correo: 'admin@ventacontrol.com',
                    nombre: 'Administrador',
                    apellido: 'Sistema',
                    fecha_nacimiento: '1990-01-01',
                    rol: 'Administrador'
                }
            ],
            transacciones: [],
            nextUserId: 2,
            nextTransaccionId: 1
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(initialData));
    }
}

// Obtener todos los datos
function getStorageData() {
    return JSON.parse(localStorage.getItem(STORAGE_KEY));
}

// Guardar datos
function saveStorageData(data) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

// Limpiar todos los datos (solo para desarrollo)
function clearStorage() {
    localStorage.removeItem(STORAGE_KEY);
    initializeStorage();
}

// Exportar datos como archivo JSON
function exportData() {
    const data = getStorageData();
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ventacontrol-backup.json';
    a.click();
    URL.revokeObjectURL(url);
}

// Importar datos desde archivo JSON
function importData(file, callback) {
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = JSON.parse(e.target.result);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            if (callback) callback(true, 'Datos importados exitosamente');
        } catch (error) {
            if (callback) callback(false, 'Error al importar datos: formato inválido');
        }
    };
    reader.readAsText(file);
}

// Inicializar almacenamiento al cargar
initializeStorage();