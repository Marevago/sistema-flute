<?php
session_start();

// Inicializa o contador
$quantidade = 0;

// Verifica se o carrinho existe na sessão
if (isset($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    // Soma as quantidades de todos os itens no carrinho
    foreach ($_SESSION['carrinho'] as $item) {
        if (isset($item['quantidade'])) {
            $quantidade += (int)$item['quantidade'];
        }
    }
}

// Retorna a quantidade em formato JSON
header('Content-Type: application/json');
echo json_encode(['quantidade' => $quantidade]);
?>
