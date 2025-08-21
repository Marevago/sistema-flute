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

// Buscar todos os produtos da categoria 'Masala Small Packet'
$queryMasalaSmall = "SELECT * FROM produtos 
                   WHERE categoria = 'Masala Small Packet' 
                   AND tipo = 'produto'
                   ORDER BY nome";
$stmtMasalaSmall = $conn->prepare($queryMasalaSmall);
$stmtMasalaSmall->execute();
$masalaSmallProducts = $stmtMasalaSmall->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Masala Small Packet - Flute Incensos</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.2">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />

    <style>
        .cart-mini.cart-bump { animation: cart-bump 500ms ease; }
        @keyframes cart-bump { 0%{transform:scale(1)} 30%{transform:scale(1.15)} 60%{transform:scale(.95)} 100%{transform:scale(1)} }
        .cart-message { position:fixed; top:20px; right:20px; z-index:9999; background:#333; color:#fff; padding:12px 16px; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,.2); opacity:0; transition:opacity .3s ease; }
        .cart-message.success{ background:#2e7d32 }
        .cart-message.error{ background:#c62828 }

        /* Alinhamento à esquerda nos cards */
        .product-card .product-info { text-align: left; display: flex; flex-direction: column; align-items: flex-start; width: 100%; }
        .product-card .product-name { width: 100%; margin-bottom: 6px; }
        .product-card .product-name a { display: inline-block; }
        .price-area { width: 100%; }
        .rating { width: 100%; }
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

        /* Estrelas de avaliação */
        .rating { display:flex; align-items:center; gap:8px; margin: 2px 0 8px; }
        .stars { position: relative; display: inline-block; line-height: 1; font-size: 16px; }
        .stars-bg, .stars-fill { font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif; letter-spacing: 2px; }
        .stars-bg { color: #d7dbe0; }
        .stars-fill { color: #f5a623; position:absolute; top:0; left:0; white-space: nowrap; overflow:hidden; }
        .rating-num { font-size: 13px; color:#6b7280; font-weight: 600; }

        /* Ícone de favoritos (coração em círculo cinza) */
        .product-card { position: relative; }
        .favorite-icon { position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px #e5e7eb; cursor: pointer; }
        .favorite-icon svg { display: block; }
        .favorite-icon:hover { background: #eceff3; }
        .favorite-icon.active svg path { stroke: #e53935; fill: #e53935; }
    </style>

    <script>
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
        });
        // Mantém o ícone do carrinho consistente entre abas/páginas
        window.addEventListener('storage', (e) => {
            if (e.key === 'cart_updated') {
                atualizarContadorCarrinho();
            }
        });
        window.addEventListener('pageshow', atualizarContadorCarrinho);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) atualizarContadorCarrinho(); });
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
        const USER_LOGGED_IN = <?php echo usuarioEstaLogado() ? 'true' : 'false'; ?>;

        // Favoritos
        async function inicializarFavoritos(){
            try {
                const res = await fetch('favoritos.php?action=list', { credentials: 'same-origin' });
                const data = await res.json();
                if (!data || !data.sucesso) return;
                const ids = new Set((data.ids || []).map(Number));
                document.querySelectorAll('.favorite-icon[data-produto-id]').forEach(el=>{
                    const id = Number(el.getAttribute('data-produto-id'));
                    const isFav = ids.has(id);
                    if (isFav) el.classList.add('active');
                    el.setAttribute('role', 'button');
                    el.setAttribute('aria-pressed', isFav ? 'true' : 'false');
                    el.setAttribute('title', isFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
                    el.addEventListener('click', ()=>toggleFavorito(el, id));
                });
            } catch(e) {}
        }
        async function toggleFavorito(el, produtoId){
            if (!USER_LOGGED_IN) { window.location.href = 'login.html'; return; }
            const adding = !el.classList.contains('active');
            try {
                const resp = await fetch('favoritos.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action: adding ? 'add' : 'remove', produto_id: produtoId }) });
                const data = await resp.json();
                if (data && data.sucesso) {
                    el.classList.toggle('active', adding);
                    el.setAttribute('aria-pressed', adding ? 'true' : 'false');
                    el.setAttribute('title', adding ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
                    mostrarMensagem(adding ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.', 'success');
                } else {
                    mostrarMensagem((data && data.erro) ? data.erro : 'Erro ao atualizar favorito.', 'error');
                }
            } catch (e) { mostrarMensagem('Erro ao atualizar favorito.', 'error'); }
        }
        document.addEventListener('DOMContentLoaded', inicializarFavoritos);

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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ produto_id: produtoId, quantidade })
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

        function pulseCartIcon() {
            const cart = document.querySelector('.cart-mini');
            if (!cart) return;
            cart.classList.remove('cart-bump');
            void cart.offsetWidth;
            cart.classList.add('cart-bump');
            setTimeout(() => cart.classList.remove('cart-bump'), 500);
        }
        // Stepper de quantidade (+/-)
        document.addEventListener('click', function(e){
            const btn = e.target.closest('.qty-btn');
            if (!btn) return;
            const stepper = btn.closest('.qty-stepper');
            if (!stepper) return;
            const input = stepper.querySelector('.quantity-input');
            if (!input) return;
            let val = parseInt(input.value || '1', 10);
            if (btn.classList.contains('plus')) val += 1; else val = Math.max(1, val - 1);
            input.value = String(val);
        });
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
                <h2>Masala Small Packet</h2>

                
                <div class="products-grid">
                    <?php foreach ($masalaSmallProducts as $produto): 
                        // 1. Título padronizado reutilizável
                        $nomePadronizado = formatarTituloProduto($produto['categoria'], $produto['nome']);

                        // 2. Preparar variação para o CAMINHO DA IMAGEM (limpeza agressiva)
                        $nomeDeExibicao = $produto['nome'];
                        if (!empty($produto['categoria'])) {
                            $nomeDeExibicao = str_ireplace($produto['categoria'] . ' -', '', $nomeDeExibicao);
                        }
                        $nomeDeExibicao = trim(str_replace(['"', '&quot;', "'"], '', $nomeDeExibicao));
                        $variacaoParaImagem = str_ireplace('incenso', '', $nomeDeExibicao);
                        $variacaoParaImagem = str_replace(['"', '&quot;', "'"], '', $variacaoParaImagem);
                        $variacaoParaImagem = trim($variacaoParaImagem);
                        // 3. Link dinâmico para produto por ID
                        $linkProduto = 'produto.php?id=' . (int)$produto['id'];
                    ?>
                        <div class="product-card">
                            <span class="favorite-icon" data-produto-id="<?php echo (int)$produto['id']; ?>" role="button" aria-label="Adicionar aos favoritos">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 21s-6.5-4.35-9.33-7.17C.63 11.79.63 8.21 2.67 6.17c2.04-2.04 5.34-2.04 7.38 0L12 8.12l1.95-1.95c2.04-2.04 5.34-2.04 7.38 0 2.04 2.04 2.04 5.62 0 7.66C18.5 16.65 12 21 12 21z" stroke="#6b7280" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div class="product-image-container">
                                <?php if ($linkProduto): ?><a href="<?php echo $linkProduto; ?>" class="no-underline" aria-label="Ver produto: <?php echo htmlspecialchars($nomePadronizado); ?>"><?php endif; ?>
                                <img 
                                    src="<?php echo getImagePath($produto['categoria'], $variacaoParaImagem); ?>" 
                                    alt="<?php echo htmlspecialchars($nomePadronizado); ?>"
                                    class="product-image"
                                >
                                <?php if ($linkProduto): ?></a><?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h2 class="product-name">
                                    <?php if ($linkProduto): ?><a href="<?php echo $linkProduto; ?>" class="no-underline"><?php endif; ?>
                                    <?php echo htmlspecialchars($nomePadronizado); ?>
                                    <?php if ($linkProduto): ?></a><?php endif; ?>
                                </h2>
                                <?php 
                                  // Avaliação simulada estável por produto: 4.5 a 4.9
                                  $rating = 4.5 + (($produto['id'] % 5) * 0.1);
                                  $ratingPct = min(100, max(0, ($rating / 5) * 100));
                                ?>
                                <div class="rating" aria-label="Avaliação: <?php echo number_format($rating,1,',','.'); ?> de 5">
                                    <div class="stars" role="img" aria-hidden="true">
                                        <div class="stars-bg">★★★★★</div>
                                        <div class="stars-fill" style="width: <?php echo number_format($ratingPct,0,'.',''); ?>%">★★★★★</div>
                                    </div>
                                    <span class="rating-num"><?php echo number_format($rating,1,',','.'); ?></span>
                                </div>
                                
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
                                        <button onclick="adicionarAoCarrinho('<?php echo $produto['id']; ?>', '<?php echo htmlspecialchars($nomePadronizado); ?>')" 
                                                class="btn btn-cart btn-full">
                                            Adicionar ao carrinho
                                        </button>
                                        <a href="https://wa.me/5548996107541?text=Ol%C3%A1%2C%20tenho%20interesse%20no%20produto%3A%20<?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp btn-full">
                                            Comprar pelo WhatsApp
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="cart-controls">
                                        <a href="login.html" class="btn btn-cart">Ver preço</a>
                                        <a href="https://wa.me/5548996107541?text=Ol%C3%A1%2C%20tenho%20interesse%20no%20produto%3A%20<?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp">
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

        // Atualiza o contador quando a página carrega
        document.addEventListener('DOMContentLoaded', atualizarContadorCarrinho);
    </script>
</body>
</html>