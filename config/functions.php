<?php

function getImagePath($categoria, $variacao) {
    // Array de substituição para caracteres acentuados
    $caracteresEspeciais = array(
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u',
        'ý' => 'y',
        'ñ' => 'n',
        'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a',
        'É' => 'e', 'È' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Õ' => 'o', 'Ô' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u',
        'Ý' => 'y',
        'Ñ' => 'n',
        'Ç' => 'c'
    );
    
    // Normaliza nome base
    $nomeBase = strtr(mb_strtolower($variacao, 'UTF-8'), $caracteresEspeciais);

    // Normaliza categoria para um slug comparável (sem acento, minúsculo, hifens)
    $catBase = strtr(mb_strtolower((string)$categoria, 'UTF-8'), $caracteresEspeciais);
    $catNorm = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', trim($catBase)));

    // Mapeia categoria para pasta e regras de nome/extensão (usando aliases possíveis)
    $categoriaFolder = '';
    $ext = 'jpg';
    $prefix = '';
    $nomeArquivo = '';

    switch ($catNorm) {
        case 'regular-square':
            $categoriaFolder = 'regular-square';
            // usa hifens
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
        case 'masala-square':
            $categoriaFolder = 'masala-square';
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
        case 'masala-small-packet':
        case 'masala-small':
            $categoriaFolder = 'masala-small';
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
        case 'long-square':
            $categoriaFolder = 'long-square';
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
        case 'cycle-brand-regular':
        case 'cycle-brand-regular-square':
            $categoriaFolder = 'cycle-brand-regular';
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
        case 'cycle-brand-rectangle':
            $categoriaFolder = 'cycle-brand-rectangle';
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
        case 'incenso-xamanico-tube':
        case 'xamanico-tube':
        case 'incenso-xamanico':
            $categoriaFolder = 'xamanico-tube';
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
        case 'clove-brand':
            $categoriaFolder = 'clove-brand';
            $ext = 'png';
            $prefix = 'clove-';
            // clove usa nome sem espaços/hifens e somente alfanumérico
            $nomeArquivo = preg_replace('/[^a-z0-9]/', '', str_replace(' ', '', $nomeBase));
            break;
        default:
            // fallback genérico: usa hifens
            $categoriaFolder = 'regular-square';
            $nomeArquivo = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $nomeBase));
            break;
    }

    // Define o caminho da imagem
    $imagePath = "uploads/incensos/{$categoriaFolder}/{$prefix}{$nomeArquivo}.{$ext}";
    // Caminho absoluto no filesystem para evitar problemas de diretório corrente
    $fsPath = __DIR__ . '/../' . $imagePath;

    // Adiciona timestamp para evitar cache
    if (file_exists($fsPath)) {
        $timestamp = filemtime($fsPath);
        // log básico para diagnosticar
        error_log("IMG OK: cat={$categoria} ({$catNorm}) nome={$variacao} => {$imagePath}");
        return $imagePath . "?v=" . $timestamp;
    }

    // Tentativas com aliases conhecidos (ex.: números por extenso) e variações automáticas
    if ($categoriaFolder === 'regular-square') {
        $candidates = [];
        $baseAtual = $prefix . $nomeArquivo;
        // Trocas específicas
        $mapEspecifico = [
            '7-poderes' => 'sete-poderes',
            '7-ervas'   => 'sete-ervas',
        ];
        foreach ($mapEspecifico as $de => $para) {
            if ($baseAtual === $de) {
                $candidates[] = $para;
            }
        }
        // Regras gerais: alterna 7- <-> sete-
        if (strpos($baseAtual, '7-') !== false) {
            $candidates[] = str_replace('7-', 'sete-', $baseAtual);
        }
        if (strpos($baseAtual, 'sete-') !== false) {
            $candidates[] = str_replace('sete-', '7-', $baseAtual);
        }
        // Remove duplicatas
        $candidates = array_values(array_unique($candidates));

        foreach ($candidates as $cand) {
            $altImagePath = "uploads/incensos/{$categoriaFolder}/{$cand}.{$ext}";
            $altFsPath = __DIR__ . '/../' . $altImagePath;
            if (file_exists($altFsPath)) {
                $timestamp = filemtime($altFsPath);
                error_log("IMG ALIAS HIT: {$baseAtual}=>{$cand} cat={$categoria} nome={$variacao} => {$altImagePath}");
                return $altImagePath . "?v=" . $timestamp;
            }
        }
    }

    // Tenta extensões alternativas
    $altExts = ['jpeg', 'png', 'jpg'];
    foreach ($altExts as $altExt) {
        if ($altExt === $ext) continue;
        $altImagePath = "uploads/incensos/{$categoriaFolder}/{$prefix}{$nomeArquivo}.{$altExt}";
        $altFsPath = __DIR__ . '/../' . $altImagePath;
        if (file_exists($altFsPath)) {
            $timestamp = filemtime($altFsPath);
            error_log("IMG EXT HIT: .{$ext}=>.{$altExt} cat={$categoria} nome={$variacao} => {$altImagePath}");
            return $altImagePath . "?v=" . $timestamp;
        }
    }

    // Tentativa: glob por slug base independente de extensão
    $globPattern = __DIR__ . '/../' . "uploads/incensos/{$categoriaFolder}/{$prefix}{$nomeArquivo}.*";
    $matches = glob($globPattern);
    if ($matches && count($matches) > 0) {
        // Pega o primeiro
        $matchFs = $matches[0];
        // Converte para caminho web relativo
        $baseDir = realpath(__DIR__ . '/..');
        if ($baseDir && strpos($matchFs, $baseDir) === 0) {
            $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $matchFs);
            $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
            $timestamp = filemtime($matchFs);
            error_log("IMG GLOB HIT: cat={$categoria} nome={$variacao} => {$relPath}");
            return $relPath . "?v=" . $timestamp;
        }
    }

    // Varredura heurística: procura arquivo contendo tokens do nome (ex.: 'sete' e 'poderes')
    $dirPath = __DIR__ . '/../' . "uploads/incensos/{$categoriaFolder}";
    if (is_dir($dirPath)) {
        $tokens = array_filter(explode('-', $prefix . $nomeArquivo));
        $dh = opendir($dirPath);
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                if ($file === '.' || $file === '..' || is_dir($dirPath . '/' . $file)) continue;
                $lower = mb_strtolower($file, 'UTF-8');
                $ok = true;
                foreach ($tokens as $t) {
                    if ($t === '') continue;
                    if (strpos($lower, $t) === false) { $ok = false; break; }
                }
                if ($ok) {
                    closedir($dh);
                    $relPath = "uploads/incensos/{$categoriaFolder}/" . $file;
                    $fs = $dirPath . '/' . $file;
                    $timestamp = @filemtime($fs) ?: time();
                    error_log("IMG HEURISTIC HIT: cat={$categoria} nome={$variacao} => {$relPath}");
                    return $relPath . "?v=" . $timestamp;
                }
            }
            closedir($dh);
        }
    }

    // log quando não encontrou
    error_log("IMG MISS: cat={$categoria} ({$catNorm}) nome={$variacao} => {$imagePath}");
    // Fallback final: usa logo se default não existir
    $fallback = 'uploads/incensos/default.jpg';
    $fallbackFs = __DIR__ . '/../' . $fallback;
    if (!file_exists($fallbackFs)) {
        $fallback = 'uploads/flute_logo.png';
    }
    return file_exists($fsPath) ? $imagePath : $fallback;
}

