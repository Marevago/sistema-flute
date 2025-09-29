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

// Capturar termo de busca
$termoBusca = isset($_GET['q']) ? trim($_GET['q']) : '';
$produtos = [];
$produtosSimilares = [];
$totalResultados = 0;
$sugestoesBusca = [];

// Função para busca inteligente com sinônimos e palavras relacionadas
function buscarProdutosInteligente($conn, $termo) {
    // Mapeamento de sinônimos e palavras relacionadas
    $sinonimos = [
        'incenso' => ['incenso', 'incensos', 'bastão', 'bastões', 'vara', 'varas'],
        'masala' => ['masala', 'massala', 'natural', 'artesanal'],
        'regular' => ['regular', 'comum', 'tradicional', 'clássico'],
        'square' => ['square', 'quadrado', 'quadrada'],
        'tube' => ['tube', 'tubo', 'cilindro'],
        'xamanico' => ['xamanico', 'xamânico', 'shamanico', 'shamânico', 'ritual', 'sagrado'],
        'clove' => ['clove', 'cravo', 'tempero'],
        'cycle' => ['cycle', 'ciclo'],
        'brand' => ['brand', 'marca'],
        'small' => ['small', 'pequeno', 'mini'],
        'long' => ['long', 'longo', 'comprido'],
        'rectangle' => ['rectangle', 'retangular', 'retângulo'],
        'packet' => ['packet', 'pacote', 'embalagem']
    ];
    
    // Palavras conectivas que devem ser ignoradas na busca
    $palavrasIgnorar = ['de', 'da', 'do', 'das', 'dos', 'para', 'com', 'sem', 'por', 'em', 'na', 'no', 'nas', 'nos', 'a', 'o', 'as', 'os', 'e', 'ou', 'que', 'todos', 'todas', 'todo', 'toda'];
    
    // Quebrar termo em palavras individuais
    $palavrasBusca = preg_split('/\s+/', strtolower(trim($termo)));
    $palavrasBusca = array_filter($palavrasBusca, function($palavra) use ($palavrasIgnorar) {
        return strlen($palavra) >= 2 && !in_array($palavra, $palavrasIgnorar);
    });
    
    // Expandir com sinônimos
    $todasPalavras = [];
    foreach ($palavrasBusca as $palavra) {
        $todasPalavras[] = $palavra;
        
        // Adicionar sinônimos
        foreach ($sinonimos as $chave => $lista) {
            if (stripos($palavra, $chave) !== false || in_array($palavra, $lista)) {
                $todasPalavras = array_merge($todasPalavras, $lista);
            }
        }
    }
    
    $todasPalavras = array_unique($todasPalavras);
    
    if (empty($todasPalavras)) {
        return [];
    }
    
    // Construir query com sistema de pontuação avançado
    $conditions = [];
    $params = [];
    $scoreConditions = [];
    
    // Busca por termo completo (maior pontuação)
    $params[':termoCompleto'] = '%' . $termo . '%';
    $scoreConditions[] = "CASE WHEN (nome LIKE :termoCompleto OR categoria LIKE :termoCompleto) THEN 100 ELSE 0 END";
    
    // Busca por palavras individuais
    foreach ($todasPalavras as $index => $palavra) {
        $paramNome = ':palavra' . $index;
        $paramCategoria = ':categoria' . $index;
        
        $conditions[] = "(nome LIKE $paramNome OR categoria LIKE $paramCategoria)";
        $params[$paramNome] = '%' . $palavra . '%';
        $params[$paramCategoria] = '%' . $palavra . '%';
        
        // Pontuação por palavra encontrada
        $scoreConditions[] = "CASE WHEN nome LIKE $paramNome THEN 10 ELSE 0 END";
        $scoreConditions[] = "CASE WHEN categoria LIKE $paramCategoria THEN 5 ELSE 0 END";
    }
    
    // Bonus para múltiplas palavras encontradas
    $bonusConditions = [];
    if (count($palavrasBusca) > 1) {
        foreach ($palavrasBusca as $index => $palavra) {
            $paramBonus = ':bonus' . $index;
            $params[$paramBonus] = '%' . $palavra . '%';
            $bonusConditions[] = "(nome LIKE $paramBonus OR categoria LIKE $paramBonus)";
        }
        
        if (!empty($bonusConditions)) {
            $scoreConditions[] = "CASE WHEN (" . implode(' AND ', $bonusConditions) . ") THEN 50 ELSE 0 END";
        }
    }
    
    $scoreCalculation = implode(' + ', $scoreConditions);
    
    $query = "SELECT *, 
              ($scoreCalculation) as relevancia
              FROM produtos 
              WHERE (" . implode(' OR ', $conditions) . ") 
              AND tipo = 'produto'
              HAVING relevancia > 0
              ORDER BY relevancia DESC, nome ASC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar produtos similares baseados na categoria
function buscarProdutosSimilares($conn, $produtos, $limite = 6) {
    if (empty($produtos)) return [];
    
    $categorias = array_unique(array_column($produtos, 'categoria'));
    $idsExcluir = array_column($produtos, 'id');
    
    $placeholders = str_repeat('?,', count($categorias) - 1) . '?';
    $placeholdersIds = str_repeat('?,', count($idsExcluir) - 1) . '?';
    
    $query = "SELECT * FROM produtos 
              WHERE categoria IN ($placeholders) 
              AND id NOT IN ($placeholdersIds)
              AND tipo = 'produto'
              ORDER BY RAND() 
              LIMIT $limite";
    
    $stmt = $conn->prepare($query);
    $params = array_merge($categorias, $idsExcluir);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para gerar sugestões de busca
function gerarSugestoesBusca() {
    return [
        'Masala Square',
        'Regular Square', 
        'Incenso Xamânico',
        'Cycle Brand',
        'Long Square',
        'Clove Brand',
        'Masala Small'
    ];
}

if (!empty($termoBusca)) {
    // Busca inteligente
    $produtos = buscarProdutosInteligente($conn, $termoBusca);
    $totalResultados = count($produtos);
    
    // Se encontrou produtos, buscar similares
    if ($totalResultados > 0) {
        $produtosSimilares = buscarProdutosSimilares($conn, $produtos);
    } else {
        // Se não encontrou nada, gerar sugestões
        $sugestoesBusca = gerarSugestoesBusca();
    }
}

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
    <title>Busca: <?php echo htmlspecialchars($termoBusca); ?> - Flute Incensos</title>
    <?php // Google Analytics
        $gaInclude = __DIR__ . '/config/analytics.php';
        if (file_exists($gaInclude)) { include $gaInclude; }
    ?>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.2">

    <style>
        /* Animação no ícone do carrinho quando adicionar */
        .cart-mini.cart-bump { animation: cart-bump 500ms ease; }
        @keyframes cart-bump {
            0% { transform: scale(1); }
            30% { transform: scale(1.15); }
            60% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }
        /* Toast básico */
        .cart-message {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: #333; color: #fff; padding: 12px 16px; border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2); opacity: 0; transition: opacity .3s ease;
        }
        .cart-message.success { background: #2e7d32; }
        .cart-message.error { background: #c62828; }
        
        .search-results-header {
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .search-results-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .search-results-count {
            color: #666;
            font-size: 16px;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-results h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .search-suggestions {
            margin-top: 30px;
        }
        
        .search-suggestions h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .suggestions-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }
        
        .suggestion-link {
            display: inline-block;
            padding: 10px 20px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 25px;
            color: #495057;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .suggestion-link:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
            transform: translateY(-2px);
        }
        
        .similar-products-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #e5e5e5;
        }
        
        .similar-products-section h3 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .suggestions-grid {
                justify-content: center;
            }
            
            .suggestion-link {
                padding: 8px 16px;
                font-size: 13px;
            }
            
            .similar-products-section {
                margin-top: 30px;
            }
            
            .similar-products-section h3 {
                font-size: 20px;
                margin-bottom: 20px;
            }
        }
    </style>

    <script>
        const USER_LOGGED_IN = <?php echo usuarioEstaLogado() ? 'true' : 'false'; ?>;
        // Script para o cabeçalho (menu mobile e dropdowns)
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

            // Lógica de dropdown para o menu mobile
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

            setTimeout(() => msg.style.opacity = '1', 100);

            setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            }, 3000);
        }

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
                    // Emitir evento para sincronização entre páginas
                    localStorage.setItem('cart_updated', Date.now().toString());
                    window.dispatchEvent(new Event('cart_updated'));
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
            void cart.offsetWidth;
            cart.classList.add('cart-bump');
            setTimeout(() => cart.classList.remove('cart-bump'), 500);
        }
        
        // Sincronização do carrinho entre páginas
        window.addEventListener('storage', function(e) {
            if (e.key === 'cart_updated') {
                atualizarContadorCarrinho();
            }
        });

        window.addEventListener('pageshow', function(e) {
            atualizarContadorCarrinho();
        });

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                atualizarContadorCarrinho();
            }
        });
    </script>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Container principal -->
    <div class="main-container">
        <main class="products-area">
            <div class="search-results-header">
                <h1>Resultados da busca</h1>
                <?php if (!empty($termoBusca)): ?>
                    <p class="search-results-count">
                        <?php if ($totalResultados > 0): ?>
                            <?php echo $totalResultados; ?> resultado<?php echo $totalResultados > 1 ? 's' : ''; ?> encontrado<?php echo $totalResultados > 1 ? 's' : ''; ?> para "<strong><?php echo htmlspecialchars($termoBusca); ?></strong>"
                        <?php else: ?>
                            Nenhum resultado encontrado para "<strong><?php echo htmlspecialchars($termoBusca); ?></strong>"
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (empty($termoBusca)): ?>
                <div class="no-results">
                    <h2>Digite um termo para buscar</h2>
                    <p>Use a barra de busca acima para encontrar produtos.</p>
                </div>
            <?php elseif ($totalResultados == 0): ?>
                <div class="no-results">
                    <h2>Nenhum produto encontrado</h2>
                    <p>Tente usar termos diferentes ou experimente uma das sugestões abaixo:</p>
                    
                    <?php if (!empty($sugestoesBusca)): ?>
                        <div class="search-suggestions">
                            <h3>Sugestões de busca:</h3>
                            <div class="suggestions-grid">
                                <?php foreach ($sugestoesBusca as $sugestao): ?>
                                    <a href="buscar.php?q=<?php echo urlencode($sugestao); ?>" class="suggestion-link">
                                        <?php echo htmlspecialchars($sugestao); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($produtos as $produto): 
                        // Nome padronizado para exibição
                        $nomePadronizado = formatarTituloProduto($produto['categoria'], $produto['nome']);

                        // Preparar variação para o CAMINHO DA IMAGEM
                        $variacaoApenas = str_ireplace($produto['categoria'] . ' -', '', $produto['nome']);
                        $variacaoApenas = str_ireplace('incenso', '', $variacaoApenas);
                        $variacaoApenas = trim(str_replace(['"', '&quot;', "'"], '', $variacaoApenas));
                        $variacaoParaImagem = $variacaoApenas;
                        
                        // Link dinâmico para produto por ID
                        $linkProduto = 'produto.php?id=' . (int)$produto['id'];
                    ?>
                        <div class="product-card">
                            <span class="favorite-icon" data-produto-id="<?php echo (int)$produto['id']; ?>" role="button" aria-label="Adicionar aos favoritos">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 21s-6.5-4.35-9.33-7.17C.63 11.79.63 8.21 2.67 6.17c2.04-2.04 5.34-2.04 7.38 0L12 8.12l1.95-1.95c2.04-2.04 5.34-2.04 7.38 0 2.04 2.04 2.04 5.62 0 7.66C18.5 16.65 12 21 12 21z" stroke="#6b7280" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div class="product-image-container">
                                <a href="<?php echo $linkProduto; ?>" class="no-underline" aria-label="Ver produto: <?php echo htmlspecialchars($nomePadronizado); ?>">
                                <img 
                                    src="<?php echo getImagePath($produto['categoria'], $variacaoParaImagem); ?>" 
                                    alt="<?php echo htmlspecialchars($nomePadronizado); ?>"
                                    class="product-image"
                                >
                                </a>
                            </div>
                            <div class="product-info">
                                <h2 class="product-name">
                                    <a href="<?php echo $linkProduto; ?>" class="no-underline">
                                    <?php echo htmlspecialchars($nomePadronizado); ?>
                                    </a>
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
                                        <a href="https://wa.me/5548996107541?text=Olá, tenho interesse no produto: <?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp">
                                            Comprar pelo WhatsApp
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Seção de produtos similares -->
                <?php if (!empty($produtosSimilares)): ?>
                    <div class="similar-products-section">
                        <h3>Produtos que podem interessar você:</h3>
                        <div class="products-grid">
                            <?php foreach ($produtosSimilares as $produto): 
                                // Nome padronizado para exibição
                                $nomePadronizado = formatarTituloProduto($produto['categoria'], $produto['nome']);

                                // Preparar variação para o CAMINHO DA IMAGEM
                                $variacaoApenas = str_ireplace($produto['categoria'] . ' -', '', $produto['nome']);
                                $variacaoApenas = str_ireplace('incenso', '', $variacaoApenas);
                                $variacaoApenas = trim(str_replace(['"', '&quot;', "'"], '', $variacaoApenas));
                                $variacaoParaImagem = $variacaoApenas;
                                
                                // Link dinâmico para produto por ID
                                $linkProduto = 'produto.php?id=' . (int)$produto['id'];
                            ?>
                                <div class="product-card">
                                    <span class="favorite-icon" data-produto-id="<?php echo (int)$produto['id']; ?>" role="button" aria-label="Adicionar aos favoritos">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M12 21s-6.5-4.35-9.33-7.17C.63 11.79.63 8.21 2.67 6.17c2.04-2.04 5.34-2.04 7.38 0L12 8.12l1.95-1.95c2.04-2.04 5.34-2.04 7.38 0 2.04 2.04 2.04 5.62 0 7.66C18.5 16.65 12 21 12 21z" stroke="#6b7280" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <div class="product-image-container">
                                        <a href="<?php echo $linkProduto; ?>" class="no-underline" aria-label="Ver produto: <?php echo htmlspecialchars($nomePadronizado); ?>">
                                        <img 
                                            src="<?php echo getImagePath($produto['categoria'], $variacaoParaImagem); ?>" 
                                            alt="<?php echo htmlspecialchars($nomePadronizado); ?>"
                                            class="product-image"
                                        >
                                        </a>
                                    </div>
                                    <div class="product-info">
                                        <h2 class="product-name">
                                            <a href="<?php echo $linkProduto; ?>" class="no-underline">
                                            <?php echo htmlspecialchars($nomePadronizado); ?>
                                            </a>
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
                                                <a href="https://wa.me/5548996107541?text=Olá, tenho interesse no produto: <?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp">
                                                    Comprar pelo WhatsApp
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // Atualiza o contador e inicializa favoritos quando a página carrega
        document.addEventListener('DOMContentLoaded', function(){
            atualizarContadorCarrinho();
            inicializarFavoritos();
        });
    </script>
    
    <style>
      /* Alinhamento à esquerda nos cards e largura total das seções */
      .product-card .product-info { text-align: left; display: flex; flex-direction: column; align-items: flex-start; width: 100%; }
      .product-card .product-name { width: 100%; margin-bottom: 6px; }
      .product-card .product-name a { display: inline-block; }
      .price-area { width: 100%; }
      .cart-controls { width: 100%; }
      .cart-controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
      .cart-controls { flex-direction: column; align-items: flex-start; }
      .cart-controls .buy-row { display:flex; gap: 10px; align-items: center; }
      .cart-controls .btn-whatsapp { margin-top: 6px; }

      /* Stepper de quantidade */
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
      @media (max-width: 560px) { .price-row { flex-wrap: wrap; gap: 8px; } .price { margin-right: auto; } .cart-controls .btn-whatsapp { width: 100%; } }

      /* Ícone de favoritos (coração em círculo cinza) */
      .product-card { position: relative; }
      .favorite-icon { position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px #e5e7eb; cursor: pointer; }
      .favorite-icon svg { display: block; }
      .favorite-icon:hover { background: #eceff3; }
      .favorite-icon.active svg path { stroke: #e53935; fill: #e53935; }
    </style>
    
    <script>
      // Comportamento do stepper: incrementa/decrementa e garante mínimo de 1
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
