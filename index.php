<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

$database = new Database();
$conn = $database->getConnection();

// Produtos em destaque (fallback: últimos cadastrados)
$produtos = [];
try {
    // Um produto por categoria (o mais recente por categoria)
    $sql = "SELECT p.id, p.nome, p.preco, p.categoria
            FROM produtos p
            INNER JOIN (
                SELECT categoria, MAX(id) AS max_id
                FROM produtos
                WHERE tipo = 'produto' AND categoria IS NOT NULL AND categoria <> ''
                GROUP BY categoria
            ) t ON t.categoria = p.categoria AND t.max_id = p.id
            WHERE p.tipo = 'produto'
            ORDER BY p.categoria";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $produtos = []; }

// Monta imagens por categoria para os cards de "Categorias em destaque"
$catMap = [
    'regular-square' => [
        'label'    => 'Regular Square',
        'href'     => 'regular-square.php?cat=regular-square',
        'dbCat'    => 'Regular Square', // nome exatamente como está no banco
        'imgCat'   => 'regular-square', // slug para getImagePath
        'fallbackImg' => 'uploads/cats_regular_square.jpg',
        'imgDir'  => 'regular-square',
    ],
    'masala-square' => [
        'label'    => 'Masala Square',
        'href'     => 'masala-square.php?cat=masala-square',
        'dbCat'    => 'Masala Square',
        'imgCat'   => 'masala-square',
        'fallbackImg' => 'uploads/cats_masala_square.jpg',
        'imgDir'  => 'masala-square',
    ],
    'incenso-xamanico' => [
        'label'    => 'Incenso Xamânico',
        'href'     => 'xamanico-tube.php?cat=incenso-xamanico',
        'dbCat'    => 'Incenso Xamânico',
        'imgCat'   => 'incenso-xamanico',
        'fallbackImg' => 'uploads/cats_xamanico_tube.jpg',
        // pasta física usada pelas imagens desta categoria
        'imgDir'  => 'xamanico-tube',
    ],
    'cycle-brand-regular' => [
        'label'    => 'Cycle Brand Regular',
        'href'     => 'cycle-brand-regular.php?cat=cycle-brand-regular',
        'dbCat'    => 'Cycle Brand Regular',
        'imgCat'   => 'cycle-brand-regular',
        'fallbackImg' => 'uploads/cats_cycle_regular.jpg',
        'imgDir'  => 'cycle-brand-regular',
    ],
];

$catSlides = [];
try {
    $stmtCat = $conn->prepare("SELECT nome FROM produtos WHERE tipo = 'produto' AND categoria = :cat ORDER BY id DESC LIMIT 6");
    foreach ($catMap as $key => $info) {
        $imgs = [];
        $stmtCat->execute([':cat' => $info['dbCat']]);
        $rows = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $img = getImagePath($info['imgCat'], $r['nome']);
            if ($img && is_string($img)) {
                $clean = strtok($img, '?');
                // ignora fallbacks genéricos
                if (strpos($clean, 'uploads/flute_logo.png') !== false) continue;
                if (strpos($clean, 'uploads/incensos/default.jpg') !== false) continue;
                // valida existência do arquivo
                $fs = __DIR__ . '/' . ltrim($clean, '/');
                if (file_exists($fs)) { $imgs[] = $img; }
            }
        }
        // Se não houver imagens válidas a partir do BD, varre a pasta física da categoria
        if (empty($imgs) && !empty($info['imgDir'])) {
            $dirPath = __DIR__ . '/uploads/incensos/' . $info['imgDir'];
            if (is_dir($dirPath)) {
                $collected = [];
                $exts = ['jpg','jpeg','png','webp'];
                foreach ($exts as $ext) {
                    foreach (glob($dirPath . '/*.' . $ext) as $matchFs) {
                        if (!is_file($matchFs)) continue;
                        $mtime = @filemtime($matchFs) ?: time();
                        // caminho relativo web
                        $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $matchFs);
                        $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
                        // ignora logos/arquivos genéricos
                        if (strpos($rel, 'uploads/flute_logo.png') !== false) continue;
                        if (strpos($rel, 'uploads/incensos/default.jpg') !== false) continue;
                        $collected[] = $rel . '?v=' . $mtime;
                        if (count($collected) >= 10) break 2; // limita
                    }
                }
                if (!empty($collected)) { $imgs = $collected; }
            }
        }
        // se ainda estiver vazio, tenta fallback estático se existir
        if (empty($imgs) && !empty($info['fallbackImg'])) {
            $fallbackClean = $info['fallbackImg'];
            $fs = __DIR__ . '/' . ltrim($fallbackClean, '/');
            if (file_exists($fs)) { $imgs[] = $fallbackClean; }
        }
        $catSlides[$key] = $imgs;
    }
} catch (Exception $e) {
    foreach ($catMap as $key => $info) { $catSlides[$key] = ['uploads/flute_logo.png']; }
}

// Lista completa de categorias (para "Navegue por linhas")
function slugifyCategoria($s) {
    $s = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/','-',$s);
    $s = trim($s,'-');
    return $s ?: 'categoria';
}

