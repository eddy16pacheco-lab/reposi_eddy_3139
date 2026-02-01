<?php
/**
 * API REST - Mantenimiento
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
            getMantenimiento($db, $id);
        } else {
            getMantenimientos($db);
        }
        break;
    
    case 'POST':
        createMantenimiento($db);
        break;
    
    case 'PUT':
        updateMantenimiento($db, $id);
        break;
    
    case 'DELETE':
        deleteMantenimiento($db, $id);
        break;
    
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}

/**
 * Obtener todos los mantenimientos
 */
function getMantenimientos($db) {
    $query = "SELECT 
                m.id_mantenimiento,
                m.fecha_reporte,
                m.fecha_resolucion,
                m.tipo,
                m.descripicon_falla,
                i.serial,
                eq.modelo,
                c.nombre as categoria,
                u.nombre_usuario,
                p.nombre as nombre_persona,
                p.apellido
              FROM mantenimiento m
              LEFT JOIN inventario i ON m.id_inventario = i.id_inventario
              LEFT JOIN equipos eq ON i.id_equipos = eq.id_equipos
              LEFT JOIN categoria c ON eq.id_categoria = c.id_categoria
              LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              ORDER BY m.fecha_reporte DESC";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $mantenimientos = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode($mantenimientos);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener mantenimientos", "error" => $e->getMessage()));
    }
}

/**
 * Obtener un mantenimiento específico
 */
function getMantenimiento($db, $id) {
    $query = "SELECT 
                m.id_mantenimiento,
                m.id_inventario,
                m.id_usuario,
                m.fecha_reporte,
                m.fecha_resolucion,
                m.tipo,
                m.descripicon_falla,
                i.serial,
                eq.modelo,
                c.nombre as categoria,
                u.nombre_usuario,
                p.nombre as nombre_persona,
                p.apellido
              FROM mantenimiento m
              LEFT JOIN inventario i ON m.id_inventario = i.id_inventario
              LEFT JOIN equipos eq ON i.id_equipos = eq.id_equipos
              LEFT JOIN categoria c ON eq.id_categoria = c.id_categoria
              LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              WHERE m.id_mantenimiento = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $mantenimiento = $stmt->fetch();
        
        if($mantenimiento) {
            http_response_code(200);
            echo json_encode($mantenimiento);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Mantenimiento no encontrado"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener mantenimiento", "error" => $e->getMessage()));
    }
}

/**
 * Crear nuevo mantenimiento
 */
function createMantenimiento($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->id_inventario) && !empty($data->id_usuario) && 
       !empty($data->fecha_reporte) && !empty($data->tipo)) {
        
        $query = "INSERT INTO mantenimiento (id_inventario, id_usuario, fecha_reporte, fecha_resolucion, tipo, descripicon_falla) 
                 VALUES (:id_inventario, :id_usuario, :fecha_reporte, :fecha_resolucion, :tipo, :descripicon_falla)";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_inventario', $data->id_inventario);
            $stmt->bindParam(':id_usuario', $data->id_usuario);
            $stmt->bindParam(':fecha_reporte', $data->fecha_reporte);
            $fecha_resolucion = isset($data->fecha_resolucion) ? $data->fecha_resolucion : null;
            $stmt->bindParam(':fecha_resolucion', $fecha_resolucion);
            $stmt->bindParam(':tipo', $data->tipo);
            $descripcion = isset($data->descripicon_falla) ? $data->descripicon_falla : null;
            $stmt->bindParam(':descripicon_falla', $descripcion);
            $stmt->execute();
            
            http_response_code(201);
            echo json_encode(array("message" => "Mantenimiento creado exitosamente", "id" => $db->lastInsertId()));
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error al crear mantenimiento", "error" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Datos incompletos"));
    }
}

/**
 * Actualizar mantenimiento
 */
function updateMantenimiento($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    $query = "UPDATE mantenimiento SET ";
    $updates = array();
    
    if(isset($data->id_inventario)) $updates[] = "id_inventario = :id_inventario";
    if(isset($data->id_usuario)) $updates[] = "id_usuario = :id_usuario";
    if(!empty($data->fecha_reporte)) $updates[] = "fecha_reporte = :fecha_reporte";
    if(isset($data->fecha_resolucion)) $updates[] = "fecha_resolucion = :fecha_resolucion";
    if(!empty($data->tipo)) $updates[] = "tipo = :tipo";
    if(isset($data->descripicon_falla)) $updates[] = "descripicon_falla = :descripicon_falla";
    
    if(count($updates) > 0) {
        $query .= implode(", ", $updates) . " WHERE id_mantenimiento = :id";
        
        try {
            $stmt = $db->prepare($query);
            if(isset($data->id_inventario)) $stmt->bindParam(':id_inventario', $data->id_inventario);
            if(isset($data->id_usuario)) $stmt->bindParam(':id_usuario', $data->id_usuario);
            if(!empty($data->fecha_reporte)) $stmt->bindParam(':fecha_reporte', $data->fecha_reporte);
            if(isset($data->fecha_resolucion)) $stmt->bindParam(':fecha_resolucion', $data->fecha_resolucion);
            if(!empty($data->tipo)) $stmt->bindParam(':tipo', $data->tipo);
            if(isset($data->descripicon_falla)) $stmt->bindParam(':descripicon_falla', $data->descripicon_falla);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode(array("message" => "Mantenimiento actualizado exitosamente"));
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error al actualizar mantenimiento", "error" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No hay datos para actualizar"));
    }
}

/**
 * Eliminar mantenimiento
 */
function deleteMantenimiento($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $query = "DELETE FROM mantenimiento WHERE id_mantenimiento = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(array("message" => "Mantenimiento eliminado exitosamente"));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Mantenimiento no encontrado"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al eliminar mantenimiento", "error" => $e->getMessage()));
    }
}
?>
