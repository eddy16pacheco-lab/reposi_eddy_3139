-- =====================================================
-- SISTEMA DE GESTIÓN CBIT - BASE DE DATOS COMPLETA
-- Versión 3.0 - Con todos los requisitos implementados
-- =====================================================

-- Usar la base de datos
USE db_sistema_web_cbit;

-- =====================================================
-- MODIFICACIONES A TABLAS EXISTENTES
-- =====================================================

-- Agregar estado "Baja" a inventario
ALTER TABLE inventario 
MODIFY COLUMN estado ENUM('Operativo', 'No operativo', 'Mantenimiento', 'Baja') DEFAULT 'Operativo';

-- Agregar campos de seguridad a usuario
ALTER TABLE usuario 
ADD COLUMN intentos_fallidos INT DEFAULT 0,
ADD COLUMN bloqueado_hasta DATETIME NULL,
ADD COLUMN token_recuperacion VARCHAR(255) NULL,
ADD COLUMN token_expiracion DATETIME NULL,
ADD COLUMN ultimo_acceso DATETIME NULL;

-- Agregar campos a equipo para garantía
ALTER TABLE equipo
ADD COLUMN fecha_compra DATE NULL,
ADD COLUMN meses_garantia INT DEFAULT 12,
ADD COLUMN proveedor VARCHAR(100) NULL;

-- Agregar campos a espacio para control de aforo
ALTER TABLE espacio
ADD COLUMN capacidad_maxima INT DEFAULT 30,
ADD COLUMN descripcion TEXT NULL,
ADD COLUMN equipamiento TEXT NULL;

-- =====================================================
-- NUEVAS TABLAS - SISTEMA DE PRÉSTAMOS
-- =====================================================

