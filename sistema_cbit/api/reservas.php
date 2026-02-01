<?php
/**
 * API de Reservas de Espacios
 * Gestión completa de reservas con calendario, equipos y materiales
 */

require_once '../config/database.php';
require_once '../config/cors.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

$uri_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$id = isset($uri_parts[count($uri_parts) - 1]) && is_numeric($uri_parts[count($uri_parts) - 1]) 
    ? (int)$uri_parts[count($uri_parts) - 1] 
    : null;

try {
    switch($method) {
        case 'GET':
            if ($id) {
                getReserva($db, $id);
            } else {
                if (isset($_GET['calendario'])) {
                    getCalendarioReservas($db);
                } elseif (isset($_GET['espacio'])) {
                    getReservasPorEspacio($db, $_GET['espacio']);
                } elseif (isset($_GET['usuario'])) {
                    getReservasPorUsuario($db, $_GET['usuario']);
                } elseif (isset($_GET['disponibilidad'])) {
                    verificarDisponibilidad($db, $_GET);
                } else {
                    getAllReservas($db);
                }
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['accion'])) {
                switch($data['accion']) {
                    case 'aprobar':
                        aprobarReserva($db, $data);
                        break;
                    case 'rechazar':
                        rechazarReserva($db, $data);
                        break;
                    case 'completar':
                        completarReserva($db, $data);
                        break;
                    default:
                        crearReserva($db, $data);
                }
            } else {
                crearReserva($db, $data);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents("php://input"), true);
            updateReserva($db, $id, $data);
            break;
            
        case 'DELETE':
            deleteReserva($db, $id);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ==================== FUNCIONES ====================

function getAllReservas($db) {
    $query = "SELECT 
                r.*,
                e.nombre AS espacio,
                e.capacidad_maxima,
                a.nombre AS actividad,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante,
                u.correo AS correo_solicitante,
                (SELECT COUNT(*) FROM equipos_reserva WHERE id_reserva = r.id_reserva) AS num_equipos
              FROM reserva_espacio r
              JOIN espacio e ON r.id_espacio = e.id_espacio
              JOIN actividad a ON r.id_actividad = a.id_actividad
              JOIN usuario u ON r.id_usuario = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              ORDER BY r.fecha_reserva DESC, r.hora_inicio DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $reservas]);
}

function getReserva($db, $id) {
    $query = "SELECT 
                r.*,
                e.nombre AS espacio,
                e.capacidad_maxima,
                e.descripcion AS descripcion_espacio,
                a.nombre AS actividad,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante,
                u.correo AS correo_solicitante,
                per.telefono AS telefono_solicitante
              FROM reserva_espacio r
              JOIN espacio e ON r.id_espacio = e.id_espacio
              JOIN actividad a ON r.id_actividad = a.id_actividad
              JOIN usuario u ON r.id_usuario = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              WHERE r.id_reserva = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reserva) {
        // Obtener equipos de la reserva
        $query_eq = "SELECT 
                        er.*,
                        eq.modelo,
                        c.nombre AS categoria
                     FROM equipos_reserva er
                     JOIN equipo eq ON er.id_equipo = eq.id_equipo
                     JOIN categoria c ON eq.id_categoria = c.id_categoria
                     WHERE er.id_reserva = :id";
        $stmt_eq = $db->prepare($query_eq);
        $stmt_eq->bindParam(':id', $id);
        $stmt_eq->execute();
        $reserva['equipos'] = $stmt_eq->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $reserva]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
    }
}

function getReservasPorEspacio($db, $id_espacio) {
    $query = "SELECT 
                r.*,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante,
                a.nombre AS actividad
              FROM reserva_espacio r
              JOIN usuario u ON r.id_usuario = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              JOIN actividad a ON r.id_actividad = a.id_actividad
              WHERE r.id_espacio = :id_espacio
              ORDER BY r.fecha_reserva DESC, r.hora_inicio DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_espacio', $id_espacio);
    $stmt->execute();
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $reservas]);
}

