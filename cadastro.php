<?php
require_once 'config/database.php';
require_once 'config/email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];
    
    if ($senha !== $confirma_senha) {
        echo "
            <div style='text-align: center; padding: 20px; font-family: Arial, sans-serif;'>
                <h2 style='color: #e74c3c;'>As senhas não conferem</h2>
                <p>Por favor, volte e tente novamente.</p>
                <a href='javascript:history.back()' style='display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Voltar</a>
            </div>
        ";
        exit;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuarios (nome, email, senha_hash) VALUES (:nome, :email, :senha_hash)";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":senha_hash", $senha_hash);
        
        if ($stmt->execute()) {
            try {
                $emailService = new EmailService();
                $emailService->enviarBoasVindas($email, $nome);
                // Notifica o admin sobre o novo cadastro
                $emailService->enviarCadastroAdmin($nome, $email);
                
                // Mensagem de sucesso bonita e profissional
                echo "
                    <div style='text-align: center; padding: 40px; font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #2ecc71;'>Cadastro Realizado com Sucesso!</h2>
                        <p style='color: #333; font-size: 16px;'>Olá {$nome}, seu cadastro foi confirmado.</p>
                        <p style='color: #666;'>Enviamos um email de boas-vindas para: {$email}</p>
                        <a href='login.html' style='display: inline-block; margin-top: 20px; padding: 12px 25px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Fazer Login</a>
                    </div>
                ";
                
            } catch (Exception $e) {
                // Mensagem amigável mesmo se o email falhar
                echo "
                    <div style='text-align: center; padding: 40px; font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #2ecc71;'>Cadastro Realizado com Sucesso!</h2>
                        <p style='color: #333; font-size: 16px;'>Olá {$nome}, seu cadastro foi confirmado.</p>
                        <p style='color: #666;'>Você já pode acessar o sistema.</p>
                        <a href='login.html' style='display: inline-block; margin-top: 20px; padding: 12px 25px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Fazer Login</a>
                    </div>
                ";
                
                // Registramos o erro sem mostrar para o usuário
                error_log("Erro ao enviar email: " . $e->getMessage());
            }
        }
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "
                <div style='text-align: center; padding: 40px; font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #e74c3c;'>Email já Cadastrado</h2>
                    <p style='color: #333; font-size: 16px;'>Este email já está sendo usado em nossa plataforma.</p>
                    <div style='margin-top: 20px;'>
                        <a href='login.html' style='display: inline-block; margin: 10px; padding: 12px 25px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Fazer Login</a>
                        <a href='javascript:history.back()' style='display: inline-block; margin: 10px; padding: 12px 25px; background-color: #95a5a6; color: white; text-decoration: none; border-radius: 5px;'>Tentar Outro Email</a>
                    </div>
                </div>
            ";
        } else {
            echo "
                <div style='text-align: center; padding: 40px; font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #e74c3c;'>Ops! Algo deu errado</h2>
                    <p style='color: #333; font-size: 16px;'>Desculpe pelo inconveniente. Por favor, tente novamente em alguns instantes.</p>
                    <a href='javascript:history.back()' style='display: inline-block; margin-top: 20px; padding: 12px 25px; background-color: #95a5a6; color: white; text-decoration: none; border-radius: 5px;'>Voltar</a>
                </div>
            ";
            error_log("Erro no cadastro: " . $e->getMessage());
        }
    }
}
?>