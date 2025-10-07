<?php
require_once 'config/database.php';
require_once 'config/email.php';

// --- Funções Auxiliares ---
function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    }
    return $telefone;
}

// --- Funções de Renderização ---
function render_page_header($title) {
    $ga_include = __DIR__ . '/config/analytics.php';
    $analytics_script = file_exists($ga_include) ? file_get_contents($ga_include) : '';

    echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>{$title} - Flute Incensos</title>{$analytics_script}<link href='https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap' rel='stylesheet'><link href='https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap' rel='stylesheet'><link rel='stylesheet' href='styles.css?v=1.3'><style>body{background-color:#f7f7f8;background-image:url('uploads/background04.png');background-repeat:no-repeat;background-position:center center;background-size:cover;background-attachment:fixed}.main-container{max-width:1200px;margin:0 auto;padding:20px;display:flex;justify-content:center;align-items:center;min-height:calc(100vh - 200px)}.card{text-align:center;background-color:white;padding:40px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);max-width:550px;width:100%}.card-success{border-top:5px solid #2ecc71}.card-error{border-top:5px solid #e53e3e}img.logo{width:180px;margin-bottom:15px}.icon{font-size:48px;margin-bottom:15px}.icon-success{color:#2ecc71}.icon-error{color:#c53030}h1,h2{margin-top:0}.details{text-align:left;margin:25px 0;padding:15px;background-color:#f9f9f9;border-radius:8px;border:1px solid #eee}.details p{margin:8px 0}.highlight{font-weight:500;color:#2c3e50}a.btn{display:inline-block;margin-top:20px;padding:12px 25px;background-color:#3498db;color:white;text-decoration:none;border-radius:8px;font-weight:500;transition:background-color .3s}a.btn:hover{background-color:#2980b9}a.btn-secondary{background-color:#95a5a6}a.btn-secondary:hover{background-color:#7f8c8d}</style></head><body>";
    include __DIR__ . '/includes/header.php';
}

function render_page_footer() {
    include __DIR__ . '/includes/footer.php';
    echo "</body></html>";
}

// Função para validar CNPJ
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false; // Todos os dígitos iguais
    
    $tamanho = strlen($cnpj) - 2;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) $pos = 9;
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    if ($resultado != $digitos[0]) return false;
    
    $tamanho = $tamanho + 1;
    $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) $pos = 9;
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    return $resultado == $digitos[1];
}

// --- Lógica Principal do Formulário ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $camposObrigatorios = ['nome' => 'Nome', 'nome_empresa' => 'Nome da Empresa', 'cnpj' => 'CNPJ', 'email' => 'E-mail', 'telefone' => 'Telefone', 'cidade' => 'Cidade', 'estado' => 'Estado', 'senha' => 'Senha', 'confirma_senha' => 'Confirmar senha'];
    $erros = [];

    foreach ($camposObrigatorios as $campo => $rotulo) {
        if (empty(trim($_POST[$campo] ?? ''))) {
            $erros[] = "O campo <strong>{$rotulo}</strong> é obrigatório.";
        }
    }

    if (!empty($_POST['cnpj']) && !validarCNPJ($_POST['cnpj'])) {
        $erros[] = "O <strong>CNPJ</strong> informado é inválido.";
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
        render_page_header('Erro no Cadastro');
        echo "<main class='main-container'><div class='card card-error'><h1 class='icon-error'>&#10006;</h1><h2>Erro no Cadastro</h2><p>Por favor, corrija os seguintes erros:</p><ul style='text-align:left;display:inline-block;'>";
        foreach ($erros as $erro) {
            echo "<li>{$erro}</li>";
        }
        echo "</ul><br><a href='javascript:history.back()' class='btn btn-secondary'>Voltar e Corrigir</a></div></main>";
        render_page_footer();
        exit;
    }

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $email = trim($_POST['email']);
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
        
        // Verifica se email já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            render_page_header('Erro no Cadastro');
            echo "<main class='main-container'><div class='card card-error'><img src='uploads/flute_logo.png' alt='Flute Incensos' class='logo'><h2>E-mail já cadastrado</h2><p>O e-mail informado já está em uso. Por favor, utilize outro e-mail ou faça login.</p><div><a href='login.html' class='btn'>Fazer Login</a><a href='cadastro.html' class='btn btn-secondary' style='margin-left:10px;'>Voltar</a></div></div></main>";
            render_page_footer();
            exit;
        }
        
        // Verifica se CNPJ já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE cnpj = :cnpj");
        $stmt->execute([':cnpj' => $cnpj]);
        if ($stmt->fetch()) {
            render_page_header('Erro no Cadastro');
            echo "<main class='main-container'><div class='card card-error'><img src='uploads/flute_logo.png' alt='Flute Incensos' class='logo'><h2>CNPJ já cadastrado</h2><p>O CNPJ informado já está em uso. Cada empresa pode ter apenas uma conta.</p><div><a href='login.html' class='btn'>Fazer Login</a><a href='cadastro.html' class='btn btn-secondary' style='margin-left:10px;'>Voltar</a></div></div></main>";
            render_page_footer();
            exit;
        }

        $nome = trim($_POST['nome']);
        $nome_empresa = trim($_POST['nome_empresa']);
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
        $cidade = trim($_POST['cidade']);
        $estado = $_POST['estado'];
        $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $query = "INSERT INTO usuarios (nome, nome_empresa, cnpj, email, telefone, cidade, estado, senha_hash, data_cadastro) VALUES (:nome, :nome_empresa, :cnpj, :email, :telefone, :cidade, :estado, :senha_hash, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([':nome' => $nome, ':nome_empresa' => $nome_empresa, ':cnpj' => $cnpj, ':email' => $email, ':telefone' => $telefone, ':cidade' => $cidade, ':estado' => $estado, ':senha_hash' => $senha_hash]);

        try {
            $emailService = new EmailService();
            $emailService->enviarBoasVindas($email, $nome);
        } catch (Exception $e) {
            error_log("Erro ao enviar email de boas-vindas: " . $e->getMessage());
        }

        render_page_header('Cadastro Realizado');
        $cnpj_formatado = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        echo "<main class='main-container'><div class='card card-success'><div class='icon icon-success'>✓</div><h1>Cadastro Realizado com Sucesso!</h1><p>Olá <span class='highlight'>{$nome}</span>, seu cadastro foi confirmado!</p><div class='details'><p><strong>Empresa:</strong> {$nome_empresa}</p><p><strong>CNPJ:</strong> {$cnpj_formatado}</p><p><strong>E-mail:</strong> {$email}</p><p><strong>Telefone:</strong> " . formatarTelefone($telefone) . "</p><p><strong>Localização:</strong> {$cidade} / {$estado}</p></div><p>Enviamos um e-mail de boas-vindas para você. Já pode fazer login na plataforma.</p><a href='login.html' class='btn'>Fazer Login</a></div></main>";
        render_page_footer();

    } catch (PDOException $e) {
        error_log("Erro no cadastro: " . $e->getMessage());
        render_page_header('Erro no Cadastro');
        echo "<main class='main-container'><div class='card card-error'><h1>Ops! Algo deu errado.</h1><p>Não foi possível concluir seu cadastro. Por favor, tente novamente mais tarde.</p><a href='javascript:history.back()' class='btn btn-secondary'>Voltar</a></div></main>";
        render_page_footer();
    }
}
?>