<?php
/**
 * API REST - Dashboard
 * Sistema de Gestión CBIT
 * Proporciona estadísticas y datos para el dashboard
 */

require_once '../config/database.php';
require_once '../config/cors.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if($method !== 'GET') {
    http_response_code(405);
    echo json_encode(array("message" => "Método no permitido"));
    exit();
}

try {
    $estadisticas = array();
    
    // Solicitudes de hoy
    $query = "SELECT COUNT(*) as total FROM solicitudes WHERE DATE(fecha) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $estadisticas['solicitudes_hoy'] = $result['total'];
    
    // Solicitudes pendientes
    $query = "SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'Pendiente'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $estadisticas['solicitudes_pendientes'] = $result['total'];
    
    // Usuarios activos
    $query = "SELECT COUNT(*) as total FROM usuario WHERE estado = 'Activo'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $estadisticas['usuarios_activos'] = $result['total'];
    
    // Equipos operativos
    $query = "SELECT COUNT(*) as total FROM inventario WHERE estado = 'Operativo'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $estadisticas['equipos_operativos'] = $result['total'];
    
    // Equipos en mantenimiento
    $query = "SELECT COUNT(*) as total FROM inventario WHERE estado = 'Mantenimiento'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $estadisticas['equipos_mantenimiento'] = $result['total'];
    
    // Mantenimientos pendientes (sin fecha de resolución)
    $query = "SELECT COUNT(*) as total FROM mantenimiento WHERE fecha_resolucion IS NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $estadisticas['mantenimientos_pendientes'] = $result['total'];
    
    // Próximas solicitudes (próximos 7 días)
    $query = "SELECT 
                s.id_solicitud,
                s.fecha,
                s.estado,
                u.nombre_usuario,
                p.nombre,
                p.apellido,
                e.nombre as espacio,
                a.nombre as actividad
              FROM solicitudes s
              LEFT JOIN usuario u ON s.id_usuario = u.id_usuario
              LEFT JOIN persona p ON u.id_persona = p.id_persona
              LEFT JOIN espacio e ON s.id_espacio = e.id_espacio
              LEFT JOIN actividad a ON s.id_actividad = a.id_actividad
              WHERE DATE(s.fecha) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              ORDER BY s.fecha ASC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estadisticas['proximas_solicitudes'] = $stmt->fetchAll();
    
    // Mantenimientos recientes
    $query = "SELECT 
                m.id_mantenimiento,
                m.fecha_reporte,
                m.tipo,
                m.descripicon_falla,
                i.serial,
                eq.modelo
              FROM mantenimiento m
              LEFT JOIN inventario i ON m.id_inventario = i.id_inventario
              LEFT JOIN equipos eq ON i.id_equipos = eq.id_equipos
              WHERE m.fecha_resolucion IS NULL
              ORDER BY m.fecha_reporte DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estadisticas['mantenimientos_recientes'] = $stmt->fetchAll();
    
    // Estadísticas por tipo de solicitud
    $query = "SELECT 
                a.nombre as actividad,
                COUNT(*) as total
              FROM solicitudes s
              LEFT JOIN actividad a ON s.id_actividad = a.id_actividad
              WHERE MONTH(s.fecha) = MONTH(CURDATE())
              GROUP BY s.id_actividad
              ORDER BY total DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estadisticas['solicitudes_por_actividad'] = $stmt->fetchAll();
    
    // Estadísticas por estado de inventario
    $query = "SELECT 
                estado,
                COUNT(*) as total
              FROM inventario
              GROUP BY estado";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estadisticas['inventario_por_estado'] = $stmt->fetchAll();
    
    http_response_code(200);
    echo json_encode($estadisticas);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error al obtener estadísticas", "error" => $e->getMessage()));
}
?>
