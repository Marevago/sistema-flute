<?php
// adicionar_ao_carrinho.php
header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/carrinho.php';

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// Lê JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Payload inválido']);
    exit;
}

$produto_id = isset($data['produto_id']) ? (int)$data['produto_id'] : 0;
$quantidade = isset($data['quantidade']) ? (int)$data['quantidade'] : 1;
$variacao   = isset($data['variacao']) ? (string)$data['variacao'] : '';

if ($produto_id <= 0 || $quantidade <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados insuficientes']);
    exit;
}

try {
    $handler = new CarrinhoHandler();
    $resultado = $handler->adicionarProduto($produto_id, $quantidade, $variacao);

    if (isset($resultado['erro'])) {
        // Se usuário não logado, retorna 401 para o front poder reagir se desejar
        if ($resultado['erro'] === 'Usuário precisa estar logado') {
            http_response_code(401);
        } else {
            http_response_code(400);
        }
        echo json_encode(['sucesso' => false, 'erro' => $resultado['erro']]);
        exit;
    }

    echo json_encode(['sucesso' => true, 'mensagem' => 'Produto adicionado ao carrinho']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno: ' . $e->getMessage()]);
}
