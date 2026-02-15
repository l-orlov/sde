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
$q = "SELECT c.id, c.name, c.main_activity, c.website, c.start_date, c.organization_type
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

// Localidad, departamento y domicilio por empresa (desde company_addresses, primera dirección)
$localidadPorEmpresa = [];
$departamentoPorEmpresa = [];
$domicilioPorEmpresa = [];
$descripcionPorEmpresa = []; // breve descripción: primer producto por empresa, truncado
if (!empty($companyIds)) {
    $ids = implode(',', array_map('intval', $companyIds));
    $q = "SELECT company_id, locality, department, street, street_number FROM company_addresses WHERE company_id IN ($ids) ORDER BY company_id, id ASC";
    $r = @mysqli_query($link, $q);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($localidadPorEmpresa[$cid]) && $row['locality'] !== null && $row['locality'] !== '') {
                $localidadPorEmpresa[$cid] = $row['locality'];
            }
            if (!isset($departamentoPorEmpresa[$cid]) && !empty(trim($row['department'] ?? ''))) {
                $departamentoPorEmpresa[$cid] = trim($row['department']);
            }
            if (!isset($domicilioPorEmpresa[$cid])) {
                $parts = array_filter([trim($row['street'] ?? ''), trim($row['street_number'] ?? '')]);
                $domicilioPorEmpresa[$cid] = implode(' ', $parts);
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

// Contacto principal por empresa (cargo, email, teléfono desde company_contacts)
$contactoPorEmpresa = [];
if (!empty($companyIds)) {
    $check = @mysqli_query($link, "SHOW TABLES LIKE 'company_contacts'");
    if ($check && mysqli_num_rows($check) > 0) {
        $ids = implode(',', array_map('intval', $companyIds));
        $q = "SELECT company_id, position, email, area_code, phone FROM company_contacts WHERE company_id IN ($ids) ORDER BY company_id, id ASC";
        $r = @mysqli_query($link, $q);
        if ($r) {
            while ($row = mysqli_fetch_assoc($r)) {
                $cid = (int) $row['company_id'];
                if (!isset($contactoPorEmpresa[$cid])) {
                    $contactoPorEmpresa[$cid] = [
                        'position' => trim($row['position'] ?? ''),
                        'email'    => trim($row['email'] ?? ''),
                        'phone'    => trim(($row['area_code'] ?? '') . ' ' . ($row['phone'] ?? '')),
                    ];
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
        // Slide 1 corporativo: solo API. Azul = 44%; imagen izq. con marco; texto der. EMPRESA/EMPRENDIMIENTO; header con logo SDE + logo empresa
        $s1Pad = 20;
        $s1MiddleH = round($hMm * 0.44);
        $remaining = $hMm - $s1MiddleH;
        $s1HeaderH = (int) round($remaining * 0.42);
        $s1FooterH = $remaining - $s1HeaderH;
        $s1MiddleOffset = 10;
        $s1MiddleY = $s1HeaderH + $s1MiddleOffset;
        $s1MiddleH = $s1MiddleH - $s1MiddleOffset;

        // Franja superior negra: logo SDE, a la derecha logo empresa, Página 01 al borde derecho
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
        $s1LogoGap = 12;
        $s1CompanyLogoX = $s1LogoX + $s1LogoW + $s1LogoGap;
        $s1CompanyLogoW = 44;
        $s1CompanyLogoH = 22;
        $s1FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $s1CompanyLogoPath = null;
        if ($s1FirstCompanyId && isset($logosPorEmpresa[$s1FirstCompanyId])) {
            $s1CompanyLogoPath = $logosPorEmpresa[$s1FirstCompanyId];
        } elseif ($s1FirstCompanyId && isset($imagenesPorEmpresa[$s1FirstCompanyId])) {
            $s1CompanyLogoPath = $imagenesPorEmpresa[$s1FirstCompanyId];
        }
        if ($s1CompanyLogoPath && file_exists($s1CompanyLogoPath)) {
            $imgSize = @getimagesize($s1CompanyLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($s1CompanyLogoH * $r <= $s1CompanyLogoW) {
                    $lw = $s1CompanyLogoH * $r;
                    $lh = $s1CompanyLogoH;
                } else {
                    $lw = $s1CompanyLogoW;
                    $lh = $s1CompanyLogoW / $r;
                }
                $mpdf->Image($s1CompanyLogoPath, $s1CompanyLogoX, ($s1HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $s1Pad - 36, ($s1HeaderH - 8) / 2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell(32, 8, 'Página 01', 0, 0, 'R');

        $s1LineH = 0.5;
        $s1LineGap = 12;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($s1Pad, $s1HeaderH - $s1LineGap - $s1LineH, $wMm - 2 * $s1Pad, $s1LineH, 'F');

        // Franja negra entre header y bloque azul (por el offset)
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s1HeaderH, $wMm, $s1MiddleOffset, 'F');

        // Zona central azul (#0B1878); franja inferior negra
        $mpdf->SetFillColor(11, 24, 120);
        $mpdf->Rect(0, $s1MiddleY, $wMm, $s1MiddleH, 'F');

        $s1FootY = $hMm - $s1FooterH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $s1FootY, $wMm, $s1FooterH, 'F');

        // Imagen recortada cuadrada 40%, marco blanco, rotación -3°
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

        // Texto a la derecha: EMPRESA /, EMPRENDIMIENTO, ACTIVIDAD • LOCALIDAD
        $s1TextLeft = $s1ImgX + $s1ImgBoxW + 24;
        $s1TextW = $wMm - $s1TextLeft - $s1Pad;
        $mpdf->SetLeftMargin($s1TextLeft);
        $mpdf->SetRightMargin($s1Pad);
        $s1Ty = $s1MiddleY + 28;
        $mpdf->SetXY($s1TextLeft, $s1Ty);
        $mpdf->SetFont('dejavusans', 'B', 56);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell($s1TextW, 20, 'EMPRESA /', 0, 1, 'L');
        $mpdf->SetFont('dejavusans', 'B', 56);
        $mpdf->Cell($s1TextW, 20, 'EMPRENDIMIENTO', 0, 1, 'L');
        $s1Sub = 'ACTIVIDAD PRINCIPAL • LOCALIDAD';
        if (!empty($companies[0])) {
            $act = trim($companies[0]['main_activity'] ?? '');
            $cid = $companies[0]['id'] ?? null;
            $loc = isset($cid, $localidadPorEmpresa[$cid]) ? trim($localidadPorEmpresa[$cid]) : '';
            if ($act !== '' || $loc !== '') {
                $s1Sub = (function_exists('mb_strtoupper') ? mb_strtoupper($act) : strtoupper($act)) . ($act !== '' && $loc !== '' ? ' • ' : '') . (function_exists('mb_strtoupper') ? mb_strtoupper($loc) : strtoupper($loc));
            }
        }
        $mpdf->SetFont('dejavusans', '', 22);
        $mpdf->SetXY($s1TextLeft, $s1Ty + 48);
        $mpdf->Cell($s1TextW, 10, $s1Sub, 0, 0, 'L');

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

        // Slide Perfil de la empresa (Página 02): header igual; izquierda título PERFIL/DE LA/EMPRESA; centro imagen; derecha bloque azul con ACTIVIDAD PRINCIPAL, UBICACIÓN, CANALES, CONTACTO
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $pfPad = 20;
        $pfMiddleH = round($hMm * 0.44);
        $pfRemaining = $hMm - $pfMiddleH;
        $pfHeaderH = (int) round($pfRemaining * 0.42);
        $pfContentY = $pfHeaderH;
        $pfContentH = $hMm - $pfHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $pfHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $pfLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $pfLogoX = $pfPad;
        $pfLogoW = 44;
        $pfLogoH = 22;
        if (file_exists($pfLogoPath)) {
            $imgSize = @getimagesize($pfLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($pfLogoH * $r <= $pfLogoW) ? $pfLogoH * $r : $pfLogoW;
                $lh = ($pfLogoH * $r <= $pfLogoW) ? $pfLogoH : $pfLogoW / $r;
                $mpdf->Image($pfLogoPath, $pfLogoX, ($pfHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $pfLogoGap = 12;
        $pfCompanyLogoX = $pfLogoX + $pfLogoW + $pfLogoGap;
        $pfCompanyLogoW = 44;
        $pfCompanyLogoH = 22;
        $pfFirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $pfCompanyLogoPath = null;
        if ($pfFirstCompanyId && isset($logosPorEmpresa[$pfFirstCompanyId])) {
            $pfCompanyLogoPath = $logosPorEmpresa[$pfFirstCompanyId];
        } elseif ($pfFirstCompanyId && isset($imagenesPorEmpresa[$pfFirstCompanyId])) {
            $pfCompanyLogoPath = $imagenesPorEmpresa[$pfFirstCompanyId];
        }
        if ($pfCompanyLogoPath && file_exists($pfCompanyLogoPath)) {
            $imgSize = @getimagesize($pfCompanyLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($pfCompanyLogoH * $r <= $pfCompanyLogoW) ? $pfCompanyLogoH * $r : $pfCompanyLogoW;
                $lh = ($pfCompanyLogoH * $r <= $pfCompanyLogoW) ? $pfCompanyLogoH : $pfCompanyLogoW / $r;
                $mpdf->Image($pfCompanyLogoPath, $pfCompanyLogoX, ($pfHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $pfPad - 36, ($pfHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Página 02', 0, 0, 'R');
        $pfLineH = 0.5;
        $pfLineGap = 12;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($pfPad, $pfHeaderH - $pfLineGap - $pfLineH, $wMm - 2 * $pfPad, $pfLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $pfContentY, $wMm, $pfContentH, 'F');

        $pfLeftW = round($wMm * 0.26);
        $pfCenterW = round($wMm * 0.34);
        $pfRightW = $wMm - $pfLeftW - $pfCenterW - 2 * $pfPad - 8;
        $pfLogoSize = 56;
        $pfImgOffsetRight = 10;
        $pfImgX = $pfLeftW + $pfPad + ($pfCenterW - 8 - $pfLogoSize) / 2 + 4 + $pfImgOffsetRight;
        $pfImgY = $pfContentY + ($pfContentH - 12 - $pfLogoSize) / 2 + 6;
        $pfImgW = $pfImgH = $pfLogoSize;
        $pfTitleX = $pfPad;
        $pfTitleW = $pfLeftW - 4;
        $pfTitleY = $pfContentY + 26;
        $mpdf->SetXY($pfTitleX, $pfTitleY);
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 38);
        $mpdf->Cell($pfTitleW, 14, 'PERFIL DE LA', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 38);
        $mpdf->SetXY($pfTitleX, $pfTitleY + 20);
        $mpdf->Cell($pfTitleW, 14, 'EMPRESA', 0, 1, 'L');

        $pfImgPath = $pfCompanyLogoPath;
        if (!$pfImgPath || !file_exists($pfImgPath)) {
            $pfImgPath = ($pfFirstCompanyId && isset($imagenesPorEmpresa[$pfFirstCompanyId]) && file_exists($imagenesPorEmpresa[$pfFirstCompanyId]))
                ? $imagenesPorEmpresa[$pfFirstCompanyId]
                : null;
        }
        if ($pfImgPath && file_exists($pfImgPath)) {
            $mpdf->Image($pfImgPath, $pfImgX, $pfImgY, $pfImgW, $pfImgH);
        } else {
            $mpdf->SetFillColor(40, 40, 50);
            $mpdf->Rect($pfImgX, $pfImgY, $pfImgW, $pfImgH, 'F');
        }

        $pfPanelMargin = 5;
        $pfPanelBottomMargin = 14;
        $pfPanelX = $pfLeftW + $pfCenterW + $pfPad + 4 + $pfPanelMargin;
        $pfPanelW = $pfRightW - 2 * $pfPanelMargin;
        $pfPanelY = $pfContentY + 6 + $pfPanelMargin;
        $pfPanelH = $pfContentH - 12 - $pfPanelMargin - $pfPanelBottomMargin;
        $pfBlue = [11, 24, 120];
        $mpdf->SetFillColor($pfBlue[0], $pfBlue[1], $pfBlue[2]);
        $mpdf->Rect($pfPanelX, $pfPanelY, $pfPanelW, $pfPanelH, 'F');
        $pfInnerPad = 12;
        $pfLineSep = 0.4;
        $pfTitleFs = 13;
        $pfLabelFs = 11;
        $pfRowH = 6;
        $pfFirst = $companies[0] ?? [];
        $pfCid = $pfFirst['id'] ?? null;
        $pfOrgType = trim($pfFirst['organization_type'] ?? '');
        $pfAct = trim($pfFirst['main_activity'] ?? '');
        $pfLoc = $pfCid && isset($localidadPorEmpresa[$pfCid]) ? $localidadPorEmpresa[$pfCid] : '';
        $pfDept = $pfCid && isset($departamentoPorEmpresa[$pfCid]) ? $departamentoPorEmpresa[$pfCid] : '';
        $pfDom = $pfCid && isset($domicilioPorEmpresa[$pfCid]) ? $domicilioPorEmpresa[$pfCid] : '';
        $pfWeb = trim($pfFirst['website'] ?? '');
        $pfRedes = ($pfCid && isset($redesPorEmpresa[$pfCid]) && is_array($redesPorEmpresa[$pfCid])) ? implode(', ', $redesPorEmpresa[$pfCid]) : '';
        $pfContact = ($pfCid && isset($contactoPorEmpresa[$pfCid])) ? $contactoPorEmpresa[$pfCid] : ['position' => '', 'email' => '', 'phone' => ''];
        $pfSections = [
            ['ACTIVIDAD PRINCIPAL', ['Tipo de Organización: ' . $pfOrgType, 'Actividad principal: ' . $pfAct]],
            ['UBICACIÓN', ['Localidad: ' . $pfLoc, 'Departamento: ' . $pfDept, 'Domicilio: ' . $pfDom]],
            ['CANALES', ['Web: ' . $pfWeb, 'Redes: ' . $pfRedes]],
            ['CONTACTO', ['Cargo: ' . $pfContact['position'], 'Email: ' . $pfContact['email'], 'Teléfono: ' . $pfContact['phone']]],
        ];
        $pfY = $pfPanelY + $pfInnerPad;
        $pfSectionGap = 8;
        $mpdf->SetLeftMargin($pfPanelX + $pfInnerPad);
        $mpdf->SetRightMargin($wMm - $pfPanelX - $pfPanelW + $pfInnerPad);
        foreach ($pfSections as $si => $sec) {
            if ($si > 0) {
                $pfY += $pfSectionGap / 2;
                $mpdf->SetFillColor(255, 255, 255);
                $mpdf->Rect($pfPanelX + $pfInnerPad, $pfY - $pfLineSep / 2, $pfPanelW - 2 * $pfInnerPad, $pfLineSep, 'F');
                $pfY += 2 + $pfSectionGap / 2;
            }
            $mpdf->SetXY($pfPanelX + $pfInnerPad, $pfY);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', $pfTitleFs);
            $mpdf->Cell($pfPanelW - 2 * $pfInnerPad, $pfRowH, $sec[0], 0, 1, 'L');
            $mpdf->SetFont('dejavusans', '', $pfLabelFs);
            $pfLabelRowH = 5.5;
            foreach ($sec[1] as $line) {
                $mpdf->SetX($pfPanelX + $pfInnerPad);
                $mpdf->Cell($pfPanelW - 2 * $pfInnerPad, $pfLabelRowH, $line !== '' ? $line : ' ', 0, 1, 'L');
            }
            $pfY += $pfRowH + count($sec[1]) * $pfLabelRowH + $pfSectionGap;
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

    } elseif ($i === 2) {
        // (slide Identidad provincial eliminado)
    } elseif ($i === 3) {
        // (slide Identidad provincial eliminado)
    } elseif ($i === 4) {
        // (slide Empresas exportadoras eliminado)
    } elseif ($i === 5) {
        // Intro slide Productos exportables: mismo estilo que slide 4; dos imágenes (izq más ancha); PRODUCTOS/EXPORTABLES (izq), párrafo (der)
        $prodIntroPageNum = 3;
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
        $p6LogoX = $p6Pad;
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
                $mpdf->Image($p6LogoPath, $p6LogoX, ($p6HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $p6LogoGap = 12;
        $p6CompanyLogoX = $p6LogoX + $p6LogoW + $p6LogoGap;
        $p6CompanyLogoW = 44;
        $p6CompanyLogoH = 22;
        $p6FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $p6CompanyLogoPath = null;
        if ($p6FirstCompanyId && isset($logosPorEmpresa[$p6FirstCompanyId])) {
            $p6CompanyLogoPath = $logosPorEmpresa[$p6FirstCompanyId];
        } elseif ($p6FirstCompanyId && isset($imagenesPorEmpresa[$p6FirstCompanyId])) {
            $p6CompanyLogoPath = $imagenesPorEmpresa[$p6FirstCompanyId];
        }
        if ($p6CompanyLogoPath && file_exists($p6CompanyLogoPath)) {
            $imgSize = @getimagesize($p6CompanyLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                if ($p6CompanyLogoH * $r <= $p6CompanyLogoW) {
                    $lw = $p6CompanyLogoH * $r;
                    $lh = $p6CompanyLogoH;
                } else {
                    $lw = $p6CompanyLogoW;
                    $lh = $p6CompanyLogoW / $r;
                }
                $mpdf->Image($p6CompanyLogoPath, $p6CompanyLogoX, ($p6HeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $p6Pad - 36, ($p6HeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $prodIntroPageNum), 0, 0, 'R');
        $p6LineH = 0.5;
        $p6LineGap = 12;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($p6Pad, $p6HeaderH - $p6LineGap - $p6LineH, $wMm - 2 * $p6Pad, $p6LineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $p6ContentY, $wMm, $p6ContentH, 'F');
        $p6ContentPad = $p6Pad;
        $p6ImgGap = 14;
        $p6ImgLeftW = round(($wMm - 2 * $p6Pad - $p6ImgGap) * 0.62);
        $p6ImgRightW = $wMm - 2 * $p6Pad - $p6ImgGap - $p6ImgLeftW;
        $p6ImgH = round($p6ContentH * 0.48);
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
        $p6TextRowY = $p6ImgY + $p6ImgH + 27;
        $p6TitleLeft = $p6Pad;
        $p6TitleW = $wMm - 2 * $p6Pad;
        $p6TitleGap = 6;
        $p6TitleLineH = 18;
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 52);
        $mpdf->SetXY($p6TitleLeft, $p6TextRowY);
        $mpdf->Cell($p6TitleW, $p6TitleLineH, 'PRODUCTOS Y SERVICIOS', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 52);
        $mpdf->SetXY($p6TitleLeft, $p6TextRowY + $p6TitleLineH + $p6TitleGap);
        $mpdf->Cell($p6TitleW, $p6TitleLineH, 'EXPORTABLES', 0, 1, 'L');
        // Un producto por slide: header como p6; izq. imagen grande; der. NOMBRE / PRODUCTO (nombre en azul), número + línea + descripción, etiquetas
        $productoSlidesChunks = array_chunk($productosParaSlides, 1);
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
        foreach ($productosParaSlides as $prodIdx => $prod) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $mpdf->SetTextColor(255, 255, 255);
            $p7Pad = 20;
            $p7MiddleH = round($hMm * 0.44);
            $p7Remaining = $hMm - $p7MiddleH;
            $p7HeaderH = (int) round($p7Remaining * 0.42);
            $p7ContentY = $p7HeaderH;
            $p7ContentH = $hMm - $p7HeaderH;
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect(0, 0, $wMm, $p7HeaderH, 'F');
            $p7LogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
            $p7LogoX = $p7Pad;
            $p7LogoW = 44;
            $p7LogoH = 22;
            if (file_exists($p7LogoPath)) {
                $imgSize = @getimagesize($p7LogoPath);
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $r = $imgSize[0] / $imgSize[1];
                    $lw = ($p7LogoH * $r <= $p7LogoW) ? $p7LogoH * $r : $p7LogoW;
                    $lh = ($p7LogoH * $r <= $p7LogoW) ? $p7LogoH : $p7LogoW / $r;
                    $mpdf->Image($p7LogoPath, $p7LogoX, ($p7HeaderH - $lh) / 2, $lw, $lh);
                }
            }
            $p7LogoGap = 12;
            $p7CompanyLogoX = $p7LogoX + $p7LogoW + $p7LogoGap;
            $p7CompanyLogoW = 44;
            $p7CompanyLogoH = 22;
            $p7FirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
            $p7CompanyLogoPath = null;
            if ($p7FirstCompanyId && isset($logosPorEmpresa[$p7FirstCompanyId])) {
                $p7CompanyLogoPath = $logosPorEmpresa[$p7FirstCompanyId];
            } elseif ($p7FirstCompanyId && isset($imagenesPorEmpresa[$p7FirstCompanyId])) {
                $p7CompanyLogoPath = $imagenesPorEmpresa[$p7FirstCompanyId];
            }
            if ($p7CompanyLogoPath && file_exists($p7CompanyLogoPath)) {
                $imgSize = @getimagesize($p7CompanyLogoPath);
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $r = $imgSize[0] / $imgSize[1];
                    $lw = ($p7CompanyLogoH * $r <= $p7CompanyLogoW) ? $p7CompanyLogoH * $r : $p7CompanyLogoW;
                    $lh = ($p7CompanyLogoH * $r <= $p7CompanyLogoW) ? $p7CompanyLogoH : $p7CompanyLogoW / $r;
                    $mpdf->Image($p7CompanyLogoPath, $p7CompanyLogoX, ($p7HeaderH - $lh) / 2, $lw, $lh);
                }
            }
            $mpdf->SetFont('dejavusans', '', 17);
            $mpdf->SetXY($wMm - $p7Pad - 36, ($p7HeaderH - 8) / 2);
            $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $prodPageNum), 0, 0, 'R');
            $p7LineH = 0.5;
            $p7LineGap = 12;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($p7Pad, $p7HeaderH - $p7LineGap - $p7LineH, $wMm - 2 * $p7Pad, $p7LineH, 'F');
            $mpdf->SetFillColor(0, 0, 0);
            $mpdf->Rect(0, $p7ContentY, $wMm, $p7ContentH, 'F');
            $p7ImgBottomPad = 20;
            $p7ImgW = round(($wMm - 2 * $p7Pad) * 0.35);
            $p7ImgGap = 32;
            $p7TextOffsetRight = 27;
            $p7ImgX = $p7Pad;
            $p7ImgH = $p7ContentH - $p7ImgBottomPad;
            $pid = (int) $prod['id'];
            $imgPath = $imagenesPorProducto[$pid] ?? null;
            $tmp = $p7LoadCrop($imgPath, $p7ImgW, $p7ImgH);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $p7ImgX, $p7ContentY, $p7ImgW, $p7ImgH);
                @unlink($tmp);
            } elseif ($imgPath && file_exists($imgPath)) {
                $mpdf->Image($imgPath, $p7ImgX, $p7ContentY, $p7ImgW, $p7ImgH);
            } else {
                $mpdf->SetFillColor(50, 50, 50);
                $mpdf->Rect($p7ImgX, $p7ContentY, $p7ImgW, $p7ImgH, 'F');
            }
            $p7RightX = $p7ImgX + $p7ImgW + $p7ImgGap + $p7TextOffsetRight;
            $p7RightW = $wMm - $p7RightX - $p7Pad;
            $p7TextY = $p7ContentY + 38;
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', 42);
            $mpdf->SetXY($p7RightX, $p7TextY);
            $mpdf->Cell($p7RightW, 16, 'NOMBRE', 0, 1, 'L');
            $mpdf->SetTextColor(141, 188, 220);
            $mpdf->SetFont('dejavusans', 'B', 42);
            $nameStr = $prod['name'] ?? '';
            $mpdf->SetXY($p7RightX, $p7TextY + 18);
            $mpdf->Cell($p7RightW, 16, $nameStr, 0, 1, 'L');
            $p7TextY += 18 + 16 + 22;
            $p7NumW = 16;
            $p7NumX = $p7RightX;
            $mpdf->SetTextColor(141, 188, 220);
            $mpdf->SetFont('dejavusans', 'B', 22);
            $mpdf->SetXY($p7NumX, $p7TextY);
            $mpdf->Cell($p7NumW, 12, sprintf('%02d', $prodIdx + 1), 0, 0, 'L');
            $p7LineX = $p7NumX + $p7NumW + 8;
            $p7DescX = $p7LineX + 12;
            $p7DescW = $p7RightW - ($p7DescX - $p7RightX);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', '', 16);
            $mpdf->SetXY($p7DescX, $p7TextY);
            $descStr = trim($prod['description'] ?? '') ?: 'Breve descripción del producto';
            $mpdf->MultiCell($p7DescW, 7, $descStr, 0, 'L');
            $p7TextY += 32;
            $p7LabelFont = 'dejavusans';
            $p7LabelSize = 14;
            $p7LabelH = 9;
            $p7LineBottom = $p7TextY + 4 * $p7LabelH + 10;
            $mpdf->SetDrawColor(255, 255, 255);
            $mpdf->SetLineWidth(0.3);
            $mpdf->Line($p7LineX, $p7TextY - 32, $p7LineX, $p7LineBottom);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont($p7LabelFont, 'B', $p7LabelSize);
            $mpdf->SetXY($p7DescX, $p7TextY);
            $mpdf->Cell($p7DescW, $p7LabelH, 'Exportación anual (USD): ' . (trim($prod['annual_export'] ?? '') ?: '-'), 0, 1, 'L');
            $mpdf->SetX($p7DescX);
            $mpdf->Cell($p7DescW, $p7LabelH, 'Certificaciones: ' . (trim($prod['certifications'] ?? '') ?: '-'), 0, 1, 'L');
            $mpdf->SetX($p7DescX);
            $mpdf->Cell($p7DescW, $p7LabelH, 'Mercados actuales: -', 0, 1, 'L');
            $mpdf->SetX($p7DescX);
            $mpdf->Cell($p7DescW, $p7LabelH, 'Mercados de interés: -', 0, 1, 'L');
            $mpdf->SetDrawColor(0, 0, 0);
            $prodPageNum++;
        }
        $productoSlidesChunks = array_chunk($productosParaSlides, 1);
    } elseif ($i === 6) {
        // Slide Nuestra Historia (antes de Competitividad): siguiente página al último slide de productos
        $histPageNum = $prodIntroPageNum + 1 + count($productoSlidesChunks);
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $histPad = 20;
        $histMiddleH = round($hMm * 0.44);
        $histRemaining = $hMm - $histMiddleH;
        $histHeaderH = (int) round($histRemaining * 0.42);
        $histContentY = $histHeaderH;
        $histContentH = $hMm - $histHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $histHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $histLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $histLogoX = $histPad;
        $histLogoW = 44;
        $histLogoH = 22;
        if (file_exists($histLogoPath)) {
            $imgSize = @getimagesize($histLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($histLogoH * $r <= $histLogoW) ? $histLogoH * $r : $histLogoW;
                $lh = ($histLogoH * $r <= $histLogoW) ? $histLogoH : $histLogoW / $r;
                $mpdf->Image($histLogoPath, $histLogoX, ($histHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $histLogoGap = 12;
        $histCompanyLogoX = $histLogoX + $histLogoW + $histLogoGap;
        $histCompanyLogoW = 44;
        $histCompanyLogoH = 22;
        $histFirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $histCompanyLogoPath = null;
        if ($histFirstCompanyId && isset($logosPorEmpresa[$histFirstCompanyId])) {
            $histCompanyLogoPath = $logosPorEmpresa[$histFirstCompanyId];
        } elseif ($histFirstCompanyId && isset($imagenesPorEmpresa[$histFirstCompanyId])) {
            $histCompanyLogoPath = $imagenesPorEmpresa[$histFirstCompanyId];
        }
        if ($histCompanyLogoPath && file_exists($histCompanyLogoPath)) {
            $imgSize = @getimagesize($histCompanyLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($histCompanyLogoH * $r <= $histCompanyLogoW) ? $histCompanyLogoH * $r : $histCompanyLogoW;
                $lh = ($histCompanyLogoH * $r <= $histCompanyLogoW) ? $histCompanyLogoH : $histCompanyLogoW / $r;
                $mpdf->Image($histCompanyLogoPath, $histCompanyLogoX, ($histHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $histPad - 36, ($histHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $histPageNum), 0, 0, 'R');
        $histLineH = 0.5;
        $histLineGap = 12;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($histPad, $histHeaderH - $histLineGap - $histLineH, $wMm - 2 * $histPad, $histLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $histContentY, $wMm, $histContentH, 'F');
        $histLeftW = round($wMm * 0.36);
        $histTextPad = 16;
        $histTextLeft = $histPad;
        $histTextW = $histLeftW - $histPad - $histTextPad;
        $histTextTop = $histContentY + 7;
        $histFontSize = 15;
        $histLineHeight = 7;
        $histImgGap = 8;
        $histImgW = ($wMm - $histLeftW - 16 - $histImgGap - $histPad) / 2 * 0.88;
        $histImgH = round($histContentH * 0.82);
        $histImgY = $histContentY + 4;
        $histImgBottom = $histImgY + $histImgH;
        $histImgRightEdge = $wMm - $histPad;
        $histRightX = $histImgRightEdge - 2 * $histImgW - $histImgGap;
        $mpdf->SetLeftMargin($histTextLeft);
        $mpdf->SetRightMargin($histLeftW);
        $mpdf->SetXY($histTextLeft, $histTextTop);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->Cell($histTextW, 14, 'NUESTRA', 0, 1, 'L');
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($histTextLeft, $histTextTop + 16);
        $mpdf->Cell($histTextW, 16, 'HISTORIA', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', $histFontSize);
        $mpdf->Ln(8);
        $histPara = 'Nisi justo faucibus lectus blandit donec gravida proin natoque, malesuada a facilisis dictumst rhoncus pulvinar aliquet feugiat ultrices, mollis phasellus varius tortor habitasse purus enim. Nunc lacus sociis tortor volutpat egestas vel duis erat, eleifend dapibus praesent vehicula fringilla ac suscipit conubia, nibh pulvinar elementum faucibus urna nullam luctus. Augue senectus rutrum suscipit habitasse felis aptent phasellus, nec hendrerit mattis enim congue tempor auctor magnis, mollis neque libero sagittis urna orci.';
        $mpdf->MultiCell($histTextW, $histLineHeight, $histPara, 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($histTextLeft, $histImgBottom - 0.5, $histTextW, 0.5, 'F');
        $histScale = 100 / 25.4;
        $histDstWpx = (int) max(1, round($histImgW * $histScale));
        $histDstHpx = (int) max(1, round($histImgH * $histScale));
        foreach ([0, 1] as $idx) {
            $path = isset($productivoSlide2Paths[$idx]) ? $productivoSlide2Paths[$idx] : null;
            if (!$path || !file_exists($path)) continue;
            $info = @getimagesize($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $src = false;
            if ($info && $info[2] === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path);
            elseif ($info && $info[2] === IMAGETYPE_PNG) $src = @imagecreatefrompng($path);
            elseif (($ext === 'webp' || ($info && $info[2] === 18)) && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path);
            $histCropPath = null;
            if ($src && $histDstWpx > 0 && $histDstHpx > 0) {
                $sw = imagesx($src);
                $sh = imagesy($src);
                $boxRatio = $histImgW / $histImgH;
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
                $dst = @imagecreatetruecolor($histDstWpx, $histDstHpx);
                if ($dst && @imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $histDstWpx, $histDstHpx, $cropW, $cropH)) {
                    $tmp = sys_get_temp_dir() . '/corp_hist_img_' . $idx . '_' . uniqid() . '.png';
                    if (imagepng($dst, $tmp)) $histCropPath = $tmp;
                    imagedestroy($dst);
                }
                imagedestroy($src);
            }
            $histImgX = $histRightX + $idx * ($histImgW + $histImgGap);
            if ($histCropPath && file_exists($histCropPath)) {
                $mpdf->Image($histCropPath, $histImgX, $histImgY, $histImgW, $histImgH);
                @unlink($histCropPath);
            } else {
                $mpdf->Image($path, $histImgX, $histImgY, $histImgW, $histImgH);
            }
        }
        // Slide Competitividad y diferenciación (después de Nuestra Historia, antes de Contacto)
        $compPageNum = $histPageNum + 1;
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $mpdf->SetTextColor(255, 255, 255);
        $cPad = 20;
        $cMiddleH = round($hMm * 0.44);
        $cRemaining = $hMm - $cMiddleH;
        $cHeaderH = (int) round($cRemaining * 0.42);
        $cContentY = $cHeaderH;
        $cContentH = $hMm - $cHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $cHeaderH, 'F');
        $cLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $cLogoX = $cPad;
        $cLogoW = 44;
        $cLogoH = 22;
        if (file_exists($cLogoPath)) {
            $imgSize = @getimagesize($cLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($cLogoH * $r <= $cLogoW) ? $cLogoH * $r : $cLogoW;
                $lh = ($cLogoH * $r <= $cLogoW) ? $cLogoH : $cLogoW / $r;
                $mpdf->Image($cLogoPath, $cLogoX, ($cHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $cLogoGap = 12;
        $cCompanyLogoX = $cLogoX + $cLogoW + $cLogoGap;
        $cCompanyLogoW = 44;
        $cCompanyLogoH = 22;
        $cFirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $cCompanyLogoPath = null;
        if ($cFirstCompanyId && isset($logosPorEmpresa[$cFirstCompanyId])) {
            $cCompanyLogoPath = $logosPorEmpresa[$cFirstCompanyId];
        } elseif ($cFirstCompanyId && isset($imagenesPorEmpresa[$cFirstCompanyId])) {
            $cCompanyLogoPath = $imagenesPorEmpresa[$cFirstCompanyId];
        }
        if ($cCompanyLogoPath && file_exists($cCompanyLogoPath)) {
            $imgSize = @getimagesize($cCompanyLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($cCompanyLogoH * $r <= $cCompanyLogoW) ? $cCompanyLogoH * $r : $cCompanyLogoW;
                $lh = ($cCompanyLogoH * $r <= $cCompanyLogoW) ? $cCompanyLogoH : $cCompanyLogoW / $r;
                $mpdf->Image($cCompanyLogoPath, $cCompanyLogoX, ($cHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $cPad - 36, ($cHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $compPageNum), 0, 0, 'R');
        $cLineH = 0.5;
        $cLineGap = 12;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($cPad, $cHeaderH - $cLineGap - $cLineH, $wMm - 2 * $cPad, $cLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $cContentY, $wMm, $cContentH, 'F');
        $cImgGap = 14;
        $cImgTotalW = $wMm - 2 * $cPad - $cImgGap;
        $cImgLeftW = round($cImgTotalW * 0.5);
        $cImgRightW = $cImgTotalW - $cImgLeftW;
        $cImgH = round($cContentH * 0.48);
        $cImgY = $cContentY + 8;
        $cScale = 100 / 25.4;
        $cLoadCrop = function ($path, $boxW, $boxH) use ($cScale) {
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
            $dwPx = (int) max(1, round($boxW * $cScale));
            $dhPx = (int) max(1, round($boxH * $cScale));
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
            $tmp = sys_get_temp_dir() . '/corp_comp_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        $cImgPaths = [
            isset($productivoCandidates[0]) ? $productivoCandidates[0] : (isset($empresaSlide4Paths[0]) ? $empresaSlide4Paths[0] : null),
            isset($productivoCandidates[1]) ? $productivoCandidates[1] : (isset($empresaSlide4Paths[1]) ? $empresaSlide4Paths[1] : null),
        ];
        if (!$cImgPaths[0] && !empty($empresaSlide4Paths)) {
            $cImgPaths[0] = $empresaSlide4Paths[0];
        }
        if (!$cImgPaths[1] && !empty($empresaSlide4Paths)) {
            $cImgPaths[1] = count($empresaSlide4Paths) > 1 ? $empresaSlide4Paths[1] : $empresaSlide4Paths[0];
        }
        foreach ([0, 1] as $cidx) {
            $path = $cImgPaths[$cidx];
            $cw = $cidx === 0 ? $cImgLeftW : $cImgRightW;
            $cx = $cPad + $cidx * ($cImgLeftW + $cImgGap);
            $tmp = $cLoadCrop($path, $cw, $cImgH);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $cx, $cImgY, $cw, $cImgH);
                @unlink($tmp);
            } elseif ($path && file_exists($path)) {
                $mpdf->Image($path, $cx, $cImgY, $cw, $cImgH);
            } else {
                $mpdf->SetFillColor(60, 60, 60);
                $mpdf->Rect($cx, $cImgY, $cw, $cImgH, 'F');
            }
        }
        $cTextRowY = $cImgY + $cImgH + 27;
        $cTitleLeft = $cPad;
        $cTitleW = $wMm - 2 * $cPad;
        $cTitleGap = 6;
        $cTitleLineH = 18;
        $mpdf->SetTextColor(141, 188, 220);
        $mpdf->SetFont('dejavusans', 'B', 52);
        $mpdf->SetXY($cTitleLeft, $cTextRowY);
        $mpdf->Cell($cTitleW, $cTitleLineH, 'COMPETITIVIDAD Y', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 52);
        $mpdf->SetXY($cTitleLeft, $cTextRowY + $cTitleLineH + $cTitleGap);
        $mpdf->Cell($cTitleW, $cTitleLineH, 'DIFERENCIACIÓN', 0, 1, 'L');

        // Slide Premios / Ferias / Rondas / Experiencia exportadora / Referencias comerciales (antes de Contacto)
        $logrosPageNum = $compPageNum + 1;
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $logPad = 20;
        $logMiddleH = round($hMm * 0.44);
        $logRemaining = $hMm - $logMiddleH;
        $logHeaderH = (int) round($logRemaining * 0.42);
        $logContentY = $logHeaderH;
        $logContentH = $hMm - $logHeaderH;
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, 0, $wMm, $logHeaderH, 'F');
        $mpdf->SetTextColor(255, 255, 255);
        $logLogoPath = (file_exists($pdfLogoWhitePath)) ? $pdfLogoWhitePath : $pdfLogoPath;
        $logLogoX = $logPad;
        $logLogoW = 44;
        $logLogoH = 22;
        if (file_exists($logLogoPath)) {
            $imgSize = @getimagesize($logLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($logLogoH * $r <= $logLogoW) ? $logLogoH * $r : $logLogoW;
                $lh = ($logLogoH * $r <= $logLogoW) ? $logLogoH : $logLogoW / $r;
                $mpdf->Image($logLogoPath, $logLogoX, ($logHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $logLogoGap = 12;
        $logCompanyLogoX = $logLogoX + $logLogoW + $logLogoGap;
        $logCompanyLogoW = 44;
        $logCompanyLogoH = 22;
        $logFirstCompanyId = !empty($companies[0]['id']) ? $companies[0]['id'] : null;
        $logCompanyLogoPath = null;
        if ($logFirstCompanyId && isset($logosPorEmpresa[$logFirstCompanyId])) {
            $logCompanyLogoPath = $logosPorEmpresa[$logFirstCompanyId];
        } elseif ($logFirstCompanyId && isset($imagenesPorEmpresa[$logFirstCompanyId])) {
            $logCompanyLogoPath = $imagenesPorEmpresa[$logFirstCompanyId];
        }
        if ($logCompanyLogoPath && file_exists($logCompanyLogoPath)) {
            $imgSize = @getimagesize($logCompanyLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $r = $imgSize[0] / $imgSize[1];
                $lw = ($logCompanyLogoH * $r <= $logCompanyLogoW) ? $logCompanyLogoH * $r : $logCompanyLogoW;
                $lh = ($logCompanyLogoH * $r <= $logCompanyLogoW) ? $logCompanyLogoH : $logCompanyLogoW / $r;
                $mpdf->Image($logCompanyLogoPath, $logCompanyLogoX, ($logHeaderH - $lh) / 2, $lw, $lh);
            }
        }
        $mpdf->SetFont('dejavusans', '', 17);
        $mpdf->SetXY($wMm - $logPad - 36, ($logHeaderH - 8) / 2);
        $mpdf->Cell(32, 8, 'Página ' . sprintf('%02d', $logrosPageNum), 0, 0, 'R');
        $logLineH = 0.5;
        $logLineGap = 12;
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($logPad, $logHeaderH - $logLineGap - $logLineH, $wMm - 2 * $logPad, $logLineH, 'F');
        $mpdf->SetFillColor(0, 0, 0);
        $mpdf->Rect(0, $logContentY, $wMm, $logContentH, 'F');
        // Izquierda: dos imágenes
        $logLeftW = round($wMm * 0.48);
        $logImgGap = 8;
        $logImg1W = round(($logLeftW - $logPad - $logImgGap - $logPad) * 0.34);
        $logImg2W = $logLeftW - $logPad - $logImgGap - $logImg1W - $logPad;
        $logImgH = round($logContentH * 0.52);
        $logImgBottomMargin = 18;
        $logImg1Y = $logContentY + $logContentH - $logImgH - $logImgBottomMargin;
        $logImg2Y = $logContentY + 8;
        $logImg1X = $logPad;
        $logImg2X = $logPad + $logImg1W + $logImgGap;
        $logScale = 100 / 25.4;
        $logLoadCrop = function ($path, $boxW, $boxH) use ($logScale) {
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
            $dwPx = (int) max(1, round($boxW * $logScale));
            $dhPx = (int) max(1, round($boxH * $logScale));
            $dst = @imagecreatetruecolor($dwPx, $dhPx);
            if (!$dst || !@imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dwPx, $dhPx, $cropW, $cropH)) {
                if ($dst) imagedestroy($dst);
                imagedestroy($src);
                return null;
            }
            imagedestroy($src);
            $tmp = sys_get_temp_dir() . '/corp_logros_' . uniqid() . '.png';
            if (!imagepng($dst, $tmp)) {
                imagedestroy($dst);
                return null;
            }
            imagedestroy($dst);
            return $tmp;
        };
        $logImgPaths = [
            isset($productivoCandidates[0]) ? $productivoCandidates[0] : (isset($empresaSlide4Paths[0]) ? $empresaSlide4Paths[0] : null),
            isset($productivoCandidates[1]) ? $productivoCandidates[1] : (isset($empresaSlide4Paths[1]) ? $empresaSlide4Paths[1] : null),
        ];
        if (!$logImgPaths[0] && !empty($empresaSlide4Paths)) {
            $logImgPaths[0] = $empresaSlide4Paths[0];
        }
        if (!$logImgPaths[1] && !empty($empresaSlide4Paths)) {
            $logImgPaths[1] = count($empresaSlide4Paths) > 1 ? $empresaSlide4Paths[1] : $empresaSlide4Paths[0];
        }
        foreach ([0, 1] as $lidxs) {
            $path = $logImgPaths[$lidxs];
            $lw = $lidxs === 0 ? $logImg1W : $logImg2W;
            $lx = $lidxs === 0 ? $logImg1X : $logImg2X;
            $ly = $lidxs === 0 ? $logImg1Y : $logImg2Y;
            $tmp = $logLoadCrop($path, $lw, $logImgH);
            if ($tmp && file_exists($tmp)) {
                $mpdf->Image($tmp, $lx, $ly, $lw, $logImgH);
                @unlink($tmp);
            } elseif ($path && file_exists($path)) {
                $mpdf->Image($path, $lx, $ly, $lw, $logImgH);
            } else {
                $mpdf->SetFillColor(40, 40, 50);
                $mpdf->Rect($lx, $ly, $lw, $logImgH, 'F');
            }
        }
        // Derecha: panel azul oscuro con 5 secciones (PREMIOS, FERIAS, RONDAS, EXPERIENCIA EXPORTADORA, REFERENCIAS COMERCIALES)
        $logPanelMargin = 12;
        $logPanelBottomMargin = 16;
        $logPanelX = $logLeftW + 4 + $logPanelMargin;
        $logPanelW = $wMm - $logPanelX - $logPad - $logPanelMargin;
        $logPanelY = $logContentY + 4 + $logPanelMargin;
        $logPanelH = $logContentH - 8 - $logPanelMargin - $logPanelBottomMargin;
        $logBlue = [11, 24, 120];
        $mpdf->SetFillColor($logBlue[0], $logBlue[1], $logBlue[2]);
        $mpdf->Rect($logPanelX, $logPanelY, $logPanelW, $logPanelH, 'F');
        $logSectionTitles = ['PREMIOS', 'FERIAS', 'RONDAS', 'EXPERIENCIA EXPORTADORA', 'REFERENCIAS COMERCIALES'];
        $logSectionDesc = 'Información proveniente del input del formulario';
        $logSections = 5;
        $logInnerPad = 12;
        $logLineSepH = 0.4;
        $logSectionH = ($logPanelH - 2 * $logInnerPad - ($logSections - 1) * $logLineSepH) / $logSections;
        $logTitleFontSize = 12;
        $logDescFontSize = 9;
        $logTitleH = 6;
        $logDescLineH = 4;
        $logTextLeft = $logPanelX + $logInnerPad;
        $logTextW = $logPanelW - 2 * $logInnerPad;
        $mpdf->SetLeftMargin($logTextLeft);
        $mpdf->SetRightMargin($wMm - $logTextLeft - $logTextW);
        for ($s = 0; $s < $logSections; $s++) {
            $sy = $logPanelY + $logInnerPad + $s * ($logSectionH + $logLineSepH);
            if ($s > 0) {
                $mpdf->SetFillColor(255, 255, 255);
                $mpdf->Rect($logPanelX + $logInnerPad, $sy - $logLineSepH / 2, $logPanelW - 2 * $logInnerPad, $logLineSepH, 'F');
            }
            $mpdf->SetXY($logTextLeft, $sy + 1);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', 'B', $logTitleFontSize);
            $mpdf->Cell($logTextW, $logTitleH, $logSectionTitles[$s], 0, 1, 'L');
            $mpdf->SetFont('dejavusans', '', $logDescFontSize);
            $mpdf->MultiCell($logTextW, $logDescLineH, $logSectionDesc, 0, 'L');
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);

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
