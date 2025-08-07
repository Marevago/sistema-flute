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
    <style>
        /* Estilos base e reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "EB Garamond", serif;
            line-height: 1.6;
            background-color:rgb(241, 240, 157);
            background-image: url('uploads/background04.png');
        }

        /* Cabeçalho fixo */
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
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
            top: 100%;
            left: 0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .dropdown-content a:hover {
            background-color: #f5f5f5;
            color: #2ecc71;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
        }

        .site-title {
            font-size: 24px;
            color: #333;
        }

        /* Menu de navegação */
        .main-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            text-decoration: none;
            color: #333;
            padding: 5px 10px;
        }

        /* Container principal com sidebar */
        .main-container {
            display: flex;
            margin-top: 100px;
            min-height: calc(100vh - 160px);
        }

        .main-text {
            max-width: 800px;
            margin: 60px auto 30px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.4);
            text-align: center;
        }

        .main-quote {
            font-size: 20px;   
        }

        /* Menu lateral de categorias */
        .sidebar {
            width: 150px;
            background-color: white;
            padding: 20px;
            box-shadow: 2px 0 4px rgba(0,0,0,0.4);
        }

        .category-menu {
            list-style: none;
        }

        .category-link {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
            border-radius: 4px;
        }

        .category-link:hover {
            background-color: #f0f0f0;
            color: #2ecc71;
        }

        /* Grade de produtos */
        .products-area {
            flex: 1;
            padding: 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-variations {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .variation-select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .variation-select:hover {
            border-color: #2ecc71;
        }

        .variation-select:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 0 2px rgba(46, 204, 113, 0.1);
        }

        /* Cards de produto */
        .product-card {
            background-image: url('background05.png');
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.4);
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 2px 2px 4px rgba(0,0,0,0.9);

        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .product-name {
            font-size: 18px;
            margin: 10px 0;
        }

        .product-description {
            color: #666;
            margin-bottom: 15px;
        }

        /* Área de preços e carrinho */
        .price-area {
            margin: 15px 0;
        }

        .price {
            font-size: 24px;
            color: #2ecc71;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .cart-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            margin-top: 15px;
        }

        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        /* Botões e interações */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-cart {
            background-color:rgb(255, 208, 0);
            color: black;
        }

        .btn-cart:hover {
            background-color:rgb(255, 153, 0);
        }

        /* Contador do carrinho */
        .cart-icon {
            position: relative;
            display: inline-block;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }

        /* Mensagens de feedback */
        .cart-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }

        .cart-message.success {
            background-color: #2ecc71;
        }

        .cart-message.error {
            background-color: #e74c3c;
        }

        /* Rodapé */
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            width: 100%;
        }


    </style>
</head>
<body>
    <!-- Cabeçalho com logo e navegação -->
    <header class="header">
            <div class="logo-area">
                <img src="uploads/flute_logo.png" alt="Logo da Loja" class="logo">
                <h1 class="site-title">Flute Incensos</h1>
            </div>
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
        </header>

    <!-- Container principal com sidebar e produtos -->
    <div class="main-container">
        <!-- Área principal de produtos -->
        <main class="products-area">
             <div class="main-text">
                <h1>Seja Bem-Vindo!</h1>
                <p class="main-quote">Somos uma empresa voltada para vendas no atacado. Nosso pedido mínimo de compras é de R$300 e alguns produtos é necessário uma quantidade mínima para compra.
                    Se for cliente novo, faça o cadastro básico e você receberá as condições de compras no e-mail de Boas Vindas. 
                    Caso já tenha cadastro no site, para ter acesso aos preços e fazer compras, primeiramente faça login em sua conta com seu e-mail e senha.</p>
            </div>

            <div class="main-text2">
                <h2>Veja Nossos Produtos Disponíveis</h2>

                
                <div class="products-grid">
                    <?php foreach ($featuredProducts as $produto): 
                        $nomeSemIncenso = str_replace('Incenso ', '', $produto['nome']); // Remove "Incenso " do nome
                    ?>
                        <div class="product-card">
                            <img 
                                src="<?php echo getImagePath($nomeSemIncenso); ?>" 
                                alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                class="product-image"
                            >
                            <h2 class="product-name"><?php echo htmlspecialchars($nomeSemIncenso); ?></h2>
                            
                            <?php if (usuarioEstaLogado()): ?>
                                <div class="price-area">
                                    <div class="price">
                                        R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                    </div>
                                    <div class="cart-controls">
                                        <input type="number" min="1" value="1" 
                                            class="quantity-input"
                                            id="qty_<?php echo $produto['id']; ?>">
                                        <button onclick="adicionarAoCarrinho('<?php echo $produto['id']; ?>', '<?php echo htmlspecialchars($nomeSemIncenso); ?>')" 
                                                class="btn btn-cart">
                                            Adicionar ao Carrinho
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="price-restricted">
                                    <p>Faça login para ver o preço</p>
                                    <a href="login.html" class="btn btn-cart">Login</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

        </main>
    </div>

    <!-- Rodapé -->
    <footer class="footer">
        <p>&copy; 2025 Flute Incensos. Todos os direitos reservados.</p>
    </footer>

    <script>
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

        function atualizarPreco(select) {
            const option = select.options[select.selectedIndex];
            const preco = option.getAttribute('data-price');
            const produtoId = select.id.split('_')[1];
            
            const precoElement = select.closest('.product-card').querySelector('.price');
            if (precoElement) {
                precoElement.textContent = `R$ ${parseFloat(preco).toFixed(2).replace('.', ',')}`;
            }
        }

        async function adicionarAoCarrinho(produtoId, variacao = null) {
            if (variacao) {
                const normalizedVariacao = variacao.replace(/\s+/g, '_');
                const quantityId = `qty_${produtoId}_${encodeURIComponent(normalizedVariacao)}`;
                const quantityInput = document.getElementById(quantityId);
                
                if (!quantityInput) {
                    console.error('Elemento de quantidade não encontrado:', quantityId);
                    mostrarMensagem('Erro ao adicionar ao carrinho: quantidade não encontrada', 'error');
                    return;
                }

                const quantidade = quantityInput.value;
                
                try {
                    const response = await fetch('adicionar_ao_carrinho.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            produto_id: produtoId,
                            variacao: variacao,
                            quantidade: quantidade
                        })
                    });

                    const data = await response.json();
                    
                    if (data.sucesso) {
                        mostrarMensagem(`${variacao} adicionado ao carrinho!`, 'success');
                        atualizarContadorCarrinho();
                    } else {
                        mostrarMensagem(data.erro || 'Erro ao adicionar ao carrinho', 'error');
                    }
                } catch (error) {
                    console.error('Erro ao adicionar ao carrinho:', error);
                    mostrarMensagem('Erro ao adicionar ao carrinho: ' + error.message, 'error');
                }
            } else {
                // Outros produtos
                const select = document.getElementById(`variation_${produtoId}`);
                if (select && !select.value) {
                    mostrarMensagem('Por favor, selecione uma fragrância', 'error');
                    return;
                }
                
                const quantidade = document.getElementById(`qty_${produtoId}`).value;
                
                try {
                    const response = await fetch('adicionar_ao_carrinho.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
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
</body>
</html>