<?php
/**
 * API de Préstamos de Equipos
 * Gestión completa de préstamos, devoluciones, multas y comprobantes
 */

require_once '../config/database.php';
require_once '../config/cors.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Extraer ID si existe en la URL
$uri_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$id = isset($uri_parts[count($uri_parts) - 1]) && is_numeric($uri_parts[count($uri_parts) - 1]) 
    ? (int)$uri_parts[count($uri_parts) - 1] 
    : null;

try {
    switch($method) {
        case 'GET':
            if ($id) {
                getPrestamo($db, $id);
            } else {
                // Verificar si se solicita calendario
                if (isset($_GET['calendario'])) {
                    getCalendarioPrestamos($db);
                } elseif (isset($_GET['activos'])) {
                    getPrestamosActivos($db);
                } elseif (isset($_GET['vencidos'])) {
                    getPrestamosVencidos($db);
                } elseif (isset($_GET['usuario'])) {
                    getPrestamosPorUsuario($db, $_GET['usuario']);
                } elseif (isset($_GET['equipo'])) {
                    getHistorialEquipo($db, $_GET['equipo']);
                } else {
                    getAllPrestamos($db);
                }
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['accion'])) {
                switch($data['accion']) {
                    case 'devolver':
                        devolverPrestamo($db, $data);
                        break;
                    case 'renovar':
                        renovarPrestamo($db, $data);
                        break;
                    case 'generar_comprobante':
                        generarComprobante($db, $data);
                        break;
                    default:
                        crearPrestamo($db, $data);
                }
            } else {
                crearPrestamo($db, $data);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents("php://input"), true);
            updatePrestamo($db, $id, $data);
            break;
            
        case 'DELETE':
            deletePrestamo($db, $id);
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

function getAllPrestamos($db) {
    $query = "SELECT 
                p.*,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante,
                u.correo AS correo_solicitante,
                e.modelo AS equipo,
                i.serial,
                uf.nombre AS ubicacion,
                CONCAT(pera.nombre, ' ', pera.apellido) AS autorizado_por,
                DATEDIFF(p.fecha_devolucion_programada, NOW()) AS dias_restantes
              FROM prestamo p
              JOIN usuario us ON p.id_usuario_solicitante = us.id_usuario
              JOIN persona per ON us.id_persona = per.id_persona
              JOIN inventario i ON p.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              JOIN ubicacion_fisica uf ON i.id_ubicacion = uf.id_ubicacion
              LEFT JOIN usuario ua ON p.id_usuario_autoriza = ua.id_usuario
              LEFT JOIN persona pera ON ua.id_persona = pera.id_persona
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $prestamos]);
}

function getPrestamo($db, $id) {
    $query = "SELECT 
                p.*,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante,
                u.correo AS correo_solicitante,
                per.telefono AS telefono_solicitante,
                e.modelo AS equipo,
                i.serial,
                i.estado AS estado_equipo,
                uf.nombre AS ubicacion,
                CONCAT(pera.nombre, ' ', pera.apellido) AS autorizado_por,
                (SELECT COUNT(*) FROM multa WHERE id_prestamo = p.id_prestamo AND estado = 'Pendiente') AS multas_pendientes
              FROM prestamo p
              JOIN usuario us ON p.id_usuario_solicitante = us.id_usuario
              JOIN persona per ON us.id_persona = per.id_persona
              JOIN inventario i ON p.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              JOIN ubicacion_fisica uf ON i.id_ubicacion = uf.id_ubicacion
              LEFT JOIN usuario ua ON p.id_usuario_autoriza = ua.id_usuario
              LEFT JOIN persona pera ON ua.id_persona = pera.id_persona
              WHERE p.id_prestamo = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prestamo) {
        echo json_encode(['success' => true, 'data' => $prestamo]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Préstamo no encontrado']);
    }
}

function getPrestamosActivos($db) {
    $query = "SELECT * FROM vista_prestamos_activos WHERE estado = 'Activo' ORDER BY fecha_devolucion_programada";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $prestamos]);
}

function getPrestamosVencidos($db) {
    $query = "SELECT * FROM vista_prestamos_activos WHERE dias_retraso > 0 ORDER BY dias_retraso DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $prestamos]);
}

