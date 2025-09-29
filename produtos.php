<?php
// Iniciamos a sessão para manter o usuário logado e suas informações do carrinho
session_start();



// Importamos nossas configurações e classes necessárias
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'carrinho.php';

// Função que verifica se o usuário está logado
function usuarioEstaLogado() {
    return isset($_SESSION['user_id']);
}

// Conectamos ao banco de dados
$database = new Database();
$conn = $database->getConnection();
// Buscar produtos featured da Regular Square (sem depender de nomes específicos)
$queryFeatured = "SELECT * FROM produtos 
                  WHERE categoria = 'Regular Square' 
                  AND tipo = 'produto'
                  ORDER BY nome";
$stmtFeatured = $conn->prepare($queryFeatured);
$stmtFeatured->execute();
$featuredProducts = $stmtFeatured->fetchAll(PDO::FETCH_ASSOC);

// Fallback: se nenhum produto retornar (possível mudança de nome de categoria no servidor),
// exibe um conjunto geral de produtos disponíveis
if (!$featuredProducts || count($featuredProducts) === 0) {
    $queryFeaturedFallback = "SELECT * FROM produtos WHERE tipo = 'produto' ORDER BY nome LIMIT 12";
    $stmtFeaturedFallback = $conn->prepare($queryFeaturedFallback);
    $stmtFeaturedFallback->execute();
    $featuredProducts = $stmtFeaturedFallback->fetchAll(PDO::FETCH_ASSOC);
}

$featuredRegularSquare = [
    'Alecrim',
    'Alfazema',
    'Canela',
    'Sândalo',
    'Lavanda',
    'Jasmim',
    'Rosa Branca',
    'Rosa Vermelha',
    'Patchuly',
    'Sete Ervas',
    'Mirra',
    'Arruda'
];

// Buscamos todas as categorias únicas para o menu lateral
$queryCategories = "SELECT * FROM produtos WHERE tipo = 'categoria' ORDER BY nome";$stmtCategories = $conn->prepare($queryCategories);
$queryProdutos = "SELECT * FROM produtos WHERE tipo = 'produto' AND categoria_id = ? ORDER BY nome";
$stmtCategories->execute();
$categorias = $stmtCategories->fetchAll(PDO::FETCH_COLUMN);

