<?php
/**
 * API de Tickets de Mantenimiento
 * Sistema completo de gestión de tickets con asignación, seguimiento y tiempos
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
                getTicket($db, $id);
            } else {
                if (isset($_GET['pendientes'])) {
                    getTicketsPendientes($db);
                } elseif (isset($_GET['tecnico'])) {
                    getTicketsPorTecnico($db, $_GET['tecnico']);
                } elseif (isset($_GET['equipo'])) {
                    getTicketsPorEquipo($db, $_GET['equipo']);
                } elseif (isset($_GET['estadisticas'])) {
                    getEstadisticasTickets($db);
                } else {
                    getAllTickets($db);
                }
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['accion'])) {
                switch($data['accion']) {
                    case 'asignar':
                        asignarTicket($db, $data);
                        break;
                    case 'cambiar_estado':
                        cambiarEstadoTicket($db, $data);
                        break;
                    case 'resolver':
                        resolverTicket($db, $data);
                        break;
                    case 'cerrar':
                        cerrarTicket($db, $data);
                        break;
                    case 'reabrir':
                        reabrirTicket($db, $data);
                        break;
                    default:
                        crearTicket($db, $data);
                }
            } else {
                crearTicket($db, $data);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents("php://input"), true);
            updateTicket($db, $id, $data);
            break;
            
        case 'DELETE':
            deleteTicket($db, $id);
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

function getAllTickets($db) {
    $query = "SELECT 
                t.*,
                m.tipo AS tipo_mantenimiento,
                m.descripcion_falla,
                e.modelo AS equipo,
                i.serial,
                CONCAT(per.nombre, ' ', per.apellido) AS reportado_por,
                CONCAT(tec.nombre, ' ', tec.apellido) AS tecnico_asignado,
                TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_abierto
              FROM ticket_mantenimiento t
              JOIN mantenimiento m ON t.id_mantenimiento = m.id_mantenimiento
              JOIN inventario i ON m.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              JOIN usuario u ON m.id_usuario = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              LEFT JOIN usuario utec ON t.id_tecnico_asignado = utec.id_usuario
              LEFT JOIN persona tec ON utec.id_persona = tec.id_persona
              ORDER BY 
                CASE t.prioridad
                    WHEN 'Crítica' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Media' THEN 3
                    WHEN 'Baja' THEN 4
                END,
                t.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $tickets]);
}

function getTicket($db, $id) {
    $query = "SELECT 
                t.*,
                m.*,
                e.modelo AS equipo,
                i.serial,
                i.estado AS estado_equipo,
                uf.nombre AS ubicacion,
                CONCAT(per.nombre, ' ', per.apellido) AS reportado_por,
                u.correo AS correo_reportante,
                CONCAT(tec.nombre, ' ', tec.apellido) AS tecnico_asignado,
                utec.correo AS correo_tecnico
              FROM ticket_mantenimiento t
              JOIN mantenimiento m ON t.id_mantenimiento = m.id_mantenimiento
              JOIN inventario i ON m.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              JOIN ubicacion_fisica uf ON i.id_ubicacion = uf.id_ubicacion
              JOIN usuario u ON m.id_usuario = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              LEFT JOIN usuario utec ON t.id_tecnico_asignado = utec.id_usuario
              LEFT JOIN persona tec ON utec.id_persona = tec.id_persona
              WHERE t.id_ticket = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ticket) {
        // Obtener historial del ticket
        $query_hist = "SELECT 
                          h.*,
                          CONCAT(p.nombre, ' ', p.apellido) AS usuario
                       FROM historial_ticket h
                       JOIN usuario u ON h.id_usuario = u.id_usuario
                       JOIN persona p ON u.id_persona = p.id_persona
                       WHERE h.id_ticket = :id
                       ORDER BY h.created_at DESC";
        $stmt_hist = $db->prepare($query_hist);
        $stmt_hist->bindParam(':id', $id);
        $stmt_hist->execute();
        $ticket['historial'] = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $ticket]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
    }
}

function getTicketsPendientes($db) {
    $query = "SELECT * FROM vista_tickets_pendientes ORDER BY prioridad, horas_abierto DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $tickets]);
}

function getTicketsPorTecnico($db, $id_tecnico) {
    $query = "SELECT 
                t.*,
                m.tipo AS tipo_mantenimiento,
                e.modelo AS equipo,
                i.serial,
                TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_abierto
              FROM ticket_mantenimiento t
              JOIN mantenimiento m ON t.id_mantenimiento = m.id_mantenimiento
              JOIN inventario i ON m.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              WHERE t.id_tecnico_asignado = :id_tecnico
              AND t.estado_ticket NOT IN ('Cerrado')
              ORDER BY t.prioridad, t.created_at";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_tecnico', $id_tecnico);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $tickets]);
}

function getTicketsPorEquipo($db, $id_inventario) {
    $query = "SELECT 
                t.*,
                m.tipo AS tipo_mantenimiento,
                m.descripcion_falla,
                CONCAT(per.nombre, ' ', per.apellido) AS reportado_por,
                CONCAT(tec.nombre, ' ', tec.apellido) AS tecnico_asignado
              FROM ticket_mantenimiento t
              JOIN mantenimiento m ON t.id_mantenimiento = m.id_mantenimiento
              JOIN usuario u ON m.id_usuario = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              LEFT JOIN usuario utec ON t.id_tecnico_asignado = utec.id_usuario
              LEFT JOIN persona tec ON utec.id_persona = tec.id_persona
              WHERE m.id_inventario = :id_inventario
              ORDER BY t.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_inventario', $id_inventario);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $tickets]);
}

function getEstadisticasTickets($db) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado_ticket = 'Abierto' THEN 1 ELSE 0 END) as abiertos,
                SUM(CASE WHEN estado_ticket = 'En Proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado_ticket = 'Resuelto' THEN 1 ELSE 0 END) as resueltos,
                SUM(CASE WHEN estado_ticket = 'Cerrado' THEN 1 ELSE 0 END) as cerrados,
                AVG(tiempo_respuesta) as tiempo_respuesta_promedio,
                AVG(tiempo_resolucion) as tiempo_resolucion_promedio
              FROM ticket_mantenimiento";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $stats]);
}

function crearTicket($db, $data) {
    // Primero crear el mantenimiento si no existe
    if (!isset($data['id_mantenimiento'])) {
        $query_mant = "INSERT INTO mantenimiento 
                       (id_inventario, id_usuario, tipo, descripcion_falla, fecha_reporte) 
                       VALUES 
                       (:id_inventario, :id_usuario, :tipo, :descripcion, NOW())";
        $stmt_mant = $db->prepare($query_mant);
        $stmt_mant->bindParam(':id_inventario', $data['id_inventario']);
        $stmt_mant->bindParam(':id_usuario', $data['id_usuario']);
        $stmt_mant->bindParam(':tipo', $data['tipo']);
        $stmt_mant->bindParam(':descripcion', $data['descripcion_falla']);
        $stmt_mant->execute();
        $id_mantenimiento = $db->lastInsertId();
    } else {
        $id_mantenimiento = $data['id_mantenimiento'];
    }
    
    // Generar número de ticket
    $numero_ticket = 'TKT-' . date('Ymd') . '-' . str_pad($id_mantenimiento, 6, '0', STR_PAD_LEFT);
    
    $query = "INSERT INTO ticket_mantenimiento 
              (numero_ticket, id_mantenimiento, prioridad, estado_ticket) 
              VALUES 
              (:numero_ticket, :id_mantenimiento, :prioridad, 'Abierto')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_ticket', $numero_ticket);
    $stmt->bindParam(':id_mantenimiento', $id_mantenimiento);
    $prioridad = $data['prioridad'] ?? 'Media';
    $stmt->bindParam(':prioridad', $prioridad);
    
    if ($stmt->execute()) {
        $id_ticket = $db->lastInsertId();
        
        // Registrar en historial
        registrarHistorial($db, $id_ticket, $data['id_usuario'], 'Creación', 'Ticket creado');
        
        // Cambiar estado del equipo a Mantenimiento
        $query_eq = "UPDATE inventario SET estado = 'Mantenimiento' WHERE id_inventario = :id";
        $stmt_eq = $db->prepare($query_eq);
        $stmt_eq->bindParam(':id', $data['id_inventario']);
        $stmt_eq->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Ticket creado exitosamente',
            'id_ticket' => $id_ticket,
            'numero_ticket' => $numero_ticket
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear el ticket']);
    }
}

function asignarTicket($db, $data) {
    $id_ticket = $data['id_ticket'];
    $id_tecnico = $data['id_tecnico'];
    $id_usuario_asigna = $data['id_usuario_asigna'];
    
    // Calcular tiempo de respuesta
    $query_tiempo = "SELECT TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutos 
                     FROM ticket_mantenimiento WHERE id_ticket = :id";
    $stmt_tiempo = $db->prepare($query_tiempo);
    $stmt_tiempo->bindParam(':id', $id_ticket);
    $stmt_tiempo->execute();
    $tiempo = $stmt_tiempo->fetch(PDO::FETCH_ASSOC);
    
    $query = "UPDATE ticket_mantenimiento 
              SET id_tecnico_asignado = :id_tecnico,
                  estado_ticket = 'En Proceso',
                  tiempo_respuesta = :tiempo_respuesta
              WHERE id_ticket = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_tecnico', $id_tecnico);
    $stmt->bindParam(':tiempo_respuesta', $tiempo['minutos']);
    $stmt->bindParam(':id', $id_ticket);
    
    if ($stmt->execute()) {
        registrarHistorial($db, $id_ticket, $id_usuario_asigna, 'Asignación', "Ticket asignado a técnico");
        echo json_encode(['success' => true, 'message' => 'Ticket asignado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al asignar el ticket']);
    }
}

function cambiarEstadoTicket($db, $data) {
    $id_ticket = $data['id_ticket'];
    $nuevo_estado = $data['estado'];
    $id_usuario = $data['id_usuario'];
    $notas = $data['notas'] ?? null;
    
    $query = "UPDATE ticket_mantenimiento 
              SET estado_ticket = :estado,
                  notas_tecnico = CONCAT(COALESCE(notas_tecnico, ''), '\n', :notas)
              WHERE id_ticket = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':estado', $nuevo_estado);
    $stmt->bindParam(':notas', $notas);
    $stmt->bindParam(':id', $id_ticket);
    
    if ($stmt->execute()) {
        registrarHistorial($db, $id_ticket, $id_usuario, 'Cambio de estado', "Estado cambiado a: $nuevo_estado");
        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado']);
    }
}

function resolverTicket($db, $data) {
    $id_ticket = $data['id_ticket'];
    $id_usuario = $data['id_usuario'];
    $solucion = $data['solucion'];
    
    // Calcular tiempo de resolución
    $query_tiempo = "SELECT TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutos 
                     FROM ticket_mantenimiento WHERE id_ticket = :id";
    $stmt_tiempo = $db->prepare($query_tiempo);
    $stmt_tiempo->bindParam(':id', $id_ticket);
    $stmt_tiempo->execute();
    $tiempo = $stmt_tiempo->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar ticket
    $query = "UPDATE ticket_mantenimiento 
              SET estado_ticket = 'Resuelto',
                  tiempo_resolucion = :tiempo_resolucion,
                  notas_tecnico = CONCAT(COALESCE(notas_tecnico, ''), '\n[SOLUCIÓN] ', :solucion)
              WHERE id_ticket = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':tiempo_resolucion', $tiempo['minutos']);
    $stmt->bindParam(':solucion', $solucion);
    $stmt->bindParam(':id', $id_ticket);
    
    if ($stmt->execute()) {
        // Actualizar mantenimiento
        $query_mant = "UPDATE mantenimiento m
                       JOIN ticket_mantenimiento t ON m.id_mantenimiento = t.id_mantenimiento
                       SET m.fecha_resolucion = NOW(),
                           m.descripcion_solucion = :solucion
                       WHERE t.id_ticket = :id";
        $stmt_mant = $db->prepare($query_mant);
        $stmt_mant->bindParam(':solucion', $solucion);
        $stmt_mant->bindParam(':id', $id_ticket);
        $stmt_mant->execute();
        
        registrarHistorial($db, $id_ticket, $id_usuario, 'Resolución', 'Ticket resuelto');
        echo json_encode(['success' => true, 'message' => 'Ticket resuelto exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al resolver el ticket']);
    }
}

function cerrarTicket($db, $data) {
    $id_ticket = $data['id_ticket'];
    $id_usuario = $data['id_usuario'];
    
    // Obtener id_inventario del ticket
    $query_inv = "SELECT m.id_inventario 
                  FROM ticket_mantenimiento t
                  JOIN mantenimiento m ON t.id_mantenimiento = m.id_mantenimiento
                  WHERE t.id_ticket = :id";
    $stmt_inv = $db->prepare($query_inv);
    $stmt_inv->bindParam(':id', $id_ticket);
    $stmt_inv->execute();
    $result = $stmt_inv->fetch(PDO::FETCH_ASSOC);
    
    $query = "UPDATE ticket_mantenimiento 
              SET estado_ticket = 'Cerrado'
              WHERE id_ticket = :id AND estado_ticket = 'Resuelto'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_ticket);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        // Cambiar estado del equipo a Operativo
        $query_eq = "UPDATE inventario SET estado = 'Operativo' WHERE id_inventario = :id";
        $stmt_eq = $db->prepare($query_eq);
        $stmt_eq->bindParam(':id', $result['id_inventario']);
        $stmt_eq->execute();
        
        registrarHistorial($db, $id_ticket, $id_usuario, 'Cierre', 'Ticket cerrado');
        echo json_encode(['success' => true, 'message' => 'Ticket cerrado exitosamente']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Solo se pueden cerrar tickets resueltos']);
    }
}

function reabrirTicket($db, $data) {
    $id_ticket = $data['id_ticket'];
    $id_usuario = $data['id_usuario'];
    $motivo = $data['motivo'];
    
    $query = "UPDATE ticket_mantenimiento 
              SET estado_ticket = 'Reabierto',
                  notas_tecnico = CONCAT(COALESCE(notas_tecnico, ''), '\n[REABIERTO] ', :motivo)
              WHERE id_ticket = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':motivo', $motivo);
    $stmt->bindParam(':id', $id_ticket);
    
    if ($stmt->execute()) {
        registrarHistorial($db, $id_ticket, $id_usuario, 'Reapertura', $motivo);
        echo json_encode(['success' => true, 'message' => 'Ticket reabierto']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al reabrir el ticket']);
    }
}

function updateTicket($db, $id, $data) {
    $query = "UPDATE ticket_mantenimiento SET ";
    $fields = [];
    $params = [':id' => $id];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id_ticket') {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }
    
    $query .= implode(', ', $fields) . " WHERE id_ticket = :id";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Ticket actualizado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el ticket']);
    }
}

function deleteTicket($db, $id) {
    // Solo se pueden eliminar tickets cerrados
    $query = "DELETE FROM ticket_mantenimiento WHERE id_ticket = :id AND estado_ticket = 'Cerrado'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Ticket eliminado exitosamente']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Solo se pueden eliminar tickets cerrados']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el ticket']);
    }
}

function registrarHistorial($db, $id_ticket, $id_usuario, $accion, $descripcion) {
    $query = "INSERT INTO historial_ticket (id_ticket, id_usuario, accion, descripcion) 
              VALUES (:id_ticket, :id_usuario, :accion, :descripcion)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_ticket', $id_ticket);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->bindParam(':accion', $accion);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->execute();
}
?>
