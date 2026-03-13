<?php
require_once 'Model.php';

class Usuario extends Model {
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    
    /**
     * Crear usuario
     */
    public function create($data) {
        $sql = "INSERT INTO usuario (nombre_usuario, contraseña_usuario, correo_electronico, estado, id_persona, rol) 
                VALUES (:nombre_usuario, :contraseña, :correo, :estado, :id_persona, :rol)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre_usuario' => $data['nombre_usuario'],
            'contraseña' => md5($data['contraseña']), // Usar password_hash() en producción
            'correo' => $data['correo_electronico'],
            'estado' => $data['estado'] ?? 'Activo',
            'id_persona' => $data['id_persona'],
            'rol' => $data['rol'] ?? 'Trabajador'
        ]);
    }
    
    /**
     * Autenticar usuario
     */
    public function login($username, $password) {
        $sql = "SELECT u.*, p.nombre, p.apellido 
                FROM usuario u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.nombre_usuario = :username 
                AND u.contraseña_usuario = :password 
                AND u.estado = 'Activo'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'username' => $username,
            'password' => md5($password) // Usar password_verify() en producción
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener usuarios con datos de persona
     */
    public function getUsuariosCompletos() {
        $sql = "SELECT u.*, p.cedula, p.nombre, p.apellido 
                FROM usuario u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                ORDER BY u.rol, p.nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Cambiar estado del usuario
     */
    public function cambiarEstado($id, $estado) {
        $sql = "UPDATE usuario SET estado = :estado WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($id, $newPassword) {
        $sql = "UPDATE usuario SET contraseña_usuario = :password WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'password' => md5($newPassword),
            'id' => $id
        ]);
    }
}