// Buscamos todos os produtos para exibição
$query = "SELECT * FROM produtos ORDER BY categoria, nome";
$stmt = $conn->prepare($query);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se o usuário estiver logado, inicializamos o carrinho
if (usuarioEstaLogado()) {
    $carrinho = new CarrinhoHandler();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flute Incensos - Produtos</title>
    <link rel="icon" type="image/png" href="uploads/flute_logo.png">
    <link rel="apple-touch-icon" href="uploads/flute_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.3">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <?php include __DIR__ . '/config/analytics.php'; ?>

    <style>
        /* Pequena animação no ícone do carrinho quando adicionar */
        .cart-mini.cart-bump { animation: cart-bump 500ms ease; }
        @keyframes cart-bump {
            0% { transform: scale(1); }
            30% { transform: scale(1.15); }
            60% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }
        /* Toast de feedback ao adicionar ao carrinho */
        .cart-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #333;
            color: #fff;
            padding: 12px 16px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .cart-message.success { background: #2e7d32; }
        .cart-message.error { background: #c62828; }
        /* Favoritos (coração) */
        .product-card { position: relative; }
        .favorite-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 22px;
            color: #b0b0b0;
            transition: color .2s ease, transform .15s ease;
            z-index: 2;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            background: transparent;
            border: none;
            box-shadow: none;
        }
        .favorite-icon:hover { transform: scale(1.08); }
        .favorite-icon.active { color: #e53935; }
        /* Título principal com a mesma fonte do login (EB Garamond) */
        .main-text2 h2 {
            font-family: "EB Garamond", serif;
            font-weight: 600;
            letter-spacing: .2px;
        }
        /* Mobile: reduzir espaçamento entre linhas e deixar em negrito */
        @media (max-width: 767px) {
            .main-text2 h2 {
                line-height: 1.15;
                font-weight: 700;
            }
        }
    </style>

    <script>
        const USER_LOGGED_IN = <?php echo usuarioEstaLogado() ? 'true' : 'false'; ?>;
        // Novo script para o cabeçalho (menu mobile e dropdowns)
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
                    // Fecha submenus abertos no mobile
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

            // Lógica de dropdown para o menu mobile (drawer)
            const dropdownsInNav = document.querySelectorAll('.main-nav .nav-item.dropdown');
            dropdownsInNav.forEach(dropdown => {
                const link = dropdown.querySelector('a');
                link.addEventListener('click', function(e) {
                    if (window.matchMedia('(max-width: 768px)').matches && mainNav.classList.contains('open')) {
                        e.preventDefault();
                        dropdown.classList.toggle('open');
                    }
                });
            });
            // Atualiza contador ao carregar a página
            atualizarContadorCarrinho();

            // Inicializa estado dos favoritos (corações)
            inicializarFavoritos();
        });

        // Mantém o ícone do carrinho consistente entre abas/páginas
        window.addEventListener('storage', (e) => {
            if (e.key === 'cart_updated') {
                atualizarContadorCarrinho();
            }
        });
        window.addEventListener('pageshow', atualizarContadorCarrinho);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) atualizarContadorCarrinho(); });
        async function inicializarFavoritos() {
            try {
                const res = await fetch('favoritos.php?action=list', { credentials: 'same-origin' });
                const data = await res.json();
                if (!data || !data.sucesso) return;
                const ids = new Set((data.ids || []).map(Number));
                document.querySelectorAll('.favorite-icon[data-produto-id]')
                    .forEach(el => {
                        const id = Number(el.getAttribute('data-produto-id'));
                        const isFav = ids.has(id);
                        if (isFav) el.classList.add('active');
                        el.setAttribute('role', 'button');
                        el.setAttribute('aria-pressed', isFav ? 'true' : 'false');
                        el.setAttribute('title', isFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
                        el.addEventListener('click', () => toggleFavorito(el, id));
                    });
            } catch (e) { /* silencioso */ }
        }

        async function toggleFavorito(el, produtoId) {
            if (!USER_LOGGED_IN) { window.location.href = 'login.html'; return; }
            const adding = !el.classList.contains('active');
            try {
                const resp = await fetch('favoritos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: adding ? 'add' : 'remove', produto_id: produtoId })
                });
                const data = await resp.json();
                if (data && data.sucesso) {
                    el.classList.toggle('active', adding);
                    el.setAttribute('aria-pressed', adding ? 'true' : 'false');
                    el.setAttribute('title', adding ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
                    mostrarMensagem(adding ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.', 'success');
                } else {
                    mostrarMensagem((data && data.erro) ? data.erro : 'Erro ao atualizar favorito.', 'error');
                }
            } catch (e) {
                mostrarMensagem('Erro ao atualizar favorito.', 'error');
            }
        }
        // Função para atualizar o contador do carrinho
        function atualizarContadorCarrinho() {
            fetch('contar_itens_carrinho.php')
                .then(response => response.json())
                .then(data => {
                    const contador = document.querySelector('.cart-count');
                    if (contador) {
                        contador.textContent = data.quantidade || '0';
                        contador.style.display = data.quantidade > 0 ? 'flex' : 'none';
                    }
                })
                .catch(error => console.error('Erro ao atualizar contador:', error));
        }

        // Função para exibir mensagens
        function mostrarMensagem(mensagem, tipo) {
            const msg = document.createElement('div');
            msg.className = `cart-message ${tipo}`;
            msg.textContent = mensagem;
            document.body.appendChild(msg);

            // Anima a entrada
            setTimeout(() => msg.style.opacity = '1', 100);

            // Remove a mensagem após 3 segundos
            setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            }, 3000);
        }

        // Função global para adicionar produto ao carrinho
        window.adicionarAoCarrinho = async function(produtoId, nomeProduto) {
            const quantityInput = document.getElementById(`qty_${produtoId}`);
            const quantidade = quantityInput ? parseInt(quantityInput.value, 10) : 1;

            if (quantidade < 1) {
                mostrarMensagem('A quantidade deve ser de pelo menos 1.', 'error');
                return;
            }

            try {
                const response = await fetch('adicionar_ao_carrinho.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        produto_id: produtoId,
                        quantidade: quantidade
                    })
                });

                const data = await response.json();

                if (data.sucesso) {
                    mostrarMensagem(`${nomeProduto} adicionado ao carrinho!`, 'success');
                    atualizarContadorCarrinho();
                    pulseCartIcon();
                    try { localStorage.setItem('cart_updated', Date.now().toString()); } catch (e) {}
                } else {
                    mostrarMensagem(data.erro || 'Erro ao adicionar ao carrinho.', 'error');
                }
            } catch (error) {
                console.error('Erro ao adicionar ao carrinho:', error);
                mostrarMensagem('Erro ao adicionar ao carrinho: ' + error.message, 'error');
            }
        };

        function pulseCartIcon() {
            const cart = document.querySelector('.cart-mini');
            if (!cart) return;
            cart.classList.remove('cart-bump');
            // força reflow para reiniciar animação caso repetida rapidamente
            // eslint-disable-next-line no-unused-expressions
            void cart.offsetWidth;
            cart.classList.add('cart-bump');
            setTimeout(() => cart.classList.remove('cart-bump'), 500);
        }

        // Função para atualizar preço quando uma variação é selecionada
        function atualizarPreco(select) {
            const option = select.options[select.selectedIndex];
            const preco = option.getAttribute('data-price');
            const produtoId = select.id.split('_')[1];
            
            const precoElement = select.closest('.product-card').querySelector('.price');
            if (precoElement) {
                precoElement.textContent = `R$ ${parseFloat(preco).toFixed(2).replace('.', ',')}`;
            }
        }
    </script>
