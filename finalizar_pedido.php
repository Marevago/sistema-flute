<?php
session_start();
require_once 'config/database.php';
require_once 'config/email.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não está logado']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Inicia a transação
    $conn->beginTransaction();
    
    // Busca informações do usuário
    $stmt = $conn->prepare("
        SELECT nome, email, cpf, telefone
        FROM usuarios
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Busca itens do carrinho
    $stmt = $conn->prepare("
        SELECT 
            c.quantidade,
            p.id as produto_id,
            p.nome as produto_nome,
            p.preco,
            (p.preco * c.quantidade) as subtotal
        FROM carrinhos c
        JOIN produtos p ON c.produto_id = p.id
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Impede finalizar com carrinho vazio
    if (!$itens || count($itens) === 0) {
        $conn->rollBack();
        echo json_encode(['sucesso' => false, 'erro' => 'Seu carrinho está vazio.']);
        exit;
    }
    
    // Calcula valor total
    $valor_total = 0;
    foreach ($itens as $item) {
        $valor_total += $item['subtotal'];
    }
    
    // Cria o pedido
    $stmt = $conn->prepare("
        INSERT INTO pedidos (usuario_id, valor_total)
        VALUES (?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $valor_total]);
    $pedido_id = $conn->lastInsertId();
    
    // Insere os itens do pedido
    $stmt = $conn->prepare("
        INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($itens as $item) {
        $stmt->execute([
            $pedido_id,
            $item['produto_id'],
            $item['quantidade'],
            $item['preco']
        ]);
    }
    
    // Limpa o carrinho
    $stmt = $conn->prepare("DELETE FROM carrinhos WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Confirma todas as alterações no banco ANTES de enviar e-mail
    $conn->commit();

    // Responde ao cliente imediatamente para evitar esperar o SMTP
    // (mantém a experiência de checkout instantânea)
    ignore_user_abort(true);
    $response = [
        'sucesso' => true,
        'pedido_id' => $pedido_id
    ];
    echo json_encode($response);
    // Força o flush da resposta se possível
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        @ob_end_flush();
        @flush();
    }

    // Após responder, tenta enviar o email em background (não impacta o cliente)
    try {
        $emailService = new EmailService();
        // Monta o corpo do email com os detalhes do pedido
        $corpo_email = "
            <h2>Novo Pedido Recebido - #{$pedido_id}</h2>
            
            <h3>Dados do Cliente:</h3>
            <p>Nome: {$usuario['nome']}</p>
            <p>Email: {$usuario['email']}</p>
            <p>CPF: {$usuario['cpf']}</p>
            <p>Telefone: {$usuario['telefone']}</p>
            
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
            $corpo_email .= "
                <tr>
                    <td>{$item['produto_nome']}</td>
                    <td>{$item['quantidade']}</td>
                    <td>R$ " . number_format($item['preco'], 2, ',', '.') . "</td>
                    <td>R$ " . number_format($item['subtotal'], 2, ',', '.') . "</td>
                </tr>
            ";
        }
        $corpo_email .= "
            </table>
            <h3>Valor Total: R$ " . number_format($valor_total, 2, ',', '.') . "</h3>
        ";
        // Evita que o processo fique muito tempo preso aqui
        @set_time_limit(15);
        $emailService->enviarPedidoAdmin($corpo_email);
    } catch (Exception $e) {
        // Log silencioso no error_log sem impactar o usuário
        error_log('[EmailPedido] Falha ao enviar email do pedido #' . $pedido_id . ': ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Se algo der errado ANTES de commitar, tenta desfazer
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    // Sempre retorna JSON válido
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao finalizar pedido: ' . $e->getMessage()]);
}
?>