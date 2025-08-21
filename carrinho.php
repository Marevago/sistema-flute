<?php
// carrinho.php
class CarrinhoHandler {
    private $conn;
    private $valor_minimo = 600.00;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Adiciona um produto ao carrinho
    public function adicionarProduto($produto_id, $quantidade, $variacao) {
        if (!isset($_SESSION['user_id'])) {
            return ['erro' => 'Usuário precisa estar logado'];
        }
    
        try {
            // Verifica se o produto já está no carrinho com a mesma variação
            $stmt = $this->conn->prepare("
                SELECT id, quantidade 
                FROM carrinhos 
                WHERE usuario_id = ? AND produto_id = ? AND variacao = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $produto_id, $variacao]);
            $item_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($item_existente) {
                // Atualiza a quantidade se já existe
                $nova_quantidade = $item_existente['quantidade'] + $quantidade;
                $stmt = $this->conn->prepare("
                    UPDATE carrinhos 
                    SET quantidade = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$nova_quantidade, $item_existente['id']]);
            } else {
                // Insere novo item no carrinho
                $stmt = $this->conn->prepare("
                    INSERT INTO carrinhos (usuario_id, produto_id, quantidade, variacao) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $produto_id, $quantidade, $variacao]);
            }
    
            return ['sucesso' => 'Produto adicionado ao carrinho'];
        } catch (PDOException $e) {
            return ['erro' => 'Erro ao adicionar produto: ' . $e->getMessage()];
        }
    }
}
?>