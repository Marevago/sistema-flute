<?php
// Configuração do banco de dados
require_once 'config/database.php';

// Conexão com o banco de dados
$database = new Database();
$conn = $database->getConnection();

// Lista de produtos da linha Clove Brand
$produtos = [
    '30 Ervas', 'Abrindo Caminhos', 'Absinto', 'Alecrim', 'Alfazema', 
    'Almiscar', 'Arruda', 'Arruda Sal Grosso Canforado', 'Atrativo do Amor', 
    'Benjoin', 'Bebê e Carinho', 'Baunilha', 'Bambu', 'Camomila', 'Canela', 
    'Chama Freguês', 'Chamando Dinheiro', 'Chora nos Meus Pés', 'Chocolate', 
    'Citronella', 'Coco', 'Comigo Ninguém Pode', 'Contra Olho Grande e Inveja', 
    'Corre Atrás de Mim', 'Cravo', 'Cravo Canela', 'Dama da Noite', 'Erva Doce', 
    'Eucaliptus', 'Flor de Laranja', 'Folha de Guiné', 'Hortelã', 'Incenso dos Anjos', 
    'Jasmim', 'Lavanda', 'Lótus', 'Lua', 'Maçã Verde', 'Madeira do Oriente', 
    'Mel', 'Melancia', 'Mil Flores', 'Mirra', 'Morango', 'Ópium', 'Patchouli', 
    'Pega Mulher', 'Pega Homem', 'Pomba Gira', 'Quebra Demanda', 'Rosa Amarela', 
    'Rosa Branca', 'Rosa Vermelha', 'Sal Grosso Canforado', 'Sândalo', 
    'Santo Expedito', 'Sete Ervas', 'Sete Ervas com Sal Grosso Canforado', 
    'Sol', 'Verbena', 'Violeta', 'Zé Pilindra'
];

// Preço base para os produtos (ajuste conforme necessário)
$preco = 15.00;

// Inserir produtos
$query = "INSERT INTO produtos (tipo, nome, categoria, preco, descricao) 
          VALUES (:tipo, :nome, :categoria, :preco, :descricao)";
$stmt = $conn->prepare($query);

$contador = 0;
foreach ($produtos as $produto) {
    $stmt->execute([
        ':tipo' => 'produto',
        ':nome' => 'Incenso ' . $produto,
        ':categoria' => 'Clove Brand',
        ':preco' => $preco,
        ':descricao' => 'Incenso ' . $produto . ' da linha Clove Brand'
    ]);
    $contador++;
}

echo "Foram adicionados $contador produtos da linha Clove Brand com sucesso!";
?>
