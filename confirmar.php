<?php
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    
    // Verificar o e-mail no banco de dados e liberar o acesso
    // Exemplo simplificado:
    
    // Atualizar o status do usuário no banco de dados para "ativo"
    
    echo "Cadastro confirmado! Agora você pode acessar o conteúdo exclusivo.";
}
?>