</head>
<body>
    <!-- Cabeçalho com logo, busca e ações -->
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
                    <?php if (usuarioEstaLogado()): ?>
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
        
        <!-- Barra de busca mobile sempre visível -->
        <div class="mobile-search-fixed" style="display: none; background: #fff; border-bottom: 1px solid #e9ecef; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="container">
                <form class="mobile-search-form" action="buscar.php" method="get" style="display: flex; align-items: center; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 25px; padding: 10px 20px; gap: 12px; margin: 0 15px;">
                    <input type="text" name="q" placeholder="Digite o que você procura..." aria-label="Buscar produtos" style="flex: 1; border: none; background: transparent; outline: none; font-size: 16px; color: #333; padding: 0;">
                    <button type="submit" aria-label="Buscar" class="mobile-search-submit" style="background: none; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
        </div>
        
        <style>
        @media (max-width: 768px) {
            .mobile-search-fixed {
                display: block !important;
                padding: 12px 0;
            }
        }
        </style>
    </header>

    <!-- Slider main container -->
    <div class="swiper-container banner-slider">
        <div class="swiper-wrapper">
            <!-- Slides -->
            <div class="swiper-slide"><img src="uploads/banners/banner-masala.png" alt="Banner Flute Incense Masala"></div>
            <div class="swiper-slide"><img src="uploads/banners/banner-tulasi.png" alt="Banner Flute Incense Tulasi"></div>
            <div class="swiper-slide"><img src="uploads/banners/banner-clove.png" alt="Banner Flute Incense Clove"></div>
        </div>
    </div>

    <!-- Container principal com sidebar e produtos -->
    <div class="main-container">
        <main class="products-area">
            <div class="main-text2">
                <h2>Veja Nossos Produtos Disponíveis</h2>

                
                <div class="products-grid">
                    <?php foreach ($featuredProducts as $produto): 
                        // 1. Preparar nome para EXIBIÇÃO
                        $nomeDeExibicao = $produto['nome'];
                        if (!empty($produto['categoria'])) {
                            $nomeDeExibicao = str_ireplace($produto['categoria'] . ' -', '', $nomeDeExibicao);
                        }
                        $nomeDeExibicao = trim(str_replace(['"', '&quot;', "'"], '', $nomeDeExibicao));

                        // Título padronizado para exibição/CTA (igual ao usado na loja)
                        $tituloFormatado = formatarTituloProduto($produto['categoria'], $produto['nome']);

                        // 2. Preparar variação para o CAMINHO DA IMAGEM (limpeza agressiva)
                        $variacaoParaImagem = str_ireplace('incenso', '', $nomeDeExibicao);
                        $variacaoParaImagem = str_replace(['"', '&quot;', "'"], '', $variacaoParaImagem);
                        $variacaoParaImagem = trim($variacaoParaImagem);
                        
                        // 3. Link dinâmico para página de produto por ID
                        $linkProduto = 'produto.php?id=' . (int)$produto['id'];
                     ?>
                        <div class="product-card">
                            <span class="favorite-icon" data-produto-id="<?php echo (int)$produto['id']; ?>" role="button" aria-label="Adicionar aos favoritos">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 21s-6.5-4.35-9.33-7.17C.63 11.79.63 8.21 2.67 6.17c2.04-2.04 5.34-2.04 7.38 0L12 8.12l1.95-1.95c2.04-2.04 5.34-2.04 7.38 0 2.04 2.04 2.04 5.62 0 7.66C18.5 16.65 12 21 12 21z" stroke="#6b7280" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div class="product-image-container">
                                <?php if ($linkProduto): ?><a href="<?php echo $linkProduto; ?>" class="no-underline" aria-label="Ver produto: <?php echo htmlspecialchars($nomeDeExibicao); ?>"><?php endif; ?>
                                <img 
                                    src="<?php echo getImagePath($produto['categoria'], $variacaoParaImagem); ?>" 
                                     alt="<?php echo htmlspecialchars($nomeDeExibicao); ?>"
                                     class="product-image"
                                >
                                <?php if ($linkProduto): ?></a><?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h2 class="product-name">
                                    <?php if ($linkProduto): ?><a href="<?php echo $linkProduto; ?>" class="no-underline"><?php endif; ?>
                                    <?php echo htmlspecialchars($tituloFormatado); ?>
                                    <?php if ($linkProduto): ?></a><?php endif; ?>
                                </h2>
                                
                                <?php if (usuarioEstaLogado()): ?>
                                    <div class="price-area">
                                        <div class="price-row">
                                            <div class="price">
                                                <span class="currency">R$</span> <strong class="amount"><?php echo number_format($produto['preco'], 2, ',', '.'); ?></strong>
                                            </div>
                                            <div class="qty-stepper" data-id="<?php echo (int)$produto['id']; ?>">
                                                <button type="button" class="qty-btn minus" aria-label="Diminuir quantidade">&minus;</button>
                                                <input type="number" id="qty_<?php echo $produto['id']; ?>" class="quantity-input" value="1" min="1" inputmode="numeric" pattern="[0-9]*" aria-label="Quantidade">
                                                <button type="button" class="qty-btn plus" aria-label="Aumentar quantidade">+</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="cart-controls">
                                        <button onclick="adicionarAoCarrinho('<?php echo $produto['id']; ?>', '<?php echo htmlspecialchars($tituloFormatado); ?>')" 
                                                class="btn btn-cart btn-full">
                                            Adicionar ao carrinho
                                        </button>
                                        <a href="https://wa.me/5548996107541?text=Ol%C3%A1%2C%20tenho%20interesse%20no%20produto%3A%20<?php echo urlencode($tituloFormatado); ?>" target="_blank" class="btn btn-whatsapp btn-full">
                                            Comprar pelo WhatsApp
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="cart-controls">
                                        <a href="login.html" class="btn btn-cart">Ver preço</a>
                                        <a href="https://wa.me/5548996107541?text=Ol%C3%A1%2C%20tenho%20interesse%20no%20produto%3A%20<?php echo urlencode($tituloFormatado); ?>" target="_blank" class="btn btn-whatsapp">
                                            Comprar pelo WhatsApp
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                     <?php endforeach; ?>
                 </div>

        </main>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

    <!-- Initialize Swiper -->
    <script>
        var swiper = new Swiper('.banner-slider', {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            }
        });
    </script>
    <!-- Styles to mirror index.php product card layout -->
    <style>
      /* Alinhamento à esquerda nos cards e largura total */
      .product-card .product-info { text-align: left; display: flex; flex-direction: column; align-items: flex-start; width: 100%; }
      .product-card .product-name { width: 100%; margin-bottom: 6px; }
      .product-card .product-name a { display: inline-block; }
      .price-area { width: 100%; }
      .cart-controls { width: 100%; }
      .cart-controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
      .cart-controls { flex-direction: column; align-items: flex-start; }
      .cart-controls .buy-row { display:flex; gap: 10px; align-items: center; }
      .cart-controls .btn-whatsapp { margin-top: 6px; }
      .qty-stepper { display: inline-flex; align-items: center; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; background: #fff; }
      .qty-stepper .qty-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 0; background: #f5f5f7; color: #333; font-size: 18px; cursor: pointer; transition: background .15s ease; }
      .qty-stepper .qty-btn:hover { background: #ececf1; }
      .qty-stepper .quantity-input { width: 48px; height: 32px; border: 0; outline: 0; text-align: center; font-weight: 600; color: #333; background: #fff; -moz-appearance: textfield; }
      .qty-stepper .quantity-input::-webkit-outer-spin-button,
      .qty-stepper .quantity-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
      @media (max-width: 560px) { .qty-stepper .qty-btn { width: 30px; height: 30px; } .qty-stepper .quantity-input { width: 42px; height: 30px; } }

      /* Preço: somente números em negrito */
      .price { display: flex; align-items: baseline; gap: 4px; }
      .price .currency { font-weight: 400; color: #444; }
      .price .amount { font-weight: 700; color: #000; }
      .price-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%; }
      .btn-full { display: block; width: 100%; box-sizing: border-box; }
      @media (max-width: 560px) {
        .price-row { flex-wrap: wrap; gap: 8px; }
        .price { margin-right: auto; }
        .cart-controls .btn-whatsapp { width: 100%; }
      }

      

      /* Ícone de favoritos (coração em círculo cinza) */
      .favorite-icon { position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px #e5e7eb; cursor: pointer; }
      .favorite-icon svg { display: block; }
      .favorite-icon:hover { background: #eceff3; }
      .favorite-icon.active svg path { stroke: #e53935; fill: #e53935; }
    </style>
    <!-- Stepper behavior identical to index.php -->
    <script>
      (function(){
        function clampToInt(val){
          var n = parseInt(val, 10);
          if (isNaN(n) || n < 1) n = 1;
          return n;
        }
        document.addEventListener('click', function(e){
          var btn = e.target.closest('.qty-btn');
          if (!btn) return;
          var stepper = btn.closest('.qty-stepper');
          if (!stepper) return;
          var input = stepper.querySelector('.quantity-input');
          var current = clampToInt(input && input.value ? input.value : '1');
          if (btn.classList.contains('plus')) current += 1; else current = Math.max(1, current - 1);
          if (input) { input.value = String(current); input.dispatchEvent(new Event('change', { bubbles: true })); }
        });
        document.addEventListener('input', function(e){
          if (!e.target.classList.contains('quantity-input')) return;
          e.target.value = String(clampToInt(e.target.value));
        });
      })();
    </script>
</body>
</html>