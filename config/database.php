<?php
class Database {
    private $host = "localhost:8889"; // Porta padrão do MySQL no MAMP
    private $db_name = "sistema_login";
    private $username = "root";
    private $password = "root"; // Senha padrão do MAMP
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
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