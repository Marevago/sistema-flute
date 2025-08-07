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
    
    // Define o caminho da imagem - Note a mudança para pasta masala-square
    $imagePath = "uploads/incensos/cycle-brand-rectangle/{$imageName}.jpg";

    if (file_exists($imagePath)) {
        $timestamp = filemtime($imagePath);
        return $imagePath . "?v=" . $timestamp;
    }
    
    return file_exists($imagePath) ? $imagePath : "uploads/incensos/default.jpg";
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Na query de busca dos produtos
$queryCycleRectangle = "SELECT * FROM produtos 
                        WHERE categoria = 'Cycle Brand Rectangle' 
                        AND tipo = 'produto'
                        ORDER BY nome";
$stmtCycleRectangle = $conn->prepare($queryCycleRectangle);
$stmtCycleRectangle->execute();
$cycleRectangleProducts = $stmtCycleRectangle->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cycle Brand Rectangle - Flute Incensos</title>
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
            background-color: white;
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
            transition: transform 0.2s;
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
            <h1 class="category-title">Cycle Brand Rectangle</h1>
            <p class="category-description">
                Nossa linha Cycle Brand Rectangle apresenta incensos retangulares da marca Cycle, 
                com fragrâncias exclusivas e tradicionais da Índia.
            </p>
        </div>

        <div class="variations-grid">
            <?php foreach ($cycleRectangleProducts as $produto): 
                $nomeSemIncenso = str_replace('Incenso ', '', $produto['nome']); // Remove "Incenso " do nome
            ?>
                <div class="variation-card">
                    <div class="product-image-container">
                        <img 
                            src="<?php echo getImagePath($nomeSemIncenso); ?>" 
                            alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                            class="product-image"
                        >
                    </div>
                    <h2 class="product-name"><?php echo htmlspecialchars($nomeSemIncenso); ?></h2>
                    
                    <?php if (usuarioEstaLogado()): ?>
                        <div class="price-area">
                            <div class="price">
                                R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                            </div>
                            <div class="cart-controls">
                                <input type="number" min="1" value="1" 
                                    class="quantity-input"
                                    id="qty_<?php echo $produto['id']; ?>_<?php echo urlencode(str_replace(' ', '_', $nomeSemIncenso)); ?>">                                <button onclick="adicionarAoCarrinho('<?php echo $produto['id']; ?>', '<?php echo htmlspecialchars($nomeSemIncenso); ?>')" 
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

            // Make sure the message is visible
            msg.style.opacity = '1';
            
            setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            }, 3000);
        }

        async function adicionarAoCarrinho(produtoId, variacao = null) {
            if (variacao) {
                // Caso Masala Square
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
            }
        }

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

        // Update cart count when page loads
        document.addEventListener('DOMContentLoaded', atualizarContadorCarrinho);
    </script>
</body>
</html>