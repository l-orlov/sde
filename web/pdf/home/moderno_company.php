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
// Fondo del primer slide: solo Empresa0.jpg
$backgroundSlide1Path = $assetsDir . '/Empresa0.jpg';
if (!file_exists($backgroundSlide1Path)) {
    $backgroundSlide1Path = null;
}
// Portadas para slide Contacto (otras imágenes)
$portadaCandidates = [];
foreach (['Portada1.webp', 'portada2.webp', 'portada3.jpg', 'portada4.jpg', 'portada5.jpg', 'portada6.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $portadaCandidates[] = $p;
    }
}
$backgroundContactPath = !empty($portadaCandidates) ? $portadaCandidates[array_rand($portadaCandidates)] : $backgroundSlide1Path;
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
// Imágenes Producto para slide Productos destacados — все Producto*.jpg / Producto*.png из assets, рандомно на каждый слайд
$productoImgCandidates = [];
if (is_dir($assetsDir)) {
    foreach (scandir($assetsDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (preg_match('/^Producto\d*\.(jpg|jpeg|png|webp)$/i', $f)) {
            $p = $assetsDir . '/' . $f;
            if (file_exists($p)) {
                $productoImgCandidates[] = $p;
            }
        }
    }
}
if (empty($productoImgCandidates)) {
    foreach (['Producto1.jpg', 'Producto2.jpg', 'Producto3.jpg', 'Producto4.jpg'] as $name) {
        $p = $assetsDir . '/' . $name;
        if (file_exists($p)) {
            $productoImgCandidates[] = $p;
        }
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
$q = "SELECT c.id, c.name, c.main_activity, c.website, c.start_date
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

// Un producto o servicio por empresa para slides "Productos exportables" (un producto por slide)
$productosParaSlides = [];
$hasTargetMarkets = false;
$targetMarketsCheck = @mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'target_markets'");
if ($targetMarketsCheck && mysqli_num_rows($targetMarketsCheck) > 0) {
    $hasTargetMarkets = true;
}
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT p.id, p.name, p.activity, p.description, p.annual_export, p.certifications, p.company_id, p.type" . ($hasTargetMarkets ? ", p.target_markets" : "") . "
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

// Logos por empresa (solo file_type='logo', product_id IS NULL/0 — nunca fotos de productos)
$logosPorEmpresa = [];
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT c.id AS company_id, f.file_path FROM companies c
          INNER JOIN files f ON f.user_id = c.user_id AND f.file_type = 'logo' AND (f.product_id IS NULL OR f.product_id = 0)
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

// Redes sociales por empresa (para slide de datos de empresa)
$redesPorEmpresa = [];
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
                    if (!isset($redesPorEmpresa[$cid])) {
                        $redesPorEmpresa[$cid] = [];
                    }
                    $redesPorEmpresa[$cid][] = ($t !== '' ? $t . ': ' : '') . $u;
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
        $s1LogoPadFromImage = 10;
        $s1LogoBadgeW = 62;
        $s1LogoBadgeH = 28;
        $s1LogoBadgeGap = 10;
        $s1LogoBadgeY = $s1BgMargin + $s1LogoPadFromImage;
        $s1FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s1CompanyLogoPath = null;
        if ($s1FirstCompanyId && isset($logosPorEmpresa[$s1FirstCompanyId])) {
            $s1CompanyLogoPath = $logosPorEmpresa[$s1FirstCompanyId];
        } elseif ($s1FirstCompanyId && isset($imagenesPorEmpresa[$s1FirstCompanyId])) {
            $s1CompanyLogoPath = $imagenesPorEmpresa[$s1FirstCompanyId];
        }
        $s1HasCompanyLogo = $s1CompanyLogoPath && file_exists($s1CompanyLogoPath);
        $s1CompanyBadgeX = $s1HasCompanyLogo
            ? $s1BgMargin + $s1BgW - $s1LogoPadFromImage - 2 * $s1LogoBadgeW - $s1LogoBadgeGap
            : 0;
        $s1SdeBadgeX = $s1HasCompanyLogo
            ? $s1CompanyBadgeX + $s1LogoBadgeW + $s1LogoBadgeGap
            : $s1BgMargin + $s1BgW - $s1LogoPadFromImage - $s1LogoBadgeW;
        if ($s1HasCompanyLogo) {
            $mpdf->SetFillColor(196, 52, 59);
            $mpdf->Rect($s1CompanyBadgeX, $s1LogoBadgeY, $s1LogoBadgeW, $s1LogoBadgeH, 'F');
            $imgSize = @getimagesize($s1CompanyLogoPath);
            $maxW = $s1LogoBadgeW - 8;
            $maxH = $s1LogoBadgeH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s1CompanyLogoPath, $s1CompanyBadgeX + ($s1LogoBadgeW - $lw) / 2, $s1LogoBadgeY + ($s1LogoBadgeH - $lh) / 2, $lw, $lh);
        }
        $mpdf->SetFillColor(196, 52, 59);
        $mpdf->Rect($s1SdeBadgeX, $s1LogoBadgeY, $s1LogoBadgeW, $s1LogoBadgeH, 'F');
        $s1LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s1LogoPath)) {
            $imgSize = @getimagesize($s1LogoPath);
            $maxW = $s1LogoBadgeW - 8;
            $maxH = $s1LogoBadgeH - 8;
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
            $mpdf->Image($s1LogoPath, $s1SdeBadgeX + ($s1LogoBadgeW - $lw) / 2, $s1LogoBadgeY + ($s1LogoBadgeH - $lh) / 2, $lw, $lh);
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
        // Slide PERFIL DE EMPRESA: bloque logos arriba izq; título y 4 bloques texto (más grande); derecha bloque rojo estrecho + logo empresa superpuesto (parte en blanco, parte en rojo)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $pfPad = 20;
        $pfRedR = 196;
        $pfRedG = 52;
        $pfRedB = 59;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $pfLeftW = round($wMm * 0.62);
        $pfRightW = round($wMm * 0.18);
        $pfRightX = $wMm - $pfRightW;
        $pfEmp = !empty($companies[0]) ? $companies[0] : null;
        $pfCid = $pfEmp ? (int)$pfEmp['id'] : 0;
        $pfCompanyLogoPath = ($pfCid && isset($logosPorEmpresa[$pfCid])) ? $logosPorEmpresa[$pfCid] : (($pfCid && isset($imagenesPorEmpresa[$pfCid])) ? $imagenesPorEmpresa[$pfCid] : null);
        $pfLogoSectionY = $pfPad;
        $pfLogoSectionX = $pfPad;
        $pfCompanyLogoW = 44;
        $pfCompanyLogoH = 22;
        $pfSdeLogoW = 52;
        $pfSdeLogoH = 26;
        $pfLogoGap = 12;
        if ($pfCompanyLogoPath && file_exists($pfCompanyLogoPath)) {
            $imgSize = @getimagesize($pfCompanyLogoPath);
            $maxW = $pfCompanyLogoW - 4;
            $maxH = $pfCompanyLogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($pfCompanyLogoPath, $pfLogoSectionX, $pfLogoSectionY + ($pfCompanyLogoH - $lh) / 2, $lw, $lh);
        }
        $pfSdeLogoX = $pfLogoSectionX + $pfCompanyLogoW + $pfLogoGap;
        if (file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $maxW = $pfSdeLogoW - 4;
            $maxH = $pfSdeLogoH - 4;
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
            $mpdf->Image($pdfLogoPath, $pfSdeLogoX + ($pfSdeLogoW - $lw) / 2, $pfLogoSectionY + ($pfSdeLogoH - $lh) / 2, $lw, $lh);
        }
        $pfTitleY = $pfLogoSectionY + max($pfCompanyLogoH, $pfSdeLogoH) + 14;
        $mpdf->SetTextColor($pfRedR, $pfRedG, $pfRedB);
        $mpdf->SetFont('dejavusans', 'B', 32);
        $mpdf->SetXY($pfPad, $pfTitleY);
        $mpdf->Cell($pfLeftW - 2 * $pfPad, 12, 'PERFIL DE', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', 'B', 48);
        $mpdf->SetXY($pfPad, $pfTitleY + 14);
        $mpdf->Cell($pfLeftW - 2 * $pfPad, 18, 'EMPRESA', 0, 1, 'L');
        $pfPerfil1 = 'Tipo de Organización';
        $pfPerfil2 = trim($pfEmp['main_activity'] ?? '') ?: '-';
        $pfLoc = $pfCid && isset($localidadPorEmpresa[$pfCid]) ? $localidadPorEmpresa[$pfCid] : '-';
        $pfDepto = '-';
        $pfDomicilio = '-';
        $pfWeb = trim($pfEmp['website'] ?? '') ?: '-';
        $pfRedes = ($pfCid && isset($redesPorEmpresa[$pfCid]) && !empty($redesPorEmpresa[$pfCid])) ? implode(' ', array_slice($redesPorEmpresa[$pfCid], 0, 3)) : '-';
        $pfContacto = $configInstitucional;
        $pfCargo = trim($pfContacto['area_responsable'] ?? '') ?: '-';
        $pfEmail = trim($pfContacto['mail'] ?? '') ?: '-';
        $pfTel = trim($pfContacto['telefono'] ?? '') ?: '-';
        $pfBlockY = $pfTitleY + 50;
        $pfBlockH = 44;
        $pfColW = ($pfLeftW - 2 * $pfPad - 18) / 2;
        $pfGap = 18;
        $pfRows = [
            ['01', 'PERFIL:', [$pfPerfil1, $pfPerfil2]],
            ['02', 'UBICACIÓN:', [$pfLoc, $pfDepto, $pfDomicilio]],
            ['03', 'CANALES:', [$pfWeb, $pfRedes]],
            ['04', 'CONTACTO:', [$pfCargo, $pfEmail, $pfTel]],
        ];
        $pfNumToTextGap = 6;
        foreach ($pfRows as $idx => $row) {
            $col = $idx % 2;
            $rowIdx = (int)floor($idx / 2);
            $bx = $pfPad + $col * ($pfColW + $pfGap);
            $by = $pfBlockY + $rowIdx * $pfBlockH;
            $mpdf->SetTextColor($pfRedR, $pfRedG, $pfRedB);
            $mpdf->SetFont('dejavusans', 'B', 18);
            $mpdf->SetXY($bx, $by);
            $mpdf->Cell(24, 8, $row[0], 0, 1, 'L');
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', 16);
            $mpdf->SetXY($bx + $pfNumToTextGap, $by + 9);
            $mpdf->Cell($pfColW - $pfNumToTextGap, 8, $row[1], 0, 1, 'L');
            $mpdf->SetFont('dejavusans', '', 14);
            $valStr = implode("\n", array_filter($row[2]));
            $mpdf->SetXY($bx + $pfNumToTextGap, $by + 19);
            $mpdf->MultiCell($pfColW - $pfNumToTextGap, 7, $valStr !== '' ? $valStr : '-', 0, 'L');
        }
        $pfRedBlockMarginTop = 28;
        $pfRedBlockMarginBottom = 28;
        $pfRedBlockY = $pfRedBlockMarginTop;
        $pfRedBlockH = $hMm - $pfRedBlockMarginTop - $pfRedBlockMarginBottom;
        $mpdf->SetFillColor($pfRedR, $pfRedG, $pfRedB);
        $mpdf->Rect($pfRightX, $pfRedBlockY, $pfRightW, $pfRedBlockH, 'F');
        $pfLogoImgPath = ($pfCid && isset($logosPorEmpresa[$pfCid])) ? $logosPorEmpresa[$pfCid] : (($pfCid && isset($imagenesPorEmpresa[$pfCid])) ? $imagenesPorEmpresa[$pfCid] : null);
        $pfLogoSize = 136;
        $pfLogoOverlap = 12;
        $pfLogoX = $pfRightX - $pfLogoSize + $pfLogoOverlap + 55;
        $pfLogoImgY = $pfRedBlockY + ($pfRedBlockH - $pfLogoSize) / 2;
        if ($pfLogoImgPath && file_exists($pfLogoImgPath)) {
            if (extension_loaded('gd')) {
                $info = @getimagesize($pfLogoImgPath);
                $ext = $info ? strtolower(pathinfo($pfLogoImgPath, PATHINFO_EXTENSION)) : '';
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($pfLogoImgPath);
                elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($pfLogoImgPath);
                elseif (($ext === 'webp' || ($info && isset($info[2]) && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($pfLogoImgPath);
                if ($src && !empty($info[0]) && !empty($info[1])) {
                    $scale = 100 / 25.4;
                    $dw = (int)max(1, round($pfLogoSize * $scale));
                    $dh = (int)max(1, round($pfLogoSize * $scale));
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $boxR = 1;
                    $imgR = $sw / $sh;
                    if ($imgR >= $boxR) {
                        $cropW = (int)round($sh * $boxR);
                        $cropH = $sh;
                        $srcX = (int)floor(($sw - $cropW) / 2);
                        $srcY = 0;
                    } else {
                        $cropW = $sw;
                        $cropH = (int)round($sw / $boxR);
                        $srcX = 0;
                        $srcY = (int)floor(($sh - $cropH) / 2);
                    }
                    $dst = @imagecreatetruecolor($dw, $dh);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dw, $dh, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/moderno_pf_' . uniqid() . '.png';
                        if (imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $pfLogoX, $pfLogoImgY, $pfLogoSize, $pfLogoSize);
                            @unlink($tmp);
                        }
                        imagedestroy($dst);
                    }
                    imagedestroy($src);
                }
            } else {
                $mpdf->Image($pfLogoImgPath, $pfLogoX, $pfLogoImgY, $pfLogoSize, $pfLogoSize);
            }
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 2) {
        // Skip (Nuestra Historia movido antes de Competitividad)
    } elseif ($i === 3) {
        // Skip (Identidad provincial eliminado)
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
        $s4LogoBadgeGap = 10;
        $s4FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s4CompanyLogoPath = null;
        if ($s4FirstCompanyId && isset($logosPorEmpresa[$s4FirstCompanyId])) {
            $s4CompanyLogoPath = $logosPorEmpresa[$s4FirstCompanyId];
        } elseif ($s4FirstCompanyId && isset($imagenesPorEmpresa[$s4FirstCompanyId])) {
            $s4CompanyLogoPath = $imagenesPorEmpresa[$s4FirstCompanyId];
        }
        $s4HasCompanyLogo = $s4CompanyLogoPath && file_exists($s4CompanyLogoPath);
        $s4CompanyBadgeX = $s4HasCompanyLogo
            ? $wMm - $s4LogoMargin - $s4LogoPad - 2 * $s4LogoBadgeW - $s4LogoBadgeGap
            : 0;
        $s4SdeBadgeX = $s4HasCompanyLogo
            ? $s4CompanyBadgeX + $s4LogoBadgeW + $s4LogoBadgeGap
            : $wMm - $s4LogoMargin - $s4LogoPad - $s4LogoBadgeW;
        $s4LogoBadgeY = $s4LogoMargin + $s4LogoPad;
        if ($s4HasCompanyLogo) {
            $mpdf->SetFillColor(196, 52, 59);
            $mpdf->Rect($s4CompanyBadgeX, $s4LogoBadgeY, $s4LogoBadgeW, $s4LogoBadgeH, 'F');
            $imgSize = @getimagesize($s4CompanyLogoPath);
            $maxW = $s4LogoBadgeW - 8;
            $maxH = $s4LogoBadgeH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s4CompanyLogoPath, $s4CompanyBadgeX + ($s4LogoBadgeW - $lw) / 2, $s4LogoBadgeY + ($s4LogoBadgeH - $lh) / 2, $lw, $lh);
        }
        $mpdf->SetFillColor(196, 52, 59);
        $mpdf->Rect($s4SdeBadgeX, $s4LogoBadgeY, $s4LogoBadgeW, $s4LogoBadgeH, 'F');
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
            $mpdf->Image($s4LogoPath, $s4SdeBadgeX + ($s4LogoBadgeW - $lw) / 2, $s4LogoBadgeY + ($s4LogoBadgeH - $lh) / 2, $lw, $lh);
        }
        $s4TitleY = round($hMm * 0.70);
        $s4TitleLine1 = 'PRODUCTOS Y SERVICIOS';
        $s4TitleLine2 = 'EXPORTABLES';
        $mpdf->SetTextColor($s4RedR, $s4RedG, $s4RedB);
        $mpdf->SetFont('dejavusans', 'B', 32);
        $mpdf->SetXY(0, $s4TitleY);
        $mpdf->Cell($wMm, 12, $s4TitleLine1, 0, 1, 'C');
        $mpdf->SetFont('dejavusans', 'B', 26);
        $mpdf->SetXY(0, $s4TitleY + 14);
        $mpdf->Cell($wMm, 10, $s4TitleLine2, 0, 1, 'C');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 5) {
        // Slides Productos: un producto por slide — header (nombre producto + logo empresa + SDE), dos imágenes (producto con borde rojo + contextual), tres columnas abajo
        $p6CompanyNameById = [];
        foreach ($companies as $c) {
            $p6CompanyNameById[(int) $c['id']] = $c['name'] ?? '';
        }
        $p6RedR = 196;
        $p6RedG = 52;
        $p6RedB = 59;
        $p6Scale = 100 / 25.4;
        $p6LoadCrop = function ($path, $boxW, $boxH) use ($p6Scale) {
            if (!$path || !file_exists($path) || !extension_loaded('gd')) return null;
            $info = @getimagesize($path);
            $ext = $info ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && isset($info[2]) && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
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
            $tmp = sys_get_temp_dir() . '/moderno_p6_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        foreach ($productosParaSlides as $p6PageIdx => $prod) {
            $p6ProductoPool = array_values($productoImgCandidates);
            shuffle($p6ProductoPool);
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $mpdf->SetLeftMargin(0);
            $mpdf->SetRightMargin(0);
            $p6Pad = 20;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
            $p6LogoW = 52;
            $p6LogoH = 26;
            $p6LogoGap = 12;
            $p6CompanyLogoW = 44;
            $p6CompanyLogoH = 22;
            $p6Cid = (int) ($prod['company_id'] ?? 0);
            $p6LogoCompanyId = !empty($companies[0]['id']) ? (int)$companies[0]['id'] : $p6Cid;
            $p6CompanyLogoPath = null;
            if ($p6LogoCompanyId && isset($logosPorEmpresa[$p6LogoCompanyId])) {
                $p6CompanyLogoPath = $logosPorEmpresa[$p6LogoCompanyId];
            }
            $p6HasCompanyLogo = $p6CompanyLogoPath && file_exists($p6CompanyLogoPath);
            $p6LogosTotalW = $p6CompanyLogoW + $p6LogoGap + $p6LogoW;
            $p6LogoX = $wMm - $p6Pad - $p6LogosTotalW;
            $p6LogoY = $p6Pad;
            if ($p6HasCompanyLogo && $p6LogoCompanyId && isset($logosPorEmpresa[$p6LogoCompanyId]) && $logosPorEmpresa[$p6LogoCompanyId] === $p6CompanyLogoPath) {
                $p6LogoPathToDraw = $logosPorEmpresa[$p6LogoCompanyId];
                $imgSize = @getimagesize($p6LogoPathToDraw);
                $maxW = $p6CompanyLogoW - 4;
                $maxH = $p6CompanyLogoH - 4;
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $r = $imgSize[0] / $imgSize[1];
                    $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                    $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
                } else {
                    $lw = $maxW;
                    $lh = $maxH;
                }
                $mpdf->Image($p6LogoPathToDraw, $p6LogoX, $p6LogoY + ($p6CompanyLogoH - $lh) / 2, $lw, $lh);
            }
            $p6SdeLogoX = $p6LogoX + $p6CompanyLogoW + $p6LogoGap;
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
                $mpdf->Image($p6LogoPath, $p6SdeLogoX + ($p6LogoW - $lw) / 2, $p6LogoY + ($p6LogoH - $lh) / 2, $lw, $lh);
            }
            $p6TitleY = $p6LogoY + $p6LogoH + 6;
            $p6ProductName = trim($prod['name'] ?? '');
            $p6IsService = (strtolower($prod['type'] ?? '') === 'service' || strtolower($prod['type'] ?? '') === 'servicio');
            $p6TipoLabel = $p6IsService ? 'SERVICIO' : 'PRODUCTO';
            $p6TitleLeft = 'NOMBRE DEL ' . $p6TipoLabel . ':';
            $p6TitleRight = $p6ProductName !== '' ? (function_exists('mb_strtoupper') ? mb_strtoupper($p6ProductName) : strtoupper($p6ProductName)) : '';
            $mpdf->SetTextColor($p6RedR, $p6RedG, $p6RedB);
            $mpdf->SetFont('dejavusans', 'B', 28);
            $mpdf->SetXY($p6Pad, $p6TitleY);
            $p6TitleLeftW = $mpdf->GetStringWidth($p6TitleLeft) + 2;
            $mpdf->Cell($p6TitleLeftW, 11, $p6TitleLeft, 0, 0, 'L');
            if ($p6TitleRight !== '') {
                $p6TitleIndent = 6;
                $mpdf->SetXY($p6Pad + $p6TitleLeftW + $p6TitleIndent, $p6TitleY);
                $mpdf->Cell(0, 11, $p6TitleRight, 0, 1, 'L');
            } else {
                $mpdf->Ln();
            }
            $p6ImgGap = 2;
            $p6ContentW = $wMm - 2 * $p6Pad - $p6ImgGap;
            $p6ImgLeftW = round($p6ContentW * 0.48);
            $p6ImgRightW = $p6ContentW - $p6ImgLeftW;
            $p6ColBlockH = 42;
            $p6ColTop = $hMm - $p6Pad - $p6ColBlockH - 6;
            $p6ImgGapToCols = 10;
            $p6ImgLeftY = $p6TitleY + 32;
            $p6ImgRightY = $p6TitleY + 48;
            $p6AvailableLeftH = $p6ColTop - $p6ImgLeftY - $p6ImgGapToCols;
            $p6AvailableRightH = $p6ColTop - $p6ImgRightY - $p6ImgGapToCols;
            $p6ImgLeftH = round($p6AvailableLeftH * 0.88);
            $p6ImgRightH = round($p6AvailableRightH * 0.68);
            $p6BorderW = 3;
            $p6WhiteMargin = 2.5;
            $p6LeftImgPath = isset($p6ProductoPool[0]) ? $p6ProductoPool[0] : null;
            $p6RightImgPath = isset($p6ProductoPool[1]) ? $p6ProductoPool[1] : (isset($p6ProductoPool[0]) ? $p6ProductoPool[0] : (isset($empresaSlide4Paths[0]) ? $empresaSlide4Paths[0] : null));
            $p6xLeft = $p6Pad;
            $mpdf->SetDrawColor($p6RedR, $p6RedG, $p6RedB);
            $mpdf->SetLineWidth($p6BorderW);
            $mpdf->Rect($p6xLeft, $p6ImgLeftY, $p6ImgLeftW, $p6ImgLeftH, 'D');
            $p6innerX = $p6xLeft + $p6BorderW + $p6WhiteMargin;
            $p6innerY = $p6ImgLeftY + $p6BorderW + $p6WhiteMargin;
            $p6innerW = $p6ImgLeftW - 2 * $p6BorderW - 2 * $p6WhiteMargin;
            $p6innerH = $p6ImgLeftH - 2 * $p6BorderW - 2 * $p6WhiteMargin;
            $tmp = $p6LoadCrop($p6LeftImgPath, $p6innerW, $p6innerH);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $p6innerX, $p6innerY, $p6innerW, $p6innerH);
                @unlink($tmp);
            } elseif ($p6LeftImgPath && file_exists($p6LeftImgPath)) {
                $mpdf->Image($p6LeftImgPath, $p6innerX, $p6innerY, $p6innerW, $p6innerH);
            } else {
                $mpdf->SetFillColor(248, 248, 248);
                $mpdf->Rect($p6innerX, $p6innerY, $p6innerW, $p6innerH, 'F');
            }
            $p6xRight = $p6Pad + $p6ImgLeftW + $p6ImgGap;
            $tmp = $p6LoadCrop($p6RightImgPath, $p6ImgRightW, $p6ImgRightH);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $p6xRight, $p6ImgRightY, $p6ImgRightW, $p6ImgRightH);
                @unlink($tmp);
            } elseif ($p6RightImgPath && file_exists($p6RightImgPath)) {
                $mpdf->Image($p6RightImgPath, $p6xRight, $p6ImgRightY, $p6ImgRightW, $p6ImgRightH);
            } else {
                $mpdf->SetFillColor(248, 248, 248);
                $mpdf->Rect($p6xRight, $p6ImgRightY, $p6ImgRightW, $p6ImgRightH, 'F');
            }
            $p6ColGap = 14;
            $p6N = 3;
            $p6ColW = ($wMm - 2 * $p6Pad - ($p6N - 1) * $p6ColGap) / $p6N;
            $p6ColTitles = ['01. EXPORTACIÓN ANUAL (USD):', '02. CERTIFICACIONES', '03. MERCADOS DE INTERÉS'];
            $p6Annual = trim($prod['annual_export'] ?? '') ?: 'TEXTO';
            $p6Cert = trim($prod['certifications'] ?? '') ?: 'TEXTO';
            $p6Mercados = 'TEXTO';
            if (!empty($prod['target_markets'])) {
                $dec = is_string($prod['target_markets']) ? json_decode($prod['target_markets'], true) : $prod['target_markets'];
                if (is_array($dec)) {
                    $list = [];
                    foreach ($dec as $m) {
                        $list[] = is_array($m) ? ($m['nombre'] ?? $m['name'] ?? '') : (string)$m;
                    }
                    $p6Mercados = implode("\n", array_filter(array_slice($list, 0, 5)));
                }
            }
            $p6ColContents = [$p6Annual, $p6Cert, $p6Mercados];
            $p6TitleRowH = 9;
            for ($col = 0; $col < $p6N; $col++) {
                $cx = $p6Pad + $col * ($p6ColW + $p6ColGap);
                $mpdf->SetTextColor(0, 0, 0);
                $mpdf->SetFont('dejavusans', 'B', 14);
                $mpdf->SetXY($cx, $p6ColTop);
                $mpdf->Cell($p6ColW, $p6TitleRowH, $p6ColTitles[$col], 0, 1, 'L');
                $mpdf->SetFont('dejavusans', '', 14);
                $mpdf->SetXY($cx, $p6ColTop + $p6TitleRowH + 2);
                $mpdf->MultiCell($p6ColW, 7, $p6ColContents[$col], 0, 'L');
            }
            $mpdf->SetDrawColor(0, 0, 0);
        }
        // Slide NUESTRA HISTORIA (antes de Competitividad)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
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
        $s2LogoGap = 12;
        $s2CompanyLogoW = 44;
        $s2CompanyLogoH = 22;
        $s2FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s2CompanyLogoPath = null;
        if ($s2FirstCompanyId && isset($logosPorEmpresa[$s2FirstCompanyId])) {
            $s2CompanyLogoPath = $logosPorEmpresa[$s2FirstCompanyId];
        } elseif ($s2FirstCompanyId && isset($imagenesPorEmpresa[$s2FirstCompanyId])) {
            $s2CompanyLogoPath = $imagenesPorEmpresa[$s2FirstCompanyId];
        }
        $s2LogosTotalW = $s2CompanyLogoPath && file_exists($s2CompanyLogoPath) ? ($s2CompanyLogoW + $s2LogoGap + $s2LogoW) : $s2LogoW;
        $s2LogoX = $wMm - $s2Pad - $s2LogosTotalW;
        $s2LogoY = $s2Pad;
        if ($s2CompanyLogoPath && file_exists($s2CompanyLogoPath)) {
            $imgSize = @getimagesize($s2CompanyLogoPath);
            $maxW = $s2CompanyLogoW - 4;
            $maxH = $s2CompanyLogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s2CompanyLogoPath, $s2LogoX, $s2LogoY + ($s2CompanyLogoH - $lh) / 2, $lw, $lh);
        }
        $s2LogoPath = $pdfLogoPath;
        $s2SdeLogoX = $s2LogoX + ($s2CompanyLogoPath && file_exists($s2CompanyLogoPath) ? $s2CompanyLogoW + $s2LogoGap : 0);
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
            $mpdf->Image($s2LogoPath, $s2SdeLogoX + ($s2LogoW - $lw) / 2, $s2LogoY + ($s2LogoH - $lh) / 2, $lw, $lh);
        }
        $s2TextPad = 45;
        $s2TextLeft = $s2LeftW + $s2TextPad;
        $s2TextW = $s2RightW - $s2TextPad - $s2Pad;
        $s2TitleShiftLeft = 18;
        $s2TitleLeft = $s2TextLeft - $s2TitleShiftLeft;
        $s2TitleW = $s2TextW + $s2TitleShiftLeft;
        $s2TitleTop = $s2LogoY + $s2LogoH + 20;
        $mpdf->SetTextColor(160, 35, 35);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s2TitleLeft, $s2TitleTop);
        $mpdf->Cell($s2TitleW, 12, 'NUESTRA', 0, 1, 'C');
        $mpdf->SetFont('dejavusans', 'B', 32);
        $mpdf->SetXY($s2TitleLeft, $s2TitleTop + 14);
        $mpdf->Cell($s2TitleW, 12, 'HISTORIA', 0, 1, 'C');
        $s2ParaTop = $s2TitleTop + 40;
        $mpdf->SetTextColor(0, 0, 0);
        $s2FontSize = 15;
        $s2LineHeight = 7;
        $mpdf->SetLeftMargin($s2TextLeft);
        $mpdf->SetRightMargin($wMm - $s2Pad);
        $s2Para = 'Nisi justo faucibus lectus blandit donec gravida proin natoque, malesuada a facilisis dictumst rhoncus pulvinar aliquet feugiat ultrices, mollis phasellus varius tortor habitasse purus enim. Nunc lacus sociis tortor volutpat egestas vel duis erat, eleifend dapibus praesent vehicula fringilla ac suscipit conubia, nibh pulvinar elementum faucibus urna nullam luctus. Augue senectus rutrum suscipit habitasse felis aptent phasellus, nec hendrerit mattis enim congue tempor auctor magnis, mollis neque libero sagittis urna orci.';
        $mpdf->SetFont('dejavusans', '', $s2FontSize);
        $mpdf->SetXY($s2TextLeft, $s2ParaTop);
        $mpdf->MultiCell($s2TextW, $s2LineHeight, $s2Para, 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Slide COMPETITIVIDAD Y DIFERENCIACIÓN (antes de Contacto): fondo gris; izq = título + 5 ítems con icono; derecha = imagen + overlay rojo + logo SDE
        $iconRedPath = $assetsDir . '/icon_red.png';
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $c8Pad = 22;
        $c8RedR = 196;
        $c8RedG = 52;
        $c8RedB = 59;
        $c8Gray = 238;
        $mpdf->SetFillColor($c8Gray, $c8Gray, $c8Gray);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $c8LeftW = round($wMm * 0.58);
        $c8RightX = $c8LeftW;
        $c8RightW = $wMm - $c8LeftW;
        $c8TitleY = $c8Pad + 28;
        $mpdf->SetTextColor($c8RedR, $c8RedG, $c8RedB);
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($c8Pad, $c8TitleY);
        $mpdf->Cell($c8LeftW - 2 * $c8Pad, 13, 'COMPETITIVIDAD Y', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($c8Pad, $c8TitleY + 16);
        $mpdf->Cell($c8LeftW - 2 * $c8Pad, 14, 'DIFERENCIACIÓN', 0, 1, 'L');
        $c8ItemY = $c8TitleY + 58;
        $c8IconSize = 20;
        $c8ColW = ($c8LeftW - 2 * $c8Pad - 14) / 2;
        $c8ColGap = 14;
        $c8ItemH = 38;
        $c8Items = [
            ['PREMIOS', 'Información proveniente del input del formulario.'],
            ['RONDAS', 'Información proveniente del input del formulario.'],
            ['REFERENCIAS COMERCIALES', 'Información proveniente del input del formulario.'],
            ['FERIAS', 'Información proveniente del input del formulario.'],
            ['EXPERIENCIA EXPORTADORA', 'Información proveniente del input del formulario.'],
        ];
        foreach ($c8Items as $idx => $item) {
            if ($idx < 3) {
                $col = 0;
                $row = $idx;
            } else {
                $col = 1;
                $row = $idx - 3;
            }
            $bx = $c8Pad + $col * ($c8ColW + $c8ColGap);
            $by = $c8ItemY + $row * $c8ItemH;
            if ($iconRedPath && file_exists($iconRedPath)) {
                $mpdf->Image($iconRedPath, $bx, $by, $c8IconSize, $c8IconSize);
            }
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', 15);
            $mpdf->SetXY($bx + $c8IconSize + 4, $by);
            $mpdf->Cell($c8ColW - $c8IconSize - 4, 8, $item[0], 0, 1, 'L');
            $mpdf->SetFont('dejavusans', '', 14);
            $mpdf->SetXY($bx + $c8IconSize + 4, $by + 9);
            $mpdf->MultiCell($c8ColW - $c8IconSize - 4, 6, $item[1], 0, 'L');
        }
        $c8RedW = round($wMm * 0.20);
        $c8RedX = $wMm - $c8RedW;
        $c8ImgPad = 10;
        $c8ImgW = round($wMm * 0.35);
        $c8ImgX = $wMm - $c8ImgPad - $c8ImgW;
        $c8ImgY = $c8ImgPad;
        $c8ImgH = $hMm - 2 * $c8ImgPad;
        $c8PortadaPaths = [];
        foreach (['portada2.webp', 'portada3.jpg'] as $pn) {
            $p = $assetsDir . '/' . $pn;
            if (file_exists($p)) {
                $c8PortadaPaths[] = $p;
            }
        }
        $c8ImgPath = !empty($c8PortadaPaths) ? $c8PortadaPaths[array_rand($c8PortadaPaths)] : null;
        $c8RedAlpha = 0.65;
        if (method_exists($mpdf, 'SetAlpha')) {
            $mpdf->SetAlpha($c8RedAlpha);
        }
        $mpdf->SetFillColor($c8RedR, $c8RedG, $c8RedB);
        $mpdf->Rect($c8RedX, 0, $c8RedW, $hMm, 'F');
        if (method_exists($mpdf, 'SetAlpha')) {
            $mpdf->SetAlpha(1);
        }
        if ($c8ImgPath && file_exists($c8ImgPath) && extension_loaded('gd')) {
            $info = @getimagesize($c8ImgPath);
            $ext = $info ? strtolower(pathinfo($c8ImgPath, PATHINFO_EXTENSION)) : '';
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($c8ImgPath);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($c8ImgPath);
            elseif (($ext === 'webp' || ($info && isset($info[2]) && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($c8ImgPath);
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $scale = 100 / 25.4;
                $dw = (int)max(1, round($c8ImgW * $scale));
                $dh = (int)max(1, round($c8ImgH * $scale));
                $sw = imagesx($src);
                $sh = imagesy($src);
                $boxR = $dw / $dh;
                $imgR = $sw / $sh;
                if ($imgR >= $boxR) {
                    $cropW = (int)round($sh * $boxR);
                    $cropH = $sh;
                    $srcX = (int)floor(($sw - $cropW) / 2);
                    $srcY = 0;
                } else {
                    $cropW = $sw;
                    $cropH = (int)round($sw / $boxR);
                    $srcX = 0;
                    $srcY = (int)floor(($sh - $cropH) / 2);
                }
                $dst = @imagecreatetruecolor($dw, $dh);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dw, $dh, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/moderno_c8_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, $c8ImgX, $c8ImgY, $c8ImgW, $c8ImgH);
                        @unlink($tmp);
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        } elseif ($c8ImgPath && file_exists($c8ImgPath)) {
            $mpdf->Image($c8ImgPath, $c8ImgX, $c8ImgY, $c8ImgW, $c8ImgH);
        } else {
            $mpdf->SetFillColor(200, 200, 200);
            $mpdf->Rect($c8ImgX, $c8ImgY, $c8ImgW, $c8ImgH, 'F');
        }
        $c8LogoBadgeW = 62;
        $c8LogoBadgeH = 28;
        $c8LogoBadgeGap = 10;
        $c8LogoBadgePad = 14;
        $c8LogosY = $hMm - $c8LogoBadgeH - $c8LogoBadgePad;
        $c8FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $c8CompanyLogoPath = null;
        if ($c8FirstCompanyId && isset($logosPorEmpresa[$c8FirstCompanyId])) {
            $c8CompanyLogoPath = $logosPorEmpresa[$c8FirstCompanyId];
        } elseif ($c8FirstCompanyId && isset($imagenesPorEmpresa[$c8FirstCompanyId])) {
            $c8CompanyLogoPath = $imagenesPorEmpresa[$c8FirstCompanyId];
        }
        $c8HasCompanyLogo = $c8CompanyLogoPath && file_exists($c8CompanyLogoPath);
        $c8CompanyBadgeX = $c8HasCompanyLogo
            ? $wMm - $c8LogoBadgePad - 2 * $c8LogoBadgeW - $c8LogoBadgeGap
            : 0;
        $c8SdeBadgeX = $c8HasCompanyLogo
            ? $c8CompanyBadgeX + $c8LogoBadgeW + $c8LogoBadgeGap
            : $wMm - $c8LogoBadgePad - $c8LogoBadgeW;
        if ($c8HasCompanyLogo) {
            $mpdf->SetFillColor($c8RedR, $c8RedG, $c8RedB);
            $mpdf->Rect($c8CompanyBadgeX, $c8LogosY, $c8LogoBadgeW, $c8LogoBadgeH, 'F');
            $imgSize = @getimagesize($c8CompanyLogoPath);
            $maxW = $c8LogoBadgeW - 8;
            $maxH = $c8LogoBadgeH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($c8CompanyLogoPath, $c8CompanyBadgeX + ($c8LogoBadgeW - $lw) / 2, $c8LogosY + ($c8LogoBadgeH - $lh) / 2, $lw, $lh);
        }
        $mpdf->SetFillColor($c8RedR, $c8RedG, $c8RedB);
        $mpdf->Rect($c8SdeBadgeX, $c8LogosY, $c8LogoBadgeW, $c8LogoBadgeH, 'F');
        $c8SdeLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($c8SdeLogoPath)) {
            $imgSize = @getimagesize($c8SdeLogoPath);
            $maxW = $c8LogoBadgeW - 8;
            $maxH = $c8LogoBadgeH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($c8SdeLogoPath, $c8SdeBadgeX + ($c8LogoBadgeW - $lw) / 2, $c8LogosY + ($c8LogoBadgeH - $lh) / 2, $lw, $lh);
        }
    } elseif ($i === 6) {
        // Slide Contacto (GRACIAS): fondo imagen portada; V rojo/blanco; izq = GRACIAS + Contacto (rojo) + Teléfonos/Emails (borde rojo); abajo derecha = bloque logos (empresa + SDE) como slide 1
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
        $s7TitleY = $s7Pad + 8;
        $mpdf->SetTextColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetFont('dejavusans', 'B', 54);
        $mpdf->SetXY($s7Pad, $s7TitleY);
        $mpdf->Cell($s7LeftW - 2 * $s7Pad, 20, 'GRACIAS', 0, 1, 'L');
        $contacto = $configInstitucional;
        $s7ContactoPersona = trim($contacto['area_responsable'] ?? '') ?: 'Persona de contacto';
        $s7TelRaw = trim($contacto['telefono'] ?? '') ?: '+12345678';
        $s7MailRaw = trim($contacto['mail'] ?? '') ?: 'mail@contacto.com';
        if (strpos($s7MailRaw, ',') !== false) {
            $s7Mails = array_map('trim', explode(',', $s7MailRaw));
        } else {
            $s7Mails = [$s7MailRaw];
        }
        $s7BoxTop = $s7TitleY + 38;
        $s7BoxW = ($s7LeftW - 2 * $s7Pad - 12) / 2;
        $s7BoxH = 34;
        $s7BoxPad = 6;
        $s7InnerW = $s7BoxW - 2 * $s7BoxPad;
        $mpdf->SetFillColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->Rect($s7Pad, $s7BoxTop, $s7BoxW, $s7BoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 11);
        $mpdf->SetXY($s7Pad + $s7BoxPad, $s7BoxTop + $s7BoxPad);
        $mpdf->Cell($s7InnerW, 5, 'Contacto', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', '', 9);
        $mpdf->SetX($s7Pad + $s7BoxPad);
        $mpdf->Cell($s7InnerW, 4, $s7ContactoPersona, 0, 1, 'L');
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->SetDrawColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetLineWidth(0.5);
        $mpdf->Rect($s7Pad + $s7BoxW + 12, $s7BoxTop, $s7BoxW, $s7BoxH, 'FD');
        $mpdf->SetTextColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetFont('dejavusans', 'B', 11);
        $mpdf->SetXY($s7Pad + $s7BoxW + 12 + $s7BoxPad, $s7BoxTop + $s7BoxPad);
        $mpdf->Cell($s7InnerW, 5, 'Teléfonos', 0, 1, 'L');
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', '', 9);
        $mpdf->SetX($s7Pad + $s7BoxW + 12 + $s7BoxPad);
        $mpdf->MultiCell($s7InnerW, 4, $s7TelRaw, 0, 'L');
        $s7EmailBoxY = $s7BoxTop + $s7BoxH + 10;
        $s7EmailBoxW = $s7LeftW - 2 * $s7Pad;
        $s7EmailBoxH = 40;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s7Pad, $s7EmailBoxY, $s7EmailBoxW, $s7EmailBoxH, 'FD');
        $mpdf->SetTextColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->SetFont('dejavusans', 'B', 11);
        $mpdf->SetXY($s7Pad + $s7BoxPad, $s7EmailBoxY + $s7BoxPad);
        $mpdf->Cell($s7EmailBoxW - 2 * $s7BoxPad, 5, 'Emails', 0, 1, 'L');
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', '', 9);
        $mpdf->SetX($s7Pad + $s7BoxPad);
        $mpdf->MultiCell($s7EmailBoxW - 2 * $s7BoxPad, 4, implode("\n", array_slice($s7Mails, 0, 3)), 0, 'L');
        $mpdf->SetDrawColor(0, 0, 0);

        $s7LogoBadgeW = 62;
        $s7LogoBadgeH = 28;
        $s7LogoBadgeGap = 10;
        $s7LogoBadgePad = 14;
        $s7LogosY = $s7FullH - $s7LogoBadgeH - $s7LogoBadgePad;
        $s7FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s7CompanyLogoPath = null;
        if ($s7FirstCompanyId && isset($logosPorEmpresa[$s7FirstCompanyId])) {
            $s7CompanyLogoPath = $logosPorEmpresa[$s7FirstCompanyId];
        } elseif ($s7FirstCompanyId && isset($imagenesPorEmpresa[$s7FirstCompanyId])) {
            $s7CompanyLogoPath = $imagenesPorEmpresa[$s7FirstCompanyId];
        }
        $s7HasCompanyLogo = $s7CompanyLogoPath && file_exists($s7CompanyLogoPath);
        $s7CompanyBadgeX = $s7HasCompanyLogo
            ? $s7FullW - $s7LogoBadgePad - 2 * $s7LogoBadgeW - $s7LogoBadgeGap
            : 0;
        $s7SdeBadgeX = $s7HasCompanyLogo
            ? $s7CompanyBadgeX + $s7LogoBadgeW + $s7LogoBadgeGap
            : $s7FullW - $s7LogoBadgePad - $s7LogoBadgeW;
        if ($s7HasCompanyLogo) {
            $mpdf->SetFillColor($s7RedR, $s7RedG, $s7RedB);
            $mpdf->Rect($s7CompanyBadgeX, $s7LogosY, $s7LogoBadgeW, $s7LogoBadgeH, 'F');
            $imgSize = @getimagesize($s7CompanyLogoPath);
            $maxW = $s7LogoBadgeW - 8;
            $maxH = $s7LogoBadgeH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s7CompanyLogoPath, $s7CompanyBadgeX + ($s7LogoBadgeW - $lw) / 2, $s7LogosY + ($s7LogoBadgeH - $lh) / 2, $lw, $lh);
        }
        $mpdf->SetFillColor($s7RedR, $s7RedG, $s7RedB);
        $mpdf->Rect($s7SdeBadgeX, $s7LogosY, $s7LogoBadgeW, $s7LogoBadgeH, 'F');
        $s7LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s7LogoPath)) {
            $imgSize = @getimagesize($s7LogoPath);
            $maxW = $s7LogoBadgeW - 8;
            $maxH = $s7LogoBadgeH - 8;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $lw = $maxW;
                $lh = $maxH;
            }
            $mpdf->Image($s7LogoPath, $s7SdeBadgeX + ($s7LogoBadgeW - $lw) / 2, $s7LogosY + ($s7LogoBadgeH - $lh) / 2, $lw, $lh);
        }
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
// Para ver cambios al editar: abrir con ?page=moderno_pdf&nocache=1 — así el nombre incluye timestamp y no se usa caché del navegador
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
