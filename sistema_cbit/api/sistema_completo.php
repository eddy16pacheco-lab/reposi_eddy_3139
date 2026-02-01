<?php
/**
 * API Unificada para Funcionalidades Adicionales
 * Incluye: Talleres, Notificaciones, Reportes, Recuperación de contraseña
 */

require_once '../config/database.php';
require_once '../config/cors.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Determinar el módulo solicitado
$modulo = $_GET['modulo'] ?? $data['modulo'] ?? null;

try {
    switch($modulo) {
        case 'talleres':
            gestionarTalleres($db, $method, $data);
            break;
        case 'notificaciones':
            gestionarNotificaciones($db, $method, $data);
            break;
        case 'reportes':
            gestionarReportes($db, $method, $data);
            break;
        case 'recuperar_password':
            recuperarPassword($db, $data);
            break;
        case 'logs':
            obtenerLogs($db, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Módulo no especificado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ==================== TALLERES ====================

function gestionarTalleres($db, $method, $data) {
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                getTaller($db, $_GET['id']);
            } elseif (isset($_GET['inscripciones'])) {
                getInscripciones($db, $_GET['inscripciones']);
            } else {
                getAllTalleres($db);
            }
            break;
        case 'POST':
            if (isset($data['accion'])) {
                switch($data['accion']) {
                    case 'inscribir':
                        inscribirTaller($db, $data);
                        break;
                    case 'registrar_asistencia':
                        registrarAsistencia($db, $data);
                        break;
                    case 'evaluar':
                        evaluarTaller($db, $data);
                        break;
                    case 'generar_certificado':
                        generarCertificado($db, $data);
                        break;
                    default:
                        crearTaller($db, $data);
                }
            } else {
                crearTaller($db, $data);
            }
            break;
    }
}

function getAllTalleres($db) {
    $query = "SELECT 
                t.*,
                CONCAT(p.nombre, ' ', p.apellido) AS facilitador,
                (t.cupo_maximo - t.cupo_disponible) AS inscritos
              FROM taller t
              JOIN usuario u ON t.id_facilitador = u.id_usuario
              JOIN persona p ON u.id_persona = p.id_persona
              ORDER BY t.fecha_inicio DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getTaller($db, $id) {
    $query = "SELECT 
                t.*,
                CONCAT(p.nombre, ' ', p.apellido) AS facilitador,
                u.correo AS correo_facilitador
              FROM taller t
              JOIN usuario u ON t.id_facilitador = u.id_usuario
              JOIN persona p ON u.id_persona = p.id_persona
              WHERE t.id_taller = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
}

function crearTaller($db, $data) {
    $query = "INSERT INTO taller 
              (nombre, descripcion, id_facilitador, fecha_inicio, fecha_fin, horario, 
               cupo_maximo, cupo_disponible, requisitos, materiales) 
              VALUES 
              (:nombre, :descripcion, :id_facilitador, :fecha_inicio, :fecha_fin, :horario, 
               :cupo_maximo, :cupo_maximo, :requisitos, :materiales)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':descripcion', $data['descripcion']);
    $stmt->bindParam(':id_facilitador', $data['id_facilitador']);
    $stmt->bindParam(':fecha_inicio', $data['fecha_inicio']);
    $stmt->bindParam(':fecha_fin', $data['fecha_fin']);
    $stmt->bindParam(':horario', $data['horario']);
    $stmt->bindParam(':cupo_maximo', $data['cupo_maximo']);
    $stmt->bindParam(':requisitos', $data['requisitos']);
    $stmt->bindParam(':materiales', $data['materiales']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Taller creado', 'id' => $db->lastInsertId()]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear taller']);
    }
}

