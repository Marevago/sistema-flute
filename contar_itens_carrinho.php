<?php
// contar_itens_carrinho.php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['quantidade' => 0]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("
    SELECT SUM(quantidade) as total 
    FROM carrinhos 
    WHERE usuario_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['quantidade' => (int)$resultado['total'] ?: 0]);
?>