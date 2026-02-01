# Sistema de Gestión CBIT - Integrado con Base de Datos

Sistema completo de gestión para CBIT con backend PHP y frontend HTML/JavaScript integrado con base de datos MySQL/MariaDB.

## Características

### Módulos Implementados

1. **Dashboard**
   - Estadísticas en tiempo real
   - Solicitudes del día
   - Usuarios activos
   - Equipos operativos
   - Mantenimientos pendientes

2. **Gestión de Usuarios**
   - CRUD completo de usuarios
   - Roles: Administrador, Docente, Estudiante, Usuario Externo
   - Estados: Activo, Inactivo, Bloqueado
   - Información personal integrada

3. **Gestión de Solicitudes**
   - Solicitudes de espacios y equipos
   - Horarios por día de semana
   - Estados: Aprobado, Pendiente, Cancelado
   - Vinculación con usuarios, espacios y actividades

4. **Gestión de Inventario**
   - Catálogo de equipos
   - Control de ubicación física
   - Estados: Operativo, No operativo, Mantenimiento
   - Categorías y marcas

5. **Gestión de Mantenimiento**
   - Registro de incidencias
   - Mantenimiento preventivo y correctivo
   - Seguimiento de resoluciones
   - Historial completo

6. **Configuración**
   - Espacios físicos
   - Actividades
   - Categorías de equipos
   - Marcas
   - Ubicaciones físicas
   - Catálogo de equipos

## Requisitos del Sistema

- **Servidor Web**: Apache 2.4+ o Nginx
- **PHP**: 7.4 o superior
- **Base de Datos**: MySQL 5.7+ o MariaDB 10.3+
- **Extensiones PHP requeridas**:
  - PDO
  - pdo_mysql
  - json
  - mbstring

## Instalación

### 1. Preparar el Entorno

