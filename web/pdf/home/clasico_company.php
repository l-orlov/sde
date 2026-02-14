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
$assetsDir = __DIR__ . '/../assets';
$logoPath = $webRoot . '/img/logo.svg';
$catImages = glob($webRoot . '/img/landing/*.png');
$catImagePath = !empty($catImages) ? $catImages[0] : null;
// Fondo del primer slide: solo Empresa0.jpg
$backgroundSlide1Path = $assetsDir . '/Empresa0.jpg';
if (!file_exists($backgroundSlide1Path)) {
    $backgroundSlide1Path = null;
}
$portadaCandidates = [];
foreach (['Portada1.webp', 'portada2.webp', 'portada3.jpg', 'portada4.jpg', 'portada5.jpg', 'portada6.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $portadaCandidates[] = $p;
    }
}
$backgroundContactPath = null;
if (!empty($portadaCandidates)) {
    $backgroundContactPath = $portadaCandidates[array_rand($portadaCandidates)];
}
// Fondo para slide Competitividad (solo portada3, portada2, portada6)
$competitividadBgCandidates = [];
foreach (['portada3.jpg', 'portada2.webp', 'portada6.jpg'] as $name) {
    $p = $assetsDir . '/' . $name;
    if (file_exists($p)) {
        $competitividadBgCandidates[] = $p;
    }
}
$competitividadBgPath = !empty($competitividadBgCandidates) ? $competitividadBgCandidates[array_rand($competitividadBgCandidates)] : (!empty($portadaCandidates) ? $portadaCandidates[array_rand($portadaCandidates)] : null);
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
$iconYesPath = $assetsDir . '/icon_yes.png';
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

