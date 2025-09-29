<?php
session_start();
?>
<header class="site-header">
    <div class="header-top-bar">
        <div class="container">
            <p>Seja um distribuidor Flute! Entre em contato conosco.</p>
            <a href="central-atendimento.php" class="action-link">Central de Atendimento</a>
        </div>
    </div>
    <div class="header-main">
        <div class="container">
            <button class="menu-toggle" id="menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <span></span>
            </button>
            <div class="logo-area">
                <a href="index.php">
                    <img src="uploads/flute_logo.png" alt="Logo Flute Incensos" class="logo">
                </a>
            </div>
            <div class="search-bar-area">
                <form class="search-bar" action="buscar.php" method="get">
                    <input type="text" name="q" placeholder="Digite o que você procura..." aria-label="Buscar produtos">
                    <button type="submit" aria-label="Buscar" class="search-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
            <div class="header-actions-area">
                <a href="minha-conta.php" class="action-icon user-account" title="Minha Conta">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span>Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                    <?php else: ?>
                        <span>Minha Conta</span>
                    <?php endif; ?>
                </a>
                <a href="carrinho-pagina.php" class="action-icon cart-mini" title="Carrinho">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
            </div>
        </div>
    </div>
    <nav class="main-nav" id="main-nav">
        <div class="container">
            <!-- Barra de busca mobile -->
            <div class="mobile-search-area">
                <form class="mobile-search-bar" action="buscar.php" method="get">
                    <input type="text" name="q" placeholder="Digite o que você procura..." aria-label="Buscar produtos">
                    <button type="submit" aria-label="Buscar" class="mobile-search-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
            
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Início</a></li>
                <li class="nav-item dropdown">
                    <a href="produtos.php" class="nav-link">Produtos</a>
                    <div class="dropdown-content">
                        <a href="regular-square.php?cat=regular-square">Regular Square</a>
                        <a href="masala-square.php?cat=masala-square">Masala Square</a>
                        <a href="xamanico-tube.php?cat=incenso-xamanico">Incenso Xamânico Tube</a>
                        <a href="cycle-brand-regular.php?cat=cycle-brand-regular">Cycle Brand Regular</a>
                        <a href="long-square.php?cat=long-square">Long Square</a>
                        <a href="cycle-brand-rectangle.php?cat=cycle-brand-rectangle">Cycle Brand Rectangle</a>
                        <a href="masala-small.php?cat=masala-small">Masala Small Packet</a>
                        <a href="clove-brand.php?cat=clove-brand">Clove Brand</a>
                        <a href="produtos.php">Ver Todos</a>
                    </div>
                </li>
                <li><a href="sobre.php" class="nav-link">Sobre Nós</a></li>
                <li><a href="contato.php" class="nav-link">Contato</a></li>
            </ul>
        </div>
    </nav>
    <div class="backdrop" id="backdrop"></div>
</header>