#### Opción A: XAMPP (Windows/Mac/Linux)
1. Descargar e instalar [XAMPP](https://www.apachefriends.org/)
2. Iniciar Apache y MySQL desde el panel de control

#### Opción B: WAMP (Windows)
1. Descargar e instalar [WAMP](https://www.wampserver.com/)
2. Iniciar todos los servicios

#### Opción C: LAMP (Linux)
```bash
sudo apt update
sudo apt install apache2 php php-mysql mysql-server
sudo systemctl start apache2
sudo systemctl start mysql
```

### 2. Configurar la Base de Datos

1. Acceder a phpMyAdmin (generalmente en `http://localhost/phpmyadmin`)
2. Crear una nueva base de datos o importar el archivo SQL:
   - Clic en "Nuevo" o "New"
   - Nombre: `db_sistema_web_cbit`
   - Collation: `utf8mb4_uca1400_ai_ci`
   - Clic en "Importar" o "Import"
   - Seleccionar el archivo `db_cbit.sql`
   - Clic en "Continuar" o "Go"

**Alternativa por línea de comandos:**
```bash
mysql -u root -p
CREATE DATABASE db_sistema_web_cbit CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
exit;

mysql -u root -p db_sistema_web_cbit < db_cbit.sql
```

### 3. Instalar el Sistema

1. Copiar la carpeta `sistema_cbit` al directorio web:
   - **XAMPP**: `C:\xampp\htdocs\sistema_cbit` (Windows) o `/opt/lampp/htdocs/sistema_cbit` (Linux)
   - **WAMP**: `C:\wamp64\www\sistema_cbit`
   - **LAMP**: `/var/www/html/sistema_cbit`

2. Configurar la conexión a la base de datos:
   - Abrir el archivo `config/database.php`
   - Modificar las credenciales si es necesario:
     ```php
     private $host = "localhost";
     private $db_name = "db_sistema_web_cbit";
     private $username = "root";
     private $password = "";  // Cambiar si tienes contraseña
     ```

3. Verificar permisos (solo Linux):
```bash
sudo chown -R www-data:www-data /var/www/html/sistema_cbit
sudo chmod -R 755 /var/www/html/sistema_cbit
```

### 4. Configurar la URL de la API

1. Abrir el archivo `assets/js/api.js`
2. Modificar la constante `API_BASE_URL` según tu configuración:
   ```javascript
   // Si instalaste en la raíz de htdocs/www
   const API_BASE_URL = 'http://localhost/sistema_cbit/api';
   
   // Si instalaste en un subdirectorio
   const API_BASE_URL = 'http://localhost/mi_carpeta/sistema_cbit/api';
   ```

### 5. Acceder al Sistema

1. Abrir el navegador web
2. Navegar a: `http://localhost/sistema_cbit/index_new.html`
3. El sistema debería cargar y mostrar el dashboard

## Estructura del Proyecto

```
sistema_cbit/
├── api/                        # Backend PHP - API REST
│   ├── usuarios.php           # Gestión de usuarios
│   ├── solicitudes.php        # Gestión de solicitudes
│   ├── inventario.php         # Gestión de inventario
│   ├── mantenimiento.php      # Gestión de mantenimiento
│   ├── auxiliares.php         # Tablas auxiliares
│   └── dashboard.php          # Estadísticas del dashboard
├── config/                     # Configuración
│   ├── database.php           # Conexión a base de datos
│   └── cors.php               # Configuración CORS
├── assets/                     # Recursos frontend
│   ├── js/
│   │   ├── api.js            # Cliente API
│   │   └── app.js            # Lógica de la aplicación
│   ├── css/                   # Estilos (si se separan)
│   └── img/                   # Imágenes
├── docs/                       # Documentación
├── index.html                 # HTML original (referencia)
├── index_new.html             # HTML integrado con BD
├── db_cbit.sql                # Script de base de datos
├── .htaccess                  # Configuración Apache
└── README.md                  # Este archivo
```

## API REST - Endpoints

### Usuarios
- `GET /api/usuarios.php` - Obtener todos los usuarios
- `GET /api/usuarios.php/{id}` - Obtener un usuario
- `POST /api/usuarios.php` - Crear usuario
- `PUT /api/usuarios.php/{id}` - Actualizar usuario
- `DELETE /api/usuarios.php/{id}` - Eliminar usuario

### Solicitudes
- `GET /api/solicitudes.php` - Obtener todas las solicitudes
- `GET /api/solicitudes.php/{id}` - Obtener una solicitud
- `POST /api/solicitudes.php` - Crear solicitud
- `PUT /api/solicitudes.php/{id}` - Actualizar solicitud
- `DELETE /api/solicitudes.php/{id}` - Eliminar solicitud

### Inventario
- `GET /api/inventario.php` - Obtener todo el inventario
- `GET /api/inventario.php/{id}` - Obtener un item
- `POST /api/inventario.php` - Crear item
- `PUT /api/inventario.php/{id}` - Actualizar item
- `DELETE /api/inventario.php/{id}` - Eliminar item

### Mantenimiento
- `GET /api/mantenimiento.php` - Obtener todos los mantenimientos
- `GET /api/mantenimiento.php/{id}` - Obtener un mantenimiento
- `POST /api/mantenimiento.php` - Crear mantenimiento
- `PUT /api/mantenimiento.php/{id}` - Actualizar mantenimiento
- `DELETE /api/mantenimiento.php/{id}` - Eliminar mantenimiento

### Tablas Auxiliares
- `GET /api/auxiliares.php/espacios` - Obtener espacios
- `GET /api/auxiliares.php/actividades` - Obtener actividades
- `GET /api/auxiliares.php/categorias` - Obtener categorías
- `GET /api/auxiliares.php/marcas` - Obtener marcas
- `GET /api/auxiliares.php/ubicaciones` - Obtener ubicaciones
- `GET /api/auxiliares.php/equipos` - Obtener equipos

### Dashboard
- `GET /api/dashboard.php` - Obtener estadísticas del dashboard

## Solución de Problemas

### Error: "No se puede conectar a la base de datos"
- Verificar que MySQL/MariaDB esté ejecutándose
- Verificar credenciales en `config/database.php`
- Verificar que la base de datos `db_sistema_web_cbit` exista

### Error: "Access-Control-Allow-Origin"
- Verificar que el archivo `config/cors.php` esté incluido en todos los archivos API
- Verificar configuración de `.htaccess`

### Error: "404 Not Found" en las peticiones API
- Verificar que la URL en `assets/js/api.js` sea correcta
- Verificar que mod_rewrite esté habilitado en Apache
- Verificar permisos de archivos

### Los datos no se cargan
- Abrir la consola del navegador (F12) y verificar errores
- Verificar que la base de datos tenga datos de prueba
- Verificar que las rutas de la API sean correctas

### Habilitar mod_rewrite en Apache (si es necesario)
```bash
# Linux
sudo a2enmod rewrite
sudo systemctl restart apache2

# Windows (XAMPP)
# Editar httpd.conf y descomentar:
# LoadModule rewrite_module modules/mod_rewrite.so
```

## Datos de Prueba

Para insertar datos de prueba, puedes ejecutar estos comandos SQL en phpMyAdmin:

```sql
-- Insertar persona de prueba
INSERT INTO persona (nombre, apellido, cedula, telefono) 
VALUES ('Juan', 'Pérez', '12345678', '04121234567');

-- Insertar usuario de prueba
INSERT INTO usuario (id_persona, nombre_usuario, contrasena_usuario, correo, estado, roles) 
VALUES (1, 'jperez', '$2y$10$abcdefghijklmnopqrstuv', 'jperez@cbit.org', 'Activo', 'Administrador');

-- Insertar espacio de prueba
INSERT INTO espacio (nombre) VALUES ('Sala de Computación 1');

-- Insertar actividad de prueba
INSERT INTO actividad (nombre) VALUES ('Clase de Programación');
```

## Tecnologías Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL/MariaDB
- **API**: REST con JSON
- **Autenticación**: Password hashing con bcrypt
- **Arquitectura**: MVC simplificado

## Próximas Mejoras

- [ ] Sistema de autenticación y sesiones
- [ ] Permisos por rol de usuario
- [ ] Calendario visual para solicitudes
- [ ] Reportes en PDF
- [ ] Exportación de datos a Excel
- [ ] Sistema de notificaciones
- [ ] Búsqueda y filtros avanzados
- [ ] Historial de cambios (audit log)
- [ ] Backup automático de base de datos

## Soporte

Para reportar problemas o sugerencias, por favor documenta:
1. Descripción del problema
2. Pasos para reproducirlo
3. Mensajes de error (consola del navegador y logs de PHP)
4. Versión de PHP y base de datos
5. Sistema operativo y navegador

## Licencia

Este proyecto es de uso educativo y puede ser modificado según las necesidades.

---

**Desarrollado para CBIT** - Sistema de Gestión Integrado
