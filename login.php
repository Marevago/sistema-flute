<?php
// Inclui o arquivo de configuração do banco de dados
require_once 'config/database.php';

// Inicia a sessão
session_start();

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pega os dados do formulário
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Cria uma nova conexão com o banco
    $database = new Database();
    $db = $database->getConnection();

    try {
        // Prepara a consulta SQL
        $query = "SELECT id, nome, senha_hash FROM usuarios WHERE email = :email";
        $stmt = $db->prepare($query);
        
        // Vincula os valores
        $stmt->bindParam(":email", $email);
        
        // Executa a consulta
        $stmt->execute();

        // Se encontrou o usuário
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verifica se a senha está correta
            if (password_verify($senha, $row['senha_hash'])) {
                // Login bem sucedido
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['nome'];
                
                // Redireciona para a página principal
                header("Location: produtos.php");
                exit;
            } else {
                $erro = "Senha incorreta";
            }
        } else {
            $erro = "Usuário não encontrado";
        }
    } catch(PDOException $e) {
        $erro = "Erro no sistema: " . $e->getMessage();
    }

    // Se chegou aqui, é porque deu erro
    echo $erro;
}
?>