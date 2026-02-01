<?php
/**
 * API REST - Solicitudes
 * Sistema de Gestión CBIT
 */

require_once '../config/database.php';
require_once '../config/cors.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

$uri_parts = explode('/', trim($request_uri, '/'));
$id = isset($uri_parts[count($uri_parts) - 1]) && is_numeric($uri_parts[count($uri_parts) - 1]) 
    ? intval($uri_parts[count($uri_parts) - 1]) 
    : null;

switch($method) {
    case 'GET':
        if($id) {
            getSolicitud($db, $id);
        } else {
            getSolicitudes($db);
        }
        break;
    
    case 'POST':
        createSolicitud($db);
        break;
    
    case 'PUT':
        updateSolicitud($db, $id);
        break;
    
    case 'DELETE':
        deleteSolicitud($db, $id);
        break;
    
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}

/**
 * Obtener todas las solicitudes
 */
function getSolicitudes($db) {
    $query = "SELECT 
                s.id_solicitud,
                s.fecha,
                s.estado,
                u.nombre_usuario,
                p.nombre as nombre_persona,
                p.apellido,
                e.nombre as espacio,
                a.nombre as actividad
              FROM solicitudes s
              LEFT JOIN usuario u ON s.id_usuario = u.id_usuario
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              LEFT JOIN espacio e ON s.id_espacio = e.id_espacio
              LEFT JOIN actividad a ON s.id_actividad = a.id_actividad
              ORDER BY s.fecha DESC";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $solicitudes = $stmt->fetchAll();
        
        // Obtener horarios para cada solicitud
        foreach($solicitudes as &$solicitud) {
            $query_horarios = "SELECT dia_semana, hora_inicio, hora_final 
                              FROM horario 
                              WHERE id_solicitud = :id_solicitud";
            $stmt_horarios = $db->prepare($query_horarios);
            $stmt_horarios->bindParam(':id_solicitud', $solicitud['id_solicitud']);
            $stmt_horarios->execute();
            $solicitud['horarios'] = $stmt_horarios->fetchAll();
        }
        
        http_response_code(200);
        echo json_encode($solicitudes);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener solicitudes", "error" => $e->getMessage()));
    }
}

/**
 * Obtener una solicitud específica
 */
function getSolicitud($db, $id) {
    $query = "SELECT 
                s.id_solicitud,
                s.id_usuario,
                s.id_espacio,
                s.id_actividad,
                s.fecha,
                s.estado,
                u.nombre_usuario,
                p.nombre as nombre_persona,
                p.apellido,
                e.nombre as espacio,
                a.nombre as actividad
              FROM solicitudes s
              LEFT JOIN usuario u ON s.id_usuario = u.id_usuario
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              LEFT JOIN espacio e ON s.id_espacio = e.id_espacio
              LEFT JOIN actividad a ON s.id_actividad = a.id_actividad
              WHERE s.id_solicitud = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $solicitud = $stmt->fetch();
        
        if($solicitud) {
            // Obtener horarios
            $query_horarios = "SELECT id_horario, dia_semana, hora_inicio, hora_final 
                              FROM horario 
                              WHERE id_solicitud = :id_solicitud";
            $stmt_horarios = $db->prepare($query_horarios);
            $stmt_horarios->bindParam(':id_solicitud', $id);
            $stmt_horarios->execute();
            $solicitud['horarios'] = $stmt_horarios->fetchAll();
            
            // Obtener equipos solicitados
            $query_equipos = "SELECT 
                                ds.id_detalle_solicitud,
                                i.id_inventario,
                                i.serial,
                                eq.modelo,
                                c.nombre as categoria,
                                m.nombre as marca
                              FROM detalle_solicitudes ds
                              LEFT JOIN inventario i ON ds.id_inventario = i.id_inventario
                              LEFT JOIN equipos eq ON i.id_equipos = eq.id_equipos
                              LEFT JOIN categoria c ON eq.id_categoria = c.id_categoria
                              LEFT JOIN marca m ON eq.id_marca = m.id_marca
                              WHERE ds.id_solicitud = :id_solicitud";
            $stmt_equipos = $db->prepare($query_equipos);
            $stmt_equipos->bindParam(':id_solicitud', $id);
            $stmt_equipos->execute();
            $solicitud['equipos'] = $stmt_equipos->fetchAll();
            
            http_response_code(200);
            echo json_encode($solicitud);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Solicitud no encontrada"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener solicitud", "error" => $e->getMessage()));
    }
}

