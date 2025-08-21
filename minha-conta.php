<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Redireciona se não estiver logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$userId = $_SESSION['user_id'];

// Garante a existência da tabela de favoritos
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS favoritos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        produto_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_prod (usuario_id, produto_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    // silencioso: se falhar, apenas não mostra favoritos
}

// Busca pedidos do usuário (sem depender de coluna created_at)
$pedidos = [];
try {
    $stmt = $conn->prepare("SELECT id, valor_total FROM pedidos WHERE usuario_id = ? ORDER BY id DESC");
    $stmt->execute([$userId]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pedidos = [];
}

// Busca itens por pedido
$itensPorPedido = [];
if ($pedidos) {
    $ids = array_column($pedidos, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT ip.pedido_id, ip.produto_id, ip.quantidade, ip.preco_unitario, p.nome, p.categoria
            FROM itens_pedido ip
            JOIN produtos p ON p.id = ip.produto_id
            WHERE ip.pedido_id IN ($in)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($ids);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $itensPorPedido[$row['pedido_id']][] = $row;
    }
}

// Busca favoritos do usuário
$favoritos = [];
try {
    $stmt = $conn->prepare("SELECT f.produto_id, p.nome, p.preco, p.categoria FROM favoritos f JOIN produtos p ON p.id = f.produto_id WHERE f.usuario_id = ? ORDER BY f.created_at DESC");
    $stmt->execute([$userId]);
    $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $favoritos = [];
}

function formatCurrencyBR($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Flute Incensos</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.2">
    <style>
        .account-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .account-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .section { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .orders-list { display: grid; grid-template-columns: 1fr; gap: 16px; }
        .order-card { border: 1px solid #eee; border-radius: 8px; padding: 12px; }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .order-items { width: 100%; border-collapse: collapse; }
        .order-items th, .order-items td { padding: 8px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .favorites-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .favorite-card { border: 1px solid #eee; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
        .favorite-actions { display: flex; gap: 8px; align-items: center; }
        .btn { background: #2c3e50; color: #fff; padding: 8px 12px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; }
        .btn.secondary { background: #888; }
        .btn.danger { background: #c62828; }
        .cart-message { position: fixed; top: 20px; right: 20px; z-index: 9999; background: #333; color: #fff; padding: 12px 16px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); opacity: 0; transition: opacity 0.3s ease; }
        .cart-message.success { background: #2e7d32; }
        .cart-message.error { background: #c62828; }
        .header-actions-area .cart-count { display: none; }
        @media (min-width: 768px) { .account-header { margin-top: 8px; } }
        /* Responsivo: tabela de pedidos em layout empilhado no mobile */
        @media (max-width: 768px) {
            .order-items thead { display: none; }
            .order-items, .order-items tbody, .order-items tr, .order-items td { display: block; width: 100%; }
            .order-items tr { background: #fafafa; border: 1px solid #eee; border-radius: 8px; padding: 8px; margin-bottom: 12px; }
            /* Valores alinhados à direita, rótulos à esquerda */
            .order-items td {
                border: none;
                border-bottom: 1px solid #f0f0f0;
                position: relative;
                padding-left: 46%;
                padding-right: 12px;
                min-height: 36px;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                text-align: right;
                word-break: break-word;
            }
            .order-items td:last-child { border-bottom: none; }
            .order-items td::before {
                content: attr(data-label);
                position: absolute;
                left: 12px;
                top: 0;
                bottom: 0;
                display: flex;
                align-items: center;
                font-weight: 600;
                color: #555;
                text-align: left;
            }
        }
        /* Mostrar só o último pedido por padrão */
        .orders-list:not(.show-all) .order-card.extra-order { display: none; }
        .toggle-orders { margin-top: 12px; }
    </style>
    <script>
        // Script de cabeçalho (menu mobile e dropdowns) igual ao produtos.php
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
                    if (mainNav.classList.contains('open')) {
                        closeMenu();
                    } else {
                        openMenu();
                    }
                });

                backdrop.addEventListener('click', closeMenu);
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && mainNav.classList.contains('open')) {
                        closeMenu();
                    }
                });
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
            // Toggle de pedidos (mostrar só o último por padrão)
            (function initToggleOrders(){
                const list = document.querySelector('.orders-list');
                const btn = document.getElementById('toggle-orders-btn');
                if (!list || !btn) return;
                btn.addEventListener('click', function(){
                    const showAll = list.classList.toggle('show-all');
                    btn.textContent = showAll ? 'Mostrar menos' : 'Mostrar todos os pedidos';
                });
            })();
        });
        // Sync do carrinho entre páginas
        window.addEventListener('storage', (e) => { if (e.key === 'cart_updated') atualizarContadorCarrinho(); });
        window.addEventListener('pageshow', atualizarContadorCarrinho);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) atualizarContadorCarrinho(); });

        function atualizarContadorCarrinho() {
            fetch('contar_itens_carrinho.php')
                .then(r => r.json())
                .then(data => {
                    const el = document.querySelector('.cart-count');
                    if (el) {
                        el.textContent = data.quantidade || '0';
                        el.style.display = (data.quantidade > 0) ? 'flex' : 'none';
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
        async function removerFavorito(produtoId, btn) {
            try {
                const resp = await fetch('favoritos.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove', produto_id: produtoId })
                });
                const data = await resp.json();
                if (data.sucesso) {
                    mostrarMensagem('Removido dos favoritos.', 'success');
                    // Remover card do DOM
                    const card = btn.closest('.favorite-card');
                    if (card) card.remove();
                } else {
                    mostrarMensagem(data.erro || 'Erro ao remover favorito.', 'error');
                }
            } catch (e) { mostrarMensagem('Erro ao remover favorito.', 'error'); }
        }
        async function adicionarAoCarrinho(produtoId, nomeProduto) {
            try {
                const resp = await fetch('adicionar_ao_carrinho.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ produto_id: produtoId, quantidade: 1 })
                });
                const data = await resp.json();
                if (data.sucesso) {
                    mostrarMensagem(`${nomeProduto} adicionado ao carrinho!`, 'success');
                    atualizarContadorCarrinho();
                    try { localStorage.setItem('cart_updated', Date.now().toString()); } catch (e) {}
                } else { mostrarMensagem(data.erro || 'Erro ao adicionar ao carrinho.', 'error'); }
            } catch (e) { mostrarMensagem('Erro ao adicionar ao carrinho.', 'error'); }
        }
    </script>
</head>
<body>
    <!-- Cabeçalho replicado de produtos.php -->
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
                    <a href="minha-conta.php" class="action-icon user-account" title="Minha Conta">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                    </a>
                    <a href="logout.php" class="action-link">Sair</a>

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

    <div class="account-container">
        <div class="account-header">
            <h1>Minha Conta</h1>
            <a href="produtos.php" class="btn">Continuar Comprando</a>
        </div>

        <section class="section">
            <h2>Meus Pedidos</h2>
            <?php if (!$pedidos): ?>
                <p>Você ainda não tem pedidos.</p>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($pedidos as $i => $pedido): ?>
                        <div class="order-card <?php echo ($i > 0) ? 'extra-order' : ''; ?>">
                            <div class="order-header">
                                <strong>Pedido #<?php echo (int)$pedido['id']; ?></strong>
                                <span>Total: <?php echo formatCurrencyBR($pedido['valor_total']); ?></span>
                            </div>
                            <table class="order-items">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Quantidade</th>
                                        <th>Preço Unitário</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $itens = $itensPorPedido[$pedido['id']] ?? []; ?>
                                    <?php foreach ($itens as $item): ?>
                                        <tr>
                                            <td data-label="Produto"><?php echo htmlspecialchars($item['nome']); ?></td>
                                            <td data-label="Quantidade"><?php echo (int)$item['quantidade']; ?></td>
                                            <td data-label="Preço Unitário"><?php echo formatCurrencyBR($item['preco_unitario']); ?></td>
                                            <td data-label="Subtotal"><?php echo formatCurrencyBR($item['preco_unitario'] * $item['quantidade']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($pedidos) > 1): ?>
                    <button class="btn secondary toggle-orders" id="toggle-orders-btn">Mostrar todos os pedidos</button>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="section">
            <h2>Meus Favoritos</h2>
            <?php if (!$favoritos): ?>
                <p>Você ainda não tem favoritos.</p>
            <?php else: ?>
                <div class="favorites-grid">
                    <?php foreach ($favoritos as $fav): 
                        $nomeExib = $fav['nome'];
                        if (!empty($fav['categoria'])) { $nomeExib = str_ireplace($fav['categoria'] . ' -', '', $nomeExib); }
                        $nomeExib = trim(str_replace(['"', '&quot;', "'"], '', $nomeExib));
                        $variacaoParaImagem = str_ireplace('incenso', '', $nomeExib);
                        $variacaoParaImagem = str_replace(['"', '&quot;', "'"], '', $variacaoParaImagem);
                        $variacaoParaImagem = trim($variacaoParaImagem);
                    ?>
                        <div class="favorite-card">
                            <img src="<?php echo getImagePath($fav['categoria'], $variacaoParaImagem); ?>" alt="<?php echo htmlspecialchars($nomeExib); ?>" style="width:100%; height:160px; object-fit:contain;">
                            <strong><?php echo htmlspecialchars($nomeExib); ?></strong>
                            <span><?php echo formatCurrencyBR($fav['preco']); ?></span>
                            <div class="favorite-actions">
                                <button class="btn" onclick="adicionarAoCarrinho('<?php echo (int)$fav['produto_id']; ?>', '<?php echo htmlspecialchars($nomeExib); ?>')">Adicionar ao Carrinho</button>
                                <button class="btn danger" onclick="removerFavorito('<?php echo (int)$fav['produto_id']; ?>', this)">Remover</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
