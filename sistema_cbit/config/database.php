<?php
/**
 * Configuración de Base de Datos
 * Sistema de Gestión CBIT
 */

class Database {
    private $host = "localhost";
    private $db_name = "db_sistema_web_cbit";
    private $username = "root";
    private $password = "Eddy123.";
    private $conn;

    /**
     * Obtener conexión a la base de datos
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
