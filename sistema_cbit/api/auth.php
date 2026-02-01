<?php
/**
 * API REST - Autenticación
 * Sistema de Gestión CBIT
 */

require_once '../config/database.php';
require_once '../config/cors.php';

// Iniciar sesión
session_start();

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request = json_decode(file_get_contents("php://input"));

switch($method) {
    case 'POST':
        // Determinar acción
        if(isset($request->action)) {
            if($request->action === 'login') {
                login($db, $request);
            } elseif($request->action === 'logout') {
                logout();
            } elseif($request->action === 'check') {
                checkSession();
            }
        } else {
            login($db, $request);
        }
        break;
    
    case 'GET':
        checkSession();
        break;
    
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido"));
        break;
}

/**
 * Login de usuario
 */
function login($db, $request) {
    if(empty($request->nombre_usuario) || empty($request->contrasena)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Usuario y contraseña requeridos"));
        return;
    }
    
    $query = "SELECT 
                u.id_usuario,
                u.nombre_usuario,
                u.correo,
                u.contrasena,
                u.estado,
                u.rol,
                p.nombre,
                p.apellido
              FROM usuario u
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              WHERE u.nombre_usuario = :nombre_usuario";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre_usuario', $request->nombre_usuario);
        $stmt->execute();
        $usuario = $stmt->fetch();
        
        if(!$usuario) {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Usuario no encontrado"));
            return;
        }
        
        // Verificar estado del usuario
        if($usuario['estado'] !== 'Activo') {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Usuario inactivo o bloqueado"));
            return;
        }
        
        // Verificar contraseña
        if(!password_verify($request->contrasena, $usuario['contrasena'])) {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Contraseña incorrecta"));
            return;
        }
        
        // Crear sesión
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['username'] = $usuario['nombre_usuario'];
        $_SESSION['email'] = $usuario['correo'];
        $_SESSION['role'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['apellido'] = $usuario['apellido'];
        $_SESSION['logged_in'] = true;
        
        // Respuesta exitosa
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Login exitoso",
            "user" => array(
                "id" => $usuario['id_usuario'],
                "username" => $usuario['nombre_usuario'],
                "email" => $usuario['correo'],
                "role" => $usuario['rol'],
                "nombre" => $usuario['nombre'],
                "apellido" => $usuario['apellido']
            )
        ));
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error en el servidor", "error" => $e->getMessage()));
    }
}

/**
 * Logout de usuario
 */
function logout() {
    session_unset();
    session_destroy();
    
    http_response_code(200);
    echo json_encode(array("success" => true, "message" => "Logout exitoso"));
}

/**
 * Verificar sesión activa
 */
function checkSession() {
    if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "logged_in" => true,
            "user" => array(
                "id" => $_SESSION['user_id'],
                "username" => $_SESSION['username'],
                "email" => $_SESSION['email'],
                "role" => $_SESSION['role'],
                "nombre" => $_SESSION['nombre'],
                "apellido" => $_SESSION['apellido']
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("success" => false, "logged_in" => false, "message" => "No hay sesión activa"));
    }
}
?>
