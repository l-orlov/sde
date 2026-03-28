<?php
// Oferta Exportable — variante Moderno (misma estructura que Corporativo; nombre y archivo de salida distintos)
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
    echo "Para generar el PDF, ejecute en la raíz del proyecto: composer install\n";
    exit;
}
require_once $vendorAutoload;

require_once $webRoot . '/includes/functions.php';
DBconnect();

global $link;

// ——— Configuración institucional (editar o mover a BD/config según necesidad) ———
$configInstitucional = [
    'titulo_documento'   => 'Oferta Exportable',
    'nombre_provincia'   => 'Santiago del Estero',
    'periodo_ano'        => date('Y'),
    'area_responsable'   => 'Área de Comercio Exterior',
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
foreach (['Productivo1.webp', 'Productivo2.jpg', 'Productivo3.jpg', 'Productivo4.jpg', 'Productivo5.jpg'] as $name) {
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
$identidadCandidatesShuffled = $identidadCandidates;
shuffle($identidadCandidatesShuffled);
$identidadSlide3Paths = array_slice($identidadCandidatesShuffled, 0, 5);
while (count($identidadSlide3Paths) < 5) {
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
$pdfLogoCfiPath = $assetsDir . '/LogoCFI.png';
$pdfLogoCfiWhitePath = $assetsDir . '/LogoCFIwhite.png';
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

// Companies aprobadas (slide empresa: contacto desde company_contacts)
$q = "SELECT c.id, c.name, c.main_activity, c.website
      FROM companies c
      INNER JOIN users u ON u.id = c.user_id
      WHERE c.moderation_status = 'approved' AND u.include_in_business_exports = 1
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
$contactoSlidePorEmpresa = pdf_load_first_company_contact_strings_for_slides($link, $companyIds);
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
    $q = "SELECT p.id, p.name, p.activity, p.description, p.company_id, p.type
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
    $q = "SELECT p.id, p.name, p.activity, p.description, p.annual_export, p.certifications, p.company_id, p.type, p.tariff_code
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
if (!empty($empresasDestacadas)) {
    $destacadaIds = array_column($empresasDestacadas, 'id');
    $idsStr = implode(',', array_map('intval', $destacadaIds));
    $q = "SELECT p.company_id, f.file_path FROM files f
          INNER JOIN products p ON p.id = f.product_id
          WHERE p.company_id IN ($idsStr) AND f.file_type IN ('product_photo','product_photo_sec')
            AND (f.is_temporary = 0 OR f.is_temporary IS NULL)
          ORDER BY p.company_id ASC, f.id ASC";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($imagenesPorEmpresa[$cid])) {
                $imagenesPorEmpresa[$cid] = $storageUploadsDir . '/' . ltrim($row['file_path'], '/');
            }
        }
    }
}

