# Sistema de Gestión CBIT - Versión 2.0

## 🎉 Novedades de la Versión 2.0

### ✅ Sistema de Login Completo
- Página de inicio de sesión profesional
- Autenticación con base de datos
- Gestión de sesiones PHP
- Protección de rutas

### ✅ Formularios Completos
- **Usuarios**: Crear y editar con todos los campos
- **Solicitudes**: Formulario completo con selección de usuario, espacio y actividad
- **Inventario**: Agregar equipos con serial y ubicación
- **Mantenimiento**: Registrar mantenimientos con tipo y descripción
- **Equipos**: Catálogo de equipos con modelo, categoría y marca
- **Configuración**: Agregar espacios, actividades, categorías, marcas y ubicaciones

### ✅ IDs Ocultos
- Todas las tablas ahora ocultan los campos de ID
- Interfaz más limpia y profesional
- IDs se mantienen internamente para operaciones

### ✅ Funcionalidad de Edición
- Editar usuarios con carga de datos en modal
- Estructura preparada para editar todas las entidades

### ✅ Mejoras en UX/UI
- Botones de acción más pequeños y elegantes
- Notificaciones visuales (success, error, warning, info)
- Confirmaciones antes de eliminar
- Loading states en formularios
- Badges de estado con colores

## 🚀 Inicio Rápido

### 1. Instalar XAMPP/WAMP/LAMP

Descargar e instalar XAMPP desde: https://www.apachefriends.org/

### 2. Crear Base de Datos

```sql
-- En phpMyAdmin (http://localhost/phpmyadmin)
CREATE DATABASE db_sistema_web_cbit CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
```

### 3. Importar SQL

1. Seleccionar la base de datos `db_sistema_web_cbit`
2. Ir a "Importar"
3. Seleccionar `db_cbit.sql`
4. Clic en "Continuar"
5. Luego importar `insert_usuario_prueba.sql`

### 4. Copiar Archivos

Copiar la carpeta `sistema_cbit` a:
- **Windows (XAMPP)**: `C:\xampp\htdocs\sistema_cbit`
- **Windows (WAMP)**: `C:\wamp64\www\sistema_cbit`
- **Linux**: `/var/www/html/sistema_cbit`
- **Mac**: `/Applications/XAMPP/htdocs/sistema_cbit`

### 5. Configurar (Opcional)

Si tu MySQL tiene contraseña, editar `config/database.php`:

```php
private $password = "tu_contraseña";
```

### 6. Acceder al Sistema

1. Abrir navegador
2. Ir a: `http://localhost/sistema_cbit/login.html`
3. **Usuario**: `admin`
4. **Contraseña**: `password`
5. ¡Listo! 🎉

## 📁 Estructura del Proyecto

```
sistema_cbit/
├── login.html                 # ⭐ NUEVO - Página de login
├── index.html                 # Página principal del sistema
├── api/                       # Backend PHP
│   ├── auth.php              # ⭐ NUEVO - Autenticación
│   ├── usuarios.php
│   ├── solicitudes.php
│   ├── inventario.php
│   ├── mantenimiento.php
│   ├── auxiliares.php
│   └── dashboard.php
├── assets/js/
│   ├── api.js                # Cliente API
│   └── app_complete.js       # ⭐ NUEVO - App completa
├── config/
│   ├── database.php          # Conexión BD
│   └── cors.php              # CORS
├── db_cbit.sql               # Estructura BD
├── insert_usuario_prueba.sql # ⭐ NUEVO - Datos de prueba
├── README_V2.md              # Este archivo
├── ACTUALIZACION.md          # ⭐ NUEVO - Guía de actualización
└── INSTALACION_RAPIDA.txt    # Guía rápida
```

## 🔐 Credenciales de Prueba

Después de importar `insert_usuario_prueba.sql`:

- **Usuario**: `admin`
- **Contraseña**: `password`
- **Rol**: Administrador

## 📊 Módulos del Sistema

