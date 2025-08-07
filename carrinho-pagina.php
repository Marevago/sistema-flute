<?php
session_start();
require_once 'config/database.php';
require_once 'carrinho.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Inicia conexão com o banco
$database = new Database();
$conn = $database->getConnection();

// Busca os itens do carrinho
$query = "
    SELECT 
        c.id as carrinho_id,
        c.quantidade,
        c.variacao,
        p.id as produto_id,
        p.nome,
        p.preco,
        (p.preco * c.quantidade) as subtotal
    FROM carrinhos c
    JOIN produtos p ON c.produto_id = p.id
    WHERE c.usuario_id = ?
    ORDER BY p.nome
";

$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula o total
$total = 0;
foreach ($itens as $item) {
    $total += $item['subtotal'];
}

$valor_minimo = 300.00;

// Processa ações do carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    // Processa atualização de quantidade
    if (isset($dados['carrinho_id']) && isset($dados['mudanca'])) {
        try {
            $carrinho_id = $dados['carrinho_id'];
            $mudanca = $dados['mudanca'];
            
            // Busca quantidade atual
            $stmt = $conn->prepare("
                SELECT quantidade 
                FROM carrinhos 
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([$carrinho_id, $_SESSION['user_id']]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $nova_quantidade = $item['quantidade'] + $mudanca;
                if ($nova_quantidade > 0) {
                    // Atualiza a quantidade
                    $stmt = $conn->prepare("
                        UPDATE carrinhos 
                        SET quantidade = ? 
                        WHERE id = ? AND usuario_id = ?
                    ");
                    $stmt->execute([$nova_quantidade, $carrinho_id, $_SESSION['user_id']]);
                    
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => true]);
                    exit;
                }
            }
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['erro' => 'Erro ao atualizar quantidade']);
            exit;
        }
    }
    
    // Processa remoção de item
    if (isset($dados['carrinho_id']) && !isset($dados['mudanca'])) {
        try {
            $carrinho_id = $dados['carrinho_id'];
            
            $stmt = $conn->prepare("
                DELETE FROM carrinhos 
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([$carrinho_id, $_SESSION['user_id']]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true]);
            exit;
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['erro' => 'Erro ao remover item']);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <title>Seu Carrinho - Loja de Atacado</title>
    <style>
        /* Estilos base que mantêm consistência com outras páginas */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
            background-image: url('uploads/background04.png');
        }

        /* Header consistente com outras páginas */
        .header {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 30px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'EB Garamond', serif;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 50px;
            height: 50px;
        }

        .site-title {
            font-size: 24px;
            color: #333;
        }

        .main-container {
            margin-top: 80px;
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Estilos específicos do carrinho */
        .cart-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .cart-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        /* Área do resumo do pedido */
        .order-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .total-row {
            font-size: 20px;
            font-weight: bold;
            color: #2ecc71;
            border-bottom: none;
            padding-top: 20px;
        }

        /* Botões consistentes com outras páginas */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
            font-size: 16px;
        }

        .btn-primary {
            background-color:rgb(204, 186, 46);
            color: white;
        }

        .btn-primary:hover {
            background-color:rgb(174, 102, 39);
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            padding: 5px 10px;
            border: none;
            background-color: #eee;
            cursor: pointer;
            border-radius: 4px;
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empty-cart p {
            margin-bottom: 20px;
            color: #666;
        }

        .value-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            text-align: center;
        }

        /* Footer consistente */
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo-area">
            <img src="uploads/flute_logo.png" alt="Logo da Loja" class="logo">
            <h1 class="site-title">Flute Incensos</h1>
        </div>
        <nav class="main-nav">
            <a href="produtos.php" class="btn btn-secondary">Voltar para Produtos</a>
        </nav>
    </header>

    <!-- Conteúdo Principal -->
    <div class="main-container">
        <h2>Seu Carrinho</h2>

        <?php if (empty($itens)): ?>
            <div class="empty-cart">
                <p>Seu carrinho está vazio. Que tal adicionar alguns produtos?</p>
                <a href="produtos.php" class="btn btn-primary">Ver Produtos</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço Unitário</th>
                        <th>Quantidade</th>
                        <th>Subtotal</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                        <td>
                            <?php 
                                // Se houver uma variação, mostra apenas ela
                                // Caso contrário, mostra o nome do produto
                                if (!empty($item['variacao'])) {
                                    echo 'Incenso ' . htmlspecialchars($item['variacao']);
                                } else {
                                    echo htmlspecialchars($item['nome']);
                                }
                            ?>
                        </td>                            <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                            <td>
                                <div class="quantity-control">
                                    <button class="quantity-btn" 
                                            onclick="atualizarQuantidade(<?php echo $item['carrinho_id']; ?>, -1)">-</button>
                                    <span><?php echo $item['quantidade']; ?></span>
                                    <button class="quantity-btn" 
                                            onclick="atualizarQuantidade(<?php echo $item['carrinho_id']; ?>, 1)">+</button>
                                </div>
                            </td>
                            <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                            <td>
                                <button onclick="removerItem(<?php echo $item['carrinho_id']; ?>)" 
                                        class="btn btn-secondary">Remover</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="order-summary">
                <h3>Resumo do Pedido</h3>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                </div>
                <div class="summary-row total-row">
                    <span>Total:</span>
                    <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                </div>

                <?php if ($total < $valor_minimo): ?>
                    <div class="value-warning">
                        <p>O pedido mínimo é de R$ <?php echo number_format($valor_minimo, 2, ',', '.'); ?></p>
                        <p>Faltam R$ <?php echo number_format($valor_minimo - $total, 2, ',', '.'); ?> 
                           para atingir o valor mínimo</p>
                    </div>
                <?php else: ?>
                    <button onclick="finalizarPedido()" class="btn btn-primary">
                        Finalizar Pedido
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Sua Loja de Atacado. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Funções JavaScript permanecem as mesmas
        async function atualizarQuantidade(carrinhoId, mudanca) {
            try {
                const response = await fetch('atualizar_carrinho.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        carrinho_id: carrinhoId,
                        mudanca: mudanca
                    })
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Erro ao atualizar quantidade:', error);
            }
        }

        async function removerItem(carrinhoId) {
            if (confirm('Tem certeza que deseja remover este item?')) {
                try {
                    const response = await fetch('remover_do_carrinho.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            carrinho_id: carrinhoId
                        })
                    });

                    if (response.ok) {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Erro ao remover item:', error);
                }
            }
        }

        async function finalizarPedido() {
            try {
                const response = await fetch('finalizar_pedido.php', {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.sucesso) {
                    alert('Pedido finalizado com sucesso!');
                    window.location.href = 'pedido_confirmado.php';
                } else {
                    alert(data.erro || 'Erro ao finalizar pedido');
                }
            } catch (error) {
                console.error('Erro ao finalizar pedido:', error);
            }

            
        }
    </script>
</body>
</html>