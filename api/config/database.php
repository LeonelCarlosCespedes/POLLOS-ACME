<?php
class Database {
    private $host = "localhost";
    private $db_name = "pollos_acme";
    private $username = "root"; // Cambia si tu usuario de MySQL es diferente
    private $password = "";     // Cambia si tu MySQL tiene contraseña
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8"); // Para soportar tildes y ñ
        } catch(PDOException $e) {
            echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
        }
        return $this->conn;
    }
}
?>