/**
 * Formata o título padronizado do produto conforme linha/categoria.
 * Exemplos:
 *  - Caixa Incenso Flute Regular Square - 7 Poderes
 *  - Caixa Incenso Flute Xamanico - Descarrego
 *  - Caixa Incenso Flute Masala - Arruda
 *
 * Aplica-se hoje à linha Masala. Pode ser expandido para outras linhas.
 */
function formatarTituloProduto($categoria, $nomeProduto) {
    $cat = (string)$categoria;
    $nome = (string)$nomeProduto;

    // Normaliza para comparações (sem acento, minúsculo)
    $map = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','í'=>'i','ì'=>'i','î'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ç'=>'c',
        'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','É'=>'e','È'=>'e','Ê'=>'e','Í'=>'i','Ì'=>'i','Î'=>'i',
        'Ó'=>'o','Ò'=>'o','Õ'=>'o','Ô'=>'o','Ú'=>'u','Ù'=>'u','Û'=>'u','Ç'=>'c'
    ];
    $catNorm = strtr(mb_strtolower($cat, 'UTF-8'), $map);

    // Extrai variação removendo prefixos incidentais
    $variacao = $nome;
    $variacao = str_ireplace($cat . ' -', '', $variacao);
    $variacao = str_ireplace('incenso', '', $variacao);
    $variacao = str_ireplace('caixa', '', $variacao);
    $variacao = trim(str_replace(['"', "'", '&quot;'], '', $variacao));

    // Mapeia categoria normalizada para rótulo de exibição
    $labels = [
        'regular-square' => 'Regular Square',
        'masala-square' => 'Masala',
        'masala-small' => 'Masala Small Packet',
        'masala-small-packet' => 'Masala Small Packet',
        'long-square' => 'Long Square',
        'cycle-brand-regular' => 'Cycle Brand Regular',
        'cycle-brand-regular-square' => 'Cycle Brand Regular',
        'cycle-brand-rectangle' => 'Cycle Brand Rectangle',
        'xamanico-tube' => 'Xamânico',
        'incenso-xamanico' => 'Xamânico',
        'incenso-xamanico-tube' => 'Xamânico',
        'clove-brand' => 'Clove Brand',
    ];

    // Normaliza categoria para chave de lookup
    $catKey = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', trim($catNorm)));
    $catLabel = isset($labels[$catKey]) ? $labels[$catKey] : null;

    if ($catLabel) {
        return 'Caixa Incenso Flute ' . $catLabel . ' - ' . $variacao;
    }

    // Sem mapeamento: mantém nome original
    return $nomeProduto;
}
