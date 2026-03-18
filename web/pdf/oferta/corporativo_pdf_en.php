<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_time_limit(120);
@ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "PDF Error: " . $e['message'] . "\n in " . $e['file'] . " on line " . $e['line'];
    }
});

$webRoot = dirname(dirname(__DIR__)); // web/ (o sde/)
$vendorAutoload = $webRoot . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    $vendorAutoload = $webRoot . '/vendor/autoload.php';
}
if (!file_exists($vendorAutoload)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "To generate the PDF, run in the project root: composer install\n";
    exit;
}
require_once $vendorAutoload;

require_once $webRoot . '/includes/functions.php';
DBconnect();

global $link;

// ——— Configuración institucional (editar o mover a BD/config según necesidad) ———
$configInstitucional = [
    'titulo_documento'   => 'EXPORTABLE SUPPLY',
    'nombre_provincia'   => 'Santiago del Estero',
    'periodo_ano'        => date('Y'),
    'area_responsable'   => 'Foreign Trade Office',
    'telefono'           => '+54 385 421 1234',
    'sitio_web'          => 'https://www.santiago.gob.ar',
    'mail'               => 'comercioexterior@santiago.gob.ar',
    'localidad_direccion'=> 'Santiago del Estero, Argentina',
];
$assetsDir = __DIR__ . '/../assets';
$logoPath = $webRoot . '/img/logo.svg';
$catImages = glob($webRoot . '/img/landing/*.png');
$catImagePath = !empty($catImages) ? $catImages[0] : null;
// Fondo del primer slide: una de las 6 Portada al azar en cada descarga
$portadaCandidates = [];
foreach (['Portada1.webp', 'portada2.webp', 'portada3.jpg', 'portada4.jpg', 'portada5.jpg', 'portada6.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $portadaCandidates[] = $p;
    }
}
$backgroundSlide1Path = !empty($portadaCandidates) ? $portadaCandidates[array_rand($portadaCandidates)] : $assetsDir . '/background_slide1.jpg';
if (!file_exists($backgroundSlide1Path)) {
    $backgroundSlide1Path = null;
}
$backgroundContactPath = null;
if (count($portadaCandidates) >= 2) {
    $others = array_values(array_filter($portadaCandidates, function ($p) use ($backgroundSlide1Path) { return $p !== $backgroundSlide1Path; }));
    $backgroundContactPath = $others[array_rand($others)];
} elseif (!empty($portadaCandidates)) {
    $backgroundContactPath = $backgroundSlide1Path;
}
if ($backgroundContactPath && !file_exists($backgroundContactPath)) {
    $backgroundContactPath = $backgroundSlide1Path;
}
$backgroundSlide1Uri = ($backgroundSlide1Path && file_exists($backgroundSlide1Path))
    ? 'data:' . (pathinfo($backgroundSlide1Path, PATHINFO_EXTENSION) === 'webp' ? 'image/webp' : 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($backgroundSlide1Path))
    : '';
// Imagen del slide 2 (Contexto provincial): una de las Productivo al azar
$productivoCandidates = [];
foreach (['Productivo1.webp', 'Productivo2.jpg', 'Productivo3.jpg', 'Productivo4.jpg', 'Productivo5 copy.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $productivoCandidates[] = $p;
    }
}
$productivoSlide2Path = !empty($productivoCandidates) ? $productivoCandidates[array_rand($productivoCandidates)] : null;
$productivoSlide2Paths = [];
if (count($productivoCandidates) >= 2) {
    $keys = array_rand($productivoCandidates, 2);
    $keys = is_array($keys) ? $keys : [$keys];
    foreach ($keys as $k) {
        $productivoSlide2Paths[] = $productivoCandidates[$k];
    }
} else {
    $productivoSlide2Paths = array_slice($productivoCandidates, 0, 2);
}
while (count($productivoSlide2Paths) < 2) {
    $productivoSlide2Paths[] = !empty($productivoCandidates) ? $productivoCandidates[array_rand($productivoCandidates)] : null;
}
// Tres imágenes aleatorias para el slide 3 (Identidad provincial) desde la carpeta assets
$identidadCandidates = [];
foreach (['identidad1.jpg', 'identidad2.jpg', 'identidad3.jpg', 'identidad4.jpg', 'identidad5.jpg', 'Identidad6.jpg', 'identidad6(1).JPG', 'Identidad7.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $identidadCandidates[] = $p;
    }
}
$identidadSlide3Paths = [];
if (count($identidadCandidates) >= 3) {
    $keys = array_rand($identidadCandidates, 3);
    if (!is_array($keys)) {
        $keys = [$keys];
    }
    foreach ($keys as $k) {
        $identidadSlide3Paths[] = $identidadCandidates[$k];
    }
} else {
    $identidadSlide3Paths = array_slice($identidadCandidates, 0, 3);
}
while (count($identidadSlide3Paths) < 3) {
    $identidadSlide3Paths[] = !empty($identidadCandidates) ? $identidadCandidates[array_rand($identidadCandidates)] : null;
}
// Tres imágenes para slide 4 (Empresas y productos exportables) desde grupo Empresa
$empresaCandidates = [];
foreach (['Empresa1.jpg', 'Empresa2.jpg', 'Empresa3.jpg', 'Empresa4.jpg', 'Empresa5.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $empresaCandidates[] = $p;
    }
}
$empresaSlide4Paths = [];
if (count($empresaCandidates) >= 3) {
    $keys = array_rand($empresaCandidates, 3);
    $keys = is_array($keys) ? $keys : [$keys];
    foreach ($keys as $k) {
        $empresaSlide4Paths[] = $empresaCandidates[$k];
    }
} else {
    $empresaSlide4Paths = array_slice($empresaCandidates, 0, 3);
}
while (count($empresaSlide4Paths) < 3) {
    $empresaSlide4Paths[] = !empty($empresaCandidates) ? $empresaCandidates[array_rand($empresaCandidates)] : null;
}
// Imágenes Producto para slide Productos destacados (derecha: 25% × 50%)
$productoImgCandidates = [];
foreach (['Producto1.jpg', 'Producto2.jpg', 'Producto3.jpg', 'Producto4.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $productoImgCandidates[] = $p;
    }
}
$pdfLogoPath = $assetsDir . '/logo.png';
$pdfLogoWhitePath = $assetsDir . '/logo_white.png';
$pdfLogoUri = (file_exists($pdfLogoPath)) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pdfLogoPath)) : '';
$imgSlide2Path = $assetsDir . '/img_slide2.png';
$imgSlide3Path = $assetsDir . '/img_slide3.png';
$iconTelefonoPath = $assetsDir . '/telefono.png';
$iconMailPath = $assetsDir . '/mail.png';
$iconWebPath = $assetsDir . '/web.png';
$iconDireccionPath = $assetsDir . '/direccion.png';
$storageUploadsDir = $webRoot . '/uploads';
if (is_file($webRoot . '/includes/config/config.php')) {
    $storageConfig = @include $webRoot . '/includes/config/config.php';
    if (!empty($storageConfig['storage']['local']['base_path'])) {
        $storageUploadsDir = $storageConfig['storage']['local']['base_path'];
    }
}

// ——— Datos desde BD ———
$companies = [];
$rubros = [];
$metrics = ['empresas' => 0, 'productos' => 0];
$empresasDestacadas = [];
$productosMuestra = [];
$mercadosPorRegion = [];
$contactoInstitucional = $configInstitucional;

// Companies aprobadas (con start_date para año de inicio en slide empresa)
$q = "SELECT c.id, c.name, c.name_en, c.main_activity, c.main_activity_en, c.website, c.start_date
      FROM companies c
      INNER JOIN users u ON u.id = c.user_id
      WHERE c.moderation_status = 'approved'
      ORDER BY c.id ASC";
$res = mysqli_query($link, $q);
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $companies[] = $r;
    }
}
$metrics['empresas'] = count($companies);

// Rubros: distinct activity de products de empresas aprobadas (+ main_activity de companies)
$companyIds = array_column($companies, 'id');
$rubrosMap = [];
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT DISTINCT p.activity FROM products p WHERE p.company_id IN ($ids) AND p.activity IS NOT NULL AND p.activity != ''";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $rubrosMap[$row['activity']] = true;
        }
    }
    foreach ($companies as $c) {
        if (!empty($c['main_activity'])) {
            $rubrosMap[$c['main_activity']] = true;
        }
    }
}
$rubros = array_slice(array_keys($rubrosMap), 0, 3);
if (count($rubros) < 3) {
    $rubros = array_pad($rubros, 3, 'Otros sectores');
}

// Métricas: empresas con oferta y productos cargados
$metrics['productos'] = 0;
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT COUNT(*) AS n FROM products WHERE company_id IN ($ids)";
    $r = mysqli_query($link, $q);
    if ($r && ($row = mysqli_fetch_assoc($r))) {
        $metrics['productos'] = (int) $row['n'];
    }
}

// Empresas destacadas (hasta 3)
$empresasDestacadas = array_slice($companies, 0, 3);

// Productos y servicios para grilla HTML (hasta 6; sin filtrar por type para incluir ambos)
$productosMuestra = [];
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT p.id, p.name, p.name_en, p.activity, p.description, p.description_en, p.company_id, p.type
          FROM products p
          WHERE p.company_id IN ($ids)
          ORDER BY p.id ASC
          LIMIT 6";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $productosMuestra[] = $row;
        }
    }
}

// Un producto o servicio por empresa para slides "Productos exportables" (grilla 2×3, 6 por página; productos y servicios)
$productosParaSlides = [];
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT p.id, p.name, p.name_en, p.activity, p.description, p.description_en, p.annual_export, p.annual_export_en, p.certifications, p.certifications_en, p.company_id, p.type, p.tariff_code
          FROM products p
          INNER JOIN (SELECT company_id, MIN(id) AS mid FROM products WHERE company_id IN ($ids) GROUP BY company_id) first
          ON p.company_id = first.company_id AND p.id = first.mid
          ORDER BY p.company_id ASC";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $productosParaSlides[] = $row;
        }
    }
}

// Imágenes por producto/servicio (product_photo para productos, service_photo para servicios) — para muestra y para slides
$productIds = array_column($productosMuestra, 'id');
$productosParaSlidesIds = array_column($productosParaSlides, 'id');
$productIds = array_unique(array_merge($productIds, $productosParaSlidesIds));
$imagenesPorProducto = [];
if (!empty($productIds)) {
    $ids = implode(',', array_map('intval', $productIds));
    $q = "SELECT product_id, file_path FROM files WHERE product_id IN ($ids) AND file_type IN ('product_photo','product_photo_sec','service_photo') AND (is_temporary = 0 OR is_temporary IS NULL) ORDER BY product_id, id ASC";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $pid = (int) $row['product_id'];
            if (!isset($imagenesPorProducto[$pid])) {
                $path = ltrim($row['file_path'], '/');
                $full = $storageUploadsDir . '/' . $path;
                if (!file_exists($full)) {
                    $full = $webRoot . '/' . $path;
                }
                $imagenesPorProducto[$pid] = $full;
            }
        }
    }
}

// Logos por empresa (para slide Empresas destacadas — columna central: logo de cada empresa)
$logosPorEmpresa = [];
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT c.id AS company_id, f.file_path FROM companies c
          INNER JOIN files f ON f.user_id = c.user_id AND f.file_type = 'logo' AND (f.product_id IS NULL OR f.product_id = 0) AND (f.is_temporary = 0 OR f.is_temporary IS NULL)
          WHERE c.id IN ($ids) ORDER BY c.id, f.id DESC";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($logosPorEmpresa[$cid])) {
                $rel = ltrim($row['file_path'], '/');
                $fullPath = $storageUploadsDir . '/' . $rel;
                if (!file_exists($fullPath)) {
                    $fullPath = $webRoot . '/' . $rel;
                }
                $logosPorEmpresa[$cid] = $fullPath;
            }
        }
    }
}

// Imágenes por empresa destacada (primera imagen de algún producto de esa empresa; respaldo si no hay logo)
$imagenesPorEmpresa = [];
foreach ($empresasDestacadas as $emp) {
    $cid = (int) $emp['id'];
    $q = "SELECT f.file_path FROM files f
          INNER JOIN products p ON p.id = f.product_id AND p.company_id = ?
          WHERE f.file_type IN ('product_photo','product_photo_sec') AND (f.is_temporary = 0 OR f.is_temporary IS NULL)
          ORDER BY f.id ASC LIMIT 1";
    $stmt = mysqli_prepare($link, $q);
    mysqli_stmt_bind_param($stmt, 'i', $cid);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    if ($r && ($row = mysqli_fetch_assoc($r))) {
        $imagenesPorEmpresa[$cid] = $storageUploadsDir . '/' . ltrim($row['file_path'], '/');
    }
    mysqli_stmt_close($stmt);
}

// Localidad por empresa (company_addresses; EN PDF usa locality_en)
$localidadPorEmpresa = [];
$descripcionPorEmpresa = []; // breve descripción: primer producto por empresa, truncado
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT company_id, locality, locality_en FROM company_addresses WHERE company_id IN ($ids) ORDER BY company_id, id ASC";
    $r = @mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($localidadPorEmpresa[$cid])) {
                $loc = !empty(trim($row['locality_en'] ?? '')) ? trim($row['locality_en']) : (($row['locality'] !== null && $row['locality'] !== '') ? $row['locality'] : '');
                if ($loc !== '') $localidadPorEmpresa[$cid] = $loc;
            }
        }
    }
    $q = "SELECT company_id, description, description_en FROM products WHERE company_id IN ($ids) AND (description IS NOT NULL AND description != '' OR description_en IS NOT NULL AND description_en != '') ORDER BY company_id, id ASC";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($descripcionPorEmpresa[$cid])) {
                $d = !empty(trim((string)($row['description_en'] ?? ''))) ? trim($row['description_en']) : trim($row['description'] ?? '');
                $descripcionPorEmpresa[$cid] = mb_substr($d, 0, 120);
                if (mb_strlen($d) > 120) {
                    $descripcionPorEmpresa[$cid] .= '…';
                }
            }
        }
    }
}

// Redes sociales por empresa — solo nombre de red y enlace principal (ej. Instagram: /frre)
$redesPorEmpresa = [];
$formatSocialUrlToHandle = function ($url) {
    $u = trim($url);
    if ($u === '') return '';
    $u = preg_replace('#^https?://#i', '', $u);
    $u = preg_replace('#[?#].*$#', '', $u);
    $u = trim($u);
    $u = preg_replace('#^www\.#i', '', $u);
    $parts = array_values(array_filter(explode('/', $u), function ($p) { return $p !== ''; }));
    if (count($parts) === 0) return $u;
    $host = strtolower(preg_replace('/:\d+$/', '', $parts[0]));
    $pathSegments = array_slice($parts, 1);
    $socialHosts = ['instagram.com', 'facebook.com', 'fb.com', 'fb.me', 'linkedin.com', 'twitter.com', 'x.com', 'youtube.com', 'youtu.be', 'tiktok.com', 'wa.me', 'web.whatsapp.com', 't.me', 'telegram.me', 'vk.com', 'vkontakte.ru', 'vkontakte.com'];
    $skipSegments = ['p', 'reel', 'reels', 'stories', 'share', 'watch', 'pages', 'photo', 'video', 'in', 'company', 'sharing'];
    if (in_array($host, $socialHosts) && count($pathSegments) > 0) {
        while (count($pathSegments) > 0 && in_array(strtolower($pathSegments[0]), $skipSegments)) {
            array_shift($pathSegments);
        }
        if (count($pathSegments) > 0) {
            $handle = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $pathSegments[0]);
            return trim($handle) !== '' ? trim($handle) : $u;
        }
        return $parts[1] ?? $u;
    }
    foreach (['www.instagram.com/', 'instagram.com/', 'www.facebook.com/', 'facebook.com/', 'www.fb.com/', 'fb.com/', 'www.vk.com/', 'vk.com/', 'www.vkontakte.com/', 'vkontakte.com/', 'www.vkontakte.ru/', 'vkontakte.ru/', 'vkontakte.com'] as $dom) {
        $pos = stripos($u, $dom);
        if ($pos !== false) {
            $after = substr($u, $pos + strlen($dom));
            $after = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $after);
            $first = strpos($after, '/') !== false ? substr($after, 0, strpos($after, '/')) : $after;
            if (trim($first) !== '') return trim($first);
        }
    }
    return $u;
};
if (!empty($companyIds)) {
    $check = @mysqli_query($link, "SHOW TABLES LIKE 'company_social_networks'");
    if ($check && mysqli_num_rows($check) > 0) {
        $ids = implode(',', array_map('intval', $companyIds));
        $q = "SELECT company_id, network_type, url FROM company_social_networks WHERE company_id IN ($ids) ORDER BY company_id, id";
        $r = @mysqli_query($link, $q);
        if ($r) {
            while ($row = mysqli_fetch_assoc($r)) {
                $cid = (int) $row['company_id'];
                $t = trim($row['network_type'] ?? '');
                $u = trim($row['url'] ?? '');
                if ($u !== '') {
                    $display = $formatSocialUrlToHandle($u);
                    $display = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $display);
                    if ($display !== '') {
                        $display = preg_replace('~^/?(?:www\.)?(instagram\.com|facebook\.com|fb\.com|vk\.com|vkontakte\.ru|vkontakte\.com|youtube\.com|youtu\.be|tiktok\.com|t\.me|telegram\.me|linkedin\.com|reddit\.com|x\.com|twitter\.com)(?:/@?|$)~i', '', $display);
                    }
                    if ($display === '') {
                        $display = preg_replace('#^https?://#i', '', $u);
                        $display = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $display);
                        $display = preg_replace('~^/?(?:www\.)?(instagram\.com|facebook\.com|fb\.com|vk\.com|vkontakte\.ru|vkontakte\.com|youtube\.com|youtu\.be|tiktok\.com|t\.me|telegram\.me|linkedin\.com|reddit\.com|x\.com|twitter\.com)(?:/@?|$)~i', '', $display);
                    }
                    if ($display !== '') {
                        if (!isset($redesPorEmpresa[$cid])) {
                            $redesPorEmpresa[$cid] = [];
                        }
                        $redesPorEmpresa[$cid][] = ($t !== '' ? $t . ': /' : '/') . $display;
                    }
                }
            }
        }
    }
}

// Mercados objetivo: desde products.target_markets (principal) y company_data.target_markets (respaldo) para empresas aprobadas
$todosLosPaises = [];
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    // Some DBs may have only `target_markets` or only `target_markets_en`.
    // Avoid referencing missing columns (unknown column errors).
    $hasProductsTargetMarkets = false;
    $hasProductsTargetMarketsEn = false;

    $checkTm = @mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'target_markets'");
    if ($checkTm && mysqli_num_rows($checkTm) > 0) {
        $hasProductsTargetMarkets = true;
    }
    $checkTmen = @mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'target_markets_en'");
    if ($checkTmen && mysqli_num_rows($checkTmen) > 0) {
        $hasProductsTargetMarketsEn = true;
    }

    $cols = [];
    if ($hasProductsTargetMarkets) $cols[] = 'target_markets';
    if ($hasProductsTargetMarketsEn) $cols[] = 'target_markets_en';

    if (!empty($cols)) {
        $selectCols = implode(', ', $cols);
        $conds = [];
        if ($hasProductsTargetMarkets) {
            $conds[] = "(target_markets IS NOT NULL AND target_markets != '' AND target_markets != '[]')";
        }
        if ($hasProductsTargetMarketsEn) {
            $conds[] = "(target_markets_en IS NOT NULL AND target_markets_en != '' AND target_markets_en != '[]')";
        }
        $where = implode(' OR ', $conds);

        $q = "SELECT $selectCols FROM products WHERE company_id IN ($ids) AND ($where)";
        $res = @mysqli_query($link, $q);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $rawEn = $row['target_markets_en'] ?? null;
                $raw = (!empty(trim((string)($rawEn ?? '')))) ? $rawEn : ($row['target_markets'] ?? null);

                $dec = is_string($raw) ? json_decode($raw, true) : $raw;
                if (is_array($dec)) {
                    foreach ($dec as $p) {
                        if (is_string($p)) {
                            $todosLosPaises[] = trim($p);
                        } elseif (is_array($p)) {
                            $n = trim((string)($p['name'] ?? $p['nombre'] ?? ''));
                            if ($n !== '') $todosLosPaises[] = $n;
                        }
                    }
                }
            }
        }
    }
    // Respaldo: company_data.target_markets (datos antiguos o desde admin)
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $stmt = mysqli_prepare($link, "SELECT target_markets FROM company_data WHERE company_id IN ($placeholders)");
    $types = str_repeat('i', count($companyIds));
    mysqli_stmt_bind_param($stmt, $types, ...$companyIds);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['target_markets'])) {
                $dec = json_decode($row['target_markets'], true);
                if (is_array($dec)) {
                    foreach ($dec as $p) {
                        if (is_string($p)) {
                            $todosLosPaises[] = trim($p);
                        } elseif (is_array($p) && isset($p['nombre'])) {
                            $todosLosPaises[] = trim($p['nombre']);
                        }
                    }
                }
            }
        }
    }
    mysqli_stmt_close($stmt);
}
$todosLosPaises = array_unique(array_filter($todosLosPaises));
$chunks = array_chunk(array_values($todosLosPaises), max(1, (int) ceil(count($todosLosPaises) / 4)));
$mercadosPorRegion = array_pad($chunks, 4, []);

