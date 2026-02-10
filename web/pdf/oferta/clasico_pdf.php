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
$assetsDir = __DIR__ . '/assets';
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

// Un producto o servicio por empresa para slides "Productos exportables" (grilla 2×3, 6 por página; productos y servicios)
$productosParaSlides = [];
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT p.id, p.name, p.activity, p.description, p.annual_export, p.certifications, p.company_id, p.type
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
        $mpdf->WriteHTML($htmlChunks[0]);
    } elseif ($i === 1) {
        // Slide 1: izq. dos bloques (blanco arriba ~20%, azul abajo ~80%, cada uno 5% ancho); fondo Portada tocando arriba/abajo/derecha; texto; badge solo logo_white; número página
        $s1FullH = $hMm;
        $s1LeftColW = round($wMm * 0.04);
        $s1BlueH = round($s1FullH * 0.75);
        $s1WhiteH = $s1FullH - $s1BlueH;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $s1LeftColW, $s1WhiteH, 'F');
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect(0, $s1WhiteH, $s1LeftColW, $s1BlueH, 'F');
        $s1BgW = $wMm - $s1LeftColW;
        $mpdf->SetFillColor(30, 30, 40);
        $mpdf->Rect($s1LeftColW, 0, $s1BgW, $s1FullH, 'F');
        $s1BgStretchedPath = null;
        if ($backgroundSlide1Path && file_exists($backgroundSlide1Path) && extension_loaded('gd')) {
            $info = @getimagesize($backgroundSlide1Path);
            $s1BgExt = strtolower(pathinfo($backgroundSlide1Path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($backgroundSlide1Path);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($backgroundSlide1Path);
            } elseif (($s1BgExt === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($backgroundSlide1Path);
            }
            if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $scale = 100 / 25.4;
                    $dw = (int) max(1, round($s1BgW * $scale));
                    $dh = (int) max(1, round($dw * $s1FullH / $s1BgW));
                    $dst = imagecreatetruecolor($dw, $dh);
                    if ($dst && @imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh)) {
                        if (function_exists('imagefilter')) {
                            @imagefilter($dst, IMG_FILTER_BRIGHTNESS, -85);
                        }
                        $tmp = sys_get_temp_dir() . '/clasico_portada_' . uniqid() . '.png';
                        if (imagepng($dst, $tmp)) {
                            $s1BgStretchedPath = $tmp;
                        }
                        imagedestroy($dst);
                    }
                    imagedestroy($src);
            }
        }
        if ($s1BgStretchedPath && file_exists($s1BgStretchedPath)) {
            $mpdf->Image($s1BgStretchedPath, $s1LeftColW, 0, $s1BgW, $s1FullH);
            @unlink($s1BgStretchedPath);
        } elseif ($backgroundSlide1Path && file_exists($backgroundSlide1Path)) {
            $mpdf->Image($backgroundSlide1Path, $s1LeftColW, 0, $s1BgW, $s1FullH);
        }
        $s1TextLeft = $s1LeftColW + 14;
        $s1TextW = $wMm - $s1TextLeft - 24;
        $s1Y = 28;
        $mpdf->SetLeftMargin($s1TextLeft);
        $mpdf->SetRightMargin(24);
        $mpdf->SetXY($s1TextLeft, $s1Y);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 16);
        $mpdf->Cell($s1TextW, 9, 'Edición ' . $configInstitucional['periodo_ano'], 0, 1, 'L');
        $s1Y = 75;
        $mpdf->SetXY($s1TextLeft, $s1Y);
        $mpdf->SetFont('dejavusans', 'B', 59);
        $mpdf->Cell($s1TextW, 21, 'OFERTA', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', 'B', 59);
        $mpdf->Cell($s1TextW, 21, 'EXPORTABLE', 0, 1, 'L');
        $mpdf->SetTextColor(117, 168, 218);
        $mpdf->SetFont('dejavusans', 'B', 26);
        $mpdf->SetXY($s1TextLeft, $s1Y + 40 + 6);
        $mpdf->Cell($s1TextW, 12, function_exists('mb_strtoupper') ? mb_strtoupper($configInstitucional['nombre_provincia']) : strtoupper($configInstitucional['nombre_provincia']), 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->Ln(10);
        $s1ParW = round($s1TextW * 0.45);
        $mpdf->MultiCell($s1ParW, 7, 'Presentación provincial de empresas registradas y productos exportables destacados.', 0, 'L');
        $s1BadgeW = 64;
        $s1BadgeH = 24;
        $s1BadgeX = $wMm - $s1BadgeW - 24;
        $s1BadgeY = 20;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s1BadgeX, $s1BadgeY, $s1BadgeW, $s1BadgeH, 'F');
        $s1LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s1LogoPath)) {
            $imgSize = @getimagesize($s1LogoPath);
            $maxLogoW = $s1BadgeW - 4;
            $maxLogoH = $s1BadgeH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                if ($maxLogoH * $imgRatio <= $maxLogoW) {
                    $logoH = $maxLogoH;
                    $logoW = $maxLogoH * $imgRatio;
                } else {
                    $logoW = $maxLogoW;
                    $logoH = $maxLogoW / $imgRatio;
                }
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s1BadgeX + ($s1BadgeW - $logoW) / 2;
            $logoY = $s1BadgeY + ($s1BadgeH - $logoH) / 2;
            $mpdf->Image($s1LogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        // Número de página abajo derecha: igual que en slide 2 — área azul 40mm, "01" con mismo margen derecho que "02"
        $s1PageBoxW = 40;
        $s1PageBoxH = 13;
        $s1PageBoxX = $wMm - $s1PageBoxW;
        $s1PageBoxY = $s1FullH - $s1PageBoxH - 18;
        $mpdf->SetFillColor(141, 188, 220);
        $mpdf->Rect($s1PageBoxX, $s1PageBoxY, $s1PageBoxW, $s1PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s1PageBoxX, $s1PageBoxY + 2.2);
        $mpdf->Cell($s1PageBoxW - 26, 9, '01', 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->AddPage();
        // Anclar contexto de dibujo a la página 2 (evita que mPDF asocie contenido a otra página)
        $mpdf->SetXY(0, 0);
        // Slide 2: imagen al 80% del ancho del slide; encima, centrado, bloque blanco con texto
        $s2ImageW = round($wMm * 0.80);
        $s2ImagePath = null;
        if ($productivoSlide2Path && extension_loaded('gd')) {
            $info = @getimagesize($productivoSlide2Path);
            $s2Ext = strtolower(pathinfo($productivoSlide2Path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($productivoSlide2Path);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($productivoSlide2Path);
            } elseif (($s2Ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($productivoSlide2Path);
            }
            if ($src) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $scale = 100 / 25.4;
                $dw = (int) max(1, round($s2ImageW * $scale));
                $dh = (int) max(1, round($hMm * $scale));
                $dst = imagecreatetruecolor($dw, $dh);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh)) {
                    if (function_exists('imagefilter')) {
                        @imagefilter($dst, IMG_FILTER_BRIGHTNESS, -75);
                        @imagefilter($dst, IMG_FILTER_COLORIZE, 20, 20, 60);
                    }
                    $tmp = sys_get_temp_dir() . '/clasico_productivo_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $s2ImagePath = $tmp;
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        }
        if ($s2ImagePath && file_exists($s2ImagePath)) {
            $mpdf->SetLineWidth(0);
            $mpdf->Image($s2ImagePath, 0, 0, $s2ImageW, $hMm);
            @unlink($s2ImagePath);
        } else {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect(0, 0, $s2ImageW, $hMm, 'F');
        }
        if ($s2ImageW < $wMm) {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($s2ImageW, 0, $wMm - $s2ImageW, $hMm, 'F');
        }
        // Badge logo sobre la imagen — misma estilística que slide 1 (azul #003399), ubicado a la izquierda
        $s2BadgeW = 64;
        $s2BadgeH = 24;
        $s2BadgeX = 24;
        $s2BadgeY = 20;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s2BadgeX, $s2BadgeY, $s2BadgeW, $s2BadgeH, 'F');
        $s2LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s2LogoPath)) {
            $imgSize = @getimagesize($s2LogoPath);
            $maxLogoW = $s2BadgeW - 4;
            $maxLogoH = $s2BadgeH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                if ($maxLogoH * $imgRatio <= $maxLogoW) {
                    $logoH = $maxLogoH;
                    $logoW = $maxLogoH * $imgRatio;
                } else {
                    $logoW = $maxLogoW;
                    $logoH = $maxLogoW / $imgRatio;
                }
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s2BadgeX + ($s2BadgeW - $logoW) / 2;
            $logoY = $s2BadgeY + ($s2BadgeH - $logoH) / 2;
            $mpdf->Image($s2LogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        // Bloque blanco: 50% ancho, 100% alto, desde el centro del slide ocupando la mitad derecha (sin borde/contorno)
        $s2BlockX = round($wMm / 2);
        $s2BlockW = $wMm - $s2BlockX;
        $s2BlockH = $hMm;
        $s2BlockY = 0;
        $mpdf->SetLineWidth(0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s2BlockX, $s2BlockY, $s2BlockW, $s2BlockH, 'F');
        $s2Pad = 24;
        $s2TextLeft = $s2BlockX + $s2Pad;
        $s2TextRight = $s2Pad;
        $s2TextTop = 22;
        $mpdf->SetLeftMargin($s2TextLeft);
        $mpdf->SetRightMargin($s2TextRight);
        $s2TextW = $s2BlockW - 2 * $s2Pad;
        $s2LineH = 6.5;
        $mpdf->SetXY($s2TextLeft, $s2TextTop);
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', 'B', 32);
        $mpdf->Cell($s2TextW, 12, 'CONTEXTO', 0, 1, 'L');
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 26);
        $mpdf->Cell($s2TextW, 10, 'PROVINCIAL', 0, 1, 'L');
        $mpdf->Ln(8);
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', '', 13);
        // Tres párrafos con frases en negrita; segmentos unidos para evitar comas o "y" solos en una línea (API: Write + SetFont)
        $mpdf->Write($s2LineH, 'Santiago del Estero impulsa una ');
        $mpdf->SetFont('dejavusans', 'B', 13);
        $mpdf->Write($s2LineH, 'Oferta Exportable Provincial');
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->Write($s2LineH, ' para ');
        $mpdf->SetFont('dejavusans', 'B', 13);
        $mpdf->Write($s2LineH, 'visibilizar, ordenar y promover su entramado productivo');
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->Write($s2LineH, " ante organismos de promoción, misiones comerciales y compradores.\n");
        $mpdf->Ln(6);
        $mpdf->Write($s2LineH, 'Esta presentación reúne ');
        $mpdf->SetFont('dejavusans', 'B', 13);
        $mpdf->Write($s2LineH, 'información declarada por las empresas registradas');
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->Write($s2LineH, ', con foco en ');
        $mpdf->SetFont('dejavusans', 'B', 13);
        $mpdf->Write($s2LineH, "productos y servicios exportables.\n");
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->Ln(6);
        $mpdf->Write($s2LineH, 'La iniciativa busca ');
        $mpdf->SetFont('dejavusans', 'B', 13);
        $mpdf->Write($s2LineH, 'facilitar el acceso a datos clave, mejorar la difusión institucional y habilitar oportunidades de vinculación comercial');
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->Write($s2LineH, ', fortaleciendo una cultura exportadora ');
        $mpdf->SetFont('dejavusans', 'B', 13);
        $mpdf->Write($s2LineH, "moderna, inclusiva y federal.\n");
        $mpdf->SetFont('dejavusans', '', 13);
        // Badge número de página 02: área azul más ancha, "02" desplazado a la izquierda dentro del rectángulo
        $s2PageBoxW = 40;
        $s2PageBoxH = 13;
        $s2PageBoxX = $wMm - $s2PageBoxW;
        $s2PageBoxY = $hMm - $s2PageBoxH - 18;
        $mpdf->SetFillColor(141, 188, 220);
        $mpdf->Rect($s2PageBoxX, $s2PageBoxY, $s2PageBoxW, $s2PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s2PageBoxX, $s2PageBoxY + 2.2);
        $mpdf->Cell($s2PageBoxW - 26, 9, '02', 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // No incrementar $i: en la siguiente iteración (i=2) se hará AddPage() para el slide 3
    } elseif ($i === 2) {
        // Chunk 2 = slide 2 ya dibujado por API en i=1; solo añadir página para el slide 3
        $mpdf->AddPage();
    } elseif ($i === 3) {
        // Slide 3: Identidad provincial — izquierda blanco (30%) con logo+texto, derecha negro (70%, 100% alto), abajo tres imágenes identidad
        $mpdf->SetXY(0, 0);
        $s3ImgStripH = round($hMm * 0.60);
        $s3TopH = $hMm - $s3ImgStripH;
        $s3LeftW = round($wMm * 0.35);     // columna izquierda blanca 30%
        $s3RightW = $wMm - $s3LeftW;       // columna derecha negra 70%
        // Columna izquierda: fondo blanco (toda la altura)
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $s3LeftW, $hMm, 'F');
        // Bloque logo como en slide 2 pero sin recuadro azul, solo logo.png
        $s3LogoX = 24;
        $s3LogoY = 20;
        $s3LogoW = 64;
        $s3LogoH = 24;
        if (file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $maxLogoW = $s3LogoW;
            $maxLogoH = $s3LogoH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                if ($maxLogoH * $imgRatio <= $maxLogoW) {
                    $logoH = $maxLogoH;
                    $logoW = $maxLogoH * $imgRatio;
                } else {
                    $logoW = $maxLogoW;
                    $logoH = $maxLogoW / $imgRatio;
                }
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s3LogoX + ($s3LogoW - $logoW) / 2;
            $logoY = $s3LogoY + ($s3LogoH - $logoH) / 2;
            $mpdf->Image($pdfLogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        // Texto bajo el logo en la columna blanca
        $s3TextPad = 22;
        $s3TextLeft = $s3LogoX;
        $s3TextW = $s3LeftW - 2 * $s3TextPad;
        $s3TextY = $s3LogoY + $s3LogoH + 18;
        $mpdf->SetLeftMargin($s3TextLeft);
        $mpdf->SetRightMargin($wMm - $s3LeftW + $s3TextPad);
        $mpdf->SetXY($s3TextLeft, $s3TextY);
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->MultiCell($s3TextW, 6.5, 'Un territorio con capacidad productiva diversa y proyección para la vinculación comercial.', 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Columna derecha: fondo negro 70% ancho, 100% alto; título IDENTIDAD / PROVINCIAL como en diseño (azul claro, alineado a la izquierda con margen, PROVINCIAL ligeramente indentado)
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect($s3LeftW, 0, $s3RightW, $hMm, 'F');
        $s3TitleY = 28;
        $s3TitleMargin = 95;   // margen izquierdo del bloque de título (más a la derecha)
        $s3TitleIndent = 14;   // indentación de PROVINCIAL respecto a IDENTIDAD
        $mpdf->SetXY($s3LeftW + $s3TitleMargin, $s3TitleY);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 50);
        $mpdf->Cell($s3RightW - $s3TitleMargin - 20, 18, 'IDENTIDAD', 0, 1, 'L');
        $mpdf->SetXY($s3LeftW + $s3TitleMargin + $s3TitleIndent, $s3TitleY + 22);
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 50);
        $mpdf->Cell($s3RightW - $s3TitleMargin - $s3TitleIndent - 20, 16, 'PROVINCIAL', 0, 1, 'L');
        // Franja inferior: tres imágenes identidad — más anchas (28%), menos hueco (2%); recorte tipo cover (rellenan el bloque sin deformar)
        $s3SingleImgW = round($wMm * 0.26);
        $s3ImgGap = round($wMm * 0.025);
        $s3ImgY = $s3TopH;
        $s3Scale = 100 / 25.4;
        $s3TwPx = (int) max(1, round($s3SingleImgW * $s3Scale));
        $s3ThPx = (int) max(1, round($s3ImgStripH * $s3Scale));
        foreach ([0, 1, 2] as $idx) {
            $s3ImgX = $idx * ($s3SingleImgW + $s3ImgGap);
            $path = isset($identidadSlide3Paths[$idx]) ? $identidadSlide3Paths[$idx] : null;
            $s3ImgDrawn = false;
            if ($path && file_exists($path) && extension_loaded('gd')) {
                $info = @getimagesize($path);
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($path);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($path);
                } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($path);
                }
                if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $r = max($s3TwPx / $sw, $s3ThPx / $sh);
                    $cropW = (int) round($s3TwPx / $r);
                    $cropH = (int) round($s3ThPx / $r);
                    $srcX = (int) max(0, round(($sw - $cropW) / 2));
                    $srcY = (int) max(0, round(($sh - $cropH) / 2));
                    $cropW = min($cropW, $sw - $srcX);
                    $cropH = min($cropH, $sh - $srcY);
                    $dst = imagecreatetruecolor($s3TwPx, $s3ThPx);
                    if ($dst && $cropW > 0 && $cropH > 0 && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $s3TwPx, $s3ThPx, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/clasico_identidad_' . $idx . '_' . uniqid() . '.png';
                        if (imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $s3ImgX, $s3ImgY, $s3SingleImgW, $s3ImgStripH);
                            @unlink($tmp);
                            $s3ImgDrawn = true;
                        }
                        imagedestroy($dst);
                    } elseif ($dst) {
                        imagedestroy($dst);
                    }
                    imagedestroy($src);
                }
            }
            if (!$s3ImgDrawn && $path && file_exists($path)) {
                $mpdf->Image($path, $s3ImgX, $s3ImgY, $s3SingleImgW, $s3ImgStripH);
            } elseif (!$path || !file_exists($path)) {
                $mpdf->SetFillColor(200, 200, 200);
                $mpdf->Rect($s3ImgX, $s3ImgY, $s3SingleImgW, $s3ImgStripH, 'F');
            }
        }
        // Bloque blanco sobre el borde derecho: 5% ancho, 100% alto (superpuesto al negro)
        $s3WhiteStripW = round($wMm * 0.05);
        $s3WhiteStripX = $wMm - $s3WhiteStripW;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s3WhiteStripX, 0, $s3WhiteStripW, $hMm, 'F');
        // Badge número de página 03
        $s3PageBoxW = 40;
        $s3PageBoxH = 13;
        $s3PageBoxX = $wMm - $s3PageBoxW;
        $s3PageBoxY = $hMm - $s3PageBoxH - 18;
        $mpdf->SetFillColor(141, 188, 220);
        $mpdf->Rect($s3PageBoxX, $s3PageBoxY, $s3PageBoxW, $s3PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s3PageBoxX, $s3PageBoxY + 2.2);
        $mpdf->Cell($s3PageBoxW - 26, 9, '03', 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 4) {
        // Slide 4 intro: Empresas y productos exportables — como en diseño (logo como s3, 3 imágenes Empresa, título y texto)
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s4LeftW = round($wMm * 0.38);   // columna izquierda (logo + 2 imágenes)
        $s4RightW = $wMm - $s4LeftW;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        // Bloque logo igual que slide 3 (sin recuadro azul, logo.png)
        $s4LogoX = 24;
        $s4LogoY = 20;
        $s4LogoW = 64;
        $s4LogoH = 24;
        if (file_exists($pdfLogoPath)) {
            $imgSize = @getimagesize($pdfLogoPath);
            $maxLogoW = $s4LogoW;
            $maxLogoH = $s4LogoH;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                $logoW = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH * $imgRatio : $maxLogoW;
                $logoH = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH : $maxLogoW / $imgRatio;
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s4LogoX + ($s4LogoW - $logoW) / 2;
            $logoY = $s4LogoY + ($s4LogoH - $logoH) / 2;
            $mpdf->Image($pdfLogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        // Dos imágenes Empresa apiladas a la izquierda: recorte tipo cover (rellenan el bloque sin deformar)
        $s4LeftImgW = round($wMm * 0.20);
        $s4LeftImgH = round($hMm * 0.34);
        $s4LeftImgGap = 8;
        $s4LeftImgX = 18;
        $s4LeftImgY0 = $s4LogoY + $s4LogoH + 16;
        $s4Scale = 100 / 25.4;
        $s4LeftTwPx = (int) max(1, round($s4LeftImgW * $s4Scale));
        $s4LeftThPx = (int) max(1, round($s4LeftImgH * $s4Scale));
        for ($k = 0; $k < 2; $k++) {
            $path = isset($empresaSlide4Paths[$k]) ? $empresaSlide4Paths[$k] : null;
            $y = $s4LeftImgY0 + $k * ($s4LeftImgH + $s4LeftImgGap);
            $s4LeftDrawn = false;
            if ($path && file_exists($path) && extension_loaded('gd')) {
                $info = @getimagesize($path);
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($path);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($path);
                } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($path);
                }
                if ($src) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $r = max($s4LeftTwPx / $sw, $s4LeftThPx / $sh);
                    $cropW = (int) round($s4LeftTwPx / $r);
                    $cropH = (int) round($s4LeftThPx / $r);
                    $srcX = (int) max(0, round(($sw - $cropW) / 2));
                    $srcY = (int) max(0, round(($sh - $cropH) / 2));
                    $cropW = min($cropW, $sw - $srcX);
                    $cropH = min($cropH, $sh - $srcY);
                    $dst = imagecreatetruecolor($s4LeftTwPx, $s4LeftThPx);
                    if ($dst && $cropW > 0 && $cropH > 0 && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $s4LeftTwPx, $s4LeftThPx, $cropW, $cropH)) {
                        $tmp = sys_get_temp_dir() . '/clasico_empresa_left_' . $k . '_' . uniqid() . '.png';
                        if (imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, $s4LeftImgX, $y, $s4LeftImgW, $s4LeftImgH);
                            @unlink($tmp);
                            $s4LeftDrawn = true;
                        }
                        imagedestroy($dst);
                    } elseif ($dst) {
                        imagedestroy($dst);
                    }
                    imagedestroy($src);
                }
            }
            if (!$s4LeftDrawn && $path && file_exists($path)) {
                $mpdf->Image($path, $s4LeftImgX, $y, $s4LeftImgW, $s4LeftImgH);
            } elseif (!$path || !file_exists($path)) {
                $mpdf->SetFillColor(230, 230, 230);
                $mpdf->Rect($s4LeftImgX, $y, $s4LeftImgW, $s4LeftImgH, 'F');
            }
        }
        // Imagen grande: recorte tipo cover; 68% ancho, 30% alto; margen superior y derecho
        $s4BigImgW = round($wMm * 0.68);
        $s4BigImgH = round($hMm * 0.30);
        $s4BigImgTopMargin = 18;
        $s4BigImgRightMargin = 22;
        $s4BigImgX = $wMm - $s4BigImgRightMargin - $s4BigImgW;
        $s4BigImgY = $s4BigImgTopMargin;
        $s4BigTwPx = (int) max(1, round($s4BigImgW * $s4Scale));
        $s4BigThPx = (int) max(1, round($s4BigImgH * $s4Scale));
        $pathBig = isset($empresaSlide4Paths[2]) ? $empresaSlide4Paths[2] : null;
        $s4BigDrawn = false;
        if ($pathBig && file_exists($pathBig) && extension_loaded('gd')) {
            $info = @getimagesize($pathBig);
            $ext = strtolower(pathinfo($pathBig, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($pathBig);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($pathBig);
            } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($pathBig);
            }
            if ($src) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $r = max($s4BigTwPx / $sw, $s4BigThPx / $sh);
                $cropW = (int) round($s4BigTwPx / $r);
                $cropH = (int) round($s4BigThPx / $r);
                $srcX = (int) max(0, round(($sw - $cropW) / 2));
                // Recorte un poco más arriba (mostrar más parte superior de la imagen)
                $srcY = (int) max(0, round(($sh - $cropH) * 0.28));
                $cropW = min($cropW, $sw - $srcX);
                $cropH = min($cropH, $sh - $srcY);
                $dst = imagecreatetruecolor($s4BigTwPx, $s4BigThPx);
                if ($dst && $cropW > 0 && $cropH > 0 && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $s4BigTwPx, $s4BigThPx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/clasico_empresa_big_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, $s4BigImgX, $s4BigImgY, $s4BigImgW, $s4BigImgH);
                        @unlink($tmp);
                        $s4BigDrawn = true;
                    }
                    imagedestroy($dst);
                } elseif ($dst) {
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        }
        if (!$s4BigDrawn && $pathBig && file_exists($pathBig)) {
            $mpdf->Image($pathBig, $s4BigImgX, $s4BigImgY, $s4BigImgW, $s4BigImgH);
        } elseif (!$pathBig || !file_exists($pathBig)) {
            $mpdf->SetFillColor(230, 230, 230);
            $mpdf->Rect($s4BigImgX, $s4BigImgY, $s4BigImgW, $s4BigImgH, 'F');
        }
        // Bloque de texto: más abajo, fuentes más grandes
        $s4TextTop = $s4BigImgY + $s4BigImgH + 38;
        $s4TextPadLeft = 10;
        $s4TextPadRight = 22;
        $s4TextW = $wMm - $s4BigImgX - $s4TextPadRight;
        $s4ParagraphW = round($s4TextW * 0.72);
        $mpdf->SetLeftMargin($s4BigImgX + $s4TextPadLeft);
        $mpdf->SetRightMargin($s4TextPadRight);
        $mpdf->SetXY($s4BigImgX + $s4TextPadLeft, $s4TextTop);
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', 'B', 44);
        $mpdf->Cell($s4TextW, 16, 'EMPRESAS Y', 0, 1, 'L');
        $mpdf->Ln(4);
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 44);
        $mpdf->Cell($s4TextW, 16, 'PRODUCTOS EXPORTABLES', 0, 1, 'L');
        $mpdf->Ln(10);
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->MultiCell($s4ParagraphW, 7, 'Empresas registradas y productos/servicios exportables declarados para su difusión institucional.', 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Badge número de página 04
        $s4PageBoxW = 40;
        $s4PageBoxH = 13;
        $s4PageBoxX = $wMm - $s4PageBoxW;
        $s4PageBoxY = $hMm - $s4PageBoxH - 18;
        $mpdf->SetFillColor(141, 188, 220);
        $mpdf->Rect($s4PageBoxX, $s4PageBoxY, $s4PageBoxW, $s4PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s4PageBoxX, $s4PageBoxY + 2.2);
        $mpdf->Cell($s4PageBoxW - 26, 9, '04', 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Una slide por empresa: izquierda blanca (título + datos), derecha bloque negro 50% con márgenes y logo de la empresa + logo provincial arriba derecha sin recuadro azul
        $pageNum = 5;
        foreach ($companies as $emp) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $cid = (int) $emp['id'];
            $s5LeftW = round($wMm / 2);
            $s5RightW = $wMm - $s5LeftW;
            $s5BlackPadT = 10;
            $s5BlackPadB = 10;
            $s5BlackPadR = 14;
            $s5BlackX = $s5LeftW;
            $s5BlackW = $s5RightW - $s5BlackPadR;
            $s5BlackY = $s5BlackPadT;
            $s5BlackH = $hMm - $s5BlackPadT - $s5BlackPadB;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, 0, $s5LeftW, $hMm, 'F');
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect($s5BlackX, $s5BlackY, $s5BlackW, $s5BlackH, 'F');
            // Logo provincial arriba a la derecha del bloque negro (como slide 1 pero sin recuadro azul)
            $s5ProvLogoW = 64;
            $s5ProvLogoH = 24;
            $s5ProvLogoX = $wMm - $s5BlackPadR - $s5ProvLogoW - 10;
            $s5ProvLogoY = $s5BlackY + 12;
            $s5ProvLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
            if (file_exists($s5ProvLogoPath)) {
                $imgSize = @getimagesize($s5ProvLogoPath);
                $maxW = $s5ProvLogoW;
                $maxH = $s5ProvLogoH;
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $ratio = $imgSize[0] / $imgSize[1];
                    $logoW = ($maxH * $ratio <= $maxW) ? $maxH * $ratio : $maxW;
                    $logoH = ($maxH * $ratio <= $maxW) ? $maxH : $maxW / $ratio;
                } else {
                    $logoW = $maxW;
                    $logoH = $maxH;
                }
                $lx = $s5ProvLogoX + ($s5ProvLogoW - $logoW) / 2;
                $ly = $s5ProvLogoY + ($s5ProvLogoH - $logoH) / 2;
                $mpdf->Image($s5ProvLogoPath, $lx, $ly, $logoW, $logoH);
            }
            // Logo de la empresa centrado en el bloque negro (debajo del logo provincial, algo más bajo)
            $s5CompLogoSize = min(90, $s5BlackW - 30, $s5BlackH - 80);
            $s5CompLogoX = $s5BlackX + ($s5BlackW - $s5CompLogoSize) / 2;
            $s5CompLogoY = $s5BlackY + 62;
            $compLogoPath = $logosPorEmpresa[$cid] ?? $imagenesPorEmpresa[$cid] ?? null;
            if ($compLogoPath && file_exists($compLogoPath)) {
                $mpdf->Image($compLogoPath, $s5CompLogoX, $s5CompLogoY, $s5CompLogoSize, $s5CompLogoSize);
            }
            // Izquierda: título "NOMBRE DE LA" / nombre empresa (azul) alineado debajo, luego lista con líneas azules debajo de cada fila (sin línea encima de la primera)
            $s5Pad = 24;
            $s5TextW = $s5LeftW - 2 * $s5Pad;
            $mpdf->SetLeftMargin($s5Pad);
            $mpdf->SetRightMargin($wMm - $s5LeftW + $s5Pad);
            $s5TitleY = 28;
            $mpdf->SetXY($s5Pad, $s5TitleY);
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', 38);
            $mpdf->Cell($s5TextW, 13, 'NOMBRE DE LA', 0, 1, 'L');
            $mpdf->Ln(5);
            $mpdf->SetX($s5Pad);
            $mpdf->SetTextColor(141, 188, 220);
            $mpdf->SetFont('dejavusans', 'B', 34);
            $nombreEmpresa = $emp['name'] ?? '';
            $mpdf->Cell($s5TextW, 13, function_exists('mb_strtoupper') ? mb_strtoupper($nombreEmpresa) : strtoupper($nombreEmpresa), 0, 1, 'L');
            $mpdf->Ln(28);
            $s5LineH = 10;
            $s5LabelH = 7;
            $s5LineColor = [141, 188, 220];
            $s5Rows = [
                ['ACTIVIDAD', 'PRINCIPAL', $emp['main_activity'] ?? '-'],
                ['LOCALIDAD', null, $localidadPorEmpresa[$cid] ?? '-'],
                ['SITIO WEB', null, $emp['website'] ?? '-'],
                ['REDES', 'SOCIALES', isset($redesPorEmpresa[$cid]) ? implode(' ', $redesPorEmpresa[$cid]) : '-'],
                ['AÑO DE', 'INICIO', !empty($emp['start_date']) ? date('Y', (int)$emp['start_date']) : '-'],
            ];
            $s5LabelW = $s5TextW * 0.38;
            $s5ValW = $s5TextW * 0.62;
            $s5ValX = $s5Pad + $s5LabelW;
            $s5GapAfterText = 2;
            $s5Y = $s5TitleY + 13 + 5 + 13 + 28;
            foreach ($s5Rows as $row) {
                $line1 = $row[0];
                $line2 = $row[1];
                $value = $row[2];
                $mpdf->SetXY($s5Pad, $s5Y);
                $mpdf->SetTextColor(60, 60, 60);
                $mpdf->SetFont('dejavusans', 'B', 14);
                if ($line2 !== null) {
                    $mpdf->Cell($s5LabelW, $s5LabelH, $line1, 0, 1, 'L');
                    $mpdf->SetX($s5Pad);
                    $mpdf->Cell($s5LabelW, $s5LabelH, $line2, 0, 0, 'L');
                } else {
                    $mpdf->Cell($s5LabelW, $s5LineH, $line1, 0, 0, 'L');
                }
                $mpdf->SetXY($s5ValX, $s5Y);
                $mpdf->SetFont('dejavusans', '', 12);
                $mpdf->SetTextColor(0, 0, 0);
                $valStr = is_string($value) ? $value : (string)$value;
                $rowH = ($line2 !== null) ? $s5LabelH * 2 : $s5LineH;
                if (mb_strlen($valStr) > 45) {
                    $mpdf->MultiCell($s5ValW, 5, $valStr, 0, 'R');
                    $rowH = max($rowH, 15);
                } else {
                    $mpdf->Cell($s5ValW, $rowH, $valStr, 0, 1, 'R');
                }
                $s5Y += $rowH + $s5GapAfterText;
                $mpdf->SetDrawColor($s5LineColor[0], $s5LineColor[1], $s5LineColor[2]);
                $mpdf->SetLineWidth(0.4);
                $mpdf->Line($s5Pad, $s5Y, $s5Pad + $s5TextW, $s5Y);
                $s5Y += 3;
            }
            $mpdf->SetLeftMargin(0);
            $mpdf->SetRightMargin(0);
            $mpdf->SetDrawColor(0, 0, 0);
            // Badge número de página
            $s5PageBoxW = 40;
            $s5PageBoxH = 13;
            $s5PageBoxX = $wMm - $s5PageBoxW;
            $s5PageBoxY = $hMm - $s5PageBoxH - 18;
            $mpdf->SetFillColor(141, 188, 220);
            $mpdf->Rect($s5PageBoxX, $s5PageBoxY, $s5PageBoxW, $s5PageBoxH, 'F');
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', 14);
            $mpdf->SetXY($s5PageBoxX, $s5PageBoxY + 2.2);
            $mpdf->Cell($s5PageBoxW - 26, 9, sprintf('%02d', $pageNum), 0, 0, 'R');
            $pageNum++;
        }
    } elseif ($i === 5) {
        // Slide(s) Productos y servicios destacados: izquierda 70% (título + lista de productos con thumb, datos y descripción), derecha 30% (logo sin azul, imagen Producto 25%×50%, bloque negro 30%×45% con número)
        $productoSlidesChunks = array_chunk($productosParaSlides, 3);
        $prodLeftW = round($wMm * 0.70);
        $prodRightW = $wMm - $prodLeftW;
        $prodImgW = round($wMm * 0.23);
        $prodImgH = round($hMm * 0.60);
        $prodBlackW = round($wMm * 0.35);
        $prodBlackH = round($hMm * 0.42);
        $prodBlackX = $wMm - $prodBlackW;
        $prodBlackY = $hMm - $prodBlackH;
        $prodPageNum = 5 + count($companies);
        foreach ($productoSlidesChunks as $idx => $chunk) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, 0, $prodLeftW, $hMm, 'F');
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect($prodBlackX, $prodBlackY, $prodBlackW, $prodBlackH, 'F');
            $prodImgPadR = 24;
            $prodImgX = $wMm - $prodImgW - $prodImgPadR;
            $prodImgY = $prodBlackY - $prodImgH / 2 - 12;
            if (!empty($productoImgCandidates)) {
                $prodImgPath = $productoImgCandidates[$idx % count($productoImgCandidates)];
                if (file_exists($prodImgPath)) {
                    $prodImgOutPath = null;
                    if (extension_loaded('gd')) {
                        $info = @getimagesize($prodImgPath);
                        $ext = strtolower(pathinfo($prodImgPath, PATHINFO_EXTENSION));
                        $src = false;
                        if ($info && $info[2] === IMAGETYPE_JPEG) {
                            $src = @imagecreatefromjpeg($prodImgPath);
                        } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                            $src = @imagecreatefrompng($prodImgPath);
                        } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                            $src = @imagecreatefromwebp($prodImgPath);
                        }
                        if ($src && !empty($info[0]) && !empty($info[1])) {
                            $sw = imagesx($src);
                            $sh = imagesy($src);
                            $pxPerMm = 96 / 25.4;
                            $tw = (int) round($prodImgW * $pxPerMm);
                            $th = (int) round($prodImgH * $pxPerMm);
                            $scale = max($tw / $sw, $th / $sh);
                            $srcCropW = (int) round($tw / $scale);
                            $srcCropH = (int) round($th / $scale);
                            $srcX = (int) max(0, ($sw - $srcCropW) / 2);
                            $srcY = (int) max(0, ($sh - $srcCropH) / 2);
                            $dst = @imagecreatetruecolor($tw, $th);
                            if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $tw, $th, $srcCropW, $srcCropH)) {
                                $tmp = sys_get_temp_dir() . '/clasico_producto_' . uniqid() . '.png';
                                if (imagepng($dst, $tmp)) {
                                    $prodImgOutPath = $tmp;
                                }
                                imagedestroy($dst);
                            }
                            imagedestroy($src);
                        }
                    }
                    if ($prodImgOutPath && file_exists($prodImgOutPath)) {
                        $mpdf->Image($prodImgOutPath, $prodImgX, $prodImgY, $prodImgW, $prodImgH);
                        @unlink($prodImgOutPath);
                    } else {
                        $mpdf->Image($prodImgPath, $prodImgX, $prodImgY, $prodImgW, $prodImgH);
                    }
                }
            }
            if (file_exists($pdfLogoPath)) {
                $prodLogoW = 64;
                $prodLogoH = 24;
                $prodLogoPadR = 14;
                $prodLogoPadTop = 12;
                $prodLogoX = $wMm - $prodLogoPadR - $prodLogoW - 10;
                $prodLogoY = $prodLogoPadTop + 10;
                $imgSize = @getimagesize($pdfLogoPath);
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $ratio = $imgSize[0] / $imgSize[1];
                    $maxW = $prodLogoW;
                    $maxH = $prodLogoH;
                    $lw = ($maxH * $ratio <= $maxW) ? $maxH * $ratio : $maxW;
                    $lh = ($maxH * $ratio <= $maxW) ? $maxH : $maxW / $ratio;
                } else {
                    $lw = $prodLogoW;
                    $lh = $prodLogoH;
                }
                $mpdf->Image($pdfLogoPath, $prodLogoX + ($prodLogoW - $lw) / 2, $prodLogoY + ($prodLogoH - $lh) / 2, $lw, $lh);
            }
            $prodPad = 22;
            $prodTextW = $prodLeftW - 2 * $prodPad - 48;
            $mpdf->SetLeftMargin($prodPad);
            $mpdf->SetRightMargin($wMm - $prodLeftW + $prodPad);
            $prodTitleY = 34;
            $mpdf->SetXY($prodPad, $prodTitleY);
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', 42);
            $mpdf->Cell($prodTextW, 15, 'Productos y servicios', 0, 1, 'L');
            $mpdf->Ln(6);
            $mpdf->SetTextColor(141, 188, 220);
            $mpdf->SetFont('dejavusans', 'B', 36);
            $mpdf->Cell($prodTextW, 15, 'Destacados', 0, 1, 'L');
            $mpdf->Ln(18);
            $prodThumbW = 52;
            $prodThumbH = 38;
            $prodLineColor = [221, 153, 153];
            $prodY = $prodTitleY + 15 + 6 + 15 + 10;
            foreach ($chunk as $k => $prod) {
                $pid = (int) $prod['id'];
                $mpdf->SetXY($prodPad, $prodY);
                $imgPath = $imagenesPorProducto[$pid] ?? null;
                if ($imgPath && file_exists($imgPath)) {
                    $info = @getimagesize($imgPath);
                    if (!empty($info[0]) && !empty($info[1])) {
                        $pxToMm = 25.4 / 96;
                        $imgWmm = $info[0] * $pxToMm;
                        $imgHmm = $info[1] * $pxToMm;
                        $scale = min($prodThumbW / $imgWmm, $prodThumbH / $imgHmm);
                        $iw = $imgWmm * $scale;
                        $ih = $imgHmm * $scale;
                        $mpdf->Image($imgPath, $prodPad, $prodY, $iw, $ih);
                    } else {
                        $mpdf->Image($imgPath, $prodPad, $prodY, $prodThumbW, $prodThumbH);
                    }
                }
                $prodContentX = $prodPad + $prodThumbW + 10;
                $prodContentW = $prodTextW - $prodThumbW - 10;
                $mpdf->SetXY($prodContentX, $prodY);
                $mpdf->SetTextColor(0, 0, 0);
                $mpdf->SetFont('dejavusans', 'B', 16);
                $mpdf->Cell($prodContentW * 0.55, 9, $prod['name'] ?? '', 0, 1, 'L');
                $mpdf->Ln(4);
                $mpdf->SetX($prodContentX);
                $mpdf->SetFont('dejavusans', 'B', 11);
                $mpdf->SetTextColor(80, 80, 80);
                $mpdf->Cell($prodContentW * 0.55, 7, 'EXPORTACIÓN ANUAL:', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 10);
                $mpdf->SetTextColor(0, 0, 0);
                $mpdf->Cell($prodContentW * 0.45, 7, trim($prod['annual_export'] ?? '-') ?: '-', 0, 1, 'L');
                $mpdf->Ln(2);
                $mpdf->SetX($prodContentX);
                $mpdf->SetFont('dejavusans', 'B', 11);
                $mpdf->SetTextColor(80, 80, 80);
                $mpdf->Cell($prodContentW * 0.55, 7, 'CERTIFICACIONES:', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 10);
                $mpdf->SetTextColor(0, 0, 0);
                $certStr = trim($prod['certifications'] ?? '-') ?: '-';
                $mpdf->Cell($prodContentW * 0.45, 7, (mb_strlen($certStr) > 28 ? mb_substr($certStr, 0, 27) . '…' : $certStr), 0, 1, 'L');
                $mpdf->Ln(2);
                $mpdf->SetX($prodContentX);
                $mpdf->SetFont('dejavusans', 'B', 11);
                $mpdf->SetTextColor(80, 80, 80);
                $mpdf->Cell($prodContentW * 0.55, 7, 'BREVE DESCRIPCIÓN:', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 10);
                $mpdf->SetTextColor(0, 0, 0);
                $descStr = trim($prod['description'] ?? '') ?: '-';
                $descStr = mb_strlen($descStr) > 80 ? mb_substr($descStr, 0, 79) . '…' : $descStr;
                $mpdf->MultiCell($prodContentW * 0.45, 4.5, $descStr, 0, 'L');
                $prodY += 50;
                $mpdf->SetDrawColor($prodLineColor[0], $prodLineColor[1], $prodLineColor[2]);
                $mpdf->SetLineWidth(0.3);
                $mpdf->Line($prodPad, $prodY, $prodPad + $prodTextW, $prodY);
                $prodY += 6;
            }
            $mpdf->SetLeftMargin(0);
            $mpdf->SetRightMargin(0);
            $mpdf->SetDrawColor(0, 0, 0);
            $prodPageBoxW = 40;
            $prodPageBoxH = 13;
            $prodPageBoxX = $wMm - $prodPageBoxW;
            $prodPageBoxY = $hMm - $prodPageBoxH - 18;
            $mpdf->SetFillColor(141, 188, 220);
            $mpdf->Rect($prodPageBoxX, $prodPageBoxY, $prodPageBoxW, $prodPageBoxH, 'F');
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', 14);
            $mpdf->SetXY($prodPageBoxX, $prodPageBoxY + 2.2);
            $mpdf->Cell($prodPageBoxW - 26, 9, sprintf('%02d', $prodPageNum), 0, 0, 'R');
            $prodPageNum++;
        }
    } elseif ($i === 6) {
        // Slide Contacto: como slide 1 pero espejado — imagen a la izquierda (misma Portada+oscurecido), franja derecha blanco+azul; bloque logo igual que slide 1; CONTACTO y datos más abajo y título más grande
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s7FullH = $hMm;
        $s7RightColW = round($wMm * 0.04);
        $s7ImageW = $wMm - $s7RightColW;
        $s7BlueH = round($s7FullH * 0.75);
        $s7WhiteH = $s7FullH - $s7BlueH;
        $s7Overlap = 1;
        $s7BgPath = ($backgroundContactPath && file_exists($backgroundContactPath)) ? $backgroundContactPath : $backgroundSlide1Path;
        $mpdf->SetFillColor(30, 30, 40);
        $mpdf->Rect(0, 0, $s7ImageW + $s7Overlap, $s7FullH, 'F');
        $s7BgStretchedPath = null;
        if ($s7BgPath && file_exists($s7BgPath) && extension_loaded('gd')) {
            $info = @getimagesize($s7BgPath);
            $s7BgExt = strtolower(pathinfo($s7BgPath, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($s7BgPath);
            } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($s7BgPath);
            } elseif (($s7BgExt === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($s7BgPath);
            }
            if ($src && !empty($info[0]) && !empty($info[1])) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $scale = 100 / 25.4;
                $dw = (int) max(1, round($s7ImageW * $scale));
                $dh = (int) max(1, round($dw * $s7FullH / $s7ImageW));
                $dst = imagecreatetruecolor($dw, $dh);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh)) {
                    if (function_exists('imagefilter')) {
                        @imagefilter($dst, IMG_FILTER_BRIGHTNESS, -85);
                    }
                    $tmp = sys_get_temp_dir() . '/clasico_contacto_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $s7BgStretchedPath = $tmp;
                    }
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
        }
        if ($s7BgStretchedPath && file_exists($s7BgStretchedPath)) {
            $mpdf->Image($s7BgStretchedPath, 0, 0, $s7ImageW + $s7Overlap, $s7FullH);
            @unlink($s7BgStretchedPath);
        } elseif ($s7BgPath && file_exists($s7BgPath)) {
            $mpdf->Image($s7BgPath, 0, 0, $s7ImageW + $s7Overlap, $s7FullH);
        } else {
            $mpdf->SetFillColor(30, 30, 40);
            $mpdf->Rect(0, 0, $s7ImageW + $s7Overlap, $s7FullH, 'F');
        }
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s7ImageW - $s7Overlap, 0, $s7RightColW + $s7Overlap, $s7WhiteH, 'F');
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s7ImageW - $s7Overlap, $s7WhiteH, $s7RightColW + $s7Overlap, $s7BlueH, 'F');
        $s7BadgeW = 64;
        $s7BadgeH = 24;
        $s7BadgeX = $s7ImageW - $s7BadgeW - 24;
        $s7BadgeY = 20;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s7BadgeX, $s7BadgeY, $s7BadgeW, $s7BadgeH, 'F');
        $s7LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s7LogoPath)) {
            $imgSize = @getimagesize($s7LogoPath);
            $maxLogoW = $s7BadgeW - 4;
            $maxLogoH = $s7BadgeH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                if ($maxLogoH * $imgRatio <= $maxLogoW) {
                    $logoH = $maxLogoH;
                    $logoW = $maxLogoH * $imgRatio;
                } else {
                    $logoW = $maxLogoW;
                    $logoH = $maxLogoW / $imgRatio;
                }
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s7BadgeX + ($s7BadgeW - $logoW) / 2;
            $logoY = $s7BadgeY + ($s7BadgeH - $logoH) / 2;
            $mpdf->Image($s7LogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        $s7PadLeft = 24;
        $s7PadTop = 82;
        $s7TextW = $s7ImageW - $s7PadLeft - 24;
        $mpdf->SetLeftMargin($s7PadLeft);
        $mpdf->SetRightMargin($wMm - $s7ImageW + $s7PadLeft);
        $mpdf->SetXY($s7PadLeft, $s7PadTop);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 95);
        $mpdf->Cell($s7TextW, 32, 'CONTACTO', 0, 1, 'L');
        $contacto = $configInstitucional;
        $s7Lines = [
            trim($contacto['mail'] ?? ''),
            trim($contacto['sitio_web'] ?? ''),
            trim($contacto['localidad_direccion'] ?? ''),
        ];
        $s7Lines = array_filter($s7Lines);
        if (empty($s7Lines)) {
            $s7Lines = ['—', '—', '—'];
        }
        $s7LineH = 10;
        $s7LineGap = 8;
        $s7ContactTopMargin = 28;
        $s7Y = $s7PadTop + 24 + $s7ContactTopMargin;
        foreach ($s7Lines as $line) {
            $mpdf->SetXY($s7PadLeft, $s7Y);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', 10);
            $mpdf->Cell(6, $s7LineH, "\xE2\x80\xA2", 0, 0, 'L');
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', '', 14);
            $mpdf->Cell($s7TextW - 20, $s7LineH, $line, 0, 1, 'L');
            $s7Y += $s7LineH + $s7LineGap;
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $s7PageBoxW = 40;
        $s7PageBoxH = 13;
        $s7PageBoxX = $wMm - $s7PageBoxW;
        $s7PageBoxY = $hMm - $s7PageBoxH - 18;
        $s7PageNum = $mpdf->PageNo();
        $mpdf->SetFillColor(141, 188, 220);
        $mpdf->Rect($s7PageBoxX, $s7PageBoxY, $s7PageBoxW, $s7PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s7PageBoxX, $s7PageBoxY + 2.2);
        $mpdf->Cell($s7PageBoxW - 26, 9, sprintf('%02d', $s7PageNum), 0, 0, 'R');
    } else {
        $mpdf->AddPage();
        $mpdf->WriteHTML($htmlChunks[$i]);
    }
}
$nombreArchivo = 'Oferta_Exportable_' . preg_replace('/\s+/', '_', $configInstitucional['nombre_provincia']) . '_' . $configInstitucional['periodo_ano'] . '.pdf';

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

    // Constantes del slide 1
    $redBarMm = 5;
    $contentH = $slideH - $redBarMm;
    $pullUpMm = $contentH - 25;

    // Estilos reutilizables (en mm para que mPDF no deje huecos)
    $tableStyle = "width:{$slideW}mm;height:{$slideH}mm;table-layout:fixed;";
    $cellContentStyle = "width:{$slideW}mm;height:{$contentH}mm;padding:0;vertical-align:top;";
    $cellLogoRowStyle = "width:{$slideW}mm;height:0;padding:0;vertical-align:top;overflow:visible;";
    $cellRedBarStyle = "width:{$slideW}mm;height:{$redBarMm}mm;padding:0;background:#FF0000;"; // mismo color y altura que API slide 1
    $imgBgStyle = "width:{$slideW}mm;height:{$contentH}mm;display:block;object-fit:fill;";
    $logoOuterStyle = "margin-top:-{$pullUpMm}mm;margin-left:10px;display:inline-block;";
    $logoInnerStyle = "background:#fff;padding:10px 12px;";
    $logoImgStyle = "height:50px;max-width:200px;display:block;";

    // Bloque del logo (dos contenedores: exterior = márgenes, interior = fondo blanco + padding)
    $logoBox = '';
    if ($pdfLogoUri) {
        $logoBox = '<div style="' . $logoOuterStyle . '">'
            . '<div style="' . $logoInnerStyle . '">'
            . '<img src="' . $pdfLogoUri . '" alt="Logo" style="' . $logoImgStyle . '" />'
            . '</div></div>';
    }

    // Fila 1: solo imagen de fondo
    $row1Content = $backgroundSlide1Uri
        ? '<img src="' . $backgroundSlide1Uri . '" alt="" style="' . $imgBgStyle . '" />'
        : '<div style="width:' . $slideW . 'mm;height:' . $contentH . 'mm;background:#000066;"></div>';

    // Fila 2: logo (height:0 + margin-top negativo para intentar superponer; mPDF puede ignorarlo)
    // Fila 3: franja roja
    $s1 = '<div class="slide">'
        . '<table cellpadding="0" cellspacing="0" border="0" style="' . $tableStyle . '">'
        . '<tr><td style="' . $cellContentStyle . '">' . $row1Content . '</td></tr>'
        . '<tr><td style="' . $cellLogoRowStyle . '">' . $logoBox . '</td></tr>'
        . '<tr><td style="' . $cellRedBarStyle . '"></td></tr>'
        . '</table></div>';

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
