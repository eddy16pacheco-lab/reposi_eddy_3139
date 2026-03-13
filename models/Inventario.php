<?php
require_once 'Model.php';

class Inventario extends Model {
    protected $table = 'inventario';
    protected $primaryKey = 'id_inventario';
    
    /**
     * Crear registro de inventario
     */
    public function create($data) {
        $sql = "INSERT INTO inventario (producto_id, cantidad_disponible, cantidad_min_stock, 
                estado_inventario, fecha_vencimiento) 
                VALUES (:producto_id, :cantidad_disponible, :cantidad_min_stock, 
                :estado_inventario, :fecha_vencimiento)";
        
        $estado = ($data['cantidad_disponible'] <= $data['cantidad_min_stock']) ? 'NO DISPONIBLE' : 'DISPONIBLE';
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'producto_id' => $data['producto_id'],
            'cantidad_disponible' => $data['cantidad_disponible'],
            'cantidad_min_stock' => $data['cantidad_min_stock'] ?? 1,
            'estado_inventario' => $estado,
            'fecha_vencimiento' => $data['fecha_vencimiento']
        ]);
    }
    
    /**
     * Obtener inventario completo con datos de producto
     */
    public function getInventarioCompleto() {
        $sql = "SELECT i.*, p.nombre_producto, p.codigo_barras, p.precio_venta, 
                       c.tipo_categoria,
                       DATEDIFF(i.fecha_vencimiento, CURDATE()) as dias_para_vencer,
                       CASE 
                           WHEN i.cantidad_disponible <= i.cantidad_min_stock THEN 'CRÍTICO'
                           WHEN i.cantidad_disponible <= i.cantidad_min_stock * 2 THEN 'BAJO'
                           ELSE 'NORMAL'
                       END as nivel_stock
                FROM inventario i
                INNER JOIN producto p ON i.producto_id = p.producto_id
                INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                ORDER BY 
                    CASE 
                        WHEN i.cantidad_disponible <= i.cantidad_min_stock THEN 1
                        WHEN DATEDIFF(i.fecha_vencimiento, CURDATE()) <= 15 THEN 2
                        ELSE 3
                    END,
                    i.fecha_vencimiento ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Productos con bajo stock (usando la vista)
     */
    public function getProductosBajoStock() {
        $sql = "SELECT * FROM vw_productos_bajo_stock ORDER BY cantidad_disponible ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Productos próximos a vencer
     */
    public function getProximosAVencer($dias = 30) {
        $sql = "SELECT i.*, p.nombre_producto, p.codigo_barras,
                       DATEDIFF(i.fecha_vencimiento, CURDATE()) as dias_restantes
                FROM inventario i
                INNER JOIN producto p ON i.producto_id = p.producto_id
                WHERE i.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                AND i.estado_inventario = 'DISPONIBLE'
                ORDER BY i.fecha_vencimiento ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dias' => $dias]);
        return $stmt->fetchAll();
    }
    
    /**
     * Ajustar stock manualmente
     */
    public function ajustarStock($productoId, $nuevaCantidad, $motivo = '') {
        try {
            $this->db->beginTransaction();
            
            // Obtener inventario actual
            $sql = "SELECT * FROM inventario WHERE producto_id = :producto_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['producto_id' => $productoId]);
            $inventario = $stmt->fetch();
            
            if (!$inventario) {
                throw new Exception("Producto no encontrado en inventario");
            }
            
            // Registrar movimiento (asumiendo tabla de movimientos)
            $sqlMov = "INSERT INTO movimiento_inventario (id_inventario, cantidad_anterior, cantidad_nueva, motivo, fecha) 
                       VALUES (:id_inventario, :anterior, :nueva, :motivo, NOW())";
            $stmt = $this->db->prepare($sqlMov);
            $stmt->execute([
                'id_inventario' => $inventario['id_inventario'],
                'anterior' => $inventario['cantidad_disponible'],
                'nueva' => $nuevaCantidad,
                'motivo' => $motivo
            ]);
            
            // Actualizar inventario
            $estado = ($nuevaCantidad <= $inventario['cantidad_min_stock']) ? 'NO DISPONIBLE' : 'DISPONIBLE';
            
            $sqlUpd = "UPDATE inventario SET 
                      cantidad_disponible = :cantidad,
                      estado_inventario = :estado
                      WHERE id_inventario = :id";
            
            $stmt = $this->db->prepare($sqlUpd);
            $result = $stmt->execute([
                'cantidad' => $nuevaCantidad,
                'estado' => $estado,
                'id' => $inventario['id_inventario']
            ]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}