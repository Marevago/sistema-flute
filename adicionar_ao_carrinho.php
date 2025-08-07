<?php
session_start();
require_once 'config/database.php';
require_once 'carrinho.php';

// Get the JSON data from the request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['erro' => 'Usuário não está logado']);
    exit;
}

if (!$data) {
    echo json_encode(['erro' => 'Dados inválidos']);
    exit;
}

$produto_id = $data['produto_id'];
$variacao = $data['variacao'];
$quantidade = $data['quantidade'];

// Validate the data
if (!$produto_id || !$variacao || !$quantidade) {
    echo json_encode(['erro' => 'Dados incompletos']);
    exit;
}

try {
    $carrinho = new CarrinhoHandler();
    $resultado = $carrinho->adicionarProduto($produto_id, $quantidade, $variacao);
    
    if ($resultado) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['erro' => 'Erro ao adicionar ao carrinho']);
    }
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}