function getReservasPorUsuario($db, $id_usuario) {
    $query = "SELECT 
                r.*,
                e.nombre AS espacio,
                a.nombre AS actividad
              FROM reserva_espacio r
              JOIN espacio e ON r.id_espacio = e.id_espacio
              JOIN actividad a ON r.id_actividad = a.id_actividad
              WHERE r.id_usuario = :id_usuario
              ORDER BY r.fecha_reserva DESC, r.hora_inicio DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $reservas]);
}

function getCalendarioReservas($db) {
    $query = "SELECT 
                r.id_reserva,
                CONCAT(r.fecha_reserva, ' ', r.hora_inicio) AS start,
                CONCAT(r.fecha_reserva, ' ', r.hora_fin) AS end,
                CONCAT(e.nombre, ' - ', a.nombre) AS title,
                r.estado,
                CASE 
                    WHEN r.estado = 'Aprobada' THEN '#28a745'
                    WHEN r.estado = 'Pendiente' THEN '#ffc107'
                    WHEN r.estado = 'Rechazada' THEN '#dc3545'
                    WHEN r.estado = 'Completada' THEN '#6c757d'
                    ELSE '#17a2b8'
                END AS color,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante
              FROM reserva_espacio r
              JOIN espacio e ON r.id_espacio = e.id_espacio
              JOIN actividad a ON r.id_actividad = a.id_actividad
              JOIN usuario u ON r.id_usuario = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              WHERE r.estado NOT IN ('Cancelada', 'Rechazada')";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $eventos]);
}

function verificarDisponibilidad($db, $params) {
    $id_espacio = $params['espacio'];
    $fecha = $params['fecha'];
    $hora_inicio = $params['hora_inicio'];
    $hora_fin = $params['hora_fin'];
    
    $query = "SELECT COUNT(*) as conflictos
              FROM reserva_espacio
              WHERE id_espacio = :id_espacio
              AND fecha_reserva = :fecha
              AND estado IN ('Aprobada', 'Pendiente')
              AND (
                  (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)
              )";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_espacio', $id_espacio);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':hora_inicio', $hora_inicio);
    $stmt->bindParam(':hora_fin', $hora_fin);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $disponible = $result['conflictos'] == 0;
    echo json_encode([
        'success' => true, 
        'disponible' => $disponible,
        'message' => $disponible ? 'Espacio disponible' : 'Espacio no disponible en ese horario'
    ]);
}

