<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$cep_destino = $dados['cep'] ?? '';

// Remove caracteres não numéricos do CEP
$cep_destino = preg_replace('/[^0-9]/', '', $cep_destino);

if (strlen($cep_destino) !== 8) {
    echo json_encode(['erro' => 'CEP inválido. Digite um CEP válido com 8 dígitos.']);
    exit;
}

// Busca os itens do carrinho para calcular peso total
$database = new Database();
$conn = $database->getConnection();

$query = "
    SELECT 
        c.quantidade,
        p.preco,
        p.categoria
    FROM carrinhos c
    JOIN produtos p ON c.produto_id = p.id
    WHERE c.usuario_id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($itens)) {
    echo json_encode(['erro' => 'Carrinho vazio']);
    exit;
}

// Calcula peso total (estimativa: cada unidade = 100g)
$quantidade_total = 0;
$valor_total = 0;
foreach ($itens as $item) {
    $quantidade_total += $item['quantidade'];
    $valor_total += $item['preco'] * $item['quantidade'];
}

$peso_total = $quantidade_total * 0.1; // 100g por unidade = 0.1 kg

// CEP de origem (ajuste para o CEP da sua empresa)
$cep_origem = '01310930'; // Exemplo: Avenida Paulista, São Paulo

// Função para calcular frete de múltiplas transportadoras (simulação)
// Em produção, você pode integrar com APIs como Melhor Envio, Correios, Jadlog, etc.
function calcularFreteMultiplasTransportadoras($cep_origem, $cep_destino, $peso_kg, $valor_declarado) {
    // Validação básica de região
    $regiao_destino = substr($cep_destino, 0, 2);
    
    // Tabela base de preços por região para diferentes transportadoras
    $precos_base = [
        // Sudeste (SP, RJ, MG, ES) - preços mais baixos
        '01' => ['base' => 20], '02' => ['base' => 22], '03' => ['base' => 24], '04' => ['base' => 22], '05' => ['base' => 20],
        '06' => ['base' => 21], '07' => ['base' => 22], '08' => ['base' => 23], '09' => ['base' => 24],
        '20' => ['base' => 25], '21' => ['base' => 25], '22' => ['base' => 27], '23' => ['base' => 27], '24' => ['base' => 27],
        '25' => ['base' => 27], '26' => ['base' => 27], '27' => ['base' => 27], '28' => ['base' => 27], '29' => ['base' => 28],
        '30' => ['base' => 27], '31' => ['base' => 27], '32' => ['base' => 28], '33' => ['base' => 28], '34' => ['base' => 29],
        '35' => ['base' => 29], '36' => ['base' => 30], '37' => ['base' => 30], '38' => ['base' => 30], '39' => ['base' => 31],
        
        // Sul (PR, SC, RS)
        '80' => ['base' => 33], '81' => ['base' => 33], '82' => ['base' => 34], '83' => ['base' => 35], '84' => ['base' => 35],
        '85' => ['base' => 36], '86' => ['base' => 37], '87' => ['base' => 37], '88' => ['base' => 35], '89' => ['base' => 36],
        '90' => ['base' => 37], '91' => ['base' => 37], '92' => ['base' => 38], '93' => ['base' => 39], '94' => ['base' => 39],
        '95' => ['base' => 40], '96' => ['base' => 40], '97' => ['base' => 41], '98' => ['base' => 41], '99' => ['base' => 42],
        
        // Centro-Oeste (GO, DF, MT, MS)
        '70' => ['base' => 35], '71' => ['base' => 35], '72' => ['base' => 36], '73' => ['base' => 37], '74' => ['base' => 37],
        '75' => ['base' => 38], '76' => ['base' => 39], '78' => ['base' => 43], '79' => ['base' => 40],
        
        // Nordeste
        '40' => ['base' => 45], '41' => ['base' => 45], '42' => ['base' => 46], '43' => ['base' => 46], '44' => ['base' => 47],
        '45' => ['base' => 47], '46' => ['base' => 48], '47' => ['base' => 48], '48' => ['base' => 49], '49' => ['base' => 50],
        '50' => ['base' => 47], '51' => ['base' => 47], '52' => ['base' => 48], '53' => ['base' => 48], '54' => ['base' => 49],
        '55' => ['base' => 49], '56' => ['base' => 50], '57' => ['base' => 50], '58' => ['base' => 49], '59' => ['base' => 48],
        '60' => ['base' => 47], '61' => ['base' => 47], '62' => ['base' => 48], '63' => ['base' => 49], '64' => ['base' => 50],
        '65' => ['base' => 51],
        
        // Norte
        '66' => ['base' => 55], '67' => ['base' => 55], '68' => ['base' => 53], '69' => ['base' => 53], '76' => ['base' => 57], '77' => ['base' => 55],
    ];
    
    $preco_base = $precos_base[$regiao_destino]['base'] ?? 50;
    
    // Adiciona valor proporcional ao peso
    $adicional_peso = max(0, ($peso_kg - 1)) * 4; // R$ 4 por kg adicional após o primeiro
    
    // Define as transportadoras disponíveis com seus multiplicadores e características
    $transportadoras = [
        'correios_pac' => [
            'nome' => 'CORREIOS PAC',
            'multiplicador' => 1.0,
            'prazo_base' => 8,
            'prazo_adicional' => 2
        ],
        'correios_sedex' => [
            'nome' => 'CORREIOS SEDEX',
            'multiplicador' => 1.8,
            'prazo_base' => 3,
            'prazo_adicional' => 1
        ],
        'jadlog_package' => [
            'nome' => 'JADLOG PACKAGE',
            'multiplicador' => 0.85,
            'prazo_base' => 6,
            'prazo_adicional' => 2
        ],
        'jadlog_com' => [
            'nome' => 'JADLOG .COM',
            'multiplicador' => 0.75,
            'prazo_base' => 7,
            'prazo_adicional' => 2
        ],
        'loggi_coleta' => [
            'nome' => 'LOGGI COLETA',
            'multiplicador' => 1.2,
            'prazo_base' => 5,
            'prazo_adicional' => 1
        ],
        'jet_standard' => [
            'nome' => 'JET STANDARD',
            'multiplicador' => 0.78,
            'prazo_base' => 6,
            'prazo_adicional' => 2
        ],
        'loggi_loggi_ponto' => [
            'nome' => 'LOGGI LOGGI PONTO',
            'multiplicador' => 0.9,
            'prazo_base' => 5,
            'prazo_adicional' => 1
        ]
    ];
    
    // Calcula preços e prazos para cada transportadora
    $opcoes = [];
    foreach ($transportadoras as $codigo => $dados) {
        $valor = ($preco_base * $dados['multiplicador']) + $adicional_peso;
        $valor = round($valor, 2);
        
        // Calcula prazo baseado na região (mais longe = mais dias)
        $prazo_regiao = calcularPrazoRegiao($regiao_destino);
        $prazo = $dados['prazo_base'] + $prazo_regiao + $dados['prazo_adicional'];
        
        $opcoes[$codigo] = [
            'valor' => $valor,
            'prazo' => $prazo,
            'nome' => $dados['nome']
        ];
    }
    
    // Ordena por preço (mais barato primeiro)
    uasort($opcoes, function($a, $b) {
        return $a['valor'] <=> $b['valor'];
    });
    
    return $opcoes;
}

