<?php
session_start();
require_once 'config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Usuário não está logado']);
    exit;
}

// Recebe e decodifica os dados JSON
$dados = json_decode(file_get_contents('php://input'), true);

if (!isset($dados['carrinho_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'ID do item não fornecido']);
    exit;
}

$carrinho_id = $dados['carrinho_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Remove o item, verificando se pertence ao usuário atual
    $stmt = $conn->prepare("
        DELETE FROM carrinhos 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$carrinho_id, $_SESSION['user_id']]);
    
    // Verifica se alguma linha foi afetada
    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Item não encontrado']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Erro ao remover item do carrinho']);
    error_log("Erro ao remover item do carrinho: " . $e->getMessage());
    exit;
}
?>