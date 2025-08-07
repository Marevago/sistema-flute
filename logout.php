<?php
// Inicia a sessão (precisamos acessar a sessão antes de destruí-la)
session_start();

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói o cookie da sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header('Location: login.html');
exit;
?>