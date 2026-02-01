<?php
/**
 * API REST - Usuarios
 * Sistema de Gestión CBIT
 */

require_once '../config/database.php';
require_once '../config/cors.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Obtener ID si existe en la URL
$uri_parts = explode('/', trim($request_uri, '/'));
$id = isset($uri_parts[count($uri_parts) - 1]) && is_numeric($uri_parts[count($uri_parts) - 1]) 
    ? intval($uri_parts[count($uri_parts) - 1]) 
    : null;

switch($method) {
    case 'GET':
        if($id) {
            getUsuario($db, $id);
        } else {
            getUsuarios($db);
        }
        break;
    
    case 'POST':
        createUsuario($db);
        break;
    
    case 'PUT':
        updateUsuario($db, $id);
        break;
    
    case 'DELETE':
        deleteUsuario($db, $id);
        break;
    
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}

/**
 * Obtener todos los usuarios con información de persona
 */
function getUsuarios($db) {
    $query = "SELECT 
                u.id_usuario,
                u.nombre_usuario,
                u.correo,
                u.estado,
                u.roles,
                p.nombre,
                p.apellido,
                p.cedula,
                p.telefono
              FROM usuario u
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              ORDER BY u.id_usuario DESC";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $usuarios = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode($usuarios);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener usuarios", "error" => $e->getMessage()));
    }
}

/**
 * Obtener un usuario específico
 */
function getUsuario($db, $id) {
    $query = "SELECT 
                u.id_usuario,
                u.id_persona,
                u.nombre_usuario,
                u.correo,
                u.estado,
                u.roles,
                p.nombre,
                p.apellido,
                p.cedula,
                p.telefono
              FROM usuario u
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              WHERE u.id_usuario = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $usuario = $stmt->fetch();
        
        if($usuario) {
            http_response_code(200);
            echo json_encode($usuario);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Usuario no encontrado"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener usuario", "error" => $e->getMessage()));
    }
}

/**
 * Crear nuevo usuario y persona
 */
function createUsuario($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->nombre) && !empty($data->apellido) && !empty($data->cedula) && 
       !empty($data->telefono) && !empty($data->nombre_usuario) && 
       !empty($data->contrasena_usuario) && !empty($data->correo) && !empty($data->roles)) {
        
        try {
            $db->beginTransaction();
            
            // Insertar persona
            $query_persona = "INSERT INTO persona (nombre, apellido, cedula, telefono) 
                             VALUES (:nombre, :apellido, :cedula, :telefono)";
            $stmt = $db->prepare($query_persona);
            $stmt->bindParam(':nombre', $data->nombre);
            $stmt->bindParam(':apellido', $data->apellido);
            $stmt->bindParam(':cedula', $data->cedula);
            $stmt->bindParam(':telefono', $data->telefono);
            $stmt->execute();
            
            $id_persona = $db->lastInsertId();
            
            // Insertar usuario
            $contrasena_hash = password_hash($data->contrasena_usuario, PASSWORD_DEFAULT);
            $estado = isset($data->estado) ? $data->estado : 'Activo';
            
            $query_usuario = "INSERT INTO usuario (id_persona, nombre_usuario, contrasena_usuario, correo, estado, roles) 
                             VALUES (:id_persona, :nombre_usuario, :contrasena_usuario, :correo, :estado, :roles)";
            $stmt = $db->prepare($query_usuario);
            $stmt->bindParam(':id_persona', $id_persona);
            $stmt->bindParam(':nombre_usuario', $data->nombre_usuario);
            $stmt->bindParam(':contrasena_usuario', $contrasena_hash);
            $stmt->bindParam(':correo', $data->correo);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':roles', $data->roles);
            $stmt->execute();
            
            $db->commit();
            
            http_response_code(201);
            echo json_encode(array("message" => "Usuario creado exitosamente", "id" => $db->lastInsertId()));
        } catch(PDOException $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Error al crear usuario", "error" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Datos incompletos"));
    }
}

/**
 * Actualizar usuario
 */
function updateUsuario($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        $db->beginTransaction();
        
        // Actualizar persona si hay datos
        if(!empty($data->nombre) || !empty($data->apellido) || !empty($data->telefono)) {
            $query_persona = "UPDATE persona p
                             INNER JOIN usuario u ON p.id_persona = u.id_persona
                             SET ";
            $updates = array();
            
            if(!empty($data->nombre)) $updates[] = "p.nombre = :nombre";
            if(!empty($data->apellido)) $updates[] = "p.apellido = :apellido";
            if(!empty($data->telefono)) $updates[] = "p.telefono = :telefono";
            
            $query_persona .= implode(", ", $updates) . " WHERE u.id_usuario = :id";
            
            $stmt = $db->prepare($query_persona);
            if(!empty($data->nombre)) $stmt->bindParam(':nombre', $data->nombre);
            if(!empty($data->apellido)) $stmt->bindParam(':apellido', $data->apellido);
            if(!empty($data->telefono)) $stmt->bindParam(':telefono', $data->telefono);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        }
        
        // Actualizar usuario
        $query_usuario = "UPDATE usuario SET ";
        $updates = array();
        
        if(!empty($data->nombre_usuario)) $updates[] = "nombre_usuario = :nombre_usuario";
        if(!empty($data->correo)) $updates[] = "correo = :correo";
        if(!empty($data->estado)) $updates[] = "estado = :estado";
        if(!empty($data->roles)) $updates[] = "roles = :roles";
        if(!empty($data->contrasena_usuario)) $updates[] = "contrasena_usuario = :contrasena_usuario";
        
        if(count($updates) > 0) {
            $query_usuario .= implode(", ", $updates) . " WHERE id_usuario = :id";
            
            $stmt = $db->prepare($query_usuario);
            if(!empty($data->nombre_usuario)) $stmt->bindParam(':nombre_usuario', $data->nombre_usuario);
            if(!empty($data->correo)) $stmt->bindParam(':correo', $data->correo);
            if(!empty($data->estado)) $stmt->bindParam(':estado', $data->estado);
            if(!empty($data->roles)) $stmt->bindParam(':roles', $data->roles);
            if(!empty($data->contrasena_usuario)) {
                $contrasena_hash = password_hash($data->contrasena_usuario, PASSWORD_DEFAULT);
                $stmt->bindParam(':contrasena_usuario', $contrasena_hash);
            }
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        }
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode(array("message" => "Usuario actualizado exitosamente"));
    } catch(PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("message" => "Error al actualizar usuario", "error" => $e->getMessage()));
    }
}

/**
 * Eliminar usuario
 */
function deleteUsuario($db, $id) {
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID requerido"));
        return;
    }
    
    $query = "DELETE FROM usuario WHERE id_usuario = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(array("message" => "Usuario eliminado exitosamente"));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Usuario no encontrado"));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al eliminar usuario", "error" => $e->getMessage()));
    }
}
?>
