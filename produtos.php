<?php
// Iniciamos a sessão para manter o usuário logado e suas informações do carrinho
session_start();

function getImagePath($variacao) {
    // Array de substituição para caracteres acentuados
    $caracteresEspeciais = array(
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u',
        'ý' => 'y',
        'ñ' => 'n',
        'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a',
        'É' => 'e', 'È' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Õ' => 'o', 'Ô' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u',
        'Ý' => 'y',
        'Ñ' => 'n',
        'Ç' => 'c'
    );
    
    // Remove acentos e converte para ASCII
    $imageName = strtr(mb_strtolower($variacao, 'UTF-8'), $caracteresEspeciais);
    
    // Substitui espaços por hífens
    $imageName = str_replace(' ', '-', $imageName);
    
    // Remove caracteres que não sejam letras, números ou hífens
    $imageName = preg_replace('/[^a-z0-9-]/', '', $imageName);
    
    // Define o caminho da imagem
    $imagePath = "uploads/incensos/regular-square/{$imageName}.jpg";

    // Adiciona timestamp para evitar cache
    if (file_exists($imagePath)) {
        $timestamp = filemtime($imagePath);
        return $imagePath . "?v=" . $timestamp;
    }
    
    return file_exists($imagePath) ? $imagePath : "uploads/incensos/default.jpg";
}

// Importamos nossas configurações e classes necessárias
require_once 'config/database.php';
require_once 'carrinho.php';

// Função que verifica se o usuário está logado
function usuarioEstaLogado() {
    return isset($_SESSION['user_id']);
}

// Conectamos ao banco de dados
$database = new Database();
$conn = $database->getConnection();
// Buscar produtos featured da Regular Square
$queryFeatured = "SELECT * FROM produtos 
                  WHERE categoria = 'Regular Square' 
                  AND tipo = 'produto'
                  AND nome IN (
                      'Incenso Alecrim',
                      'Incenso Alfazema',
                      'Incenso Canela',
                      'Incenso Sândalo',
                      'Incenso Lavanda',
                      'Incenso Jasmim',
                      'Incenso Rosa Branca',
                      'Incenso Rosa Vermelha',
                      'Incenso Patchuly',
                      'Incenso Sete Ervas',
                      'Incenso Mirra',
                      'Incenso Arruda'
                  )
                  ORDER BY nome";
