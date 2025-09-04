<?php
// Worker para envio assíncrono de e-mail de pedido
// Endpoint: email_pedido_worker.php?pedido_id=123&token=...

header('Content-Type: application/json');

require_once __DIR__ . '/config/worker.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/email.php';

try {
    error_log('[Email Worker] start pedido_id=' . ($_GET['pedido_id'] ?? ''));    
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    $pedido_id = isset($_GET['pedido_id']) ? (int) $_GET['pedido_id'] : 0;
    if ($token !== FLUTE_WORKER_TOKEN) {
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'erro' => 'Token inválido']);
        exit;
    }
    if ($pedido_id <= 0) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'pedido_id inválido']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Buscar dados do pedido, usuário e itens
    $stmt = $conn->prepare('SELECT p.id, p.usuario_id, p.valor_total, u.nome, u.email, u.cpf, u.telefone
                            FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
                            WHERE p.id = ?');
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode(['sucesso' => false, 'erro' => 'Pedido não encontrado']);
        exit;
    }

    $stmt = $conn->prepare('SELECT ip.quantidade, ip.preco_unitario, pr.nome as produto_nome
                            FROM itens_pedido ip JOIN produtos pr ON pr.id = ip.produto_id
                            WHERE ip.pedido_id = ?');
    $stmt->execute([$pedido_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monta corpo do email
    $corpo_email = "
        <h2>Novo Pedido Recebido - #{$pedido_id}</h2>
        <h3>Dados do Cliente:</h3>
        <p>Nome: {$pedido['nome']}</p>
        <p>Email: {$pedido['email']}</p>
        <p>CPF: {$pedido['cpf']}</p>
        <p>Telefone: {$pedido['telefone']}</p>
        <h3>Itens do Pedido:</h3>
        <table border='1' style='border-collapse: collapse; width: 100%;'>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unit.</th>
                <th>Subtotal</th>
            </tr>
    ";
    foreach ($itens as $item) {
        $q = (int) $item['quantidade'];
        $pu = (float) $item['preco_unitario'];
        $subtotal = $q * $pu;
        $corpo_email .= "
            <tr>
                <td>" . htmlspecialchars($item['produto_nome']) . "</td>
                <td>{$q}</td>
                <td>R$ " . number_format($pu, 2, ',', '.') . "</td>
                <td>R$ " . number_format($subtotal, 2, ',', '.') . "</td>
            </tr>
        ";
    }
    $corpo_email .= "
        </table>
        <h3>Valor Total: R$ " . number_format((float)$pedido['valor_total'], 2, ',', '.') . "</h3>
    ";

    $email = new EmailService();
    $email->enviarPedidoAdmin($corpo_email);
    error_log('[Email Worker] enviado com sucesso pedido_id=' . $pedido_id);
    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    error_log('[Email Worker] erro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Falha no worker']);
}
