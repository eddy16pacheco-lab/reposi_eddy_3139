/**
 * API Client - Sistema de Gestión CBIT
 * Maneja todas las comunicaciones con el backend PHP
 */

// Configuración de la API
const API_BASE_URL = 'http://localhost/sistema_cbit/api';

/**
 * Clase para manejar las peticiones a la API
 */
class APIClient {
    /**
     * Realizar petición HTTP
     */
    static async request(endpoint, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${API_BASE_URL}/${endpoint}`, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Error en la petición');
            }

            return result;
        } catch (error) {
            console.error('Error en la petición:', error);
            throw error;
        }
    }

    // ========== USUARIOS ==========
    static async getUsuarios() {
        return await this.request('usuarios.php');
    }

    static async getUsuario(id) {
        return await this.request(`usuarios.php/${id}`);
    }

    static async createUsuario(data) {
        return await this.request('usuarios.php', 'POST', data);
    }

    static async updateUsuario(id, data) {
        return await this.request(`usuarios.php/${id}`, 'PUT', data);
    }

    static async deleteUsuario(id) {
        return await this.request(`usuarios.php/${id}`, 'DELETE');
    }

    // ========== SOLICITUDES ==========
    static async getSolicitudes() {
        return await this.request('solicitudes.php');
    }

    static async getSolicitud(id) {
        return await this.request(`solicitudes.php/${id}`);
    }

    static async createSolicitud(data) {
        return await this.request('solicitudes.php', 'POST', data);
    }

    static async updateSolicitud(id, data) {
        return await this.request(`solicitudes.php/${id}`, 'PUT', data);
    }

    static async deleteSolicitud(id) {
        return await this.request(`solicitudes.php/${id}`, 'DELETE');
    }

    // ========== INVENTARIO ==========
    static async getInventario() {
        return await this.request('inventario.php');
    }

    static async getInventarioItem(id) {
        return await this.request(`inventario.php/${id}`);
    }

    static async createInventarioItem(data) {
        return await this.request('inventario.php', 'POST', data);
    }

    static async updateInventarioItem(id, data) {
        return await this.request(`inventario.php/${id}`, 'PUT', data);
    }

    static async deleteInventarioItem(id) {
        return await this.request(`inventario.php/${id}`, 'DELETE');
    }

    // ========== MANTENIMIENTO ==========
    static async getMantenimientos() {
        return await this.request('mantenimiento.php');
    }

    static async getMantenimiento(id) {
        return await this.request(`mantenimiento.php/${id}`);
    }

    static async createMantenimiento(data) {
        return await this.request('mantenimiento.php', 'POST', data);
    }

    static async updateMantenimiento(id, data) {
        return await this.request(`mantenimiento.php/${id}`, 'PUT', data);
    }

    static async deleteMantenimiento(id) {
        return await this.request(`mantenimiento.php/${id}`, 'DELETE');
    }

    // ========== TABLAS AUXILIARES ==========
    static async getEspacios() {
        return await this.request('auxiliares.php/espacios');
    }

    static async createEspacio(data) {
        return await this.request('auxiliares.php/espacios', 'POST', data);
    }

    static async deleteEspacio(id) {
        return await this.request(`auxiliares.php/espacios/${id}`, 'DELETE');
    }

    static async getCategorias() {
        return await this.request('auxiliares.php/categorias');
    }

    static async createCategoria(data) {
        return await this.request('auxiliares.php/categorias', 'POST', data);
    }

    static async deleteCategoria(id) {
        return await this.request(`auxiliares.php/categorias/${id}`, 'DELETE');
    }

    static async getMarcas() {
        return await this.request('auxiliares.php/marcas');
    }

    static async createMarca(data) {
        return await this.request('auxiliares.php/marcas', 'POST', data);
    }

    static async deleteMarca(id) {
        return await this.request(`auxiliares.php/marcas/${id}`, 'DELETE');
    }

    static async getActividades() {
        return await this.request('auxiliares.php/actividades');
    }

    static async createActividad(data) {
        return await this.request('auxiliares.php/actividades', 'POST', data);
    }

    static async deleteActividad(id) {
        return await this.request(`auxiliares.php/actividades/${id}`, 'DELETE');
    }

    static async getUbicaciones() {
        return await this.request('auxiliares.php/ubicaciones');
    }

    static async createUbicacion(data) {
        return await this.request('auxiliares.php/ubicaciones', 'POST', data);
    }

    static async deleteUbicacion(id) {
        return await this.request(`auxiliares.php/ubicaciones/${id}`, 'DELETE');
    }

    static async getEquipos() {
        return await this.request('auxiliares.php/equipos');
    }

    static async createEquipo(data) {
        return await this.request('auxiliares.php/equipos', 'POST', data);
    }

    static async deleteEquipo(id) {
        return await this.request(`auxiliares.php/equipos/${id}`, 'DELETE');
    }

    // ========== DASHBOARD ==========
    static async getDashboardStats() {
        return await this.request('dashboard.php');
    }
}

/**
 * Utilidades para formateo de datos
 */
class DataFormatter {
    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    static formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    static formatTime(timeString) {
        if (!timeString) return '';
        const parts = timeString.split(':');
        return `${parts[0]}:${parts[1]}`;
    }

    static getEstadoBadgeClass(estado) {
        const estados = {
            'Activo': 'badge-success',
            'Inactivo': 'badge-secondary',
            'Bloqueado': 'badge-danger',
            'Aprobado': 'badge-success',
            'Pendiente': 'badge-warning',
            'Cancelado': 'badge-danger',
            'Operativo': 'badge-success',
            'No operativo': 'badge-danger',
            'Mantenimiento': 'badge-warning'
        };
        return estados[estado] || 'badge-secondary';
    }
}

/**
 * Manejo de notificaciones
 */
class NotificationManager {
    static show(message, type = 'success') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;

        // Agregar al DOM
        document.body.appendChild(notification);

        // Cerrar al hacer clic
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });

        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    static success(message) {
        this.show(message, 'success');
    }

    static error(message) {
        this.show(message, 'error');
    }

    static warning(message) {
        this.show(message, 'warning');
    }

    static info(message) {
        this.show(message, 'info');
    }
}
