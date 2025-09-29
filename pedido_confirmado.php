<?php
// Recebe os dados do pedido via URL
$pedido_id = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : 0;
$valor_total = isset($_GET['valor_total']) ? (float)$_GET['valor_total'] : 0;

// Validação básica dos dados
if ($pedido_id <= 0 || $valor_total <= 0) {
    // Redireciona para a página inicial se os dados estão inválidos
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <title>Pedido Confirmado</title>
    <?php // Google Analytics
        $gaInclude = __DIR__ . '/config/analytics.php';
        if (file_exists($gaInclude)) { include $gaInclude; }
    ?>
    <style>
        /* Use os mesmos estilos base das outras páginas */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
            margin: 1rem;
            padding: 20px;
            background-image: url('uploads/background04.png');
        }

        .logo {
            display: block;
            margin: 0 auto;
        }

        .success-message {
            color:rgb(174, 143, 49);
            font-size: 24px;
            margin-bottom: 20px;
            font-family: 'EB Garamond', serif;
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .details {
            color: #666;
            margin: 20px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: rgb(174, 143, 49);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="logo-area">
        <a href="index.php">
            <img src="uploads/flute_logo.png" alt="Logo da loja" width="200" class="logo">
        </a>
    <div class="confirmation-container">
        <h1 class="success-message">Pedido Confirmado!</h1>
        <p>Obrigado por sua compra. Seu pedido foi recebido com sucesso!</p>
        <div class="details">
            <p><strong>Número do Pedido:</strong> #<?php echo $pedido_id; ?></p>
            <p><strong>Valor Total:</strong> R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></p>
            <p>Entraremos em contato em breve para combinar os detalhes do pagamento e entrega.</p>
            <p>Fique atento ao seu email e telefone.</p>
        </div>
        <a href="produtos.php" class="btn">Voltar para a Loja</a>
    </div>

    <!-- Google Ads Conversion Tracking -->
    <script>
      gtag('event', 'conversion', {
          'send_to': 'AW-17592109818/PbRLCMCi9Z8bEPqVycRB',
          'value': <?php echo $valor_total; ?>,
          'currency': 'BRL',
          'transaction_id': '<?php echo $pedido_id; ?>'
      });
    </script>
</body>
</html>