function getPrestamosPorUsuario($db, $id_usuario) {
    $query = "SELECT 
                p.*,
                e.modelo AS equipo,
                i.serial,
                DATEDIFF(p.fecha_devolucion_programada, NOW()) AS dias_restantes
              FROM prestamo p
              JOIN inventario i ON p.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              WHERE p.id_usuario_solicitante = :id_usuario
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $prestamos]);
}

function getHistorialEquipo($db, $id_inventario) {
    $query = "SELECT 
                p.*,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante
              FROM prestamo p
              JOIN usuario u ON p.id_usuario_solicitante = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              WHERE p.id_inventario = :id_inventario
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_inventario', $id_inventario);
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $historial]);
}

function getCalendarioPrestamos($db) {
    $query = "SELECT 
                p.id_prestamo,
                p.fecha_prestamo AS start,
                p.fecha_devolucion_programada AS end,
                CONCAT(e.modelo, ' - ', per.nombre) AS title,
                p.estado,
                CASE 
                    WHEN p.estado = 'Activo' THEN '#28a745'
                    WHEN p.estado = 'Vencido' THEN '#dc3545'
                    WHEN p.estado = 'Devuelto' THEN '#6c757d'
                    ELSE '#ffc107'
                END AS color
              FROM prestamo p
              JOIN inventario i ON p.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              JOIN usuario u ON p.id_usuario_solicitante = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              WHERE p.estado IN ('Activo', 'Vencido')";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $eventos]);
}

function crearPrestamo($db, $data) {
    // Verificar disponibilidad del equipo
    $query_check = "SELECT estado FROM inventario WHERE id_inventario = :id_inventario";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':id_inventario', $data['id_inventario']);
    $stmt_check->execute();
    $equipo = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$equipo || $equipo['estado'] !== 'Operativo') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El equipo no está disponible para préstamo']);
        return;
    }
    
    // Verificar si el equipo ya está prestado
    $query_prestamo = "SELECT COUNT(*) as count FROM prestamo 
                       WHERE id_inventario = :id_inventario 
                       AND estado = 'Activo'";
    $stmt_prestamo = $db->prepare($query_prestamo);
    $stmt_prestamo->bindParam(':id_inventario', $data['id_inventario']);
    $stmt_prestamo->execute();
    $result = $stmt_prestamo->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El equipo ya está prestado']);
        return;
    }
    
    $query = "INSERT INTO prestamo 
              (id_inventario, id_usuario_solicitante, id_usuario_autoriza, fecha_prestamo, 
               fecha_devolucion_programada, observaciones, estado) 
              VALUES 
              (:id_inventario, :id_usuario_solicitante, :id_usuario_autoriza, :fecha_prestamo, 
               :fecha_devolucion_programada, :observaciones, 'Activo')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_inventario', $data['id_inventario']);
    $stmt->bindParam(':id_usuario_solicitante', $data['id_usuario_solicitante']);
    $stmt->bindParam(':id_usuario_autoriza', $data['id_usuario_autoriza']);
    $stmt->bindParam(':fecha_prestamo', $data['fecha_prestamo']);
    $stmt->bindParam(':fecha_devolucion_programada', $data['fecha_devolucion_programada']);
    $stmt->bindParam(':observaciones', $data['observaciones']);
    
    if ($stmt->execute()) {
        $id_prestamo = $db->lastInsertId();
        
        // Generar código de comprobante
        $codigo = 'PRES-' . date('Ymd') . '-' . str_pad($id_prestamo, 6, '0', STR_PAD_LEFT);
        $query_comp = "INSERT INTO comprobante_prestamo (id_prestamo, codigo_comprobante) 
                       VALUES (:id_prestamo, :codigo)";
        $stmt_comp = $db->prepare($query_comp);
        $stmt_comp->bindParam(':id_prestamo', $id_prestamo);
        $stmt_comp->bindParam(':codigo', $codigo);
        $stmt_comp->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Préstamo creado exitosamente',
            'id_prestamo' => $id_prestamo,
            'codigo_comprobante' => $codigo
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear el préstamo']);
    }
}