### 1. Dashboard
- Estadísticas en tiempo real
- Solicitudes del día
- Usuarios activos
- Equipos operativos
- Mantenimientos pendientes
- Próximas solicitudes

### 2. Gestión de Usuarios
- ✅ Crear usuarios con datos personales completos
- ✅ Editar usuarios existentes
- ✅ Eliminar usuarios
- ✅ Roles: Administrador, Docente, Estudiante, Usuario Externo
- ✅ Estados: Activo, Inactivo, Bloqueado

### 3. Gestión de Solicitudes
- ✅ Crear solicitudes de espacios y actividades
- ✅ Selección de usuario, espacio, actividad
- ✅ Fecha y hora
- ✅ Estados: Aprobado, Pendiente, Cancelado
- ✅ Eliminar solicitudes

### 4. Gestión de Inventario
- ✅ Agregar items al inventario
- ✅ Serial único por equipo
- ✅ Ubicación física
- ✅ Estados: Operativo, No operativo, Mantenimiento
- ✅ Vinculación con catálogo de equipos

### 5. Gestión de Mantenimiento
- ✅ Registrar mantenimientos
- ✅ Tipos: Preventivo, Correctivo, Incidencia
- ✅ Fecha de reporte y resolución
- ✅ Descripción de la falla
- ✅ Usuario que reporta

### 6. Configuración
- ✅ Espacios físicos
- ✅ Actividades
- ✅ Categorías de equipos
- ✅ Marcas
- ✅ Ubicaciones físicas
- ✅ Catálogo de equipos

## 🎯 Flujo de Uso Recomendado

### Primera Vez

1. **Login** con admin/password
2. **Configurar datos básicos**:
   - Agregar espacios (Sala 1, Sala 2, etc.)
   - Agregar actividades (Clase, Taller, etc.)
   - Agregar categorías (Computadoras, Monitores, etc.)
   - Agregar marcas (Dell, HP, etc.)
   - Agregar ubicaciones (Edificio A, etc.)
3. **Crear catálogo de equipos**:
   - Modelo, categoría, marca
4. **Agregar al inventario**:
   - Seleccionar equipo del catálogo
   - Asignar serial y ubicación
5. **Crear usuarios**:
   - Docentes, estudiantes, etc.
6. **Registrar solicitudes**:
   - Usuario solicita espacio para actividad
7. **Registrar mantenimientos**:
   - Cuando un equipo requiere mantenimiento

## 🔧 Tecnologías Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+ / MariaDB 10.3+
- **API**: REST con JSON
- **Autenticación**: Sesiones PHP + bcrypt
- **Iconos**: Font Awesome 6.4
- **Arquitectura**: MVC simplificado

## 🛡️ Seguridad

- ✅ Contraseñas hasheadas con bcrypt
- ✅ Sesiones PHP seguras
- ✅ Prepared statements (PDO)
- ✅ Validación de entrada
- ✅ Protección contra SQL injection
- ✅ CORS configurado

## 📱 Compatibilidad

### Navegadores
- Chrome 90+
- Firefox 88+
- Edge 90+
- Safari 14+

### Servidores
- Apache 2.4+ (recomendado)
- Nginx 1.18+

### PHP
- 7.4, 8.0, 8.1, 8.2

### Base de Datos
- MySQL 5.7, 8.0
- MariaDB 10.3, 10.4, 10.5, 10.6, 10.11

## 🐛 Solución de Problemas

### No puedo hacer login

**Síntoma**: "Usuario no encontrado"

**Solución**:
```sql
-- Ejecutar en phpMyAdmin
SOURCE insert_usuario_prueba.sql;
```

### Error de conexión a la base de datos

**Síntoma**: "Could not connect to database"

**Solución**:
1. Verificar que MySQL esté ejecutándose
2. Verificar credenciales en `config/database.php`
3. Verificar que la base de datos exista

### Los datos no se cargan

**Síntoma**: Tablas vacías o "Cargando..."

