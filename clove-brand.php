<?php
// First, we start the session to maintain user login state
session_start();

// Import necessary configuration and classes
require_once 'config/database.php';
require_once 'carrinho.php';

// Function to check if user is logged in
function usuarioEstaLogado() {
    return isset($_SESSION['user_id']);
}

function getImagePath($variacao) {
     // Primeiro, vamos criar um array de substituição para caracteres acentuados
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
     // 1. Primeiro substituímos os caracteres especiais
     $imageName = strtr(mb_strtolower($variacao, 'UTF-8'), $caracteresEspeciais);
    
     // 2. Substituímos espaços por nada (concatenado para seguir o padrão clove<nome>.png)
     $imageName = str_replace(' ', '', $imageName);
     
     // 3. Removemos quaisquer caracteres que não sejam letras ou números
     $imageName = preg_replace('/[^a-z0-9]/', '', $imageName);
     
     // Para debug: vamos ver qual nome de arquivo está sendo gerado
     error_log("Nome original: " . $variacao);
     error_log("Nome convertido: " . $imageName);
     
     // 4. Prefixo 'clove-' e extensão .png no diretório uploads/incensos/clove-brand
     $fileBase = "clove-{$imageName}";
     $imagePath = "uploads/incensos/clove-brand/{$fileBase}.png";

     if (file_exists($imagePath)) {
         $timestamp = filemtime($imagePath);
         return $imagePath . "?v=" . $timestamp;
     }
     
     return "uploads/incensos/default.jpg";
 }

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();
// Buscar todos os produtos Clove Brand
$queryAllCloveBrand = "SELECT * FROM produtos 
                         WHERE categoria = 'Clove Brand' 
                         AND tipo = 'Incensos'
                         ORDER BY nome";
$stmtCloveBrand = $conn->prepare($queryAllCloveBrand);
$stmtCloveBrand->execute();
$cloveBrandProducts = $stmtCloveBrand->fetchAll(PDO::FETCH_ASSOC);