$stmtFeatured = $conn->prepare($queryFeatured);
$stmtFeatured->execute();
$featuredProducts = $stmtFeatured->fetchAll(PDO::FETCH_ASSOC);

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
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <style>
        /* Somente estilos do drawer/backdrop no mobile; não sobrescrever layout do header-top */
        @media (max-width: 768px) {
            .menu-toggle { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border: 1px solid #ddd; border-radius: 8px; background:#fff; }
            .menu-toggle span { display:block; width:22px; height:2px; background:#333; position:relative; }
            .menu-toggle span::before, .menu-toggle span::after { content:""; position:absolute; left:0; width:22px; height:2px; background:#333; }
            .menu-toggle span::before { top:-7px; }
            .menu-toggle span::after { top:7px; }
            /* Off-canvas side menu */
            .main-nav { position: fixed; top: 0; left: -100%; width: 80%; max-width: 320px; height: 100vh; background: #fff; padding: 20px; box-shadow: 2px 0 12px rgba(0,0,0,.15); display: flex; flex-direction: column; gap: 12px; overflow-y: auto; z-index: 1001; }
            .header-top.open ~ .main-nav { left: 0; }
            .dropdown-content { position: static; display: none; box-shadow: none; border: 1px solid #eee; border-radius: 6px; }
            .dropdown.open .dropdown-content { display: block; }
            /* Backdrop */
            .backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1000; }
            .header-top.open + .backdrop { display: block; }
        }
    </style>
    <script>
        // Mobile menu toggle and side-drawer behavior
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.querySelector('.header');
            const toggle = document.getElementById('menu-toggle');
            const backdrop = document.getElementById('backdrop');
            const mobileSearchBtn = document.getElementById('mobile-search-btn');
            const mobileSearch = document.getElementById('mobile-search');
            const mobileSearchClose = document.getElementById('mobile-search-close');
            const dropdownLinks = document.querySelectorAll('.nav-item.dropdown > a');
            const openMenu = () => {
                header.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            };
            const closeMenu = () => {
                header.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
                document.querySelectorAll('.dropdown.open').forEach(el => el.classList.remove('open'));
            };
            if (toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (header.classList.contains('open')) closeMenu(); else openMenu();
                });
            }
            if (backdrop) backdrop.addEventListener('click', closeMenu);
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
            // Mobile search open/close
            if (mobileSearchBtn && mobileSearch) {
                mobileSearchBtn.addEventListener('click', () => {
                    mobileSearch.classList.add('open');
                    const input = mobileSearch.querySelector('input[name="q"]');
                    if (input) { setTimeout(() => input.focus(), 0); }
                });
            }
            if (mobileSearchClose && mobileSearch) {
                mobileSearchClose.addEventListener('click', () => mobileSearch.classList.remove('open'));
            }
            // Enable tap to open dropdowns on mobile
            dropdownLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (window.matchMedia('(max-width: 768px)').matches) {
                        e.preventDefault();
                        const parent = link.closest('.dropdown');
                        parent.classList.toggle('open');
                    }
                });
            });
        });
        // Função para atualizar o contador do carrinho
        function atualizarContadorCarrinho() {
            fetch('get_carrinho_count.php')
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
    </script>
</head>
<body>
    <!-- Cabeçalho com logo, busca e ações -->
    <header class="header header-top">
        <button class="menu-toggle" id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
            <span></span>
        </button>
        <div class="logo-area">
            <img src="uploads/flute_logo.png" alt="Logo da Loja" class="logo">
        </div>
        <form class="search-bar" action="buscar.php" method="get">
            <input type="text" name="q" placeholder="Digite o que você procura" aria-label="Buscar produtos">
            <button type="submit" aria-label="Buscar" class="search-btn">🔍</button>
        </form>
        <div class="header-actions">
            <button type="button" class="header-search-btn" id="mobile-search-btn" aria-label="Buscar" title="Buscar">🔍</button>
            <a href="central-atendimento.php" class="action-link">Central de Atendimento</a>
            <?php if (usuarioEstaLogado()): ?>
                <span class="action-link">Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                <a href="logout.php" class="action-link">Sair</a>
            <?php else: ?>
                <a href="login.html" class="action-link">Entrar / Cadastrar</a>
            <?php endif; ?>
            <a href="carrinho-pagina.php" class="cart-mini">
                🛒 <span class="cart-count" id="cart-count">0</span>
            </a>
        </div>
    </header>

    <!-- Sobreposição de busca mobile -->
    <div class="mobile-search-overlay" id="mobile-search">
        <form class="mobile-search-form" action="buscar.php" method="get">
            <input type="text" name="q" placeholder="Busque produtos" aria-label="Buscar" autofocus>
            <button type="submit" class="mobile-search-submit">Buscar</button>
            <button type="button" class="mobile-search-close" id="mobile-search-close" aria-label="Fechar">✕</button>
        </form>
    </div>

    <!-- Barra de categorias (desktop) e drawer (mobile já existente) -->
    <nav class="main-categories">
        <ul class="cat-list">
            <li class="nav-item dropdown">
                <a href="#" class="nav-link">Produtos</a>
                <div class="dropdown-content">
                    <a href="regular-square.php?cat=regular-square">Regular Square</a>
                    <a href="masala-square.php?cat=masala-square">Masala Square</a>
                    <a href="xamanico-tube.php?cat=incenso-xamanico">Incenso Xamânico Tube</a>
                    <a href="cycle-brand-regular.php?cat=cycle-brand-regular">Cycle Brand Regular Square</a>
                    <a href="long-square.php?cat=long-square">Long Square</a>
                    <a href="cycle-brand-rectangle.php?cat=cycle-brand-rectangle">Cycle Brand Rectangle</a>
                    <a href="masala-small.php?cat=masala-small">Masala Small Packet</a>
                    <a href="clove-brand.php?cat=clove-brand">Clove Brand</a>
                    <a href="produtos.php">Ver Todos os Produtos</a>
                </div>
            </li>
            <li><a href="index.php" class="nav-link">Início</a></li>
            <li><a href="sobre.php" class="nav-link">Sobre</a></li>
            <li><a href="contato.php" class="nav-link">Contato</a></li>
        </ul>
    </nav>

    <!-- Drawer mobile reaproveita a estrutura existente abaixo -->
    <div class="backdrop" id="backdrop"></div>
    <nav class="main-nav">
                <div class="nav-item">
                    <a href="index.php" class="nav-link">Início</a>
                </div>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link">Produtos</a>
                    <div class="dropdown-content">
                        <a href="regular-square.php?cat=regular-square">Regular Square</a>
                        <a href="masala-square.php?cat=masala-square">Masala Square</a>
                        <a href="xamanico-tube.php?cat=incenso-xamanico">Incenso Xamânico Tube</a>
                        <a href="cycle-brand-regular.php?cat=cycle-brand-regular">Cycle Brand Regular Square</a>
                        <a href="long-square.php?cat=long-square">Long Square</a>
                        <a href="cycle-brand-rectangle.php?cat=cycle-brand-rectangle">Cycle Brand Rectangle</a>
                        <a href="masala-small.php?cat=masala-small">Masala Small Packet</a>
                        <a href="clove-brand.php?cat=clove-brand">Clove Brand</a>
                        <a href="produtos.php">Ver Todos os Produtos</a>
                    </div>
                </div>
                <a href="sobre.php" class="nav-link">Sobre</a>
                <a href="contato.php" class="nav-link">Contato</a>
                
                <?php if (usuarioEstaLogado()): ?>
                    <div class="cart-icon">
                        <a href="carrinho-pagina.php" class="btn btn-cart">
                            🛒 Carrinho
                            <span class="cart-count" id="cart-count">0</span>
                        </a>
                    </div>
                    <span class="nav-link">Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <a href="logout.php" class="btn">Sair</a>
                <?php else: ?>
                    <a href="login.html" class="btn btn-cart">Login</a>
                <?php endif; ?>
            </nav>

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
                        $nomeSemIncenso = str_replace('Incenso ', '', $produto['nome']); // Remove "Incenso " do nome
                    ?>
                        <div class="product-card">
                            <span class="favorite-icon">&hearts;</span>
                            <div class="product-image-container">
                                <img 
                                    src="<?php echo getImagePath($nomeSemIncenso); ?>" 
                                    alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                    class="product-image"
                                >
                            </div>
                            <div class="product-info">
                                <h2 class="product-name"><?php echo htmlspecialchars($nomeSemIncenso); ?></h2>
                                
                                <?php if (usuarioEstaLogado()): ?>
                                    <div class="price-area">
                                        <div class="price">
                                            R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                        </div>
                                    </div>
                                    <div class="cart-controls">
                                        <div class="quantity-selector">
                                            <label for="qty_<?php echo $produto['id']; ?>">Qtd:</label>
                                            <input type="number" id="qty_<?php echo $produto['id']; ?>" class="quantity-input" value="1" min="1">
                                        </div>
                                        <button onclick="adicionarAoCarrinho('<?php echo $produto['id']; ?>', '<?php echo htmlspecialchars($nomeSemIncenso); ?>')" 
                                                class="btn btn-cart">
                                            Ver Preço / Comprar
                                        </button>
                                        <a href="https://wa.me/5511999999999?text=Olá, tenho interesse no produto: <?php echo urlencode($nomeSemIncenso); ?>" target="_blank" class="btn btn-whatsapp">
                                            Comprar pelo WhatsApp
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="price-restricted">
                                        <p>Faça login para ver o preço e comprar</p>
                                        <a href="login.html" class="btn btn-cart">Login</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

        </main>
    </div>

    <!-- Rodapé -->
    <footer class="footer">
        <p>&copy; 2025 Flute Incensos. Todos os direitos reservados.</p>
    </footer>

                            produto_id: produtoId,
                            quantidade: quantidade,
                            variacao: select ? decodeURIComponent(select.value.split('_')[1]) : null
                        })
                    });

                    const data = await response.json();
                    
                    if (data.sucesso) {
                        mostrarMensagem('Produto adicionado ao carrinho!', 'success');
                        atualizarContadorCarrinho();
                    } else {
                        mostrarMensagem(data.erro, 'error');
                    }
                } catch (error) {
                    mostrarMensagem('Erro ao adicionar ao carrinho', 'error');
                }
            }
        }

        // Função para atualizar o contador de itens no carrinho
        async function atualizarContadorCarrinho() {
            try {
                const response = await fetch('contar_itens_carrinho.php');
                const data = await response.json();
                
                const contadorElement = document.getElementById('cart-count');
                if (contadorElement) {
                    contadorElement.textContent = data.quantidade;
                }
            } catch (error) {
                console.error('Erro ao atualizar contador:', error);
            }
        }

        // Atualiza o contador quando a página carrega
        document.addEventListener('DOMContentLoaded', atualizarContadorCarrinho);
    </script>
    <script>
        // Função para adicionar produto ao carrinho
        async function adicionarAoCarrinho(produtoId, nomeProduto) {
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
                } else {
                    mostrarMensagem(data.erro || 'Erro ao adicionar ao carrinho.', 'error');
                }
            } catch (error) {
                console.error('Erro ao adicionar ao carrinho:', error);
                mostrarMensagem('Erro ao adicionar ao carrinho: ' + error.message, 'error');
            }
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

        // Função para atualizar o contador do carrinho
        function atualizarContadorCarrinho() {
            fetch('get_carrinho_count.php')
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
    </script>

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
</body>
</html>