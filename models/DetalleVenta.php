<?php
require_once 'Model.php';

class DetalleVenta extends Model {
    protected $table = 'detalle_venta';
    protected $primaryKey = 'id_detalle_venta';
    
    /**
     * Agregar producto a venta
     */
    public function agregarProducto($idVenta, $idProducto, $cantidad, $precioVenta) {
        $montoVenta = $cantidad * $precioVenta;
        
        $sql = "INSERT INTO detalle_venta (id_venta, producto_id, cantidad_vendida, monto_venta) 
                VALUES (:id_venta, :producto_id, :cantidad, :monto)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_venta' => $idVenta,
            'producto_id' => $idProducto,
            'cantidad' => $cantidad,
            'monto' => $montoVenta
        ]);
    }
    
    /**
     * Productos más vendidos (usando vista)
     */
    public function getProductosMasVendidos($limite = 10) {
        $sql = "SELECT * FROM vw_productos_mas_vendidos LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Detalles de una venta
     */
    public function getDetallesByVenta($idVenta) {
        $sql = "SELECT dv.*, p.nombre_producto, p.codigo_barras, 
                       p.precio_venta, p.unidad_medida
                FROM detalle_venta dv
                INNER JOIN producto p ON dv.producto_id = p.producto_id
                WHERE dv.id_venta = :id_venta";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_venta' => $idVenta]);
        return $stmt->fetchAll();
    }
    
    /**
     * Actualizar cantidad de un detalle
     */
    public function actualizarCantidad($idDetalle, $nuevaCantidad, $precioUnitario) {
        $nuevoMonto = $nuevaCantidad * $precioUnitario;
        
        $sql = "UPDATE detalle_venta SET 
                cantidad_vendida = :cantidad,
                monto_venta = :monto
                WHERE id_detalle_venta = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'cantidad' => $nuevaCantidad,
            'monto' => $nuevoMonto,
            'id' => $idDetalle
        ]);
    }
}