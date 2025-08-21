<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Suporte a listagem via GET: /favoritos.php?action=list
$method = $_SERVER['REQUEST_METHOD'];
$action = '';
$produtoId = 0;

if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
} else if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $input['action'] ?? '';
    $produtoId = isset($input['produto_id']) ? (int)$input['produto_id'] : 0;
}

if (!isset($_SESSION['user_id'])) {
    // Para list, retornar vazio se não logado, para não quebrar UI
    if ($action === 'list') { echo json_encode(['sucesso' => true, 'ids' => []]); exit; }
    echo json_encode(['erro' => 'Usuário não está logado']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Garante a existência da tabela
    $conn->exec("CREATE TABLE IF NOT EXISTS favoritos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        produto_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_prod (usuario_id, produto_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    if ($action === 'list') {
        $stmt = $conn->prepare("SELECT produto_id FROM favoritos WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['sucesso' => true, 'ids' => array_map('intval', $ids)]);
        exit;
    }

    if ($method !== 'POST' || !in_array($action, ['add','remove'], true) || (!$produtoId)) {
        echo json_encode(['erro' => 'Parâmetros inválidos']);
        exit;
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT IGNORE INTO favoritos (usuario_id, produto_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $produtoId]);
        echo json_encode(['sucesso' => true]);
    } else { // remove
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND produto_id = ?");
        $stmt->execute([$_SESSION['user_id'], $produtoId]);
        echo json_encode(['sucesso' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro ao processar favorito: ' . $e->getMessage()]);
}