// Get product details from database
$query = "SELECT * FROM produtos WHERE categoria = 'Clove Brand' AND tipo = 'Incensos'";
$stmt = $conn->prepare($query);
$stmt->execute();
$cloveBrandProduct = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clove Brand - Flute Incensos</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Base styles and reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "EB Garamond", serif;
            line-height: 1.6;
            background-color: rgb(241, 240, 157);
            background-image: url('uploads/background04.png');
        }

        /* Header styles */
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

        /* Category-specific styles */
        .main-container {
            margin-top: 120px;
            padding: 0 20px;
        }

        .category-header {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255, 240, 200, 0.95));
            margin: 0 auto 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.4);
            max-width: 800px;
        }

        .category-title {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 15px;
        }

        .variations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-image-container {
            position: relative;
            width: 100%;
            padding-top: 100%; /* Creates a square aspect ratio */
            overflow: hidden;
            border-radius: 8px 8px 0 0;
            background-color: #f8f9fa;
        }

        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* This ensures the image covers the area without distortion */
            transition: transform 0.3s ease;
        }

        .variation-card:hover .product-image {
            transform: scale(1.05);
        }

        .variation-card {
            background-color: white;
            background-image: url('background05.png');
            border-radius: 8px;
            padding: 0; /* Changed from 15px to 0 */
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden; /* Add this to contain the image zoom effect */
        }

        .variation-card .product-name {
            padding: 15px 15px 10px;
            margin: 0;
        }

        .variation-card .price-area {
            padding: 0 15px 15px;
        }

        /* Cart and pricing styles */
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
            background-color: rgb(255, 208, 0);
            color: black;
        }

        .btn-cart:hover {
            background-color: rgb(255, 153, 0);
        }

        /* Search bar */
        .toolbar {
            display: flex;
            gap: 12px;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            max-width: 800px;
        }
        .search-input {
            flex: 1;
            min-width: 240px;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        .chip {
            display: inline-block;
            padding: 6px 10px;
            background: #f1f1f1;
            border-radius: 999px;
            font-size: 12px;
            color: #555;
            margin: 8px 0 0;
        }

        /* Footer styles */
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        /* Cart message styles */
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
    </style>
</head>
<body>
    <!-- Header section -->
    <header class="header">
        <div class="logo-area">
            <img src="uploads/flute_logo.png" alt="Logo da Loja" class="logo">
            <h1 class="site-title">Flute Incensos</h1>
        </div>
        <nav class="main-nav">
            <a href="produtos.php" class="nav-link">Início</a>
            <a href="#" class="nav-link">Sobre</a>
            <a href="#" class="nav-link">Contato</a>
            
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

    <!-- Main content -->
    <div class="main-container">
        <div class="category-header">
            <h1 class="category-title">Clove Brand</h1>
            <p class="category-description">
                Nossa linha premium Clove Brand oferece uma seleção especial de incensos com fragrâncias 
                únicas e envolventes. Cada aroma é cuidadosamente desenvolvido para criar uma atmosfera 
                de tranquilidade e bem-estar, perfeita para momentos de meditação e relaxamento.
            </p>
        </div>

        <div class="toolbar">
            <input id="search-input" type="search" class="search-input" placeholder="Buscar fragrância..." aria-label="Buscar fragrância" />
        </div>

        <div class="variations-grid" id="grid">
            <?php foreach ($cloveBrandProducts as $produto): 
                $nomeSemIncenso = str_replace('Incenso ', '', $produto['nome']); // Remove "Incenso " do nome
            ?>
                <div class="variation-card" data-name="<?php echo mb_strtolower($nomeSemIncenso, 'UTF-8'); ?>">
                    <div class="product-image-container">
                        <img 
                            src="<?php echo getImagePath($nomeSemIncenso); ?>" 
                            alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                            loading="lazy"
                            class="product-image"
                        >
                    </div>
                    <h2 class="product-name"><?php echo htmlspecialchars($nomeSemIncenso); ?></h2>
                    <span class="chip">Clove Brand</span>
                    
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
    </div>

    <!-- Footer section -->
    <footer class="footer">
        <p>&copy; 2025 Flute Incensos. Todos os direitos reservados.</p>
    </footer>

    <!-- JavaScript for cart functionality -->
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

        async function adicionarAoCarrinho(produtoId, variacao) {
            // Normalize the variation name for the quantity input ID
            const normalizedVariacao = variacao.replace(/\s+/g, '_');
            const quantityId = `qty_${produtoId}`;
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
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `produto_id=${produtoId}&quantidade=${quantidade}&variacao=${encodeURIComponent(variacao)}`
                });

                const result = await response.text();
                
                if (response.ok) {
                    mostrarMensagem(`${variacao} adicionado ao carrinho!`, 'success');
                    atualizarContadorCarrinho();
                } else {
                    mostrarMensagem('Erro ao adicionar ao carrinho', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarMensagem('Erro de conexão', 'error');
            }
        }

        async function atualizarContadorCarrinho() {
            try {
                const response = await fetch('get_carrinho_count.php');
                const count = await response.text();
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = count;
                }
            } catch (error) {
                console.error('Erro ao atualizar contador do carrinho:', error);
            }
        }

        // Atualizar contador do carrinho ao carregar a página
        document.addEventListener('DOMContentLoaded', atualizarContadorCarrinho);

        // Busca/filtragem de cards por nome
        const searchInput = document.getElementById('search-input');
        const grid = document.getElementById('grid');
        if (searchInput && grid) {
            searchInput.addEventListener('input', () => {
                const q = searchInput.value.trim().toLowerCase();
                const cards = grid.querySelectorAll('.variation-card');
                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    card.style.display = name.includes(q) ? '' : 'none';
                });
            });
        }
    </script>

    <script src="script.js"></script>
</body>
</html>
