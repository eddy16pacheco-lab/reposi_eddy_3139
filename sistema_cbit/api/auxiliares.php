<?php
/**
 * API REST - Tablas Auxiliares
 * Sistema de Gestión CBIT
 * Maneja: espacios, equipos, categorías, marcas, actividades, ubicaciones físicas
 */

require_once '../config/database.php';
require_once '../config/cors.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Determinar qué tabla se está consultando
$uri_parts = explode('/', trim($request_uri, '/'));
$tabla = isset($uri_parts[count($uri_parts) - 2]) ? $uri_parts[count($uri_parts) - 2] : null;
$id = isset($uri_parts[count($uri_parts) - 1]) && is_numeric($uri_parts[count($uri_parts) - 1]) 
    ? intval($uri_parts[count($uri_parts) - 1]) 
    : null;

// Configuración de tablas
$tablas_config = array(
    'espacios' => array('tabla' => 'espacio', 'id' => 'id_espacio', 'campos' => array('nombre')),
    'categorias' => array('tabla' => 'categoria', 'id' => 'id_categoria', 'campos' => array('nombre')),
    'marcas' => array('tabla' => 'marca', 'id' => 'id_marca', 'campos' => array('nombre')),
    'actividades' => array('tabla' => 'actividad', 'id' => 'id_actividad', 'campos' => array('nombre')),
    'ubicaciones' => array('tabla' => 'ubicacion_fisica', 'id' => 'id_ubicacion_fisica', 'campos' => array('nombre')),
    'equipos' => array('tabla' => 'equipos', 'id' => 'id_equipos', 'campos' => array('modelo', 'id_categoria', 'id_marca'))
);

if(!isset($tablas_config[$tabla])) {
    http_response_code(404);
    echo json_encode(array("message" => "Recurso no encontrado"));
    exit();
}

$config = $tablas_config[$tabla];

switch($method) {
    case 'GET':
        if($id) {
            getItem($db, $config, $id);
        } else {
            getItems($db, $config);
        }
        break;
    
    case 'POST':
        createItem($db, $config);
        break;
    
    case 'PUT':
        updateItem($db, $config, $id);
        break;
    
    case 'DELETE':
        deleteItem($db, $config, $id);
        break;
    
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}

/**
 * Obtener todos los items
 */
function getItems($db, $config) {
    $tabla = $config['tabla'];
    
    if($tabla === 'equipos') {
        $query = "SELECT 
                    e.id_equipos,
                    e.modelo,
                    e.id_categoria,
                    e.id_marca,
                    c.nombre as categoria,
                    m.nombre as marca
                  FROM equipos e
                  LEFT JOIN categoria c ON e.id_categoria = c.id_categoria
                  LEFT JOIN marca m ON e.id_marca = m.id_marca
                  ORDER BY e.id_equipos DESC";
    } else {
        $query = "SELECT * FROM {$tabla} ORDER BY {$config['id']} DESC";
    }
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $items = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode($items);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener datos", "error" => $e->getMessage()));
    }
}

/**
 * Obtener un item específico
 */
function getItem($db, $config, $id) {
    $tabla = $config['tabla'];
    $id_campo = $config['id'];
    
    if($tabla === 'equipos') {
        $query = "SELECT 
                    e.id_equipos,
                    e.modelo,
                    e.id_categoria,
                    e.id_marca,
                    c.nombre as categoria,
                    m.nombre as marca
                  FROM equipos e
                  LEFT JOIN categoria c ON e.id_categoria = c.id_categoria
                  LEFT JOIN marca m ON e.id_marca = m.id_marca
                  WHERE e.id_equipos = :id";
    } else {
        $query = "SELECT * FROM {$tabla} WHERE {$id_campo} = :id";
    }
    
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
 * Crear nuevo item
 */
function createItem($db, $config) {
    $data = json_decode(file_get_contents("php://input"));
    $tabla = $config['tabla'];
    $campos = $config['campos'];
    
    // Validar que todos los campos requeridos estén presentes
    $valores = array();
    foreach($campos as $campo) {
        if(!isset($data->$campo) && $data->$campo !== null) {
            http_response_code(400);
            echo json_encode(array("message" => "Campo {$campo} requerido"));
            return;
        }
        $valores[$campo] = $data->$campo;
    }
    
    $campos_str = implode(", ", $campos);
    $placeholders = ":" . implode(", :", $campos);
    
    $query = "INSERT INTO {$tabla} ({$campos_str}) VALUES ({$placeholders})";
    
    try {
        $stmt = $db->prepare($query);
        foreach($valores as $campo => $valor) {
            $stmt->bindValue(":{$campo}", $valor);
        }
        $stmt->execute();
        
        http_response_code(201);
        echo json_encode(array("message" => "Item creado exitosamente", "id" => $db->lastInsertId()));
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al crear item", "error" => $e->getMessage()));
    }
}

/**
 * Actualizar item
 */
function updateItem($db, $config, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    $tabla = $config['tabla'];
    $id_campo = $config['id'];
    $campos = $config['campos'];
    
    $updates = array();
    $valores = array();
    
    foreach($campos as $campo) {
        if(isset($data->$campo)) {
            $updates[] = "{$campo} = :{$campo}";
            $valores[$campo] = $data->$campo;
        }
    }
    
    if(count($updates) === 0) {
        http_response_code(400);
        echo json_encode(array("message" => "No hay datos para actualizar"));
        return;
    }
    
    $query = "UPDATE {$tabla} SET " . implode(", ", $updates) . " WHERE {$id_campo} = :id";
    
    try {
        $stmt = $db->prepare($query);
        foreach($valores as $campo => $valor) {
            $stmt->bindValue(":{$campo}", $valor);
        }
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        http_response_code(200);
        echo json_encode(array("message" => "Item actualizado exitosamente"));
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al actualizar item", "error" => $e->getMessage()));
    }
}

/**
 * Eliminar item
 */
function deleteItem($db, $config, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $tabla = $config['tabla'];
    $id_campo = $config['id'];
    
    $query = "DELETE FROM {$tabla} WHERE {$id_campo} = :id";
    
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
