<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$query = $_GET['q'] ?? '';
$produtos = [];

if (!empty($query)) {
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $searchTerm = '%' . $query . '%';
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE nome LIKE :query OR descricao LIKE :query ORDER BY nome ASC");
        $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Silenciosamente ignora o erro, a página mostrará 'nenhum resultado encontrado'
        error_log("Erro na busca: " . $e->getMessage());
    }
}

function formatCurrencyBR($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

$page_title = 'Resultados da Busca por "' . htmlspecialchars($query) . '"';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Flute Incensos</title>
    <?php 
        $gaInclude = __DIR__ . '/config/analytics.php';
        if (file_exists($gaInclude)) { include $gaInclude; }
    ?>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.2">
    <style>
        .search-results-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .results-header { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; display: flex; flex-direction: column; }
        .product-card img { width: 100%; height: 200px; object-fit: contain; padding: 10px; }
        .product-info { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-info h3 { margin: 0 0 10px; font-size: 1.1em; }
        .product-info .price { font-weight: 600; color: #2c3e50; margin-top: auto; }
        .no-results { text-align: center; padding: 40px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="search-results-container">
        <div class="results-header">
            <h1>Resultados da Busca</h1>
            <p>Você buscou por: <strong>"<?php echo htmlspecialchars($query); ?>"</strong></p>
        </div>

        <?php if (!empty($produtos)): ?>
            <div class="products-grid">
                <?php foreach ($produtos as $produto): ?>
                    <div class="product-card">
                        <img src="<?php echo getImagePath($produto['categoria'], $produto['nome']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                            <p class="price"><?php echo formatCurrencyBR($produto['preco']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h2>Nenhum resultado encontrado</h2>
                <p>Tente buscar por um termo diferente ou navegue por nossas categorias.</p>
                <a href="produtos.php" class="btn" style="background-color:#3498db; color:white;">Ver todos os produtos</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