function devolverPrestamo($db, $data) {
    $id_prestamo = $data['id_prestamo'];
    $fecha_devolucion = $data['fecha_devolucion'] ?? date('Y-m-d H:i:s');
    $observaciones = $data['observaciones'] ?? null;
    
    // Obtener datos del préstamo
    $query_pres = "SELECT * FROM prestamo WHERE id_prestamo = :id";
    $stmt_pres = $db->prepare($query_pres);
    $stmt_pres->bindParam(':id', $id_prestamo);
    $stmt_pres->execute();
    $prestamo = $stmt_pres->fetch(PDO::FETCH_ASSOC);
    
    if (!$prestamo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Préstamo no encontrado']);
        return;
    }
    
    // Verificar si hay retraso y crear multa
    $fecha_programada = new DateTime($prestamo['fecha_devolucion_programada']);
    $fecha_real = new DateTime($fecha_devolucion);
    $dias_retraso = $fecha_real->diff($fecha_programada)->days;
    
    if ($fecha_real > $fecha_programada && $dias_retraso > 0) {
        $monto_multa = $dias_retraso * 5; // $5 por día de retraso
        $query_multa = "INSERT INTO multa (id_prestamo, monto, motivo, estado, fecha_multa) 
                        VALUES (:id_prestamo, :monto, :motivo, 'Pendiente', :fecha)";
        $stmt_multa = $db->prepare($query_multa);
        $stmt_multa->bindParam(':id_prestamo', $id_prestamo);
        $stmt_multa->bindParam(':monto', $monto_multa);
        $motivo = "Retraso de $dias_retraso días en la devolución";
        $stmt_multa->bindParam(':motivo', $motivo);
        $stmt_multa->bindParam(':fecha', $fecha_devolucion);
        $stmt_multa->execute();
    }
    
    // Actualizar préstamo
    $query = "UPDATE prestamo 
              SET fecha_devolucion_real = :fecha_devolucion, 
                  estado = 'Devuelto',
                  observaciones = CONCAT(COALESCE(observaciones, ''), ' | Devolución: ', :obs)
              WHERE id_prestamo = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':fecha_devolucion', $fecha_devolucion);
    $stmt->bindParam(':obs', $observaciones);
    $stmt->bindParam(':id', $id_prestamo);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Préstamo devuelto exitosamente',
            'dias_retraso' => $dias_retraso,
            'multa' => $dias_retraso > 0 ? $monto_multa : 0
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al devolver el préstamo']);
    }
}

function renovarPrestamo($db, $data) {
    $id_prestamo = $data['id_prestamo'];
    $nueva_fecha = $data['nueva_fecha_devolucion'];
    
    $query = "UPDATE prestamo 
              SET fecha_devolucion_programada = :nueva_fecha,
                  observaciones = CONCAT(COALESCE(observaciones, ''), ' | Renovado hasta: ', :nueva_fecha)
              WHERE id_prestamo = :id AND estado = 'Activo'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nueva_fecha', $nueva_fecha);
    $stmt->bindParam(':id', $id_prestamo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Préstamo renovado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al renovar el préstamo']);
    }
}

function updatePrestamo($db, $id, $data) {
    $query = "UPDATE prestamo SET ";
    $fields = [];
    $params = [':id' => $id];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id_prestamo') {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }
    
    $query .= implode(', ', $fields) . " WHERE id_prestamo = :id";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Préstamo actualizado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el préstamo']);
    }
}

function deletePrestamo($db, $id) {
    // Solo se puede eliminar si está en estado Cancelado
    $query = "DELETE FROM prestamo WHERE id_prestamo = :id AND estado = 'Cancelado'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Préstamo eliminado exitosamente']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Solo se pueden eliminar préstamos cancelados']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el préstamo']);
    }
}

function generarComprobante($db, $data) {
    // Esta función generaría un PDF con el comprobante
    // Por ahora solo retornamos los datos
    $id_prestamo = $data['id_prestamo'];
    
    $query = "SELECT 
                cp.*,
                p.*,
                CONCAT(per.nombre, ' ', per.apellido) AS solicitante,
                e.modelo AS equipo,
                i.serial
              FROM comprobante_prestamo cp
              JOIN prestamo p ON cp.id_prestamo = p.id_prestamo
              JOIN usuario u ON p.id_usuario_solicitante = u.id_usuario
              JOIN persona per ON u.id_persona = per.id_persona
              JOIN inventario i ON p.id_inventario = i.id_inventario
              JOIN equipo e ON i.id_equipo = e.id_equipo
              WHERE cp.id_prestamo = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_prestamo);
    $stmt->execute();
    $comprobante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comprobante) {
        echo json_encode(['success' => true, 'data' => $comprobante]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comprobante no encontrado']);
    }
}
?>
