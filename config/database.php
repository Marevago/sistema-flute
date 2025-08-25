<?php
class Database {
    private $host = "localhost"; // GoDaddy geralmente usa localhost
    private $db_name = "creates_sistema_login";
    private $username = "user_mda";
    private $password = "jeqhiw-mojjos-7boRqe";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Erro na conexão: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>