// Contacto: prioridad config; si se desea tomar de la primera empresa, se puede aquí
// $contactoInstitucional ya está en $configInstitucional

// ——— Dimensiones del slide (16:9, altura reducida) ———
$wMm = 450;
$hMm = 253;

// Parámetros del bloque logo (slide 1 API y slide 2 HTML) — reutilizables
$logoBlockConfig = [
    'x_mm' => 30,
    'y_mm' => 25,
    'rect_w_mm' => 70,
    'rect_h_mm' => 25,
    'height_mm' => 14,
    'pad_mm' => 5,
];

// ——— Construir HTML de las 7 slides (en partes para no superar pcre.backtrack_limit) ———
$companyNameById = [];
foreach ($companies as $c) {
    $companyNameById[(int)($c['id'] ?? 0)] = !empty(trim((string)($c['name_en'] ?? ''))) ? ($c['name_en'] ?? '') : ($c['name'] ?? '');
}
$htmlChunks = buildOfertaPdfHtml([
    'config'              => $configInstitucional,
    'logo_path'           => $logoPath,
    'cat_image_path'      => $catImagePath,
    'background_slide1_uri' => $backgroundSlide1Uri,
    'pdf_logo_uri'        => $pdfLogoUri,
    'web_root'            => $webRoot,
    'rubros'              => $rubros,
    'metrics'             => $metrics,
    'empresas_destacadas'  => $empresasDestacadas,
    'productos_muestra'   => $productosMuestra,
    'company_name_by_id'  => $companyNameById,
    'mercados_por_region' => $mercadosPorRegion,
    'contacto'            => $contactoInstitucional,
    'imagenes_producto'   => $imagenesPorProducto,
    'imagenes_empresa'    => $imagenesPorEmpresa,
    'slide_w_mm'         => $wMm,
    'slide_h_mm'         => $hMm,
    'logo_block_config'  => $logoBlockConfig,
]);

// ——— Generar PDF con mPDF ———
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => [$wMm, $hMm],
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
]);
$mpdf->SetDisplayMode('fullpage');
$mpdf->SetTitle($configInstitucional['titulo_documento'] . ' - ' . $configInstitucional['nombre_provincia']);

$redBarH = 5;
$contentH = $hMm - $redBarH;

