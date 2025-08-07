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

if (!isset($dados['carrinho_id']) || !isset($dados['mudanca'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Dados inválidos']);
    exit;
}

$carrinho_id = $dados['carrinho_id'];
$mudanca = $dados['mudanca'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Primeiro, verificamos se o item existe e pertence ao usuário
    $stmt = $conn->prepare("
        SELECT quantidade 
        FROM carrinhos 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$carrinho_id, $_SESSION['user_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Item não encontrado']);
        exit;
    }
    
    // Calcula a nova quantidade
    $nova_quantidade = $item['quantidade'] + $mudanca;
    
    // Verifica se a nova quantidade é válida
    if ($nova_quantidade <= 0) {
        // Se a quantidade for 0 ou menor, removemos o item
        $stmt = $conn->prepare("
            DELETE FROM carrinhos 
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([$carrinho_id, $_SESSION['user_id']]);
        
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'acao' => 'removido']);
        exit;
    }
    
    // Atualiza a quantidade
    $stmt = $conn->prepare("
        UPDATE carrinhos 
        SET quantidade = ? 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$nova_quantidade, $carrinho_id, $_SESSION['user_id']]);
    
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => true, 'quantidade' => $nova_quantidade]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Erro ao atualizar carrinho']);
    error_log("Erro ao atualizar carrinho: " . $e->getMessage());
    exit;
}
?>