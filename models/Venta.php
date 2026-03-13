<?php
require_once 'Model.php';

class Venta extends Model {
    protected $table = 'venta';
    protected $primaryKey = 'id_venta';
    
    /**
     * Crear venta (encabezado)
     */
    public function create($data) {
        $sql = "INSERT INTO venta (id_usuario, id_cliente, fecha_venta, tipo_venta, 
                fecha_cobro, porcentaje, estado) 
                VALUES (:id_usuario, :id_cliente, :fecha_venta, :tipo_venta, 
                :fecha_cobro, :porcentaje, :estado)";
        
        $fechaVenta = $data['fecha_venta'] ?? date('Y-m-d');
        $fechaCobro = $data['fecha_cobro'] ?? ($data['tipo_venta'] == 'Credito' ? 
                     date('Y-m-d', strtotime('+30 days')) : $fechaVenta);
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_usuario' => $data['id_usuario'],
            'id_cliente' => $data['id_cliente'],
            'fecha_venta' => $fechaVenta,
            'tipo_venta' => $data['tipo_venta'],
            'fecha_cobro' => $fechaCobro,
            'porcentaje' => $data['porcentaje'] ?? 0,
            'estado' => $data['estado'] ?? 'Activo'
        ]);
    }
    
    /**
     * Obtener venta con todos los detalles
     */
    public function getVentaCompleta($id) {
        $sql = "SELECT v.*, 
                       CONCAT(per.nombre, ' ', per.apellido) as cliente_nombre,
                       u.nombre_usuario as vendedor,
                       c.telefono as cliente_telefono
                FROM venta v
                INNER JOIN cliente c ON v.id_cliente = c.id_cliente
                INNER JOIN persona per ON c.id_persona = per.id_persona
                INNER JOIN usuario u ON v.id_usuario = u.id_usuario
                WHERE v.id_venta = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $venta = $stmt->fetch();
        
        if ($venta) {
            // Obtener detalles de la venta
            $sqlDet = "SELECT dv.*, p.nombre_producto, p.codigo_barras 
                      FROM detalle_venta dv
                      INNER JOIN producto p ON dv.producto_id = p.producto_id
                      WHERE dv.id_venta = :id_venta";
            
            $stmt = $this->db->prepare($sqlDet);
            $stmt->execute(['id_venta' => $id]);
            $venta['detalles'] = $stmt->fetchAll();
            
            // Calcular totales
            $venta['subtotal'] = array_sum(array_column($venta['detalles'], 'monto_venta'));
            $venta['intereses'] = $venta['subtotal'] * ($venta['porcentaje'] / 100);
            $venta['total'] = $venta['subtotal'] + $venta['intereses'];
        }
        
        return $venta;
    }
    
    /**
     * Ventas del día (usando vista)
     */
    public function getVentasDelDia($fecha = null) {
        $fecha = $fecha ?? date('Y-m-d');
        
        $sql = "SELECT * FROM vw_ventas_dia WHERE fecha_venta = :fecha";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['fecha' => $fecha]);
        return $stmt->fetchAll();
    }
    
    /**
     * Reporte de ventas diarias
     */
    public function getReporteVentasDiarias($fechaInicio, $fechaFin) {
        $sql = "SELECT * FROM vw_report_ventas_diarias 
                WHERE fecha_venta BETWEEN :inicio AND :fin
                ORDER BY fecha_venta DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['inicio' => $fechaInicio, 'fin' => $fechaFin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Ventas por cliente
     */
    public function getVentasByCliente($idCliente) {
        $sql = "SELECT v.*, 
                       SUM(dv.monto_venta) as total_venta,
                       COUNT(dv.id_detalle_venta) as items
                FROM venta v
                INNER JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                WHERE v.id_cliente = :id_cliente
                GROUP BY v.id_venta
                ORDER BY v.fecha_venta DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_cliente' => $idCliente]);
        return $stmt->fetchAll();
    }
    
    /**
     * Anular venta
     */
    public function anular($id) {
        try {
            $this->db->beginTransaction();
            
            // Obtener detalles de la venta
            $sqlDet = "SELECT * FROM detalle_venta WHERE id_venta = :id_venta";
            $stmt = $this->db->prepare($sqlDet);
            $stmt->execute(['id_venta' => $id]);
            $detalles = $stmt->fetchAll();
            
            // Devolver productos al inventario (el trigger lo hace automático al eliminar detalles)
            foreach ($detalles as $detalle) {
                $sqlDel = "DELETE FROM detalle_venta WHERE id_detalle_venta = :id";
                $stmt = $this->db->prepare($sqlDel);
                $stmt->execute(['id' => $detalle['id_detalle_venta']]);
            }
            
            // Cambiar estado de la venta
            $sql = "UPDATE venta SET estado = 'Inactivo' WHERE id_venta = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['id' => $id]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}