function inscribirTaller($db, $data) {
    // Verificar cupo
    $query_cupo = "SELECT cupo_disponible FROM taller WHERE id_taller = :id";
    $stmt_cupo = $db->prepare($query_cupo);
    $stmt_cupo->bindParam(':id', $data['id_taller']);
    $stmt_cupo->execute();
    $taller = $stmt_cupo->fetch(PDO::FETCH_ASSOC);
    
    if ($taller['cupo_disponible'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No hay cupos disponibles']);
        return;
    }
    
    $query = "INSERT INTO inscripcion_taller (id_taller, id_usuario, fecha_inscripcion) 
              VALUES (:id_taller, :id_usuario, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_taller', $data['id_taller']);
    $stmt->bindParam(':id_usuario', $data['id_usuario']);
    
    if ($stmt->execute()) {
        // Actualizar cupo
        $query_update = "UPDATE taller SET cupo_disponible = cupo_disponible - 1 WHERE id_taller = :id";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bindParam(':id', $data['id_taller']);
        $stmt_update->execute();
        
        echo json_encode(['success' => true, 'message' => 'Inscripción exitosa']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error en la inscripción']);
    }
}

function registrarAsistencia($db, $data) {
    $query = "INSERT INTO asistencia (id_inscripcion, fecha_sesion, hora_entrada, presente) 
              VALUES (:id_inscripcion, :fecha_sesion, NOW(), TRUE)
              ON DUPLICATE KEY UPDATE hora_entrada = NOW(), presente = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_inscripcion', $data['id_inscripcion']);
    $stmt->bindParam(':fecha_sesion', $data['fecha_sesion']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Asistencia registrada']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al registrar asistencia']);
    }
}

function evaluarTaller($db, $data) {
    $query = "INSERT INTO evaluacion 
              (id_taller, id_usuario, calificacion_contenido, calificacion_facilitador, 
               calificacion_instalaciones, comentarios, sugerencias, fecha_evaluacion) 
              VALUES 
              (:id_taller, :id_usuario, :contenido, :facilitador, :instalaciones, 
               :comentarios, :sugerencias, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_taller', $data['id_taller']);
    $stmt->bindParam(':id_usuario', $data['id_usuario']);
    $stmt->bindParam(':contenido', $data['calificacion_contenido']);
    $stmt->bindParam(':facilitador', $data['calificacion_facilitador']);
    $stmt->bindParam(':instalaciones', $data['calificacion_instalaciones']);
    $stmt->bindParam(':comentarios', $data['comentarios']);
    $stmt->bindParam(':sugerencias', $data['sugerencias']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Evaluación registrada']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al registrar evaluación']);
    }
}

function generarCertificado($db, $data) {
    $codigo = 'CERT-' . date('Ymd') . '-' . str_pad($data['id_inscripcion'], 6, '0', STR_PAD_LEFT);
    $query = "INSERT INTO certificado (id_inscripcion, codigo_certificado, fecha_emision) 
              VALUES (:id_inscripcion, :codigo, CURDATE())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_inscripcion', $data['id_inscripcion']);
    $stmt->bindParam(':codigo', $codigo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Certificado generado', 'codigo' => $codigo]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al generar certificado']);
    }
}

function getInscripciones($db, $id_taller) {
    $query = "SELECT 
                i.*,
                CONCAT(p.nombre, ' ', p.apellido) AS participante,
                u.correo,
                (SELECT COUNT(*) FROM asistencia a WHERE a.id_inscripcion = i.id_inscripcion AND a.presente = TRUE) AS asistencias
              FROM inscripcion_taller i
              JOIN usuario u ON i.id_usuario = u.id_usuario
              JOIN persona p ON u.id_persona = p.id_persona
              WHERE i.id_taller = :id_taller";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_taller', $id_taller);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ==================== NOTIFICACIONES ====================

function gestionarNotificaciones($db, $method, $data) {
    switch($method) {
        case 'GET':
            if (isset($_GET['usuario'])) {
                getNotificacionesUsuario($db, $_GET['usuario']);
            } elseif (isset($_GET['anuncios'])) {
                getAnuncios($db);
            }
            break;
        case 'POST':
            if (isset($data['tipo']) && $data['tipo'] == 'anuncio') {
                crearAnuncio($db, $data);
            } else {
                crearNotificacion($db, $data);
            }
            break;
        case 'PUT':
            marcarNotificacionLeida($db, $data);
            break;
    }
}

function getNotificacionesUsuario($db, $id_usuario) {
    $query = "SELECT * FROM notificacion 
              WHERE id_usuario = :id_usuario 
              ORDER BY fecha_envio DESC 
              LIMIT 50";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function crearNotificacion($db, $data) {
    $query = "INSERT INTO notificacion (id_usuario, tipo, titulo, mensaje, fecha_envio) 
              VALUES (:id_usuario, :tipo, :titulo, :mensaje, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $data['id_usuario']);
    $stmt->bindParam(':tipo', $data['tipo']);
    $stmt->bindParam(':titulo', $data['titulo']);
    $stmt->bindParam(':mensaje', $data['mensaje']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notificación enviada']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al enviar notificación']);
    }
}

function marcarNotificacionLeida($db, $data) {
    $query = "UPDATE notificacion SET leida = TRUE, fecha_lectura = NOW() WHERE id_notificacion = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id_notificacion']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notificación marcada como leída']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error']);
    }
}

function getAnuncios($db) {
    $query = "SELECT 
                a.*,
                CONCAT(p.nombre, ' ', p.apellido) AS creador
              FROM anuncio a
              JOIN usuario u ON a.id_usuario_creador = u.id_usuario
              JOIN persona p ON u.id_persona = p.id_persona
              WHERE a.activo = TRUE 
              AND (a.fecha_expiracion IS NULL OR a.fecha_expiracion > NOW())
              ORDER BY a.fecha_publicacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function crearAnuncio($db, $data) {
    $query = "INSERT INTO anuncio 
              (titulo, contenido, tipo, id_usuario_creador, fecha_publicacion, fecha_expiracion) 
              VALUES 
              (:titulo, :contenido, :tipo, :id_usuario, NOW(), :fecha_exp)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':titulo', $data['titulo']);
    $stmt->bindParam(':contenido', $data['contenido']);
    $stmt->bindParam(':tipo', $data['tipo_anuncio']);
    $stmt->bindParam(':id_usuario', $data['id_usuario']);
    $stmt->bindParam(':fecha_exp', $data['fecha_expiracion']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Anuncio publicado']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al publicar anuncio']);
    }
}

