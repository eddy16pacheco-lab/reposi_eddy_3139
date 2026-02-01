<?php
/**
 * API REST - Inventario
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
            getInventarioItem($db, $id);
        } else {
            getInventario($db);
        }
        break;
    
    case 'POST':
        createInventarioItem($db);
        break;
    
    case 'PUT':
        updateInventarioItem($db, $id);
        break;
    
    case 'DELETE':
        deleteInventarioItem($db, $id);
        break;
    
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}

/**
 * Obtener todo el inventario
 */
function getInventario($db) {
    $query = "SELECT 
                i.id_inventario,
                i.serial,
                i.estado,
                eq.modelo,
                c.nombre as categoria,
                m.nombre as marca,
                uf.nombre as ubicacion
              FROM inventario i
              LEFT JOIN equipos eq ON i.id_equipos = eq.id_equipos
              LEFT JOIN categoria c ON eq.id_categoria = c.id_categoria
              LEFT JOIN marca m ON eq.id_marca = m.id_marca
              LEFT JOIN ubicacion_fisica uf ON i.id_ubicacion_fisica = uf.id_ubicacion_fisica
              ORDER BY i.id_inventario DESC";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $inventario = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode($inventario);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener inventario", "error" => $e->getMessage()));
    }
}

/**
 * Obtener un item del inventario
 */
function getInventarioItem($db, $id) {
    $query = "SELECT 
                i.id_inventario,
                i.id_equipos,
                i.id_ubicacion_fisica,
                i.serial,
                i.estado,
                eq.modelo,
                eq.id_categoria,
                eq.id_marca,
                c.nombre as categoria,
                m.nombre as marca,
                uf.nombre as ubicacion
              FROM inventario i
              LEFT JOIN equipos eq ON i.id_equipos = eq.id_equipos
              LEFT JOIN categoria c ON eq.id_categoria = c.id_categoria
              LEFT JOIN marca m ON eq.id_marca = m.id_marca
              LEFT JOIN ubicacion_fisica uf ON i.id_ubicacion_fisica = uf.id_ubicacion_fisica
              WHERE i.id_inventario = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $item = $stmt->fetch();
        
        if($item) {
            http_response_code(200);
            echo json_encode($item);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Item no encontrado"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener item", "error" => $e->getMessage()));
    }
}

/**
 * Crear nuevo item en inventario
 */
function createInventarioItem($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->id_equipos) && !empty($data->serial) && !empty($data->estado)) {
        $query = "INSERT INTO inventario (id_equipos, id_ubicacion_fisica, serial, estado) 
                 VALUES (:id_equipos, :id_ubicacion_fisica, :serial, :estado)";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_equipos', $data->id_equipos);
            $stmt->bindParam(':id_ubicacion_fisica', $data->id_ubicacion_fisica);
            $stmt->bindParam(':serial', $data->serial);
            $stmt->bindParam(':estado', $data->estado);
            $stmt->execute();
            
            http_response_code(201);
            echo json_encode(array("message" => "Item creado exitosamente", "id" => $db->lastInsertId()));
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error al crear item", "error" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Datos incompletos"));
    }
}

/**
 * Actualizar item del inventario
 */
function updateInventarioItem($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    $query = "UPDATE inventario SET ";
    $updates = array();
    
    if(isset($data->id_equipos)) $updates[] = "id_equipos = :id_equipos";
    if(isset($data->id_ubicacion_fisica)) $updates[] = "id_ubicacion_fisica = :id_ubicacion_fisica";
    if(!empty($data->serial)) $updates[] = "serial = :serial";
    if(!empty($data->estado)) $updates[] = "estado = :estado";
    
    if(count($updates) > 0) {
        $query .= implode(", ", $updates) . " WHERE id_inventario = :id";
        
        try {
            $stmt = $db->prepare($query);
            if(isset($data->id_equipos)) $stmt->bindParam(':id_equipos', $data->id_equipos);
            if(isset($data->id_ubicacion_fisica)) $stmt->bindParam(':id_ubicacion_fisica', $data->id_ubicacion_fisica);
            if(!empty($data->serial)) $stmt->bindParam(':serial', $data->serial);
            if(!empty($data->estado)) $stmt->bindParam(':estado', $data->estado);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode(array("message" => "Item actualizado exitosamente"));
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Error al actualizar item", "error" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "No hay datos para actualizar"));
    }
}

/**
 * Eliminar item del inventario
 */
function deleteInventarioItem($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $query = "DELETE FROM inventario WHERE id_inventario = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(array("message" => "Item eliminado exitosamente"));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Item no encontrado"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al eliminar item", "error" => $e->getMessage()));
    }
}
?>
