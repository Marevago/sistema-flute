<?php
session_start();
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'config/worker.php';

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

    // Após responder, dispara um worker assíncrono para envio do e-mail (não bloqueia o cliente)
    try {
        // URL do worker em produção (hardcoded para evitar variações de path)
        $url = 'https://incensosflute.com.br/email_pedido_worker.php?pedido_id=' . urlencode((string)$pedido_id) . '&token=' . urlencode(FLUTE_WORKER_TOKEN);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        // Opcional: em alguns hosts, desabilitar a verificação SSL localmente (URL é https)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);
        // Logar informações do disparo do worker para diagnóstico
        error_log('[EmailPedido] Worker URL: ' . $url . ' | HTTP: ' . $http . ' | cURL error: ' . ($cerr ?: 'none'));
    } catch (Throwable $e) {
        error_log('[EmailPedido] Falha ao disparar worker do pedido #' . $pedido_id . ': ' . $e->getMessage());
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