// Dirección completa primera empresa (para slide Perfil: departamento, domicilio)
$perfilDepartamento = '';
$perfilDomicilio = '';
if (!empty($companies[0]['id'])) {
    $fcid = (int) $companies[0]['id'];
    $r = @mysqli_query($link, "SELECT * FROM company_addresses WHERE company_id = $fcid ORDER BY id ASC LIMIT 1");
    if ($r && ($row = mysqli_fetch_assoc($r))) {
        if (!empty($row['department'])) {
            $perfilDepartamento = trim($row['department']);
        }
        $st = isset($row['street']) ? trim((string)$row['street']) : '';
        $num = isset($row['street_number']) ? trim((string)$row['street_number']) : '';
        if ($st !== '' || $num !== '') {
            $perfilDomicilio = trim($st . ' ' . $num);
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

// Escribir HTML en trozos para no superar pcre.backtrack_limit (~1M)
$writeHtmlChunks = function ($mpdf, $html, $maxLen = 400000) {
    $len = strlen($html);
    if ($len <= $maxLen) {
        $mpdf->WriteHTML($html);
        return;
    }
    $offset = 0;
    while ($offset < $len) {
        $chunk = substr($html, $offset, $maxLen);
        $cut = strlen($chunk);
        $lastClose = strrpos($chunk, '>');
        if ($lastClose !== false && $lastClose > $cut / 2) {
            $chunk = substr($html, $offset, $lastClose + 1);
            $offset += $lastClose + 1;
        } else {
            $offset += $cut;
        }
        $mpdf->WriteHTML($chunk);
    }
};

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

for ($i = 0; $i < 5; $i++) {
    if ($i === 0) {
        $writeHtmlChunks($mpdf, $htmlChunks[0]);
    } elseif ($i === 1) {
        // Slide 1: izq. dos bloques (blanco arriba ~20%, azul abajo ~80%); fondo Portada; texto EMPRESA/EMPRENDIMIENTO y ACTIVIDAD · LOCALIDAD; badge: logo empresa + logo SDE; número página
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
        $s1Y = 75;
        $mpdf->SetLeftMargin($s1TextLeft);
        $mpdf->SetRightMargin(24);
        $mpdf->SetXY($s1TextLeft, $s1Y);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 59);
        $mpdf->Cell($s1TextW, 21, 'EMPRESA/', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', 'B', 59);
        $mpdf->Cell($s1TextW, 21, 'EMPRENDIMIENTO', 0, 1, 'L');
        $actividad = !empty($companies[0]['main_activity']) ? $companies[0]['main_activity'] : 'Comercio Exterior';
        $localidad = !empty($configInstitucional['localidad_direccion']) ? $configInstitucional['localidad_direccion'] : $configInstitucional['nombre_provincia'];
        $s1Subtitle = (function_exists('mb_strtoupper') ? mb_strtoupper($actividad) : strtoupper($actividad)) . ' · ' . (function_exists('mb_strtoupper') ? mb_strtoupper($localidad) : strtoupper($localidad));
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 26);
        $mpdf->SetXY($s1TextLeft, $s1Y + 44);
        $mpdf->Cell($s1TextW, 12, $s1Subtitle, 0, 1, 'L');
        // Badge: logo empresa (izq) + logo SDE (derecha)
        $s1BadgeH = 24;
        $s1BadgeGap = 8;
        $s1SdeBadgeW = 64;
        $s1CompanyBadgeW = 56;
        $s1BadgeY = 20;
        $s1SdeBadgeX = $wMm - $s1SdeBadgeW - 24;
        $s1CompanyBadgeX = $s1SdeBadgeX - $s1CompanyBadgeW - $s1BadgeGap;
        $s1FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s1CompanyLogoPath = ($s1FirstCompanyId && isset($logosPorEmpresa[$s1FirstCompanyId])) ? $logosPorEmpresa[$s1FirstCompanyId] : (($s1FirstCompanyId && isset($imagenesPorEmpresa[$s1FirstCompanyId])) ? $imagenesPorEmpresa[$s1FirstCompanyId] : null);
        if ($s1CompanyLogoPath && file_exists($s1CompanyLogoPath)) {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($s1CompanyBadgeX, $s1BadgeY, $s1CompanyBadgeW, $s1BadgeH, 'F');
            $imgSize = @getimagesize($s1CompanyLogoPath);
            $maxW = $s1CompanyBadgeW - 4;
            $maxH = $s1BadgeH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $logoW = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $logoH = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $logoW = $maxW;
                $logoH = $maxH;
            }
            $lx = $s1CompanyBadgeX + ($s1CompanyBadgeW - $logoW) / 2;
            $ly = $s1BadgeY + ($s1BadgeH - $logoH) / 2;
            $mpdf->Image($s1CompanyLogoPath, $lx, $ly, $logoW, $logoH);
        }
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s1SdeBadgeX, $s1BadgeY, $s1SdeBadgeW, $s1BadgeH, 'F');
        $s1LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s1LogoPath)) {
            $imgSize = @getimagesize($s1LogoPath);
            $maxLogoW = $s1SdeBadgeW - 4;
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
            $logoX = $s1SdeBadgeX + ($s1SdeBadgeW - $logoW) / 2;
            $logoY = $s1BadgeY + ($s1BadgeH - $logoH) / 2;
            $mpdf->Image($s1LogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        $s1PageBoxW = 40;
        $s1PageBoxH = 13;
        $s1PageBoxX = $wMm - $s1PageBoxW;
        $s1PageBoxY = $s1FullH - $s1PageBoxH - 18;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s1PageBoxX, $s1PageBoxY, $s1PageBoxW, $s1PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s1PageBoxX, $s1PageBoxY + 2.2);
        $mpdf->Cell($s1PageBoxW - 26, 9, '01', 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        // Slide 2: PERFIL DE LA EMPRESA — header (logos izq + título der), columna izq 4 secciones, columna der imagen, página 02
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $pfLeftW = round($wMm * 0.50);
        $pfRightW = $wMm - $pfLeftW;
        $pfHeaderH = 50;
        $perfilFirstId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        // Arriba izquierda: dos logos con fondo azul como en slide 3 (empresa 56×24, SDE 64×24)
        $pfLogoY = 20;
        $pfLogoH = 24;
        $pfCompanyLogoW = 56;
        $pfSdeLogoW = 64;
        $pfLogoGap = 8;
        $pfCompanyLogoX = 24;
        $pfSdeLogoX = $pfCompanyLogoX + $pfCompanyLogoW + $pfLogoGap;
        $pfCompanyLogoPath = ($perfilFirstId && isset($logosPorEmpresa[$perfilFirstId])) ? $logosPorEmpresa[$perfilFirstId] : (($perfilFirstId && isset($imagenesPorEmpresa[$perfilFirstId])) ? $imagenesPorEmpresa[$perfilFirstId] : null);
        if ($pfCompanyLogoPath && file_exists($pfCompanyLogoPath)) {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($pfCompanyLogoX, $pfLogoY, $pfCompanyLogoW, $pfLogoH, 'F');
            $imgSize = @getimagesize($pfCompanyLogoPath);
            $maxW = $pfCompanyLogoW - 4;
            $maxH = $pfLogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $logoW = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $logoH = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $logoW = $maxW;
                $logoH = $maxH;
            }
            $lx = $pfCompanyLogoX + ($pfCompanyLogoW - $logoW) / 2;
            $ly = $pfLogoY + ($pfLogoH - $logoH) / 2;
            $mpdf->Image($pfCompanyLogoPath, $lx, $ly, $logoW, $logoH);
        }
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($pfSdeLogoX, $pfLogoY, $pfSdeLogoW, $pfLogoH, 'F');
        $pfSdePath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($pfSdePath)) {
            $isz = @getimagesize($pfSdePath);
            $mw = $pfSdeLogoW - 4;
            $mh = $pfLogoH - 4;
            if (!empty($isz[0]) && !empty($isz[1])) {
                $ir = $isz[0] / $isz[1];
                $lw = ($mh * $ir <= $mw) ? $mh * $ir : $mw;
                $lh = ($mh * $ir <= $mw) ? $mh : $mw / $ir;
            } else {
                $lw = $mw;
                $lh = $mh;
            }
            $mpdf->Image($pfSdePath, $pfSdeLogoX + ($pfSdeLogoW - $lw) / 2, $pfLogoY + ($pfLogoH - $lh) / 2, $lw, $lh);
        }
        $pfTitleX = $wMm - 28;
        $mpdf->SetXY($pfTitleX - 140, 18);
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 42);
        $mpdf->Cell(140, 14, 'PERFIL DE LA', 0, 1, 'R');
        $mpdf->SetXY($pfTitleX - 140, 34);
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', 'B', 48);
        $mpdf->Cell(140, 18, 'EMPRESA', 0, 1, 'R');
        $pfSecLeft = 24;
        $pfSecW = $pfLeftW - 2 * $pfSecLeft;
        $pfLineColor = [141, 188, 220];
        $pfY = $pfHeaderH + 22;
        $perfilEmp = !empty($companies[0]) ? $companies[0] : [];
        $perfilCid = (int)($perfilEmp['id'] ?? 0);
        $pfSections = [
            'PERFIL' => ['Tipo de Organización:', 'Actividad principal:'],
            'UBICACIÓN' => ['Localidad:', 'Departamento:', 'Domicilio:'],
            'CANALES' => ['Web:', 'Redes:'],
            'CONTACTO' => ['Cargo:', 'Email:', 'Teléfono:'],
        ];
        $pfValues = [
            'PERFIL' => ['Empresa', $perfilEmp['main_activity'] ?? '-'],
            'UBICACIÓN' => [$localidadPorEmpresa[$perfilCid] ?? '-', $perfilDepartamento ?: '-', $perfilDomicilio ?: '-'],
            'CANALES' => [$perfilEmp['website'] ?? '-', isset($redesPorEmpresa[$perfilCid]) ? implode(' ', $redesPorEmpresa[$perfilCid]) : '-'],
            'CONTACTO' => ['-', '-', $contactoInstitucional['telefono'] ?? '-'],
        ];
        $pfSectionIdx = 0;
        foreach ($pfSections as $heading => $labels) {
            if ($pfSectionIdx++ > 0) {
                $pfY += 8;
            }
            $mpdf->SetXY($pfSecLeft, $pfY);
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', 16);
            $mpdf->Cell($pfSecW, 8, $heading, 0, 1, 'L');
            $pfY += 8;
            $vals = $pfValues[$heading];
            $mpdf->SetFont('dejavusans', '', 11);
            foreach ($labels as $idx => $label) {
                $mpdf->SetXY($pfSecLeft, $pfY);
                $mpdf->SetTextColor(0, 0, 0);
                $mpdf->Cell($pfSecW * 0.4, 6, $label, 0, 0, 'L');
                $mpdf->Cell($pfSecW * 0.6, 6, $vals[$idx] ?? '-', 0, 1, 'L');
                $pfY += 7;
            }
            $pfY += 4;
            $mpdf->SetDrawColor($pfLineColor[0], $pfLineColor[1], $pfLineColor[2]);
            $mpdf->SetLineWidth(0.3);
            $mpdf->Line($pfSecLeft, $pfY, $pfSecLeft + $pfSecW, $pfY);
            $pfY += 4;
        }
        // Derecha: logo de la empresa (tamaño mayor)
        $pfLogoSize = 125;
        $pfLogoRightX = $pfLeftW + ($pfRightW - $pfLogoSize) / 2;
        $pfLogoRightY = $pfHeaderH + 30;
        $pfCompanyLogoImgPath = ($perfilFirstId && isset($logosPorEmpresa[$perfilFirstId])) ? $logosPorEmpresa[$perfilFirstId] : (($perfilFirstId && isset($imagenesPorEmpresa[$perfilFirstId])) ? $imagenesPorEmpresa[$perfilFirstId] : null);
        if ($pfCompanyLogoImgPath && file_exists($pfCompanyLogoImgPath)) {
            $isz = @getimagesize($pfCompanyLogoImgPath);
            $mw = $pfLogoSize - 4;
            $mh = $pfLogoSize - 4;
            if (!empty($isz[0]) && !empty($isz[1])) {
                $ir = $isz[0] / $isz[1];
                $lw = ($mh * $ir <= $mw) ? $mh * $ir : $mw;
                $lh = ($mh * $ir <= $mw) ? $mh : $mw / $ir;
            } else {
                $lw = $mw;
                $lh = $mh;
            }
            $mpdf->Image($pfCompanyLogoImgPath, $pfLogoRightX + ($pfLogoSize - $lw) / 2, $pfLogoRightY + ($pfLogoSize - $lh) / 2, $lw, $lh);
        } else {
            $mpdf->SetFillColor(230, 230, 230);
            $mpdf->Rect($pfLogoRightX, $pfLogoRightY, $pfLogoSize, $pfLogoSize, 'F');
        }
        $pfPageBoxW = 40;
        $pfPageBoxH = 13;
        $pfPageBoxX = $wMm - $pfPageBoxW;
        $pfPageBoxY = $hMm - $pfPageBoxH - 18;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($pfPageBoxX, $pfPageBoxY, $pfPageBoxW, $pfPageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($pfPageBoxX, $pfPageBoxY + 2.2);
        $mpdf->Cell($pfPageBoxW - 26, 9, '02', 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 2) {
        // Slide 3 intro: Productos y servicios exportables — logos en columna, una imagen abajo izq., texto y imagen grande derecha
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s4LeftW = round($wMm * 0.38);
        $s4RightW = $wMm - $s4LeftW;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        $s4Scale = 100 / 25.4;
        // Columna izquierda: arriba SDE (64×24), abajo logo empresa (56×24) — mismos tamaños que en otros slides
        $s4ColX = 24;
        $s4LogoY = 20;
        $s4LogoH = 24;
        $s4ColGap = 8;
        $s4SdeLogoW = 64;
        $s4CompanyLogoW = 64;
        // 1) SDE logo arriba con fondo azul (64×24 como slide 2/3)
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s4ColX, $s4LogoY, $s4SdeLogoW, $s4LogoH, 'F');
        $s4LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s4LogoPath)) {
            $imgSize = @getimagesize($s4LogoPath);
            $maxLogoW = $s4SdeLogoW - 4;
            $maxLogoH = $s4LogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                $logoW = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH * $imgRatio : $maxLogoW;
                $logoH = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH : $maxLogoW / $imgRatio;
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s4ColX + ($s4SdeLogoW - $logoW) / 2;
            $logoY = $s4LogoY + ($s4LogoH - $logoH) / 2;
            $mpdf->Image($s4LogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        // 2) Logo empresa debajo, caja azul 56×24 como en otros slides (centrado bajo la columna)
        $s4BelowLogoH = 24;
        $s4BelowLogoY = $s4LogoY + $s4LogoH + $s4ColGap;
        $s4CompanyLogoX = $s4ColX;
        $s4FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s4ColImgPath = ($s4FirstCompanyId && isset($logosPorEmpresa[$s4FirstCompanyId])) ? $logosPorEmpresa[$s4FirstCompanyId] : (($s4FirstCompanyId && isset($imagenesPorEmpresa[$s4FirstCompanyId])) ? $imagenesPorEmpresa[$s4FirstCompanyId] : (isset($empresaSlide4Paths[1]) ? $empresaSlide4Paths[1] : null));
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s4CompanyLogoX, $s4BelowLogoY, $s4CompanyLogoW, $s4BelowLogoH, 'F');
        if ($s4ColImgPath && file_exists($s4ColImgPath)) {
            $imgSize = @getimagesize($s4ColImgPath);
            $maxW = $s4CompanyLogoW - 4;
            $maxH = $s4BelowLogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $iw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $ih = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $iw = $maxW;
                $ih = $maxH;
            }
            $ix = $s4CompanyLogoX + ($s4CompanyLogoW - $iw) / 2;
            $iy = $s4BelowLogoY + ($s4BelowLogoH - $ih) / 2;
            $mpdf->Image($s4ColImgPath, $ix, $iy, $iw, $ih);
        }
        // Una imagen abajo a la izquierda (producto/commodity), pegada al borde inferior con margen
        $s4LeftImgW = round($wMm * 0.20);
        $s4LeftImgH = round($hMm * 0.34);
        $s4LeftImgX = 18;
        $s4BottomMargin = 18;
        $s4LeftImgY0 = $hMm - $s4LeftImgH - $s4BottomMargin;
        $s4LeftTwPx = (int) max(1, round($s4LeftImgW * $s4Scale));
        $s4LeftThPx = (int) max(1, round($s4LeftImgH * $s4Scale));
        $path = isset($empresaSlide4Paths[0]) ? $empresaSlide4Paths[0] : null;
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
                    $tmp = sys_get_temp_dir() . '/clasico_empresa_left_0_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) {
                        $mpdf->Image($tmp, $s4LeftImgX, $s4LeftImgY0, $s4LeftImgW, $s4LeftImgH);
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
            $mpdf->Image($path, $s4LeftImgX, $s4LeftImgY0, $s4LeftImgW, $s4LeftImgH);
        } elseif (!$path || !file_exists($path)) {
            $mpdf->SetFillColor(230, 230, 230);
            $mpdf->Rect($s4LeftImgX, $s4LeftImgY0, $s4LeftImgW, $s4LeftImgH, 'F');
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
        // Bloque de texto: PRODUCTOS Y SERVICIOS / EXPORTABLES
        $s4TextTop = $s4BigImgY + $s4BigImgH + 52;
        $s4TextPadLeft = 10;
        $s4TextPadRight = 22;
        $s4TextW = $wMm - $s4BigImgX - $s4TextPadRight;
        $mpdf->SetLeftMargin($s4BigImgX + $s4TextPadLeft);
        $mpdf->SetRightMargin($s4TextPadRight);
        $mpdf->SetXY($s4BigImgX + $s4TextPadLeft, $s4TextTop);
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', 'B', 52);
        $mpdf->Cell($s4TextW, 20, 'PRODUCTOS Y SERVICIOS', 0, 1, 'L');
        $mpdf->Ln(6);
        $mpdf->SetTextColor(102, 163, 214);
        $mpdf->SetFont('dejavusans', 'B', 48);
        $mpdf->Cell($s4TextW, 20, 'EXPORTABLES', 0, 1, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Badge número de página 04 (azul oscuro)
        $s4PageBoxW = 40;
        $s4PageBoxH = 13;
        $s4PageBoxX = $wMm - $s4PageBoxW;
        $s4PageBoxY = $hMm - $s4PageBoxH - 18;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s4PageBoxX, $s4PageBoxY, $s4PageBoxW, $s4PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s4PageBoxX, $s4PageBoxY + 2.2);
        $mpdf->Cell($s4PageBoxW - 26, 9, '03', 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 3) {
        // Slide(s) Productos y servicios destacados: un producto por slide; icon_yes.png para галочки
        $productoSlidesChunks = array_chunk($productosParaSlides, 1);
        $prodCompanyNameById = [];
        foreach ($companies as $c) {
            $prodCompanyNameById[(int)($c['id'] ?? 0)] = $c['name'] ?? '';
        }
        $prodLeftW = round($wMm * 0.70);
        $prodRightW = $wMm - $prodLeftW;
        $prodImgW = round($wMm * 0.3);
        $prodImgH = round($hMm * 0.60);
        $prodBlackW = round($wMm * 0.4);
        $prodBlackH = round($hMm * 0.42);
        $prodBlackX = $wMm - $prodBlackW;
        $prodBlackY = $hMm - $prodBlackH;
        $prodPageNum = 4;
        foreach ($productoSlidesChunks as $idx => $chunk) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect(0, 0, $prodLeftW, $hMm, 'F');
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($prodBlackX, $prodBlackY, $prodBlackW, $prodBlackH, 'F');
            $prodImgPadR = 24;
            $prodImgX = $wMm - $prodImgW - $prodImgPadR;
            $prodImgY = $prodBlackY - $prodImgH / 2 - 12;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($prodImgX, $prodImgY, $prodImgW, $prodImgH, 'F');
            $firstProdInChunk = $chunk[0];
            $firstProdPid = (int) ($firstProdInChunk['id'] ?? 0);
            $prodImgPath = isset($imagenesPorProducto[$firstProdPid]) ? $imagenesPorProducto[$firstProdPid] : null;
            if (!$prodImgPath || !file_exists($prodImgPath)) {
                $prodImgPath = !empty($productoImgCandidates) ? $productoImgCandidates[$idx % count($productoImgCandidates)] : null;
            }
            if ($prodImgPath && file_exists($prodImgPath)) {
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
            $prodBadgeH = 24;
            $prodSdeBadgeW = 64;
            $prodCompanyBadgeW = 56;
            $prodBadgeGap = 8;
            $prodLogoPadLeft = 22;
            $prodLogoPadTop = 12;
            $prodCompanyBadgeX = $prodLogoPadLeft;
            $prodSdeBadgeX = $prodCompanyBadgeX + $prodCompanyBadgeW + $prodBadgeGap;
            $prodLogoY = $prodLogoPadTop;
            $prodFirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
            $prodCompanyLogoPath = ($prodFirstCompanyId && isset($logosPorEmpresa[$prodFirstCompanyId])) ? $logosPorEmpresa[$prodFirstCompanyId] : (($prodFirstCompanyId && isset($imagenesPorEmpresa[$prodFirstCompanyId])) ? $imagenesPorEmpresa[$prodFirstCompanyId] : null);
            if ($prodCompanyLogoPath && file_exists($prodCompanyLogoPath)) {
                $mpdf->SetFillColor(0, 51, 153);
                $mpdf->Rect($prodCompanyBadgeX, $prodLogoY, $prodCompanyBadgeW, $prodBadgeH, 'F');
                $imgSize = @getimagesize($prodCompanyLogoPath);
                $maxW = $prodCompanyBadgeW - 4;
                $maxH = $prodBadgeH - 4;
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $r = $imgSize[0] / $imgSize[1];
                    $lw = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                    $lh = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
                } else {
                    $lw = $maxW;
                    $lh = $maxH;
                }
                $mpdf->Image($prodCompanyLogoPath, $prodCompanyBadgeX + ($prodCompanyBadgeW - $lw) / 2, $prodLogoY + ($prodBadgeH - $lh) / 2, $lw, $lh);
            }
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($prodSdeBadgeX, $prodLogoY, $prodSdeBadgeW, $prodBadgeH, 'F');
            $prodSdeLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
            if (file_exists($prodSdeLogoPath)) {
                $imgSize = @getimagesize($prodSdeLogoPath);
                $maxW = $prodSdeBadgeW - 4;
                $maxH = $prodBadgeH - 4;
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $ratio = $imgSize[0] / $imgSize[1];
                    $lw = ($maxH * $ratio <= $maxW) ? $maxH * $ratio : $maxW;
                    $lh = ($maxH * $ratio <= $maxW) ? $maxH : $maxW / $ratio;
                } else {
                    $lw = $maxW;
                    $lh = $maxH;
                }
                $mpdf->Image($prodSdeLogoPath, $prodSdeBadgeX + ($prodSdeBadgeW - $lw) / 2, $prodLogoY + ($prodBadgeH - $lh) / 2, $lw, $lh);
            }
            $prodPad = 22;
            $prodTextW = $prodLeftW - 2 * $prodPad - 48;
            $mpdf->SetLeftMargin($prodPad);
            $mpdf->SetRightMargin($wMm - $prodLeftW + $prodPad);
            $prodTitleY = $prodLogoY + $prodBadgeH + 18;
            $prod = $chunk[0];
            $pid = (int) $prod['id'];
            $mpdf->SetXY($prodPad, $prodTitleY);
            $mpdf->SetTextColor(117, 168, 218);
            $mpdf->SetFont('dejavusans', 'B', 52);
            $mpdf->Cell($prodTextW, 18, 'NOMBRE DEL', 0, 1, 'L');
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', 52);
            $mpdf->Cell($prodTextW, 18, mb_strlen($prod['name'] ?? '') > 28 ? (mb_substr($prod['name'], 0, 27) . '…') : ($prod['name'] ?? 'PRODUCTO/SERVICIO'), 0, 1, 'L');
            $mpdf->Ln(12);
            $prodContentX = $prodPad;
            $prodContentW = $prodTextW;
            $prodY = $prodTitleY + 18 + 18 + 12 + 6;
            $mpdf->SetXY($prodContentX, $prodY);
            $mpdf->SetFont('dejavusans', 'B', 16);
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->Cell($prodContentW, 8, 'Descripción del producto:', 0, 1, 'L');
            $mpdf->SetFont('dejavusans', '', 14);
            $descStr = trim($prod['description'] ?? '') ?: 'Nisi justo faucibus lectus blandit donec gravida proin natoque, malesuada a facilisis dictumst rhoncus pulvinar aliquet feugiat ultrices, mollis phasellus varius tortor habitasse purus enim.';
            $descStr = mb_strlen($descStr) > 200 ? mb_substr($descStr, 0, 199) . '…' : $descStr;
            $mpdf->MultiCell($prodContentW, 7, $descStr, 0, 'L');
            $mpdf->Ln(8);
            $prodIconSize = 18;
            $prodIconW = $prodIconSize;
            $prodIconH = $prodIconSize;
            $prodRowH = 20;
            $prodRowY0 = $prodY + 8 + 28 + 10;
            $prodIconOffsetY = ($prodRowH - $prodIconH) / 2;
            $prodRowLineH = 9;
            $prodTextOffsetY = ($prodRowH - $prodRowLineH) / 2;
            $prodLabelGap = 4;
            $prodDataGap = 8;
            if (file_exists($iconYesPath)) {
                $prodRowY = $prodRowY0;
                $mpdf->Image($iconYesPath, $prodContentX, $prodRowY + $prodIconOffsetY, $prodIconW, $prodIconH);
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $mpdf->SetTextColor(0, 0, 0);
                $lbl = 'Exportación anual (USD):';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $mpdf->Cell(0, $prodRowLineH, trim($prod['annual_export'] ?? '-') ?: '-', 0, 1, 'L');
                $prodRowY += $prodRowH;
                $mpdf->Image($iconYesPath, $prodContentX, $prodRowY + $prodIconOffsetY, $prodIconW, $prodIconH);
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $lbl = 'Certificaciones:';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $certStr = trim($prod['certifications'] ?? '-') ?: '-';
                $mpdf->Cell(0, $prodRowLineH, (mb_strlen($certStr) > 30 ? mb_substr($certStr, 0, 29) . '…' : $certStr), 0, 1, 'L');
                $prodRowY += $prodRowH;
                $mpdf->Image($iconYesPath, $prodContentX, $prodRowY + $prodIconOffsetY, $prodIconW, $prodIconH);
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $lbl = 'Mercados actuales:';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $mpdf->Cell(0, $prodRowLineH, '-', 0, 1, 'L');
                $prodRowY += $prodRowH;
                $mpdf->Image($iconYesPath, $prodContentX, $prodRowY + $prodIconOffsetY, $prodIconW, $prodIconH);
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $lbl = 'Mercados de interés:';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $mpdf->Cell(0, $prodRowLineH, '-', 0, 1, 'L');
            } else {
                $prodRowY = $prodRowY0;
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $mpdf->SetTextColor(0, 51, 153);
                $mpdf->Cell(6, $prodRowLineH, "\xE2\x9C\x94", 0, 0, 'L');
                $mpdf->SetTextColor(0, 0, 0);
                $lbl = 'Exportación anual (USD):';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $mpdf->Cell(0, $prodRowLineH, trim($prod['annual_export'] ?? '-') ?: '-', 0, 1, 'L');
                $prodRowY += $prodRowH;
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $mpdf->SetTextColor(0, 51, 153);
                $mpdf->Cell(6, $prodRowLineH, "\xE2\x9C\x94", 0, 0, 'L');
                $mpdf->SetTextColor(0, 0, 0);
                $lbl = 'Certificaciones:';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $certStr = trim($prod['certifications'] ?? '-') ?: '-';
                $mpdf->Cell(0, $prodRowLineH, (mb_strlen($certStr) > 30 ? mb_substr($certStr, 0, 29) . '…' : $certStr), 0, 1, 'L');
                $prodRowY += $prodRowH;
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $mpdf->SetTextColor(0, 51, 153);
                $mpdf->Cell(6, $prodRowLineH, "\xE2\x9C\x94", 0, 0, 'L');
                $mpdf->SetTextColor(0, 0, 0);
                $lbl = 'Mercados actuales:';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $mpdf->Cell(0, $prodRowLineH, '-', 0, 1, 'L');
                $prodRowY += $prodRowH;
                $mpdf->SetXY($prodContentX + $prodIconW + $prodLabelGap, $prodRowY + $prodTextOffsetY);
                $mpdf->SetFont('dejavusans', 'B', 17);
                $mpdf->SetTextColor(0, 51, 153);
                $mpdf->Cell(6, $prodRowLineH, "\xE2\x9C\x94", 0, 0, 'L');
                $mpdf->SetTextColor(0, 0, 0);
                $lbl = 'Mercados de interés:';
                $mpdf->Cell($mpdf->GetStringWidth($lbl) + 2, $prodRowLineH, $lbl, 0, 0, 'L');
                $mpdf->Cell($prodDataGap, $prodRowLineH, '', 0, 0, 'L');
                $mpdf->SetFont('dejavusans', '', 15);
                $mpdf->Cell(0, $prodRowLineH, '-', 0, 1, 'L');
            }
            $mpdf->SetLeftMargin(0);
            $mpdf->SetRightMargin(0);
            $mpdf->SetDrawColor(0, 0, 0);
            $prodPageBoxW = 40;
            $prodPageBoxH = 13;
            $prodPageBoxX = $wMm - $prodPageBoxW;
            $prodPageBoxY = $hMm - $prodPageBoxH - 18;
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect($prodPageBoxX, $prodPageBoxY, $prodPageBoxW, $prodPageBoxH, 'F');
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', 14);
            $mpdf->SetXY($prodPageBoxX, $prodPageBoxY + 2.2);
            $mpdf->Cell($prodPageBoxW - 26, 9, sprintf('%02d', $prodPageNum), 0, 0, 'R');
            $prodPageNum++;
        }
    } elseif ($i === 4) {
        // Nuestra historia (antes de Competitividad), luego Competitividad y slide final
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s2LeftW = round($wMm * 0.50);
        $s2RightW = $wMm - $s2LeftW;
        $s2ImagePath = null;
        if ($productivoSlide2Path && file_exists($productivoSlide2Path) && extension_loaded('gd')) {
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
                $dw = (int) max(1, round($s2LeftW * $scale));
                $dh = (int) max(1, round($hMm * $scale));
                $scaleCover = max($dw / $sw, $dh / $sh);
                $tw = (int) round($sw * $scaleCover);
                $th = (int) round($sh * $scaleCover);
                $sx = (int) max(0, round(($tw - $dw) / 2));
                $sy = (int) max(0, round(($th - $dh) / 2));
                $tmpImg = @imagecreatetruecolor($tw, $th);
                $dst = $tmpImg ? @imagecreatetruecolor($dw, $dh) : false;
                if ($tmpImg && $dst && @imagecopyresampled($tmpImg, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh)) {
                    @imagecopy($dst, $tmpImg, 0, 0, $sx, $sy, $dw, $dh);
                    imagedestroy($tmpImg);
                    $overlay = imagecreatetruecolor($dw, $dh);
                    if ($overlay) {
                        $dark = imagecolorallocate($overlay, 0, 30, 80);
                        imagefill($overlay, 0, 0, $dark);
                        imagecopymerge($dst, $overlay, 0, 0, 0, 0, $dw, $dh, 65);
                        imagedestroy($overlay);
                    }
                    $tmpPath = sys_get_temp_dir() . '/clasico_s2_' . uniqid() . '.png';
                    if (imagepng($dst, $tmpPath)) {
                        $s2ImagePath = $tmpPath;
                    }
                    imagedestroy($dst);
                } elseif ($tmpImg) {
                    imagedestroy($tmpImg);
                }
                imagedestroy($src);
            }
        }
        if ($s2ImagePath && file_exists($s2ImagePath)) {
            $mpdf->Image($s2ImagePath, 0, 0, $s2LeftW, $hMm);
            @unlink($s2ImagePath);
        } else {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect(0, 0, $s2LeftW, $hMm, 'F');
        }
        $s2LogoH = 24;
        $s2LogoGap = 8;
        $s2CompanyLogoW = 56;
        $s2SdeBadgeW = 64;
        $s2LogoY = 20;
        $s2CompanyLogoX = 18;
        $s2SdeBadgeX = $s2CompanyLogoX + $s2CompanyLogoW + $s2LogoGap;
        $s2FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s2CompanyLogoPath = null;
        if ($s2FirstCompanyId && isset($logosPorEmpresa[$s2FirstCompanyId])) {
            $s2CompanyLogoPath = $logosPorEmpresa[$s2FirstCompanyId];
        } elseif ($s2FirstCompanyId && isset($imagenesPorEmpresa[$s2FirstCompanyId])) {
            $s2CompanyLogoPath = $imagenesPorEmpresa[$s2FirstCompanyId];
        }
        if ($s2CompanyLogoPath && file_exists($s2CompanyLogoPath)) {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($s2CompanyLogoX, $s2LogoY, $s2CompanyLogoW, $s2LogoH, 'F');
            $imgSize = @getimagesize($s2CompanyLogoPath);
            $maxW = $s2CompanyLogoW - 4;
            $maxH = $s2LogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $logoW = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $logoH = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $logoW = $maxW;
                $logoH = $maxH;
            }
            $lx = $s2CompanyLogoX + ($s2CompanyLogoW - $logoW) / 2;
            $ly = $s2LogoY + ($s2LogoH - $logoH) / 2;
            $mpdf->Image($s2CompanyLogoPath, $lx, $ly, $logoW, $logoH);
        } else {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($s2CompanyLogoX, $s2LogoY, $s2CompanyLogoW, $s2LogoH, 'F');
        }
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s2SdeBadgeX, $s2LogoY, $s2SdeBadgeW, $s2LogoH, 'F');
        $s2LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s2LogoPath)) {
            $imgSize = @getimagesize($s2LogoPath);
            $maxLogoW = $s2SdeBadgeW - 4;
            $maxLogoH = $s2LogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                $logoW = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH * $imgRatio : $maxLogoW;
                $logoH = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH : $maxLogoW / $imgRatio;
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s2SdeBadgeX + ($s2SdeBadgeW - $logoW) / 2;
            $logoY = $s2LogoY + ($s2LogoH - $logoH) / 2;
            $mpdf->Image($s2LogoPath, $logoX, $logoY, $logoW, $logoH);
        }
        $s2BlockX = $s2LeftW;
        $s2BlockW = $s2RightW;
        $s2BlockH = $hMm;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s2BlockX, 0, $s2BlockW, $s2BlockH, 'F');
        $s2Pad = 32;
        $s2TextMaxW = 95;
        $s2TextLeft = $s2BlockX + $s2Pad;
        $s2TextTop = 52;
        $mpdf->SetLeftMargin($s2TextLeft);
        $mpdf->SetRightMargin($wMm - $s2TextLeft - $s2TextMaxW);
        $s2TextW = $s2TextMaxW;
        $mpdf->SetXY($s2TextLeft, $s2TextTop);
        $mpdf->SetTextColor(117, 168, 218);
        $mpdf->SetFont('dejavusans', 'B', 42);
        $mpdf->Cell($s2TextW, 14, 'NUESTRA', 0, 1, 'L');
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', 'B', 42);
        $mpdf->Cell($s2TextW, 14, 'HISTORIA', 0, 1, 'L');
        $mpdf->Ln(14);
        $mpdf->SetTextColor(0, 0, 0);
        $s2FontSize = 11;
        $s2LineHeight = 5.5;
        $s2ParaTop = $s2TextTop + 14 + 14 + 18;
        $mpdf->SetXY($s2TextLeft, $s2ParaTop);
        $mpdf->SetFont('dejavusans', '', $s2FontSize);
        $s2Para = "Nisi justo faucibus lectus blandit donec gravida proin natoque, malesuada a facilisis dictumst rhoncus pulvinar aliquet feugiat ultrices, mollis phasellus varius tortor habitasse purus enim. Nunc lacus sociis tortor volutpat egestas vel duis erat, eleifend dapibus praesent vehicula fringilla ac suscipit conubia, nibh pulvinar elementum faucibus urna nullam luctus. Augue senectus rutrum suscipit habitasse felis aptent phasellus, nec hendrerit mattis enim congue tempor auctor magnis, mollis neque libero sagittis urna orci.";
        $mpdf->MultiCell($s2TextW, $s2LineHeight, $s2Para, 0, 'L');
        $s2PageBoxW = 40;
        $s2PageBoxH = 13;
        $s2PageBoxX = $wMm - $s2PageBoxW;
        $s2PageBoxY = $hMm - $s2PageBoxH - 18;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s2PageBoxX, $s2PageBoxY, $s2PageBoxW, $s2PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s2PageBoxX, $s2PageBoxY + 2.2);
        $mpdf->Cell($s2PageBoxW - 26, 9, sprintf('%02d', $mpdf->PageNo()), 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Slide Competitividad y diferenciación (antes del final): parte superior imagen+overlay azul, parte inferior 5 columnas gris
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s6TopH = round($hMm * 0.48);
        $s6BottomH = $hMm - $s6TopH;
        $s6Scale = 100 / 25.4;
        $s6BgPath = $competitividadBgPath;
        $s6BgDrawn = false;
        if ($s6BgPath && file_exists($s6BgPath)) {
            if (extension_loaded('gd')) {
                $info = @getimagesize($s6BgPath);
                $ext = strtolower(pathinfo($s6BgPath, PATHINFO_EXTENSION));
                $src = false;
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $src = @imagecreatefromjpeg($s6BgPath);
                } elseif ($info && $info[2] === IMAGETYPE_PNG) {
                    $src = @imagecreatefrompng($s6BgPath);
                } elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($s6BgPath);
                }
                if ($src && !empty($info[0]) && !empty($info[1])) {
                    $sw = imagesx($src);
                    $sh = imagesy($src);
                    $dw = (int) max(1, round($wMm * $s6Scale));
                    $dh = (int) max(1, round($s6TopH * $s6Scale));
                    $r = max($dw / $sw, $dh / $sh);
                    $srcW = (int) round($dw / $r);
                    $srcH = (int) round($dh / $r);
                    $srcX = (int) max(0, round(($sw - $srcW) / 2));
                    $srcY = (int) max(0, round(($sh - $srcH) / 2));
                    $dst = @imagecreatetruecolor($dw, $dh);
                    if ($dst && $srcW > 0 && $srcH > 0 && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dw, $dh, $srcW, $srcH)) {
                        $overlay = imagecreatetruecolor($dw, $dh);
                        if ($overlay) {
                            $blue = imagecolorallocate($overlay, 0, 30, 80);
                            imagefill($overlay, 0, 0, $blue);
                            imagecopymerge($dst, $overlay, 0, 0, 0, 0, $dw, $dh, 60);
                            imagedestroy($overlay);
                        }
                        $tmp = sys_get_temp_dir() . '/clasico_comp_' . uniqid() . '.png';
                        if (imagepng($dst, $tmp)) {
                            $mpdf->Image($tmp, 0, 0, $wMm, $s6TopH);
                            @unlink($tmp);
                            $s6BgDrawn = true;
                        }
                        imagedestroy($dst);
                    } elseif ($dst) {
                        imagedestroy($dst);
                    }
                    imagedestroy($src);
                }
            }
            if (!$s6BgDrawn) {
                $mpdf->Image($s6BgPath, 0, 0, $wMm, $s6TopH);
                $s6BgDrawn = true;
                if (method_exists($mpdf, 'SetAlpha')) {
                    $mpdf->SetAlpha(0.62);
                    $mpdf->SetFillColor(0, 51, 153);
                    $mpdf->Rect(0, 0, $wMm, $s6TopH, 'F');
                    $mpdf->SetAlpha(1);
                }
            }
        }
        if (!$s6BgDrawn) {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect(0, 0, $wMm, $s6TopH, 'F');
        }
        $s6TitleLeft = 28;
        $s6TitleY = 38;
        $mpdf->SetXY($s6TitleLeft, $s6TitleY);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 44);
        $mpdf->Cell($wMm - 120, 16, 'COMPETITIVIDAD Y', 0, 1, 'L');
        $mpdf->SetXY($s6TitleLeft, $s6TitleY + 20);
        $mpdf->Cell($wMm - 120, 18, 'DIFERENCIACIÓN', 0, 1, 'L');
        $s6LineY = $s6TitleY + 20 + 18 + 6;
        $s6LineMarginL = 28;
        $s6LineMarginR = 28;
        $mpdf->SetDrawColor(255, 255, 255);
        $mpdf->SetLineWidth(0.6);
        $mpdf->Line($s6LineMarginL, $s6LineY, $wMm - $s6LineMarginR, $s6LineY);
        $mpdf->SetDrawColor(0, 0, 0);
        $mpdf->SetFont('dejavusans', '', 14);
        $mpdf->SetXY($s6TitleLeft, $s6LineY + 6);
        $mpdf->Cell($wMm - 120, 8, 'FACTOR DE DIFERENCIACIÓN 1 - FACTOR DE DIFERENCIACIÓN 2 - FACTOR DE DIFERENCIACIÓN 3 - FACTOR DE DIFERENCIACIÓN 4', 0, 1, 'L');
        // Bloques de logos arriba a la derecha como en slide 2 (Perfil): empresa 56×24 + SDE 64×24
        $s6LogoY = 20;
        $s6LogoH = 24;
        $s6CompanyLogoW = 56;
        $s6SdeBadgeW = 64;
        $s6LogoGap = 8;
        $s6SdeBadgeX = $wMm - $s6SdeBadgeW - 24;
        $s6CompanyLogoX = $s6SdeBadgeX - $s6CompanyLogoW - $s6LogoGap;
        $s6FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s6CompanyLogoPath = ($s6FirstCompanyId && isset($logosPorEmpresa[$s6FirstCompanyId])) ? $logosPorEmpresa[$s6FirstCompanyId] : (($s6FirstCompanyId && isset($imagenesPorEmpresa[$s6FirstCompanyId])) ? $imagenesPorEmpresa[$s6FirstCompanyId] : null);
        if ($s6CompanyLogoPath && file_exists($s6CompanyLogoPath)) {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($s6CompanyLogoX, $s6LogoY, $s6CompanyLogoW, $s6LogoH, 'F');
            $imgSize = @getimagesize($s6CompanyLogoPath);
            $maxW = $s6CompanyLogoW - 4;
            $maxH = $s6LogoH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $logoW = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $logoH = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $logoW = $maxW;
                $logoH = $maxH;
            }
            $lx = $s6CompanyLogoX + ($s6CompanyLogoW - $logoW) / 2;
            $ly = $s6LogoY + ($s6LogoH - $logoH) / 2;
            $mpdf->Image($s6CompanyLogoPath, $lx, $ly, $logoW, $logoH);
        }
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s6SdeBadgeX, $s6LogoY, $s6SdeBadgeW, $s6LogoH, 'F');
        $s6SdePath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s6SdePath)) {
            $isz = @getimagesize($s6SdePath);
            $mw = $s6SdeBadgeW - 4;
            $mh = $s6LogoH - 4;
            if (!empty($isz[0]) && !empty($isz[1])) {
                $ir = $isz[0] / $isz[1];
                $lw = ($mh * $ir <= $mw) ? $mh * $ir : $mw;
                $lh = ($mh * $ir <= $mw) ? $mh : $mw / $ir;
            } else {
                $lw = $mw;
                $lh = $mh;
            }
            $mpdf->Image($s6SdePath, $s6SdeBadgeX + ($s6SdeBadgeW - $lw) / 2, $s6LogoY + ($s6LogoH - $lh) / 2, $lw, $lh);
        }
        $mpdf->SetFillColor(240, 240, 240);
        $mpdf->Rect(0, $s6TopH, $wMm, $s6BottomH, 'F');
        $s6ColTitles = [
            ['PREMIOS'],
            ['FERIAS'],
            ['RONDAS'],
            ['EXPERIENCIA', 'EXPORTADORA'],
            ['REFERENCIAS', 'COMERCIALES'],
        ];
        $s6ColDesc = 'Información proveniente del input del formulario';
        $s6ColCount = 5;
        $s6ColPad = 16;
        $s6ColW = ($wMm - 2 * $s6ColPad - ($s6ColCount - 1) * 8) / $s6ColCount;
        $s6BottomY = $s6TopH + 16;
        $s6TitleLineH = 9;
        $s6TitleFontSize = 19;
        $s6MaxTitleH = 2 * $s6TitleLineH;
        $s6ArrowSize = 20;
        $s6ArrowLeftOffset = 10;
        $s6ArrowY = $s6BottomY + $s6MaxTitleH + 2;
        $s6NumbersY = $s6BottomY + $s6MaxTitleH + $s6ArrowSize + 8;
        $s6DescY = $s6NumbersY + 14;
        for ($col = 0; $col < $s6ColCount; $col++) {
            $cx = $s6ColPad + $col * ($s6ColW + 8);
            $mpdf->SetXY($cx, $s6BottomY);
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', $s6TitleFontSize);
            $titleLines = $s6ColTitles[$col];
            foreach ($titleLines as $line) {
                $mpdf->SetX($cx);
                $mpdf->Cell($s6ColW, $s6TitleLineH, $line, 0, 1, 'L');
            }
            $s6ArrowIconPath = $assetsDir . '/icon_rect.png';
            $s6ArrowX = $cx - $s6ArrowLeftOffset;
            if (file_exists($s6ArrowIconPath)) {
                $mpdf->Image($s6ArrowIconPath, $s6ArrowX, $s6ArrowY, $s6ArrowSize, $s6ArrowSize);
            } else {
                $mpdf->SetXY($cx, $s6ArrowY);
                $mpdf->SetTextColor(141, 188, 220);
                $mpdf->SetFont('dejavusans', 'B', 11);
                $mpdf->Cell($s6ColW, 6, "\xE2\x86\x92", 0, 0, 'L');
            }
            $mpdf->SetXY($cx, $s6NumbersY);
            $mpdf->SetTextColor(0, 0, 0);
            $mpdf->SetFont('dejavusans', 'B', 20);
            $mpdf->Cell($s6ColW, 12, sprintf('%02d', $col + 1), 0, 1, 'L');
            $mpdf->SetXY($cx, $s6DescY);
            $mpdf->SetFont('dejavusans', '', 14);
            $mpdf->MultiCell($s6ColW, 6, $s6ColDesc, 0, 'L');
        }
        $s6PageBoxW = 40;
        $s6PageBoxH = 13;
        $s6PageBoxX = $wMm - $s6PageBoxW;
        $s6PageBoxY = $hMm - $s6PageBoxH - 18;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s6PageBoxX, $s6PageBoxY, $s6PageBoxW, $s6PageBoxH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s6PageBoxX, $s6PageBoxY + 2.2);
        $mpdf->Cell($s6PageBoxW - 26, 9, sprintf('%02d', $mpdf->PageNo()), 0, 0, 'R');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Slide final (Muchas gracias): fondo Empresa0.jpg; franja derecha blanco+azul; dos logos (empresa + SDE) con fondo azul; texto MUCHAS GRACIAS
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s7FullH = $hMm;
        $s7RightColW = round($wMm * 0.04);
        $s7ImageW = $wMm - $s7RightColW;
        $s7BlueH = round($s7FullH * 0.75);
        $s7WhiteH = $s7FullH - $s7BlueH;
        $s7Overlap = 1;
        $s7BgPath = $backgroundSlide1Path;
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
        $s7BadgeH = 24;
        $s7BadgeGap = 8;
        $s7CompanyBadgeW = 56;
        $s7SdeBadgeW = 64;
        $s7BadgeY = 20;
        $s7SdeBadgeX = $s7ImageW - $s7SdeBadgeW - 24;
        $s7CompanyBadgeX = $s7SdeBadgeX - $s7CompanyBadgeW - $s7BadgeGap;
        $s7FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s7CompanyLogoPath = ($s7FirstCompanyId && isset($logosPorEmpresa[$s7FirstCompanyId])) ? $logosPorEmpresa[$s7FirstCompanyId] : (($s7FirstCompanyId && isset($imagenesPorEmpresa[$s7FirstCompanyId])) ? $imagenesPorEmpresa[$s7FirstCompanyId] : null);
        if ($s7CompanyLogoPath && file_exists($s7CompanyLogoPath)) {
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($s7CompanyBadgeX, $s7BadgeY, $s7CompanyBadgeW, $s7BadgeH, 'F');
            $imgSize = @getimagesize($s7CompanyLogoPath);
            $maxW = $s7CompanyBadgeW - 4;
            $maxH = $s7BadgeH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $logoW = ($maxH * $r <= $maxW) ? $maxH * $r : $maxW;
                $logoH = ($maxH * $r <= $maxW) ? $maxH : $maxW / $r;
            } else {
                $logoW = $maxW;
                $logoH = $maxH;
            }
            $lx = $s7CompanyBadgeX + ($s7CompanyBadgeW - $logoW) / 2;
            $ly = $s7BadgeY + ($s7BadgeH - $logoH) / 2;
            $mpdf->Image($s7CompanyLogoPath, $lx, $ly, $logoW, $logoH);
        }
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s7SdeBadgeX, $s7BadgeY, $s7SdeBadgeW, $s7BadgeH, 'F');
        $s7LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        if (file_exists($s7LogoPath)) {
            $imgSize = @getimagesize($s7LogoPath);
            $maxLogoW = $s7SdeBadgeW - 4;
            $maxLogoH = $s7BadgeH - 4;
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $imgRatio = $imgSize[0] / $imgSize[1];
                $logoW = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH * $imgRatio : $maxLogoW;
                $logoH = ($maxLogoH * $imgRatio <= $maxLogoW) ? $maxLogoH : $maxLogoW / $imgRatio;
            } else {
                $logoW = $maxLogoW;
                $logoH = $maxLogoH;
            }
            $logoX = $s7SdeBadgeX + ($s7SdeBadgeW - $logoW) / 2;
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
        $mpdf->Cell($s7TextW, 32, 'MUCHAS', 0, 1, 'L');
        $mpdf->SetTextColor(96, 176, 224);
        $mpdf->SetFont('dejavusans', 'B', 95);
        $mpdf->Cell($s7TextW, 32, 'GRACIAS', 0, 1, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $s7PageBoxW = 40;
        $s7PageBoxH = 13;
        $s7PageBoxX = $wMm - $s7PageBoxW;
        $s7PageBoxY = $hMm - $s7PageBoxH - 18;
        $s7PageNum = $mpdf->PageNo();
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s7PageBoxX, $s7PageBoxY, $s7PageBoxW, $s7PageBoxH, 'F');
        $mpdf->SetTextColor(0, 51, 153);
        $mpdf->SetFont('dejavusans', 'B', 14);
        $mpdf->SetXY($s7PageBoxX, $s7PageBoxY + 2.2);
        $mpdf->Cell($s7PageBoxW - 26, 9, sprintf('%02d', $s7PageNum), 0, 0, 'R');
    } else {
        $mpdf->AddPage();
        $writeHtmlChunks($mpdf, $htmlChunks[$i]);
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