/**
 * Crear nueva solicitud
 */
function createSolicitud($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->id_usuario) && !empty($data->fecha) && !empty($data->estado)) {
        try {
            $db->beginTransaction();
            
            // Insertar solicitud
            $query = "INSERT INTO solicitudes (id_usuario, id_espacio, id_actividad, fecha, estado) 
                     VALUES (:id_usuario, :id_espacio, :id_actividad, :fecha, :estado)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_usuario', $data->id_usuario);
            $stmt->bindParam(':id_espacio', $data->id_espacio);
            $stmt->bindParam(':id_actividad', $data->id_actividad);
            $stmt->bindParam(':fecha', $data->fecha);
            $stmt->bindParam(':estado', $data->estado);
            $stmt->execute();
            
            $id_solicitud = $db->lastInsertId();
            
            // Insertar horarios si existen
            if(!empty($data->horarios) && is_array($data->horarios)) {
                $query_horario = "INSERT INTO horario (id_solicitud, dia_semana, hora_inicio, hora_final) 
                                 VALUES (:id_solicitud, :dia_semana, :hora_inicio, :hora_final)";
                $stmt_horario = $db->prepare($query_horario);
                
                foreach($data->horarios as $horario) {
                    $stmt_horario->bindParam(':id_solicitud', $id_solicitud);
                    $stmt_horario->bindParam(':dia_semana', $horario->dia_semana);
                    $stmt_horario->bindParam(':hora_inicio', $horario->hora_inicio);
                    $stmt_horario->bindParam(':hora_final', $horario->hora_final);
                    $stmt_horario->execute();
                }
            }
            
            // Insertar equipos solicitados si existen
            if(!empty($data->equipos) && is_array($data->equipos)) {
                $query_equipo = "INSERT INTO detalle_solicitudes (id_solicitud, id_inventario) 
                                VALUES (:id_solicitud, :id_inventario)";
                $stmt_equipo = $db->prepare($query_equipo);
                
                foreach($data->equipos as $id_inventario) {
                    $stmt_equipo->bindParam(':id_solicitud', $id_solicitud);
                    $stmt_equipo->bindParam(':id_inventario', $id_inventario);
                    $stmt_equipo->execute();
                }
            }
            
            $db->commit();
            
            http_response_code(201);
            echo json_encode(array("message" => "Solicitud creada exitosamente", "id" => $id_solicitud));
        } catch(PDOException $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Error al crear solicitud", "error" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Datos incompletos"));
    }
}

/**
 * Actualizar solicitud
 */
function updateSolicitud($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        $db->beginTransaction();
        
        // Actualizar solicitud
        $query = "UPDATE solicitudes SET ";
        $updates = array();
        
        if(isset($data->id_usuario)) $updates[] = "id_usuario = :id_usuario";
        if(isset($data->id_espacio)) $updates[] = "id_espacio = :id_espacio";
        if(isset($data->id_actividad)) $updates[] = "id_actividad = :id_actividad";
        if(!empty($data->fecha)) $updates[] = "fecha = :fecha";
        if(!empty($data->estado)) $updates[] = "estado = :estado";
        
        if(count($updates) > 0) {
            $query .= implode(", ", $updates) . " WHERE id_solicitud = :id";
            
            $stmt = $db->prepare($query);
            if(isset($data->id_usuario)) $stmt->bindParam(':id_usuario', $data->id_usuario);
            if(isset($data->id_espacio)) $stmt->bindParam(':id_espacio', $data->id_espacio);
            if(isset($data->id_actividad)) $stmt->bindParam(':id_actividad', $data->id_actividad);
            if(!empty($data->fecha)) $stmt->bindParam(':fecha', $data->fecha);
            if(!empty($data->estado)) $stmt->bindParam(':estado', $data->estado);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        }
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode(array("message" => "Solicitud actualizada exitosamente"));
    } catch(PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("message" => "Error al actualizar solicitud", "error" => $e->getMessage()));
    }
}

/**
 * Eliminar solicitud
 */
function deleteSolicitud($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $query = "DELETE FROM solicitudes WHERE id_solicitud = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(array("message" => "Solicitud eliminada exitosamente"));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Solicitud no encontrada"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al eliminar solicitud", "error" => $e->getMessage()));
    }
}
?>