$catDbToSlug = function($dbName) use ($catMap) {
    $norm = function($s){
        $s = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
        $s = strtolower($s);
        return preg_replace('/[^a-z0-9]+/',' ', $s);
    };
    $nDb = trim($norm($dbName));
    // 1) match por dbCat
    foreach ($catMap as $slug => $info) {
        if (isset($info['dbCat']) && strcasecmp($info['dbCat'], $dbName) === 0) {
            return $slug;
        }
    }
    // 2) match por label aproximado
    foreach ($catMap as $slug => $info) {
        if (!empty($info['label'])) {
            $nLbl = trim($norm($info['label']));
            if ($nLbl && (strpos($nDb, $nLbl) !== false || strpos($nLbl, $nDb) !== false)) {
                return $slug;
            }
        }
    }
    // 3) heurísticas conhecidas
    if (strpos($nDb,'xamanic') !== false) return 'xamanico-tube';
    if (strpos($nDb,'cycle') !== false && strpos($nDb,'regular') !== false) return 'cycle-brand-regular';
    if (strpos($nDb,'masala') !== false && (strpos($nDb,'small') !== false || strpos($nDb,'packet') !== false)) return 'masala-small-packet';
    // 4) fallback slugificado
    return slugifyCategoria($dbName);
};

$scanFirstImage = function($slug) use ($catMap) {
    $toRel = function($abs){
        $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $abs);
        return str_replace(DIRECTORY_SEPARATOR, '/', $rel);
    };
    $baseDir = __DIR__ . '/uploads/incensos/';
    $tryDirs = [];
    if (isset($catMap[$slug]['imgDir'])) { $tryDirs[] = $catMap[$slug]['imgDir']; }
    $tryDirs[] = $slug;
    // tentativa por aproximação: procurar diretório que contenha parte do slug
    if (is_dir($baseDir)) {
        $cands = glob($baseDir . '*', GLOB_ONLYDIR);
        $needle = preg_replace('/[^a-z0-9]+/','', strtolower($slug));
        foreach ($cands as $cand) {
            $name = strtolower(basename($cand));
            $flat = preg_replace('/[^a-z0-9]+/','', $name);
            if ($needle && strpos($flat, $needle) !== false) { $tryDirs[] = $name; }
        }
    }
    $exts = ['jpg','jpeg','png','webp'];
    foreach (array_unique($tryDirs) as $dirKey) {
        $dirPath = $baseDir . $dirKey;
        if (!is_dir($dirPath)) continue;
        foreach ($exts as $ext) {
            $matches = glob($dirPath . '/*.' . $ext);
            if (!empty($matches)) { return $toRel($matches[0]); }
        }
    }
    return null;
};