function calcularPrazoRegiao($regiao) {
    // Dias adicionais baseados na distância da região
    $prazos_regiao = [
        // Sudeste (SP, RJ, MG, ES) - mais próximo
        '01' => 0, '02' => 0, '03' => 1, '04' => 0, '05' => 0,
        '06' => 0, '07' => 1, '08' => 1, '09' => 1,
        '20' => 1, '21' => 1, '22' => 2, '23' => 2, '24' => 2,
        '25' => 2, '26' => 2, '27' => 2, '28' => 2, '29' => 2,
        '30' => 2, '31' => 2, '32' => 3, '33' => 3, '34' => 3,
        '35' => 3, '36' => 3, '37' => 3, '38' => 3, '39' => 3,
        
        // Sul (PR, SC, RS)
        '80' => 2, '81' => 2, '82' => 2, '83' => 3, '84' => 3,
        '85' => 3, '86' => 3, '87' => 3, '88' => 2, '89' => 2,
        '90' => 3, '91' => 3, '92' => 3, '93' => 4, '94' => 4,
        '95' => 4, '96' => 4, '97' => 4, '98' => 4, '99' => 4,
        
        // Centro-Oeste (GO, DF, MT, MS)
        '70' => 2, '71' => 2, '72' => 2, '73' => 3, '74' => 3,
        '75' => 3, '76' => 3, '78' => 4, '79' => 3,
        
        // Nordeste
        '40' => 4, '41' => 4, '42' => 4, '43' => 4, '44' => 5,
        '45' => 5, '46' => 5, '47' => 5, '48' => 5, '49' => 5,
        '50' => 4, '51' => 4, '52' => 4, '53' => 4, '54' => 5,
        '55' => 5, '56' => 5, '57' => 5, '58' => 4, '59' => 4,
        '60' => 4, '61' => 4, '62' => 4, '63' => 5, '64' => 5,
        '65' => 5,
        
        // Norte - mais distante
        '66' => 6, '67' => 6, '68' => 5, '69' => 5, '76' => 7, '77' => 6,
    ];
    
    return $prazos_regiao[$regiao] ?? 5;
}

// Calcula o frete
$opcoes_frete = calcularFreteMultiplasTransportadoras($cep_origem, $cep_destino, $peso_total, $valor_total);

echo json_encode([
    'sucesso' => true,
    'opcoes' => $opcoes_frete,
    'cep' => $cep_destino
]);