function crearReserva($db, $data) {
    // Verificar capacidad
    $query_cap = "SELECT capacidad_maxima FROM espacio WHERE id_espacio = :id_espacio";
    $stmt_cap = $db->prepare($query_cap);
    $stmt_cap->bindParam(':id_espacio', $data['id_espacio']);
    $stmt_cap->execute();
    $espacio = $stmt_cap->fetch(PDO::FETCH_ASSOC);
    
    if ($data['num_participantes'] > $espacio['capacidad_maxima']) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => "El número de participantes excede la capacidad máxima ({$espacio['capacidad_maxima']})"
        ]);
        return;
    }
    
    // Verificar disponibilidad
    $query_disp = "SELECT COUNT(*) as conflictos
                   FROM reserva_espacio
                   WHERE id_espacio = :id_espacio
                   AND fecha_reserva = :fecha
                   AND estado IN ('Aprobada', 'Pendiente')
                   AND (
                       (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)
                   )";
    
    $stmt_disp = $db->prepare($query_disp);
    $stmt_disp->bindParam(':id_espacio', $data['id_espacio']);
    $stmt_disp->bindParam(':fecha', $data['fecha_reserva']);
    $stmt_disp->bindParam(':hora_inicio', $data['hora_inicio']);
    $stmt_disp->bindParam(':hora_fin', $data['hora_fin']);
    $stmt_disp->execute();
    $result_disp = $stmt_disp->fetch(PDO::FETCH_ASSOC);
    
    if ($result_disp['conflictos'] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El espacio no está disponible en ese horario']);
        return;
    }
    
    $query = "INSERT INTO reserva_espacio 
              (id_espacio, id_usuario, id_actividad, fecha_reserva, hora_inicio, hora_fin, 
               num_participantes, materiales_solicitados, observaciones, estado) 
              VALUES 
              (:id_espacio, :id_usuario, :id_actividad, :fecha_reserva, :hora_inicio, :hora_fin, 
               :num_participantes, :materiales_solicitados, :observaciones, 'Pendiente')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_espacio', $data['id_espacio']);
    $stmt->bindParam(':id_usuario', $data['id_usuario']);
    $stmt->bindParam(':id_actividad', $data['id_actividad']);
    $stmt->bindParam(':fecha_reserva', $data['fecha_reserva']);
    $stmt->bindParam(':hora_inicio', $data['hora_inicio']);
    $stmt->bindParam(':hora_fin', $data['hora_fin']);
    $stmt->bindParam(':num_participantes', $data['num_participantes']);
    $stmt->bindParam(':materiales_solicitados', $data['materiales_solicitados']);
    $stmt->bindParam(':observaciones', $data['observaciones']);
    
    if ($stmt->execute()) {
        $id_reserva = $db->lastInsertId();
        
        // Agregar equipos si se especificaron
        if (isset($data['equipos']) && is_array($data['equipos'])) {
            foreach ($data['equipos'] as $equipo) {
                $query_eq = "INSERT INTO equipos_reserva (id_reserva, id_equipo, cantidad) 
                             VALUES (:id_reserva, :id_equipo, :cantidad)";
                $stmt_eq = $db->prepare($query_eq);
                $stmt_eq->bindParam(':id_reserva', $id_reserva);
                $stmt_eq->bindParam(':id_equipo', $equipo['id_equipo']);
                $stmt_eq->bindParam(':cantidad', $equipo['cantidad']);
                $stmt_eq->execute();
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Reserva creada exitosamente',
            'id_reserva' => $id_reserva
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear la reserva']);
    }
}

function aprobarReserva($db, $data) {
    $id_reserva = $data['id_reserva'];
    
    $query = "UPDATE reserva_espacio 
              SET estado = 'Aprobada'
              WHERE id_reserva = :id AND estado = 'Pendiente'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_reserva);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Reserva aprobada exitosamente']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo aprobar la reserva']);
    }
}

function rechazarReserva($db, $data) {
    $id_reserva = $data['id_reserva'];
    $motivo = $data['motivo_rechazo'] ?? 'Sin motivo especificado';
    
    $query = "UPDATE reserva_espacio 
              SET estado = 'Rechazada',
                  motivo_rechazo = :motivo
              WHERE id_reserva = :id AND estado = 'Pendiente'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_reserva);
    $stmt->bindParam(':motivo', $motivo);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Reserva rechazada']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo rechazar la reserva']);
    }
}

function completarReserva($db, $data) {
    $id_reserva = $data['id_reserva'];
    
    $query = "UPDATE reserva_espacio 
              SET estado = 'Completada'
              WHERE id_reserva = :id AND estado = 'Aprobada'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_reserva);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Reserva marcada como completada']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo completar la reserva']);
    }
}

function updateReserva($db, $id, $data) {
    $query = "UPDATE reserva_espacio SET ";
    $fields = [];
    $params = [':id' => $id];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id_reserva' && $key !== 'equipos') {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }
    
    $query .= implode(', ', $fields) . " WHERE id_reserva = :id";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Reserva actualizada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la reserva']);
    }
}

function deleteReserva($db, $id) {
    // Cancelar en lugar de eliminar
    $query = "UPDATE reserva_espacio SET estado = 'Cancelada' WHERE id_reserva = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reserva cancelada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al cancelar la reserva']);
    }
}
?>
