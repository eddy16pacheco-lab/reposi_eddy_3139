<?php
require_once 'Model.php';

class Categoria extends Model {
    protected $table = 'categoria';
    protected $primaryKey = 'id_categoria';
    
    /**
     * Crear categoría
     */
    public function create($data) {
        $sql = "INSERT INTO categoria (tipo_categoria, descripcion, estado) 
                VALUES (:tipo_categoria, :descripcion, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'tipo_categoria' => $data['tipo_categoria'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $data['estado'] ?? 'Activo'
        ]);
    }
    
    /**
     * Actualizar categoría
     */
    public function update($id, $data) {
        $sql = "UPDATE categoria SET 
                tipo_categoria = :tipo_categoria, 
                descripcion = :descripcion, 
                estado = :estado 
                WHERE id_categoria = :id";
        
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }
    
    /**
     * Obtener categorías activas
     */
    public function getActivas() {
        $sql = "SELECT * FROM categoria WHERE estado = 'Activo' ORDER BY tipo_categoria";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}