for ($i = 0; $i < count($htmlChunks); $i++) {
    if ($i === 0) {
        // No usar HTML para la primera página; el slide 1 se dibuja solo por API (i=1)
        continue;
    } elseif ($i === 1) {
        // Primera página: solo API (sin WriteHTML), para que SetTextColor(255,255,255) se respete
        $mpdf->AddPage();
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetDrawColor(255, 255, 255);
        // Slide 1 corporativo: solo API. Sin línea blanca sobre el azul; azul = 44%, desplazado hacia abajo (más header).
        $s1Pad = 20;
        $s1MiddleH = round($hMm * 0.44);
        $remaining = $hMm - $s1MiddleH;
        $s1HeaderH = (int) round($remaining * 0.42);
        $s1FooterH = $remaining - $s1HeaderH;
        $s1MiddleY = $s1HeaderH;

        // Franja superior negra: logo más grande, margen izq. s1Pad; Página 01 margen der. s1Pad, fuente más grande
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s1HeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s1LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s1LogoX = $s1Pad;
        $s1LogoW = 44;
        $s1LogoH = 22;
        if (file_exists($s1LogoPath)) {
            $imgSize = @getimagesize($s1LogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s1LogoH * $r <= $s1LogoW) {
                    $lw = $s1LogoH * $r;
                    $lh = $s1LogoH;
                } else {
                    $lw = $s1LogoW;
                    $lh = $s1LogoW / $r;
                }
                $mpdf->Image($s1LogoPath, $s1LogoX, ($s1HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s1Pad - 36, ($s1HeaderH - 8) / 2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell(32, 8, 'Page 01', 0, 0, 'R');

        // Línea blanca bajo logo y Página 01, más arriba (cerca del logo), mismos márgenes s1Pad
        $s1LineH = 0.5;
        $s1LineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s1Pad, $s1HeaderH - $s1LineGap - $s1LineH, $wMm - 2 * $s1Pad, $s1LineH, 'F');

        // Zona central azul (#0B1878); empieza justo bajo el header
        $mpdf->SetFillColor(11, 24, 120);
        $mpdf->Rect(0, $s1MiddleY, $wMm, $s1MiddleH, 'F');

        // Franja inferior negra (se dibuja antes de la imagen para que la imagen quede encima)
        $s1FootY = $hMm - $s1FooterH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s1FootY, $wMm, $s1FooterH, 'F');

        // Preparar imagen recortada (cuadrada 40% del slide)
        $s1ImgBorder = 4;
        $s1ImgSize = round($hMm * 0.46);
        $s1ImgW = $s1ImgH = $s1ImgSize;
        $s1ImgBoxW = $s1ImgBoxH = $s1ImgSize + 2 * $s1ImgBorder;
        $s1ImgX = 38;
        $s1ImgOverlap = 6;
        $s1ImgY = $s1MiddleY - $s1ImgOverlap;
        $s1ImgCx = $s1ImgX + $s1ImgBoxW / 2;
        $s1ImgCy = $s1ImgY + $s1ImgBoxH / 2;
        $s1FramePath = null;
        $s1FrameWmm = $s1ImgBoxW;
        $s1FrameHmm = $s1ImgBoxH;
        $s1CroppedPath = null;
        $scale = 100 / 25.4;
        $boxWpx = (int) max(1, round($s1ImgBoxW * $scale));
        $boxHpx = (int) max(1, round($s1ImgBoxH * $scale));
        if (extension_loaded('gd') && function_exists('imagerotate')) {
            $boxImg = @imagecreatetruecolor($boxWpx, $boxHpx);
            if ($boxImg) {
                $white = (int) imagecolorallocate($boxImg, 255, 255, 255);
                imagefill($boxImg, 0, 0, $white);
                $bgRotate = 0x010101;
                $rotatedBox = @imagerotate($boxImg, -3, $bgRotate);
                imagedestroy($boxImg);
                if ($rotatedBox) {
                    imagesavealpha($rotatedBox, true);
                    imagealphablending($rotatedBox, false);
                    $rotWpx = imagesx($rotatedBox);
                    $rotHpx = imagesy($rotatedBox);
                    $trans = (int) imagecolorallocatealpha($rotatedBox, 1, 1, 1, 127);
                    for ($py = 0; $py < $rotHpx; $py++) {
                        for ($px = 0; $px < $rotWpx; $px++) {
                            $c = imagecolorat($rotatedBox, $px, $py);
                            $r = ($c >> 16) & 0xFF;
                            $g = ($c >> 8) & 0xFF;
                            $b = $c & 0xFF;
                            if ($r <= 1 && $g <= 1 && $b <= 1) {
                                imagesetpixel($rotatedBox, $px, $py, $trans);
                            }
                        }
                    }
                    $s1FrameWmm = $rotWpx / $scale;
                    $s1FrameHmm = $rotHpx / $scale;
                    $tmpFrame = sys_get_temp_dir() . '/corp_frame_' . uniqid() . '.png';
                    if (imagepng($rotatedBox, $tmpFrame)) {
                        $s1FramePath = $tmpFrame;
                    }
                    imagedestroy($rotatedBox);
                }
            }
        }
        if ($backgroundSlide1Path && file_exists($backgroundSlide1Path) && extension_loaded('gd')) {
            $info = @getimagesize($backgroundSlide1Path);
            $ext = $info ? strtolower(pathinfo($backgroundSlide1Path, PATHINFO_EXTENSION)) : '';
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($backgroundSlide1Path);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($backgroundSlide1Path);
            } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($backgroundSlide1Path);
            }
            if ($src) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $minSide = min($sw, $sh);
                $srcX = (int) floor(($sw - $minSide) / 2);
                $srcY = (int) floor(($sh - $minSide) / 2);
                $dw = (int) max(1, round($s1ImgSize * $scale));
                $dst = imagecreatetruecolor($dw, $dw);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dw, $dw, $minSide, $minSide)) {
                    if (function_exists('imagerotate')) {
                        $rotated = @imagerotate($dst, -3, 0xFFFFFF);
                        if ($rotated) {
                            imagedestroy($dst);
                            $dst = $rotated;
                        }
                    }
                    $tmp = sys_get_temp_dir() . '/corp_portada_sq_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $s1CroppedPath = $tmp;
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        }
        // Marco blanco rotado -3°: dibujar en tamaño natural (sin escalar al rectángulo) para que se vea el giro
        if ($s1FramePath && file_exists($s1FramePath)) {
            $frameX = $s1ImgCx - $s1FrameWmm / 2;
            $frameY = $s1ImgCy - $s1FrameHmm / 2;
            $mpdf->Image($s1FramePath, $frameX, $frameY, $s1FrameWmm, $s1FrameHmm);
            @unlink($s1FramePath);
        } else {
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($s1ImgX, $s1ImgY, $s1ImgBoxW, $s1ImgBoxH, 'F');
        }
        if ($s1CroppedPath && file_exists($s1CroppedPath)) {
            $mpdf->Image($s1CroppedPath, $s1ImgX + $s1ImgBorder, $s1ImgY + $s1ImgBorder, $s1ImgW, $s1ImgH);
            @unlink($s1CroppedPath);
        } elseif ($backgroundSlide1Path && file_exists($backgroundSlide1Path)) {
            $mpdf->Image($backgroundSlide1Path, $s1ImgX + $s1ImgBorder, $s1ImgY + $s1ImgBorder, $s1ImgW, $s1ImgH);
        }

        // Texto a la derecha — solo API, SetTextColor(255,255,255) se mantiene porque no hay Rotate
        $s1TextLeft = $s1ImgX + $s1ImgBoxW + 24;
        $s1TextW = $wMm - $s1TextLeft - $s1Pad;
        $mpdf->SetLeftMargin($s1TextLeft);
        $mpdf->SetRightMargin($s1Pad);
        $s1Ty = $s1MiddleY + 28;
        $mpdf->SetXY($s1TextLeft, $s1Ty);
        $mpdf->SetFont('dejavusans', 'B', 56);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell($s1TextW, 20, 'EXPORTABLE', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 56);
        $mpdf->Cell($s1TextW, 20, 'SUPPLY', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 22);
        $mpdf->SetXY($s1TextLeft, $s1Ty + 48);
        $mpdf->Cell($s1TextW, 10, function_exists('mb_strtoupper') ? mb_strtoupper($configInstitucional['nombre_provincia']) : strtoupper($configInstitucional['nombre_provincia']), 0, 0, 'L');

        // Pie: Edición 2026 y flecha — mismos márgenes s1Pad que header; fuente más grande, flecha más ancha y grande
        $s1FootTextY = $s1FootY + ($s1FooterH - 10) / 2 + 1;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 22);
        $mpdf->SetXY($s1Pad, $s1FootTextY);
        $mpdf->Cell($wMm - 2 * $s1Pad - 28, 10, 'Edition ' . $configInstitucional['periodo_ano'], 0, 0, 'L');
        $mpdf->SetFont('dejavusans', 'B', 28);
        $mpdf->SetXY($wMm - $s1Pad - 24, $s1FootTextY - 2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell(22, 12, chr(0xE2) . chr(0x86) . chr(0x92), 0, 0, 'R');

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        // Slide 2: шапка como slide 1 (negra, logo sin texto, Página 02, línea blanca); fondo negro; izquierda texto, derecha 2 imágenes Productivo
        $s2Pad = 20;
        $s2MiddleH = round($hMm * 0.44);
        $s2Remaining = $hMm - $s2MiddleH;
        $s2HeaderH = (int) round($s2Remaining * 0.42);
        $s2ContentY = $s2HeaderH;
        $s2ContentH = $hMm - $s2HeaderH;
        // Franja superior negra = igual que slide 1
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s2HeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s2LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s2LogoW = 44;
        $s2LogoH = 22;
        if (file_exists($s2LogoPath)) {
            $imgSize = @getimagesize($s2LogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s2LogoH * $r <= $s2LogoW) {
                    $lw = $s2LogoH * $r;
                    $lh = $s2LogoH;
                } else {
                    $lw = $s2LogoW;
                    $lh = $s2LogoW / $r;
                }
                $mpdf->Image($s2LogoPath, $s2Pad, ($s2HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s2Pad - 36, ($s2HeaderH - 8) / 2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell(32, 8, 'Page 02', 0, 0, 'R');
        $s2LineH = 0.5;
        $s2LineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s2Pad, $s2HeaderH - $s2LineGap - $s2LineH, $wMm - 2 * $s2Pad, $s2LineH, 'F');
        // Zona de contenido negra
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s2ContentY, $wMm, $s2ContentH, 'F');
        // Columna izquierda: texto (ancho reducido ~36%); отступ слева как у шапки (s2Pad)
        $s2LeftW = round($wMm * 0.36);
        $s2TextPad = 16;
        $s2TextLeft = $s2Pad;
        $s2TextW = $s2LeftW - $s2Pad - $s2TextPad;
        $s2TextTop = $s2ContentY + 7;
        $s2FontSize = 15;
        $s2LineHeight = 7;
        $s2ImgGap = 8;
        $s2ImgW = ($wMm - $s2LeftW - 16 - $s2ImgGap - $s2Pad) / 2 * 0.88;
        $s2ImgH = round($s2ContentH * 0.82);
        $s2ImgY = $s2ContentY + 4;
        $s2ImgBottom = $s2ImgY + $s2ImgH;
        $s2ImgRightEdge = $wMm - $s2Pad;
        $s2RightX = $s2ImgRightEdge - 2 * $s2ImgW - $s2ImgGap;
        $mpdf->SetLeftMargin($s2TextLeft);
        $mpdf->SetRightMargin($s2LeftW);
        $mpdf->SetXY($s2TextLeft, $s2TextTop);
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->Cell($s2TextW, 12, 'PROVINCIAL', 0, 1, 'L');
        $mpdf->Ln(3);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 30);
        $mpdf->Cell($s2TextW, 11, 'CONTEXT', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', $s2FontSize);
        $mpdf->Ln(9);
        $s2Cell = function ($bold, $txt, $ln = 0) use ($mpdf, $s2LineHeight, $s2FontSize) {
            $mpdf->SetFont('dejavusans', $bold ? 'B' : '', $s2FontSize);
            $w = $mpdf->GetStringWidth($txt);
            $mpdf->Cell($w, $s2LineHeight, $txt, 0, $ln, 'L');
        };
        $s2Cell(true, 'Santiago del Estero', 0);
        $s2Cell(false, ' promotes a ', 0);
        $s2Cell(true, 'Provincial Exportable Supply', 1);
        $s2Cell(false, ' to showcase, organize and promote its ', 1);
        $s2Cell(true, 'productive', 0);
        $s2Cell(false, ' landscape to promotion agencies, trade ', 1);
        $s2Cell(false, 'missions and buyers.', 1);
        $mpdf->Ln(9);
        $s2Cell(false, 'This presentation brings together ', 0);
        $s2Cell(true, 'information declared by', 1);
        $s2Cell(true, 'registered companies, focusing on exportable', 1);
        $s2Cell(true, 'products and services.', 1);
        $mpdf->Ln(9);
        $s2Cell(false, 'The initiative aims to ', 0);
        $s2Cell(true, 'facilitate access to key data, improve', 1);
        $s2Cell(true, 'institutional outreach and enable trade', 1);
        $s2Cell(true, 'linkage opportunities, strengthening a modern,', 1);
        $s2Cell(true, 'inclusive and federal export culture.', 1);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s2TextLeft, $s2ImgBottom - 0.5, $s2TextW, 0.5, 'F');
        // Columna derecha: dos imágenes Productivo
        $s2Scale = 100 / 25.4;
        $s2DstWpx = (int) max(1, round($s2ImgW * $s2Scale));
        $s2DstHpx = (int) max(1, round($s2ImgH * $s2Scale));
        foreach ([0, 1] as $idx) {
            $path = isset($productivoSlide2Paths[$idx]) ? $productivoSlide2Paths[$idx] : null;
            if (!$path || !file_exists($path)) continue;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            $s2CropPath = null;
            if ($src && $s2DstWpx > 0 && $s2DstHpx > 0) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $boxRatio = $s2ImgW / $s2ImgH;
                $imgRatio = $sw / $sh;
                if ($imgRatio >= $boxRatio) {
                    $cropW = (int) round($sh * $boxRatio);
                    $cropH = $sh;
                    $srcX = (int) floor(($sw - $cropW) / 2);
                    $srcY = 0;
                } else {
                    $cropW = $sw;
                    $cropH = (int) round($sw / $boxRatio);
                    $srcX = 0;
                    $srcY = (int) floor(($sh - $cropH) / 2);
                }
                $dst = @imagecreatetruecolor($s2DstWpx, $s2DstHpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $s2DstWpx, $s2DstHpx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/corp_s2_img_' . $idx . '_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) $s2CropPath = $tmp;
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
            $s2ImgX = $s2RightX + $idx * ($s2ImgW + $s2ImgGap);
            if ($s2CropPath && file_exists($s2CropPath)) {
                $mpdf->Image($s2CropPath, $s2ImgX, $s2ImgY, $s2ImgW, $s2ImgH);
                @unlink($s2CropPath);
            } else {
                $mpdf->Image($path, $s2ImgX, $s2ImgY, $s2ImgW, $s2ImgH);
            }
        }
        // No incrementar $i: en la siguiente iteración (i=2) se hará AddPage() para el slide 3
    } elseif ($i === 2) {
        // Chunk 2 = slide 2 ya dibujado por API en i=1; solo añadir página para el slide 3
        $mpdf->AddPage();
    } elseif ($i === 3) {
        // Slide 3: Identidad provincial — шапка como slide 1; izquierda collage 3 imágenes identidad (2 apiladas + 1 vertical); derecha IDENTIDAD/PROVINCIAL y párrafo
        $mpdf->SetXY(0, 0);
        $s3Pad = 20;
        $s3MiddleH = round($hMm * 0.44);
        $s3Remaining = $hMm - $s3MiddleH;
        $s3HeaderH = (int) round($s3Remaining * 0.42);
        $s3ContentY = $s3HeaderH;
        $s3ContentH = $hMm - $s3HeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3HeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3LogoW = 44;
        $s3LogoH = 22;
        if (file_exists($s3LogoPath)) {
            $imgSize = @getimagesize($s3LogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3LogoH * $r <= $s3LogoW) {
                    $lw = $s3LogoH * $r;
                    $lh = $s3LogoH;
                } else {
                    $lw = $s3LogoW;
                    $lh = $s3LogoW / $r;
                }
                $mpdf->Image($s3LogoPath, $s3Pad, ($s3HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3Pad - 36, ($s3HeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 03', 0, 0, 'R');
        $s3LineH = 0.5;
        $s3LineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3Pad, $s3HeaderH - $s3LineGap - $s3LineH, $wMm - 2 * $s3Pad, $s3LineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3ContentY, $wMm, $s3ContentH, 'F');
        $s3ContentPad = $s3Pad;
        $s3CollageY = $s3ContentY + $s3ContentPad;
        $s3CollageH = $s3ContentH - 2 * $s3ContentPad;
        $s3CollageW = round($wMm * 0.50);
        $s3CollageGap = 5;
        $s3BoxLeftW = ($s3CollageW - $s3CollageGap) / 2;
        $s3BoxLeftH = ($s3CollageH - $s3CollageGap) / 2;
        $s3Scale = 100 / 25.4;
        $s3LoadCrop = function ($path, $boxW, $boxH) use ($s3Scale) {
            if (!$path || !file_exists($path) || !extension_loaded('gd')) return null;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            if (!$src) return null;
            $sw = imagesx($src);
            $sh = imagesy($src);
            $dwPx = (int) max(1, round($boxW * $s3Scale));
            $dhPx = (int) max(1, round($boxH * $s3Scale));
            $ratio = $boxW / $boxH;
            $imgR = $sw / $sh;
            if ($imgR >= $ratio) {
                $cropW = (int) round($sh * $ratio);
                $cropH = $sh;
                $srcX = (int) floor(($sw - $cropW) / 2);
                $srcY = 0;
            } else {
                $cropW = $sw;
                $cropH = (int) round($sw / $ratio);
                $srcX = 0;
                $srcY = (int) floor(($sh - $cropH) / 2);
            }
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/corp_s3_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        $s3Boxes = [
            ['x' => $s3Pad, 'y' => $s3CollageY, 'w' => $s3BoxLeftW, 'h' => $s3BoxLeftH],
            ['x' => $s3Pad, 'y' => $s3CollageY + $s3BoxLeftH + $s3CollageGap, 'w' => $s3BoxLeftW, 'h' => $s3BoxLeftH],
            ['x' => $s3Pad + $s3BoxLeftW + $s3CollageGap, 'y' => $s3CollageY, 'w' => $s3BoxLeftW, 'h' => $s3CollageH],
        ];
        foreach ([0, 1, 2] as $idx) {
            $path = isset($identidadSlide3Paths[$idx]) ? $identidadSlide3Paths[$idx] : null;
            $box = $s3Boxes[$idx];
            $tmp = $s3LoadCrop($path, $box['w'], $box['h']);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $box['x'], $box['y'], $box['w'], $box['h']);
                @unlink($tmp);
            } elseif ($path && file_exists($path)) {
                $mpdf->Image($path, $box['x'], $box['y'], $box['w'], $box['h']);
            } else {
                $mpdf->SetFillColor(60, 60, 60);
                $mpdf->Rect($box['x'], $box['y'], $box['w'], $box['h'], 'F');
            }
        }
        $s3TextLeft = $s3CollageW + 40;
        $s3TextW = $wMm - $s3TextLeft - $s3Pad;
        $s3TitleY = $s3ContentY + 34;
        $s3TitleGap = 6;
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 42);
        $mpdf->SetXY($s3TextLeft, $s3TitleY);
        $mpdf->Cell($s3TextW, 16, 'PROVINCIAL', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3TextLeft, $s3TitleY + 16 + $s3TitleGap);
        $mpdf->Cell($s3TextW, 14, 'IDENTITY', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->Ln(6);
        $s3LineH = 7;
        $s3ParaY = $s3TitleY + 16 + $s3TitleGap + 14 + 6;
        $s3Cell = function ($bold, $txt, $ln = 0) use ($mpdf, $s3LineH) {
            $mpdf->SetFont('dejavusans', $bold ? 'B' : '', 15);
            $w = $mpdf->GetStringWidth($txt);
            $mpdf->Cell($w, $s3LineH, $txt, 0, $ln, 'L');
        };
        $mpdf->SetXY($s3TextLeft, $s3ParaY);
        $s3Cell(false, 'A territory with ', 0);
        $s3Cell(true, 'diverse productive capabilities', 0);
        $s3Cell(false, ' and strong', 1);
        $mpdf->SetXY($s3TextLeft, $s3ParaY + $s3LineH);
        $s3Cell(true, 'potentialfor business', 0);
        $s3Cell(false, ' and trade linkages.', 1);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Slide 3bis: Estratégico — header negro (logo, Página 04, línea); zona negra con título SANTIAGO DEL ESTERO, ESTRATÉGICO + flecha, dos párrafos; imagen abajo
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3bPad = 20;
        $s3bMiddleH = round($hMm * 0.44);
        $s3bRemaining = $hMm - $s3bMiddleH;
        $s3bHeaderH = (int) round($s3bRemaining * 0.42);
        $s3bContentY = $s3bHeaderH;
        $s3bContentH = $hMm - $s3bHeaderH;
        $s3bImgH = round($hMm * 0.34);
        $s3bTextH = $s3bContentH - $s3bImgH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3bHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3bLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3bLogoW = 44;
        $s3bLogoH = 22;
        if (file_exists($s3bLogoPath)) {
            $imgSize = @getimagesize($s3bLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3bLogoH * $r <= $s3bLogoW) {
                    $lw = $s3bLogoH * $r;
                    $lh = $s3bLogoH;
                } else {
                    $lw = $s3bLogoW;
                    $lh = $s3bLogoW / $r;
                }
                $mpdf->Image($s3bLogoPath, $s3bPad, ($s3bHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3bPad - 36, ($s3bHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 04', 0, 0, 'R');
        $s3bLineH = 0.5;
        $s3bLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3bPad, $s3bHeaderH - $s3bLineGap - $s3bLineH, $wMm - 2 * $s3bPad, $s3bLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3bContentY, $wMm, $s3bContentH, 'F');
        $s3bTextPad = 24;
        $s3bTextW = $wMm - 2 * $s3bTextPad;
        $s3bTitleY = $s3bContentY + 6;
        $s3bTitleGap = 2;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 38);
        $mpdf->SetXY($s3bTextPad, $s3bTitleY);
        $mpdf->Cell($s3bTextW, 14, 'SANTIAGO DEL ESTERO', 0, 1, 'L');
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 38);
        $mpdf->SetXY($s3bTextPad, $s3bTitleY + 14 + $s3bTitleGap);
        $mpdf->Cell($s3bTextW - 16, 14, 'STRATEGIC', 0, 0, 'L');
        // Icono (reemplaza flecha): icon_rect.png grande y rotado 45° a la derecha
        $s3bIconPath = $assetsDir . '/icon_rect.png';
        $s3bIconSize = 62;
        $s3bIconX = $wMm - $s3bTextPad - $s3bIconSize;
        $s3bIconY = $s3bTitleY + 14 + $s3bTitleGap - 40;
        if ($s3bIconPath && file_exists($s3bIconPath)) {
            $s3bIconToRender = $s3bIconPath;
            if (extension_loaded('gd')) {
                $icon = @imagecreatefrompng($s3bIconPath);
                if ($icon && function_exists('imagerotate')) {
                    imagesavealpha($icon, true);
                    imagealphablending($icon, true);
                    $transparent = (int) imagecolorallocatealpha($icon, 0, 0, 0, 127);
                    // imagerotate rotates counter-clockwise; -45 = 45° clockwise (to the right)
                    $rot = @imagerotate($icon, -45, $transparent);
                    if ($rot) {
                        imagesavealpha($rot, true);
                        imagealphablending($rot, false);
                        $tmp = sys_get_temp_dir() . '/corp_s3b_icon_' . uniqid() . '.png';
                        if (@imagepng($rot, $tmp)) {
                            $s3bIconToRender = $tmp;
                        }
                        imagedestroy($rot);
                    }
                    imagedestroy($icon);
                } elseif ($icon) {
                    imagedestroy($icon);
                }
            }
            $mpdf->Image($s3bIconToRender, $s3bIconX, $s3bIconY, $s3bIconSize, $s3bIconSize);
            if ($s3bIconToRender !== $s3bIconPath && file_exists($s3bIconToRender)) {
                @unlink($s3bIconToRender);
            }
        }
        $s3bParaY = $s3bTitleY + 14 + $s3bTitleGap + 16 + 8;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 16);
        $mpdf->SetXY($s3bTextPad, $s3bParaY);
        $s3bParaW = max(1, $s3bTextW - 60);
        $mpdf->MultiCell($s3bParaW, 7, 'Santiago del Estero occupies a key position in Northern Argentina and is integrated into connectivity axes that link regional production with logistics corridors to the Atlantic and the Pacific.', 0, 'L');
        $mpdf->SetXY($s3bTextPad, $mpdf->y + 6);
        $mpdf->MultiCell($s3bParaW, 7, 'With expanding road infrastructure and territorial articulation, the province facilitates the movement of goods, access to markets, and the creation of new investment opportunities.', 0, 'L');
        $s3bImgY = $hMm - $s3bImgH;
        $s3bImgPath = $assetsDir . '/MOTOR_ECONOMICO.jpg';
        $s3bScale = 100 / 25.4;
        $s3bDstWpx = (int) max(1, round($wMm * $s3bScale));
        $s3bDstHpx = (int) max(1, round($s3bImgH * $s3bScale));
        if ($s3bImgPath && file_exists($s3bImgPath) && extension_loaded('gd')) {
            $info = @getimagesize($s3bImgPath);
            $ext = strtolower(pathinfo($s3bImgPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($s3bImgPath);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($s3bImgPath);
            } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($s3bImgPath);
            }
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $scale = max($s3bDstWpx / $sw, $s3bDstHpx / $sh);
                $cropW = (int) min($sw, round($s3bDstWpx / $scale));
                $cropH = (int) min($sh, round($s3bDstHpx / $scale));
                $srcX = (int) max(0, round(($sw - $cropW) / 2));
                $srcY = (int) max(0, round(($sh - $cropH) * 0.26));
                $dst = @imagecreatetruecolor($s3bDstWpx, $s3bDstHpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $s3bDstWpx, $s3bDstHpx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/corp_s3b_img_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, 0, $s3bImgY, $wMm, $s3bImgH);
                        @unlink($tmp);
                    } else {
                        $mpdf->Image($s3bImgPath, 0, $s3bImgY, $wMm, $s3bImgH);
                    }
                    imagedestroy($dst);
                } else {
                    $mpdf->Image($s3bImgPath, 0, $s3bImgY, $wMm, $s3bImgH);
                }
                imagedestroy($src);
            } else {
                if ($src) {
                    imagedestroy($src);
                }
                $mpdf->SetFillColor(60, 60, 60);
                $mpdf->Rect(0, $s3bImgY, $wMm, $s3bImgH, 'F');
            }
        } elseif ($s3bImgPath && file_exists($s3bImgPath)) {
            $mpdf->Image($s3bImgPath, 0, $s3bImgY, $wMm, $s3bImgH);
        } else {
            $mpdf->SetFillColor(60, 60, 60);
            $mpdf->Rect(0, $s3bImgY, $wMm, $s3bImgH, 'F');
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Slide 3ter: Estructura productiva y sectores clave — header negro + panel azul + imagen derecha
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3cPad = 20;
        $s3cMiddleH = round($hMm * 0.44);
        $s3cRemaining = $hMm - $s3cMiddleH;
        $s3cHeaderH = (int) round($s3cRemaining * 0.42);
        $s3cContentY = $s3cHeaderH;
        $s3cContentH = $hMm - $s3cHeaderH;
        // Header
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3cHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3cLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3cLogoW = 44;
        $s3cLogoH = 22;
        if (file_exists($s3cLogoPath)) {
            $imgSize = @getimagesize($s3cLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3cLogoH * $r <= $s3cLogoW) {
                    $lw = $s3cLogoH * $r;
                    $lh = $s3cLogoH;
                } else {
                    $lw = $s3cLogoW;
                    $lh = $s3cLogoW / $r;
                }
                $mpdf->Image($s3cLogoPath, $s3cPad, ($s3cHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3cPad - 36, ($s3cHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 05', 0, 0, 'R');
        $s3cLineH = 0.5;
        $s3cLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3cPad, $s3cHeaderH - $s3cLineGap - $s3cLineH, $wMm - 2 * $s3cPad, $s3cLineH, 'F');

        // Content background (black)
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3cContentY, $wMm, $s3cContentH, 'F');

        // Image block (right): 45% width, 65% height of slide; bottom-aligned
        // Drawn first so blue panel can sit on top.
        $s3cImgW = round($wMm * 0.45);
        $s3cImgH = round($hMm * 0.65);
        $s3cImgX = $wMm - $s3cImgW;
        $s3cImgY = $hMm - $s3cImgH;
        $s3cImgPath = $assetsDir . '/Productivo2.jpg';
        if ($s3cImgPath && file_exists($s3cImgPath)) {
            $s3cScale = 100 / 25.4;
            $dstWpx = (int) max(1, round($s3cImgW * $s3cScale));
            $dstHpx = (int) max(1, round($s3cImgH * $s3cScale));
            if (extension_loaded('gd')) {
                $info = @getimagesize($s3cImgPath);
                $ext = strtolower(pathinfo($s3cImgPath, PATHINFO_EXTENSION));
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s3cImgPath);
                elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s3cImgPath);
                elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s3cImgPath);
                if ($src && !empty($info[0]) && !empty($info[1])) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $scale = max($dstWpx / $sw, $dstHpx / $sh);
                    $cropW = (int) min($sw, round($dstWpx / $scale));
                    $cropH = (int) min($sh, round($dstHpx / $scale));
                    $srcX = (int) max(0, round(($sw - $cropW) / 2));
                    $srcY = 0;
                    $dst = @imagecreatetruecolor($dstWpx, $dstHpx);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstWpx, $dstHpx, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/corp_s3c_img_' . uniqid() . '.png';
                        if (@imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $s3cImgX, $s3cImgY, $s3cImgW, $s3cImgH);
                            @unlink($tmp);
                        } else {
                            $mpdf->Image($s3cImgPath, $s3cImgX, $s3cImgY, $s3cImgW, $s3cImgH);
                        }
                        imagedestroy($dst);
                    } else {
                        $mpdf->Image($s3cImgPath, $s3cImgX, $s3cImgY, $s3cImgW, $s3cImgH);
                    }
                    imagedestroy($src);
                } else {
                    if ($src) imagedestroy($src);
                    $mpdf->Image($s3cImgPath, $s3cImgX, $s3cImgY, $s3cImgW, $s3cImgH);
                }
            } else {
                $mpdf->Image($s3cImgPath, $s3cImgX, $s3cImgY, $s3cImgW, $s3cImgH);
            }
        }

        // Blue panel (left) drawn on top of the image
        $s3cPanelX = 0;
        $s3cPanelY = $s3cContentY + 2;
        $s3cPanelW = (int) round($wMm * 0.74);
        $s3cPanelH = $s3cContentH - 35;
        $mpdf->SetFillColor(11, 24, 120);
        $mpdf->Rect($s3cPanelX, $s3cPanelY, $s3cPanelW, $s3cPanelH, 'F');

        $s3cTextPadX = 34;
        $s3cTextX = $s3cTextPadX;
        $s3cTextY = $s3cPanelY + 46;
        // Give the title more width to avoid extra wrapping; keep body text narrower.
        $s3cTitleW = $s3cPanelW - $s3cTextPadX - 10;
        $s3cBodyW = $s3cPanelW - $s3cTextPadX - 135;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3cTextX, $s3cTextY);
        $mpdf->MultiCell($s3cTitleW, 13, "PRODUCTIVE STRUCTURE AND\nKEY SECTORS", 0, 'L');

        $mpdf->SetFont('dejavusans', '', 14);
        $mpdf->SetXY($s3cTextX, $mpdf->y + 8);
        $mpdf->MultiCell($s3cBodyW, 6, 'Santiago del Estero presents a diversified productive matrix based on natural resources, agro-industrial development, energy growth, and consolidation of regional economies with national and international projection.', 0, 'L');

        // Icon inside panel (rotate 45° to the left)
        $s3cIconPath = $assetsDir . '/icon_rect.png';
        if ($s3cIconPath && file_exists($s3cIconPath)) {
            $s3cIconToRender = $s3cIconPath;
            if (extension_loaded('gd')) {
                $icon = @imagecreatefrompng($s3cIconPath);
                if ($icon && function_exists('imagerotate')) {
                    imagesavealpha($icon, true);
                    imagealphablending($icon, true);
                    $transparent = (int) imagecolorallocatealpha($icon, 0, 0, 0, 127);
                    // imagerotate rotates counter-clockwise; 90 = 90° to the left
                    $rot = @imagerotate($icon, 90, $transparent);
                    if ($rot) {
                        imagesavealpha($rot, true);
                        imagealphablending($rot, false);
                        $tmp = sys_get_temp_dir() . '/corp_s3c_icon_' . uniqid() . '.png';
                        if (@imagepng($rot, $tmp)) {
                            $s3cIconToRender = $tmp;
                        }
                        imagedestroy($rot);
                    }
                    imagedestroy($icon);
                } elseif ($icon) {
                    imagedestroy($icon);
                }
            }
            $iconSize = 62;
            $iconX = $s3cPanelW - $iconSize - 38;
            $iconY = $s3cPanelY + (int) round($s3cPanelH * 0.58);
            $mpdf->Image($s3cIconToRender, $iconX, $iconY, $iconSize, $iconSize);
            if ($s3cIconToRender !== $s3cIconPath && file_exists($s3cIconToRender)) {
                @unlink($s3cIconToRender);
            }
        }

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Slide 3quater (sectors): header negro; centro Productivo5.jpg; izq 01 AGROINDUSTRIA + 02 GANADERÍA; der 03 REGIONALES + 04 DESARROLLO — Página 06
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3ePad = 20;
        $s3eMiddleH = round($hMm * 0.44);
        $s3eRemaining = $hMm - $s3eMiddleH;
        $s3eHeaderH = (int) round($s3eRemaining * 0.42);
        $s3eContentY = $s3eHeaderH;
        $s3eContentH = $hMm - $s3eHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3eHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3eLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3eLogoW = 44;
        $s3eLogoH = 22;
        if (file_exists($s3eLogoPath)) {
            $imgSize = @getimagesize($s3eLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3eLogoH * $r <= $s3eLogoW) {
                    $lw = $s3eLogoH * $r;
                    $lh = $s3eLogoH;
                } else {
                    $lw = $s3eLogoW;
                    $lh = $s3eLogoW / $r;
                }
                $mpdf->Image($s3eLogoPath, $s3ePad, ($s3eHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3ePad - 36, ($s3eHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 06', 0, 0, 'R');
        $s3eLineH = 0.5;
        $s3eLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3ePad, $s3eHeaderH - $s3eLineGap - $s3eLineH, $wMm - 2 * $s3ePad, $s3eLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3eContentY, $wMm, $s3eContentH, 'F');

        $s3eGap = 12;
        $s3eImgW = round($wMm * 0.35);
        $s3eColW = (int) round(($wMm - 2 * $s3ePad - $s3eImgW - 2 * $s3eGap) / 2);
        $s3eImgH = $s3eContentH - 10;
        $s3eImgY = $s3eContentY + 5;
        $s3eImgX = $s3ePad + $s3eColW + $s3eGap;
        $s3eImgPath = $assetsDir . '/Productivo5.jpg';
        if ($s3eImgPath && file_exists($s3eImgPath)) {
            $s3eScale = 100 / 25.4;
            $dstWpx = (int) max(1, round($s3eImgW * $s3eScale));
            $dstHpx = (int) max(1, round($s3eImgH * $s3eScale));
            if (extension_loaded('gd')) {
                $info = @getimagesize($s3eImgPath);
                $ext = strtolower(pathinfo($s3eImgPath, PATHINFO_EXTENSION));
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s3eImgPath);
                elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s3eImgPath);
                elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s3eImgPath);
                if ($src && !empty($info[0]) && !empty($info[1])) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $scale = max($dstWpx / $sw, $dstHpx / $sh);
                    $cropW = (int) min($sw, round($dstWpx / $scale));
                    $cropH = (int) min($sh, round($dstHpx / $scale));
                    $srcX = (int) max(0, round(($sw - $cropW) / 2));
                    $srcY = (int) max(0, round(($sh - $cropH) / 2));
                    $dst = @imagecreatetruecolor($dstWpx, $dstHpx);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstWpx, $dstHpx, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/corp_s3e_img_' . uniqid() . '.png';
                        if (@imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $s3eImgX, $s3eImgY, $s3eImgW, $s3eImgH);
                            @unlink($tmp);
                        } else {
                            $mpdf->Image($s3eImgPath, $s3eImgX, $s3eImgY, $s3eImgW, $s3eImgH);
                        }
                        imagedestroy($dst);
                    } else {
                        $mpdf->Image($s3eImgPath, $s3eImgX, $s3eImgY, $s3eImgW, $s3eImgH);
                    }
                    imagedestroy($src);
                } else {
                    if ($src) imagedestroy($src);
                    $mpdf->Image($s3eImgPath, $s3eImgX, $s3eImgY, $s3eImgW, $s3eImgH);
                }
            } else {
                $mpdf->Image($s3eImgPath, $s3eImgX, $s3eImgY, $s3eImgW, $s3eImgH);
            }
        }

        $s3eNumFont = 24;
        $s3eTitleFont = 18;
        $s3eBulletFont = 12;
        $s3eNumW = 12;
        $s3eNumToTitleGap = 6;
        $s3eTitleXOff = $s3eNumW + $s3eNumToTitleGap;
        $s3eLineUnder = 0.3;
        $s3eLineTopPad = 4.0;
        $s3eLineRightInset = 25;
        $s3eLineBottomPad = 3.5;
        $s3eBulletGap = 4;
        $s3eBlockGap = 14;
        $s3eBlockBottomPad = 8;
        $s3eRowH = 8;
        $s3eLeftX = $s3ePad;
        $s3eRightGapExtra = 22;
        $s3eRightX = $s3eImgX + $s3eImgW + $s3eGap + $s3eRightGapExtra;
        $s3eRightColW = max(1, $s3eColW - $s3eRightGapExtra);
        $s3eY = $s3eContentY + 10;
        $s3eBlockTopPad = 12;
        $s3eBlue = [141, 188, 220];

        $s3eY += $s3eBlockTopPad;
        $mpdf->SetTextColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->SetFont('dejavusans', 'B', $s3eNumFont);
        $mpdf->SetXY($s3eLeftX, $s3eY);
        $mpdf->Cell($s3eNumW, $s3eRowH, '01', 0, 0, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', $s3eTitleFont);
        $mpdf->SetXY($s3eLeftX + $s3eTitleXOff, $s3eY);
        $mpdf->Cell($s3eColW - $s3eTitleXOff, $s3eRowH, 'AGROINDUSTRY', 0, 1, 'L');
        $mpdf->SetDrawColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->SetLineWidth($s3eLineUnder);
        $mpdf->Line($s3eLeftX, $s3eY + $s3eRowH + $s3eLineTopPad, $s3eLeftX + $s3eColW - $s3eLineRightInset, $s3eY + $s3eRowH + $s3eLineTopPad);
        $s3eY = $s3eY + $s3eRowH + $s3eLineTopPad + $s3eLineBottomPad;
        $mpdf->SetFont('dejavusans', '', $s3eBulletFont);
        $mpdf->SetTextColor(255, 255, 255);
        $bulletsLeft1 = ['Cotton', 'Corn and soy', 'Alfalfa and forage', 'Horticultural production', 'Agroindustrial development'];
        foreach ($bulletsLeft1 as $b) {
            $mpdf->SetXY($s3eLeftX, $s3eY);
            $mpdf->Cell(4, 5.5, "\xE2\x80\xA2", 0, 0, 'L');
            $mpdf->SetXY($s3eLeftX + 5, $s3eY);
            $mpdf->MultiCell($s3eColW - 5, 5.5, $b, 0, 'L');
            $s3eY = $mpdf->y + $s3eBulletGap;
        }
        $s3eY += $s3eBlockBottomPad;
        $s3eY += $s3eBlockGap;
        $s3eY += $s3eBlockTopPad;
        $mpdf->SetTextColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->SetFont('dejavusans', 'B', $s3eNumFont);
        $mpdf->SetXY($s3eLeftX, $s3eY);
        $mpdf->Cell($s3eNumW, $s3eRowH, '02', 0, 0, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', $s3eTitleFont);
        $mpdf->SetXY($s3eLeftX + $s3eTitleXOff, $s3eY);
        $mpdf->Cell($s3eColW - $s3eTitleXOff, $s3eRowH, 'LIVESTOCK', 0, 1, 'L');
        $mpdf->SetDrawColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->Line($s3eLeftX, $s3eY + $s3eRowH + $s3eLineTopPad, $s3eLeftX + $s3eColW - $s3eLineRightInset, $s3eY + $s3eRowH + $s3eLineTopPad);
        $s3eY += $s3eRowH + $s3eLineTopPad + $s3eLineBottomPad;
        $mpdf->SetFont('dejavusans', '', $s3eBulletFont);
        $bulletsLeft2 = ['Cattle farming', 'Goat production', 'Pork and poultry production', 'Meatpacking industry', 'Genetic development and animal health'];
        foreach ($bulletsLeft2 as $b) {
            $mpdf->SetXY($s3eLeftX, $s3eY);
            $mpdf->Cell(4, 5.5, "\xE2\x80\xA2", 0, 0, 'L');
            $mpdf->SetXY($s3eLeftX + 5, $s3eY);
            $mpdf->MultiCell($s3eColW - 5, 5.5, $b, 0, 'L');
            $s3eY = $mpdf->y + $s3eBulletGap;
        }

        $s3eY = $s3eContentY + 10;
        $s3eY += $s3eBlockTopPad;
        $mpdf->SetTextColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->SetFont('dejavusans', 'B', $s3eNumFont);
        $mpdf->SetXY($s3eRightX, $s3eY);
        $mpdf->Cell($s3eNumW, $s3eRowH, '03', 0, 0, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', $s3eTitleFont);
        $mpdf->SetXY($s3eRightX + $s3eTitleXOff, $s3eY);
        $mpdf->Cell($s3eRightColW - $s3eTitleXOff, $s3eRowH, 'REGIONALS', 0, 1, 'L');
        $mpdf->SetDrawColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->Line($s3eRightX, $s3eY + $s3eRowH + $s3eLineTopPad, $s3eRightX + $s3eRightColW - $s3eLineRightInset, $s3eY + $s3eRowH + $s3eLineTopPad);
        $s3eY += $s3eRowH + $s3eLineTopPad + $s3eLineBottomPad;
        $mpdf->SetFont('dejavusans', '', $s3eBulletFont);
        $bulletsRight1 = ['Beekeeping (honey)', 'Capers', 'Forest production', 'Family farming', 'Regional added value'];
        foreach ($bulletsRight1 as $b) {
            $mpdf->SetXY($s3eRightX, $s3eY);
            $mpdf->Cell(4, 5.5, "\xE2\x80\xA2", 0, 0, 'L');
            $mpdf->SetXY($s3eRightX + 5, $s3eY);
            $mpdf->MultiCell($s3eRightColW - 5, 5.5, $b, 0, 'L');
            $s3eY = $mpdf->y + $s3eBulletGap;
        }
        $s3eY += $s3eBlockBottomPad;
        $s3eY += $s3eBlockGap;
        $s3eY += $s3eBlockTopPad;
        $mpdf->SetTextColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->SetFont('dejavusans', 'B', $s3eNumFont);
        $mpdf->SetXY($s3eRightX, $s3eY);
        $mpdf->Cell($s3eNumW, $s3eRowH, '04', 0, 0, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', $s3eTitleFont);
        $mpdf->SetXY($s3eRightX + $s3eTitleXOff, $s3eY);
        $mpdf->Cell($s3eRightColW - $s3eTitleXOff, $s3eRowH, 'DEVELOPMENT', 0, 1, 'L');
        $mpdf->SetDrawColor($s3eBlue[0], $s3eBlue[1], $s3eBlue[2]);
        $mpdf->Line($s3eRightX, $s3eY + $s3eRowH + $s3eLineTopPad, $s3eRightX + $s3eRightColW - $s3eLineRightInset, $s3eY + $s3eRowH + $s3eLineTopPad);
        $s3eY += $s3eRowH + $s3eLineTopPad + $s3eLineBottomPad;
        $mpdf->SetFont('dejavusans', '', $s3eBulletFont);
        $bulletsRight2 = ['Renewable energies', 'Productive infrastructure', 'Irrigation systems', 'Sustainable territorial development', 'Industrial expansion'];
        foreach ($bulletsRight2 as $b) {
            $mpdf->SetXY($s3eRightX, $s3eY);
            $mpdf->Cell(4, 5.5, "\xE2\x80\xA2", 0, 0, 'L');
            $mpdf->SetXY($s3eRightX + 5, $s3eY);
            $mpdf->MultiCell($s3eRightColW - 5, 5.5, $b, 0, 'L');
            $s3eY = $mpdf->y + $s3eBulletGap;
        }
        $s3eY += $s3eBlockBottomPad;

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->SetDrawColor(0, 0, 0);
        // Slide 3quater: mismo layout que slide 5 (3ter), otro texto, imagen derecha Empresa0.jpg — Página 07
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3dPad = 20;
        $s3dMiddleH = round($hMm * 0.44);
        $s3dRemaining = $hMm - $s3dMiddleH;
        $s3dHeaderH = (int) round($s3dRemaining * 0.42);
        $s3dContentY = $s3dHeaderH;
        $s3dContentH = $hMm - $s3dHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3dHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3dLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3dLogoW = 44;
        $s3dLogoH = 22;
        if (file_exists($s3dLogoPath)) {
            $imgSize = @getimagesize($s3dLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3dLogoH * $r <= $s3dLogoW) {
                    $lw = $s3dLogoH * $r;
                    $lh = $s3dLogoH;
                } else {
                    $lw = $s3dLogoW;
                    $lh = $s3dLogoW / $r;
                }
                $mpdf->Image($s3dLogoPath, $s3dPad, ($s3dHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3dPad - 36, ($s3dHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 07', 0, 0, 'R');
        $s3dLineH = 0.5;
        $s3dLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3dPad, $s3dHeaderH - $s3dLineGap - $s3dLineH, $wMm - 2 * $s3dPad, $s3dLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3dContentY, $wMm, $s3dContentH, 'F');

        $s3dImgW = round($wMm * 0.45);
        $s3dImgH = round($hMm * 0.65);
        $s3dImgX = $wMm - $s3dImgW;
        $s3dImgY = $hMm - $s3dImgH;
        $s3dImgPath = $assetsDir . '/Empresa0.jpg';
        if ($s3dImgPath && file_exists($s3dImgPath)) {
            $s3dScale = 100 / 25.4;
            $dstWpx = (int) max(1, round($s3dImgW * $s3dScale));
            $dstHpx = (int) max(1, round($s3dImgH * $s3dScale));
            if (extension_loaded('gd')) {
                $info = @getimagesize($s3dImgPath);
                $ext = strtolower(pathinfo($s3dImgPath, PATHINFO_EXTENSION));
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s3dImgPath);
                elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s3dImgPath);
                elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s3dImgPath);
                if ($src && !empty($info[0]) && !empty($info[1])) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $scale = max($dstWpx / $sw, $dstHpx / $sh);
                    $cropW = (int) min($sw, round($dstWpx / $scale));
                    $cropH = (int) min($sh, round($dstHpx / $scale));
                    $srcX = (int) max(0, round(($sw - $cropW) / 2));
                    $srcY = 0;
                    $dst = @imagecreatetruecolor($dstWpx, $dstHpx);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstWpx, $dstHpx, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/corp_s3d_img_' . uniqid() . '.png';
                        if (@imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $s3dImgX, $s3dImgY, $s3dImgW, $s3dImgH);
                            @unlink($tmp);
                        } else {
                            $mpdf->Image($s3dImgPath, $s3dImgX, $s3dImgY, $s3dImgW, $s3dImgH);
                        }
                        imagedestroy($dst);
                    } else {
                        $mpdf->Image($s3dImgPath, $s3dImgX, $s3dImgY, $s3dImgW, $s3dImgH);
                    }
                    imagedestroy($src);
                } else {
                    if ($src) imagedestroy($src);
                    $mpdf->Image($s3dImgPath, $s3dImgX, $s3dImgY, $s3dImgW, $s3dImgH);
                }
            } else {
                $mpdf->Image($s3dImgPath, $s3dImgX, $s3dImgY, $s3dImgW, $s3dImgH);
            }
        }

        $s3dPanelX = 0;
        $s3dPanelY = $s3dContentY + 2;
        $s3dPanelW = (int) round($wMm * 0.74);
        $s3dPanelH = $s3dContentH - 35;
        $mpdf->SetFillColor(11, 24, 120);
        $mpdf->Rect($s3dPanelX, $s3dPanelY, $s3dPanelW, $s3dPanelH, 'F');

        $s3dTextPadX = 34;
        $s3dTextX = $s3dTextPadX;
        $s3dTextY = $s3dPanelY + 46;
        $s3dTitleW = $s3dPanelW - $s3dTextPadX - 10;
        $s3dBodyW = $s3dPanelW - $s3dTextPadX - 135;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3dTextX, $s3dTextY);
        $mpdf->MultiCell($s3dTitleW, 13, "INNOVATION AND\nPRODUCTIVE FUTURE", 0, 'L');

        $mpdf->SetFont('dejavusans', '', 14);
        $mpdf->SetXY($s3dTextX, $mpdf->y + 8);
        $mpdf->MultiCell($s3dBodyW, 6, 'The province promotes new opportunities based on innovation, knowledge economy, education and digital transformation, strengthening productive competitiveness and international integration.', 0, 'L');

        $s3dIconPath = $assetsDir . '/icon_rect.png';
        if ($s3dIconPath && file_exists($s3dIconPath)) {
            $s3dIconToRender = $s3dIconPath;
            if (extension_loaded('gd')) {
                $icon = @imagecreatefrompng($s3dIconPath);
                if ($icon && function_exists('imagerotate')) {
                    imagesavealpha($icon, true);
                    imagealphablending($icon, true);
                    $transparent = (int) imagecolorallocatealpha($icon, 0, 0, 0, 127);
                    // -90 = 90° clockwise (to the right) from initial position
                    $rot = @imagerotate($icon, -90, $transparent);
                    if ($rot) {
                        imagesavealpha($rot, true);
                        imagealphablending($rot, false);
                        $tmp = sys_get_temp_dir() . '/corp_s3d_icon_' . uniqid() . '.png';
                        if (@imagepng($rot, $tmp)) {
                            $s3dIconToRender = $tmp;
                        }
                        imagedestroy($rot);
                    }
                    imagedestroy($icon);
                } elseif ($icon) {
                    imagedestroy($icon);
                }
            }
            $iconSize = 62;
            $iconX = $s3dPanelW - $iconSize - 38;
            $iconY = $s3dPanelY + (int) round($s3dPanelH * 0.58);
            $mpdf->Image($s3dIconToRender, $iconX, $iconY, $iconSize, $iconSize);
            if ($s3dIconToRender !== $s3dIconPath && file_exists($s3dIconToRender)) {
                @unlink($s3dIconToRender);
            }
        }

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3quinquies: Educación y tecnología — mismo layout que slide 3quater (sectors) pero sin bullets — Página 08
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3fPad = 20;
        $s3fMiddleH = round($hMm * 0.44);
        $s3fRemaining = $hMm - $s3fMiddleH;
        $s3fHeaderH = (int) round($s3fRemaining * 0.42);
        $s3fContentY = $s3fHeaderH;
        $s3fContentH = $hMm - $s3fHeaderH;

        // Header
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3fHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3fLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3fLogoW = 44;
        $s3fLogoH = 22;
        if (file_exists($s3fLogoPath)) {
            $imgSize = @getimagesize($s3fLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3fLogoH * $r <= $s3fLogoW) {
                    $lw = $s3fLogoH * $r;
                    $lh = $s3fLogoH;
                } else {
                    $lw = $s3fLogoW;
                    $lh = $s3fLogoW / $r;
                }
                $mpdf->Image($s3fLogoPath, $s3fPad, ($s3fHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3fPad - 36, ($s3fHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 08', 0, 0, 'R');
        $s3fLineH = 0.5;
        $s3fLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3fPad, $s3fHeaderH - $s3fLineGap - $s3fLineH, $wMm - 2 * $s3fPad, $s3fLineH, 'F');

        // Content bg
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3fContentY, $wMm, $s3fContentH, 'F');

        // Layout: columns + centered image (match image size of Página 06)
        $s3fGap = 12;
        $s3fImgW = round($wMm * 0.35);
        $s3fColW = (int) round(($wMm - 2 * $s3fPad - $s3fImgW - 2 * $s3fGap) / 2);
        $s3fImgH = $s3fContentH - 10;
        $s3fImgY = $s3fContentY + 5;
        $s3fImgX = $s3fPad + $s3fColW + $s3fGap;
        $s3fRightGapExtra = 15;
        $s3fRightX = $s3fImgX + $s3fImgW + $s3fGap + $s3fRightGapExtra;
        $s3fRightColW = max(1, $s3fColW - $s3fRightGapExtra);

        // Center image
        $s3fImgPath = $assetsDir . '/EDUCACION_TECNOLOGIA.jpg';
        if ($s3fImgPath && file_exists($s3fImgPath)) {
            $s3fScale = 100 / 25.4;
            $dstWpx = (int) max(1, round($s3fImgW * $s3fScale));
            $dstHpx = (int) max(1, round($s3fImgH * $s3fScale));
            if (extension_loaded('gd')) {
                $info = @getimagesize($s3fImgPath);
                $ext = strtolower(pathinfo($s3fImgPath, PATHINFO_EXTENSION));
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s3fImgPath);
                elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s3fImgPath);
                elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s3fImgPath);
                if ($src && !empty($info[0]) && !empty($info[1])) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $scale = max($dstWpx / $sw, $dstHpx / $sh);
                    $cropW = (int) min($sw, round($dstWpx / $scale));
                    $cropH = (int) min($sh, round($dstHpx / $scale));
                    $srcX = (int) max(0, round(($sw - $cropW) / 2));
                    $srcY = (int) max(0, round(($sh - $cropH) / 2));
                    $dst = @imagecreatetruecolor($dstWpx, $dstHpx);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstWpx, $dstHpx, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/corp_s3f_img_' . uniqid() . '.png';
                        if (@imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH);
                            @unlink($tmp);
                        } else {
                            $mpdf->Image($s3fImgPath, $s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH);
                        }
                        imagedestroy($dst);
                    } else {
                        $mpdf->Image($s3fImgPath, $s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH);
                    }
                    imagedestroy($src);
                } else {
                    if ($src) imagedestroy($src);
                    $mpdf->Image($s3fImgPath, $s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH);
                }
            } else {
                $mpdf->Image($s3fImgPath, $s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH);
            }
        }

        // Text styles
        $s3fBlue = [141, 188, 220];
        $s3fNumFont = 22;
        $s3fTitleFont = 11;
        $s3fNumW = 14;
        $s3fRowH = 8;
        $s3fLineUnder = 0.25;
        $s3fLineTopPad = 4.0;
        $s3fLineRightInset = 18;
        $s3fTitleXOff = $s3fNumW + 6;
        $s3fItemGap = 24;
        $s3fBlocksDown = 10;
        $s3fStartY = $s3fContentY + 46 + $s3fBlocksDown;

        $drawEduItem = function ($x, $y, $num, $title, $colW = null) use ($mpdf, $s3fBlue, $s3fNumFont, $s3fTitleFont, $s3fNumW, $s3fRowH, $s3fTitleXOff, $s3fColW, $s3fLineUnder, $s3fLineTopPad, $s3fLineRightInset) {
            $w = $colW !== null ? $colW : $s3fColW;
            $mpdf->SetTextColor($s3fBlue[0], $s3fBlue[1], $s3fBlue[2]);
            $mpdf->SetFont('dejavusans', 'B', $s3fNumFont);
            $mpdf->SetXY($x, $y);
            $mpdf->Cell($s3fNumW, $s3fRowH, $num, 0, 0, 'L');

            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', '', $s3fTitleFont);
            $mpdf->SetXY($x + $s3fTitleXOff, $y + 1);
            $mpdf->MultiCell($w - $s3fTitleXOff, 4.5, $title, 0, 'L');

            $mpdf->SetDrawColor($s3fBlue[0], $s3fBlue[1], $s3fBlue[2]);
            $mpdf->SetLineWidth($s3fLineUnder);
            $mpdf->Line($x, $y + $s3fRowH + $s3fLineTopPad, $x + $w - $s3fLineRightInset, $y + $s3fRowH + $s3fLineTopPad);
        };

        // Left column (01-03)
        $lx = $s3fPad;
        $y = $s3fStartY;
        $drawEduItem($lx, $y, '01', "EDUCATION");
        $y += $s3fItemGap;
        $drawEduItem($lx, $y, '02', "TECHNOLOGY");
        $y += $s3fItemGap;
        $drawEduItem($lx, $y, '03', "KNOWLEDGE\nECONOMY");

        // Right column (04-06)
        $rx = $s3fRightX;
        $y = $s3fStartY;
        $drawEduItem($rx, $y, '04', "VOCATIONAL\nTRAINING", $s3fRightColW);
        $y += $s3fItemGap;
        $drawEduItem($rx, $y, '05', "DIGITAL\nTRANSFORMATION", $s3fRightColW);
        $y += $s3fItemGap;
        $drawEduItem($rx, $y, '06', "DEVELOPMENT", $s3fRightColW);

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3sexies: Turismo como motor económico — header negro; imagen izquierda MOTOR_ECONOMICO.jpg; texto derecha título + párrafo + subtítulo — Página 09
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3gPad = 20;
        $s3gMiddleH = round($hMm * 0.44);
        $s3gRemaining = $hMm - $s3gMiddleH;
        $s3gHeaderH = (int) round($s3gRemaining * 0.42);
        $s3gContentY = $s3gHeaderH;
        $s3gContentH = $hMm - $s3gHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3gHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3gLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3gLogoW = 44;
        $s3gLogoH = 22;
        if (file_exists($s3gLogoPath)) {
            $imgSize = @getimagesize($s3gLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3gLogoH * $r <= $s3gLogoW) {
                    $lw = $s3gLogoH * $r;
                    $lh = $s3gLogoH;
                } else {
                    $lw = $s3gLogoW;
                    $lh = $s3gLogoW / $r;
                }
                $mpdf->Image($s3gLogoPath, $s3gPad, ($s3gHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3gPad - 36, ($s3gHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 09', 0, 0, 'R');
        $s3gLineH = 0.5;
        $s3gLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3gPad, $s3gHeaderH - $s3gLineGap - $s3gLineH, $wMm - 2 * $s3gPad, $s3gLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3gContentY, $wMm, $s3gContentH, 'F');

        $s3gGap = 20;
        $s3gImgW = round($wMm * 0.34);
        $s3gImgH = round($s3gContentH * 0.64);
        $s3gImgX = 0;
        $s3gImgY = $s3gContentY;
        $s3gImgPath = $assetsDir . '/MOTOR_ECONOMICO.jpg';
        if ($s3gImgPath && file_exists($s3gImgPath) && extension_loaded('gd')) {
            $s3gScale = 100 / 25.4;
            $dstWpx = (int) max(1, round($s3gImgW * $s3gScale));
            $dstHpx = (int) max(1, round($s3gImgH * $s3gScale));
            $info = @getimagesize($s3gImgPath);
            $ext = strtolower(pathinfo($s3gImgPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s3gImgPath);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s3gImgPath);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s3gImgPath);
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $scale = max($dstWpx / $sw, $dstHpx / $sh);
                $cropW = (int) min($sw, round($dstWpx / $scale));
                $cropH = (int) min($sh, round($dstHpx / $scale));
                $srcX = (int) max(0, round(($sw - $cropW) / 2));
                $srcY = 0;
                $dst = @imagecreatetruecolor($dstWpx, $dstHpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstWpx, $dstHpx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/corp_s3g_img_' . uniqid() . '.png';
                    if (@imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, $s3gImgX, $s3gImgY, $s3gImgW, $s3gImgH);
                        @unlink($tmp);
                    } else {
                        $mpdf->Image($s3gImgPath, $s3gImgX, $s3gImgY, $s3gImgW, $s3gImgH);
                    }
                    imagedestroy($dst);
                } else {
                    $mpdf->Image($s3gImgPath, $s3gImgX, $s3gImgY, $s3gImgW, $s3gImgH);
                }
                imagedestroy($src);
            } else {
                if ($src) imagedestroy($src);
                $mpdf->Image($s3gImgPath, $s3gImgX, $s3gImgY, $s3gImgW, $s3gImgH);
            }
        } elseif ($s3gImgPath && file_exists($s3gImgPath)) {
            $mpdf->Image($s3gImgPath, $s3gImgX, $s3gImgY, $s3gImgW, $s3gImgH);
        } else {
            $mpdf->SetFillColor(60, 60, 60);
            $mpdf->Rect($s3gImgX, $s3gImgY, $s3gImgW, $s3gImgH, 'F');
        }

        $s3gTextX = $s3gImgX + $s3gImgW + $s3gGap;
        $s3gTextW = max(1, $wMm - $s3gTextX - $s3gPad);
        $s3gParaW = max(1, $s3gTextW - 75);
        $s3gTextY = $s3gContentY + 28;
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3gTextX, $s3gTextY);
        $mpdf->Cell($s3gTextW, 13, 'TOURISM AS AN ECONOMIC', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3gTextX, $mpdf->y + 2);
        $mpdf->Cell($s3gTextW, 13, 'ENGINE', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', '', 14);
        $mpdf->SetXY($s3gTextX, $mpdf->y + 10);
        $mpdf->MultiCell($s3gParaW, 6.5, 'Tourism is consolidated as one of the strategic engines for provincial economic diversification, integrating nature, culture, sport and well-being.', 0, 'L');
        $mpdf->SetFont('dejavusans', '', 18);
        $mpdf->SetXY($s3gTextX, $mpdf->y + 45);
        $mpdf->MultiCell($s3gTextW, 8, "TOURISM AND TERRITORIAL\nDEVELOPMENT", 0, 'L');

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3sexies bis: 4 paneles — 2 tiras estrechas + 2 paneles anchos con caption azul (TERMAS DE RÍO HONDO, ESTADIO ÚNICO MADRE DE CIUDADES) — Página 10
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3jPad = 20;
        $s3jMiddleH = round($hMm * 0.44);
        $s3jRemaining = $hMm - $s3jMiddleH;
        $s3jHeaderH = (int) round($s3jRemaining * 0.42);
        $s3jContentY = $s3jHeaderH;
        $s3jContentH = $hMm - $s3jHeaderH;
        $s3jGap = 10;
        $s3jGapAfterFirstWide = 16;
        $s3jWideDown = 8; // опускание только широких панелей (с caption)
        $s3jStripW = 42;
        $s3jWideReduction = 12;
        $s3jWideW = (int) max(1, floor(($wMm - 2 * $s3jPad - 2 * $s3jStripW - 3 * $s3jGap) / 2) - $s3jWideReduction);
        $s3jCaptionH = 20;
        $s3jStripImgH = round($s3jContentH * 0.77);
        $s3jWideImgH = round($s3jContentH * 0.7);
        $s3jScale = 100 / 25.4;
        $s3jBlue = [11, 24, 120];

        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3jHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3jLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3jLogoW = 44;
        $s3jLogoH = 22;
        if (file_exists($s3jLogoPath)) {
            $imgSize = @getimagesize($s3jLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3jLogoH * $r <= $s3jLogoW) {
                    $lw = $s3jLogoH * $r;
                    $lh = $s3jLogoH;
                } else {
                    $lw = $s3jLogoW;
                    $lh = $s3jLogoW / $r;
                }
                $mpdf->Image($s3jLogoPath, $s3jPad, ($s3jHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3jPad - 36, ($s3jHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 10', 0, 0, 'R');
        $s3jLineH = 0.5;
        $s3jLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3jPad, $s3jHeaderH - $s3jLineGap - $s3jLineH, $wMm - 2 * $s3jPad, $s3jLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3jContentY, $wMm, $s3jContentH, 'F');

        $s3jLoadCrop = function ($path, $dstW, $dstH, $srcX = null, $srcY = null, $zoom = 1.0) use ($s3jScale) {
            if (!$path || !file_exists($path) || !extension_loaded('gd')) return null;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            if (!$src || empty($info[0]) || empty($info[1])) return null;
            $sw = imagesx($src);
            $sh = imagesy($src);
            $dwPx = (int) max(1, round($dstW * $s3jScale));
            $dhPx = (int) max(1, round($dstH * $s3jScale));
            $scale = max($dwPx / $sw, $dhPx / $sh);
            $cropW = (int) min($sw, round($dwPx / $scale));
            $cropH = (int) min($sh, round($dhPx / $scale));
            if ($srcX === 'left') {
                $srcX = 0;
            } elseif ($srcX === 'left_soft') {
                // Смещаем окно кропа влево, но не до самого края.
                $rangeX = max(0, $sw - $cropW);
                $srcX = (int) round($rangeX * 0.40);
            } elseif ($srcX === 'right') {
                $srcX = (int) max(0, $sw - $cropW);
            } elseif ($srcX === 'right_soft') {
                // Смещаем окно кропа вправо, но оставляем небольшой запас.
                $rangeX = max(0, $sw - $cropW);
                $srcX = (int) round($rangeX * 0.95);
            } elseif ($srcX !== null) {
                $srcX = (int) $srcX;
            } else {
                $srcX = (int) max(0, round(($sw - $cropW) / 2));
            }
            if ($srcY === 'top') {
                $srcY = 0;
            } elseif ($srcY === 'bottom') {
                $srcY = (int) max(0, $sh - $cropH);
            } elseif ($srcY !== null) {
                $srcY = (int) $srcY;
            } else {
                $srcY = (int) max(0, round(($sh - $cropH) / 2));
            }
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/corp_s3j_' . uniqid() . '.png';
            if (!@imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };

        $s3jX = $s3jPad;
        $termas1Path = $assetsDir . '/TERMAS_DE_RIO_HONDO1.jpg';
        $termas2Path = $assetsDir . '/TERMAS_DE_RIO_HONDO2.jpg';
        $estadioPath = $assetsDir . '/ESTADIO_UNICO_MADRE_DE_CIUDADES.jpg';

        foreach ([
            // Левая узкая: показываем правую часть картинки для “стыковки” с соседней панелью
            ['w' => $s3jStripW, 'img' => $termas1Path, 'srcX' => null, 'srcY' => null, 'caption' => null],
            ['w' => $s3jWideW, 'img' => $termas2Path, 'srcX' => null, 'srcY' => null, 'caption' => 'RIO HONDO THERMAL BATHS'],
            // Правый узкий: показываем левую часть картинки
            ['w' => $s3jStripW, 'img' => $estadioPath, 'srcX' => 'left', 'srcY' => null, 'caption' => null],
            // Правый широкий: продолжаем вправо — показываем правую часть картинки
            ['w' => $s3jWideW, 'img' => $estadioPath, 'srcX' => 'right', 'srcY' => null, 'caption' => 'UNIQUE STADIUM MOTHER OF CITIES'],
        ] as $idx => $panel) {
            $pw = $panel['w'];
            $px = $s3jX;
            $s3jX += $pw + $s3jGap;
            if ($idx === 1) {
                $s3jX += $s3jGapAfterFirstWide;
            }
            $ph = $panel['caption'] !== null ? $s3jWideImgH : $s3jStripImgH;
            $py = $panel['caption'] !== null ? $s3jContentY + $s3jWideDown : $s3jContentY;
            $tmp = $s3jLoadCrop(
                $panel['img'],
                $pw,
                $ph,
                $panel['srcX'],
                $panel['srcY'],
                isset($panel['zoom']) ? (float) $panel['zoom'] : 1.0
            );
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $px, $py, $pw, $ph);
                @unlink($tmp);
            } else {
                $mpdf->SetFillColor(50, 50, 50);
                $mpdf->Rect($px, $py, $pw, $ph, 'F');
            }
            if ($panel['caption'] !== null) {
                $capY = $py + $ph;
                $mpdf->SetFillColor($s3jBlue[0], $s3jBlue[1], $s3jBlue[2]);
                $mpdf->Rect($px, $capY, $pw, $s3jCaptionH - 2, 'F');
                $mpdf->SetTextColor(255, 255, 255);
                // Текст и разделитель внутри синего блока (как на скриншоте)
                $captionPadX = 6;
                $captionTextY = $capY + 3;
                $captionLineY = $capY + 13;
                $mpdf->SetFont('dejavusans', 'B', 11);
                $mpdf->SetXY($px + $captionPadX, $captionTextY);
                $mpdf->Cell($pw - 2 * $captionPadX, 6, $panel['caption'], 0, 0, 'L');
                $mpdf->SetDrawColor(255, 255, 255);
                $mpdf->SetLineWidth(0.35);
                $mpdf->Line($px + $captionPadX, $captionLineY, $px + $pw - $captionPadX, $captionLineY);
            }
        }

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3sexies ter: 4 paneles — 2 tiras estrechas + 2 paneles anchos con caption azul (TERMAS DE RÍO HONDO, ESTADIO ÚNICO MADRE DE CIUDADES) — Página 11
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3jPad = 20;
        $s3jMiddleH = round($hMm * 0.44);
        $s3jRemaining = $hMm - $s3jMiddleH;
        $s3jHeaderH = (int) round($s3jRemaining * 0.42);
        $s3jContentY = $s3jHeaderH;
        $s3jContentH = $hMm - $s3jHeaderH;
        $s3jGap = 10;
        $s3jGapAfterFirstWide = 16;
        $s3jWideDown = 8; // опускание только широких панелей (с caption)
        $s3jStripW = 42;
        $s3jWideReduction = 12;
        $s3jWideW = (int) max(1, floor(($wMm - 2 * $s3jPad - 2 * $s3jStripW - 3 * $s3jGap) / 2) - $s3jWideReduction);
        $s3jCaptionH = 20;
        $s3jStripImgH = round($s3jContentH * 0.77);
        $s3jWideImgH = round($s3jContentH * 0.7);
        $s3jScale = 100 / 25.4;
        $s3jBlue = [11, 24, 120];

        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3jHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3jLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3jLogoW = 44;
        $s3jLogoH = 22;
        if (file_exists($s3jLogoPath)) {
            $imgSize = @getimagesize($s3jLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3jLogoH * $r <= $s3jLogoW) {
                    $lw = $s3jLogoH * $r;
                    $lh = $s3jLogoH;
                } else {
                    $lw = $s3jLogoW;
                    $lh = $s3jLogoW / $r;
                }
                $mpdf->Image($s3jLogoPath, $s3jPad, ($s3jHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3jPad - 36, ($s3jHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 11', 0, 0, 'R');
        $s3jLineH = 0.5;
        $s3jLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3jPad, $s3jHeaderH - $s3jLineGap - $s3jLineH, $wMm - 2 * $s3jPad, $s3jLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3jContentY, $wMm, $s3jContentH, 'F');

        $s3jLoadCrop = function ($path, $dstW, $dstH, $srcX = null, $srcY = null, $zoom = 1.0) use ($s3jScale) {
            if (!$path || !file_exists($path) || !extension_loaded('gd')) return null;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            if (!$src || empty($info[0]) || empty($info[1])) return null;
            $sw = imagesx($src);
            $sh = imagesy($src);
            $dwPx = (int) max(1, round($dstW * $s3jScale));
            $dhPx = (int) max(1, round($dstH * $s3jScale));
            $scale = max($dwPx / $sw, $dhPx / $sh);
            $cropW = (int) min($sw, round($dwPx / $scale));
            $cropH = (int) min($sh, round($dhPx / $scale));
            $zoom = is_numeric($zoom) ? (float) $zoom : 1.0;
            if ($zoom < 1) $zoom = 1.0;
            // zoom>1 => берём меньший фрагмент оригинала и растягиваем его в dst.
            $cropW = max(1, (int) round($cropW / $zoom));
            $cropH = max(1, (int) round($cropH / $zoom));
            if ($srcX === 'left') {
                $srcX = 0;
            } elseif ($srcX === 'right') {
                $srcX = (int) max(0, $sw - $cropW);
            } elseif ($srcX !== null) {
                $srcX = (int) $srcX;
            } else {
                $srcX = (int) max(0, round(($sw - $cropW) / 2));
            }
            if ($srcY === 'top') {
                $srcY = 0;
            } elseif ($srcY === 'bottom') {
                $srcY = (int) max(0, $sh - $cropH);
            } elseif ($srcY !== null) {
                $srcY = (int) $srcY;
            } else {
                $srcY = (int) max(0, round(($sh - $cropH) / 2));
            }
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/corp_s3j_' . uniqid() . '.png';
            if (!@imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };

        $s3jX = $s3jPad;
        $termas1Path = $assetsDir . '/Naturaleza_Ecoturismo2.jpg';
        $termas2Path = $assetsDir . '/Naturaleza_Ecoturismo.jpg';
        $estadioPath = $assetsDir . '/ESTADIO_UNICO_MADRE_DE_CIUDADES.jpg';
        $ciudadPath = $assetsDir . '/CIUDAD_HISTORICA2.jpg';

        foreach ([
            // Левая часть (после swap): берем элементы справа
            ['w' => $s3jStripW, 'img' => $ciudadPath, 'srcX' => null, 'srcY' => null, 'caption' => null],
            ['w' => $s3jWideW, 'img' => $ciudadPath, 'srcX' => 'right', 'srcY' => null, 'caption' => 'HISTORIC CITY', 'zoom' => 1.6],
            // Правая часть (после swap): берем элементы слева
            ['w' => $s3jStripW, 'img' => $termas1Path, 'srcX' => null, 'srcY' => null, 'caption' => null],
            ['w' => $s3jWideW, 'img' => $termas2Path, 'srcX' => null, 'srcY' => null, 'caption' => 'NATURE AND ECOTOURISM'],
        ] as $idx => $panel) {
            $pw = $panel['w'];
            $px = $s3jX;
            $s3jX += $pw + $s3jGap;
            if ($idx === 1) {
                $s3jX += $s3jGapAfterFirstWide;
            }
            $ph = $panel['caption'] !== null ? $s3jWideImgH : $s3jStripImgH;
            $py = $panel['caption'] !== null ? $s3jContentY + $s3jWideDown : $s3jContentY;
            $tmp = $s3jLoadCrop(
                $panel['img'],
                $pw,
                $ph,
                $panel['srcX'],
                $panel['srcY'],
                isset($panel['zoom']) ? (float) $panel['zoom'] : 1.0
            );
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $px, $py, $pw, $ph);
                @unlink($tmp);
            } else {
                $mpdf->SetFillColor(50, 50, 50);
                $mpdf->Rect($px, $py, $pw, $ph, 'F');
            }
            if ($panel['caption'] !== null) {
                $capY = $py + $ph;
                $mpdf->SetFillColor($s3jBlue[0], $s3jBlue[1], $s3jBlue[2]);
                $mpdf->Rect($px, $capY, $pw, $s3jCaptionH - 2, 'F');
                $mpdf->SetTextColor(255, 255, 255);
                // Текст и разделитель внутри синего блока (как на скриншоте)
                $captionPadX = 6;
                $captionTextY = $capY + 3;
                $captionLineY = $capY + 13;
                $mpdf->SetFont('dejavusans', 'B', 11);
                $mpdf->SetXY($px + $captionPadX, $captionTextY);
                $mpdf->Cell($pw - 2 * $captionPadX, 6, $panel['caption'], 0, 0, 'L');
                $mpdf->SetDrawColor(255, 255, 255);
                $mpdf->SetLineWidth(0.35);
                $mpdf->Line($px + $captionPadX, $captionLineY, $px + $pw - $captionPadX, $captionLineY);
            }
        }

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3septies: Cultura e identidad — mismo layout que Página 09; imagen IDENTIDADCULTURALY.jpg; texto según pantalla — Página 11
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3hPad = 20;
        $s3hMiddleH = round($hMm * 0.44);
        $s3hRemaining = $hMm - $s3hMiddleH;
        $s3hHeaderH = (int) round($s3hRemaining * 0.42);
        $s3hContentY = $s3hHeaderH;
        $s3hContentH = $hMm - $s3hHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s3hHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3hLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3hLogoW = 44;
        $s3hLogoH = 22;
        if (file_exists($s3hLogoPath)) {
            $imgSize = @getimagesize($s3hLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3hLogoH * $r <= $s3hLogoW) {
                    $lw = $s3hLogoH * $r;
                    $lh = $s3hLogoH;
                } else {
                    $lw = $s3hLogoW;
                    $lh = $s3hLogoW / $r;
                }
                $mpdf->Image($s3hLogoPath, $s3hPad, ($s3hHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3hPad - 36, ($s3hHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 12', 0, 0, 'R');
        $s3hLineH = 0.5;
        $s3hLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3hPad, $s3hHeaderH - $s3hLineGap - $s3hLineH, $wMm - 2 * $s3hPad, $s3hLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3hContentY, $wMm, $s3hContentH, 'F');

        $s3hGap = 20;
        $s3hImgW = round($wMm * 0.34);
        $s3hImgH = round($s3hContentH * 0.64);
        $s3hImgX = 0;
        $s3hImgY = $s3hContentY;
        $s3hImgPath = $assetsDir . '/IDENTIDADCULTURALY.jpg';
        if ($s3hImgPath && file_exists($s3hImgPath) && extension_loaded('gd')) {
            $s3hScale = 100 / 25.4;
            $dstWpx = (int) max(1, round($s3hImgW * $s3hScale));
            $dstHpx = (int) max(1, round($s3hImgH * $s3hScale));
            $info = @getimagesize($s3hImgPath);
            $ext = strtolower(pathinfo($s3hImgPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s3hImgPath);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s3hImgPath);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s3hImgPath);
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $scale = max($dstWpx / $sw, $dstHpx / $sh);
                $cropW = (int) min($sw, round($dstWpx / $scale));
                $cropH = (int) min($sh, round($dstHpx / $scale));
                $srcX = (int) max(0, round(($sw - $cropW) / 2));
                $srcY = 0;
                $dst = @imagecreatetruecolor($dstWpx, $dstHpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstWpx, $dstHpx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/corp_s3h_img_' . uniqid() . '.png';
                    if (@imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, $s3hImgX, $s3hImgY, $s3hImgW, $s3hImgH);
                        @unlink($tmp);
                    } else {
                        $mpdf->Image($s3hImgPath, $s3hImgX, $s3hImgY, $s3hImgW, $s3hImgH);
                    }
                    imagedestroy($dst);
                } else {
                    $mpdf->Image($s3hImgPath, $s3hImgX, $s3hImgY, $s3hImgW, $s3hImgH);
                }
                imagedestroy($src);
            } else {
                if ($src) imagedestroy($src);
                $mpdf->Image($s3hImgPath, $s3hImgX, $s3hImgY, $s3hImgW, $s3hImgH);
            }
        } elseif ($s3hImgPath && file_exists($s3hImgPath)) {
            $mpdf->Image($s3hImgPath, $s3hImgX, $s3hImgY, $s3hImgW, $s3hImgH);
        } else {
            $mpdf->SetFillColor(60, 60, 60);
            $mpdf->Rect($s3hImgX, $s3hImgY, $s3hImgW, $s3hImgH, 'F');
        }

        $s3hTextX = $s3hImgX + $s3hImgW + $s3hGap;
        $s3hTextW = max(1, $wMm - $s3hTextX - $s3hPad);
        $s3hParaW = max(1, $s3hTextW - 75);
        $s3hTextY = $s3hContentY + 28;
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3hTextX, $s3hTextY);
        $mpdf->Cell($s3hTextW, 13, 'CULTURE AND', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3hTextX, $mpdf->y + 2);
        $mpdf->Cell($s3hTextW, 13, 'IDENTITY', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', '', 14);
        $mpdf->SetXY($s3hTextX, $mpdf->y + 10);
        $mpdf->MultiCell($s3hParaW, 6.5, 'Santiago del Estero, Mother of Cities, preserves a living cultural heritage that articulates tradition, music, gastronomy and artistic expressions as part of its territorial positioning.', 0, 'L');
        $mpdf->SetFont('dejavusans', '', 18);
        $mpdf->SetXY($s3hTextX, $mpdf->y + 45);
        $mpdf->MultiCell($s3hTextW, 8, "CULTURAL IDENTITY AND\nHERITAGE", 0, 'L');

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3octies: imagen grande arriba GASTRONOMIA_ARTESANIA.jpg; panel negro abajo con 4 columnas (GASTRONOMÍA, ARTESANÍA, PATRIMONIO HISTÓRICO, FOLKLORE) — Página 12
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3iPad = 20;
        $s3iMiddleH = round($hMm * 0.44);
        $s3iRemaining = $hMm - $s3iMiddleH;
        $s3iHeaderH = (int) round($s3iRemaining * 0.42);
        $s3iContentY = $s3iHeaderH;
        $s3iContentH = $hMm - $s3iHeaderH;
        $s3iImgContentH = round($hMm * 0.44);
        $s3iFooterY = $s3iContentY + $s3iImgContentH;
        $s3iFooterH = $hMm - $s3iFooterY;
        $s3iImgY = 0;
        $s3iImgH = $s3iFooterY;
        $s3iImgW = $wMm;
        $s3iOverlayAlpha = 0.5;

        $s3iImgPath = $assetsDir . '/GASTRONOMIA_ARTESANIA.jpg';
        if ($s3iImgPath && file_exists($s3iImgPath) && extension_loaded('gd')) {
            $s3iScale = 100 / 25.4;
            $dstWpx = (int) max(1, round($s3iImgW * $s3iScale));
            $dstHpx = (int) max(1, round($s3iImgH * $s3iScale));
            $info = @getimagesize($s3iImgPath);
            $ext = strtolower(pathinfo($s3iImgPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s3iImgPath);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s3iImgPath);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s3iImgPath);
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $scale = max($dstWpx / $sw, $dstHpx / $sh);
                $cropW = (int) min($sw, round($dstWpx / $scale));
                $cropH = (int) min($sh, round($dstHpx / $scale));
                $srcX = (int) max(0, round(($sw - $cropW) / 2));
                $srcY = (int) max(0, round(($sh - $cropH) / 2));
                $dst = @imagecreatetruecolor($dstWpx, $dstHpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstWpx, $dstHpx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/corp_s3i_img_' . uniqid() . '.png';
                    if (@imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, 0, $s3iImgY, $s3iImgW, $s3iImgH);
                        @unlink($tmp);
                    } else {
                        $mpdf->Image($s3iImgPath, 0, $s3iImgY, $s3iImgW, $s3iImgH);
                    }
                    imagedestroy($dst);
                } else {
                    $mpdf->Image($s3iImgPath, 0, $s3iImgY, $s3iImgW, $s3iImgH);
                }
                imagedestroy($src);
            } else {
                if ($src) imagedestroy($src);
                $mpdf->Image($s3iImgPath, 0, $s3iImgY, $s3iImgW, $s3iImgH);
            }
        } elseif ($s3iImgPath && file_exists($s3iImgPath)) {
            $mpdf->Image($s3iImgPath, 0, $s3iImgY, $s3iImgW, $s3iImgH);
        } else {
            $mpdf->SetFillColor(60, 60, 60);
            $mpdf->Rect(0, $s3iImgY, $s3iImgW, $s3iImgH, 'F');
        }

        if (extension_loaded('gd')) {
            $s3iOverlayW = 2;
            $s3iOverlayH = 2;
            $s3iOverlayImg = @imagecreatetruecolor($s3iOverlayW, $s3iOverlayH);
            if ($s3iOverlayImg) {
                imagesavealpha($s3iOverlayImg, true);
                imagealphablending($s3iOverlayImg, false);
                $s3iAlphaByte = (int) round((1 - $s3iOverlayAlpha) * 127);
                $s3iBlack = (int) imagecolorallocatealpha($s3iOverlayImg, 0, 0, 0, $s3iAlphaByte);
                imagefill($s3iOverlayImg, 0, 0, $s3iBlack);
                $s3iOverlayTmp = sys_get_temp_dir() . '/corp_s3i_overlay_' . uniqid() . '.png';
                if (@imagepng($s3iOverlayImg, $s3iOverlayTmp)) {
                    $mpdf->Image($s3iOverlayTmp, 0, $s3iImgY, $s3iImgW, $s3iImgH);
                    @unlink($s3iOverlayTmp);
                }
                imagedestroy($s3iOverlayImg);
            }
        }

        $mpdf->SetTextColor(255, 255, 255);
        $s3iLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s3iLogoW = 44;
        $s3iLogoH = 22;
        if (file_exists($s3iLogoPath)) {
            $imgSize = @getimagesize($s3iLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3iLogoH * $r <= $s3iLogoW) {
                    $lw = $s3iLogoH * $r;
                    $lh = $s3iLogoH;
                } else {
                    $lw = $s3iLogoW;
                    $lh = $s3iLogoW / $r;
                }
                $mpdf->Image($s3iLogoPath, $s3iPad, ($s3iHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s3iPad - 36, ($s3iHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 13', 0, 0, 'R');
        $s3iLineH = 0.5;
        $s3iLineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3iPad, $s3iHeaderH - $s3iLineGap - $s3iLineH, $wMm - 2 * $s3iPad, $s3iLineH, 'F');

        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s3iFooterY, $wMm, $s3iFooterH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s3iColCount = 4;
        $s3iColGap = 14;
        $s3iColW = max(1, (int) floor(($wMm - 2 * $s3iPad - ($s3iColCount - 1) * $s3iColGap) / $s3iColCount));
        $s3iFootPadY = 12;
        $s3iTitleFont = 17;
        $s3iDescFont = 14;
        $s3iDescLineH = 7;
        $s3iTitleRowH = 8;
        $s3iTitleGap = 4;
        for ($col = 0; $col < $s3iColCount; $col++) {
            $s3iColX = $s3iPad + $col * ($s3iColW + $s3iColGap);
            $s3iCurY = $s3iFooterY + $s3iFootPadY;
            if ($col === 0) {
                $mpdf->SetFont('dejavusans', 'B', $s3iTitleFont);
                $mpdf->SetXY($s3iColX, $s3iCurY);
                $mpdf->Cell($s3iColW, $s3iTitleRowH, 'GASTRONOMY', 0, 1, 'L');
                $mpdf->SetFont('dejavusans', '', $s3iDescFont);
                $mpdf->SetXY($s3iColX, $mpdf->y + $s3iTitleGap);
                $mpdf->MultiCell($s3iColW, $s3iDescLineH, "Traditional flavors and\nlocal identity.\nPopular Festivals.\nCelebrations and cultural\nencounters.", 0, 'L');
            } elseif ($col === 1) {
                $mpdf->SetFont('dejavusans', 'B', $s3iTitleFont);
                $mpdf->SetXY($s3iColX, $s3iCurY);
                $mpdf->Cell($s3iColW, $s3iTitleRowH, 'CRAFTS', 0, 1, 'L');
                $mpdf->SetFont('dejavusans', '', $s3iDescFont);
                $mpdf->SetXY($s3iColX, $mpdf->y + $s3iTitleGap);
                $mpdf->MultiCell($s3iColW, $s3iDescLineH, "Ancestral knowledge and\nregional production.", 0, 'L');
            } elseif ($col === 2) {
                $mpdf->SetFont('dejavusans', 'B', $s3iTitleFont);
                $mpdf->SetXY($s3iColX, $s3iCurY);
                $mpdf->Cell($s3iColW, $s3iTitleRowH, 'HISTORICAL HERITAGE', 0, 1, 'L');
                $mpdf->SetFont('dejavusans', '', $s3iDescFont);
                $mpdf->SetXY($s3iColX, $mpdf->y + $s3iTitleGap);
                $mpdf->MultiCell($s3iColW, $s3iDescLineH, "Spaces and provincial cultural\nmemory.", 0, 'L');
            } else {
                $mpdf->SetFont('dejavusans', 'B', $s3iTitleFont);
                $mpdf->SetXY($s3iColX, $s3iCurY);
                $mpdf->Cell($s3iColW, $s3iTitleRowH, 'FOLKLORE', 0, 1, 'L');
                $mpdf->SetFont('dejavusans', '', $s3iDescFont);
                $mpdf->SetXY($s3iColX, $mpdf->y + $s3iTitleGap);
                $mpdf->MultiCell($s3iColW, $s3iDescLineH, "Music, dance and popular\ntradition.", 0, 'L');
            }
        }

        // Slide 4: Empresas exportadoras — шапка como slide 1; dos imágenes Empresa lado a lado arriba; abajo título EMPRESAS/EXPORTADORAS (izq) y párrafo (der)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s4Pad = 20;
        $s4MiddleH = round($hMm * 0.44);
        $s4Remaining = $hMm - $s4MiddleH;
        $s4HeaderH = (int) round($s4Remaining * 0.42);
        $s4ContentY = $s4HeaderH;
        $s4ContentH = $hMm - $s4HeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $s4HeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $s4LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s4LogoW = 44;
        $s4LogoH = 22;
        if (file_exists($s4LogoPath)) {
            $imgSize = @getimagesize($s4LogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s4LogoH * $r <= $s4LogoW) {
                    $lw = $s4LogoH * $r;
                    $lh = $s4LogoH;
                } else {
                    $lw = $s4LogoW;
                    $lh = $s4LogoW / $r;
                }
                $mpdf->Image($s4LogoPath, $s4Pad, ($s4HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s4Pad - 36, ($s4HeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page 04', 0, 0, 'R');
        $s4LineH = 0.5;
        $s4LineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s4Pad, $s4HeaderH - $s4LineGap - $s4LineH, $wMm - 2 * $s4Pad, $s4LineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s4ContentY, $wMm, $s4ContentH, 'F');
        $s4ContentPad = $s4Pad;
        $s4ImgGap = 14;
        $s4ImgW = ($wMm - 2 * $s4Pad - $s4ImgGap) / 2;
        $s4ImgH = round($s4ContentH * 0.52);
        $s4ImgY = $s4ContentY + 8;
        $s4Scale = 100 / 25.4;
        $s4LoadCrop = function ($path, $boxW, $boxH) use ($s4Scale) {
            if (!$path || !file_exists($path) || !extension_loaded('gd')) return null;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            if (!$src) return null;
            $sw = imagesx($src);
            $sh = imagesy($src);
            $dwPx = (int) max(1, round($boxW * $s4Scale));
            $dhPx = (int) max(1, round($boxH * $s4Scale));
            $ratio = $boxW / $boxH;
            $imgR = $sw / $sh;
            if ($imgR >= $ratio) {
                $cropW = (int) round($sh * $ratio);
                $cropH = $sh;
                $srcX = (int) floor(($sw - $cropW) / 2);
                $srcY = 0;
            } else {
                $cropW = $sw;
                $cropH = (int) round($sw / $ratio);
                $srcX = 0;
                $srcY = (int) floor(($sh - $cropH) / 2);
            }
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/corp_s4_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        foreach ([0, 1] as $idx) {
            $path = isset($empresaSlide4Paths[$idx]) ? $empresaSlide4Paths[$idx] : null;
            $x = $s4Pad + $idx * ($s4ImgW + $s4ImgGap);
            $tmp = $s4LoadCrop($path, $s4ImgW, $s4ImgH);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $x, $s4ImgY, $s4ImgW, $s4ImgH);
                @unlink($tmp);
            } elseif ($path && file_exists($path)) {
                $mpdf->Image($path, $x, $s4ImgY, $s4ImgW, $s4ImgH);
            } else {
                $mpdf->SetFillColor(60, 60, 60);
                $mpdf->Rect($x, $s4ImgY, $s4ImgW, $s4ImgH, 'F');
            }
        }
        $s4TextRowY = $s4ImgY + $s4ImgH + 24;
        $s4TitleLeft = $s4Pad;
        $s4TitleW = $s4ImgW;
        $s4TitleGap = 6;
        $s4ParaLeft = $s4Pad + $s4ImgW + $s4ImgGap;
        $s4ParaW = $s4ImgW;
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($s4TitleLeft, $s4TextRowY);
        $mpdf->Cell($s4TitleW, 14, 'COMPANIES', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($s4TitleLeft, $s4TextRowY + 14 + $s4TitleGap);
        $mpdf->Cell($s4TitleW, 14, 'EXPORTERS', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->SetLeftMargin($s4ParaLeft);
        $mpdf->SetRightMargin($s4Pad);
        $mpdf->SetXY($s4ParaLeft, $s4TextRowY);
        $mpdf->MultiCell($s4ParaW, 7, 'Registered companies and exportable products/services declared for institutional dissemination.', 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Una slide por empresa: шапка como slide 1; fondo negro; izq "NOMBRE DE LA" + nombre (azul); centro imagen; derecha panel azul oscuro con datos
        $pageNum = 5;
        foreach ($companies as $emp) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $cid = (int) $emp['id'];
            $s5Pad = 20;
            $s5MiddleH = round($hMm * 0.44);
            $s5Remaining = $hMm - $s5MiddleH;
            $s5HeaderH = (int) round($s5Remaining * 0.42);
            $s5ContentY = $s5HeaderH;
            $s5ContentH = $hMm - $s5HeaderH;
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect(0, 0, $wMm, $s5HeaderH, 'F');
            $mpdf->SetTextColor(255, 255, 255);
            $s5LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
            $s5LogoW = 44;
            $s5LogoH = 22;
            if (file_exists($s5LogoPath)) {
                $imgSize = @getimagesize($s5LogoPath);
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $r = $imgSize[0] / $imgSize[1];
                    if ($s5LogoH * $r <= $s5LogoW) {
                        $lw = $s5LogoH * $r;
                        $lh = $s5LogoH;
                    } else {
                        $lw = $s5LogoW;
                        $lh = $s5LogoW / $r;
                    }
                    $mpdf->Image($s5LogoPath, $s5Pad, ($s5HeaderH - $lh) / 2, $lw, $lh);
                }
            }
            $mpdf->SetFont('dejavusans', '', 17);
            $mpdf->SetXY($wMm - $s5Pad - 36, ($s5HeaderH - 8) / 2);
            $mpdf->Cell(32, 8, 'Page ' . sprintf('%02d', $pageNum), 0, 0, 'R');
            $s5LineH = 0.5;
            $s5LineGap = 21;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($s5Pad, $s5HeaderH - $s5LineGap - $s5LineH, $wMm - 2 * $s5Pad, $s5LineH, 'F');
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect(0, $s5ContentY, $wMm, $s5ContentH, 'F');
            $s5ContentPad = $s5Pad;
            $s5TopPad = 8;
            $s5ColGap = 12;
            $s5LeftColW = round($wMm * 0.24);
            $s5ImgW = round($wMm * 0.28);
            $s5PanelW = $wMm - $s5Pad - $s5LeftColW - $s5ColGap - $s5ImgW - $s5ColGap - $s5Pad;
            $s5LeftX = $s5Pad;
            $s5ImgX = $s5Pad + $s5LeftColW + $s5ColGap;
            $s5PanelX = $s5ImgX + $s5ImgW + $s5ColGap;
            $s5ContentInnerH = $s5ContentH - $s5TopPad - $s5ContentPad;
            $empNameDisplay = !empty(trim((string)($emp['name_en'] ?? ''))) ? ($emp['name_en'] ?? '') : ($emp['name'] ?? '');
            $nombreEmpresa = function_exists('mb_strtoupper') ? mb_strtoupper($empNameDisplay) : strtoupper($empNameDisplay);
            $mpdf->SetTextColor(141, 188, 220);
            $mpdf->SetFont('dejavusans', 'B', 44);
            // Titles: break first between complete words; if a word exceeds width, split it with hyphen
            $s5Words = preg_split('/\s+/u', $nombreEmpresa, -1, PREG_SPLIT_NO_EMPTY);
            $s5Lines = [];
            $s5Cur = '';
            $s5NumWords = count($s5Words);
            foreach ($s5Words as $s5Idx => $wd) {
                $s5IsLastWord = ($s5Idx === $s5NumWords - 1);
                $s5Test = $s5Cur === '' ? $wd : $s5Cur . ' ' . $wd;
                if ($mpdf->GetStringWidth($s5Test) <= $s5LeftColW) {
                    $s5Cur = $s5Test;
                } else {
                    if ($mpdf->GetStringWidth($wd) <= $s5LeftColW) {
                        if ($s5Cur !== '') {
                            $s5Lines[] = $s5Cur;
                            $s5Cur = '';
                        }
                        $s5Cur = $wd;
                    } else {
                        $s5Word = $wd;
                        $s5Prefix = $s5Cur;
                        $s5Cur = '';
                        $s5AvailW = $s5LeftColW;
                        if ($s5Prefix !== '') {
                            $s5AvailW = $s5LeftColW - $mpdf->GetStringWidth($s5Prefix . ' ');
                        }
                        while ($s5Word !== '') {
                            if ($mpdf->GetStringWidth($s5Word) <= $s5AvailW) {
                                $s5LineContent = ($s5Prefix !== '' ? $s5Prefix . ' ' : '') . $s5Word;
                                $s5Lines[] = $s5LineContent;
                                $s5Prefix = '';
                                $s5AvailW = $s5LeftColW;
                                $s5Word = '';
                                break;
                            }
                            if ($mpdf->GetStringWidth($s5Word) <= $s5LeftColW && $s5Prefix !== '') {
                                $s5Lines[] = $s5Prefix;
                                $s5Lines[] = $s5Word;
                                $s5Prefix = '';
                                $s5AvailW = $s5LeftColW;
                                $s5Word = '';
                                break;
                            }
                            $s5Len = function_exists('mb_strlen') ? mb_strlen($s5Word) : strlen($s5Word);
                            $s5Fit = 0;
                            for ($s5N = 1; $s5N <= $s5Len; $s5N++) {
                                $s5Part = function_exists('mb_substr') ? mb_substr($s5Word, 0, $s5N) : substr($s5Word, 0, $s5N);
                                if ($mpdf->GetStringWidth($s5Part . '-') <= $s5AvailW) {
                                    $s5Fit = $s5N;
                                } else {
                                    break;
                                }
                            }
                            if ($s5Fit === 0) {
                                $s5Fit = 1;
                            }
                            if ($s5Fit <= 2 && $s5Len > 2) {
                                $s5Cur = ($s5Prefix !== '' ? $s5Prefix . ' ' : '') . $s5Word;
                                $s5Word = '';
                                break;
                            }
                            $s5Remainder = $s5Len - $s5Fit;
                            if ($s5Remainder >= 1 && $s5Remainder <= 2 && $s5Fit >= 2) {
                                $s5AllowShortRem = false;
                                if ($s5Remainder === 1 && $s5IsLastWord) {
                                    $s5AllowShortRem = true;
                                }
                                if ($s5Remainder === 2) {
                                    $s5RemStr = function_exists('mb_substr') ? mb_substr($s5Word, $s5Fit, 2) : substr($s5Word, $s5Fit, 2);
                                    if (in_array($s5RemStr, ['AS', 'ES', 'OS', 'ÓN', 'ON'], true) || (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($s5RemStr) === 2 && mb_substr($s5RemStr, 1, 1) === 'S')) {
                                        $s5AllowShortRem = true;
                                    }
                                }
                                if (!$s5AllowShortRem) {
                                    $s5Fit = max(1, $s5Len - 3);
                                }
                            }
                            if ($s5Len >= 10 && ($s5Len - $s5Fit) <= 3 && $s5Fit >= 2) {
                                $s5FitMin4 = max(1, $s5Len - 4);
                                $s5PartTest = (function_exists('mb_substr') ? mb_substr($s5Word, 0, $s5FitMin4) : substr($s5Word, 0, $s5FitMin4)) . '-';
                                if ($mpdf->GetStringWidth($s5PartTest) <= $s5AvailW) {
                                    $s5Fit = $s5FitMin4;
                                }
                            } elseif ($s5Len >= 7 && ($s5Len - $s5Fit) <= 2 && $s5Fit >= 2) {
                                $s5Fit = max(1, $s5Len - 4);
                                $s5PartTest = (function_exists('mb_substr') ? mb_substr($s5Word, 0, $s5Fit) : substr($s5Word, 0, $s5Fit)) . '-';
                                if ($mpdf->GetStringWidth($s5PartTest) > $s5AvailW) {
                                    $s5Fit = max(1, $s5Len - 3);
                                }
                            }
                            $s5Part = function_exists('mb_substr') ? mb_substr($s5Word, 0, $s5Fit) : substr($s5Word, 0, $s5Fit);
                            $s5LineContent = ($s5Prefix !== '' ? $s5Prefix . ' ' : '') . $s5Part . '-';
                            $s5Lines[] = $s5LineContent;
                            $s5Word = function_exists('mb_substr') ? mb_substr($s5Word, $s5Fit) : substr($s5Word, $s5Fit);
                            $s5Prefix = '';
                            $s5AvailW = $s5LeftColW;
                            if ($s5Word !== '') {
                                $s5Cur = $s5Word;
                                $s5Word = '';
                            }
                        }
                    }
                }
            }
            if ($s5Cur !== '') {
                $s5Lines[] = $s5Cur;
            }
            $s5NumLines = count($s5Lines);
            $s5TitleYBase = $s5ContentY + $s5ContentPad + round($s5ContentH * 0.28);
            $s5TitleY = $s5TitleYBase - ($s5NumLines > 2 ? ($s5NumLines - 2) * 6 : 0);
            $s5TitleY = max($s5ContentY + $s5ContentPad + 6, $s5TitleY);
            $mpdf->SetXY($s5LeftX, $s5TitleY);
            $mpdf->MultiCell($s5LeftColW, 16, implode("\n", $s5Lines), 0, 'L');
            $s5ImgH = round($s5ContentInnerH * 0.82);
            $s5ImgY = $s5ContentY + $s5TopPad + ($s5ContentInnerH - $s5ImgH) / 2;
            $compImgPath = $imagenesPorEmpresa[$cid] ?? $logosPorEmpresa[$cid] ?? null;
            if ($compImgPath && file_exists($compImgPath)) {
                $compImgOutPath = null;
                if (extension_loaded('gd')) {
                    $info = @getimagesize($compImgPath);
                    $ext = strtolower(pathinfo($compImgPath, PATHINFO_EXTENSION));
                    $src = false;
                    if ($info && $info[2] === IMAGETYPE_JPEG) {
                        $src = @imagecreatefromjpeg($compImgPath);
                    } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                        $src = @imagecreatefrompng($compImgPath);
                    } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                        $src = @imagecreatefromwebp($compImgPath);
                    }
                    if ($src && !empty($info[0]) && !empty($info[1])) {
                        $sw = imagesx($src);
                        $sh = imagesy($src);
                        $pxPerMm = 96 / 25.4;
                        $tw = (int) round($s5ImgW * $pxPerMm);
                        $th = (int) round($s5ImgH * $pxPerMm);
                        $scale = max($tw / $sw, $th / $sh);
                        $srcCropW = (int) round($tw / $scale);
                        $srcCropH = (int) round($th / $scale);
                        $srcX = (int) max(0, ($sw - $srcCropW) / 2);
                        $srcY = (int) max(0, ($sh - $srcCropH) / 2);
                        $dst = @imagecreatetruecolor($tw, $th);
                        if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $tw, $th, $srcCropW, $srcCropH)) {
                            $tmp = sys_get_temp_dir() . '/corp_comp_img_' . uniqid() . '.png';
                            if (imagepng($dst, $tmp)) {
                                $compImgOutPath = $tmp;
                            }
                            imagedestroy($dst);
                        }
                        imagedestroy($src);
                    }
                }
                if ($compImgOutPath && file_exists($compImgOutPath)) {
                    $mpdf->Image($compImgOutPath, $s5ImgX, $s5ImgY, $s5ImgW, $s5ImgH);
                    @unlink($compImgOutPath);
                } else {
                    $mpdf->Image($compImgPath, $s5ImgX, $s5ImgY, $s5ImgW, $s5ImgH);
                }
            } else {
                $mpdf->SetFillColor(50, 50, 50);
                $mpdf->Rect($s5ImgX, $s5ImgY, $s5ImgW, $s5ImgH, 'F');
            }
            $s5PanelColor = [11, 24, 120];
            $mpdf->SetFillColor($s5PanelColor[0], $s5PanelColor[1], $s5PanelColor[2]);
            $mpdf->Rect($s5PanelX, $s5ContentY + $s5TopPad, $s5PanelW, $s5ContentInnerH, 'F');
            $s5PanelPad = 14;
            $s5PanelInnerW = $s5PanelW - 2 * $s5PanelPad;
            $s5PanelY = $s5ContentY + $s5TopPad + $s5PanelPad;
            $s5LabelH = 6;
            $s5ValLineH = 5.5;
            $s5RowGap = 6;
            $s5LineGapPanel = 5;
            $s5Rows = [
                ['Main activity', !empty(trim((string)($emp['main_activity_en'] ?? ''))) ? ($emp['main_activity_en'] ?? '-') : ($emp['main_activity'] ?? '-')],
                ['Location', $localidadPorEmpresa[$cid] ?? '-'],
                ['Website', $emp['website'] ?? '-'],
                ['Social Media', isset($redesPorEmpresa[$cid]) ? implode("\n", $redesPorEmpresa[$cid]) : '-'],
                ['Year Established', !empty($emp['start_date']) ? date('Y', (int)$emp['start_date']) : '-'],
            ];
            foreach ($s5Rows as $idx => $row) {
                if ($idx > 0) {
                    $mpdf->SetDrawColor(255, 255, 255);
                    $mpdf->SetLineWidth(0.3);
                    $mpdf->Line($s5PanelX + $s5PanelPad, $s5PanelY, $s5PanelX + $s5PanelW - $s5PanelPad, $s5PanelY);
                    $s5PanelY += $s5LineGapPanel;
                }
                $mpdf->SetXY($s5PanelX + $s5PanelPad, $s5PanelY);
                $mpdf->SetTextColor(255, 255, 255);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($s5PanelInnerW, $s5LabelH, $row[0], 0, 1, 'L');
                $mpdf->SetX($s5PanelX + $s5PanelPad);
                $mpdf->SetFont('dejavusans', '', 11);
                $valStr = is_string($row[1]) ? $row[1] : (string)$row[1];
                $mpdf->MultiCell($s5PanelInnerW, $s5ValLineH, $valStr, 0, 'L');
                $s5PanelY = $mpdf->y + $s5RowGap;
            }
            $mpdf->SetDrawColor(0, 0, 0);
            $pageNum++;
        }
    } elseif ($i === 5) {
        // Intro slide Productos exportables: mismo estilo que slide 4; dos imágenes (izq más ancha); PRODUCTOS/EXPORTABLES (izq), párrafo (der)
        $prodIntroPageNum = 5 + count($companies);
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $p6Pad = 20;
        $p6MiddleH = round($hMm * 0.44);
        $p6Remaining = $hMm - $p6MiddleH;
        $p6HeaderH = (int) round($p6Remaining * 0.42);
        $p6ContentY = $p6HeaderH;
        $p6ContentH = $hMm - $p6HeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $p6HeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $p6LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $p6LogoW = 44;
        $p6LogoH = 22;
        if (file_exists($p6LogoPath)) {
            $imgSize = @getimagesize($p6LogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($p6LogoH * $r <= $p6LogoW) {
                    $lw = $p6LogoH * $r;
                    $lh = $p6LogoH;
                } else {
                    $lw = $p6LogoW;
                    $lh = $p6LogoW / $r;
                }
                $mpdf->Image($p6LogoPath, $p6Pad, ($p6HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $p6Pad - 36, ($p6HeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Page ' . sprintf('%02d', $prodIntroPageNum), 0, 0, 'R');
        $p6LineH = 0.5;
        $p6LineGap = 21;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($p6Pad, $p6HeaderH - $p6LineGap - $p6LineH, $wMm - 2 * $p6Pad, $p6LineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $p6ContentY, $wMm, $p6ContentH, 'F');
        $p6ContentPad = $p6Pad;
        $p6ImgGap = 14;
        $p6ImgLeftW = round(($wMm - 2 * $p6Pad - $p6ImgGap) * 0.62);
        $p6ImgRightW = $wMm - 2 * $p6Pad - $p6ImgGap - $p6ImgLeftW;
        $p6ImgH = round($p6ContentH * 0.38);
        $p6ImgY = $p6ContentY + 8;
        $p6Scale = 100 / 25.4;
        $p6LoadCrop = function ($path, $boxW, $boxH) use ($p6Scale) {
            if (!$path || !file_exists($path) || !extension_loaded('gd')) return null;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            if (!$src) return null;
            $sw = imagesx($src);
            $sh = imagesy($src);
            $dwPx = (int) max(1, round($boxW * $p6Scale));
            $dhPx = (int) max(1, round($boxH * $p6Scale));
            $ratio = $boxW / $boxH;
            $imgR = $sw / $sh;
            if ($imgR >= $ratio) {
                $cropW = (int) round($sh * $ratio);
                $cropH = $sh;
                $srcX = (int) floor(($sw - $cropW) / 2);
                $srcY = 0;
            } else {
                $cropW = $sw;
                $cropH = (int) round($sw / $ratio);
                $srcX = 0;
                $srcY = (int) floor(($sh - $cropH) / 2);
            }
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/corp_p6_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        $p6ImgPaths = [
            isset($productoImgCandidates[0]) ? $productoImgCandidates[0] : null,
            isset($productoImgCandidates[1]) ? $productoImgCandidates[1] : null,
        ];
        foreach ([0, 1] as $p6idx) {
            $path = $p6ImgPaths[$p6idx];
            $p6w = $p6idx === 0 ? $p6ImgLeftW : $p6ImgRightW;
            $p6x = $p6Pad + $p6idx * ($p6ImgLeftW + $p6ImgGap);
            $tmp = $p6LoadCrop($path, $p6w, $p6ImgH);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $p6x, $p6ImgY, $p6w, $p6ImgH);
                @unlink($tmp);
            } elseif ($path && file_exists($path)) {
                $mpdf->Image($path, $p6x, $p6ImgY, $p6w, $p6ImgH);
            } else {
                $mpdf->SetFillColor(60, 60, 60);
                $mpdf->Rect($p6x, $p6ImgY, $p6w, $p6ImgH, 'F');
            }
        }
        $p6TextRowY = $p6ImgY + $p6ImgH + 24;
        $p6TitleLeft = $p6Pad;
        $p6TitleW = $p6ImgLeftW;
        $p6TitleGap = 6;
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($p6TitleLeft, $p6TextRowY);
        $mpdf->Cell($p6TitleW, 14, 'PRODUCTS', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($p6TitleLeft, $p6TextRowY + 14 + $p6TitleGap);
        $mpdf->Cell($p6TitleW, 14, 'EXPORTABLES', 0, 1, 'L');
        $p6ParaLeft = $p6Pad + $p6ImgLeftW + $p6ImgGap;
        $p6ParaW = $p6ImgRightW;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->SetLeftMargin($p6ParaLeft);
        $mpdf->SetRightMargin($p6Pad);
        $mpdf->SetXY($p6ParaLeft, $p6TextRowY);
        $mpdf->MultiCell($p6ParaW, 7, 'Exportable products and services declared for institutional dissemination.', 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Slide(s) Productos/servicios: 3 por página (o 1 si queda solo uno); шапка como slide 1; tres imágenes arriba con marco blanco; bloque azul oscuro abajo en 3 columnas (o 1)
        $productoSlidesChunks = array_chunk($productosParaSlides, 3);
        $prodPageNum = $prodIntroPageNum + 1;
        $p7Scale = 100 / 25.4;
        $p7LoadCrop = function ($path, $boxW, $boxH) use ($p7Scale) {
            if (!$path || !file_exists($path) || !extension_loaded('gd')) return null;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            if (!$src) return null;
            $sw = imagesx($src);
            $sh = imagesy($src);
            $dwPx = (int) max(1, round($boxW * $p7Scale));
            $dhPx = (int) max(1, round($boxH * $p7Scale));
            $ratio = $boxW / $boxH;
            $imgR = $sw / $sh;
            if ($imgR >= $ratio) {
                $cropW = (int) round($sh * $ratio);
                $cropH = $sh;
                $srcX = (int) floor(($sw - $cropW) / 2);
                $srcY = 0;
            } else {
                $cropW = $sw;
                $cropH = (int) round($sw / $ratio);
                $srcX = 0;
                $srcY = (int) floor(($sh - $cropH) / 2);
            }
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/corp_p7_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        $p7CompanyNameById = [];
        foreach ($companies as $c) {
            $p7CompanyNameById[(int)($c['id'] ?? 0)] = !empty(trim((string)($c['name_en'] ?? ''))) ? ($c['name_en'] ?? '') : ($c['name'] ?? '');
        }
        foreach ($productoSlidesChunks as $idx => $chunk) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $p7Pad = 20;
            $p7MiddleH = round($hMm * 0.44);
            $p7Remaining = $hMm - $p7MiddleH;
            $p7HeaderH = (int) round($p7Remaining * 0.42);
            $p7ContentY = $p7HeaderH;
            $p7ContentH = $hMm - $p7HeaderH;
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect(0, 0, $wMm, $p7HeaderH, 'F');
            $mpdf->SetTextColor(255, 255, 255);
            $p7LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
            $p7LogoW = 44;
            $p7LogoH = 22;
            if (file_exists($p7LogoPath)) {
                $imgSize = @getimagesize($p7LogoPath);
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $r = $imgSize[0] / $imgSize[1];
                    if ($p7LogoH * $r <= $p7LogoW) {
                        $lw = $p7LogoH * $r;
                        $lh = $p7LogoH;
                    } else {
                        $lw = $p7LogoW;
                        $lh = $p7LogoW / $r;
                    }
                    $mpdf->Image($p7LogoPath, $p7Pad, ($p7HeaderH - $lh) / 2, $lw, $lh);
                }
            }
            $mpdf->SetFont('dejavusans', '', 17);
            $mpdf->SetXY($wMm - $p7Pad - 36, ($p7HeaderH - 8) / 2);
            $mpdf->Cell(32, 8, 'Page ' . sprintf('%02d', $prodPageNum), 0, 0, 'R');
            $p7LineH = 0.5;
            $p7LineGap = 21;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($p7Pad, $p7HeaderH - $p7LineGap - $p7LineH, $wMm - 2 * $p7Pad, $p7LineH, 'F');
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect(0, $p7ContentY, $wMm, $p7ContentH, 'F');
            $n = count($chunk);
            $p7ImgGap = 8;
            $p7ImgTopPad = 10;
            $p7ImgH = round($p7ContentH * 0.34);
            $p7ImgTotalW = ($wMm - 2 * $p7Pad - ($n - 1) * $p7ImgGap) * 0.92;
            $p7ImgW = $p7ImgTotalW / $n;
            $p7RowW = $n * $p7ImgW + ($n - 1) * $p7ImgGap;
            $p7StartX = $p7Pad + (($wMm - 2 * $p7Pad) - $p7RowW) / 2;
            $p7BlueY = $p7ContentY + $p7ImgTopPad + $p7ImgH + 8;
            $p7BlueH = $p7ContentH - ($p7ImgTopPad + $p7ImgH + 8) - $p7Pad;
            $p7BlueX = $p7Pad;
            $p7BlueW = $wMm - 2 * $p7Pad;
            foreach ($chunk as $k => $prod) {
                $pid = (int) $prod['id'];
                $p7x = $p7StartX + $k * ($p7ImgW + $p7ImgGap);
                $imgPath = $imagenesPorProducto[$pid] ?? null;
                if ($n === 1 && $imgPath && file_exists($imgPath)) {
                    $isz = @getimagesize($imgPath);
                    if (!empty($isz[0]) && !empty($isz[1])) {
                        $iw = $isz[0];
                        $ih = $isz[1];
                        $scale = min($p7ImgW / $iw, $p7ImgH / $ih);
                        $fitW = $iw * $scale;
                        $fitH = $ih * $scale;
                        $p7imgX = $p7x + ($p7ImgW - $fitW) / 2;
                        $p7imgY = $p7ContentY + $p7ImgTopPad + ($p7ImgH - $fitH) / 2;
                        $mpdf->Image($imgPath, $p7imgX, $p7imgY, $fitW, $fitH);
                    } else {
                        $mpdf->SetFillColor(50, 50, 50);
                        $mpdf->Rect($p7x, $p7ContentY + $p7ImgTopPad, $p7ImgW, $p7ImgH, 'F');
                    }
                } elseif ($n === 1) {
                    $mpdf->SetFillColor(50, 50, 50);
                    $mpdf->Rect($p7x, $p7ContentY + $p7ImgTopPad, $p7ImgW, $p7ImgH, 'F');
                } else {
                    $tmp = $p7LoadCrop($imgPath, $p7ImgW, $p7ImgH);
                    if ($tmp && file_exists($tmp)) {
                        $mpdf->Image($tmp, $p7x, $p7ContentY + $p7ImgTopPad, $p7ImgW, $p7ImgH);
                        @unlink($tmp);
                    } elseif ($imgPath && file_exists($imgPath)) {
                        $mpdf->Image($imgPath, $p7x, $p7ContentY + $p7ImgTopPad, $p7ImgW, $p7ImgH);
                    } else {
                        $mpdf->SetFillColor(50, 50, 50);
                        $mpdf->Rect($p7x, $p7ContentY + $p7ImgTopPad, $p7ImgW, $p7ImgH, 'F');
                    }
                }
            }
            $p7BlueColor = [11, 24, 120];
            $mpdf->SetFillColor($p7BlueColor[0], $p7BlueColor[1], $p7BlueColor[2]);
            $mpdf->Rect($p7BlueX, $p7BlueY, $p7BlueW, $p7BlueH, 'F');
            $p7ColW = $p7BlueW / $n;
            $p7ColPad = 6;
            $p7LineDraw = [200, 200, 220];
            $p7Align = ($n === 1) ? 'C' : 'L';
            $p7BottomY = $p7BlueY + $p7BlueH - $p7ColPad - 16;
            foreach ($chunk as $k => $prod) {
                $p7colX = $p7BlueX + $k * $p7ColW;
                if ($k > 0) {
                    $mpdf->SetDrawColor($p7LineDraw[0], $p7LineDraw[1], $p7LineDraw[2]);
                    $mpdf->SetLineWidth(0.25);
                    $mpdf->Line($p7colX, $p7BlueY, $p7colX, $p7BlueY + $p7BlueH);
                }
                $p7textX = $p7colX + $p7ColPad;
                $p7textW = $p7ColW - 2 * $p7ColPad;
                $p7EmpresaName = $p7CompanyNameById[(int)($prod['company_id'] ?? 0)] ?? '-';
                $p7EmpresaDisplay = function_exists('mb_strtoupper') ? mb_strtoupper($p7EmpresaName) : strtoupper($p7EmpresaName);
                $nameStr = !empty(trim((string)($prod['name_en'] ?? ''))) ? ($prod['name_en'] ?? '') : ($prod['name'] ?? '');
                $nameStrUpper = function_exists('mb_strtoupper') ? mb_strtoupper($nameStr) : strtoupper($nameStr);
                $descStr = !empty(trim((string)($prod['description_en'] ?? ''))) ? trim($prod['description_en']) : (trim($prod['description'] ?? '') ?: 'Brief product description');
                $p7DescMaxLen = 120;
                if (function_exists('mb_strlen') && mb_strlen($descStr) > $p7DescMaxLen) {
                    $descStr = (function_exists('mb_substr') ? mb_substr($descStr, 0, $p7DescMaxLen) : substr($descStr, 0, $p7DescMaxLen)) . '…';
                } elseif (!function_exists('mb_strlen') && strlen($descStr) > $p7DescMaxLen) {
                    $descStr = substr($descStr, 0, $p7DescMaxLen) . '…';
                }
                $mpdf->SetFont('dejavusans', 'B', 14);
                $p7CompanyLines = max(1, (int) ceil($mpdf->GetStringWidth($p7EmpresaDisplay) / max(1, $p7textW)));
                $mpdf->SetFont('dejavusans', 'B', 12);
                $p7ProductLines = max(1, (int) ceil($mpdf->GetStringWidth($nameStrUpper) / max(1, $p7textW)));
                $mpdf->SetFont('dejavusans', '', 11);
                $p7DescLines = max(1, (int) ceil($mpdf->GetStringWidth($descStr) / max(1, $p7textW)));
                $p7CompanyLineH = 9;
                $p7ProductLineH = 6;
                $p7DescLineH = 6;
                $p7DataLineH = 6;
                $p7TotalH = $p7CompanyLines * $p7CompanyLineH + 1 + $p7ProductLines * $p7ProductLineH + 1 + $p7DescLines * $p7DescLineH + 6 + 3 * $p7DataLineH;
                $p7AvailH = $p7BlueH - 2 * $p7ColPad;
                $p7textY = $p7BlueY + $p7ColPad;
                if ($p7TotalH < $p7AvailH * 0.85) {
                    $p7textY = $p7BlueY + $p7ColPad + ($p7AvailH - $p7TotalH) / 2;
                }
                $mpdf->SetTextColor(255, 255, 255);
                $mpdf->SetFont('dejavusans', 'B', 14);
                $mpdf->SetXY($p7textX, $p7textY);
                $mpdf->MultiCell($p7textW, $p7CompanyLineH, $p7EmpresaDisplay, 0, $p7Align);
                $mpdf->SetXY($p7textX, $mpdf->y);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->MultiCell($p7textW, $p7ProductLineH, $nameStrUpper, 0, $p7Align);
                $mpdf->SetX($p7textX);
                $mpdf->SetFont('dejavusans', '', 11);
                $mpdf->MultiCell($p7textW, $p7DescLineH, $descStr, 0, $p7Align);
                $p7DataBlockY = $mpdf->y + 6;
                $mpdf->SetXY($p7textX, $p7DataBlockY);
                $mpdf->SetFont('dejavusans', '', 11);
                $mpdf->MultiCell($p7textW, $p7DataLineH, 'Annual export: ' . (!empty(trim((string)($prod['annual_export_en'] ?? ''))) ? trim($prod['annual_export_en']) : (trim($prod['annual_export'] ?? '') ?: '-')), 0, $p7Align);
                $mpdf->SetX($p7textX);
                $mpdf->MultiCell($p7textW, $p7DataLineH, 'Certifications: ' . (!empty(trim((string)($prod['certifications_en'] ?? ''))) ? trim($prod['certifications_en']) : (trim($prod['certifications'] ?? '') ?: '-')), 0, $p7Align);
                $mpdf->SetX($p7textX);
                $mpdf->MultiCell($p7textW, $p7DataLineH, 'Tariff code (NCM/HS): ' . (trim($prod['tariff_code'] ?? '') ?: '-'), 0, $p7Align);
            }
            $mpdf->SetDrawColor(0, 0, 0);
            $prodPageNum++;
        }
    } elseif ($i === 6) {
        // Slide Contacto: fondo negro; título CONTACTO + línea blanca; franja central = imagen portada con overlay azul; solo logo centrado; abajo tres datos con iconos web/telefono/mail
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s7Pad = 24;
        $s7FullH = $hMm;
        $s7FullW = $wMm;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $s7FullW, $s7FullH, 'F');
        $s7TitleY = $s7Pad;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetXY($s7Pad, $s7TitleY);
        $mpdf->Cell($s7FullW - 2 * $s7Pad, 14, 'CONTACT', 0, 1, 'L');
        $s7LineY = $s7TitleY + 14 + 6;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s7Pad, $s7LineY, $s7FullW - 2 * $s7Pad, 0.5, 'F');
        $s7BlueH = round($s7FullH * 0.45);
        $s7BlueY = ($s7FullH - $s7BlueH) / 2;
        $s7BlueColor = [11, 24, 120];
        $s7PortadaPath = ($backgroundContactPath && file_exists($backgroundContactPath)) ? $backgroundContactPath : (!empty($portadaCandidates) ? $portadaCandidates[array_rand($portadaCandidates)] : null);
        $s7BlockImgPath = null;
        if ($s7PortadaPath && file_exists($s7PortadaPath) && extension_loaded('gd')) {
            $info = @getimagesize($s7PortadaPath);
            $ext = strtolower(pathinfo($s7PortadaPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($s7PortadaPath);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($s7PortadaPath);
            } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($s7PortadaPath);
            }
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $scale = 100 / 25.4;
                $dw = (int) max(1, round($s7FullW * $scale));
                $dh = (int) max(1, round($s7BlueH * $scale));
                $sw = $info[0];
                $sh = $info[1];
                $boxR = $dw / $dh;
                $imgR = $sw / $sh;
                if ($imgR >= $boxR) {
                    $cropW = (int) round($sh * $boxR);
                    $cropH = $sh;
                    $srcX = (int) floor(($sw - $cropW) / 2);
                    $srcY = 0;
                } else {
                    $cropW = $sw;
                    $cropH = (int) round($sw / $boxR);
                    $srcX = 0;
                    $srcY = (int) floor(($sh - $cropH) / 2);
                }
                $dst = @imagecreatetruecolor($dw, $dh);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dw, $dh, $cropW, $cropH)) {
                    $overlay = @imagecreatetruecolor($dw, $dh);
                    if ($overlay) {
                        $blue = imagecolorallocate($overlay, $s7BlueColor[0], $s7BlueColor[1], $s7BlueColor[2]);
                        @imagefilledrectangle($overlay, 0, 0, $dw, $dh, $blue);
                        @imagecopymerge($dst, $overlay, 0, 0, 0, 0, $dw, $dh, 62);
                        imagedestroy($overlay);
                    }
                    $tmp = sys_get_temp_dir() . '/corp_s7_portada_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $s7BlockImgPath = $tmp;
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        }
        if ($s7BlockImgPath && file_exists($s7BlockImgPath)) {
            $mpdf->Image($s7BlockImgPath, 0, $s7BlueY, $s7FullW, $s7BlueH);
            @unlink($s7BlockImgPath);
        } else {
            $mpdf->SetFillColor($s7BlueColor[0], $s7BlueColor[1], $s7BlueColor[2]);
            $mpdf->Rect(0, $s7BlueY, $s7FullW, $s7BlueH, 'F');
        }
        $s7LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $s7LogoW = 118;
        $s7LogoH = 62;
        $s7BlockX = ($s7FullW - $s7LogoW) / 2;
        $s7BlockY = $s7BlueY + ($s7BlueH - $s7LogoH) / 2;
        if (file_exists($s7LogoPath)) {
            $imgSize = @getimagesize($s7LogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s7LogoH * $r <= $s7LogoW) {
                    $lw = $s7LogoH * $r;
                    $lh = $s7LogoH;
                } else {
                    $lw = $s7LogoW;
                    $lh = $s7LogoW / $r;
                }
                $mpdf->Image($s7LogoPath, ($s7FullW - $lw) / 2, $s7BlueY + ($s7BlueH - $lh) / 2, $lw, $lh);
            } else {
                $mpdf->Image($s7LogoPath, $s7BlockX, $s7BlockY, $s7LogoW, $s7LogoH);
            }
        }
        $contacto = $configInstitucional;
        $s7Loc = trim($contacto['localidad_direccion'] ?? '') ?: 'Location';
        $s7Web = trim($contacto['sitio_web'] ?? '') ?: 'www.nombre.com.ar';
        $s7Mail = trim($contacto['mail'] ?? '') ?: 'contacto@contacto.com.ar';
        if (preg_match('#^https?://#i', $s7Web)) {
            $s7Web = preg_replace('#^https?://#i', '', $s7Web);
        }
        $s7IconSize = 8;
        $s7TextLineH = 7;
        $s7IconGap = 4;
        $s7BottomY = $s7FullH - 32;
        $s7TextTop = $s7BottomY - 2;
        $s7RowCenter = $s7TextTop + $s7TextLineH / 2;
        $s7IconY = $s7RowCenter - $s7IconSize / 2;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 11);
        // Localidad — слева, отступ как у белой линии (s7Pad)
        if (file_exists($iconTelefonoPath)) {
            $mpdf->Image($iconTelefonoPath, $s7Pad, $s7IconY, $s7IconSize, $s7IconSize);
        }
        $mpdf->SetXY($s7Pad + $s7IconSize + $s7IconGap, $s7TextTop);
        $mpdf->Cell(50, $s7TextLineH, $s7Loc, 0, 0, 'L');
        // Sitio web — по центру страницы
        $s7WebBlockW = $s7IconSize + $s7IconGap + 50;
        $s7WebStartX = ($s7FullW - $s7WebBlockW) / 2;
        if (file_exists($iconWebPath)) {
            $mpdf->Image($iconWebPath, $s7WebStartX, $s7IconY, $s7IconSize, $s7IconSize);
        }
        $mpdf->SetXY($s7WebStartX + $s7IconSize + $s7IconGap, $s7TextTop);
        $mpdf->Cell(50, $s7TextLineH, $s7Web, 0, 0, 'L');
        // Mail — справа, отступ от правого края как у белой линии (s7Pad); иконка влево с отступом от текста
        $s7MailCellW = 70;
        $s7MailRight = $s7FullW - $s7Pad;
        $s7MailIconX = $s7MailRight - $s7MailCellW - $s7IconGap - $s7IconSize;
        if (file_exists($iconMailPath)) {
            $mpdf->Image($iconMailPath, $s7MailIconX, $s7IconY, $s7IconSize, $s7IconSize);
        }
        $mpdf->SetXY($s7MailIconX + $s7IconSize + $s7IconGap, $s7TextTop);
        $mpdf->Cell($s7MailCellW, $s7TextLineH, $s7Mail, 0, 1, 'R');
    } else {
        if ($i === 2) {
            $mpdf->WriteHTML($htmlChunks[0] . $htmlChunks[$i]);
        } else {
            $mpdf->AddPage();
            $mpdf->WriteHTML($htmlChunks[$i]);
        }
    }
}
$nombreArchivo = 'Oferta_Exportable_Corporativo_' . preg_replace('/\s+/', '_', $configInstitucional['nombre_provincia']) . '_' . $configInstitucional['periodo_ano'] . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo $mpdf->Output('', 'S');
exit;

function buildOfertaPdfHtml($data) {
    $c = $data['config'];
    $logoPath = $data['logo_path'];
    $catPath = $data['cat_image_path'];
    $backgroundSlide1Uri = $data['background_slide1_uri'] ?? '';
    $pdfLogoUri = $data['pdf_logo_uri'] ?? '';
    $rubros = $data['rubros'];
    $metrics = $data['metrics'];
    $empresas = $data['empresas_destacadas'];
    $productos = $data['productos_muestra'];
    $companyNameById = $data['company_name_by_id'] ?? [];
    $mercados = $data['mercados_por_region'];
    $contacto = $data['contacto'];
    $imgProducto = $data['imagenes_producto'];
    $imgEmpresa = $data['imagenes_empresa'];
    $webRoot = $data['web_root'];
    $slideW = isset($data['slide_w_mm']) ? (int) $data['slide_w_mm'] : 450;
    $slideH = isset($data['slide_h_mm']) ? (int) $data['slide_h_mm'] : 253;
    $logoBlockConfig = isset($data['logo_block_config']) ? $data['logo_block_config'] : ['x_mm' => 30, 'y_mm' => 25, 'rect_w_mm' => 70, 'rect_h_mm' => 25, 'height_mm' => 14, 'pad_mm' => 5];

    $logoDataUri = '';
    if (file_exists($logoPath)) {
        $logoDataUri = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
    }
    $catDataUri = '';
    if ($catPath && file_exists($catPath)) {
        $catDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($catPath));
    }
    // Slide 2 imagen: prioridad CAT (productiva/infra), respaldo EMP (institucional)
    $s2ImageUri = $catDataUri;
    if (!$s2ImageUri && !empty($imgEmpresa)) {
        $firstEmpPath = reset($imgEmpresa);
        if ($firstEmpPath && file_exists($firstEmpPath)) {
            $ext = strtolower(pathinfo($firstEmpPath, PATHINFO_EXTENSION));
            $mime = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');
            $s2ImageUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($firstEmpPath));
        }
    }

    $css = '
    @page { margin: 0; }
    body { margin: 0; padding: 0; font-family: Blinker, sans-serif; }
    .slide { width: 1920px; height: 1080px; box-sizing: border-box; page-break-after: always; position: relative; }
    .slide:last-child { page-break-after: auto; }
    .primario { background: #003399; color: #fff; }
    .primario-oscuro { background: #000066; color: #fff; }
    .secundario { background: #75A8DA; color: #000; }
    .acento1 { color: #C4343B; }
    .acento2 { color: #FF0000; }
    .logo-caja { background: #fff; padding: 12px 18px; display: inline-block; }
    .logo-caja img { height: 48px; max-width: 180px; }
    h1.slide-title { font-size: 42px; margin: 0 0 12px 0; font-weight: 700; }
    h2.slide-subtitle { font-size: 28px; margin: 0 0 8px 0; font-weight: 600; }
    .texto { font-size: 18px; line-height: 1.4; }
    .bloque-texto { padding: 24px 32px; }
    ';
    // Dimensiones del slide (16:9, altura reducida)
    $css .= '
    .slide { width: ' . $slideW . 'mm; height: ' . $slideH . 'mm; }
    ';

    // Slide 1 corporativo (preview HTML): header solo logo + Página 01, línea, azul oscuro 44%, imagen encima, texto blanco, footer con Edición y flecha grandes
    $s1LineMm = 0.5;
    $s1MiddleMm = round($slideH * 0.44);
    $s1Remain = $slideH - $s1MiddleMm - $s1LineMm;
    $s1HeaderMm = (int) round($s1Remain * 0.35);
    $s1FooterMm = $s1Remain - $s1HeaderMm;
    $s1ImgSize = round($slideH * 0.46);
    $s1Border = 4;
    $row1Slide1 = '<table cellpadding="0" cellspacing="0" border="0" style="width:' . $slideW . 'mm;height:' . $slideH . 'mm;table-layout:fixed;">'
        . '<tr><td style="height:' . $s1HeaderMm . 'mm;background:#000;color:#fff;padding:0 14px;vertical-align:middle;">'
        . ($pdfLogoUri ? '<img src="' . $pdfLogoUri . '" alt="" style="height:18mm;vertical-align:middle;" />' : '')
        . '<span style="float:right;font-size:14px;">Page 01</span></td></tr>'
        . '<tr><td style="height:' . $s1LineMm . 'mm;background:#fff;"></td></tr>'
        . '<tr><td style="height:' . $s1MiddleMm . 'mm;background:#0B1878;padding:0;vertical-align:middle;">'
        . '<div style="margin-left:20mm;display:inline-block;border:' . $s1Border . 'mm solid #fff;transform:rotate(-3deg);width:' . $s1ImgSize . 'mm;height:' . $s1ImgSize . 'mm;overflow:hidden;vertical-align:middle;">'
        . ($backgroundSlide1Uri ? '<img src="' . $backgroundSlide1Uri . '" alt="" style="width:100%;height:100%;object-fit:cover;display:block;" />' : '<div style="width:100%;height:100%;background:#1a4d8c;"></div>')
        . '</div>'
        . '<div style="display:inline-block;vertical-align:middle;margin-left:24mm;color:#fff;">'
        . '<div style="font-size:56px;font-weight:bold;line-height:1.1;">OFFER</div><div style="font-size:56px;font-weight:bold;">EXPORTABLE</div>'
        . '<div style="font-size:22px;font-weight:bold;color:#fff;margin-top:12px;">' . htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper($c['nombre_provincia']) : strtoupper($c['nombre_provincia'])) . '</div></div></td></tr>'
        . '<tr><td style="height:' . $s1FooterMm . 'mm;background:#000;color:#fff;padding:0 14px 4px 14px;font-size:18px;vertical-align:middle;">'
        . '<span style="color:#fff;">Edition ' . (int)$c['periodo_ano'] . '</span><span style="float:right;font-size:24px;font-weight:bold;color:#fff;">&#8594;</span></td></tr>'
        . '</table>';
    $s1 = '<div class="slide">' . $row1Slide1 . '</div>';

    // Slide 2: solo 2 columnas (голубая 30% | синяя). La imagen se dibuja en el loop con WriteFixedPosHTML.
    $lbc = $logoBlockConfig;
    $s2LeftW = round($slideW * 0.30);
    $s2RightW = $slideW - $s2LeftW;
    $s2LogoBox = $pdfLogoUri
        ? '<div style="background:#fff;padding:' . $lbc['pad_mm'] . 'mm;width:' . $lbc['rect_w_mm'] . 'mm;height:' . $lbc['rect_h_mm'] . 'mm;display:inline-block;margin:' . $lbc['y_mm'] . 'mm 0 0 ' . $lbc['x_mm'] . 'mm;box-sizing:border-box;"><img src="' . $pdfLogoUri . '" alt="Logo" style="height:' . $lbc['height_mm'] . 'mm;max-width:100%;display:block;margin:0 auto;" /></div>'
        : '';
    $s2TextColStyle = 'max-width:110mm;margin-left:auto;margin-right:16mm;';
    $s2TdLeftStyle = 'width:' . $s2LeftW . 'mm;height:' . $slideH . 'mm;background:#75A8DA;vertical-align:top;padding:0;';
    $s2TdRightStyle = 'width:' . $s2RightW . 'mm;height:' . $slideH . 'mm;background:#003399;vertical-align:top;padding:20mm 0 20mm 12mm;';
    $s2 = '
    <div class="slide" style="width:' . $slideW . 'mm;height:' . $slideH . 'mm;margin:0;padding:0;page-break-inside:avoid;">
    <table cellpadding="0" cellspacing="0" border="0" style="width:' . $slideW . 'mm;height:' . $slideH . 'mm;table-layout:fixed;border-collapse:collapse;">
    <tr style="height:' . $slideH . 'mm;">
    <td style="' . $s2TdLeftStyle . '">' . $s2LogoBox . '</td>
    <td style="' . $s2TdRightStyle . '">
        <div style="' . $s2TextColStyle . '">
        <h1 class="slide-title" style="margin:0 0 10px 0;font-size:32px;font-weight:700;color:#fff;">Provincial productive context</h1>
        <p class="texto" style="margin:0 0 12px 0;font-size:16px;line-height:1.4;color:#fff;">The Province updates its exportable offer to provide external buyers with clear, accessible information in graphic and digital formats.</p>
        <p class="texto" style="margin:0;font-size:15px;line-height:1.4;color:#fff;">This tool strengthens trade promotion, supporting the dissemination of offers, trade missions and participation in fairs and business rounds.</p>
        </div>
    </td>
    </tr>
    </table>
    </div>';

    $rubroA = isset($rubros[0]) ? $rubros[0] : 'Sector 1';
    $rubroB = isset($rubros[1]) ? $rubros[1] : 'Sector 2';
    $rubroC = isset($rubros[2]) ? $rubros[2] : 'Sector 3';
    $s3Img = $catDataUri ? '<img src="' . $catDataUri . '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />' : '<div style="width:100%;height:100%;background:#000066;border-radius:50%;"></div>';
    $s3 = '
    <div class="slide" style="display:flex;">
        <div style="width:30%;background:#75A8DA;padding:32px 24px;">
            <div class="logo-caja" style="margin-bottom:20px;">' . ($logoDataUri ? '<img src="' . $logoDataUri . '" alt="Logo" />' : '') . '</div>
            <h1 class="slide-title" style="color:#000;">Productive sectors</h1>
            <p class="texto" style="color:#000;">The provincial exportable offer is organized by sectors to facilitate search and international promotion.</p>
        </div>
        <div class="primario" style="width:40%;padding:32px 24px;">
            <div style="margin-bottom:16px;"><strong style="color:#75A8DA;">Rubro A</strong><br><span class="texto">' . htmlspecialchars($rubroA) . '</span></div>
            <div style="margin-bottom:16px;"><strong style="color:#75A8DA;">Rubro B</strong><br><span class="texto">' . htmlspecialchars($rubroB) . '</span></div>
            <div><strong style="color:#75A8DA;">Rubro C</strong><br><span class="texto">' . htmlspecialchars($rubroC) . '</span></div>
        </div>
        <div style="width:30%;padding:32px 24px;">
            <div style="width:180px;height:180px;margin:0 auto 20px;overflow:hidden;border-radius:50%;">' . $s3Img . '</div>
            <p class="texto" style="text-align:center;"><strong>N° Registered companies:</strong> ' . (int)$metrics['empresas'] . '</p>
            <p class="texto" style="text-align:center;"><strong>N° Products and services loaded:</strong> ' . (int)$metrics['productos'] . '</p>
        </div>
    </div>';

    $mimeFromPath = function ($path) {
        if (!$path) return 'image/jpeg';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');
    };
    $bloquesEmpresa = '';
    foreach (array_slice($empresas, 0, 3) as $i => $emp) {
        $cid = (int)($emp['id'] ?? 0);
        $empName = !empty(trim((string)($emp['name_en'] ?? ''))) ? ($emp['name_en'] ?? '') : ($emp['name'] ?? '');
        $empMainAct = !empty(trim((string)($emp['main_activity_en'] ?? ''))) ? ($emp['main_activity_en'] ?? '') : ($emp['main_activity'] ?? '');
        $bloquesEmpresa .= '<div style="margin-bottom:16px;"><span class="acento1" style="font-weight:700;">' . htmlspecialchars($empName) . '</span><br><span style="color:#000066;">' . htmlspecialchars($empMainAct) . '</span></div>';
    }
    $s4Imgs = '';
    foreach (array_slice($empresas, 0, 3) as $emp) {
        $cid = (int)($emp['id'] ?? 0);
        $path = $imgEmpresa[$cid] ?? null;
        $src = '';
        if ($path && file_exists($path)) {
            $m = $mimeFromPath($path);
            $src = 'data:' . $m . ';base64,' . base64_encode(file_get_contents($path));
        } elseif ($catDataUri) {
            $src = $catDataUri;
        }
        $s4Imgs .= '<div style="flex:1;margin:4px;min-height:140px;background:#eee;border-radius:8px;overflow:hidden;">' . ($src ? '<img src="' . $src . '" alt="" style="width:100%;height:100%;object-fit:cover;" />' : '') . '</div>';
    }
    $s4 = '
    <div class="slide" style="display:flex;">
        <div style="width:28%;background:#75A8DA;padding:24px;">' . $bloquesEmpresa . '</div>
        <div style="width:44%;display:flex;flex-direction:column;padding:24px;">' . $s4Imgs . '</div>
        <div class="primario" style="width:28%;padding:24px;position:relative;">
            <h1 class="slide-title">Featured companies</h1>
            <p class="texto">Representative selection of companies with registered exportable offer.</p>
            <div class="logo-caja" style="position:absolute;bottom:24px;right:24px;">' . ($logoDataUri ? '<img src="' . $logoDataUri . '" alt="Logo" />' : '') . '</div>
        </div>
    </div>';

    $cards = '';
    foreach (array_slice($productos, 0, 6) as $p) {
        $pid = (int)($p['id'] ?? 0);
        $cid = (int)($p['company_id'] ?? 0);
        $companyName = isset($companyNameById[$cid]) ? $companyNameById[$cid] : '-';
        $path = $imgProducto[$pid] ?? null;
        $src = '';
        if ($path && file_exists($path)) {
            $m = $mimeFromPath($path);
            $src = 'data:' . $m . ';base64,' . base64_encode(file_get_contents($path));
        }
        $cards .= '<div style="background:#fff;border-radius:8px;overflow:hidden;padding:12px;text-align:center;">
            <div style="height:100px;background:#eee;border-radius:6px;overflow:hidden;">' . ($src ? '<img src="' . $src . '" alt="" style="width:100%;height:100%;object-fit:cover;" />' : '') . '</div>
            <p style="margin:6px 0 2px;font-size:12px;color:#333;">COMPANY: <strong>' . htmlspecialchars($companyName) . '</strong></p>
            <p style="margin:8px 0 4px;font-weight:700;color:#000;">' . htmlspecialchars(!empty(trim((string)($p['name_en'] ?? ''))) ? ($p['name_en'] ?? '') : ($p['name'] ?? '')) . '</p>
            <p style="margin:0;font-size:14px;color:#003399;">' . htmlspecialchars($p['activity'] ?? '') . '</p>
            <p style="margin:4px 0 0;font-size:12px;">' . htmlspecialchars(mb_substr(!empty(trim((string)($p['description_en'] ?? ''))) ? ($p['description_en'] ?? '') : ($p['description'] ?? ''), 0, 80)) . '</p>
        </div>';
    }
    $s5 = '
    <div class="slide primario" style="padding:40px;">
        <h1 class="slide-title" style="text-align:center;margin-bottom:32px;">Exportable products</h1>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">' . $cards . '</div>
    </div>';

    $s7 = '
    <div class="slide" style="display:flex;">
        <div style="width:35%;background:#C4343B;color:#fff;padding:40px;">
            <div class="logo-caja" style="margin-bottom:32px;">' . ($logoDataUri ? '<img src="' . $logoDataUri . '" alt="Logo" />' : '') . '</div>
            <h1 class="slide-title" style="font-size:32px;">Institutional contact</h1>
            <p class="texto">' . htmlspecialchars($contacto['area_responsable'] ?? '') . '</p>
            <p class="texto">Tel: ' . htmlspecialchars($contacto['telefono'] ?? '') . '</p>
            <p class="texto">Web: ' . htmlspecialchars($contacto['sitio_web'] ?? '') . '</p>
            <p class="texto">Mail: ' . htmlspecialchars($contacto['mail'] ?? '') . '</p>
            <p class="texto">' . htmlspecialchars($contacto['localidad_direccion'] ?? '') . '</p>
        </div>
        <div style="width:65%;background:#75A8DA;display:flex;align-items:center;justify-content:center;">
            <h1 class="slide-title" style="color:#000;">Institutional Contact</h1>
        </div>
    </div>';

    // Devolver por partes para que mPDF no supere pcre.backtrack_limit
    $header = '<!DOCTYPE html><html><head><meta charset="utf-8"><link href="https://fonts.googleapis.com/css2?family=Blinker:wght@400;600;700&display=swap" rel="stylesheet"><style>' . $css . '</style></head><body>';
    return [$header, $s1, $s2, $s3, $s4, $s5, $s7 . '</body></html>'];
}