**Solución**:
1. Presionar F12 (consola del navegador)
2. Ver errores en la pestaña "Console"
3. Verificar que la API responda: `http://localhost/sistema_cbit/api/dashboard.php`
4. Limpiar caché del navegador (Ctrl+Shift+Delete)

### "Access-Control-Allow-Origin" error

**Síntoma**: Error CORS en consola

**Solución**:
1. Verificar que `config/cors.php` exista
2. Verificar que esté incluido en todos los archivos API
3. Reiniciar Apache

## 📖 Documentación de la API

### Autenticación

#### Login
```http
POST /api/auth.php
Content-Type: application/json

{
  "nombre_usuario": "admin",
  "contrasena": "password"
}
```

#### Logout
```http
POST /api/auth.php
Content-Type: application/json

{
  "action": "logout"
}
```

#### Verificar Sesión
```http
GET /api/auth.php
```

### Usuarios

```http
GET    /api/usuarios.php          # Listar todos
GET    /api/usuarios.php/{id}     # Obtener uno
POST   /api/usuarios.php          # Crear
PUT    /api/usuarios.php/{id}     # Actualizar
DELETE /api/usuarios.php/{id}     # Eliminar
```

### Solicitudes

```http
GET    /api/solicitudes.php       # Listar todas
POST   /api/solicitudes.php       # Crear
DELETE /api/solicitudes.php/{id}  # Eliminar
```

### Inventario

```http
GET    /api/inventario.php        # Listar todo
POST   /api/inventario.php        # Crear
DELETE /api/inventario.php/{id}   # Eliminar
```

### Mantenimiento

```http
GET    /api/mantenimiento.php     # Listar todos
POST   /api/mantenimiento.php     # Crear
DELETE /api/mantenimiento.php/{id} # Eliminar
```

### Dashboard

```http
GET /api/dashboard.php            # Estadísticas
```

## 🎨 Personalización

### Cambiar Colores

Editar variables CSS en `index.html`:

```css
:root {
    --cbit-blue: #0056A6;
    --cbit-green: #28A745;
    --cbit-yellow: #FFC107;
    --cbit-red: #DC3545;
}
```

### Cambiar Logo

Reemplazar el icono en la clase `.logo-icon`:

```html
<i class="fas fa-laptop-code"></i>
<!-- Cambiar por otro icono de Font Awesome -->
```

### Cambiar Nombre del Sistema

Editar en `index.html` y `login.html`:

```html
<h1>CBIT<span>Manager</span></h1>
<!-- Cambiar por tu nombre -->
```

## 📝 Datos de Prueba Incluidos

El archivo `insert_usuario_prueba.sql` incluye:

- ✅ 1 usuario administrador
- ✅ 5 espacios
- ✅ 5 actividades
- ✅ 6 categorías
- ✅ 6 marcas
- ✅ 5 ubicaciones físicas

## 🚀 Próximas Mejoras

- [ ] Edición completa de todas las entidades
- [ ] Búsqueda y filtros en tablas
- [ ] Paginación de resultados
- [ ] Exportar a PDF/Excel
- [ ] Calendario visual de solicitudes
- [ ] Gráficos en dashboard (Chart.js)
- [ ] Notificaciones por email
- [ ] Historial de cambios (audit log)
- [ ] Permisos granulares por rol
- [ ] Modo oscuro
- [ ] PWA (Progressive Web App)
- [ ] API REST documentada (Swagger)

## 📄 Licencia

Este proyecto es de uso educativo y puede ser modificado según las necesidades.

## 👥 Créditos

**Desarrollado para CBIT**  
Sistema de Gestión Integrado  
Versión 2.0 - 2024

---

## 📞 Soporte

Para problemas o preguntas:
1. Revisar `ACTUALIZACION.md`
2. Revisar `INSTALACION_RAPIDA.txt`
3. Verificar logs de PHP y consola del navegador
4. Verificar que todos los archivos estén en su lugar

---

**¡Gracias por usar el Sistema de Gestión CBIT!** 🎉
