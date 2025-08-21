<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - Flute Incensos</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.2">
    <style>
        .about-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .about-hero { background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin: 12px 0 20px; }
        .about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .about-card { background: #fff; border-radius: 10px; padding: 20px; border: 1px solid #eee; }
        .about-card h3 { margin-top: 0; }
        .mv-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .mv-item { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 16px; }
        .mv-item h4 { margin: 0 0 8px; }
        .about-list { margin: 0; padding-left: 18px; }
        .about-list li { margin-bottom: 6px; }
        /* Fundo igual ao da tela de login */
        body {
            background-color: #f7f7f8;
            background-image: url('uploads/background04.png');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            background-attachment: fixed;
        }
        @media (max-width: 900px) {
            .about-grid { grid-template-columns: 1fr; }
            .mv-grid { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const mainNav = document.getElementById('main-nav');
            const backdrop = document.getElementById('backdrop');

            if (menuToggle && mainNav && backdrop) {
                const openMenu = () => {
                    mainNav.classList.add('open');
                    backdrop.classList.add('open');
                    menuToggle.setAttribute('aria-expanded', 'true');
                    document.body.style.overflow = 'hidden';
                };
                const closeMenu = () => {
                    mainNav.classList.remove('open');
                    backdrop.classList.remove('open');
                    menuToggle.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                    document.querySelectorAll('.main-nav .dropdown.open').forEach(d => d.classList.remove('open'));
                };
                menuToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (mainNav.classList.contains('open')) closeMenu(); else openMenu();
                });
                backdrop.addEventListener('click', closeMenu);
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && mainNav.classList.contains('open')) closeMenu(); });
            }

            const dropdownsInNav = document.querySelectorAll('.main-nav .nav-item.dropdown');
            dropdownsInNav.forEach(dropdown => {
                const link = dropdown.querySelector('a');
                link.addEventListener('click', function(e) {
                    if (window.matchMedia('(max-width: 768px)').matches && document.getElementById('main-nav').classList.contains('open')) {
                        e.preventDefault();
                        dropdown.classList.toggle('open');
                    }
                });
            });

            atualizarContadorCarrinho();
        });
        // Sync do carrinho entre páginas
        window.addEventListener('storage', (e) => { if (e.key === 'cart_updated') atualizarContadorCarrinho(); });
        window.addEventListener('pageshow', atualizarContadorCarrinho);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) atualizarContadorCarrinho(); });

        function atualizarContadorCarrinho() {
            fetch('contar_itens_carrinho.php')
                .then(r => r.json())
                .then(data => {
                    const el = document.querySelector('.cart-count');
                    if (el) {
                        el.textContent = data.quantidade || '0';
                        el.style.display = (data.quantidade > 0) ? 'flex' : 'none';
                    }
                })
                .catch(err => console.error('Erro ao atualizar contador:', err));
        }
    </script>
</head>
<body>
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
                        <span>Minha Conta</span>
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

    <main class="about-container">
        <section class="about-hero">
            <h1>Sobre a Flute Incensos</h1>
            <p>Somos o <strong>Distribuidor Oficial no Brasil</strong> da <strong>marca indiana Flute</strong>, referência mundial em incensos. Atendemos o atacado com portfólio completo, reposição contínua e logística ágil para todo o país.</p>
        </section>

        <div class="about-grid">
            <section class="about-card">
                <h3>Nossa História</h3>
                <p>Ao longo dos anos, consolidamos a parceria com a <strong>Flute (Índia)</strong>, passando a representá-la oficialmente no Brasil. Desde então, ampliamos o catálogo autorizado, fortalecemos a logística e mantemos um padrão rigoroso de qualidade e originalidade.</p>
                <ul class="about-list">
                    <li>Portfólio oficial das linhas Flute mais procuradas</li>
                    <li>Estoque com alta disponibilidade</li>
                    <li>Envio rápido e seguro</li>
                </ul>
            </section>
            <section class="about-card">
                <h3>Como Trabalhamos</h3>
                <p>Atendemos lojistas e revendedores com condições exclusivas para o atacado, <strong>garantia de procedência</strong> e materiais oficiais da marca Flute. Nosso time acompanha do pedido à entrega para assegurar a melhor experiência.</p>
                <ul class="about-list">
                    <li>Atendimento próximo e transparente</li>
                    <li>Condições comerciais competitivas e oficiais</li>
                    <li>Suporte pós-venda</li>
                </ul>
            </section>
        </div>

        <section class="about-card" style="margin-top:20px;">
            <div class="mv-grid">
                <div class="mv-item">
                    <h4>Missão</h4>
                    <p>Representar oficialmente a Flute no Brasil e fornecer incensos originais com agilidade e confiança, fortalecendo o negócio dos nossos clientes.</p>
                </div>
                <div class="mv-item">
                    <h4>Visão</h4>
                    <p>Ser a principal referência nacional em distribuição de incensos Flute, reconhecida pela curadoria oficial e excelência logística.</p>
                </div>
                <div class="mv-item">
                    <h4>Valores</h4>
                    <p>Originalidade, parceria, compromisso e melhoria contínua em tudo o que fazemos.</p>
                </div>
            </div>
        </section>

        <section class="about-card" style="margin-top:20px;">
            <h3>Entre em Contato</h3>
            <p>Quer se tornar um distribuidor ou tirar dúvidas sobre nosso catálogo? Fale com a nossa equipe.</p>
            <p><a class="btn btn-cart" href="contato.php">Fale Conosco</a></p>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
