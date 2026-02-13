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
$companyNameById = [];
foreach ($companies as $c) {
    $companyNameById[(int)($c['id'] ?? 0)] = $c['name'] ?? '';
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
        $mpdf->Cell(32, 8, 'Página 01', 0, 0, 'R');

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
        $mpdf->Cell($s1TextW, 20, 'OFERTA', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 56);
        $mpdf->Cell($s1TextW, 20, 'EXPORTABLE', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 22);
        $mpdf->SetXY($s1TextLeft, $s1Ty + 48);
        $mpdf->Cell($s1TextW, 10, function_exists('mb_strtoupper') ? mb_strtoupper($configInstitucional['nombre_provincia']) : strtoupper($configInstitucional['nombre_provincia']), 0, 0, 'L');

        // Pie: Edición 2026 y flecha — mismos márgenes s1Pad que header; fuente más grande, flecha más ancha y grande
        $s1FootTextY = $s1FootY + ($s1FooterH - 10) / 2 + 1;
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 22);
        $mpdf->SetXY($s1Pad, $s1FootTextY);
        $mpdf->Cell($wMm - 2 * $s1Pad - 28, 10, 'Edición ' . $configInstitucional['periodo_ano'], 0, 0, 'L');
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
        $mpdf->Cell(32, 8, 'Página 02', 0, 0, 'R');
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
        $mpdf->Cell($s2TextW, 12, 'CONTEXTO', 0, 1, 'L');
        $mpdf->Ln(3);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 30);
        $mpdf->Cell($s2TextW, 11, 'PROVINCIAL', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', $s2FontSize);
        $mpdf->Ln(9);
        $s2Cell = function ($bold, $txt, $ln = 0) use ($mpdf, $s2LineHeight, $s2FontSize) {
            $mpdf->SetFont('dejavusans', $bold ? 'B' : '', $s2FontSize);
            $w = $mpdf->GetStringWidth($txt);
            $mpdf->Cell($w, $s2LineHeight, $txt, 0, $ln, 'L');
        };
        $s2Cell(true, 'Santiago del Estero', 0);
        $s2Cell(false, ' impulsa una ', 0);
        $s2Cell(true, 'Oferta Exportable', 1);
        $s2Cell(true, 'Provincial', 0);
        $s2Cell(false, ' para visibilizar, ordenar y promover su entramado ', 1);
        $s2Cell(true, 'productivo', 0);
        $s2Cell(false, ' ante organismos de promoción, misiones ', 1);
        $s2Cell(false, 'comerciales y compradores.', 1);
        $mpdf->Ln(9);
        $s2Cell(false, 'Esta presentación reúne ', 0);
        $s2Cell(true, 'información declarada por las', 1);
        $s2Cell(true, 'empresas registradas, con foco en productos y servicios ', 1);
        $s2Cell(true, 'exportables.', 1);
        $mpdf->Ln(9);
        $s2Cell(false, 'La iniciativa busca ', 0);
        $s2Cell(true, 'facilitar el acceso a datos clave, mejorar la', 1);
        $s2Cell(true, 'difusión institucional y habilitar oportunidades de', 1);
        $s2Cell(true, 'vinculación comercial, fortaleciendo una cultura', 1);
        $s2Cell(true, 'exportadora moderna, inclusiva y federal.', 1);
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
        $mpdf->Cell(32, 8, 'Página 03', 0, 0, 'R');
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
        $mpdf->Cell($s3TextW, 16, 'IDENTIDAD', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 34);
        $mpdf->SetXY($s3TextLeft, $s3TitleY + 16 + $s3TitleGap);
        $mpdf->Cell($s3TextW, 14, 'PROVINCIAL', 0, 1, 'L');
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
        $s3Cell(false, 'Un territorio con ', 0);
        $s3Cell(true, 'capacidad productiva diversa', 0);
        $s3Cell(false, ' y', 1);
        $mpdf->SetXY($s3TextLeft, $s3ParaY + $s3LineH);
        $s3Cell(true, 'proyección', 0);
        $s3Cell(false, ' para la vinculación comercial.', 1);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 4) {
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
        $mpdf->Cell(32, 8, 'Página 04', 0, 0, 'R');
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
        $mpdf->Cell($s4TitleW, 14, 'EMPRESAS', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($s4TitleLeft, $s4TextRowY + 14 + $s4TitleGap);
        $mpdf->Cell($s4TitleW, 14, 'EXPORTADORAS', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->SetLeftMargin($s4ParaLeft);
        $mpdf->SetRightMargin($s4Pad);
        $mpdf->SetXY($s4ParaLeft, $s4TextRowY);
        $mpdf->MultiCell($s4ParaW, 7, 'Empresas registradas y productos/servicios exportables declarados para su difusión institucional.', 0, 'L');
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
            $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $pageNum), 0, 0, 'R');
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
            $s5TitleY = $s5ContentY + $s5ContentPad + round($s5ContentH * 0.28);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', 32);
            $mpdf->SetXY($s5LeftX, $s5TitleY);
            $mpdf->Cell($s5LeftColW, 11, 'NOMBRE DE LA', 0, 1, 'L');
            $s5TitleGap = 6;
            $mpdf->SetTextColor(141, 188, 220);
            $mpdf->SetFont('dejavusans', 'B', 44);
            $nombreEmpresa = $emp['name'] ?? '';
            $mpdf->SetXY($s5LeftX, $s5TitleY + 11 + $s5TitleGap);
            $mpdf->Cell($s5LeftColW, 16, function_exists('mb_strtoupper') ? mb_strtoupper($nombreEmpresa) : strtoupper($nombreEmpresa), 0, 1, 'L');
            $s5ImgH = round($s5ContentInnerH * 0.82);
            $s5ImgY = $s5ContentY + $s5TopPad + ($s5ContentInnerH - $s5ImgH) / 2;
            $compImgPath = $imagenesPorEmpresa[$cid] ?? $logosPorEmpresa[$cid] ?? null;
            if ($compImgPath && file_exists($compImgPath)) {
                $mpdf->Image($compImgPath, $s5ImgX, $s5ImgY, $s5ImgW, $s5ImgH);
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
                ['Actividad principal', $emp['main_activity'] ?? '-'],
                ['Localidad', $localidadPorEmpresa[$cid] ?? '-'],
                ['Sitio Web', $emp['website'] ?? '-'],
                ['Redes sociales', isset($redesPorEmpresa[$cid]) ? implode(' ', $redesPorEmpresa[$cid]) : '-'],
                ['Año de Inicio de actividades', !empty($emp['start_date']) ? date('Y', (int)$emp['start_date']) : '-'],
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
                $s5PanelY += $s5LabelH + 14 + $s5RowGap;
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
        $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $prodIntroPageNum), 0, 0, 'R');
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
        $mpdf->Cell($p6TitleW, 14, 'PRODUCTOS', 0, 1, 'L');
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
        $mpdf->MultiCell($p6ParaW, 7, 'Productos y servicios exportables declarados para su difusión institucional.', 0, 'L');
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
            $p7CompanyNameById[(int)($c['id'] ?? 0)] = $c['name'] ?? '';
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
            $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $prodPageNum), 0, 0, 'R');
            $p7LineH = 0.5;
            $p7LineGap = 21;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($p7Pad, $p7HeaderH - $p7LineGap - $p7LineH, $wMm - 2 * $p7Pad, $p7LineH, 'F');
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect(0, $p7ContentY, $wMm, $p7ContentH, 'F');
            $n = count($chunk);
            $p7ImgGap = 8;
            $p7ImgTopPad = 10;
            $p7ImgH = round($p7ContentH * 0.44);
            $p7ImgTotalW = ($wMm - 2 * $p7Pad - ($n - 1) * $p7ImgGap) * 0.92;
            $p7ImgW = $p7ImgTotalW / $n;
            $p7RowW = $n * $p7ImgW + ($n - 1) * $p7ImgGap;
            $p7StartX = $p7Pad + (($wMm - 2 * $p7Pad) - $p7RowW) / 2;
            $p7BlueY = $p7ContentY + $p7ImgTopPad + $p7ImgH + 12;
            $p7BlueH = $p7ContentH - ($p7ImgTopPad + $p7ImgH + 12) - $p7Pad;
            $p7BlueX = $p7Pad;
            $p7BlueW = $wMm - 2 * $p7Pad;
            foreach ($chunk as $k => $prod) {
                $pid = (int) $prod['id'];
                $p7x = $p7StartX + $k * ($p7ImgW + $p7ImgGap);
                $imgPath = $imagenesPorProducto[$pid] ?? null;
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
            $p7BlueColor = [11, 24, 120];
            $mpdf->SetFillColor($p7BlueColor[0], $p7BlueColor[1], $p7BlueColor[2]);
            $mpdf->Rect($p7BlueX, $p7BlueY, $p7BlueW, $p7BlueH, 'F');
            $p7ColW = $p7BlueW / $n;
            $p7ColPad = 12;
            $p7LineDraw = [200, 200, 220];
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
                $p7textY = $p7BlueY + $p7ColPad;
                $p7RightPad = 10;
                $p7LabelW = 40;
                $mpdf->SetTextColor(255, 255, 255);
                $mpdf->SetFont('dejavusans', 'B', 14);
                $mpdf->SetXY($p7textX, $p7textY);
                $mpdf->Cell($p7LabelW, 8, 'EMPRESA:', 0, 0, 'L');
                $p7EmpresaName = $p7CompanyNameById[(int)($prod['company_id'] ?? 0)] ?? '-';
                $mpdf->Cell($p7textW - $p7LabelW - $p7RightPad, 8, $p7EmpresaName, 0, 1, 'L');
                $p7textY += 8;
                $typeLabel = (isset($prod['type']) && strtolower($prod['type']) === 'service') ? 'SERVICIO:' : 'PRODUCTO:';
                $mpdf->SetFont('dejavusans', 'B', 14);
                $mpdf->SetXY($p7textX, $p7textY);
                $mpdf->Cell($p7LabelW, 8, $typeLabel, 0, 0, 'L');
                $mpdf->SetFont('dejavusans', 'B', 14);
                $nameStr = $prod['name'] ?? '';
                $mpdf->Cell($p7textW - $p7LabelW - $p7RightPad, 8, $nameStr, 0, 1, 'L');
                $mpdf->SetX($p7textX);
                $mpdf->SetFont('dejavusans', '', 11);
                $descStr = trim($prod['description'] ?? '') ?: 'Breve descripción del producto';
                $mpdf->MultiCell($p7textW, 6, $descStr, 0, 'L');
                $mpdf->SetXY($p7textX, $p7BottomY);
                $mpdf->SetFont('dejavusans', '', 11);
                $mpdf->Cell($p7textW, 7, 'Exportación anual: ' . (trim($prod['annual_export'] ?? '') ?: '-'), 0, 1, 'L');
                $mpdf->SetX($p7textX);
                $mpdf->Cell($p7textW, 7, 'Certificaciones: ' . (trim($prod['certifications'] ?? '') ?: '-'), 0, 1, 'L');
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
        $mpdf->Cell($s7FullW - 2 * $s7Pad, 14, 'CONTACTO', 0, 1, 'L');
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
        $s7Loc = trim($contacto['localidad_direccion'] ?? '') ?: 'Localidad';
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
            <p style="margin:6px 0 2px;font-size:12px;color:#333;">EMPRESA: <strong>' . htmlspecialchars($companyName) . '</strong></p>
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
