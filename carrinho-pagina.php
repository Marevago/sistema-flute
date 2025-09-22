<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'carrinho.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Inicia conexão com o banco
$database = new Database();
$conn = $database->getConnection();

// Busca os itens do carrinho (inclui categoria para formatar título de exibição)
$query = "
    SELECT 
        c.id as carrinho_id,
        c.quantidade,
        c.variacao,
        p.id as produto_id,
        p.nome,
        p.categoria,
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

$valor_minimo = 600.00;

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
    <title>Seu Carrinho - Flute Incensos</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.2">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <?php include __DIR__ . '/config/analytics.php'; ?>

    <style>
        /* Toast e animação do carrinho para consistência com produtos.php */
        .cart-mini.cart-bump { animation: cart-bump 500ms ease; }
        @keyframes cart-bump { 0%{transform:scale(1)} 30%{transform:scale(1.15)} 60%{transform:scale(.95)} 100%{transform:scale(1)} }
        .cart-message { position:fixed; top:20px; right:20px; z-index:9999; background:#333; color:#fff; padding:12px 16px; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,.2); opacity:0; transition:opacity .3s ease; }
        .cart-message.success{ background:#2e7d32 }
        .cart-message.error{ background:#c62828 }
        /* Estilos base que mantêm consistência com outras páginas */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .main-container { max-width: 1200px; margin: 0 auto; padding: 20px; overflow-x: hidden; }

        /* Responsividade da tabela do carrinho */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .cart-table { width: 100%; min-width: 640px; }
        .cart-table th, .cart-table td { word-break: break-word; }
        .quantity-control { flex-wrap: nowrap; }

        @media (max-width: 768px) {
            /* Stacked layout: remove need for horizontal scroll */
            .table-responsive { overflow-x: visible; }
            .cart-table { min-width: 0; border-collapse: separate; border-spacing: 0 10px; }
            .cart-table thead { display: none; }
            .cart-table tbody tr { display: block; background: #fff; padding: 12px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); margin-bottom: 12px; }
            .cart-table tbody tr td { display: grid; grid-template-columns: minmax(90px, 40%) 1fr; align-items: center; padding: 8px 0; border-bottom: none; }
            .cart-table tbody tr td::before { content: attr(data-label); font-weight: 600; color: #555; padding-right: 10px; }
            .cart-table tbody tr td:last-child { padding-bottom: 0; }
            .btn { font-size: 14px; padding: 8px 12px; }
            .btn { font-size: 14px; padding: 8px 12px; }
            .quantity-btn { padding: 4px 8px; }
            .quantity-control { justify-content: flex-start; }
            .cart-table tbody tr td[data-label="Ações"] .btn { width: 100%; }
            .cart-table tbody tr td[data-label="Quantidade"] .quantity-control { gap: 8px; }
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

        .summary-items { margin: 10px 0 6px; }
        .summary-items h4 { margin: 0 0 8px; font-size: 15px; }
        .summary-list { list-style: disc; padding-left: 18px; margin: 0 0 6px; color:#444; }
        .summary-list li { margin-bottom: 4px; }
        .summary-count { font-size: 13px; color:#666; }

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

        /* Barra de progresso do pedido mínimo */
        .min-progress { margin-top: 14px; }
        .min-progress .label { display:flex; justify-content: space-between; font-size: 13px; color:#555; margin-bottom: 6px; }
        .min-progress .bar { height: 10px; background: #eee; border-radius: 999px; overflow: hidden; }
        .min-progress .fill { height: 100%; width: 0%; background: #4caf50; border-radius: 999px; transition: width .4s ease; }
        .min-ok { color:#2e7d32; font-size: 13px; margin-top: 8px; display:flex; align-items:center; gap:6px; }
        .min-warn { background-color: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; margin-top: 12px; text-align: center; }

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

        .btn-primary[disabled], .btn-primary[aria-disabled="true"] {
            opacity: .6;
            cursor: not-allowed;
        }

        .btn-outline { background: transparent; border: 1px solid #ddd; color:#333; }
        .btn-outline:hover { background:#f7f7f7; }

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

        /* Footer simples (mantemos até integrar com global) */
        .footer { background-color: #333; color:#fff; text-align:center; padding:20px; margin-top:40px; }
        .checkout-note { font-size: 13px; color:#666; margin-top: 8px; }
    </style>
    <script>
        // Script de cabeçalho e menu (replicado de produtos.php)
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const mainNav = document.getElementById('main-nav');
            const backdrop = document.getElementById('backdrop');

            if (menuToggle && mainNav && backdrop) {
                const openMenu = () => {
                    mainNav.classList.add('open');
                    backdrop.classList.add('open');
                    menuToggle.setAttribute('aria-expanded', 'true');
                    document.body.style.overflow = 'hidden';
                };
                const closeMenu = () => {
                    mainNav.classList.remove('open');
                    backdrop.classList.remove('open');
                    menuToggle.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                    document.querySelectorAll('.main-nav .dropdown.open').forEach(d => d.classList.remove('open'));
                };
                menuToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    mainNav.classList.contains('open') ? closeMenu() : openMenu();
                });
                backdrop.addEventListener('click', closeMenu);
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && mainNav.classList.contains('open')) closeMenu(); });
            }
            const dropdownsInNav = document.querySelectorAll('.main-nav .nav-item.dropdown');
            dropdownsInNav.forEach(dropdown => {
                const link = dropdown.querySelector('a');
                link.addEventListener('click', function(e) {
                    if (window.matchMedia('(max-width: 768px)').matches && document.getElementById('main-nav').classList.contains('open')) {
                        e.preventDefault();
                        dropdown.classList.toggle('open');
                    }
                });
            });
            atualizarContadorCarrinho();
        });

        // Mantém o ícone do carrinho consistente entre abas/páginas
        window.addEventListener('storage', (e) => {
            if (e.key === 'cart_updated') {
                atualizarContadorCarrinho();
            }
        });
        window.addEventListener('pageshow', atualizarContadorCarrinho);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) atualizarContadorCarrinho(); });

        function atualizarContadorCarrinho() {
            fetch('contar_itens_carrinho.php')
                .then(r => r.json())
                .then(data => {
                    const contador = document.querySelector('.cart-count');
                    if (contador) {
                        contador.textContent = data.quantidade || '0';
                        contador.style.display = (data.quantidade && Number(data.quantidade) > 0) ? 'flex' : 'none';
                    }
                })
                .catch(err => console.error('Erro ao atualizar contador:', err));
        }

        function mostrarMensagem(mensagem, tipo) {
            const msg = document.createElement('div');
            msg.className = `cart-message ${tipo}`;
            msg.textContent = mensagem;
            document.body.appendChild(msg);
            setTimeout(() => msg.style.opacity = '1', 100);
            setTimeout(() => { msg.style.opacity = '0'; setTimeout(() => msg.remove(), 300); }, 3000);
        }

        function pulseCartIcon() {
            const cart = document.querySelector('.cart-mini');
            if (!cart) return;
            cart.classList.remove('cart-bump');
            void cart.offsetWidth;
            cart.classList.add('cart-bump');
            setTimeout(() => cart.classList.remove('cart-bump'), 500);
        }
    </script>
</head>
<body>
    <!-- Cabeçalho consistente com produtos.php -->
    <header class="site-header">
        <div class="header-top-bar">
            <div class="container">
                <p>Seja um distribuidor Flute! Entre em contato conosco.</p>
                <a href="central-atendimento.php" class="action-link">Central de Atendimento</a>
            </div>
        </div>
        <div class="header-main">
            <div class="container">
                <button class="menu-toggle" id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                    <span></span>
                </button>

                <div class="logo-area">
                    <a href="index.php">
                        <img src="uploads/flute_logo.png" alt="Logo Flute Incensos" class="logo">
                    </a>
                </div>

                <div class="search-bar-area">
                    <form class="search-bar" action="buscar.php" method="get">
                        <input type="text" name="q" placeholder="Digite o que você procura..." aria-label="Buscar produtos">
                        <button type="submit" aria-label="Buscar" class="search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </button>
                    </form>
                </div>

                <div class="header-actions-area">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="minha-conta.php" class="action-icon user-account" title="Minha Conta">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                        </a>
                        <a href="logout.php" class="action-link">Sair</a>
                    <?php else: ?>
                        <a href="login.html" class="action-icon user-account" title="Entrar ou Cadastrar">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Entrar</span>
                        </a>
                    <?php endif; ?>

                    <a href="carrinho-pagina.php" class="action-icon cart-mini" title="Carrinho">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span class="cart-count" id="cart-count">0</span>
                    </a>
                </div>
            </div>
        </div>
        <nav class="main-nav" id="main-nav">
            <div class="container">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link">Início</a></li>
                    <li class="nav-item dropdown">
                        <a href="produtos.php" class="nav-link">Produtos</a>
                        <div class="dropdown-content">
                            <a href="regular-square.php?cat=regular-square">Regular Square</a>
                            <a href="masala-square.php?cat=masala-square">Masala Square</a>
                            <a href="xamanico-tube.php?cat=incenso-xamanico">Incenso Xamânico Tube</a>
                            <a href="cycle-brand-regular.php?cat=cycle-brand-regular">Cycle Brand Regular</a>
                            <a href="long-square.php?cat=long-square">Long Square</a>
                            <a href="cycle-brand-rectangle.php?cat=cycle-brand-rectangle">Cycle Brand Rectangle</a>
                            <a href="masala-small.php?cat=masala-small">Masala Small Packet</a>
                            <a href="clove-brand.php?cat=clove-brand">Clove Brand</a>
                            <a href="produtos.php">Ver Todos</a>
                        </div>
                    </li>
                    <li><a href="sobre.php" class="nav-link">Sobre Nós</a></li>
                    <li><a href="contato.php" class="nav-link">Contato</a></li>
                </ul>
            </div>
        </nav>
        <div class="backdrop" id="backdrop"></div>
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
            <div class="table-responsive">
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
                        <td data-label="Produto">
                                <?php 
                                $nomeBase = !empty($item['variacao']) ? $item['variacao'] : $item['nome'];
                                $display = formatarTituloProduto($item['categoria'] ?? '', $nomeBase);
                                echo htmlspecialchars($display);
                            ?>
                        </td>
                            <td data-label="Preço Unitário">R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                            <td data-label="Quantidade">
                                <div class="quantity-control">
                                    <button class="quantity-btn" 
                                            onclick="atualizarQuantidade(<?php echo $item['carrinho_id']; ?>, -1)">-</button>
                                    <span><?php echo $item['quantidade']; ?></span>
                                    <button class="quantity-btn" 
                                            onclick="atualizarQuantidade(<?php echo $item['carrinho_id']; ?>, 1)">+</button>
                                </div>
                            </td>
                            <td data-label="Subtotal">R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                            <td data-label="Ações">
                                <button onclick="removerItem(<?php echo $item['carrinho_id']; ?>)" 
                                        class="btn btn-secondary">Remover</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <div class="order-summary">
                <h3>Resumo do Pedido</h3>
                <?php 
                    // Monta resumo textual dos itens com quantidade
                    $linhas = [];
                    $total_unidades = 0;
                    foreach ($itens as $it) {
                        $nomeBase = !empty($it['variacao']) ? $it['variacao'] : $it['nome'];
                        $nome_item = formatarTituloProduto($it['categoria'] ?? '', $nomeBase);
                        $qtd = (int) $it['quantidade'];
                        $total_unidades += $qtd;
                        $linhas[] = htmlspecialchars($nome_item) . ' x ' . $qtd;
                    }
                ?>
                <?php if (!empty($linhas)): ?>
                    <div class="summary-items">
                        <h4>Itens do pedido</h4>
                        <ul class="summary-list">
                            <?php foreach ($linhas as $linha): ?>
                                <li><?php echo $linha; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="summary-count">Total de unidades: <strong><?php echo $total_unidades; ?></strong></div>
                    </div>
                <?php endif; ?>
                <?php 
                    $faltam = max(0, $valor_minimo - $total);
                    $percent = min(100, (int) floor(($total > 0 ? ($total / $valor_minimo) : 0) * 100));
                ?>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                </div>
                <div class="summary-row total-row">
                    <span>Total:</span>
                    <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                </div>
                <div class="min-progress" aria-live="polite">
                    <div class="label">
                        <span>Progresso para o mínimo (R$ <?php echo number_format($valor_minimo, 2, ',', '.'); ?>)</span>
                        <span><?php echo $percent; ?>%</span>
                    </div>
                    <div class="bar">
                        <div class="fill" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>

                <?php if ($total < $valor_minimo): ?>
                    <div class="min-warn">
                        <p>Faltam <strong>R$ <?php echo number_format($faltam, 2, ',', '.'); ?></strong> para atingir o valor mínimo.</p>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap: wrap;">
                        <a href="produtos.php" class="btn btn-outline">Adicionar mais produtos</a>
                        <button class="btn btn-primary" aria-disabled="true" title="Atinga o valor mínimo para finalizar" disabled>Finalizar Pedido</button>
                    </div>
                    <p class="checkout-note">Você não será cobrado agora. Após a finalização do pedido, entraremos em contato para combinar o frete e a forma de pagamento.</p>
                <?php else: ?>
                    <div class="min-ok">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        Pedido mínimo atingido. Você pode finalizar seu pedido.
                    </div>
                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap: wrap;">
                        <a href="produtos.php" class="btn btn-outline">Continuar comprando</a>
                        <button onclick="finalizarPedido()" class="btn btn-primary">Finalizar Pedido</button>
                    </div>
                    <p class="checkout-note">Você não será cobrado agora. Após a finalização do pedido, entraremos em contato para combinar o frete e a forma de pagamento.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

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
                    try { localStorage.setItem('cart_updated', Date.now().toString()); } catch (e) {}
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
                        try { localStorage.setItem('cart_updated', Date.now().toString()); } catch (e) {}
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
                    try { localStorage.setItem('cart_updated', Date.now().toString()); } catch (e) {}
                    // Pass order data to confirmation page
                    window.location.href = `pedido_confirmado.php?pedido_id=${data.pedido_id}&valor_total=${encodeURIComponent(valorTotal)}`;
                } else {
                    alert(data.erro || 'Erro ao finalizar pedido');
                }
            } catch (error) {
                console.error('Erro ao finalizar pedido:', error);
                alert('Erro ao finalizar pedido: problema de rede ou servidor indisponível. Tente novamente em instantes.');
            }

            
        }
    </script>
</body>
</html>