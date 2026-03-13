<?php
require_once 'Model.php';

class Producto extends Model {
    protected $table = 'producto';
    protected $primaryKey = 'producto_id';
    
    /**
     * Crear producto
     */
    public function create($data) {
        $sql = "INSERT INTO producto (codigo_barras, id_categoria, nombre_producto, descripcion, 
                capacidad_unidad, unidad_medida, precio_compra, precio_venta) 
                VALUES (:codigo_barras, :id_categoria, :nombre_producto, :descripcion, 
                :capacidad_unidad, :unidad_medida, :precio_compra, :precio_venta)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'codigo_barras' => $data['codigo_barras'],
            'id_categoria' => $data['id_categoria'],
            'nombre_producto' => $data['nombre_producto'],
            'descripcion' => $data['descripcion'] ?? null,
            'capacidad_unidad' => $data['capacidad_unidad'],
            'unidad_medida' => $data['unidad_medida'],
            'precio_compra' => $data['precio_compra'] ?? 0,
            'precio_venta' => $data['precio_venta']
        ]);
    }
    
    /**
     * Obtener producto con categoría
     */
    public function getProductoCompleto($id) {
        $sql = "SELECT p.*, c.tipo_categoria, c.descripcion as categoria_desc 
                FROM producto p
                INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE p.producto_id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar producto por código de barras
     */
    public function getByCodigoBarras($codigo) {
        $sql = "SELECT p.*, i.cantidad_disponible, i.estado_inventario 
                FROM producto p
                LEFT JOIN inventario i ON p.producto_id = i.producto_id
                WHERE p.codigo_barras = :codigo";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar productos por nombre
     */
    public function searchByName($term) {
        $sql = "SELECT p.*, c.tipo_categoria, i.cantidad_disponible 
                FROM producto p
                INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                LEFT JOIN inventario i ON p.producto_id = i.producto_id
                WHERE p.nombre_producto LIKE :term 
                OR p.codigo_barras LIKE :term
                ORDER BY p.nombre_producto
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['term' => "%$term%"]);
        return $stmt->fetchAll();
    }
    
    /**
     * Productos por categoría
     */
    public function getByCategoria($idCategoria) {
        $sql = "SELECT p.*, i.cantidad_disponible 
                FROM producto p
                LEFT JOIN inventario i ON p.producto_id = i.producto_id
                WHERE p.id_categoria = :id_categoria
                ORDER BY p.nombre_producto";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_categoria' => $idCategoria]);
        return $stmt->fetchAll();
    }
    
    /**
     * Actualizar producto
     */
    public function update($id, $data) {
        $sql = "UPDATE producto SET 
                codigo_barras = :codigo_barras,
                id_categoria = :id_categoria,
                nombre_producto = :nombre_producto,
                descripcion = :descripcion,
                capacidad_unidad = :capacidad_unidad,
                unidad_medida = :unidad_medida,
                precio_compra = :precio_compra,
                precio_venta = :precio_venta
                WHERE producto_id = :id";
        
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }
}