$allCatList = [];
try {
    $rows = $conn->query("SELECT DISTINCT categoria FROM produtos WHERE tipo = 'produto' AND categoria IS NOT NULL AND categoria <> '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
    $routeOverrides = [
        'incenso-xamanico' => 'xamanico-tube.php',
    ];
    $stmtOne = $conn->prepare("SELECT nome FROM produtos WHERE tipo = 'produto' AND categoria = :c ORDER BY id DESC LIMIT 12");
    foreach ($rows as $catName) {
        $slug = $catDbToSlug($catName);
        $hrefBase = $routeOverrides[$slug] ?? ($slug . '.php');
        $href = $hrefBase . '?cat=' . $slug;
        // thumbnail: tenta um produto ALEATÓRIO recente desta categoria via getImagePath com variação tratada
        $thumb = 'uploads/flute_logo.png';
        $stmtOne->execute([':c' => $catName]);
        $names = $stmtOne->fetchAll(PDO::FETCH_COLUMN);
        if ($names && is_array($names)) { shuffle($names); }
        if (!empty($names)) {
            foreach ($names as $nomeVar) {
                if (!$nomeVar) continue;
                $base = $nomeVar;
                // remove prefixos como "<Categoria> -" (BD) e também o slug amigável se vier no nome
                $base = str_ireplace($catName . ' -', '', $base);
                $base = str_ireplace(ucwords(str_replace('-', ' ', $slug)) . ' -', '', $base);
                // remove tokens comuns que poluem a variação
                $tokensRemover = ['incenso', 'caixa', 'flute', 'tube', 'brand', 'cycle', 'masala', 'regular', 'rectangle', 'square'];
                $base = str_ireplace($tokensRemover, '', $base);
                $base = trim(str_replace(['"', '&quot;', "'"], '', $base));
                // normaliza múltiplos espaços/hífens
                $base = preg_replace('/\s+/', ' ', $base);
                $base = trim($base, " -");
                $variacaoParaImagem = $base;
                // Usa o SLUG da categoria para o getImagePath (compatível com estrutura de pastas)
                $img = getImagePath($slug, $variacaoParaImagem ?: $base);
                // valida imagem: não genérica; aceita caminhos de uploads mesmo se não conseguirmos validar no FS
                if ($img && is_string($img)
                    && strpos($img, 'uploads/flute_logo.png') === false
                    && strpos($img, 'uploads/incensos/default.jpg') === false) {
                    $accept = false;
                    if (strpos($img, 'uploads/') !== false) { $accept = true; }
                    else if (strpos($img, '/uploads/') !== false) { $accept = true; }
                    else {
                        $fs = __DIR__ . '/' . ltrim($img, '/');
                        if (@file_exists($fs)) { $accept = true; }
                    }
                    if ($accept) { $thumb = $img; break; }
                }
            }
        }
        // fallback: se imagem é logo/default OU não existe no filesystem, varre diretório físico da categoria
        $isDefault = (strpos($thumb, 'uploads/flute_logo.png') !== false) || (strpos($thumb, 'uploads/incensos/default.jpg') !== false);
        $thumbPathNoQuery = strtok($thumb, '?');
        $fsThumb = __DIR__ . '/' . ltrim($thumbPathNoQuery, '/');
        if ($isDefault || !@file_exists($fsThumb)) {
            $scan = $scanFirstImage($slug);
            if ($scan) { $thumb = $scan; }
        }
        $allCatList[] = [
            'label' => $catName,
            'slug'  => $slug,
            'href'  => $href,
            'thumb' => $thumb,
        ];
    }
} catch (Exception $e) {
    // fallback: usa $catMap
    foreach ($catMap as $key => $info) {
        $allCatList[] = [
            'label' => $info['label'],
            'slug'  => $key,
            'href'  => $info['href'],
            'thumb' => ($catSlides[$key][0] ?? ($info['fallbackImg'] ?? 'uploads/flute_logo.png')),
        ];
    }
}

function formatCurrencyBR($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function usuarioEstaLogado() {
    return isset($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flute Incensos - Atacado de Incensos e Acessórios</title>
    <link rel="icon" type="image/png" href="uploads/flute_logo.png">
    <link rel="apple-touch-icon" href="uploads/flute_logo.png">
    <?php // Google Analytics
        $gaInclude = __DIR__ . '/config/analytics.php';
        if (file_exists($gaInclude)) { include $gaInclude; }
    ?>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=1.3">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <style>
        /* Banner (Swiper) - usar regras globais do styles.css para centralização e corte */

        /* Vantagens */
        .benefits { padding: 28px 0; }
        .benefits-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; }
        .benefits-slider { display: none; }
        /* Altura uniforme para todos os cards */
        :root { --benefit-height: 170px; }
        .benefits-grid .benefit, .benefits-slider .benefit { height: var(--benefit-height); }
        /* Garantir altura uniforme dos cards no slider */
        .benefits-slider .swiper-slide { display: flex; }
        .benefits-slider .swiper-slide .benefit { flex: 1 1 auto; height: var(--benefit-height); }
        .benefit { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 14px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; box-sizing: border-box; }
        .benefit h3 { margin: 4px 0 4px; font-size: 16px; color: #ec2625; }
        .benefit p { margin: 0; font-size: 14px; color: #555; }
        .benefit .benefit-icon { width: 44px; height: 44px; color: #000; margin: 0 auto 2px; display: block; }
        .benefit .benefit-icon * { vector-effect: non-scaling-stroke; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; }

        /* Categorias em destaque */
        .cats { padding: 8px 0 24px; }
        .cats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .cat-card { position: relative; border: 1px solid #eee; border-radius: 10px; overflow: hidden; background: #fff; }
        /* Slider/imagem do card */
        .cat-card .cat-slider { width: 100%; height: 140px; position: relative; z-index: 1; }
        .cat-card .cat-slider .swiper-wrapper { width: 100%; height: 100%; transition-timing-function: cubic-bezier(0.22, 0.61, 0.36, 1) !important; }
        .cat-card .cat-slider .swiper-slide { width: 100%; height: 100%; }
        .cat-card .cat-slider img { width: 100%; height: 100%; object-fit: cover; display: block; }
        /* Título sobre a imagem */
        .cat-card .cat-info { position: absolute; left: 0; bottom: 0; right: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.55) 65%); color: #fff; padding: 12px; font-weight: 600; z-index: 3; }
        /* Desktop: aumentar altura e mostrar imagem inteira */
        @media (min-width: 992px) {
            .cat-card .cat-slider { height: 200px; }
            .cat-card .cat-slider img { object-fit: contain; background: #fff; }
        }

        /* Produtos em destaque */
        .featured { padding: 10px 0 30px; }
        .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .product-card { position: relative; border: 1px solid #eee; border-radius: 10px; padding: 12px; background: #fff; display:flex; flex-direction: column; }
        .product-image-container { width: 100%; height: 180px; display:flex; align-items:center; justify-content:center; margin-bottom: 8px; }
        .product-image-container img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .product-name { font-size: 14px; font-weight: 600; min-height: 38px; margin-bottom: 6px; }
        .product-price { font-weight: 700; margin-bottom: 8px; }
        .product-actions { display:flex; gap: 8px; margin-top: auto; }
        .favorite-icon { position:absolute; top: 10px; right: 10px; cursor:pointer; font-size:22px; color:#b0b0b0; transition: color .2s ease, transform .15s ease; z-index:2; }
        .favorite-icon:hover { transform: scale(1.08); }
        .favorite-icon.active { color:#e53935; }

        /* Utilitários */
        .section-title { font-size: 22px; margin: 0 0 12px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 16px; }

        /* Depoimentos (novo estilo) */
        .reviews { padding: 24px 0 46px; background: #fafafa; position: relative; }
        .reviews .section-title { display: flex; align-items: center; gap: 8px; font-weight: 700; }
        .reviews .section-title .star-ico { width: 18px; height: 18px; color: #222; }
        .reviews-slider { position: relative; }
        .reviews .nav-btn { position: absolute; top: 50%; transform: translateY(-50%); z-index: 3; width: 36px; height: 36px; border-radius: 50%; background: #fff; border: 1px solid #e5e5e5; display: flex; align-items: center; justify-content: center; color: #222; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .reviews .nav-btn:hover { background: #f6f6f6; }
        .reviews .prev { left: -6px; }
        .reviews .next { right: -6px; }
        .review-card { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 14px; height: 100%; display: flex; flex-direction: column; }
        .review-quote { color: #999; font-style: italic; line-height: 1.5; position: relative; padding: 8px 10px; border-radius: 8px; border: 1px solid #f0f0f0; background: #fff; }
        .review-quote:before { content: '“'; position: absolute; left: 8px; top: -8px; color: #ccc; font-size: 22px; }
        .review-quote:after { content: '”'; position: absolute; right: 8px; bottom: -12px; color: #ccc; font-size: 22px; }
        .review-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; }
        .review-person { display: flex; align-items: center; gap: 10px; color: #444; font-size: 13px; }
        .review-avatar { width: 36px; height: 36px; border-radius: 50%; border: 1px solid #f0cfa0; color: #E1A749; display: inline-flex; align-items: center; justify-content: center; }
        .review-stars { color: #f5a524; letter-spacing: 1px; font-size: 13px; }
        @media (max-width: 560px) {
            .reviews .prev { left: 0; }
            .reviews .next { right: 0; }
        }

        /* Navegue por linhas */
        .browse-lines { padding: 26px 0 10px; background: #fff; }
        .browse-lines .section-title { margin-bottom: 14px; }
        .lines-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .line-card { display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; color: #222; text-decoration: none; transition: background .2s ease, transform .12s ease, border-color .2s ease; }
        .line-card:hover { background: #fff; border-color: #e2e2e2; transform: translateY(-1px); }
        .line-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; background: #fff; border: 1px solid #eee; }
        .line-label { font-weight: 600; font-size: 14px; }
        @media (max-width: 992px) { .lines-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 640px) { .lines-grid { grid-template-columns: repeat(2, 1fr); } }

        /* Instagram removido */

        @media (max-width: 1024px) {
            .benefits-grid { grid-template-columns: repeat(2, 1fr); }
            .cats-grid { grid-template-columns: repeat(2, 1fr); }
            .products-grid { grid-template-columns: repeat(2, 1fr); }
            .reviews-grid { grid-template-columns: repeat(2, 1fr); }
            /* Instagram removido */
        }
        @media (max-width: 560px) {
            .products-grid { grid-template-columns: 1fr; }
            .reviews-grid { grid-template-columns: 1fr; }
            /* Instagram removido */
        }

        /* Footer minimal */
        .footer-min { background: #fff; border-top: 1px solid #eee; color: #222; }
        .footer-min .wrap { padding: 36px 0 48px; }
        .footer-min .min-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; align-items: start; }
        .footer-min h4 { font-size: 14px; font-weight: 800; letter-spacing: .4px; color: #222; text-transform: uppercase; margin: 0 0 14px; }
        .footer-min ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; line-height: 1.25; }
        .footer-min .list-2col { column-count: 2; column-gap: 24px; display: block; }
        .footer-min .list-2col li { break-inside: avoid; margin-bottom: 6px; }
        .footer-min a { color: #222; text-decoration: none; font-size: 13px; }
        .footer-min a:hover { text-decoration: underline; }
        .footer-min .contact li { color: #444; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .footer-min .ico { width: 18px; height: 18px; color: #222; display: inline-flex; }
        
        .footer-min .btn-outline { margin-top: 14px; padding: 8px 14px; border: 1px solid #ddd; color: #222; border-radius: 8px; background: #fff; font-size: 12px; font-weight: 600; }
        .footer-min .btn-outline:hover { background: #f6f6f6; }
        .footer-min .socials { display: flex; gap: 10px; align-items: center; }
        .footer-min .socials .ig { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #eaeaea; display: inline-flex; align-items: center; justify-content: center; color: #222; }
        .footer-min .brand-small { display: flex; justify-content: center; margin-bottom: 18px; }
        .footer-min .brand-small img { height: 44px; width: auto; display: block; }
        @media (max-width: 900px) { 
            .footer-min .min-cols { grid-template-columns: 1fr; gap: 20px; }
            .footer-min .list-2col { column-count: 1; }
            .footer-min { text-align: center; }
            .footer-min h4 { text-align: center; }
            .footer-min .contact li { justify-content: center; }
            .footer-min .socials { justify-content: center; }
        }
            /* Mobile: slider para benefícios e ícones menores/traço fino */
            .benefits-grid { display: none; }
            .benefits-slider { display: block; }
            :root { --benefit-height: 150px; }
            .benefit .benefit-icon { width: 36px; height: 36px; }
            .benefit .benefit-icon path,
            .benefit .benefit-icon circle,
            .benefit .benefit-icon line,
            .benefit .benefit-icon polyline {
                stroke-width: 1.5 !important;
            }
            /* Largura dos slides no mobile: padrão 1/2 tela; últimos com classe .span-2 ocupam 2 colunas */
            .benefits-slider .swiper-slide { width: calc(50% - 5px) !important; }
            .benefits-slider .swiper-slide.span-2 { width: 100% !important; }
        }

        /* Carrinho toast */
        .cart-message { position: fixed; top: 20px; right: 20px; z-index: 9999; background: #333; color: #fff; padding: 12px 16px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); opacity: 0; transition: opacity 0.3s ease; }
        .cart-message.success { background: #2e7d32; }
        .cart-message.error { background: #c62828; }
        .header-actions-area .cart-count { display: none; }
    </style>
    <script>
        const USER_LOGGED_IN = <?php echo usuarioEstaLogado() ? 'true' : 'false'; ?>;
        document.addEventListener('DOMContentLoaded', function() {
            // Header mobile/dropdowns (igual produtos.php)
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
            document.querySelectorAll('.main-nav .nav-item.dropdown > a').forEach(link=>{
                link.addEventListener('click', function(e){ if (window.matchMedia('(max-width: 768px)').matches && document.getElementById('main-nav').classList.contains('open')) { e.preventDefault(); this.parentElement.classList.toggle('open'); } });
            });

            atualizarContadorCarrinho();
            inicializarFavoritos();
        });

        // Sync badge carrinho
        function atualizarContadorCarrinho() {
            fetch('contar_itens_carrinho.php')
              .then(r=>r.json())
              .then(data=>{
                const el = document.querySelector('.cart-count');
                if (el) { el.textContent = data.quantidade || '0'; el.style.display = (data.quantidade > 0) ? 'flex' : 'none'; }
              })
              .catch(()=>{});
        }
        window.addEventListener('storage', (e)=>{ if (e.key==='cart_updated') atualizarContadorCarrinho(); });
        window.addEventListener('pageshow', atualizarContadorCarrinho);
        document.addEventListener('visibilitychange', ()=>{ if (!document.hidden) atualizarContadorCarrinho(); });

        // Favoritos
        async function inicializarFavoritos(){
            try {
                const res = await fetch('favoritos.php?action=list', { credentials: 'same-origin' });
                const data = await res.json();
                if (!data || !data.sucesso) return;
                const ids = new Set((data.ids || []).map(Number));
                document.querySelectorAll('.favorite-icon[data-produto-id]').forEach(el=>{
                    const id = Number(el.getAttribute('data-produto-id'));
                    const isFav = ids.has(id);
                    if (isFav) el.classList.add('active');
                    el.setAttribute('role', 'button');
                    el.setAttribute('aria-pressed', isFav ? 'true' : 'false');
                    el.setAttribute('title', isFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
                    el.addEventListener('click', ()=>toggleFavorito(el, id));
                });
            } catch(e) {}
        }
        async function toggleFavorito(el, produtoId){
            if (!USER_LOGGED_IN) { window.location.href = 'login.html'; return; }
            const adding = !el.classList.contains('active');
            try {
                const resp = await fetch('favoritos.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action: adding ? 'add' : 'remove', produto_id: produtoId }) });
                const data = await resp.json();
                if (data && data.sucesso) {
                    el.classList.toggle('active', adding);
                    el.setAttribute('aria-pressed', adding ? 'true' : 'false');
                    el.setAttribute('title', adding ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
                    mostrarMensagem(adding ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.', 'success');
                } else {
                    mostrarMensagem((data && data.erro) ? data.erro : 'Erro ao atualizar favorito.', 'error');
                }
            } catch (e) { mostrarMensagem('Erro ao atualizar favorito.', 'error'); }
        }

        // Carrinho
        async function adicionarAoCarrinho(produtoId, nomeProduto){
            const quantityInput = document.getElementById(`qty_${produtoId}`);
            const quantidade = quantityInput ? parseInt(quantityInput.value, 10) : 1;
            if (quantidade < 1) { mostrarMensagem('A quantidade deve ser de pelo menos 1.', 'error'); return; }
            try {
                const resp = await fetch('adicionar_ao_carrinho.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ produto_id: produtoId, quantidade }) });
                const data = await resp.json();
                if (data.sucesso) {
                    mostrarMensagem(`${nomeProduto} adicionado ao carrinho!`, 'success');
                    atualizarContadorCarrinho();
                    try { localStorage.setItem('cart_updated', Date.now().toString()); } catch(e){}
                } else {
                    mostrarMensagem(data.erro || 'Erro ao adicionar ao carrinho.', 'error');
                }
            } catch(e) { mostrarMensagem('Erro ao adicionar ao carrinho.', 'error'); }
        }

        function mostrarMensagem(mensagem, tipo){
            const msg = document.createElement('div');
            msg.className = `cart-message ${tipo}`;
            msg.textContent = mensagem;
            document.body.appendChild(msg);
            setTimeout(()=> msg.style.opacity = '1', 100);
            setTimeout(()=> { msg.style.opacity = '0'; setTimeout(()=> msg.remove(), 300); }, 3000);
        }

        // (init dos sliders de categoria movido para o final da página, após carregar Swiper JS)
    </script>
</head>
<body>
    <!-- Header (replicado de produtos.php) -->
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
                            <span>Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0] ?? ''); ?></span>
                        </a>
                        <a href="logout.php" class="action-link">Sair</a>
                    <?php else: ?>
                        <a href="login.html" class="action-link">Entrar</a>
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
        
        <!-- Barra de busca mobile sempre visível -->
        <div class="mobile-search-fixed" style="display: none; background: #fff; border-bottom: 1px solid #e9ecef; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="container">
                <form class="mobile-search-form" action="buscar.php" method="get" style="display: flex; align-items: center; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 25px; padding: 10px 20px; gap: 12px; margin: 0 15px;">
                    <input type="text" name="q" placeholder="Digite o que você procura..." aria-label="Buscar produtos" style="flex: 1; border: none; background: transparent; outline: none; font-size: 16px; color: #333; padding: 0;">
                    <button type="submit" aria-label="Buscar" class="mobile-search-submit" style="background: none; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
        </div>
        
        <style>
        /* Header fixo */
        .site-header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Compensar altura do header fixo */
        body {
            padding-top: 140px; /* Ajustar conforme altura do header */
        }
        
        /* Ajuste para mobile */
        @media (max-width: 768px) {
            body {
                padding-top: 120px; /* Altura menor no mobile */
            }
            
            .mobile-search-fixed {
                display: block !important;
                padding: 12px 0;
                position: relative;
                z-index: 9998;
            }
            
            /* Garantir que o menu mobile apareça sobre tudo */
            .main-nav.open {
                z-index: 10000 !important;
            }
            
            .backdrop.open {
                z-index: 9999 !important;
            }
        }
        
        /* Garantir que dropdowns apareçam corretamente */
        .dropdown-content {
            z-index: 10001;
        }
        </style>
    </header>
    
    <!-- Deploy atualizado em <?php echo date('Y-m-d H:i:s'); ?> -->

    <!-- Banner (igual produtos.php) -->
    <div class="swiper-container banner-slider">
        <div class="swiper-wrapper">
            <div class="swiper-slide"><img src="uploads/banners/banner-masala.png" alt="Banner Flute Incense Masala"></div>
            <div class="swiper-slide"><img src="uploads/banners/banner-tulasi.png" alt="Banner Flute Incense Tulasi"></div>
            <div class="swiper-slide"><img src="uploads/banners/banner-clove.png" alt="Banner Flute Incense Clove"></div>
        </div>
    </div>

    <!-- Vantagens -->
    <section class="benefits">
        <div class="container">
            <!-- Desktop/Tablet Grid -->
            <div class="benefits-grid">
                <!-- removed wholesale-only benefit -->
                <div class="benefit">
                    <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 7h13v10H3V7zM16 10h3l2 2v5h-5V10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="7.5" cy="17" r="1.5" stroke="currentColor" stroke-width="2" fill="none"/>
                        <circle cx="18.5" cy="17" r="1.5" stroke="currentColor" stroke-width="2" fill="none"/>
                    </svg>
                    <h3>Entrega Rápida</h3>
                    <p>Envio ágil para todo o Brasil.</p>
                </div>
                <div class="benefit">
                    <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Atacado Oficial</h3>
                    <p>Produtos originais e certificados.</p>
                </div>
                <div class="benefit">
                    <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <line x1="19" y1="5" x2="5" y2="19" stroke-width="2"/>
                        <circle cx="6.5" cy="6.5" r="2.5" stroke-width="2"/>
                        <circle cx="17.5" cy="17.5" r="2.5" stroke-width="2"/>
                    </svg>
                    <h3>Melhor Custo‑benefício</h3>
                    <p>Condições especiais para revenda.</p>
                </div>
                <div class="benefit">
                    <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 18v-6a9 9 0 0 1 18 0v6" stroke-width="2"/>
                        <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3" stroke-width="2"/>
                        <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3" stroke-width="2"/>
                    </svg>
                    <h3>Suporte ao Revendedor</h3>
                    <p>Atendimento próximo e consultivo.</p>
                </div>
            </div>
            <!-- Mobile Slider (2 por vez) -->
            <div class="swiper-container benefits-slider">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="benefit">
                            <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 7h13v10H3V7zM16 10h3l2 2v5h-5V10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="7.5" cy="17" r="1.5" stroke="currentColor" stroke-width="2" fill="none"/>
                                <circle cx="18.5" cy="17" r="1.5" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                            <h3>Entrega Rápida</h3>
                            <p>Envio ágil para todo o Brasil.</p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="benefit">
                            <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <h3>Atacado Oficial</h3>
                            <p>Produtos originais e certificados.</p>
                        </div>
                    </div>
                    <div class="swiper-slide span-2">
                        <div class="benefit">
                            <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <line x1="19" y1="5" x2="5" y2="19" stroke-width="2"/>
                                <circle cx="6.5" cy="6.5" r="2.5" stroke-width="2"/>
                                <circle cx="17.5" cy="17.5" r="2.5" stroke-width="2"/>
                            </svg>
                            <h3>Melhor Custo‑benefício</h3>
                            <p>Condições especiais para revenda.</p>
                        </div>
                    </div>
                    <div class="swiper-slide span-2">
                        <div class="benefit">
                            <svg class="benefit-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6" stroke="currentColor" stroke-width="2"/>
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3" stroke="currentColor" stroke-width="2"/>
                                <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <h3>Suporte ao Revendedor</h3>
                            <p>Atendimento próximo e consultivo.</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Categorias em destaque -->
    <section class="cats">
        <div class="container">
            <h2 class="section-title">Categorias em destaque</h2>
            <div class="cats-grid">
                <?php foreach ($catMap as $key => $info): ?>
                    <a class="cat-card" href="<?php echo htmlspecialchars($info['href']); ?>">
                        <div class="swiper-container cat-slider" data-cat="<?php echo htmlspecialchars($key); ?>">
                            <div class="swiper-wrapper">
                                <?php foreach (($catSlides[$key] ?? []) as $img): ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($info['label']); ?>" onerror="this.src='uploads/flute_logo.png'">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="cat-info"><?php echo htmlspecialchars($info['label']); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Produtos em destaque -->
    <section class="featured">
        <div class="container">
            <h2 class="section-title">Produtos em destaque</h2>
            <div class="products-grid">
                <?php foreach ($produtos as $produto): 
                    // 1) Título padronizado (exibição, alt, WhatsApp, botão)
                    $nomePadronizado = formatarTituloProduto($produto['categoria'], $produto['nome']);
                    
                    // 2) Variação para imagem (remove prefixos eventuais do nome original)
                    $base = $produto['nome'];
                    if (!empty($produto['categoria'])) { $base = str_ireplace($produto['categoria'] . ' -', '', $base); }
                    $base = trim(str_replace(['"', '&quot;', "'"], '', $base));
                    $variacaoParaImagem = str_ireplace('incenso', '', $base);
                    $variacaoParaImagem = trim(str_replace(['"', '&quot;', "'"], '', $variacaoParaImagem));
                ?>
                <div class="product-card">
                    <span class="favorite-icon" data-produto-id="<?php echo (int)$produto['id']; ?>" role="button" aria-label="Adicionar aos favoritos">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 21s-6.5-4.35-9.33-7.17C.63 11.79.63 8.21 2.67 6.17c2.04-2.04 5.34-2.04 7.38 0L12 8.12l1.95-1.95c2.04-2.04 5.34-2.04 7.38 0 2.04 2.04 2.04 5.62 0 7.66C18.5 16.65 12 21 12 21z" stroke="#6b7280" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="product-image-container">
                        <?php $linkProduto = 'produto.php?id=' . (int)$produto['id']; ?>
                        <?php if ($linkProduto): ?><a href="<?php echo $linkProduto; ?>" class="no-underline" aria-label="Ver produto: <?php echo htmlspecialchars($nomePadronizado); ?>"><?php endif; ?>
                        <img 
                            src="<?php echo getImagePath($produto['categoria'], $variacaoParaImagem); ?>" 
                            alt="<?php echo htmlspecialchars($nomePadronizado); ?>"
                            class="product-image"
                        >
                        <?php if ($linkProduto): ?></a><?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h2 class="product-name">
                            <?php if ($linkProduto): ?><a href="<?php echo $linkProduto; ?>" class="no-underline"><?php endif; ?>
                            <?php echo htmlspecialchars($nomePadronizado); ?>
                            <?php if ($linkProduto): ?></a><?php endif; ?>
                        </h2>
                        
                         <?php if (usuarioEstaLogado()): ?>
                            <div class="price-area">
                                 <div class="price-row">
                                     <div class="price">
                                         <span class="currency">R$</span> <strong class="amount"><?php echo number_format($produto['preco'], 2, ',', '.'); ?></strong>
                                     </div>
                                     <div class="qty-stepper" data-id="<?php echo (int)$produto['id']; ?>">
                                         <button type="button" class="qty-btn minus" aria-label="Diminuir quantidade">&minus;</button>
                                         <input type="number" id="qty_<?php echo $produto['id']; ?>" class="quantity-input" value="1" min="1" inputmode="numeric" pattern="[0-9]*" aria-label="Quantidade">
                                         <button type="button" class="qty-btn plus" aria-label="Aumentar quantidade">+</button>
                                     </div>
                                 </div>
                             </div>
                        <div class="cart-controls">
                            <button onclick="adicionarAoCarrinho('<?php echo $produto['id']; ?>', '<?php echo htmlspecialchars($nomePadronizado); ?>')" 
                                    class="btn btn-cart btn-full">
                                Adicionar ao carrinho
                            </button>
                            <a href="https://wa.me/5548996107541?text=Ol%C3%A1%2C%20tenho%20interesse%20no%20produto%3A%20<?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp btn-full">
                                Comprar pelo WhatsApp
                            </a>
                        </div>
                        <?php else: ?>
                            <div class="cart-controls">
                                <a href="login.html" class="btn btn-cart">Ver preço</a>
                                <a href="https://wa.me/5548996107541?text=Ol%C3%A1%2C%20tenho%20interesse%20no%20produto%3A%20<?php echo urlencode($nomePadronizado); ?>" target="_blank" class="btn btn-whatsapp">
                                    Comprar pelo WhatsApp
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Depoimentos de Clientes -->
    <section class="reviews">
        <div class="container">
            <h2 class="section-title">
                <svg class="star-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Últimas avaliações de nossos clientes
            </h2>
            <div class="reviews-slider swiper-container">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="review-card">
                            <div class="review-quote">O produto chegou perfeitamente. Estava muito bem embalado. Recomendo totalmente a loja.</div>
                            <div class="review-footer">
                                <div class="review-person">
                                    <span class="review-avatar">
                                      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <span>Pedro Henrique - São Paulo/SP</span>
                                </div>
                                <span class="review-stars">★★★★★</span>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="review-card">
                            <div class="review-quote">Tudo dentro dos conformes. Com certeza voltarei a comprar mais vezes.</div>
                            <div class="review-footer">
                                <div class="review-person">
                                    <span class="review-avatar">
                                      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <span>José Silveira - Canoas/RS</span>
                                </div>
                                <span class="review-stars">★★★★★</span>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="review-card">
                            <div class="review-quote">Já é a segunda vez que compro e não me decepciono. Muito obrigado pelo serviço!</div>
                            <div class="review-footer">
                                <div class="review-person">
                                    <span class="review-avatar">
                                      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <span>Luna Calheiros - Salvador/BA</span>
                                </div>
                                <span class="review-stars">★★★★★</span>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="review-card">
                            <div class="review-quote">Chegou tudo certinho e muito rápido. Voltarei a comprar.</div>
                            <div class="review-footer">
                                <div class="review-person">
                                    <span class="review-avatar">
                                      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <span>Arnaldo Souza - Fortaleza/CE</span>
                                </div>
                                <span class="review-stars">★★★★★</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nav-btn prev" aria-label="Anterior">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </div>
                <div class="nav-btn next" aria-label="Próximo">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </div>
            </div>
        </div>
    </section>
    <!-- Navegue por linhas -->
    <section class="browse-lines">
        <div class="container">
            <h2 class="section-title">Navegue por linhas</h2>
            <div class="lines-grid">
                <?php foreach ($allCatList as $c): ?>
                    <a class="line-card" href="<?php echo htmlspecialchars($c['href']); ?>">
                        <img class="line-thumb" src="<?php echo htmlspecialchars($c['thumb']); ?>" alt="<?php echo htmlspecialchars($c['label']); ?>" onerror="this.src='uploads/flute_logo.png'">
                        <span class="line-label"><?php echo htmlspecialchars($c['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper('.banner-slider', {
            loop: true,
            autoplay: { delay: 4000, disableOnInteraction: false }
        });
        // Slider de avaliações
        var reviewsSwiper = new Swiper('.reviews-slider', {
            slidesPerView: 1,
            spaceBetween: 14,
            loop: false,
            navigation: { nextEl: '.reviews .next', prevEl: '.reviews .prev' },
            breakpoints: {
                560: { slidesPerView: 2 },
                992: { slidesPerView: 4 }
            }
        });
        // Slider de benefícios (mobile: largura auto com CSS; autoplay ON; avança 2 por vez)
        var benefitsSwiper = new Swiper('.benefits-slider', {
            slidesPerView: 'auto',
            spaceBetween: 10,
            loop: false,
            autoplay: false,
            slidesPerGroup: 1,
            watchOverflow: false,
            centeredSlides: false,
            normalizeSlideIndex: true,
            observer: true,
            observeParents: true,
            on: {},
            breakpoints: {
                769: {
                    slidesPerView: 4,
                    spaceBetween: 16,
                    autoplay: false,
                    loop: false,
                    slidesPerGroup: 4
                }
            }
        });
        // Auto-avança de 2 em 2 no mobile, sem loop, evitando warnings do Swiper
        (function(){
            try {
                var isMobile = window.matchMedia && window.matchMedia('(max-width: 560px)').matches;
                if (!isMobile) return;
                var ADVANCE_MS = 3500;
                setInterval(function(){
                    if (!benefitsSwiper || !benefitsSwiper.slides) return;
                    var idx = Number(benefitsSwiper.activeIndex || 0);
                    var next;
                    // Sequência desejada com nossos tamanhos: (0/1) -> 2 -> 3 -> 0 -> ...
                    if (idx <= 1) next = 2;
                    else if (idx === 2) next = 3;
                    else next = 0;
                    benefitsSwiper.slideTo(next, 400);
                }, ADVANCE_MS);
            } catch(e) {}
        })();

        // Inicializa sliders de cada card de categoria (autoplay com transição suave estilo carrossel)
        document.querySelectorAll('.cat-slider').forEach(function(el){
            try {
                var wrapper = el.querySelector('.swiper-wrapper');
                var slides = el.querySelectorAll('.swiper-slide');
                // Se houver apenas 1 imagem, duplica para permitir autoplay perceptível
                if (slides.length === 1 && wrapper && slides[0]) {
                    wrapper.appendChild(slides[0].cloneNode(true));
                    slides = el.querySelectorAll('.swiper-slide');
                }
                var hasMultiple = slides.length > 1;
                new Swiper(el, {
                    slidesPerView: 1,
                    spaceBetween: 10,
                    loop: hasMultiple,
                    speed: 700,
                    autoplay: { delay: 5000, disableOnInteraction: false },
                    effect: 'slide',
                    allowTouchMove: true,
                    grabCursor: true,
                    centeredSlides: false,
                    on: {
                        reachEnd: function(){
                            // Se não estiver em loop, rebobina suavemente
                            if (!this.params.loop) {
                                try { this.slideTo(0, this.params.speed, true); } catch(e){}
                            }
                        }
                    }
                });
            } catch(e) {}
        });
    </script>
<style>
  /* Stepper de quantidade - escopo local da index */
  /* Alinhamento à esquerda nos cards */
  .product-card .product-info { text-align: left; display: flex; flex-direction: column; align-items: flex-start; width: 100%; }
  .product-card .product-name { width: 100%; margin-bottom: 6px; }
  .product-card .product-name a { display: inline-block; }
  .price-area { width: 100%; }
  .cart-controls { width: 100%; }
  .cart-controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .cart-controls { flex-direction: column; align-items: flex-start; }
  .cart-controls .buy-row { display:flex; gap: 10px; align-items: center; }
  .cart-controls .btn-whatsapp { margin-top: 6px; }
  .qty-stepper { display: inline-flex; align-items: center; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; background: #fff; }
  .qty-stepper .qty-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 0; background: #f5f5f7; color: #333; font-size: 18px; cursor: pointer; transition: background .15s ease; }
  .qty-stepper .qty-btn:hover { background: #ececf1; }
  .qty-stepper .quantity-input { width: 48px; height: 32px; border: 0; outline: 0; text-align: center; font-weight: 600; color: #333; background: #fff; -moz-appearance: textfield; }
  .qty-stepper .quantity-input::-webkit-outer-spin-button,
  .qty-stepper .quantity-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  @media (max-width: 560px) { .qty-stepper .qty-btn { width: 30px; height: 30px; } .qty-stepper .quantity-input { width: 42px; height: 30px; } }

  /* Preço: somente números em negrito */
  .price { display: flex; align-items: baseline; gap: 4px; }
  .price .currency { font-weight: 400; color: #444; }
  .price .amount { font-weight: 700; color: #000; }
  .price-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%; }
  .btn-full { display: block; width: 100%; box-sizing: border-box; }
  @media (max-width: 560px) {
    .price-row { flex-wrap: wrap; gap: 8px; }
    .price { margin-right: auto; }
    .cart-controls .btn-whatsapp { width: 100%; }
  }

  

  /* Ícone de favoritos (coração em círculo cinza) */
  .product-card { position: relative; }
  .favorite-icon { position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px #e5e7eb; cursor: pointer; }
  .favorite-icon svg { display: block; }
  .favorite-icon:hover { background: #eceff3; }
  .favorite-icon.active svg path { stroke: #e53935; fill: #e53935; }
</style>
<script>
  // Comportamento do stepper: incrementa/decrementa e garante mínimo de 1
  (function(){
    function clampToInt(val){
      var n = parseInt(val, 10);
      if (isNaN(n) || n < 1) n = 1;
      return n;
    }
    document.addEventListener('click', function(e){
      var btn = e.target.closest('.qty-btn');
      if (!btn) return;
      var stepper = btn.closest('.qty-stepper');
      if (!stepper) return;
      var input = stepper.querySelector('.quantity-input');
      var current = clampToInt(input && input.value ? input.value : '1');
      if (btn.classList.contains('plus')) current += 1; else current = Math.max(1, current - 1);
      if (input) { input.value = String(current); input.dispatchEvent(new Event('change', { bubbles: true })); }
    });
    document.addEventListener('input', function(e){
      if (!e.target.classList.contains('quantity-input')) return;
      e.target.value = String(clampToInt(e.target.value));
    });
  })();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
