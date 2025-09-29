<?php
require_once 'config/database.php';
require_once 'config/email.php';

function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    }
    return $telefone;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $camposObrigatorios = ['nome' => 'Nome', 'email' => 'E-mail', 'telefone' => 'Telefone', 'cidade' => 'Cidade', 'estado' => 'Estado', 'senha' => 'Senha', 'confirma_senha' => 'Confirmar senha'];
    $erros = [];

    foreach ($camposObrigatorios as $campo => $rotulo) {
        if (empty(trim($_POST[$campo] ?? ''))) {
            $erros[] = "O campo <strong>{$rotulo}</strong> é obrigatório.";
        }
    }

    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O formato do <strong>E-mail</strong> é inválido.";
    }

    if (!empty($_POST['telefone'])) {
        $telefoneLimpo = preg_replace('/[^0-9]/', '', $_POST['telefone']);
        if (strlen($telefoneLimpo) < 10 || strlen($telefoneLimpo) > 11) {
            $erros[] = "O <strong>Telefone</strong> deve ter 10 ou 11 dígitos (com DDD).";
        }
    }

    if (!empty($_POST['senha'])) {
        if (strlen($_POST['senha']) < 6) {
            $erros[] = "A <strong>Senha</strong> deve ter no mínimo 6 caracteres.";
        }
        if ($_POST['senha'] !== $_POST['confirma_senha']) {
            $erros[] = "As <strong>senhas</strong> não coincidem.";
        }
    }

    if (!empty($erros)) {
        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Erro no Cadastro</title><style>body{font-family:Roboto,sans-serif;background:#fdf2f2;color:#58151c;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}.container{background:white;padding:30px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.1);max-width:500px;width:90%;border-left:5px solid #e53e3e}h1{color:#c53030;margin-top:0}ul{padding-left:20px;margin-bottom:20px}li{margin-bottom:8px}a{display:inline-block;padding:10px 18px;background:#e53e3e;color:white;text-decoration:none;border-radius:8px;font-weight:500;transition:background .3s ease}a:hover{background:#c53030}</style></head><body><div class='container'><h1>Erro no Cadastro</h1><p>Por favor, corrija os seguintes erros:</p><ul>";
        foreach ($erros as $erro) {
            echo "<li>{$erro}</li>";
        }
        echo "</ul><a href='javascript:history.back()'>Voltar e Corrigir</a></div></body></html>";
        exit;
    }

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $email = trim($_POST['email']);
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Erro</title><style>body{font-family:Roboto,sans-serif;background:#f0f4f8;color:#333;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}.container{text-align:center;background:white;padding:30px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.1);max-width:450px;width:90%}h2{color:#e74c3c}p{margin-bottom:20px}a{display:inline-block;padding:10px 20px;background:#3498db;color:white;text-decoration:none;border-radius:8px;margin:0 5px;transition:background .3s}a:hover{background:#2980b9}a.secondary{background:#95a5a6}a.secondary:hover{background:#7f8c8d}</style></head><body><div class='container'><h2>E-mail já cadastrado</h2><p>O e-mail informado já está em uso. Por favor, utilize outro e-mail ou faça login.</p><a href='login.html'>Fazer Login</a><a href='cadastro.html' class='secondary'>Voltar</a></div></body></html>";
            exit;
        }

        $nome = trim($_POST['nome']);
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
        $cidade = trim($_POST['cidade']);
        $estado = $_POST['estado'];
        $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $query = "INSERT INTO usuarios (nome, email, telefone, cidade, estado, senha_hash, data_cadastro) VALUES (:nome, :email, :telefone, :cidade, :estado, :senha_hash, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([':nome' => $nome, ':email' => $email, ':telefone' => $telefone, ':cidade' => $cidade, ':estado' => $estado, ':senha_hash' => $senha_hash]);

        try {
            $emailService = new EmailService();
            $emailService->enviarBoasVindas($email, $nome);
        } catch (Exception $e) {
            error_log("Erro ao enviar email de boas-vindas: " . $e->getMessage());
        }

        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Cadastro Realizado</title><style>body{font-family:Roboto,sans-serif;background:#f0f9f4;color:#333;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}.container{text-align:center;background:white;padding:40px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);max-width:550px;width:90%;border-top:5px solid #2ecc71}.icon{font-size:48px;color:#2ecc71;margin-bottom:15px}h1{color:#27ae60;margin-top:0}p{color:#555;line-height:1.6}.details{text-align:left;margin:25px 0;padding:15px;background:#f9f9f9;border-radius:8px;border:1px solid #eee}.details p{margin:8px 0}.highlight{font-weight:500;color:#2c3e50}a{display:inline-block;margin-top:20px;padding:12px 25px;background:#3498db;color:white;text-decoration:none;border-radius:8px;font-weight:500;transition:background .3s}a:hover{background:#2980b9}</style></head><body><div class='container'><div class='icon'>✓</div><h1>Cadastro Realizado com Sucesso!</h1><p>Olá <span class='highlight'>{$nome}</span>, seu cadastro foi confirmado!</p><div class='details'><p><strong>E-mail:</strong> {$email}</p><p><strong>Telefone:</strong> " . formatarTelefone($telefone) . "</p><p><strong>Localização:</strong> {$cidade} / {$estado}</p></div><p>Enviamos um e-mail de boas-vindas para você. Já pode fazer login na plataforma.</p><a href='login.html'>Fazer Login</a></div></body></html>";

    } catch (PDOException $e) {
        // Modo de depuração: Mude para false em produção
        $debug_mode = true;

        $mensagem_erro = "<p>Não foi possível concluir seu cadastro. Por favor, tente novamente mais tarde.</p>";
        if ($debug_mode) {
            $mensagem_erro .= "<p style='color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 15px; font-size: 14px; text-align: left;'><strong>Detalhe do Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Erro</title><style>body{font-family:Roboto,sans-serif;background:#fdf2f2;color:#58151c;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}.container{background:white;padding:30px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.1);max-width:500px;width:90%;text-align:center}h1{color:#c53030}p{margin-bottom:20px}a{display:inline-block;padding:10px 18px;background:#95a5a6;color:white;text-decoration:none;border-radius:8px;transition:background .3s}a:hover{background:#7f8c8d}</style></head><body><div class='container'><h1>Ops! Algo deu errado.</h1>{$mensagem_erro}<a href='javascript:history.back()'>Voltar</a></div></body></html>";
        error_log("Erro no cadastro: " . $e->getMessage());
    }
}
?>