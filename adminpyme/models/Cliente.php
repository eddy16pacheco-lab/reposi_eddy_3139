<?php
require_once 'Model.php';
require_once 'Persona.php';

class Cliente extends Model {
    protected $table = 'cliente';
    protected $primaryKey = 'id_cliente';
    private $personaModel;
    
    public function __construct() {
        parent::__construct();
        $this->personaModel = new Persona();
    }
    
    /**
     * Crear cliente con persona asociada
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Crear persona primero
            $personaData = [
                'cedula' => $data['cedula'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO persona (cedula, nombre, apellido) VALUES (:cedula, :nombre, :apellido)");
            $stmt->execute($personaData);
            $idPersona = $this->db->lastInsertId();
            
            // Crear cliente
            $sql = "INSERT INTO cliente (id_persona, id_parroquia, telefono, estado) 
                    VALUES (:id_persona, :id_parroquia, :telefono, :estado)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id_persona' => $idPersona,
                'id_parroquia' => $data['id_parroquia'],
                'telefono' => $data['telefono'],
                'estado' => $data['estado'] ?? 'Activo'
            ]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener cliente con datos de persona
     */
    public function getClienteCompleto($id) {
        $sql = "SELECT c.*, p.cedula, p.nombre, p.apellido, pa.nombre_parroquia 
                FROM cliente c
                INNER JOIN persona p ON c.id_persona = p.id_persona
                LEFT JOIN parroquia pa ON c.id_parroquia = pa.id_parroquia
                WHERE c.id_cliente = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener todos los clientes con sus datos
     */
    public function getAllClientes() {
        $sql = "SELECT c.*, p.cedula, p.nombre, p.apellido, pa.nombre_parroquia 
                FROM cliente c
                INNER JOIN persona p ON c.id_persona = p.id_persona
                LEFT JOIN parroquia pa ON c.id_parroquia = pa.id_parroquia
                WHERE c.estado = 'Activo'
                ORDER BY p.nombre, p.apellido";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar cliente por teléfono
     */
    public function getByTelefono($telefono) {
        $sql = "SELECT c.*, p.nombre, p.apellido 
                FROM cliente c
                INNER JOIN persona p ON c.id_persona = p.id_persona
                WHERE c.telefono = :telefono";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['telefono' => $telefono]);
        return $stmt->fetch();
    }
    
    /**
     * Actualizar cliente
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Obtener id_persona del cliente
            $cliente = $this->getById($id);
            
            // Actualizar persona
            $sqlPersona = "UPDATE persona SET 
                          cedula = :cedula, 
                          nombre = :nombre, 
                          apellido = :apellido 
                          WHERE id_persona = :id_persona";
            
            $stmt = $this->db->prepare($sqlPersona);
            $stmt->execute([
                'cedula' => $data['cedula'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'id_persona' => $cliente['id_persona']
            ]);
            
            // Actualizar cliente
            $sqlCliente = "UPDATE cliente SET 
                          id_parroquia = :id_parroquia, 
                          telefono = :telefono, 
                          estado = :estado 
                          WHERE id_cliente = :id";
            
            $stmt = $this->db->prepare($sqlCliente);
            $result = $stmt->execute([
                'id_parroquia' => $data['id_parroquia'],
                'telefono' => $data['telefono'],
                'estado' => $data['estado'],
                'id' => $id
            ]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}