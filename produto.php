<?php
// Página dinâmica de Produto por ID
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'carrinho.php';

function usuarioEstaLogado() { return isset($_SESSION['user_id']); }

$produtoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($produtoId <= 0) {
    http_response_code(400);
    echo 'Produto não especificado.';
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $produtoId]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    http_response_code(404);
    echo 'Produto não encontrado.';
    exit;
}

$nomePadronizado = formatarTituloProduto($produto['categoria'], $produto['nome']);
$variacaoParaImagem = str_ireplace($produto['categoria'] . ' -', '', $produto['nome']);
$variacaoParaImagem = str_ireplace('incenso', '', $variacaoParaImagem);
$variacaoParaImagem = trim(str_replace(['"', "'", '&quot;'], '', $variacaoParaImagem));

$relStmt = $conn->prepare("SELECT * FROM produtos WHERE categoria = :cat AND id <> :id ORDER BY RAND() LIMIT 4");
$relStmt->execute([':cat' => $produto['categoria'], ':id' => $produto['id']]);
$relacionados = $relStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nomePadronizado); ?> - Flute Incensos</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.2">
    <?php include __DIR__ . '/config/analytics.php'; ?>
    <style>
      .product-page { max-width: 1200px; margin: 24px auto; padding: 0 16px; }
      .pp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
      .pp-gallery { display: flex; flex-direction: column; gap: 12px; align-items: center; }
      .pp-image { width: 100%; max-width: 420px; object-fit: contain; background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,.06); }
      .pp-title { font-family: "EB Garamond", serif; font-size: 28px; margin: 8px 0 12px; }
      .pp-code { color: #7a7a7a; font-size: 13px; margin-bottom: 16px; }
      .pp-price { font-size: 24px; font-weight: 600; color: #e65100; margin: 8px 0 16px; }
      .pp-desc { max-width: 900px; margin: 40px auto; padding: 0 16px; }
      .pp-desc h3 { text-align:center; font-family: "EB Garamond", serif; font-size: 28px; margin-bottom: 16px; }
      .pp-specs { max-width: 900px; margin: 24px auto; padding: 0 16px; }
      .pp-specs h4 { text-align:center; margin-bottom: 12px; }
      .pp-specs .box { background:#fff; border-radius:8px; padding:16px; box-shadow:0 2px 10px rgba(0,0,0,.06); }
      .pp-related { max-width: 1200px; margin:40px auto; padding: 0 16px; }
      .pp-related h3 { text-align:center; margin-bottom: 16px; font-family: "EB Garamond", serif; }
      .pp-related .grid { display:grid; grid-template-columns: repeat(4, 1fr); gap:16px; }
      .pp-related .card { background:#fff; border-radius:8px; padding:12px; box-shadow:0 2px 10px rgba(0,0,0,.06); text-align:center; }
      .pp-related .card img { width:100%; max-width:180px; object-fit:contain; }
      @media (max-width: 900px) { .pp-grid { grid-template-columns: 1fr; } .pp-related .grid{ grid-template-columns: repeat(2,1fr);} }
    </style>
    <script>
        const USER_LOGGED_IN = <?php echo usuarioEstaLogado() ? 'true' : 'false'; ?>;
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const mainNav = document.getElementById('main-nav');
            const backdrop = document.getElementById('backdrop');
            if (menuToggle && mainNav && backdrop) {
                const openMenu = () => { mainNav.classList.add('open'); backdrop.classList.add('open'); menuToggle.setAttribute('aria-expanded','true'); document.body.style.overflow='hidden'; };
                const closeMenu = () => { mainNav.classList.remove('open'); backdrop.classList.remove('open'); menuToggle.setAttribute('aria-expanded','false'); document.body.style.overflow=''; document.querySelectorAll('.main-nav .dropdown.open').forEach(d=>d.classList.remove('open')); };
                menuToggle.addEventListener('click', (e)=>{ e.preventDefault(); mainNav.classList.contains('open') ? closeMenu() : openMenu(); });
                backdrop.addEventListener('click', closeMenu);
                document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && mainNav.classList.contains('open')) closeMenu(); });
            }
            atualizarContadorCarrinho();
        });
        window.addEventListener('storage', (e) => { if (e.key === 'cart_updated') atualizarContadorCarrinho(); });
        window.addEventListener('pageshow', atualizarContadorCarrinho);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) atualizarContadorCarrinho(); });
        function atualizarContadorCarrinho() {
            fetch('contar_itens_carrinho.php').then(r=>r.json()).then(data=>{
                const contador=document.querySelector('.cart-count');
                if(contador){ contador.textContent = data.quantidade||'0'; contador.style.display = data.quantidade>0?'flex':'none'; }
            }).catch(()=>{});
        }
        function mostrarMensagem(mensagem, tipo){ const msg=document.createElement('div'); msg.className=`cart-message ${tipo}`; msg.textContent=mensagem; document.body.appendChild(msg); setTimeout(()=>msg.style.opacity='1',100); setTimeout(()=>{ msg.style.opacity='0'; setTimeout(()=>msg.remove(),300); },3000); }
        window.adicionarAoCarrinho = async function(produtoId, nomeProduto){ const qtyEl=document.getElementById(`qty_${produtoId}`); const quantidade=qtyEl?parseInt(qtyEl.value,10):1; if(quantidade<1){mostrarMensagem('A quantidade deve ser de pelo menos 1.','error'); return;} try{ const resp=await fetch('adicionar_ao_carrinho.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ produto_id: produtoId, quantidade })}); const data=await resp.json(); if(data.sucesso){ mostrarMensagem(`${nomeProduto} adicionado ao carrinho!`, 'success'); atualizarContadorCarrinho(); try{ localStorage.setItem('cart_updated', Date.now().toString()); }catch(e){} } else { mostrarMensagem(data.erro||'Erro ao adicionar ao carrinho.','error'); } }catch(e){ mostrarMensagem('Erro ao adicionar ao carrinho: '+e.message,'error'); } };
    </script>
    <style>
        .cart-message { position:fixed; top:20px; right:20px; z-index:9999; background:#333; color:#fff; padding:12px 16px; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,.2); opacity:0; transition:opacity .3s ease; }
        .cart-message.success{ background:#2e7d32 }
        .cart-message.error{ background:#c62828 }
    </style>
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
                <button class="menu-toggle" id="menu-toggle" aria-label="Abrir menu" aria-expanded="false"><span></span></button>
                <div class="logo-area"><a href="index.php"><img src="uploads/flute_logo.png" alt="Logo Flute Incensos" class="logo"></a></div>
                <div class="search-bar-area">
                    <form class="search-bar" action="buscar.php" method="get">
                        <input type="text" name="q" placeholder="Digite o que você procura..." aria-label="Buscar produtos">
                        <button type="submit" aria-label="Buscar" class="search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </button>
                    </form>
                </div>
                <div class="header-actions-area">
                    <?php if (usuarioEstaLogado()): ?>
                        <a href="minha-conta.php" class="action-icon user-account" title="Minha Conta">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                        </a>
                        <a href="logout.php" class="action-link">Sair</a>
                    <?php else: ?>
                        <a href="login.html" class="action-icon user-account" title="Entrar ou Cadastrar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Entrar</span>
                        </a>
                    <?php endif; ?>
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

    <main class="product-page">
        <div class="pp-grid">
            <section class="pp-gallery">
                <img class="pp-image" src="<?php echo getImagePath($produto['categoria'], $variacaoParaImagem); ?>" alt="<?php echo htmlspecialchars($nomePadronizado); ?>">
            </section>
            <section class="pp-info">
                <h1 class="pp-title"><?php echo htmlspecialchars($nomePadronizado); ?></h1>
                <div class="pp-code">Cód.: <?php echo (int)$produto['id']; ?></div>
                <?php if (usuarioEstaLogado()): ?>
                    <div class="pp-price">R$ <?php echo number_format((float)$produto['preco'], 2, ',', '.'); ?></div>
                    <div class="cart-controls">
                        <div class="quantity-selector">
                            <label for="qty_<?php echo $produto['id']; ?>">Qtd:</label>
                            <input type="number" id="qty_<?php echo $produto['id']; ?>" class="quantity-input" value="1" min="1">
                        </div>
                        <button onclick="adicionarAoCarrinho('<?php echo $produto['id']; ?>','<?php echo htmlspecialchars($nomePadronizado); ?>')" class="btn btn-cart">Comprar</button>
                        <a href="https://wa.me/5548996107541?text=Olá, tenho interesse no produto: <?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp">Comprar pelo WhatsApp</a>
                    </div>
                <?php else: ?>
                    <div class="cart-controls">
                        <a href="login.html" class="btn btn-cart">Ver preço</a>
                        <a href="https://wa.me/5548996107541?text=Olá, tenho interesse no produto: <?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp">Comprar pelo WhatsApp</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <section class="pp-desc">
            <h3>Descrição</h3>
            <p><strong><?php echo htmlspecialchars($nomePadronizado); ?></strong> integra nossa linha de produtos. Aroma ideal para harmonizar ambientes e elevar o bem‑estar.</p>
        </section>

        <section class="pp-specs">
            <h4>Especificações</h4>
            <div class="box">
                <p><strong>PRODUTO:</strong> <?php echo htmlspecialchars($nomePadronizado); ?></p>
                <p><strong>QUANTIDADE:</strong> 1 caixinha (conteúdo padrão da linha)</p>
                <p><strong>COMPOSIÇÃO:</strong> Ingredientes tradicionais de incenso (madeira, resinas e aromas)</p>
            </div>
        </section>

        <?php if ($relacionados && count($relacionados) > 0): ?>
        <section class="pp-related">
            <h3>Aproveite e compre também</h3>
            <div class="grid">
                <?php foreach ($relacionados as $rel): 
                    $nomeRel = formatarTituloProduto($rel['categoria'], $rel['nome']);
                    $varRel = str_ireplace($rel['categoria'] . ' -', '', $rel['nome']);
                    $varRel = str_ireplace('incenso', '', $varRel);
                    $varRel = trim(str_replace(['"', "'", '&quot;'], '', $varRel));
                ?>
                <div class="card">
                    <img src="<?php echo getImagePath($rel['categoria'], $varRel); ?>" alt="<?php echo htmlspecialchars($nomeRel); ?>">
                    <div style="margin-top:8px; font-size:14px; line-height:1.2; min-height:38px;"><?php echo htmlspecialchars($nomeRel); ?></div>
                    <?php if (usuarioEstaLogado()): ?>
                        <div style="margin:6px 0; font-weight:600; color:#e65100;">R$ <?php echo number_format((float)$rel['preco'], 2, ',', '.'); ?></div>
                        <div class="cart-controls" style="justify-content:center;">
                            <div class="quantity-selector">
                                <label for="qty_<?php echo $rel['id']; ?>">Qtd:</label>
                                <input type="number" id="qty_<?php echo $rel['id']; ?>" class="quantity-input" value="1" min="1">
                            </div>
                            <button onclick="adicionarAoCarrinho('<?php echo $rel['id']; ?>','<?php echo htmlspecialchars($nomeRel); ?>')" class="btn btn-cart">Comprar</button>
                            <a href="https://wa.me/5548996107541?text=Olá, tenho interesse no produto: <?php echo urlencode($nomeRel); ?>" target="_blank" class="btn btn-whatsapp">WhatsApp</a>
                        </div>
                    <?php else: ?>
                        <div class="cart-controls" style="justify-content:center;">
                            <a href="login.html" class="btn btn-cart">Ver preço</a>
                            <a href="https://wa.me/5548996107541?text=Olá, tenho interesse no produto: <?php echo urlencode($nomeRel); ?>" target="_blank" class="btn btn-whatsapp">WhatsApp</a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
