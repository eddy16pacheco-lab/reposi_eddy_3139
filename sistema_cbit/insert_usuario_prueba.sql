-- Script para insertar usuario de prueba
-- Usuario: admin
-- Contraseña: password

-- Insertar persona
INSERT INTO persona (nombre, apellido, cedula, telefono) 
VALUES ('Admin', 'Sistema', '00000000', '0000000000');

-- Obtener el ID de la persona insertada y crear usuario
INSERT INTO usuario (id_persona, nombre_usuario, contrasena_usuario, correo, estado, roles) 
VALUES (
    (SELECT id_persona FROM persona WHERE cedula = '00000000'),
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin@cbit.org',
    'Activo',
    'Administrador'
);

-- Insertar datos de prueba adicionales

-- Espacios
INSERT INTO espacio (nombre) VALUES 
('Sala de Computación 1'),
('Sala de Computación 2'),
('Laboratorio de Redes'),
('Auditorio'),
('Sala de Reuniones');

-- Actividades
INSERT INTO actividad (nombre) VALUES 
('Clase de Programación'),
('Taller de Redes'),
('Conferencia'),
('Reunión'),
('Práctica de Laboratorio');

-- Categorías
INSERT INTO categoria (nombre) VALUES 
('Computadoras'),
('Monitores'),
('Impresoras'),
('Proyectores'),
('Switches'),
('Routers');

-- Marcas
INSERT INTO marca (nombre) VALUES 
('Dell'),
('HP'),
('Lenovo'),
('Cisco'),
('Epson'),
('Samsung');

-- Ubicaciones
INSERT INTO ubicacion_fisica (nombre) VALUES 
('Edificio A - Piso 1'),
('Edificio A - Piso 2'),
('Edificio B - Piso 1'),
('Edificio C - Laboratorio'),
('Almacén Principal');