// Localidad por empresa (desde company_addresses, primera dirección)
$localidadPorEmpresa = [];
$descripcionPorEmpresa = []; // breve descripción: primer producto por empresa, truncado
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT company_id, locality FROM company_addresses WHERE company_id IN ($ids) ORDER BY company_id, id ASC";
    $r = @mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($localidadPorEmpresa[$cid]) && $row['locality'] !== null && $row['locality'] !== '') {
                $localidadPorEmpresa[$cid] = $row['locality'];
            }
        }
    }
    $q = "SELECT company_id, description FROM products WHERE company_id IN ($ids) AND description IS NOT NULL AND description != '' ORDER BY company_id, id ASC";
    $r = mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($descripcionPorEmpresa[$cid])) {
                $descripcionPorEmpresa[$cid] = mb_substr(trim($row['description']), 0, 120);
                if (mb_strlen(trim($row['description'])) > 120) {
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
    $u = preg_replace('/[?#].*$/', '', $u);
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
    $prefixesToStrip = ['www.instagram.com/', 'instagram.com/', 'www.facebook.com/', 'facebook.com/', 'www.fb.com/', 'fb.com/', 'www.vk.com/', 'vk.com/', 'www.vkontakte.ru/', 'vkontakte.ru/', 'www.vkontakte.com/', 'vkontakte.com/'];
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
    // 1) Desde products.target_markets (formulario guarda mercados por producto/servicio aquí)
    $hasProductsMarkets = false;
    $check = @mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'target_markets'");
    if ($check && mysqli_num_rows($check) > 0) {
        $hasProductsMarkets = true;
    }
    if ($hasProductsMarkets) {
        $q = "SELECT target_markets FROM products WHERE company_id IN ($ids) AND target_markets IS NOT NULL AND target_markets != '' AND target_markets != '[]'";
        $res = @mysqli_query($link, $q);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
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
    // 2) Respaldo: company_data.target_markets (datos antiguos o desde admin)
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

$renderCroppedImage = function ($imgPath, $x, $y, $w, $h) use ($mpdf) {
    if (!$imgPath || !file_exists($imgPath)) return;
    if (extension_loaded('gd')) {
        $info = @getimagesize($imgPath);
        $ext  = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
        $src  = false;
        if ($info && $info[2] === IMAGETYPE_JPEG)
            $src = @imagecreatefromjpeg($imgPath);
        elseif ($info && $info[2] === IMAGETYPE_PNG)
            $src = @imagecreatefrompng($imgPath);
        elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp'))
            $src = @imagecreatefromwebp($imgPath);
        if ($src) {
            $sw = imagesx($src); $sh = imagesy($src);
            $pxPerMm  = 96 / 25.4;
            $tw       = (int) round($w * $pxPerMm);
            $th       = (int) round($h * $pxPerMm);
            $scale    = max($tw / $sw, $th / $sh);
            $srcCropW = (int) round($tw / $scale);
            $srcCropH = (int) round($th / $scale);
            $srcX     = (int) max(0, ($sw - $srcCropW) / 2);
            $srcY     = (int) max(0, ($sh - $srcCropH) / 2);
            $dst = @imagecreatetruecolor($tw, $th);
            if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $tw, $th, $srcCropW, $srcCropH)) {
                $tmp = sys_get_temp_dir() . '/crop_' . uniqid() . '.jpg';
                if (imagejpeg($dst, $tmp, 85)) {
                    imagedestroy($dst);
                    imagedestroy($src);
                    $mpdf->Image($tmp, $x, $y, $w, $h);
                    @unlink($tmp);
                    return;
                }
                imagedestroy($dst);
            } else {
                if ($dst) imagedestroy($dst);
            }
            imagedestroy($src);
        }
    }
    $mpdf->Image($imgPath, $x, $y, $w, $h);
};

$redBarH = 5;
$contentH = $hMm - $redBarH;

for ($i = 0; $i < count($htmlChunks); $i++) {
    if ($i === 0) {
        // No usar HTML para la primera página; el slide 1 se dibuja solo por API (i=1)
        continue;
    } elseif ($i === 1) {
        // Slide 1 moderno: fondo portada a pantalla completa; izquierda OFERTA EXPORTABLE + caja roja provincia; derecha arriba logo; abajo derecha Edición
        $mpdf->AddPage();
        $s1Pad = 28;
        $s1BgMargin = 12;
        $s1PortadaPath = $backgroundSlide1Path;
        $s1BgPath = null;
        if ($s1PortadaPath && file_exists($s1PortadaPath) && extension_loaded('gd')) {
            $info = @getimagesize($s1PortadaPath);
            $ext = $info ? strtolower(pathinfo($s1PortadaPath, PATHINFO_EXTENSION)) : '';
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($s1PortadaPath);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($s1PortadaPath);
            } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($s1PortadaPath);
            }
            $s1BgW = $wMm - 2 * $s1BgMargin;
            $s1BgH = $hMm - 2 * $s1BgMargin;
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $scale = 100 / 25.4;
                $dw = (int) max(1, round($s1BgW * $scale));
                $dh = (int) max(1, round($s1BgH * $scale));
                $sw = imagesx($src);
                $sh = imagesy($src);
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
                        $black = imagecolorallocate($overlay, 0, 0, 0);
                        @imagefilledrectangle($overlay, 0, 0, $dw, $dh, $black);
                        @imagecopymerge($dst, $overlay, 0, 0, 0, 0, $dw, $dh, 35);
                        imagedestroy($overlay);
                    }
                    $tmp = sys_get_temp_dir() . '/moderno_portada_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $s1BgPath = $tmp;
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        }
        $s1BgW = $wMm - 2 * $s1BgMargin;
        $s1BgH = $hMm - 2 * $s1BgMargin;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        if ($s1BgPath && file_exists($s1BgPath)) {
            $mpdf->Image($s1BgPath, $s1BgMargin, $s1BgMargin, $s1BgW, $s1BgH);
            @unlink($s1BgPath);
        } elseif ($s1PortadaPath && file_exists($s1PortadaPath)) {
            $mpdf->Image($s1PortadaPath, $s1BgMargin, $s1BgMargin, $s1BgW, $s1BgH);
        }
        $mpdf->SetTextColor(255, 255, 255);
        $s1TextLeft = $s1Pad;
        $s1TextW = $wMm * 0.5;
        $s1Ty = round($hMm * 0.32);
        $mpdf->SetXY($s1TextLeft, $s1Ty);
        $mpdf->SetFont('dejavusans', 'B', 58);
        $mpdf->Cell($s1TextW, 22, 'OFERTA', 0, 1, 'L');
        $mpdf->SetX($s1TextLeft);
        $mpdf->SetFont('dejavusans', 'B', 52);
        $mpdf->Cell($s1TextW, 20, 'EXPORTABLE', 0, 1, 'L');
        $s1BoxPad = 8;
        $s1BoxH = 14;
        $s1BoxY = $s1Ty + 48;
        $s1ProvText = $configInstitucional['nombre_provincia'];
        $mpdf->SetFont('dejavusans', 'B', 16);
        $s1BoxW = min($s1TextW, 82);
        $mpdf->SetFillColor(196, 52, 59);
        $mpdf->Rect($s1TextLeft, $s1BoxY, $s1BoxW, $s1BoxH, 'F');
        $mpdf->SetXY($s1TextLeft + $s1BoxPad, $s1BoxY + ($s1BoxH - 8) / 2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell($s1BoxW - 2 * $s1BoxPad, 8, $s1ProvText, 0, 0, 'L');
        $s1BarH = 28;
        $s1BarPadR = 14;
        $s1LogoPadFromImage = 10;
        $s1BarY = $s1BgMargin + $s1LogoPadFromImage;
        $s1SdeBarW = 64;
        $s1CfiBarW = 54;
        $s1BarGap = 4;
        $s1CfiDarkR = 32;
        $s1CfiDarkG = 38;
        $s1CfiDarkB = 48;
        $s1SdeBarX = ($s1BgMargin + $s1BgW) - $s1BarPadR - $s1SdeBarW;
        $s1CfiBarX = $s1SdeBarX - $s1BarGap - $s1CfiBarW;
        $s1CfiImgPath = (file_exists($pdfLogoCfiWhitePath)) ? $pdfLogoCfiWhitePath : $pdfLogoCfiPath;
        if (file_exists($s1CfiImgPath)) {
            $mpdf->SetFillColor($s1CfiDarkR, $s1CfiDarkG, $s1CfiDarkB);
            $mpdf->Rect($s1CfiBarX, $s1BarY, $s1CfiBarW, $s1BarH, 'F');
            $imgSizeCfi = @getimagesize($s1CfiImgPath);
            $maxCfiInW = $s1CfiBarW - 8;
            $maxCfiInH = $s1BarH - 8;
            if (!empty($imgSizeCfi[0]) && !empty($imgSizeCfi[1])) {
                $cfiR = $imgSizeCfi[0] / $imgSizeCfi[1];
                if ($maxCfiInH * $cfiR <= $maxCfiInW) {
                    $cfiLw = $maxCfiInH * $cfiR;
                    $cfiLh = $maxCfiInH;
                } else {
                    $cfiLw = $maxCfiInW;
                    $cfiLh = $maxCfiInW / $cfiR;
                }
            } else {
                $cfiLw = $maxCfiInW;
                $cfiLh = $maxCfiInH;
            }
            $mpdf->Image($s1CfiImgPath, $s1CfiBarX + ($s1CfiBarW - $cfiLw) / 2, $s1BarY + ($s1BarH - $cfiLh) / 2, $cfiLw, $cfiLh);
        } else {
            $s1SdeBarX = ($s1BgMargin + $s1BgW) - $s1BarPadR - $s1SdeBarW;
        }
        $mpdf->SetFillColor(196, 52, 59);
        $mpdf->Rect($s1SdeBarX, $s1BarY, $s1SdeBarW, $s1BarH, 'F');
        $s1LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s1LogoPath)) {
            $imgSize = @getimagesize($s1LogoPath);
            $maxW = $s1SdeBarW - 8;
            $maxH = $s1BarH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($maxH * $r <= $maxW) {
                    $lw = $maxH * $r;
                    $lh = $maxH;
                } else {
                    $lw = $maxW;
                    $lh = $maxW / $r;
                }
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s1LogoPath, $s1SdeBarX + ($s1SdeBarW - $lw) / 2, $s1BarY + ($s1BarH - $lh) / 2, $lw, $lh);
        }
        $s1EdicionText = 'Edición ' . $configInstitucional['periodo_ano'];
        $s1EdicionBoxPad = 10;
        $s1EdicionBoxH = 18;
        $s1EdicionBoxW = 58;
        $s1EdicionX = $s1BgMargin + $s1BgW - $s1EdicionBoxW + 2;
        $s1EdicionY = $s1BgMargin + $s1BgH - $s1EdicionBoxH + 2;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s1EdicionX, $s1EdicionY, $s1EdicionBoxW, $s1EdicionBoxH, 'F');
        $mpdf->SetFont('dejavusans', '', 18);
        $mpdf->SetTextColor(100, 35, 35);
        $mpdf->SetXY($s1EdicionX, $s1EdicionY + ($s1EdicionBoxH - 9) / 2);
        $mpdf->Cell($s1EdicionBoxW, 9, $s1EdicionText, 0, 0, 'C');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        // Slide 2 moderno: izquierda = foto Productivo a toda altura (sin bloque rojo); derecha = fondo blanco, logo como slide 1, CONTEXTO PROVINCIAL centrado, párrafos
        $s2Pad = 20;
        $s2ImgW = round($wMm * 0.36);
        $s2ImgH = round($hMm * 0.60);
        $s2ImgMargin = 18;
        $s2ImgX = 32;
        $s2ImgY = ($hMm - $s2ImgH) / 2;
        $s2LeftW = $s2ImgX + $s2ImgW + $s2ImgMargin;
        $s2RightW = $wMm - $s2LeftW;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $s2LeftW, $hMm, 'F');
        $s2ImgPath = isset($productivoSlide2Paths[0]) ? $productivoSlide2Paths[0] : $productivoSlide2Path;
        if ($s2ImgPath && file_exists($s2ImgPath) && extension_loaded('gd')) {
            $info = @getimagesize($s2ImgPath);
            $ext = $info ? strtolower(pathinfo($s2ImgPath, PATHINFO_EXTENSION)) : '';
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s2ImgPath);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s2ImgPath);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s2ImgPath);
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $scale = 100 / 25.4;
                $dw = (int) max(1, round($s2ImgW * $scale));
                $dh = (int) max(1, round($s2ImgH * $scale));
                $sw = imagesx($src);
                $sh = imagesy($src);
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
                    $tmp = sys_get_temp_dir() . '/moderno_s2_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, $s2ImgX, $s2ImgY, $s2ImgW, $s2ImgH);
                        @unlink($tmp);
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        } elseif ($s2ImgPath && file_exists($s2ImgPath)) {
            $mpdf->Image($s2ImgPath, $s2ImgX, $s2ImgY, $s2ImgW, $s2ImgH);
        }
        $s2RedBlockW = round($s2ImgW * 0.58);
        $s2RedGap = 5;
        $s2RedBlockShift = 32;
        $s2RedBlockHTop = min(max(19, 2 * ($s2ImgY - $s2RedGap)), 26);
        $s2RedBlockHBot = min(max(19, 2 * ($hMm - $s2ImgY - $s2ImgH - $s2RedGap)), 26);
        $s2RedAlpha = 0.65;
        if (extension_loaded('gd')) {
            $s2RedR = 196;
            $s2RedG = 52;
            $s2RedB = 59;
            $s2TilePx = 20;
            $s2TilePy = 20;
            $s2Overlay = @imagecreatetruecolor($s2TilePx, $s2TilePy);
            if ($s2Overlay) {
                imagealphablending($s2Overlay, false);
                imagesavealpha($s2Overlay, true);
                $s2Trans = imagecolorallocatealpha($s2Overlay, 0, 0, 0, 127);
                imagefill($s2Overlay, 0, 0, $s2Trans);
                $s2RedAlphaVal = (int) round(127 * (1 - $s2RedAlpha));
                $s2RedColor = imagecolorallocatealpha($s2Overlay, $s2RedR, $s2RedG, $s2RedB, $s2RedAlphaVal);
                imagefilledrectangle($s2Overlay, 0, 0, $s2TilePx - 1, $s2TilePy - 1, $s2RedColor);
                $s2RedTmp = sys_get_temp_dir() . '/moderno_s2_red_' . uniqid() . '.png';
                if (imagepng($s2Overlay, $s2RedTmp)) {
                    $mpdf->Image($s2RedTmp, 0, $s2RedGap + $s2RedBlockShift, $s2RedBlockW, $s2RedBlockHTop);
                    $s2RedMidY = $s2RedGap + $s2RedBlockShift + $s2RedBlockHTop;
                    $s2RedMidH = $hMm - $s2RedGap - $s2RedBlockShift - $s2RedBlockHBot - $s2RedMidY;
                    $s2RedBlockWMid = round($s2RedBlockW * 0.3);
                    $s2RedMidX = 66;
                    if ($s2RedMidH > 2) {
                        $mpdf->Image($s2RedTmp, $s2RedMidX, $s2RedMidY, $s2RedBlockWMid, $s2RedMidH);
                    }
                    $mpdf->Image($s2RedTmp, 0, $hMm - $s2RedGap - $s2RedBlockShift - $s2RedBlockHBot, $s2RedBlockW, $s2RedBlockHBot);
                    @unlink($s2RedTmp);
                }
                imagedestroy($s2Overlay);
            }
        }
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s2LeftW, 0, $s2RightW, $hMm, 'F');
        $s2LogoW = 52;
        $s2LogoH = 26;
        $s2LogoX = $wMm - $s2Pad - $s2LogoW;
        $s2LogoY = $s2Pad;
        $s2LogoPath = $pdfLogoPath;
        if (file_exists($s2LogoPath)) {
            $imgSize = @getimagesize($s2LogoPath);
            $maxW = $s2LogoW - 4;
            $maxH = $s2LogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($maxH * $r <= $maxW) {
                    $lw = $maxH * $r;
                    $lh = $maxH;
                } else {
                    $lw = $maxW;
                    $lh = $maxW / $r;
                }
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s2LogoPath, $s2LogoX + ($s2LogoW - $lw) / 2, $s2LogoY + ($s2LogoH - $lh) / 2, $lw, $lh);
        }
        $s2TextPad = 36;
        $s2TextLeft = $s2LeftW + $s2TextPad;
        $s2TextW = $s2RightW - $s2TextPad - $s2Pad;
        $s2TitleTop = $s2LogoY + $s2LogoH + 20;
        $mpdf->SetTextColor(160, 35, 35);
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetXY($s2TextLeft, $s2TitleTop);
        $mpdf->Cell($s2TextW, 12, 'CONTEXTO', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetXY($s2TextLeft, $s2TitleTop + 14);
        $mpdf->Cell($s2TextW, 12, 'PROVINCIAL', 0, 1, 'L');
        $s2ParaTop = $s2TitleTop + 40;
        $mpdf->SetTextColor(0, 0, 0);
        $s2FontSize = 15;
        $s2LineHeight = 7;
        $mpdf->SetLeftMargin($s2TextLeft);
        $mpdf->SetRightMargin($wMm - $s2Pad);
        $s2Cell = function ($bold, $txt, $ln = 0) use ($mpdf, $s2LineHeight, $s2FontSize) {
            $mpdf->SetFont('dejavusans', $bold ? 'B' : '', $s2FontSize);
            $w = $mpdf->GetStringWidth($txt);
            $mpdf->Cell($w, $s2LineHeight, $txt, 0, $ln, 'L');
        };
        $mpdf->SetXY($s2TextLeft, $s2ParaTop);
        $s2Cell(true, 'Santiago del Estero', 0);
        $s2Cell(false, ' impulsa una ', 0);
        $s2Cell(true, 'Oferta Exportable', 1);
        $s2Cell(true, 'Provincial', 0);
        $s2Cell(false, ' para visibilizar, ordenar y promover su entramado ', 1);
        $s2Cell(true, 'productivo', 0);
        $s2Cell(false, ' ante organismos de promoción, misiones ', 1);
        $s2Cell(false, 'comerciales y compradores.', 1);
        $mpdf->Ln(9);
        $mpdf->SetX($s2TextLeft);
        $s2Cell(false, 'Esta presentación reúne ', 0);
        $s2Cell(true, 'información declarada por las', 1);
        $s2Cell(true, 'empresas registradas, con foco en productos y servicios ', 1);
        $s2Cell(true, 'exportables.', 1);
        $mpdf->Ln(9);
        $mpdf->SetX($s2TextLeft);
        $s2Cell(false, 'La iniciativa busca ', 0);
        $s2Cell(true, 'facilitar el acceso a datos clave, mejorar la', 1);
        $s2Cell(true, 'difusión institucional y habilitar oportunidades de', 1);
        $s2Cell(true, 'vinculación comercial, fortaleciendo una cultura', 1);
        $s2Cell(true, 'exportadora moderna, inclusiva y federal.', 1);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // No incrementar $i: en la siguiente iteración (i=2) se hará AddPage() para el slide 3
    } elseif ($i === 2) {
        // Chunk 2 = slide 2 ya dibujado por API en i=1; solo añadir página para el slide 3
        $mpdf->AddPage();
    } elseif ($i === 3) {
        // Slide 3: Identidad provincial — izquierda collage 5 imágenes identidad con marco rojo (3 lados); derecha blanco, L roja, logo como slide 2, IDENTIDAD PROVINCIAL y párrafo
        $mpdf->SetXY(0, 0);
        $s3Pad = 18;
        $s3CollageW = round($wMm * 0.50);
        $s3MarginV = 12;
        $s3MarginL = 12;
        $s3ContentW = $s3CollageW;
        $s3MosaicW = $s3ContentW - $s3MarginL;
        $s3MosaicH = $hMm - 2 * $s3MarginV;
        $s3LeftColW = (int) floor($s3MosaicW * 0.55);
        $s3RightColW = (int) ($s3MosaicW - $s3LeftColW);
        $s3H1 = (int) floor($s3MosaicH / 2);
        $s3H2 = $s3MosaicH - $s3H1;
        $s3R1 = (int) floor($s3MosaicH / 3);
        $s3R2 = (int) floor($s3MosaicH / 3);
        $s3R3 = $s3MosaicH - $s3R1 - $s3R2;
        $s3Boxes = [
            ['x' => 0, 'y' => 0, 'w' => $s3LeftColW, 'h' => $s3H1],
            ['x' => 0, 'y' => $s3H1, 'w' => $s3LeftColW, 'h' => $s3H2],
            ['x' => $s3LeftColW, 'y' => 0, 'w' => $s3RightColW, 'h' => $s3R1],
            ['x' => $s3LeftColW, 'y' => $s3R1, 'w' => $s3RightColW, 'h' => $s3R2],
            ['x' => $s3LeftColW, 'y' => $s3R1 + $s3R2, 'w' => $s3RightColW, 'h' => $s3R3],
        ];
        $s3PicturesRightX = (int) round($s3MarginL + $s3LeftColW + $s3RightColW);
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
            $dwPx = (int) max(1, round($boxW * $s3Scale));
            $dhPx = (int) max(1, round($boxH * $s3Scale));
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/mod_s3_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        $s3RedR = 196;
        $s3RedG = 52;
        $s3RedB = 59;
        $s3RedAlpha = 0.65;
        $s3RightX = $s3PicturesRightX;
        $s3RightW = $wMm - $s3RightX;
        $s3BgWidth = round($wMm * 0.501114);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3RightX, 0, $s3RightW, $hMm, 'F');
        if (extension_loaded('gd')) {
            $s3BgWpx = (int) max(1, round($s3BgWidth * $s3Scale));
            $s3BgHpx = (int) max(1, round($hMm * $s3Scale));
            $s3BgImg = @imagecreatetruecolor($s3BgWpx, $s3BgHpx);
            if ($s3BgImg) {
                imagealphablending($s3BgImg, false);
                imagesavealpha($s3BgImg, true);
                imagefill($s3BgImg, 0, 0, imagecolorallocatealpha($s3BgImg, 0, 0, 0, 127));
                $s3BgAlpha = (int) round(127 * (1 - $s3RedAlpha));
                $s3BgColor = imagecolorallocatealpha($s3BgImg, $s3RedR, $s3RedG, $s3RedB, $s3BgAlpha);
                imagefilledrectangle($s3BgImg, 0, 0, $s3BgWpx - 1, $s3BgHpx - 1, $s3BgColor);
                $s3BgTmp = sys_get_temp_dir() . '/moderno_s3_bg_' . uniqid() . '.png';
                if (imagepng($s3BgImg, $s3BgTmp)) {
                    $mpdf->Image($s3BgTmp, 0, 0, $s3BgWidth, $hMm);
                    @unlink($s3BgTmp);
                }
                imagedestroy($s3BgImg);
            }
        } else {
            $mpdf->SetFillColor($s3RedR, $s3RedG, $s3RedB);
            $mpdf->Rect(0, 0, $s3BgWidth, $hMm, 'F');
        }
        foreach ([0, 1, 2, 3, 4] as $idx) {
            $path = isset($identidadSlide3Paths[$idx]) ? $identidadSlide3Paths[$idx] : null;
            $box = $s3Boxes[$idx];
            $tmp = $s3LoadCrop($path, $box['w'], $box['h']);
            $bx = $box['x'] + $s3MarginL;
            $by = $box['y'] + $s3MarginV;
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $bx, $by, $box['w'], $box['h']);
                @unlink($tmp);
            } elseif ($path && file_exists($path)) {
                $mpdf->Image($path, $bx, $by, $box['w'], $box['h']);
            } else {
                $mpdf->SetFillColor(220, 220, 220);
                $mpdf->Rect($bx, $by, $box['w'], $box['h'], 'F');
            }
        }
        $s3RedBlockW = 130;
        $s3RedBlockH = 72;
        if (extension_loaded('gd')) {
            $s3TilePx = 20;
            $s3Overlay = @imagecreatetruecolor($s3TilePx, $s3TilePx);
            if ($s3Overlay) {
                imagealphablending($s3Overlay, false);
                imagesavealpha($s3Overlay, true);
                $s3Trans = imagecolorallocatealpha($s3Overlay, 0, 0, 0, 127);
                imagefill($s3Overlay, 0, 0, $s3Trans);
                $s3RedAlphaVal = (int) round(127 * (1 - $s3RedAlpha));
                $s3RedColor = imagecolorallocatealpha($s3Overlay, $s3RedR, $s3RedG, $s3RedB, $s3RedAlphaVal);
                imagefilledrectangle($s3Overlay, 0, 0, $s3TilePx - 1, $s3TilePx - 1, $s3RedColor);
                $s3RedTmp = sys_get_temp_dir() . '/moderno_s3_red_' . uniqid() . '.png';
                if (imagepng($s3Overlay, $s3RedTmp)) {
                    $mpdf->Image($s3RedTmp, $wMm - $s3RedBlockW, 0, $s3RedBlockW, $s3RedBlockH);
                    @unlink($s3RedTmp);
                }
                imagedestroy($s3Overlay);
            }
        } else {
            $mpdf->SetFillColor($s3RedR, $s3RedG, $s3RedB);
            $mpdf->Rect($wMm - $s3RedBlockW, 0, $s3RedBlockW, $s3RedBlockH, 'F');
        }
        $s3WhitePad = 20;
        $s3WhiteW = $s3RedBlockW - $s3WhitePad;
        $s3WhiteH = $s3RedBlockH - $s3WhitePad;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($wMm - $s3WhiteW, 0, $s3WhiteW, $s3WhiteH, 'F');
        $s3LogoX = $wMm - $s3WhiteW;
        $s3LogoY = 0;
        $s3LogoPath = $pdfLogoPath;
        if (file_exists($s3LogoPath)) {
            $imgSize = @getimagesize($s3LogoPath);
            $maxW = min($s3WhiteW - 4, round($s3WhiteW * 0.62));
            $maxH = min($s3WhiteH - 4, round($s3WhiteH * 0.62));
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($maxH * $r <= $maxW) {
                    $lw = $maxH * $r;
                    $lh = $maxH;
                } else {
                    $lw = $maxW;
                    $lh = $maxW / $r;
                }
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s3LogoPath, $s3LogoX + ($s3WhiteW - $lw) / 2, $s3LogoY + ($s3WhiteH - $lh) / 2, $lw, $lh);
        }
        $s3TextLeft = $s3RightX + $s3Pad;
        $s3TextW = $wMm - $s3TextLeft - $s3Pad;
        $s3TitleY = $s3RedBlockH + 52;
        $mpdf->SetTextColor($s3RedR, $s3RedG, $s3RedB);
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetXY($s3TextLeft, $s3TitleY);
        $mpdf->Cell($s3TextW, 14, 'IDENTIDAD', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetXY($s3TextLeft, $s3TitleY + 16);
        $mpdf->Cell($s3TextW, 14, 'PROVINCIAL', 0, 1, 'L');
        $s3ParaY = $s3TitleY + 16 + 14 + 22;
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->SetXY($s3TextLeft, $s3ParaY);
        $mpdf->MultiCell($s3TextW, 7, 'Un territorio con capacidad productiva diversa y proyección para la vinculación comercial.', 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3bis (nuevo): Estrategico — fondo ESTRATEGICO2.JPG + barra roja + bloque de texto blanco
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);

        $s3nBgPath = $assetsDir . '/ESTRATEGICO2.JPG';
        if ($s3nBgPath && file_exists($s3nBgPath) && extension_loaded('gd')) {
            $s3nScale = 100 / 25.4;
            $s3nDstWpx = (int) max(1, round($wMm * $s3nScale));
            $s3nDstHpx = (int) max(1, round($hMm * $s3nScale));
            $info = @getimagesize($s3nBgPath);
            $src = null;
            if ($info && !empty($info[2]) && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($s3nBgPath);
            }
            if ($src) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $targetAR = $wMm / $hMm;
                $imgAR = $sw / $sh;

                if ($imgAR >= $targetAR) {
                    $cropH = $sh;
                    $cropW = (int) round($sh * $targetAR);
                    $srcX = (int) floor(($sw - $cropW) / 2);
                    $srcY = 0;
                } else {
                    $cropW = $sw;
                    $cropH = (int) round($sw / $targetAR);
                    $srcX = 0;
                    $srcY = (int) floor(($sh - $cropH) / 2);
                }

                $dst = @imagecreatetruecolor($s3nDstWpx, $s3nDstHpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $s3nDstWpx, $s3nDstHpx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/moderno_s3n_bg_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, 0, 0, $wMm, $hMm);
                        @unlink($tmp);
                    } else {
                        $mpdf->Image($s3nBgPath, 0, 0, $wMm, $hMm);
                    }
                    imagedestroy($dst);
                } else {
                    $mpdf->Image($s3nBgPath, 0, 0, $wMm, $hMm);
                    if ($dst) imagedestroy($dst);
                }
                imagedestroy($src);
            } else {
                $mpdf->Image($s3nBgPath, 0, 0, $wMm, $hMm);
            }
        } elseif ($s3nBgPath && file_exists($s3nBgPath)) {
            // Fallback: sin recorte (por falta de GD)
            $mpdf->Image($s3nBgPath, 0, 0, $wMm, $hMm);
        } else {
            $mpdf->SetFillColor(220, 220, 220);
            $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        }

        // Barra roja (с отступами сверху/слева/справа)
        $s3nEdgePad = 18;
        $s3nRedR = 196;
        $s3nRedG = 52;
        $s3nRedB = 59;
        $s3nHeaderH = (int) round($hMm * 0.33);

        $s3nRedLeft = $s3nEdgePad;
        $s3nRedTop = $s3nEdgePad;
        $s3nRedW = $wMm - 2 * $s3nEdgePad;

        $mpdf->SetFillColor($s3nRedR, $s3nRedG, $s3nRedB);
        $mpdf->Rect($s3nRedLeft, $s3nRedTop, $s3nRedW, $s3nHeaderH, 'F');

        // Текст внутри красного блока
        $s3nInnerPadX = 10; // внутренний отступ от левого края красного блока
        $s3nPadX = $s3nRedLeft + $s3nInnerPadX;
        $s3nTitleW = $s3nRedW - 2 * $s3nInnerPadX;

        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 46);
        // Опустить заголовок внутри красного блока
        $s3nTitleY = $s3nRedTop + 25;
        $mpdf->SetXY($s3nPadX, $s3nTitleY);
        $mpdf->Cell($s3nTitleW, 16, 'SANTIAGO DEL ESTERO', 0, 1, 'L');
        $mpdf->SetXY($s3nPadX, $s3nTitleY + 18);
        $mpdf->Cell($s3nTitleW, 16, 'ESTRATÉGICO', 0, 1, 'L');

        $mpdf->SetFont('dejavusans', '', 12);
        $s3nCaptionY = $s3nRedTop + $s3nHeaderH - 14;
        $mpdf->SetXY($s3nRedLeft, $s3nCaptionY);
        $mpdf->Cell($s3nRedW, 10, 'CONECTANDO LA PRODUCCIÓN CON MERCADOS NACIONALES E INTERNACIONALES', 0, 0, 'C');

        // Белый блок: слева как красный; ширина ~45%; высота ~30%
        $s3nBoxPad = 12;
        $s3nBoxX = $s3nRedLeft;
        $s3nBoxW = (int) round($wMm * 0.45);
        // Чуть уменьшить высоту и опустить вниз
        $s3nBoxH = (int) round($hMm * 0.28);
        $s3nBoxY = $s3nRedTop + $s3nHeaderH + 40;

        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3nBoxX, $s3nBoxY, $s3nBoxW, $s3nBoxH, 'F');

        // Белый блок логотипа: те же размеры, что красный бейдж на слайде «EMPRESAS Y PRODUCTOS EXPORTABLES» ($s4LogoBadgeW×$s4LogoBadgeH)
        $s3nLogoBadgeW = 62;
        $s3nLogoBadgeH = 28;
        $s3nLogoMargin = 12;
        $s3nLogoPad = 10;
        $s3nLogoInnerPad = 8;
        $s3nLogoBadgeX = (int) round($wMm - $s3nLogoMargin - $s3nLogoPad - $s3nLogoBadgeW);
        $s3nLogoBadgeY = (int) round($hMm - $s3nLogoMargin - $s3nLogoPad - $s3nLogoBadgeH);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3nLogoBadgeX, $s3nLogoBadgeY, $s3nLogoBadgeW, $s3nLogoBadgeH, 'F');

        $s3nLogoMaxW = $s3nLogoBadgeW - $s3nLogoInnerPad;
        $s3nLogoMaxH = $s3nLogoBadgeH - $s3nLogoInnerPad;
        $s3nLogoPath = $pdfLogoPath;
        if (file_exists($s3nLogoPath)) {
            $imgSize = @getimagesize($s3nLogoPath);
            $lw = $s3nLogoMaxW;
            $lh = $s3nLogoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3nLogoMaxH * $r <= $s3nLogoMaxW) {
                    $lw = $s3nLogoMaxH * $r;
                    $lh = $s3nLogoMaxH;
                } else {
                    $lw = $s3nLogoMaxW;
                    $lh = $s3nLogoMaxW / $r;
                }
            }
            $s3nLogoX = (int) round($s3nLogoBadgeX + ($s3nLogoBadgeW - $lw) / 2);
            $s3nLogoY = (int) round($s3nLogoBadgeY + ($s3nLogoBadgeH - $lh) / 2);
            $mpdf->Image($s3nLogoPath, $s3nLogoX, $s3nLogoY, $lw, $lh);
        }

        // Текст (слева) внутри белого блока
        $s3nTextX = $s3nBoxX + $s3nBoxPad;
        // Доп. отступ вниз, чтобы текст в HTML не "прилипал" к верхней границе
        $s3nTextY = $s3nBoxY + $s3nBoxPad + 3;
        $s3nTextW = max(1, $s3nBoxW - 2 * $s3nBoxPad);

        $mpdf->SetTextColor(0, 0, 0);
        $s3nFontSize = 13;
        $mpdf->SetFont('dejavusans', '', $s3nFontSize);

        // Рендер через HTML, чтобы корректно выделять жирным конкретные фразы
        // Рисуем текст "по строкам" (без WriteHTML), чтобы позиционирование было детерминированным.
        $s3nLineH = 6.2;
        $y = $s3nTextY;
        $lines = [
            // 1
            [
                ['Santiago del Estero ocupa una ', false],
                ['posición clave en el Norte argentino', true],
            ],
            // 2
            [
                ['y se integra a ', false],
                ['ejes de conectividad', true],
            ],
            // 3
            [
                ['corredores logísticos hacia el Atlántico y el Pacífico.', true],
            ],
            // 4
            [
                ['Con ', false],
                ['infraestructura vial en expansión', true],
                [' y articulación territorial,', false],
            ],
            // 5
            [
                ['la provincia facilita el ', false],
                ['movimiento de bienes, la llegada a mercados', true],
            ],
            // 6
            [
                ['y la generación de nuevas ', false],
                ['oportunidades de inversión', true],
            ],
        ];

        $s3nExtraGapAfterLine4 = 6; // отступ сверху после 4-й строки (мм)
        foreach ($lines as $idx => $runs) {
            $x = $s3nTextX;
            foreach ($runs as $run) {
                [$txt, $isBold] = $run;
                if ($txt === '') {
                    continue;
                }
                $mpdf->SetFont('dejavusans', $isBold ? 'B' : '', $s3nFontSize);
                $w = $mpdf->GetStringWidth($txt);
                $mpdf->SetXY($x, $y);
                $mpdf->Cell($w, $s3nLineH, $txt, 0, 0, 'L');
                $x += $w;
            }
            $y += $s3nLineH;
            if ($idx === 2) {
                $y += $s3nExtraGapAfterLine4;
            }
        }

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3ter: Estructura productiva — izq. rojo (margen/anchura ajustados), imagen cuadrada encima; der. logo + grillas 2×2
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3pRedR = 196;
        $s3pRedG = 52;
        $s3pRedB = 59;
        $s3pLeftMargin = 46;
        // Под квадрат картинки: чтобы при высоте ~90% ширина была равной высоте и не "ломала" раскладку
        // Под квадрат картинки: слегка уменьшаем ширину красного блока
        $s3pRedW = (int) round($hMm * 0.60);
        $s3pSplitX = $s3pLeftMargin + $s3pRedW;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $s3pRedX = $s3pLeftMargin;
        $s3pRedY = 0;
        $s3pRedH = $hMm;
        $mpdf->SetFillColor($s3pRedR, $s3pRedG, $s3pRedB);
        $mpdf->Rect($s3pRedX, $s3pRedY, $s3pRedW, $s3pRedH, 'F');
        // Квадратная картинка больше и ближе к левому краю
        $s3pImgPad = 0; // минимум рамки, чтобы изображение было максимально крупным
        $s3pImgShiftLeft = 30; // сдвигаем сильнее влево
        // Требование: высота картинки ~70% высоты красного блока, ширина = высота (квадрат).
        // Квадратная картинка: высота 75% от высоты красного блока
        $s3pTargetImgH = (int) round($s3pRedH * 0.75);
        $s3pImgSize = (int) max(1, min($s3pRedW - 2 * $s3pImgPad, $s3pTargetImgH - 2 * $s3pImgPad));
        $s3pImgW = $s3pImgSize;
        $s3pImgH = $s3pImgSize;
        $s3pImgX = (int) round($s3pRedX + $s3pImgPad - $s3pImgShiftLeft);
        $s3pImgY = (int) round($s3pRedY + ($s3pRedH - $s3pImgH) / 2);
        $s3pPhotoPath = $assetsDir . '/Productivo4.jpg';
        $s3pImgDrawn = false;
        if (extension_loaded('gd') && $s3pPhotoPath && file_exists($s3pPhotoPath)) {
            $s3pScale = 100 / 25.4;
            $dwPx = (int) max(1, round($s3pImgW * $s3pScale));
            $dhPx = (int) max(1, round($s3pImgH * $s3pScale));
            $info = @getimagesize($s3pPhotoPath);
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($s3pPhotoPath);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($s3pPhotoPath);
            }
            if ($src) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $ratio = $s3pImgW / $s3pImgH;
                $imgR = $sw / $sh;
                if ($imgR >= $ratio) {
                    $cropW = (int) round($sh * $ratio);
                    $cropH = $sh;
                    $srcX = (int) floor(($sw - $cropW) / 2);
                    $srcY = (int) max(0, min(floor(($sh - $cropH) / 2) + round($sh * 0.04), $sh - $cropH));
                } else {
                    $cropW = $sw;
                    $cropH = (int) round($sw / $ratio);
                    $srcX = 0;
                    $srcY = (int) floor(($sh - $cropH) / 2);
                }
                $dst = @imagecreatetruecolor($dwPx, $dhPx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                    imagedestroy($src);
                    $s3pTmp = sys_get_temp_dir() . '/moderno_s3p_' . uniqid() . '.png';
                    if (imagepng($dst, $s3pTmp)) {
                        $mpdf->Image($s3pTmp, $s3pImgX, $s3pImgY, $s3pImgW, $s3pImgH);
                        $s3pImgDrawn = true;
                        @unlink($s3pTmp);
                    }
                    imagedestroy($dst);
                } else {
                    if ($dst) {
                        imagedestroy($dst);
                    }
                    imagedestroy($src);
                }
            }
        }
        if (!$s3pImgDrawn && file_exists($s3pPhotoPath)) {
            $mpdf->Image($s3pPhotoPath, $s3pImgX, $s3pImgY, $s3pImgW, $s3pImgH);
        } elseif (!$s3pImgDrawn) {
            $mpdf->SetFillColor(80, 80, 90);
            $mpdf->Rect($s3pImgX, $s3pImgY, $s3pImgW, $s3pImgH, 'F');
        }
        // Полупрозрачный черный блок поверх всей картинки
        $s3pOverlayTop = $s3pImgY;
        $s3pOverlayH = $s3pImgH;
        if (method_exists($mpdf, 'SetAlpha')) {
            // Меньше прозрачности => больше непрозрачность
            $mpdf->SetAlpha(0.55);
        }
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect($s3pImgX, $s3pOverlayTop, $s3pImgW, $s3pOverlayH, 'F');
        if (method_exists($mpdf, 'SetAlpha')) {
            $mpdf->SetAlpha(1);
        }
        $mpdf->SetTextColor(255, 255, 255);
        // Сузить колонку текста под заголовком (меньше ширина) и слегка увеличить размер шрифта
        $s3pTxtPad = 12;
        $s3pTxtW = $s3pImgW - 2 * $s3pTxtPad;
        $s3pTitleX = $s3pImgX + $s3pTxtPad;
        // Заголовок прижимаем к верхнему краю картинки с отступом
        $s3pHeadingTopInset = 10; // мм
        $s3pTitleY = $s3pImgY + $s3pHeadingTopInset;
        $mpdf->SetXY($s3pTitleX, $s3pTitleY);
        $s3pHeadingFont = 38;
        $s3pHeadingLineH = 16; // мм
        $mpdf->SetFont('dejavusans', 'B', $s3pHeadingFont);
        // Гарантированно прижимаем к правому краю: X = правый край рамки - ширина строки
        $s3pHead1 = 'ESTRUCTURA';
        $s3pHead2 = 'PRODUCTIVA Y';
        $s3pHead3 = 'SECTORES CLAVE';
        $s3pHeadY = $s3pTitleY;
        $w = $mpdf->GetStringWidth($s3pHead1);
        $mpdf->SetXY($s3pTitleX + $s3pTxtW - $w, $s3pHeadY);
        $mpdf->Cell($w, $s3pHeadingLineH, $s3pHead1, 0, 1, 'L');
        $s3pHeadY += $s3pHeadingLineH;
        $w = $mpdf->GetStringWidth($s3pHead2);
        $mpdf->SetXY($s3pTitleX + $s3pTxtW - $w, $s3pHeadY);
        $mpdf->Cell($w, $s3pHeadingLineH, $s3pHead2, 0, 1, 'L');
        $s3pHeadY += $s3pHeadingLineH;
        $w = $mpdf->GetStringWidth($s3pHead3);
        $mpdf->SetXY($s3pTitleX + $s3pTxtW - $w, $s3pHeadY);
        $mpdf->Cell($w, $s3pHeadingLineH, $s3pHead3, 0, 1, 'L');

        $s3pCap = str_replace('!', '.', 'Santiago del Estero presenta una matriz productiva diversificada basada en recursos naturales, desarrollo agroindustrial, crecimiento energético y consolidación de economías regionales con proyección nacional e internacional.');
        $s3pCapFontSize = 11;
        $s3pCapLineH = 5.2;
        $s3pCapBottomInset = 10; // мм от нижнего края картинки
        $s3pCapEstimatedLines = 4; // оценка, чтобы прижать абзац к низу
        $s3pCapY = (int) round($s3pImgY + $s3pImgH - $s3pCapBottomInset - $s3pCapEstimatedLines * $s3pCapLineH);
        $mpdf->SetFont('dejavusans', '', $s3pCapFontSize);
        $mpdf->SetXY($s3pTitleX, $s3pCapY);
        $mpdf->MultiCell($s3pTxtW, $s3pCapLineH, $s3pCap, 0, 'R');
        $s3pRightPad = 14;
        // Размер логотипа как на предыдущем слайде 3bis (внутренний размер бейджа: 54×20)
        $s3pLogoMaxW = 54;
        $s3pLogoMaxH = 20;
        if (file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $lw = $s3pLogoMaxW;
            $lh = $s3pLogoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3pLogoMaxH * $r <= $s3pLogoMaxW) {
                    $lw = $s3pLogoMaxH * $r;
                    $lh = $s3pLogoMaxH;
                } else {
                    $lw = $s3pLogoMaxW;
                    $lh = $s3pLogoMaxW / $r;
                }
            }
            $mpdf->Image($pdfLogoPath, (int) round($wMm - $s3pRightPad - $lw), $s3pRightPad, $lw, $lh);
        }
        // Чуть дальше от красного блока
        $s3pGridX = $s3pSplitX + 14;
        $s3pGridW = $wMm - $s3pGridX - 12;
        // Ещё сильнее сжать сетку: меньше зазор между колонками
        $s3pGap = 4;
        $s3pColW = ($s3pGridW - $s3pGap) / 2;
        // Сетка сдвинута вниз и ряды слегка ближе
        $s3pRow1Y = 85;
        $s3pRow2Y = 150;
        $s3pBlocks = [
            ['Agroindustria', ['Algodón', 'Maíz y soja', 'Alfalfa y forrajes', 'Producción hortícola', 'Desarrollo agroindustrial']],
            ['Ganadería', [
                'Ganadería bovina',
                'Producción caprina',
                'Producción porcina y aviar',
                'Industria frigorífica',
                // 2 строки как попросили
                'Desarrollo genético y\nsanidad animal',
            ]],
            ['Regionales', ['Producción apícola (miel)', 'Alcaparras', 'Producción forestal', 'Agricultura familiar', 'Valor agregado regional']],
            ['Desarrollo', ['Energías renovables', 'Infraestructura productiva', 'Sistemas de riego', 'Desarrollo territorial sostenible', 'Expansión industrial']],
        ];
        $mpdf->SetTextColor(0, 0, 0);
        foreach ($s3pBlocks as $bi => $block) {
            $col = $bi % 2;
            $row = (int) floor($bi / 2);
            $bx = $s3pGridX + $col * ($s3pColW + $s3pGap);
            $by = $row === 0 ? $s3pRow1Y : $s3pRow2Y;
            $mpdf->SetXY($bx, $by);
            $mpdf->SetFont('dejavusans', 'B', 18);
            $mpdf->Cell($s3pColW, 7.5, str_replace('!', '.', $block[0]), 0, 1, 'L');
            // Размер шрифта для пунктов
            $mpdf->SetFont('dejavusans', '', 15);
            // Пункты: вернуть обычный шаг внутри блока
            // Отступ сверху у пунктов внутри блока
            $bulletY = $by + 10;
            $s3pItemLineH = 8;
            $bulletPrefix = '• ';
            $bulletPrefixW = $mpdf->GetStringWidth($bulletPrefix);
            foreach ($block[1] as $item) {
                $mpdf->SetXY($bx, $bulletY);

                $item = str_replace('!', '.', $item);
                // В исходных строках перенос задан как литерал "\n" (из-за одинарных кавычек),
                // поэтому сначала приводим его к реальному символу перевода строки.
                $item = str_replace("\\n", "\n", $item);
                $parts = explode("\n", $item);

                // Если нужно принудительно 2 строки (например, "Desarrollo genético y sanidad animal")
                if (count($parts) >= 2) {
                    $first = $parts[0];
                    $second = $parts[1];

                    $mpdf->MultiCell($s3pColW, $s3pItemLineH, $bulletPrefix . $first, 0, 'L');

                    // Вторая строка без маркера, но с тем же отступом как после "• "
                    $mpdf->SetXY($bx + $bulletPrefixW, $mpdf->y);
                    $mpdf->MultiCell($s3pColW - $bulletPrefixW, $s3pItemLineH, $second, 0, 'L');

                    $bulletY = $mpdf->y;
                } else {
                    // Делаем точку частью строки текста, чтобы она визуально совпадала по центру строки
                    $mpdf->MultiCell($s3pColW, $s3pItemLineH, $bulletPrefix . $parts[0], 0, 'L');
                    $bulletY = $mpdf->y;
                }
            }
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3ter-futuro: повторяем 3ter, но с другим изображением и текстом (как на скриншоте)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s3fRedR = 196;
        $s3fRedG = 52;
        $s3fRedB = 59;
        $s3fLeftMargin = 46;
        $s3fRedW = (int) round($hMm * 0.60);
        $s3fSplitX = $s3fLeftMargin + $s3fRedW;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $s3fRedX = $s3fLeftMargin;
        $s3fRedY = 0;
        $s3fRedH = $hMm;
        $mpdf->SetFillColor($s3fRedR, $s3fRedG, $s3fRedB);
        $mpdf->Rect($s3fRedX, $s3fRedY, $s3fRedW, $s3fRedH, 'F');

        // Квадратная картинка
        $s3fImgPad = 0;
        $s3fImgShiftLeft = 30;
        $s3fTargetImgH = (int) round($s3fRedH * 0.75);
        $s3fImgSize = (int) max(1, min($s3fRedW - 2 * $s3fImgPad, $s3fTargetImgH - 2 * $s3fImgPad));
        $s3fImgW = $s3fImgSize;
        $s3fImgH = $s3fImgSize;
        $s3fImgX = (int) round($s3fRedX + $s3fImgPad - $s3fImgShiftLeft);
        $s3fImgY = (int) round($s3fRedY + ($s3fRedH - $s3fImgH) / 2);

        $s3fPhotoPath = $assetsDir . '/EDUCACION_TECNOLOGIA.jpg';
        $s3fImgDrawn = false;
        if (extension_loaded('gd') && $s3fPhotoPath && file_exists($s3fPhotoPath)) {
            // Для avif GD может не поддерживать ресайз/кроп. В этом случае сработает fallback mPDF.
            $mpdf->Image($s3fPhotoPath, $s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH);
            $s3fImgDrawn = true;
        }
        if (!$s3fImgDrawn && file_exists($s3fPhotoPath)) {
            $mpdf->Image($s3fPhotoPath, $s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH);
        } elseif (!$s3fImgDrawn) {
            $mpdf->SetFillColor(80, 80, 90);
            $mpdf->Rect($s3fImgX, $s3fImgY, $s3fImgW, $s3fImgH, 'F');
        }

        // Черный полупрозрачный слой поверх картинки
        $s3fOverlayTop = $s3fImgY;
        $s3fOverlayH = $s3fImgH;
        if (method_exists($mpdf, 'SetAlpha')) {
            $mpdf->SetAlpha(0.55);
        }
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect($s3fImgX, $s3fOverlayTop, $s3fImgW, $s3fOverlayH, 'F');
        if (method_exists($mpdf, 'SetAlpha')) {
            $mpdf->SetAlpha(1);
        }

        $mpdf->SetTextColor(255, 255, 255);
        $s3fTxtPad = 12;
        $s3fTxtW = $s3fImgW - 2 * $s3fTxtPad;
        $s3fTitleX = $s3fImgX + $s3fTxtPad;
        $s3fHeadingTopInset = 10;
        $s3fTitleY = $s3fImgY + $s3fHeadingTopInset;

        // Заголовок на картинке (как на 3ter)
        $mpdf->SetXY($s3fTitleX, $s3fTitleY);
        $s3fHeadingFont = 38;
        $s3fHeadingLineH = 16;
        $mpdf->SetFont('dejavusans', 'B', $s3fHeadingFont);
        $s3fHead1 = 'INNOVACIÓN Y';
        $s3fHead2 = 'FUTURO';
        $s3fHead3 = 'PRODUCTIVO';
        $w = $mpdf->GetStringWidth($s3fHead1);
        $mpdf->SetXY($s3fTitleX + $s3fTxtW - $w, $s3fTitleY);
        $mpdf->Cell($w, $s3fHeadingLineH, $s3fHead1, 0, 1, 'L');
        $s3fTitleY += $s3fHeadingLineH;
        $w = $mpdf->GetStringWidth($s3fHead2);
        $mpdf->SetXY($s3fTitleX + $s3fTxtW - $w, $s3fTitleY);
        $mpdf->Cell($w, $s3fHeadingLineH, $s3fHead2, 0, 1, 'L');
        $s3fTitleY += $s3fHeadingLineH;
        $w = $mpdf->GetStringWidth($s3fHead3);
        $mpdf->SetXY($s3fTitleX + $s3fTxtW - $w, $s3fTitleY);
        $mpdf->Cell($w, $s3fHeadingLineH, $s3fHead3, 0, 1, 'L');

        // Подпись (как на 3ter)
        $s3fCap = str_replace('!', '.', 'Santiago del Estero presenta una matriz productiva diversificada basada en recursos naturales, desarrollo agroindustrial, crecimiento energético y consolidación de economías regionales con proyección nacional e internacional.');
        $s3fCapBottomInset = 10;
        $s3fCapEstimatedLines = 4;
        $s3fCapLineH = 5.2;
        $s3fCapY = (int) round($s3fImgY + $s3fImgH - $s3fCapBottomInset - $s3fCapEstimatedLines * $s3fCapLineH);
        $mpdf->SetFont('dejavusans', '', 11);
        $mpdf->SetXY($s3fTitleX, $s3fCapY);
        $mpdf->MultiCell($s3fTxtW, $s3fCapLineH, $s3fCap, 0, 'R');

        // Лого (как на 3ter)
        $s3fRightPad = 14;
        $s3fLogoMaxW = 54;
        $s3fLogoMaxH = 20;
        if (file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $lw = $s3fLogoMaxW;
            $lh = $s3fLogoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s3fLogoMaxH * $r <= $s3fLogoMaxW) {
                    $lw = $s3fLogoMaxH * $r;
                    $lh = $s3fLogoMaxH;
                } else {
                    $lw = $s3fLogoMaxW;
                    $lh = $s3fLogoMaxW / $r;
                }
            }
            $mpdf->Image($pdfLogoPath, (int) round($wMm - $s3fRightPad - $lw), $s3fRightPad, $lw, $lh);
        }

        // Справа: текст как на скриншоте (2 колонки × 3 строки)
        // Сдвинуть всю таблицу правее
        $s3fGridX = $s3fSplitX + 30;
        $s3fGridW = $wMm - $s3fGridX - 12;
        // Сблизить 1-ю колонку со 2-й
        $s3fGap = 1;
        $s3fColW = ($s3fGridW - $s3fGap) / 2;
        $s3fRowYs = [85, 115, 145];
        $s3fLeftTitles = [
            'Educación',
            'Economía del conocimiento',
            'Transformación digital',
        ];
        $s3fRightTitles = [
            'Desarrollo',
            'Formación Profesional',
            'Tecnología',
        ];
        $mpdf->SetTextColor(0, 0, 0);
        // Увеличить шрифт заголовков с пунктами
        $mpdf->SetFont('dejavusans', 'B', 17);

        $bulletPrefix = '• ';
        $bulletPrefixW = $mpdf->GetStringWidth($bulletPrefix);
        $renderTwoLineTitle = function (string $x, int $y, string $text) use ($mpdf, $s3fColW, $bulletPrefix, $bulletPrefixW) {
            $parts = [$text];
            if ($text === 'Economía del conocimiento') {
                $parts = ['Economía del', 'conocimiento'];
            } elseif ($text === 'Formación Profesional') {
                $parts = ['Formación', 'Profesional'];
            } elseif ($text === 'Transformación digital') {
                $parts = ['Transformación', 'digital'];
            }

            $mpdf->SetXY($x, $y);
            $mpdf->MultiCell($s3fColW, 9, $bulletPrefix . $parts[0], 0, 'L');
            if (count($parts) >= 2) {
                $mpdf->SetXY($x + $bulletPrefixW, $mpdf->y);
                $mpdf->MultiCell($s3fColW - $bulletPrefixW, 9, $parts[1], 0, 'L');
            }
        };

        foreach ($s3fLeftTitles as $idx => $t) {
            $by = $s3fRowYs[$idx] ?? $s3fRowYs[count($s3fRowYs) - 1];
            $renderTwoLineTitle($s3fGridX, $by, $t);

            $rt = $s3fRightTitles[$idx] ?? '';
            $renderTwoLineTitle($s3fGridX + $s3fColW + $s3fGap, $by, $rt);
        }

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3ter-turismo: como en el screenshot (izq. texto + der. foto + logo abajo a la derecha)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');

        $tX = 60;
        $tY = 20;
        $tW = 330;
        $tH = 213;
        $tRedR = 196;
        $tRedG = 52;
        $tRedB = 59;
        $mpdf->SetFillColor($tRedR, $tRedG, $tRedB);
        $mpdf->Rect($tX, $tY, $tW, $tH, 'F');

        // Franjas rojas semitransparentes sobre blanco, a ambos lados del panel rojo
        $tStripeW = $wMm * 0.042;
        $tStripeH = $hMm * 0.30;
        $tStripeX = $tX + $tW;
        // 20 mm desde el borde inferior del panel rojo hasta el borde inferior de la franja
        $tStripeY = ($tY + $tH) - 20 - $tStripeH;
        $mpdf->SetAlpha(0.42, 'Normal');
        $mpdf->SetFillColor($tRedR, $tRedG, $tRedB);
        $mpdf->Rect($tStripeX, $tStripeY, $tStripeW, $tStripeH, 'F');
        // Franja izquierda: arriba, 20 mm bajo el borde superior del panel rojo
        $tStripeLeftX = $tX - $tStripeW;
        $tStripeLeftY = $tY + 20;
        $mpdf->Rect($tStripeLeftX, $tStripeLeftY, $tStripeW, $tStripeH, 'F');
        $mpdf->SetAlpha(1, 'Normal');

        // Mismo margen que foto grande respecto al borde rojo derecho: 10 mm
        $tContentMarginH = (int) round($tW - 175 - 145);
        $tTextX = $tX + $tContentMarginH;
        $tTextY = $tY + 40;
        // Ancho texto = ancho imagen pequeña
        $smallImgW = 120;
        $smallImgH = 72;
        $tTitleW = $smallImgW;
        $tParaW = $smallImgW;
        $tTitleLineH = 11;

        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 24);

        // Tras Cell(..., 0, 1) mPDF pone X al margen izquierdo de la página — hay que volver a $tTextX
        $ty = $tTextY;
        $mpdf->SetXY($tTextX, $ty);
        $mpdf->Cell($tTitleW, $tTitleLineH, 'TURISMO COMO', 0, 1, 'L');
        $ty += $tTitleLineH;
        $mpdf->SetXY($tTextX, $ty);
        $mpdf->Cell($tTitleW, $tTitleLineH, 'MOTOR ECONÓMICO', 0, 1, 'L');
        $ty += $tTitleLineH;

        $mpdf->SetFont('dejavusans', '', 18);
        $tSubY = $ty + 5;
        $tSubLineH = 8;
        $mpdf->SetXY($tTextX, $tSubY);
        $mpdf->Cell($tParaW, $tSubLineH, 'Turismo y Desarrollo', 0, 1, 'L');
        $mpdf->SetXY($tTextX, $tSubY + $tSubLineH);
        $mpdf->Cell($tParaW, $tSubLineH, 'Territorial', 0, 1, 'L');

        // Línea bajo el subtítulo (mPDF no tiene GetY — считаем Y вручную)
        $mpdf->SetLineWidth(1);
        $tLineY = $tSubY + ($tSubLineH * 2) + 4;
        $mpdf->SetDrawColor(255, 255, 255);
        $mpdf->Line($tTextX, $tLineY, $tTextX + $smallImgW, $tLineY);

        $mpdf->SetLineWidth(0.2);
        $mpdf->SetFont('dejavusans', '', 13);
        $tParaY = $tLineY + 7;
        $mpdf->SetXY($tTextX, $tParaY);
        $tPara = 'El turismo se consolida como uno de los motores estratégicos de diversificación económica provincial, '
            . 'integrando naturaleza, cultura, deporte y bienestar.';
        $mpdf->MultiCell($tParaW, 5.8, $tPara, 0, 'L');

        // Imagen pequeña: pegada al borde inferior del bloque rojo
        $smallImgMarginLeft = $tContentMarginH;
        $smallImgMarginBottom = 0;
        $smallImgX = (int) round($tX + $smallImgMarginLeft);
        $smallImgY = (int) round($tY + $tH - $smallImgMarginBottom - $smallImgH);
        $smallImgPath = $assetsDir . '/GASTRONOMIA_ARTESANIA.jpg';
        if (file_exists($smallImgPath)) {
            // Cover-crop: слот от (smallImgX,Y) до низа красного блока — без внутреннего отступа снизу
            $cX = $smallImgX;
            $cY = $smallImgY;
            $cW = $smallImgW;
            $cH = $smallImgH;
            if (extension_loaded('gd')) {
                $info = @getimagesize($smallImgPath);
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($smallImgPath);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($smallImgPath);
                } else {
                    $src = false;
                }
                if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $srcR = $sw / $sh;
                    $dstR = $cW / $cH;
                    // Кроп с приоритетом верхней части кадра
                    if ($srcR >= $dstR) {
                        $cropHtop = (int) round($sw / $dstR);
                        if ($cropHtop <= $sh) {
                            $cropW = $sw;
                            $cropH = $cropHtop;
                            $srcX = 0;
                            $srcY = 0;
                        } else {
                            $cropH = $sh;
                            $cropW = (int) round($sh * $dstR);
                            $srcX = (int) floor(($sw - $cropW) / 2);
                            $srcY = 0;
                        }
                    } else {
                        $cropW = $sw;
                        $cropH = (int) round($sw / $dstR);
                        $srcX = 0;
                        $srcY = 0;
                    }
                    $dstPxW = max(1, (int) round($cW * (100 / 25.4)));
                    $dstPxH = max(1, (int) round($cH * (100 / 25.4)));
                    $dst = @imagecreatetruecolor($dstPxW, $dstPxH);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstPxW, $dstPxH, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/moderno_crop_s3f_' . uniqid() . '.png';
                        if (imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $cX, $cY, $cW, $cH);
                            @unlink($tmp);
                        }
                        imagedestroy($dst);
                    } else {
                        imagedestroy($dst);
                        $mpdf->Image($smallImgPath, $cX, $cY, $cW, $cH);
                    }
                    imagedestroy($src);
                } else {
                    $mpdf->Image($smallImgPath, $cX, $cY, $cW, $cH);
                }
            } else {
                // Fallback: если GD недоступен
                $mpdf->Image($smallImgPath, $cX, $cY, $cW, $cH);
            }
        }

        // Foto grande derecha
        $photoX = $tX + 175;
        $photoW = 145;
        $photoH = 160;
        // Прижать к нижнему краю + чуть поднять фото и блок лого вместе
        $photoLiftUp = 12; // мм вверх
        $photoY = (int) round($tY + $tH - 12 - $photoH - $photoLiftUp);
        $photoPath = $assetsDir . '/MOTOR_ECONOMICO.jpg';
        if (file_exists($photoPath)) {
            if (extension_loaded('gd')) {
                $info = @getimagesize($photoPath);
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($photoPath);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($photoPath);
                }
                if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $srcR = $sw / $sh;
                    $dstR = $photoW / $photoH;
                    if ($srcR >= $dstR) {
                        $cropH = $sh;
                        $cropW = (int) round($sh * $dstR);
                        $srcX = (int) floor(($sw - $cropW) / 2);
                        $srcY = 0;
                    } else {
                        $cropW = $sw;
                        $cropH = (int) round($sw / $dstR);
                        $srcX = 0;
                        $srcY = (int) floor(($sh - $cropH) / 2);
                    }
                    $dstPxW = max(1, (int) round($photoW * (100 / 25.4)));
                    $dstPxH = max(1, (int) round($photoH * (100 / 25.4)));
                    $dst = @imagecreatetruecolor($dstPxW, $dstPxH);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstPxW, $dstPxH, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/moderno_crop_s3f2_' . uniqid() . '.png';
                        if (imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $photoX, $photoY, $photoW, $photoH);
                            @unlink($tmp);
                        }
                        imagedestroy($dst);
                    } else {
                        imagedestroy($dst);
                        $mpdf->Image($photoPath, $photoX, $photoY, $photoW, $photoH);
                    }
                    imagedestroy($src);
                } else {
                    $mpdf->Image($photoPath, $photoX, $photoY, $photoW, $photoH);
                }
            } else {
                $mpdf->Image($photoPath, $photoX, $photoY, $photoW, $photoH);
            }
        }

        // Logo abajo-derecha dentro de la foto
        $logoBoxW = 52;
        $logoBoxH = 22;
        // Отступы от правого/нижнего края красного блока такие же, как у большой картинки
        // (выравниваем внешний край белого бокса с внешним краем фото)
        $logoBoxX = $photoX + $photoW - $logoBoxW;
        $logoBoxY = $photoY + $photoH - $logoBoxH;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($logoBoxX, $logoBoxY, $logoBoxW, $logoBoxH, 'F');
        $logoMaxW = $logoBoxW - 8;
        $logoMaxH = $logoBoxH - 8;
        if (!empty($pdfLogoPath) && file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $lw = $logoMaxW;
            $lh = $logoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($logoMaxH * $r <= $logoMaxW) {
                    $lw = $logoMaxH * $r;
                    $lh = $logoMaxH;
                } else {
                    $lw = $logoMaxW;
                    $lh = $logoMaxW / $r;
                }
            }
            $mpdf->Image($pdfLogoPath, $logoBoxX + 4, $logoBoxY + 4, $lw, $lh);
        }

        // Slide turismo 3 columnas: termas / estadio / autódromo (zigzag texto–foto)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $stLogoPad = 14;
        $stLogoMaxH = 15;
        $stLogoMaxW = 58;
        if (!empty($pdfLogoPath) && file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $slw = $stLogoMaxW;
            $slh = $stLogoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $lr = $imgSize[0] / $imgSize[1];
                if ($stLogoMaxH * $lr <= $stLogoMaxW) {
                    $slw = $stLogoMaxH * $lr;
                    $slh = $stLogoMaxH;
                } else {
                    $slw = $stLogoMaxW;
                    $slh = $stLogoMaxW / $lr;
                }
            }
            $mpdf->Image($pdfLogoPath, (int) round($wMm - $stLogoPad - $slw), $stLogoPad, $slw, $slh);
        }
        $stPad = 13;
        $stGap = 7;
        $stContentT = 36;
        $stContentH = $hMm - $stContentT - 14;
        $stColW = ($wMm - 2 * $stPad - 2 * $stGap) / 3;
        $stX1 = $stPad;
        $stX2 = $stPad + $stColW + $stGap;
        $stX3 = $stPad + 2 * ($stColW + $stGap);
        $stHdrH = 86;
        $stCol2TextDrop = 16;
        $stColSideTextDrop = 12;
        // Imágenes cuadradas, más grandes, centradas en cada columna
        $stImgSq = min($stColW - 6, 96);
        $stImgInset = ($stColW - $stImgSq) / 2;
        $stStackH = max(
            $stColSideTextDrop + $stHdrH + $stGap + $stImgSq,
            $stImgSq + $stGap + $stCol2TextDrop + $stHdrH
        );
        $stY0 = $stContentT + max(0, ($stContentH - $stStackH) / 2);
        $stRedR = 196;
        $stRedG = 52;
        $stRedB = 59;
        $stScale = 100 / 25.4;

        // Solo recorte tipo «object-fit: cover» — nunca estirar la foto al tamaño del hueco
        $stDrawCoverImg = static function ($path, $bx, $by, $bw, $bh) use ($mpdf, $stScale) {
            if (!is_string($path) || !file_exists($path) || !extension_loaded('gd')) {
                return;
            }
            $src = @imagecreatefromstring((string) @file_get_contents($path));
            if (!$src) {
                $info = @getimagesize($path);
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($path);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($path);
                } elseif ($info && defined('IMAGETYPE_WEBP') && $info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($path);
                }
            }
            if (!$src) {
                return;
            }
            $sw = imagesx($src);
            $sh = imagesy($src);
            if ($sw < 1 || $sh < 1) {
                imagedestroy($src);

                return;
            }
            $dstR = $bw / $bh;
            $srcR = $sw / $sh;
            if ($srcR >= $dstR) {
                $cropH = $sh;
                $cropW = (int) max(1, min($sw, round($sh * $dstR)));
                $sx = (int) floor(($sw - $cropW) / 2);
                $sy = 0;
            } else {
                $cropW = $sw;
                $cropH = (int) max(1, min($sh, round($sw / $dstR)));
                $sx = 0;
                $sy = (int) floor(($sh - $cropH) / 2);
            }
            $pxW = max(1, (int) round($bw * $stScale));
            $pxH = max(1, (int) round($bh * $stScale));
            $dst = @imagecreatetruecolor($pxW, $pxH);
            if (!$dst) {
                imagedestroy($src);

                return;
            }
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $ok = @imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $pxW, $pxH, $cropW, $cropH);
            imagedestroy($src);
            if ($ok) {
                $tmp = sys_get_temp_dir() . '/moderno_st3_' . uniqid('', true) . '.png';
                if (imagepng($dst, $tmp, 6)) {
                    $mpdf->Image($tmp, $bx, $by, $bw, $bh);
                    @unlink($tmp);
                }
            }
            imagedestroy($dst);
        };

        $stRenderHeader = static function ($cx, $y, $colW, $numStr, $ttl, $sub) use ($mpdf, $stRedR, $stRedG, $stRedB) {
            $numW = 26;
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', '', 40);
            $mpdf->SetXY($cx, $y);
            $mpdf->Cell($numW, 14, $numStr, 0, 0, 'L');
            $rx = $cx + $numW + 2;
            $rw = $colW - $numW - 2;
            $rh = 30;
            $mpdf->SetFillColor($stRedR, $stRedG, $stRedB);
            $mpdf->Rect($rx, $y, $rw, $rh, 'F');
            $redPadX = 3;
            $redPadTop = 2.5;
            $redPadBot = 2.5;
            $innerW = max(8, $rw - 2 * $redPadX);
            $innerH = $rh - $redPadTop - $redPadBot;
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', 18);
            $lineTextH = 6.8;
            $lineGap = 2.2;
            $words = preg_split('/\s+/u', trim((string) $ttl), -1, PREG_SPLIT_NO_EMPTY);
            $lines = [];
            $line = '';
            foreach ($words as $w) {
                $test = $line === '' ? $w : $line . ' ' . $w;
                if ($mpdf->GetStringWidth($test) <= $innerW - 0.5) {
                    $line = $test;
                } else {
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                    $line = $w;
                }
            }
            if ($line !== '') {
                $lines[] = $line;
            }
            if ($lines === []) {
                $lines[] = $ttl;
            }
            $nL = count($lines);
            $blockH = $nL * $lineTextH + max(0, $nL - 1) * $lineGap;
            $ty = $y + $redPadTop + max(0, ($innerH - $blockH) / 2);
            $cy = $ty;
            foreach ($lines as $i => $ln) {
                $mpdf->SetXY($rx + $redPadX, $cy);
                $mpdf->Cell($innerW, $lineTextH, $ln, 0, 0, 'C');
                $cy += $lineTextH + ($i < $nL - 1 ? $lineGap : 0);
            }
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', '', 15);
            // Subtítulo alineado al ancho del bloque rojo, centrado bajo el título
            $stSubMarginTop = 7;
            $mpdf->SetXY($rx, $y + $rh + $stSubMarginTop);
            $mpdf->MultiCell($rw, 5, $sub, 0, 'C');
        };

        $stDrawCoverImg($assetsDir . '/Turismo_termal.jpg', $stX1 + $stImgInset, $stY0 + $stColSideTextDrop + $stHdrH + $stGap, $stImgSq, $stImgSq);
        $stRenderHeader($stX1, $stY0 + $stColSideTextDrop, $stColW, '01', 'Termas de Río Hondo', 'Turismo termal internacional');

        $stDrawCoverImg($assetsDir . '/Estadio_Unico.jpeg', $stX2 + $stImgInset, $stY0, $stImgSq, $stImgSq);
        $stRenderHeader($stX2, $stY0 + $stImgSq + $stGap + $stCol2TextDrop, $stColW, '02', 'Estadio Único Madre de Ciudades', 'Eventos y espectáculos');

        $stDrawCoverImg($assetsDir . '/Autodromo_Internacional.jpg', $stX3 + $stImgInset, $stY0 + $stColSideTextDrop + $stHdrH + $stGap, $stImgSq, $stImgSq);
        $stRenderHeader($stX3, $stY0 + $stColSideTextDrop, $stColW, '03', 'Autódromo Internacional', 'Turismo deportivo');

        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide turismo 3 columnas (2): ciudad histórica / parque / cultura — mismo layout
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        if (!empty($pdfLogoPath) && file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $slw = $stLogoMaxW;
            $slh = $stLogoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $lr = $imgSize[0] / $imgSize[1];
                if ($stLogoMaxH * $lr <= $stLogoMaxW) {
                    $slw = $stLogoMaxH * $lr;
                    $slh = $stLogoMaxH;
                } else {
                    $slw = $stLogoMaxW;
                    $slh = $stLogoMaxW / $lr;
                }
            }
            $mpdf->Image($pdfLogoPath, (int) round($wMm - $stLogoPad - $slw), $stLogoPad, $slw, $slh);
        }
        $stDrawCoverImg($assetsDir . '/CIUDAD_HISTORICA2.jpg', $stX1 + $stImgInset, $stY0 + $stColSideTextDrop + $stHdrH + $stGap, $stImgSq, $stImgSq);
        $stRenderHeader($stX1, $stY0 + $stColSideTextDrop, $stColW, '04', 'Ciudad Histórica', 'Patrimonio cultural');
        $stDrawCoverImg($assetsDir . '/parque_ashpa_kausay.png', $stX2 + $stImgInset, $stY0, $stImgSq, $stImgSq);
        $stRenderHeader($stX2, $stY0 + $stImgSq + $stGap + $stCol2TextDrop, $stColW, '05', 'Naturaleza y Ecoturismo', 'Experiencias naturales');
        $stDrawCoverImg($assetsDir . '/Identidad_santiaguena.jpg', $stX3 + $stImgInset, $stY0 + $stColSideTextDrop + $stHdrH + $stGap, $stImgSq, $stImgSq);
        $stRenderHeader($stX3, $stY0 + $stColSideTextDrop, $stColW, '06', 'Cultura y Tradición', 'Identidad santiagueña');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

        // Slide 3quater-cultura: mismo layout que turismo; textos e imágenes cultura
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $mpdf->SetFillColor($tRedR, $tRedG, $tRedB);
        $mpdf->Rect($tX, $tY, $tW, $tH, 'F');
        $mpdf->SetAlpha(0.42, 'Normal');
        $mpdf->SetFillColor($tRedR, $tRedG, $tRedB);
        $mpdf->Rect($tStripeX, $tStripeY, $tStripeW, $tStripeH, 'F');
        $mpdf->Rect($tStripeLeftX, $tStripeLeftY, $tStripeW, $tStripeH, 'F');
        $mpdf->SetAlpha(1, 'Normal');

        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 24);
        $ty = $tTextY;
        $mpdf->SetXY($tTextX, $ty);
        $mpdf->Cell($tTitleW, $tTitleLineH, 'CULTURA E', 0, 1, 'L');
        $ty += $tTitleLineH;
        $mpdf->SetXY($tTextX, $ty);
        $mpdf->Cell($tTitleW, $tTitleLineH, 'IDENTIDAD', 0, 1, 'L');
        $ty += $tTitleLineH;
        $mpdf->SetFont('dejavusans', '', 18);
        $tSubY = $ty + 5;
        $mpdf->SetXY($tTextX, $tSubY);
        $mpdf->Cell($tParaW, 9, 'Identidad Cultural y Patrimonio', 0, 1, 'L');
        $mpdf->SetLineWidth(1);
        $tLineY = $tSubY + 9 + 4;
        $mpdf->SetDrawColor(255, 255, 255);
        $mpdf->Line($tTextX, $tLineY, $tTextX + $smallImgW, $tLineY);
        $mpdf->SetLineWidth(0.2);
        $mpdf->SetFont('dejavusans', '', 13);
        $tParaY = $tLineY + 7;
        $mpdf->SetXY($tTextX, $tParaY);
        $tParaCult = 'Santiago del Estero, Madre de Ciudades, preserva un patrimonio cultural vivo que articula '
            . 'tradición, música, gastronomía y expresiones artísticas como parte de su posicionamiento territorial.';
        $mpdf->MultiCell($tParaW, 5.8, $tParaCult, 0, 'L');

        $smallImgPathC = $assetsDir . '/Identidad_Cultural.jpg';
        if (file_exists($smallImgPathC)) {
            $cX = $smallImgX;
            $cY = $smallImgY;
            $cW = $smallImgW;
            $cH = $smallImgH;
            if (extension_loaded('gd')) {
                $info = @getimagesize($smallImgPathC);
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($smallImgPathC);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($smallImgPathC);
                } else {
                    $src = false;
                }
                if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $srcR = $sw / $sh;
                    $dstR = $cW / $cH;
                    if ($srcR >= $dstR) {
                        $cropHtop = (int) round($sw / $dstR);
                        if ($cropHtop <= $sh) {
                            $cropW = $sw;
                            $cropH = $cropHtop;
                            $srcXC = 0;
                            $srcYC = 0;
                        } else {
                            $cropH = $sh;
                            $cropW = (int) round($sh * $dstR);
                            $srcXC = (int) floor(($sw - $cropW) / 2);
                            $srcYC = 0;
                        }
                    } else {
                        $cropW = $sw;
                        $cropH = (int) round($sw / $dstR);
                        $srcXC = 0;
                        $srcYC = 0;
                    }
                    $dstPxW = max(1, (int) round($cW * (100 / 25.4)));
                    $dstPxH = max(1, (int) round($cH * (100 / 25.4)));
                    $dst = @imagecreatetruecolor($dstPxW, $dstPxH);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcXC, $srcYC, $dstPxW, $dstPxH, $cropW, $cropH)) {
                        $tmpC = sys_get_temp_dir() . '/moderno_crop_s3cul_sm_' . uniqid() . '.png';
                        if (imagepng($dst, $tmpC)) {
                            $mpdf->Image($tmpC, $cX, $cY, $cW, $cH);
                            @unlink($tmpC);
                        }
                        imagedestroy($dst);
                    } else {
                        imagedestroy($dst);
                        $mpdf->Image($smallImgPathC, $cX, $cY, $cW, $cH);
                    }
                    imagedestroy($src);
                } else {
                    $mpdf->Image($smallImgPathC, $cX, $cY, $cW, $cH);
                }
            } else {
                $mpdf->Image($smallImgPathC, $cX, $cY, $cW, $cH);
            }
        }

        $photoPathC = $assetsDir . '/CULTURA_IDENTIDAD.jpg';
        if (file_exists($photoPathC)) {
            if (extension_loaded('gd')) {
                $info = @getimagesize($photoPathC);
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($photoPathC);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($photoPathC);
                }
                if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $srcR = $sw / $sh;
                    $dstR = $photoW / $photoH;
                    if ($srcR >= $dstR) {
                        $cropH = $sh;
                        $cropW = (int) round($sh * $dstR);
                        $srcXC = (int) floor(($sw - $cropW) / 2);
                        $srcYC = 0;
                    } else {
                        $cropW = $sw;
                        $cropH = (int) round($sw / $dstR);
                        $srcXC = 0;
                        $srcYC = (int) floor(($sh - $cropH) / 2);
                    }
                    $dstPxW = max(1, (int) round($photoW * (100 / 25.4)));
                    $dstPxH = max(1, (int) round($photoH * (100 / 25.4)));
                    $dst = @imagecreatetruecolor($dstPxW, $dstPxH);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcXC, $srcYC, $dstPxW, $dstPxH, $cropW, $cropH)) {
                        $tmpC = sys_get_temp_dir() . '/moderno_crop_s3cul_lg_' . uniqid() . '.png';
                        if (imagepng($dst, $tmpC)) {
                            $mpdf->Image($tmpC, $photoX, $photoY, $photoW, $photoH);
                            @unlink($tmpC);
                        }
                        imagedestroy($dst);
                    } else {
                        imagedestroy($dst);
                        $mpdf->Image($photoPathC, $photoX, $photoY, $photoW, $photoH);
                    }
                    imagedestroy($src);
                } else {
                    $mpdf->Image($photoPathC, $photoX, $photoY, $photoW, $photoH);
                }
            } else {
                $mpdf->Image($photoPathC, $photoX, $photoY, $photoW, $photoH);
            }
        }

        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($logoBoxX, $logoBoxY, $logoBoxW, $logoBoxH, 'F');
        if (!empty($pdfLogoPath) && file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $lw = $logoMaxW;
            $lh = $logoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($logoMaxH * $r <= $logoMaxW) {
                    $lw = $logoMaxH * $r;
                    $lh = $logoMaxH;
                } else {
                    $lw = $logoMaxW;
                    $lh = $logoMaxW / $r;
                }
            }
            $mpdf->Image($pdfLogoPath, $logoBoxX + 4, $logoBoxY + 4, $lw, $lh);
        }

        // Slide folklore / artesanía / gastronomía / patrimonio (panel rojo + 2 fotos)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $folLogoPad = 14;
        $folLogoMaxH = 15;
        $folLogoMaxW = 58;
        if (!empty($pdfLogoPath) && file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $flw = $folLogoMaxW;
            $flh = $folLogoMaxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $flr = $imgSize[0] / $imgSize[1];
                if ($folLogoMaxH * $flr <= $folLogoMaxW) {
                    $flw = $folLogoMaxH * $flr;
                    $flh = $folLogoMaxH;
                } else {
                    $flw = $folLogoMaxW;
                    $flh = $folLogoMaxW / $flr;
                }
            }
            $mpdf->Image($pdfLogoPath, (int) round($wMm - $folLogoPad - $flw), $folLogoPad, $flw, $flh);
        }
        $folRedX = 20;
        $folRedW = 168;
        $folRedBottomMargin = 20;
        $folRedH = 178;
        $folRedY = $hMm - $folRedBottomMargin - $folRedH;
        $folRedR = 196;
        $folRedG = 52;
        $folRedB = 59;
        $mpdf->SetFillColor($folRedR, $folRedG, $folRedB);
        $mpdf->Rect($folRedX, $folRedY, $folRedW, $folRedH, 'F');
        $folTx = $folRedX + 12;
        $folNumW = 22;
        $folSubW = $folRedW - 24 - $folNumW;
        $folSubMarginTop = 4;
        $folTitleH = 8.5;
        $folSubLineH = 5.8;
        $folTopPad = 14;
        $folBottomPad = 14;
        $folSections = [
            ['01.', 'Folklore', 'Música, danza y tradición popular.'],
            ['02.', 'Artesanía', 'Saberes ancestrales y producción regional.'],
            ['03.', 'Gastronomía', "Sabores tradicionales e identidad local.\nFiestas Populares.\nCelebraciones y encuentros culturales."],
            ['04.', 'Patrimonio Histórico', 'Espacios y memoria cultural provincial.'],
        ];
        $mpdf->SetFont('dejavusans', '', 12.5);
        $folSectionData = [];
        foreach ($folSections as $folSec) {
            $subText = trim((string) $folSec[2]);
            if (strpos($subText, "\n") !== false) {
                $lines = array_values(array_filter(array_map('trim', explode("\n", $subText))));
            } else {
                $words = preg_split('/\s+/u', $subText, -1, PREG_SPLIT_NO_EMPTY);
                $lines = [];
                $ln = '';
                foreach ($words as $w) {
                    $test = $ln === '' ? $w : $ln . ' ' . $w;
                    if ($mpdf->GetStringWidth($test) <= $folSubW - 1) {
                        $ln = $test;
                    } else {
                        if ($ln !== '') {
                            $lines[] = $ln;
                        }
                        $ln = $w;
                    }
                }
                if ($ln !== '') {
                    $lines[] = $ln;
                }
            }
            $folSectionData[] = [$folSec[0], $folSec[1], $lines];
        }
        $folContentH = 0;
        foreach ($folSectionData as $sd) {
            $folContentH += $folTitleH + $folSubMarginTop + count($sd[2]) * $folSubLineH;
        }
        $folGapCount = count($folSectionData) - 1;
        $folAvailableH = $folRedH - $folTopPad - $folBottomPad;
        $folGap = $folGapCount > 0 ? max(3, ($folAvailableH - $folContentH) / $folGapCount) : 0;
        $mpdf->SetTextColor(255, 255, 255);
        $folTy = $folRedY + $folTopPad;
        foreach ($folSectionData as $sd) {
            $mpdf->SetFont('dejavusans', 'B', 18);
            $mpdf->SetXY($folTx, $folTy);
            $mpdf->Cell($folNumW, $folTitleH, $sd[0], 0, 0, 'L');
            $mpdf->Cell($folRedW - 24 - $folNumW, $folTitleH, $sd[1], 0, 0, 'L');
            $folTy += $folTitleH + $folSubMarginTop;
            $mpdf->SetFont('dejavusans', '', 12.5);
            foreach ($sd[2] as $lineStr) {
                $mpdf->SetXY($folTx + $folNumW, $folTy);
                $mpdf->Cell($folSubW, $folSubLineH, $lineStr, 0, 0, 'L');
                $folTy += $folSubLineH;
            }
            $folTy += $folGap;
        }
        $folScale = 100 / 25.4;
        $folDrawCover = static function ($path, $bx, $by, $bw, $bh, $cropBiasX = 0) use ($mpdf, $folScale) {
            if (!is_string($path) || !file_exists($path) || !extension_loaded('gd')) {
                return;
            }
            $src = @imagecreatefromstring((string) @file_get_contents($path));
            if (!$src) {
                $info = @getimagesize($path);
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($path);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($path);
                }
            }
            if (!$src) {
                return;
            }
            $sw = imagesx($src);
            $sh = imagesy($src);
            if ($sw < 1 || $sh < 1) {
                imagedestroy($src);

                return;
            }
            $dstR = $bw / $bh;
            $srcR = $sw / $sh;
            if ($srcR >= $dstR) {
                $cropH = $sh;
                $cropW = (int) max(1, min($sw, round($sh * $dstR)));
                $sx = (int) floor(($sw - $cropW) / 2 + ($sw - $cropW) * (float) $cropBiasX);
                $sx = max(0, min($sw - $cropW, $sx));
                $sy = 0;
            } else {
                $cropW = $sw;
                $cropH = (int) max(1, min($sh, round($sw / $dstR)));
                $sx = 0;
                $sy = (int) floor(($sh - $cropH) / 2);
            }
            $pxW = max(1, (int) round($bw * $folScale));
            $pxH = max(1, (int) round($bh * $folScale));
            $dst = @imagecreatetruecolor($pxW, $pxH);
            if (!$dst) {
                imagedestroy($src);

                return;
            }
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            if (@imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $pxW, $pxH, $cropW, $cropH)) {
                $tmp = sys_get_temp_dir() . '/moderno_fol_' . uniqid('', true) . '.png';
                if (imagepng($dst, $tmp, 6)) {
                    $mpdf->Image($tmp, $bx, $by, $bw, $bh);
                    @unlink($tmp);
                }
            }
            imagedestroy($dst);
            imagedestroy($src);
        };
        $folOverlap = 26;
        $folMidW = 110;
        $folMidY = $folRedY - 10;
        $folMidH = min(172, $hMm - $folMidY - 2);
        $folMidX = $folRedX + $folRedW - $folOverlap;
        $folDrawCover($assetsDir . '/memoria_cultural.jpg', $folMidX, $folMidY, $folMidW, $folMidH, 0.18);
        $folRightW = 114;
        $folRightH = 106;
        $folRightPad = 16;
        $folRightX = $wMm - $folRightPad - $folRightW;
        $folRightY = $hMm - $folRedBottomMargin - $folRightH;
        $folDrawCover($assetsDir . '/danza_tradicion.jpg', $folRightX, $folRightY, $folRightW, $folRightH);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

    } elseif ($i === 4) {
        // Slide 4: imagen 85% altura; encima rojo semi-transparente con borde inferior en V (ángulo al centro); bloque blanco con el mismo borde en V; logo como slide 1; título y subtítulo centrados
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s4Pad = 18;
        $s4ImgH = round($hMm * 0.85);
        $s4Scale = 100 / 25.4;
        $s4Wpx = (int) max(1, round($wMm * $s4Scale));
        $s4ImgPxH = (int) max(1, round($s4ImgH * $s4Scale));
        $s4FullPxH = (int) max(1, round($hMm * $s4Scale));
        $s4RedR = 196;
        $s4RedG = 52;
        $s4RedB = 59;
        $s4RedAlpha = 0.65;
        // Blanco arriba hasta la V; rojo abajo; encima del rojo, bloque blanco con el mismo V y menor altura
        $s4BoundaryVYApex = round($hMm * 0.52);
        $s4BoundaryVYSide = round($hMm * 0.64);
        $s4WhiteOnRedBottomApex = round($hMm * 0.58);
        $s4WhiteOnRedBottomSide = round($hMm * 0.70);
        $s4BgPath = isset($empresaSlide4Paths[0]) ? $empresaSlide4Paths[0] : (isset($empresaCandidates[0]) ? $empresaCandidates[0] : null);
        $s4ImgDrawn = false;
        if (extension_loaded('gd') && $s4BgPath && file_exists($s4BgPath)) {
            $info = @getimagesize($s4BgPath);
            $ext = strtolower(pathinfo($s4BgPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s4BgPath);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s4BgPath);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s4BgPath);
            if ($src) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $ratio = $s4Wpx / $s4ImgPxH;
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
                $dst = @imagecreatetruecolor($s4Wpx, $s4ImgPxH);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $s4Wpx, $s4ImgPxH, $cropW, $cropH)) {
                    imagedestroy($src);
                    $s4TmpImg = sys_get_temp_dir() . '/moderno_s4img_' . uniqid() . '.png';
                    if (imagepng($dst, $s4TmpImg)) {
                        $mpdf->Image($s4TmpImg, 0, 0, $wMm, $s4ImgH);
                        $s4ImgDrawn = true;
                    }
                    @unlink($s4TmpImg);
                    imagedestroy($dst);
                } else {
                    if ($dst) imagedestroy($dst);
                    imagedestroy($src);
                }
            }
        }
        if (!$s4ImgDrawn) {
            $mpdf->SetFillColor(220, 220, 220);
            $mpdf->Rect(0, 0, $wMm, $s4ImgH, 'F');
        }
        $s4Smooth = 4;
        $s4OverlayW = $s4Wpx * $s4Smooth;
        $s4OverlayH = $s4FullPxH * $s4Smooth;
        $s4BoundaryVYApexPx = (int) round($s4BoundaryVYApex * $s4Scale) * $s4Smooth;
        $s4BoundaryVYSidePx = (int) round($s4BoundaryVYSide * $s4Scale) * $s4Smooth;
        $s4WhiteOnRedBottomApexPx = (int) round($s4WhiteOnRedBottomApex * $s4Scale) * $s4Smooth;
        $s4WhiteOnRedBottomSidePx = (int) round($s4WhiteOnRedBottomSide * $s4Scale) * $s4Smooth;
        $s4RightPx = $s4OverlayW - 1;
        $s4BottomPx = $s4OverlayH - 1;
        if (extension_loaded('gd')) {
            $s4Overlay = @imagecreatetruecolor($s4OverlayW, $s4OverlayH);
            if ($s4Overlay) {
                if (function_exists('imageantialias')) {
                    imageantialias($s4Overlay, true);
                }
                imagealphablending($s4Overlay, false);
                imagesavealpha($s4Overlay, true);
                imagefill($s4Overlay, 0, 0, imagecolorallocatealpha($s4Overlay, 0, 0, 0, 127));
                $white = imagecolorallocatealpha($s4Overlay, 255, 255, 255, 0);
                $s4Alpha = (int) round(127 * (1 - $s4RedAlpha));
                $red = imagecolorallocatealpha($s4Overlay, $s4RedR, $s4RedG, $s4RedB, $s4Alpha);
                $s4BottomPoly = [0, $s4BoundaryVYSidePx, (int)($s4OverlayW / 2), $s4BoundaryVYApexPx, $s4RightPx, $s4BoundaryVYSidePx, $s4RightPx, $s4BottomPx, 0, $s4BottomPx];
                imagefilledpolygon($s4Overlay, $s4BottomPoly, 5, $white);
                $s4RedBandPoly = [0, $s4BoundaryVYSidePx, (int)($s4OverlayW / 2), $s4BoundaryVYApexPx, $s4RightPx, $s4BoundaryVYSidePx, $s4RightPx, $s4WhiteOnRedBottomSidePx, (int)($s4OverlayW / 2), $s4WhiteOnRedBottomApexPx, 0, $s4WhiteOnRedBottomSidePx];
                imagefilledpolygon($s4Overlay, $s4RedBandPoly, 6, $red);
                $s4OverlayPath = sys_get_temp_dir() . '/moderno_s4over_' . uniqid() . '.png';
                if (imagepng($s4Overlay, $s4OverlayPath)) {
                    $mpdf->Image($s4OverlayPath, 0, 0, $wMm, $hMm);
                    @unlink($s4OverlayPath);
                }
                imagedestroy($s4Overlay);
            }
        } else {
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, $s4BoundaryVYSide, $wMm, $hMm - $s4BoundaryVYSide, 'F');
        }
        $s4LogoBadgeW = 62;
        $s4LogoBadgeH = 28;
        $s4LogoMargin = 12;
        $s4LogoPad = 10;
        $s4LogoBadgeX = $wMm - $s4LogoMargin - $s4LogoPad - $s4LogoBadgeW;
        $s4LogoBadgeY = $s4LogoMargin + $s4LogoPad;
        $mpdf->SetFillColor(196, 52, 59);
        $mpdf->Rect($s4LogoBadgeX, $s4LogoBadgeY, $s4LogoBadgeW, $s4LogoBadgeH, 'F');
        $s4LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s4LogoPath)) {
            $imgSize = @getimagesize($s4LogoPath);
            $maxW = $s4LogoBadgeW - 8;
            $maxH = $s4LogoBadgeH - 8;
            $lw = $maxW;
            $lh = $maxH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($maxH * $r <= $maxW) {
                    $lw = $maxH * $r;
                    $lh = $maxH;
                } else {
                    $lw = $maxW;
                    $lh = $maxW / $r;
                }
            }
            $mpdf->Image($s4LogoPath, $s4LogoBadgeX + ($s4LogoBadgeW - $lw) / 2, $s4LogoBadgeY + ($s4LogoBadgeH - $lh) / 2, $lw, $lh);
        }
        $s4TitleY = round($hMm * 0.70);
        $s4TitleLine1 = 'EMPRESAS Y PRODUCTOS';
        $s4TitleLine2 = 'EXPORTABLES';
        $s4Subtitle = 'Empresas registradas y productos/servicios exportables declarados para su difusión institucional.';
        $mpdf->SetTextColor($s4RedR, $s4RedG, $s4RedB);
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetXY(0, $s4TitleY);
        $mpdf->Cell($wMm, 12, $s4TitleLine1, 0, 1, 'C');
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetXY(0, $s4TitleY + 14);
        $mpdf->Cell($wMm, 10, $s4TitleLine2, 0, 1, 'C');
        $mpdf->SetTextColor(50, 50, 50);
        $mpdf->SetFont('dejavusans', '', 16);
        $mpdf->SetXY($s4Pad, $s4TitleY + 32);
        $mpdf->MultiCell($wMm - 2 * $s4Pad, 6, $s4Subtitle, 0, 'C');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Una slide por empresa: fondo blanco; barra roja vertical izquierda (ancha, semi-transparente); izq logo + título "NOMBRE DE" / nombre + 5 bloques; derecha solo imagen (recortada, proporcional)
        $pageNum = 5;
        $s5RedR = 196;
        $s5RedG = 52;
        $s5RedB = 59;
        foreach ($companies as $emp) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $cid = (int) $emp['id'];
            $s5RedBarW = 14;
            $s5Pad = 18;
            $s5LeftZoneW = round($wMm * 0.62);
            $s5RightZoneX = $s5RedBarW + $s5LeftZoneW;
            $s5RightZoneW = $wMm - $s5RightZoneX;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
            if (method_exists($mpdf, 'SetAlpha')) {
                $mpdf->SetAlpha(0.5);
            }
            $mpdf->SetFillColor($s5RedR, $s5RedG, $s5RedB);
            $mpdf->Rect(0, 0, $s5RedBarW, $hMm, 'F');
            if (method_exists($mpdf, 'SetAlpha')) {
                $mpdf->SetAlpha(1);
            }
            $s5LogoX = $s5RedBarW + $s5Pad;
            $s5LogoY = 14;
            $s5LogoPath = $pdfLogoPath;
            $s5LogoW = 38;
            $s5LogoH = 18;
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
                    $mpdf->Image($s5LogoPath, $s5LogoX, $s5LogoY, $lw, $lh);
                }
            }
            $s5TitleY = 58;
            $nombreEmpresa = $emp['name'] ?? '';
            $nombreUpper = function_exists('mb_strtoupper') ? mb_strtoupper($nombreEmpresa) : strtoupper($nombreEmpresa);
            $s5NameW = $s5LeftZoneW - 2 * $s5Pad;
            $mpdf->SetTextColor($s5RedR, $s5RedG, $s5RedB);
            $mpdf->SetFont('dejavusans', 'B', 40);
            $mpdf->SetXY($s5RedBarW + $s5Pad, $s5TitleY);
            $mpdf->MultiCell($s5NameW, 14, $nombreUpper, 0, 'L');
            $s5BlockY = 95;
            $s5Cols = 2;
            $s5ColW = ($s5LeftZoneW - 2 * $s5Pad - 8) / $s5Cols;
            $s5RowHeights = [42, 56, 64];
            $s5NumW = 12;
            $s5LabelLineH = 7;
            $s5Rows = [
                ['01', "ACTIVIDAD\nPRINCIPAL", $emp['main_activity'] ?? '-'],
                ['02', 'LOCALIDAD', $localidadPorEmpresa[$cid] ?? '-'],
                ['03', 'SITIO WEB', $emp['website'] ?? '-'],
                ['04', 'REDES', isset($redesPorEmpresa[$cid]) ? implode("\n", $redesPorEmpresa[$cid]) : '-'],
                ['05', 'CONTACTO', $contactoSlidePorEmpresa[$cid] ?? '-'],
            ];
            foreach ($s5Rows as $idx => $row) {
                $col = $idx % $s5Cols;
                $rowIdx = (int) floor($idx / $s5Cols);
                $by = $s5BlockY + array_sum(array_slice($s5RowHeights, 0, $rowIdx));
                $blockH = $s5RowHeights[$rowIdx];
                $bx = $s5RedBarW + $s5Pad + $col * ($s5ColW + 8);
                $mpdf->SetTextColor($s5RedR, $s5RedG, $s5RedB);
                $mpdf->SetFont('dejavusans', 'B', 15);
                $mpdf->SetXY($bx, $by);
                $mpdf->Cell($s5NumW, 7, $row[0], 0, 0, 'L');
                $mpdf->SetTextColor(0, 0, 0);
                $mpdf->SetFont('dejavusans', 'B', 18);
                $mpdf->SetXY($bx + $s5NumW, $by);
                $mpdf->MultiCell($s5ColW - $s5NumW, $s5LabelLineH, $row[1], 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $valStr = is_string($row[2]) ? $row[2] : (string)$row[2];
                $mpdf->SetXY($bx, $by + 26);
                if ($row[0] === '04' && $valStr !== '-') {
                    $s5RedesY = $by + 26;
                    $s5RedesLineH = 5;
                    $s5RedesLines = explode("\n", $valStr);
                    foreach ($s5RedesLines as $s5Line) {
                        $s5Line = trim($s5Line);
                        if ($s5Line === '') continue;
                        $s5ColonPos = strpos($s5Line, ':');
                        if ($s5ColonPos !== false) {
                            $s5Label = trim(substr($s5Line, 0, $s5ColonPos + 1));
                            $s5Val = trim(substr($s5Line, $s5ColonPos + 1));
                            if (preg_match('#^/(.+)$#', $s5Val, $m)) {
                                $s5Val = '@' . $m[1];
                            }
                            $mpdf->SetXY($bx, $s5RedesY);
                            $mpdf->SetFont('dejavusans', 'B', 12);
                            $mpdf->Cell($s5ColW, $s5RedesLineH, $s5Label, 0, 1, 'L');
                            $s5RedesY = $mpdf->y + 1;
                            $mpdf->SetXY($bx, $s5RedesY);
                            $mpdf->SetFont('dejavusans', '', 12);
                            $mpdf->MultiCell($s5ColW, $s5RedesLineH, $s5Val !== '' ? $s5Val : '-', 0, 'L');
                            $s5RedesY = $mpdf->y + 2;
                        } else {
                            $mpdf->SetXY($bx, $s5RedesY);
                            $mpdf->MultiCell($s5ColW, $s5RedesLineH, $s5Line, 0, 'L');
                            $s5RedesY = $mpdf->y + 2;
                        }
                    }
                } else {
                    $mpdf->MultiCell($s5ColW, 5, $valStr, 0, 'L');
                }
            }
            $s5RedBlockInset = 108;
            $s5RedBlockX = $s5RightZoneX + $s5RedBlockInset;
            $s5RedBlockW = $s5RightZoneW - $s5RedBlockInset;
            $s5RedBlockTop = 32;
            $s5RedBlockBottom = 16;
            $s5RedBlockY = $s5RedBlockTop;
            $s5RedBlockH = $hMm - $s5RedBlockTop - $s5RedBlockBottom;
            $mpdf->SetFillColor($s5RedR, $s5RedG, $s5RedB);
            $mpdf->Rect($s5RedBlockX, $s5RedBlockY, $s5RedBlockW, $s5RedBlockH, 'F');
            $s5ImgPadV = 18;
            // Imagen de empresa a la derecha: cuadrada, más grande y centrada en vertical.
            $s5ImgSide = min($s5RedBlockH - 2 * $s5ImgPadV, $s5RightZoneW + 24);
            $s5ImgW = $s5ImgSide;
            $s5ImgH = $s5ImgSide;
            $s5ImgX = $s5RedBlockX - (0.78 * $s5ImgW);
            $s5ImgY = $s5RedBlockY + ($s5RedBlockH - $s5ImgH) / 2;
            $compImgPath = $logosPorEmpresa[$cid] ?? $imagenesPorEmpresa[$cid] ?? null;
            if ($compImgPath && file_exists($compImgPath)) {
                $renderCroppedImage($compImgPath, $s5ImgX, $s5ImgY, $s5ImgW, $s5ImgH);
            } else {
                $mpdf->SetFillColor(220, 220, 220);
                $mpdf->Rect($s5ImgX, $s5ImgY, $s5ImgW, $s5ImgH, 'F');
            }
            $pageNum++;
        }
    } elseif ($i === 5) {
        // Slides Productos: uno por cada 3 productos/servicios; mismo diseño (logo, título, dos imágenes Producto al azar, tres columnas)
        $productoSlidesChunks = array_chunk($productosParaSlides, 3);
        $p6CompanyNameById = [];
        foreach ($companies as $c) {
            $p6CompanyNameById[(int) $c['id']] = $c['name'] ?? '';
        }
        $p6RedR = 196;
        $p6RedG = 52;
        $p6RedB = 59;
        $p6Scale = 100 / 25.4;
        foreach ($productoSlidesChunks as $p6PageIdx => $p6Chunk) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $mpdf->SetLeftMargin(0);
            $mpdf->SetRightMargin(0);
            $p6Pad = 20;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $p6LogoW = 52;
        $p6LogoH = 26;
        $p6LogoX = $wMm - $p6Pad - $p6LogoW;
        $p6LogoY = $p6Pad;
        $p6LogoPath = $pdfLogoPath;
        if (file_exists($p6LogoPath)) {
            $imgSize = @getimagesize($p6LogoPath);
            $maxW = $p6LogoW - 4;
            $maxH = $p6LogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($maxH * $r <= $maxW) {
                    $lw = $maxH * $r;
                    $lh = $maxH;
                } else {
                    $lw = $maxW;
                    $lh = $maxW / $r;
                }
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($p6LogoPath, $p6LogoX + ($p6LogoW - $lw) / 2, $p6LogoY + ($p6LogoH - $lh) / 2, $lw, $lh);
        }
        $p6TitleY = $p6LogoY + $p6LogoH + 6;
        $mpdf->SetTextColor($p6RedR, $p6RedG, $p6RedB);
        $mpdf->SetFont('dejavusans', 'B', 30);
        $mpdf->SetXY($p6Pad, $p6TitleY);
        $mpdf->Cell($wMm - 2 * $p6Pad, 12, 'PRODUCTOS Y SERVICIOS DESTACADOS', 0, 1, 'L');
        $p6ImgGap = 2;
        $p6ContentW = $wMm - 2 * $p6Pad - $p6ImgGap;
        $p6ImgLeftW = round($p6ContentW * 0.48);
        $p6ImgRightW = $p6ContentW - $p6ImgLeftW;
        $p6ColBlockH = 34;
        $p6ColTop = $hMm - $p6Pad - $p6ColBlockH;
        $p6ImgGapToCols = 10;
        $p6ImgLeftY = $p6TitleY + 32;
        $p6ImgRightY = $p6TitleY + 48;
        $p6AvailableLeftH = $p6ColTop - $p6ImgLeftY - $p6ImgGapToCols;
        $p6AvailableRightH = $p6ColTop - $p6ImgRightY - $p6ImgGapToCols;
        $p6ImgLeftH = round($p6AvailableLeftH * 0.88);
        $p6ImgRightH = round($p6AvailableRightH * 0.68);
        $p6StartX = $p6Pad;
        $p6ProductoPool = array_values($productoImgCandidates);
        shuffle($p6ProductoPool);
        $p6ImgPaths = [
            isset($p6ProductoPool[0]) ? $p6ProductoPool[0] : null,
            isset($p6ProductoPool[1]) ? $p6ProductoPool[1] : null,
        ];
        foreach ([0, 1] as $p6idx) {
            $path = $p6ImgPaths[$p6idx];
            if ($p6idx === 0) {
                $p6x = $p6StartX;
                $p6BorderW = 3;
                $p6WhiteMargin = 2.5;
                $mpdf->SetDrawColor($p6RedR, $p6RedG, $p6RedB);
                $mpdf->SetLineWidth($p6BorderW);
                $mpdf->Rect($p6x, $p6ImgLeftY, $p6ImgLeftW, $p6ImgLeftH, 'D');
                $mpdf->SetFillColor(255, 255, 255);
                $mpdf->Rect($p6x + $p6BorderW, $p6ImgLeftY + $p6BorderW, $p6ImgLeftW - 2 * $p6BorderW, $p6ImgLeftH - 2 * $p6BorderW, 'F');
                $p6innerX = $p6x + $p6BorderW + $p6WhiteMargin;
                $p6innerY = $p6ImgLeftY + $p6BorderW + $p6WhiteMargin;
                $p6innerW = $p6ImgLeftW - 2 * $p6BorderW - 2 * $p6WhiteMargin;
                $p6innerH = $p6ImgLeftH - 2 * $p6BorderW - 2 * $p6WhiteMargin;
                if ($path && file_exists($path)) {
                    $renderCroppedImage($path, $p6innerX, $p6innerY, $p6innerW, $p6innerH);
                } else {
                    $mpdf->SetFillColor(240, 240, 240);
                    $mpdf->Rect($p6innerX, $p6innerY, $p6innerW, $p6innerH, 'F');
                }
            } else {
                $p6x = $p6StartX + $p6ImgLeftW + $p6ImgGap;
                if ($path && file_exists($path)) {
                    $renderCroppedImage($path, $p6x, $p6ImgRightY, $p6ImgRightW, $p6ImgRightH);
                } else {
                    $mpdf->SetFillColor(240, 240, 240);
                    $mpdf->Rect($p6x, $p6ImgRightY, $p6ImgRightW, $p6ImgRightH, 'F');
                }
            }
        }
        $p6ColMargin = $p6Pad;
        $p6ColMarginRight = $p6ColMargin;
        $p6ColGap = 18;
        $p6N = 3;
        $p6ContentColW = $wMm - $p6ColMargin - $p6ColMarginRight - ($p6N - 1) * $p6ColGap;
        $p6ColW = $p6ContentColW / $p6N;
        while (count($p6Chunk) < 3) {
            $p6Chunk[] = null;
        }
        foreach ($p6Chunk as $k => $prod) {
            if ($k === 0) {
                $p6colX = $p6ColMargin;
            } elseif ($k === 1) {
                $p6colX = $wMm / 2 - $p6ColW / 2;
            } else {
                $p6colX = $wMm - $p6ColMarginRight - $p6ColW;
            }
            $p6numStr = sprintf('%02d', $p6PageIdx * 3 + $k + 1) . '. Producto/Servicio';
            $mpdf->SetTextColor($p6RedR, $p6RedG, $p6RedB);
            $mpdf->SetFont('dejavusans', 'B', 14);
            $mpdf->SetXY($p6colX, $p6ColTop);
            $mpdf->Cell($p6ColW, 7, $p6numStr, 0, 1, 'L');
            $p6DataY = $p6ColTop + 9;
            $mpdf->SetTextColor(0, 0, 0);
            $p6LineH = 5;
            $mpdf->SetFont('dejavusans', 'B', 12);
            $p6LblEmpresa = 'EMPRESA: ';
            $p6LblExport = 'EXPORTACIÓN ANUAL: ';
            $p6LblCert = 'CERTIFICACIONES: ';
            $p6LblTariff = 'CÓDIGO ARANCELARIO (NCM/HS): ';
            $p6WEmpresa = $mpdf->GetStringWidth($p6LblEmpresa);
            $p6WExport = $mpdf->GetStringWidth($p6LblExport);
            $p6WCert = $mpdf->GetStringWidth($p6LblCert);
            $p6WTariff = $mpdf->GetStringWidth($p6LblTariff);
            $mpdf->SetFont('dejavusans', '', 12);
            if ($prod) {
                $p6EmpresaNombre = $p6CompanyNameById[(int) ($prod['company_id'] ?? 0)] ?? '-';
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WEmpresa, $p6LineH, $p6LblEmpresa, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WEmpresa, $p6LineH, $p6EmpresaNombre ?: '-', 0, 'L');
                $p6DataY = $mpdf->y + 1;
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WExport, $p6LineH, $p6LblExport, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WExport, $p6LineH, trim($prod['annual_export'] ?? '') ?: '-', 0, 'L');
                $p6DataY = $mpdf->y + 1;
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WCert, $p6LineH, $p6LblCert, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WCert, $p6LineH, trim($prod['certifications'] ?? '') ?: '-', 0, 'L');
                $p6DataY = $mpdf->y + 1;
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WTariff, $p6LineH, $p6LblTariff, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WTariff, $p6LineH, trim($prod['tariff_code'] ?? '') ?: '-', 0, 'L');
            } else {
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WEmpresa, $p6LineH, $p6LblEmpresa, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WEmpresa, $p6LineH, '-', 0, 'L');
                $p6DataY = $mpdf->y + 1;
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WExport, $p6LineH, $p6LblExport, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WExport, $p6LineH, '-', 0, 'L');
                $p6DataY = $mpdf->y + 1;
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WCert, $p6LineH, $p6LblCert, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WCert, $p6LineH, '-', 0, 'L');
                $p6DataY = $mpdf->y + 1;
                $mpdf->SetXY($p6colX, $p6DataY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($p6WTariff, $p6LineH, $p6LblTariff, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->MultiCell($p6ColW - $p6WTariff, $p6LineH, '-', 0, 'L');
            }
        }
        $mpdf->SetDrawColor(0, 0, 0);
        }
    } elseif ($i === 6) {
        // Slide Contacto: fondo = imagen portada aleatoria a página completa; encima rojo con borde inferior en V (como slide 4); encima blanco con borde inferior en V; izq = logo + nombre provincia + CONTACTO + 3 cajas
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s7FullW = $wMm;
        $s7FullH = $hMm;
        $s7Pad = 22;
        $s7RedR = 196;
        $s7RedG = 52;
        $s7RedB = 59;
        $s7RedAlpha = 1.0;
        $s7LeftW = round($s7FullW * 0.38);
        $s7PortadaPath = ($backgroundContactPath && file_exists($backgroundContactPath)) ? $backgroundContactPath : (!empty($portadaCandidates) ? $portadaCandidates[array_rand($portadaCandidates)] : null);
        $s7Scale = 100 / 25.4;
        $s7Wpx = (int) max(1, round($s7FullW * $s7Scale));
        $s7Hpx = (int) max(1, round($s7FullH * $s7Scale));
        $s7BgDrawn = false;
        if ($s7PortadaPath && file_exists($s7PortadaPath) && extension_loaded('gd')) {
            $info = @getimagesize($s7PortadaPath);
            $ext = strtolower(pathinfo($s7PortadaPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($s7PortadaPath);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($s7PortadaPath);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($s7PortadaPath);
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $dst = @imagecreatetruecolor($s7Wpx, $s7Hpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, 0, 0, $s7Wpx, $s7Hpx, $info[0], $info[1])) {
                    $tmp = sys_get_temp_dir() . '/moderno_s7bg_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, 0, 0, $s7FullW, $s7FullH);
                        @unlink($tmp);
                        $s7BgDrawn = true;
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        }
        if (empty($s7BgDrawn)) {
            if ($s7PortadaPath && file_exists($s7PortadaPath)) {
                $mpdf->Image($s7PortadaPath, 0, 0, $s7FullW, $s7FullH);
            } else {
                $mpdf->SetFillColor(220, 220, 220);
                $mpdf->Rect(0, 0, $s7FullW, $s7FullH, 'F');
            }
        }
        $s7WhiteVApexMm = round($s7FullH * 0.24);
        $s7RedVApexMm = round($s7FullH * 0.29);
        $s7RedVSideMm = round($s7FullH * 0.90);
        $s7WhiteVSideMm = round($s7FullH * 0.85);
        $s7Smooth = 4;
        $s7OverlayW = $s7Wpx * $s7Smooth;
        $s7OverlayH = $s7Hpx * $s7Smooth;
        $s7WhiteVApexPx = (int) round($s7WhiteVApexMm * $s7Scale) * $s7Smooth;
        $s7RedVApexPx = (int) round($s7RedVApexMm * $s7Scale) * $s7Smooth;
        $s7RedVSidePx = (int) round($s7RedVSideMm * $s7Scale) * $s7Smooth;
        $s7WhiteVSidePx = (int) round($s7WhiteVSideMm * $s7Scale) * $s7Smooth;
        $s7RightPx = $s7OverlayW - 1;
        $s7WhiteApexX = (int)($s7OverlayW * 0.96);
        $s7RedApexX = (int)($s7OverlayW * 0.95);
        if (extension_loaded('gd')) {
            $s7Overlay = @imagecreatetruecolor($s7OverlayW, $s7OverlayH);
            if ($s7Overlay) {
                if (function_exists('imageantialias')) imageantialias($s7Overlay, true);
                imagealphablending($s7Overlay, false);
                imagesavealpha($s7Overlay, true);
                imagefill($s7Overlay, 0, 0, imagecolorallocatealpha($s7Overlay, 0, 0, 0, 127));
                $white = imagecolorallocatealpha($s7Overlay, 255, 255, 255, 0);
                $s7Alpha = (int) round(127 * (1 - $s7RedAlpha));
                $red = imagecolorallocatealpha($s7Overlay, $s7RedR, $s7RedG, $s7RedB, $s7Alpha);
                $s7RedPoly = [0, 0, $s7RightPx, 0, $s7RightPx, $s7RedVSidePx, $s7RedApexX, $s7RedVApexPx, 0, $s7RedVSidePx];
                imagefilledpolygon($s7Overlay, $s7RedPoly, 5, $red);
                $s7WhitePoly = [0, 0, $s7RightPx, 0, $s7RightPx, $s7WhiteVSidePx, $s7WhiteApexX, $s7WhiteVApexPx, 0, $s7WhiteVSidePx];
                imagefilledpolygon($s7Overlay, $s7WhitePoly, 5, $white);
                $s7OverlayPath = sys_get_temp_dir() . '/moderno_s7over_' . uniqid() . '.png';
                if (imagepng($s7Overlay, $s7OverlayPath)) {
                    $mpdf->Image($s7OverlayPath, 0, 0, $s7FullW, $s7FullH);
                    @unlink($s7OverlayPath);
                }
                imagedestroy($s7Overlay);
            }
        } else {
            $mpdf->SetFillColor($s7RedR, $s7RedG, $s7RedB);
            $mpdf->Rect(0, 0, $s7FullW, $s7RedVSideMm, 'F');
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, 0, $s7FullW, $s7WhiteVSideMm, 'F');
        }
        $s7BarH = 28;
        $s7BarPadR = 14;
        $s7BarPadB = 14;
        $s7SdeBarW = 64;
        $s7CfiBarW = 54;
        $s7BarGap = 4;
        $s7CfiDarkR = 32;
        $s7CfiDarkG = 38;
        $s7CfiDarkB = 48;
        $s7SdeBarX = $s7FullW - $s7BarPadR - $s7SdeBarW;
        $s7CfiBarX = $s7SdeBarX - $s7BarGap - $s7CfiBarW;
        $s7BarY = $s7FullH - $s7BarPadB - $s7BarH;
        $s7CfiImgPath = (file_exists($pdfLogoCfiWhitePath)) ? $pdfLogoCfiWhitePath : $pdfLogoCfiPath;
        if (file_exists($s7CfiImgPath)) {
            $mpdf->SetFillColor($s7CfiDarkR, $s7CfiDarkG, $s7CfiDarkB);
            $mpdf->Rect($s7CfiBarX, $s7BarY, $s7CfiBarW, $s7BarH, 'F');
            $imgSizeCfi = @getimagesize($s7CfiImgPath);
            $maxCfiInW = $s7CfiBarW - 8;
            $maxCfiInH = $s7BarH - 8;
            if (!empty($imgSizeCfi[0]) && !empty($imgSizeCfi[1])) {
                $rc = $imgSizeCfi[0] / $imgSizeCfi[1];
                if ($maxCfiInH * $rc <= $maxCfiInW) {
                    $cfiLw = $maxCfiInH * $rc;
                    $cfiLh = $maxCfiInH;
                } else {
                    $cfiLw = $maxCfiInW;
                    $cfiLh = $maxCfiInW / $rc;
                }
                $mpdf->Image($s7CfiImgPath, $s7CfiBarX + ($s7CfiBarW - $cfiLw) / 2, $s7BarY + ($s7BarH - $cfiLh) / 2, $cfiLw, $cfiLh);
            }
        } else {
            $s7SdeBarX = $s7FullW - $s7BarPadR - $s7SdeBarW;
        }
        $mpdf->SetFillColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->Rect($s7SdeBarX, $s7BarY, $s7SdeBarW, $s7BarH, 'F');
        $s7LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s7LogoPath)) {
            $imgSize = @getimagesize($s7LogoPath);
            $maxW = $s7SdeBarW - 8;
            $maxH = $s7BarH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($maxH * $r <= $maxW) {
                    $lw = $maxH * $r;
                    $lh = $maxH;
                } else {
                    $lw = $maxW;
                    $lh = $maxW / $r;
                }
                $mpdf->Image($s7LogoPath, $s7SdeBarX + ($s7SdeBarW - $lw) / 2, $s7BarY + ($s7BarH - $lh) / 2, $lw, $lh);
            }
        }
        $s7TitleY = $s7Pad + 12;
        $mpdf->SetTextColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetFont('dejavusans', 'B', 54);
        $mpdf->SetXY($s7Pad, $s7TitleY);
        $mpdf->Cell($s7LeftW - 2 * $s7Pad, 16, 'CONTACTO', 0, 1, 'L');
        $contacto = $configInstitucional;
        $s7TelRaw = trim($contacto['telefono'] ?? '') ?: '-';
        $s7WebRaw = trim($contacto['sitio_web'] ?? '') ?: '-';
        if (preg_match('#^https?://#i', $s7WebRaw)) {
            $s7WebRaw = preg_replace('#^https?://#i', '', $s7WebRaw);
        }
        $s7MailRaw = trim($contacto['mail'] ?? '') ?: '-';
        $s7BoxTop = $s7TitleY + 46;
        $s7BoxW = ($s7LeftW - 2 * $s7Pad - 12) / 2;
        $s7BoxH = 36;
        $s7BoxPad = 6;
        $s7InnerW = $s7BoxW - 2 * $s7BoxPad;
        $mpdf->SetFillColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetDrawColor(255, 255, 255);
        $mpdf->SetLineWidth(0.8);
        $mpdf->Rect($s7Pad, $s7BoxTop, $s7BoxW, $s7BoxH, 'FD');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 11);
        $mpdf->SetXY($s7Pad + $s7BoxPad, $s7BoxTop + $s7BoxPad);
        $mpdf->Cell($s7InnerW, 5, 'Teléfonos', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', '', 9);
        $mpdf->SetX($s7Pad + $s7BoxPad);
        $mpdf->MultiCell($s7InnerW, 4, $s7TelRaw, 0, 'L');
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->SetDrawColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetLineWidth(0.5);
        $mpdf->Rect($s7Pad + $s7BoxW + 12, $s7BoxTop, $s7BoxW, $s7BoxH, 'FD');
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', 'B', 11);
        $mpdf->SetXY($s7Pad + $s7BoxW + 12 + $s7BoxPad, $s7BoxTop + $s7BoxPad);
        $mpdf->Cell($s7InnerW, 5, 'Sitios Web', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', '', 9);
        $mpdf->SetX($s7Pad + $s7BoxW + 12 + $s7BoxPad);
        $mpdf->MultiCell($s7InnerW, 4, $s7WebRaw, 0, 'L');
        $s7EmailBoxY = $s7BoxTop + $s7BoxH + 10;
        $s7EmailBoxW = $s7LeftW - 2 * $s7Pad;
        $s7EmailBoxH = 38;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->SetDrawColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetLineWidth(0.5);
        $mpdf->Rect($s7Pad, $s7EmailBoxY, $s7EmailBoxW, $s7EmailBoxH, 'FD');
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', 'B', 11);
        $mpdf->SetXY($s7Pad + $s7BoxPad, $s7EmailBoxY + $s7BoxPad);
        $mpdf->Cell($s7EmailBoxW - 2 * $s7BoxPad, 5, 'Emails', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', '', 9);
        $mpdf->SetX($s7Pad + $s7BoxPad);
        $mpdf->MultiCell($s7EmailBoxW - 2 * $s7BoxPad, 4, $s7MailRaw, 0, 'L');
        $mpdf->SetDrawColor(0, 0, 0);
    } else {
        if ($i === 2) {
            $mpdf->WriteHTML($htmlChunks[0] . $htmlChunks[$i]);
        } else {
            $mpdf->AddPage();
            $mpdf->WriteHTML($htmlChunks[$i]);
        }
    }
}
$nombreArchivo = 'Oferta_Exportable_Moderno_' . preg_replace('/\s+/', '_', $configInstitucional['nombre_provincia']) . '_' . $configInstitucional['periodo_ano'] . '.pdf';
// Para ver cambios al editar: abrir con ?page=moderno_pdf_es&nocache=1 — así el nombre incluye timestamp y no se usa caché del navegador
if (!empty($_GET['nocache'])) {
    $nombreArchivo = preg_replace('/\.pdf$/', '_' . time() . '.pdf', $nombreArchivo);
}

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
        . '<span style="float:right;font-size:14px;">Página 01</span></td></tr>'
        . '<tr><td style="height:' . $s1LineMm . 'mm;background:#fff;"></td></tr>'
        . '<tr><td style="height:' . $s1MiddleMm . 'mm;background:#0B1878;padding:0;vertical-align:middle;">'
        . '<div style="margin-left:20mm;display:inline-block;border:' . $s1Border . 'mm solid #fff;transform:rotate(-3deg);width:' . $s1ImgSize . 'mm;height:' . $s1ImgSize . 'mm;overflow:hidden;vertical-align:middle;">'
        . ($backgroundSlide1Uri ? '<img src="' . $backgroundSlide1Uri . '" alt="" style="width:100%;height:100%;object-fit:cover;display:block;" />' : '<div style="width:100%;height:100%;background:#1a4d8c;"></div>')
        . '</div>'
        . '<div style="display:inline-block;vertical-align:middle;margin-left:24mm;color:#fff;">'
        . '<div style="font-size:56px;font-weight:bold;line-height:1.1;">OFERTA</div><div style="font-size:56px;font-weight:bold;">EXPORTABLE</div>'
        . '<div style="font-size:22px;font-weight:bold;color:#fff;margin-top:12px;">' . htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper($c['nombre_provincia']) : strtoupper($c['nombre_provincia'])) . '</div></div></td></tr>'
        . '<tr><td style="height:' . $s1FooterMm . 'mm;background:#000;color:#fff;padding:0 14px 4px 14px;font-size:18px;vertical-align:middle;">'
        . '<span style="color:#fff;">Edición ' . (int)$c['periodo_ano'] . '</span><span style="float:right;font-size:24px;font-weight:bold;color:#fff;">&#8594;</span></td></tr>'
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
        <h1 class="slide-title" style="margin:0 0 10px 0;font-size:32px;font-weight:700;color:#fff;">Contexto productivo provincial</h1>
        <p class="texto" style="margin:0 0 12px 0;font-size:16px;line-height:1.4;color:#fff;">La Provincia actualiza su oferta exportable para brindar a compradores externos información clara y accesible en formatos gráficos e informáticos.</p>
        <p class="texto" style="margin:0;font-size:15px;line-height:1.4;color:#fff;">Esta herramienta fortalece la promoción comercial, acompañando la difusión de oferta, misiones comerciales y participación en ferias y rondas de negocios.</p>
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
            <h1 class="slide-title" style="color:#000;">Sectores productivos</h1>
            <p class="texto" style="color:#000;">La oferta exportable provincial se organiza por sectores para facilitar la búsqueda y la promoción internacional.</p>
        </div>
        <div class="primario" style="width:40%;padding:32px 24px;">
            <div style="margin-bottom:16px;"><strong style="color:#75A8DA;">Rubro A</strong><br><span class="texto">' . htmlspecialchars($rubroA) . '</span></div>
            <div style="margin-bottom:16px;"><strong style="color:#75A8DA;">Rubro B</strong><br><span class="texto">' . htmlspecialchars($rubroB) . '</span></div>
            <div><strong style="color:#75A8DA;">Rubro C</strong><br><span class="texto">' . htmlspecialchars($rubroC) . '</span></div>
        </div>
        <div style="width:30%;padding:32px 24px;">
            <div style="width:180px;height:180px;margin:0 auto 20px;overflow:hidden;border-radius:50%;">' . $s3Img . '</div>
            <p class="texto" style="text-align:center;"><strong>N° Empresas registradas:</strong> ' . (int)$metrics['empresas'] . '</p>
            <p class="texto" style="text-align:center;"><strong>N° Productos y servicios cargados:</strong> ' . (int)$metrics['productos'] . '</p>
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
        $bloquesEmpresa .= '<div style="margin-bottom:16px;"><span class="acento1" style="font-weight:700;">' . htmlspecialchars($emp['name'] ?? '') . '</span><br><span style="color:#000066;">' . htmlspecialchars($emp['main_activity'] ?? '') . '</span></div>';
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
            <h1 class="slide-title">Empresas destacadas</h1>
            <p class="texto">Selección representativa de empresas con oferta exportable registrada.</p>
            <div class="logo-caja" style="position:absolute;bottom:24px;right:24px;">' . ($logoDataUri ? '<img src="' . $logoDataUri . '" alt="Logo" />' : '') . '</div>
        </div>
    </div>';

    $cards = '';
    foreach (array_slice($productos, 0, 6) as $p) {
        $pid = (int)($p['id'] ?? 0);
        $path = $imgProducto[$pid] ?? null;
        $src = '';
        if ($path && file_exists($path)) {
            $m = $mimeFromPath($path);
            $src = 'data:' . $m . ';base64,' . base64_encode(file_get_contents($path));
        }
        $cards .= '<div style="background:#fff;border-radius:8px;overflow:hidden;padding:12px;text-align:center;">
            <div style="height:100px;background:#eee;border-radius:6px;overflow:hidden;">' . ($src ? '<img src="' . $src . '" alt="" style="width:100%;height:100%;object-fit:cover;" />' : '') . '</div>
            <p style="margin:8px 0 4px;font-weight:700;color:#000;">' . htmlspecialchars($p['name'] ?? '') . '</p>
            <p style="margin:0;font-size:14px;color:#003399;">' . htmlspecialchars($p['activity'] ?? '') . '</p>
            <p style="margin:4px 0 0;font-size:12px;">' . htmlspecialchars(mb_substr($p['description'] ?? '', 0, 80)) . '</p>
        </div>';
    }
    $s5 = '
    <div class="slide primario" style="padding:40px;">
        <h1 class="slide-title" style="text-align:center;margin-bottom:32px;">Productos exportables</h1>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">' . $cards . '</div>
    </div>';

    $s7 = '
    <div class="slide" style="display:flex;">
        <div style="width:35%;background:#C4343B;color:#fff;padding:40px;">
            <div class="logo-caja" style="margin-bottom:32px;">' . ($logoDataUri ? '<img src="' . $logoDataUri . '" alt="Logo" />' : '') . '</div>
            <h1 class="slide-title" style="font-size:32px;">Contacto institucional</h1>
            <p class="texto">' . htmlspecialchars($contacto['area_responsable'] ?? '') . '</p>
            <p class="texto">Tel: ' . htmlspecialchars($contacto['telefono'] ?? '') . '</p>
            <p class="texto">Web: ' . htmlspecialchars($contacto['sitio_web'] ?? '') . '</p>
            <p class="texto">Mail: ' . htmlspecialchars($contacto['mail'] ?? '') . '</p>
            <p class="texto">' . htmlspecialchars($contacto['localidad_direccion'] ?? '') . '</p>
        </div>
        <div style="width:65%;background:#75A8DA;display:flex;align-items:center;justify-content:center;">
            <h1 class="slide-title" style="color:#000;">Contacto Institucional</h1>
        </div>
    </div>';

    // Devolver por partes para que mPDF no supere pcre.backtrack_limit
    $header = '<!DOCTYPE html><html><head><meta charset="utf-8"><link href="https://fonts.googleapis.com/css2?family=Blinker:wght@400;600;700&display=swap" rel="stylesheet"><style>' . $css . '</style></head><body>';
    return [$header, $s1, $s2, $s3, $s4, $s5, $s7 . '</body></html>'];
}