// ==================== REPORTES ====================

function gestionarReportes($db, $method, $data) {
    if (isset($_GET['tipo'])) {
        switch($_GET['tipo']) {
            case 'uso_equipos':
                reporteUsoEquipos($db);
                break;
            case 'uso_espacios':
                reporteUsoEspacios($db);
                break;
            case 'mantenimientos':
                reporteMantenimientos($db);
                break;
            case 'actividades':
                reporteActividades($db);
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo de reporte no válido']);
        }
    }
}

function reporteUsoEquipos($db) {
    $query = "SELECT 
                e.modelo,
                c.nombre AS categoria,
                COUNT(p.id_prestamo) AS total_prestamos,
                AVG(DATEDIFF(p.fecha_devolucion_real, p.fecha_prestamo)) AS dias_promedio_prestamo,
                SUM(CASE WHEN p.estado = 'Vencido' THEN 1 ELSE 0 END) AS prestamos_vencidos
              FROM equipo e
              JOIN categoria c ON e.id_categoria = c.id_categoria
              LEFT JOIN inventario i ON e.id_equipo = i.id_equipo
              LEFT JOIN prestamo p ON i.id_inventario = p.id_inventario
              GROUP BY e.id_equipo, e.modelo, c.nombre
              ORDER BY total_prestamos DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function reporteUsoEspacios($db) {
    $query = "SELECT 
                e.nombre AS espacio,
                COUNT(r.id_reserva) AS total_reservas,
                SUM(r.num_participantes) AS total_participantes,
                AVG(r.num_participantes) AS promedio_participantes
              FROM espacio e
              LEFT JOIN reserva_espacio r ON e.id_espacio = r.id_espacio
              WHERE r.estado = 'Completada'
              GROUP BY e.id_espacio, e.nombre
              ORDER BY total_reservas DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function reporteMantenimientos($db) {
    $query = "SELECT 
                m.tipo,
                COUNT(*) AS total,
                AVG(t.tiempo_resolucion) AS tiempo_promedio_resolucion,
                SUM(CASE WHEN t.estado_ticket = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados,
                SUM(CASE WHEN t.estado_ticket IN ('Abierto', 'En Proceso') THEN 1 ELSE 0 END) AS pendientes
              FROM mantenimiento m
              LEFT JOIN ticket_mantenimiento t ON m.id_mantenimiento = t.id_mantenimiento
              GROUP BY m.tipo";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function reporteActividades($db) {
    $query = "SELECT 
                t.nombre,
                t.fecha_inicio,
                t.fecha_fin,
                t.cupo_maximo,
                (t.cupo_maximo - t.cupo_disponible) AS inscritos,
                AVG(e.calificacion_contenido) AS promedio_contenido,
                AVG(e.calificacion_facilitador) AS promedio_facilitador
              FROM taller t
              LEFT JOIN evaluacion e ON t.id_taller = e.id_taller
              WHERE t.estado = 'Finalizado'
              GROUP BY t.id_taller";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ==================== RECUPERACIÓN DE CONTRASEÑA ====================

function recuperarPassword($db, $data) {
    $correo = $data['correo'];
    
    // Verificar si el usuario existe
    $query = "SELECT id_usuario FROM usuario WHERE correo = :correo";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':correo', $correo);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        // Por seguridad, no revelar si el correo existe
        echo json_encode(['success' => true, 'message' => 'Si el correo existe, recibirás instrucciones']);
        return;
    }
    
    // Generar token
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $query_update = "UPDATE usuario 
                     SET token_recuperacion = :token, token_expiracion = :expiracion 
                     WHERE id_usuario = :id";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->bindParam(':token', $token);
    $stmt_update->bindParam(':expiracion', $expiracion);
    $stmt_update->bindParam(':id', $usuario['id_usuario']);
    
    if ($stmt_update->execute()) {
        // Aquí se enviaría el correo con el enlace de recuperación
        // Por ahora solo retornamos el token (en producción NO hacer esto)
        echo json_encode([
            'success' => true, 
            'message' => 'Instrucciones enviadas al correo',
            'token' => $token // SOLO PARA DESARROLLO
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al procesar solicitud']);
    }
}

// ==================== LOGS ====================

function obtenerLogs($db, $data) {
    $query = "SELECT 
                l.*,
                CONCAT(p.nombre, ' ', p.apellido) AS usuario
              FROM log_auditoria l
              LEFT JOIN usuario u ON l.id_usuario = u.id_usuario
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              ORDER BY l.created_at DESC
              LIMIT 100";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
?>