CREATE TABLE IF NOT EXISTS prestamo (
    id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
    id_inventario INT NOT NULL,
    id_usuario_solicitante INT NOT NULL,
    id_usuario_autoriza INT NULL,
    fecha_prestamo DATETIME NOT NULL,
    fecha_devolucion_programada DATETIME NOT NULL,
    fecha_devolucion_real DATETIME NULL,
    estado ENUM('Activo', 'Devuelto', 'Vencido', 'Cancelado') DEFAULT 'Activo',
    observaciones TEXT NULL,
    firma_digital VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inventario) REFERENCES inventario(id_inventario) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_solicitante) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_autoriza) REFERENCES usuario(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS multa (
    id_multa INT AUTO_INCREMENT PRIMARY KEY,
    id_prestamo INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('Pendiente', 'Pagada', 'Condonada') DEFAULT 'Pendiente',
    fecha_multa DATE NOT NULL,
    fecha_pago DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_prestamo) REFERENCES prestamo(id_prestamo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS comprobante_prestamo (
    id_comprobante INT AUTO_INCREMENT PRIMARY KEY,
    id_prestamo INT NOT NULL,
    codigo_comprobante VARCHAR(50) UNIQUE NOT NULL,
    pdf_path VARCHAR(255) NULL,
    qr_code VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_prestamo) REFERENCES prestamo(id_prestamo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- NUEVAS TABLAS - GESTIÓN DE ESPACIOS MEJORADA
-- =====================================================

CREATE TABLE IF NOT EXISTS reserva_espacio (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_espacio INT NOT NULL,
    id_usuario INT NOT NULL,
    id_actividad INT NOT NULL,
    fecha_reserva DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    num_participantes INT NOT NULL,
    estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada', 'Completada') DEFAULT 'Pendiente',
    motivo_rechazo TEXT NULL,
    materiales_solicitados TEXT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_espacio) REFERENCES espacio(id_espacio) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_actividad) REFERENCES actividad(id_actividad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS equipos_reserva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    id_equipo INT NOT NULL,
    cantidad INT DEFAULT 1,
    FOREIGN KEY (id_reserva) REFERENCES reserva_espacio(id_reserva) ON DELETE CASCADE,
    FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- NUEVAS TABLAS - SISTEMA DE TICKETS DE MANTENIMIENTO
-- =====================================================

CREATE TABLE IF NOT EXISTS ticket_mantenimiento (
    id_ticket INT AUTO_INCREMENT PRIMARY KEY,
    numero_ticket VARCHAR(50) UNIQUE NOT NULL,
    id_mantenimiento INT NOT NULL,
    id_tecnico_asignado INT NULL,
    prioridad ENUM('Baja', 'Media', 'Alta', 'Crítica') DEFAULT 'Media',
    estado_ticket ENUM('Abierto', 'En Proceso', 'Resuelto', 'Cerrado', 'Reabierto') DEFAULT 'Abierto',
    tiempo_respuesta INT NULL COMMENT 'Minutos desde creación hasta asignación',
    tiempo_resolucion INT NULL COMMENT 'Minutos desde creación hasta resolución',
    notas_tecnico TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mantenimiento) REFERENCES mantenimiento(id_mantenimiento) ON DELETE CASCADE,
    FOREIGN KEY (id_tecnico_asignado) REFERENCES usuario(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS historial_ticket (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_ticket INT NOT NULL,
    id_usuario INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ticket) REFERENCES ticket_mantenimiento(id_ticket) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mantenimiento_preventivo_programado (
    id_programacion INT AUTO_INCREMENT PRIMARY KEY,
    id_inventario INT NOT NULL,
    tipo_mantenimiento VARCHAR(100) NOT NULL,
    frecuencia_dias INT NOT NULL,
    ultima_ejecucion DATE NULL,
    proxima_ejecucion DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inventario) REFERENCES inventario(id_inventario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- NUEVAS TABLAS - HISTORIAL DE MOVIMIENTOS
-- =====================================================

CREATE TABLE IF NOT EXISTS historial_movimiento (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_inventario INT NOT NULL,
    ubicacion_origen INT NULL,
    ubicacion_destino INT NOT NULL,
    id_usuario_responsable INT NOT NULL,
    fecha_movimiento DATETIME NOT NULL,
    motivo TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inventario) REFERENCES inventario(id_inventario) ON DELETE CASCADE,
    FOREIGN KEY (ubicacion_origen) REFERENCES ubicacion_fisica(id_ubicacion) ON DELETE SET NULL,
    FOREIGN KEY (ubicacion_destino) REFERENCES ubicacion_fisica(id_ubicacion) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_responsable) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- NUEVAS TABLAS - ACTIVIDADES EDUCATIVAS
-- =====================================================

CREATE TABLE IF NOT EXISTS taller (
    id_taller INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    id_facilitador INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    horario VARCHAR(100) NULL,
    cupo_maximo INT NOT NULL,
    cupo_disponible INT NOT NULL,
    estado ENUM('Planificado', 'En Curso', 'Finalizado', 'Cancelado') DEFAULT 'Planificado',
    requisitos TEXT NULL,
    materiales TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_facilitador) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS inscripcion_taller (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_taller INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_inscripcion DATETIME NOT NULL,
    estado ENUM('Inscrito', 'Asistió', 'No Asistió', 'Cancelado') DEFAULT 'Inscrito',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_taller) REFERENCES taller(id_taller) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY unique_inscripcion (id_taller, id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS asistencia (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    fecha_sesion DATE NOT NULL,
    hora_entrada TIME NULL,
    hora_salida TIME NULL,
    presente BOOLEAN DEFAULT FALSE,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion_taller(id_inscripcion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS evaluacion (
    id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
    id_taller INT NOT NULL,
    id_usuario INT NOT NULL,
    calificacion_contenido INT CHECK (calificacion_contenido BETWEEN 1 AND 5),
    calificacion_facilitador INT CHECK (calificacion_facilitador BETWEEN 1 AND 5),
    calificacion_instalaciones INT CHECK (calificacion_instalaciones BETWEEN 1 AND 5),
    comentarios TEXT NULL,
    sugerencias TEXT NULL,
    fecha_evaluacion DATETIME NOT NULL,
    FOREIGN KEY (id_taller) REFERENCES taller(id_taller) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS certificado (
    id_certificado INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    codigo_certificado VARCHAR(50) UNIQUE NOT NULL,
    fecha_emision DATE NOT NULL,
    pdf_path VARCHAR(255) NULL,
    qr_verificacion VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion_taller(id_inscripcion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- NUEVAS TABLAS - SISTEMA DE NOTIFICACIONES
-- =====================================================

CREATE TABLE IF NOT EXISTS notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('Info', 'Alerta', 'Recordatorio', 'Sistema') DEFAULT 'Info',
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    url_accion VARCHAR(255) NULL,
    fecha_envio DATETIME NOT NULL,
    fecha_lectura DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS anuncio (
    id_anuncio INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,
    tipo ENUM('General', 'Urgente', 'Mantenimiento', 'Evento') DEFAULT 'General',
    id_usuario_creador INT NOT NULL,
    fecha_publicacion DATETIME NOT NULL,
    fecha_expiracion DATETIME NULL,
    activo BOOLEAN DEFAULT TRUE,
    archivo_adjunto VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario_creador) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mensaje_interno (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    id_remitente INT NOT NULL,
    id_destinatario INT NOT NULL,
    asunto VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,
    leido BOOLEAN DEFAULT FALSE,
    fecha_envio DATETIME NOT NULL,
    fecha_lectura DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_remitente) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_destinatario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- NUEVAS TABLAS - LOGS Y AUDITORÍA
-- =====================================================

CREATE TABLE IF NOT EXISTS log_auditoria (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(100) NULL,
    registro_id INT NULL,
    datos_anteriores TEXT NULL,
    datos_nuevos TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS sesion_usuario (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token_sesion VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- NUEVAS TABLAS - REQUISITOS ESPECÍFICOS CBIT
-- =====================================================

CREATE TABLE IF NOT EXISTS proyecto_educativo (
    id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    id_docente_responsable INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    estado ENUM('Planificado', 'En Desarrollo', 'Finalizado', 'Cancelado') DEFAULT 'Planificado',
    objetivos TEXT NULL,
    resultados TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_docente_responsable) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS recurso_digital (
    id_recurso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('Software', 'Plataforma', 'Contenido Digital', 'Otro') NOT NULL,
    descripcion TEXT NULL,
    url VARCHAR(255) NULL,
    licencia VARCHAR(100) NULL,
    fecha_adquisicion DATE NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS competencia_tecnologica (
    id_competencia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    nivel ENUM('Básico', 'Intermedio', 'Avanzado') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS usuario_competencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_competencia INT NOT NULL,
    fecha_adquisicion DATE NOT NULL,
    evidencia TEXT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_competencia) REFERENCES competencia_tecnologica(id_competencia) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS visita_institucional (
    id_visita INT AUTO_INCREMENT PRIMARY KEY,
    institucion VARCHAR(200) NOT NULL,
    num_visitantes INT NOT NULL,
    fecha_visita DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    responsable_visita VARCHAR(200) NOT NULL,
    telefono_contacto VARCHAR(20) NULL,
    proposito TEXT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS actividad_comunitaria (
    id_actividad_comunitaria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    fecha_actividad DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    publico_objetivo VARCHAR(100) NULL,
    num_participantes INT NULL,
    id_coordinador INT NOT NULL,
    estado ENUM('Planificada', 'Realizada', 'Cancelada') DEFAULT 'Planificada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_coordinador) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS control_acceso_fisico (
    id_acceso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    tipo_acceso ENUM('Entrada', 'Salida') NOT NULL,
    fecha_hora DATETIME NOT NULL,
    metodo ENUM('Tarjeta', 'Biométrico', 'Manual') DEFAULT 'Manual',
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS suministro (
    id_suministro INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('Toner', 'Papel', 'Insumo', 'Otro') NOT NULL,
    cantidad_actual INT NOT NULL,
    cantidad_minima INT NOT NULL,
    unidad_medida VARCHAR(50) NOT NULL,
    ubicacion VARCHAR(100) NULL,
    ultima_compra DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS movimiento_suministro (
    id_movimiento_suministro INT AUTO_INCREMENT PRIMARY KEY,
    id_suministro INT NOT NULL,
    tipo_movimiento ENUM('Entrada', 'Salida') NOT NULL,
    cantidad INT NOT NULL,
    id_usuario_responsable INT NOT NULL,
    fecha_movimiento DATETIME NOT NULL,
    motivo TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_suministro) REFERENCES suministro(id_suministro) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_responsable) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS condicion_ambiental (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_espacio INT NOT NULL,
    temperatura DECIMAL(5,2) NULL,
    humedad DECIMAL(5,2) NULL,
    fecha_hora DATETIME NOT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_espacio) REFERENCES espacio(id_espacio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- =====================================================
-- ÍNDICES PARA MEJORAR RENDIMIENTO
-- =====================================================

CREATE INDEX idx_prestamo_estado ON prestamo(estado);
CREATE INDEX idx_prestamo_fechas ON prestamo(fecha_devolucion_programada, fecha_devolucion_real);
CREATE INDEX idx_reserva_fecha ON reserva_espacio(fecha_reserva, hora_inicio);
CREATE INDEX idx_ticket_estado ON ticket_mantenimiento(estado_ticket);
CREATE INDEX idx_notificacion_usuario ON notificacion(id_usuario, leida);
CREATE INDEX idx_log_fecha ON log_auditoria(created_at);
CREATE INDEX idx_sesion_activa ON sesion_usuario(activa, fecha_inicio);

-- =====================================================
-- VISTAS PARA REPORTES
-- =====================================================

CREATE OR REPLACE VIEW vista_prestamos_activos AS
SELECT 
    p.id_prestamo,
    p.fecha_prestamo,
    p.fecha_devolucion_programada,
    p.estado,
    CONCAT(per.nombre, ' ', per.apellido) AS solicitante,
    e.modelo AS equipo,
    i.serial,
    DATEDIFF(NOW(), p.fecha_devolucion_programada) AS dias_retraso
FROM prestamo p
JOIN usuario u ON p.id_usuario_solicitante = u.id_usuario
JOIN persona per ON u.id_persona = per.id_persona
JOIN inventario i ON p.id_inventario = i.id_inventario
JOIN equipo e ON i.id_equipo = e.id_equipo
WHERE p.estado IN ('Activo', 'Vencido');

CREATE OR REPLACE VIEW vista_estadisticas_equipos AS
SELECT 
    e.modelo,
    c.nombre AS categoria,
    COUNT(i.id_inventario) AS total_equipos,
    SUM(CASE WHEN i.estado = 'Operativo' THEN 1 ELSE 0 END) AS operativos,
    SUM(CASE WHEN i.estado = 'No operativo' THEN 1 ELSE 0 END) AS no_operativos,
    SUM(CASE WHEN i.estado = 'Mantenimiento' THEN 1 ELSE 0 END) AS en_mantenimiento,
    SUM(CASE WHEN i.estado = 'Baja' THEN 1 ELSE 0 END) AS dados_baja
FROM equipo e
JOIN categoria c ON e.id_categoria = c.id_categoria
LEFT JOIN inventario i ON e.id_equipo = i.id_equipo
GROUP BY e.id_equipo, e.modelo, c.nombre;

CREATE OR REPLACE VIEW vista_tickets_pendientes AS
SELECT 
    t.numero_ticket,
    t.prioridad,
    t.estado_ticket,
    m.tipo AS tipo_mantenimiento,
    e.modelo AS equipo,
    i.serial,
    CONCAT(per.nombre, ' ', per.apellido) AS reportado_por,
    CONCAT(tec.nombre, ' ', tec.apellido) AS tecnico_asignado,
    t.created_at AS fecha_creacion,
    TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_abierto
FROM ticket_mantenimiento t
JOIN mantenimiento m ON t.id_mantenimiento = m.id_mantenimiento
JOIN inventario i ON m.id_inventario = i.id_inventario
JOIN equipo e ON i.id_equipo = e.id_equipo
JOIN usuario u ON m.id_usuario = u.id_usuario
JOIN persona per ON u.id_persona = per.id_persona
LEFT JOIN usuario utec ON t.id_tecnico_asignado = utec.id_usuario
LEFT JOIN persona tec ON utec.id_persona = tec.id_persona
WHERE t.estado_ticket NOT IN ('Cerrado');

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
