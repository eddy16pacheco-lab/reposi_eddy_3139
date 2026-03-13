<?php
require_once 'Model.php';

class Persona extends Model {
    protected $table = 'persona';
    protected $primaryKey = 'id_persona';
    
    /**
     * Crear nueva persona
     */
    public function create($data) {
        $sql = "INSERT INTO persona (cedula, nombre, apellido) 
                VALUES (:cedula, :nombre, :apellido)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'cedula' => $data['cedula'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido']
        ]);
    }
    
    /**
     * Actualizar persona
     */
    public function update($id, $data) {
        $sql = "UPDATE persona SET 
                cedula = :cedula, 
                nombre = :nombre, 
                apellido = :apellido 
                WHERE id_persona = :id";
        
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }
    
    /**
     * Buscar por cédula
     */
    public function getByCedula($cedula) {
        $sql = "SELECT * FROM persona WHERE cedula = :cedula";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cedula' => $cedula]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener nombre completo
     */
    public function getNombreCompleto($id) {
        $persona = $this->getById($id);
        return $persona ? $persona['nombre'] . ' ' . $persona['apellido'] : '';
    }
}