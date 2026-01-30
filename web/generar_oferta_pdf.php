<?php
session_start();
set_time_limit(120);
@ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Para generar el PDF, ejecute en la raíz del proyecto: composer install\n";
    exit;
}
require_once $vendorAutoload;

require_once __DIR__ . '/includes/functions.php';
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
$webRoot = __DIR__;
$logoPath = $webRoot . '/img/logo.svg';
$catImages = glob($webRoot . '/img/landing/*.png');
$catImagePath = !empty($catImages) ? $catImages[0] : null;
// Fondo oficial del primer slide (no es una foto del landing)
$backgroundSlide1Path = $webRoot . '/img/pdf/background_slide1.jpg';
$backgroundSlide1Uri = (file_exists($backgroundSlide1Path)) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($backgroundSlide1Path)) : '';
$pdfLogoPath = $webRoot . '/img/pdf/logo.png';
$pdfLogoUri = (file_exists($pdfLogoPath)) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pdfLogoPath)) : '';
$imgSlide2Path = $webRoot . '/img/pdf/img_slide2.png';
$imgSlide3Path = $webRoot . '/img/pdf/img_slide3.png';
$iconTelefonoPath = $webRoot . '/img/pdf/telefono.png';
$iconMailPath = $webRoot . '/img/pdf/mail.png';
$iconWebPath = $webRoot . '/img/pdf/web.png';
$iconDireccionPath = $webRoot . '/img/pdf/direccion.png';
$storageUploadsDir = $webRoot . '/uploads';
if (is_file(__DIR__ . '/includes/config/config.php')) {
    $storageConfig = @include __DIR__ . '/includes/config/config.php';
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

// Companies aprobadas (solo las que tienen usuario existente: si se eliminó el user, no aparecen en el PDF)
$q = "SELECT c.id, c.name, c.main_activity, c.website
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
    $q = "SELECT p.id, p.name, p.activity, p.description, p.company_id, p.type
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

$redBarH = 5; // altura franja roja inferior
$contentH = $hMm - $redBarH; // altura zona de fondo (sin franja roja)

for ($i = 0; $i < count($htmlChunks); $i++) {
    if ($i === 0) {
        $mpdf->WriteHTML($htmlChunks[0]);
    } elseif ($i === 1) {
        // Slide 1 por API: fondo, bloque azul con texto, logo, franja roja
        if (file_exists($backgroundSlide1Path)) {
            $mpdf->Image($backgroundSlide1Path, 0, 0, $wMm, $contentH);
        }
        // Bloque azul a la derecha (Color primario #003399), sin llegar al borde derecho
        $blueBlockX = round($wMm * 0.55);
        $blueBlockMarginRight = 22; // mm de separación del borde derecho de la página
        $blueBlockW = $wMm - $blueBlockX - $blueBlockMarginRight;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($blueBlockX, 0, $blueBlockW, $contentH, 'F');
        // Texto dentro del bloque azul: OFERTA blanco más grande; EXPORTABLE y Presentación en azul claro
        $txtPadding = 15;
        $txtX = $blueBlockX + $txtPadding;
        $txtY = 70;
        $mpdf->SetLeftMargin($txtX);
        $mpdf->SetRightMargin($blueBlockMarginRight + $txtPadding);
        $mpdf->SetXY($txtX, $txtY);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 44);
        $mpdf->Cell(0, 14, 'OFERTA', 0, 1);
        $mpdf->Ln(5); // margen inferior OFERTA
        $mpdf->SetTextColor(117, 168, 218); // #75A8DA azul claro
        $mpdf->SetFont('dejavusans', 'B', 38);
        $mpdf->Cell(0, 14, 'EXPORTABLE', 0, 1);
        $mpdf->Ln(5); // margen inferior EXPORTABLE
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 16);
        $mpdf->Cell(0, 8, $configInstitucional['nombre_provincia'], 0, 1);
        $mpdf->SetXY($txtX, $contentH - 25);
        $mpdf->SetTextColor(117, 168, 218); // #75A8DA azul claro
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->Cell(0, 8, 'Presentación - ' . $configInstitucional['periodo_ano'], 0, 0);
        if (file_exists($pdfLogoPath)) {
            $lbc = $logoBlockConfig;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($lbc['x_mm'], $lbc['y_mm'], $lbc['rect_w_mm'], $lbc['rect_h_mm'], 'F');
            $imgSize = @getimagesize($pdfLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $logoWidthMm = $lbc['height_mm'] * ($imgSize[0] / $imgSize[1]);
                $logoX = $lbc['x_mm'] + ($lbc['rect_w_mm'] - $logoWidthMm) / 2;
                $logoY = $lbc['y_mm'] + ($lbc['rect_h_mm'] - $lbc['height_mm']) / 2;
            } else {
                $logoX = $lbc['x_mm'] + $lbc['pad_mm'];
                $logoY = $lbc['y_mm'] + $lbc['pad_mm'];
            }
            $mpdf->Image($pdfLogoPath, $logoX, $logoY, 0, $lbc['height_mm']);
        }
        $mpdf->SetFillColor(255, 0, 0); // #FF0000
        $mpdf->Rect(0, $contentH, $wMm, $redBarH, 'F');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        $mpdf->AddPage();
        // Anclar contexto de dibujo a la página 2 (evita que mPDF asocie contenido a otra página)
        $mpdf->SetXY(0, 0);
        // Slide 2 por API en el MISMO bloque (así mPDF asocia todo a la página 2, como con el slide 1)
        $s2LeftW = round($wMm * 0.30);
        $mpdf->SetFillColor(117, 168, 218); // #75A8DA
        $mpdf->Rect(0, 0, $s2LeftW, $hMm, 'F');
        $mpdf->SetFillColor(0, 51, 153);   // #003399
        $mpdf->Rect($s2LeftW, 0, $wMm - $s2LeftW, $hMm, 'F');
        if (file_exists($pdfLogoPath)) {
            $lbc = $logoBlockConfig;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($lbc['x_mm'], $lbc['y_mm'], $lbc['rect_w_mm'], $lbc['rect_h_mm'], 'F');
            $imgSize = @getimagesize($pdfLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $logoWidthMm = $lbc['height_mm'] * ($imgSize[0] / $imgSize[1]);
                $logoX = $lbc['x_mm'] + ($lbc['rect_w_mm'] - $logoWidthMm) / 2;
                $logoY = $lbc['y_mm'] + ($lbc['rect_h_mm'] - $lbc['height_mm']) / 2;
            } else {
                $logoX = $lbc['x_mm'] + $lbc['pad_mm'];
                $logoY = $lbc['y_mm'] + $lbc['pad_mm'];
            }
            $mpdf->Image($pdfLogoPath, $logoX, $logoY, 0, $lbc['height_mm']);
        }
        $s2ImgW = 150;
        $s2ImgH = 220; // altura reducida
        $s2ImgX = ($wMm - $s2ImgW) / 2 - 40; // más a la izquierda (22 mm)
        $s2ImgY = 0;
        if (file_exists($imgSlide2Path)) {
            $mpdf->Image($imgSlide2Path, $s2ImgX, $s2ImgY, $s2ImgW, $s2ImgH);
        }
        $s2TextLeft = $s2ImgX + $s2ImgW + 25;
        $s2TextRight = 20;
        $s2TextWidth = 110;
        $s2TextTop = 30;
        $mpdf->SetLeftMargin($s2TextLeft);
        $mpdf->SetRightMargin($s2TextRight);
        $mpdf->SetXY($s2TextLeft, $s2TextTop);
        // Título: "Contexto" en azul claro, con margen abajo
        $mpdf->SetTextColor(117, 168, 218); // #75A8DA
        $mpdf->SetFont('dejavusans', 'B', 32);
        $mpdf->Cell(0, 10, 'Contexto', 0, 1);
        $mpdf->Ln(4);
        // "Productivo" y "Provincial" con mayúscula y margen abajo
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell(0, 10, 'Productivo', 0, 1);
        $mpdf->Ln(4);
        $mpdf->Cell(0, 10, 'Provincial', 0, 1);
        $mpdf->Ln(10);
        // Párrafo 1 con margen abajo
        $mpdf->SetFont('dejavusans', '', 15);
        $mpdf->MultiCell($s2TextWidth, 7, 'La Provincia actualiza su oferta exportable para brindar a compradores externos información clara y accesible en formatos gráficos e informáticos.', 0, 'L');
        $mpdf->Ln(10);
        $mpdf->MultiCell($s2TextWidth, 7, 'Esta herramienta fortalece la promoción comercial, acompañando la difusión de oferta, misiones comerciales y participación en ferias y rondas de negocios.', 0, 'L');
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // No incrementar $i: en la siguiente iteración (i=2) se hará AddPage() para el slide 3
    } elseif ($i === 2) {
        // Chunk 2 = slide 2 ya dibujado por API en i=1; solo añadir página para el slide 3
        $mpdf->AddPage();
    } elseif ($i === 3) {
        // Slide 3 por API (por ahora): fondo secundario + bloque logo como en slides 1 y 2
        $mpdf->SetXY(0, 0);
        $mpdf->SetFillColor(117, 168, 218); // #75A8DA color secundario
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        if (file_exists($pdfLogoPath)) {
            $lbc = $logoBlockConfig;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($lbc['x_mm'], $lbc['y_mm'], $lbc['rect_w_mm'], $lbc['rect_h_mm'], 'F');
            $imgSize = @getimagesize($pdfLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $logoWidthMm = $lbc['height_mm'] * ($imgSize[0] / $imgSize[1]);
                $logoX = $lbc['x_mm'] + ($lbc['rect_w_mm'] - $logoWidthMm) / 2;
                $logoY = $lbc['y_mm'] + ($lbc['rect_h_mm'] - $lbc['height_mm']) / 2;
            } else {
                $logoX = $lbc['x_mm'] + $lbc['pad_mm'];
                $logoY = $lbc['y_mm'] + $lbc['pad_mm'];
            }
            $mpdf->Image($pdfLogoPath, $logoX, $logoY, 0, $lbc['height_mm']);
        }
        // Texto bajo el logo, cerca del borde inferior (panel izquierdo), color azul primario
        $s3TextX = $logoBlockConfig['x_mm'];
        $s3TextW = 100;
        $s3TextBottomMargin = 25; // espacio visible entre la última línea y el borde inferior del slide
        $s3TextY = $hMm - 62 - $s3TextBottomMargin; // subir el bloque para dejar margen abajo
        $mpdf->SetLeftMargin($s3TextX);
        $mpdf->SetRightMargin($wMm - $s3TextX - $s3TextW);
        $mpdf->SetXY($s3TextX, $s3TextY);
        $mpdf->SetTextColor(0, 51, 153); // #003399 azul primario
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->Cell($s3TextW, 12, 'Sectores', 0, 1);
        $mpdf->Ln(6);
        $mpdf->Cell($s3TextW, 12, 'productivos', 0, 1);
        $mpdf->Ln(10);
        $mpdf->SetFont('dejavusans', '', 14);
        $mpdf->MultiCell($s3TextW, 6, "La oferta exportable provincial se organiza por sectores para facilitar la búsqueda y la promoción internacional.", 0, 'L');
        $mpdf->Ln(18);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Bloque central azul (color primario) con Rubro A/B/C y Lorem ipsum
        $s3CenterW = 110;
        $s3CenterX = ($wMm
         - $s3CenterW) / 2;
        $s3CenterPad = 12;
        $s3CenterTextW = 76;
        $s3CenterTop = 28; // margen superior del texto en el bloque
        $mpdf->SetFillColor(0, 51, 153); // #003399
        $mpdf->Rect($s3CenterX, 0, $s3CenterW, $hMm, 'F');
        $mpdf->SetLeftMargin($s3CenterX + $s3CenterPad);
        $mpdf->SetRightMargin($wMm - $s3CenterX - $s3CenterW + $s3CenterPad);
        $mpdf->SetXY($s3CenterX + $s3CenterPad, $s3CenterTop);
        $lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.";
        $mpdf->SetTextColor(117, 168, 218); // #75A8DA títulos
        $mpdf->SetFont('dejavusans', 'B', 20);
        $mpdf->Cell($s3CenterTextW, 9, 'Rubro A', 0, 1);
        $mpdf->Ln(2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->MultiCell($s3CenterTextW, 6, $lorem, 0, 'L');
        $mpdf->Ln(26);
        $mpdf->SetTextColor(117, 168, 218);
        $mpdf->SetFont('dejavusans', 'B', 20);
        $mpdf->Cell($s3CenterTextW, 9, 'Rubro B', 0, 1);
        $mpdf->Ln(2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->MultiCell($s3CenterTextW, 6, $lorem, 0, 'L');
        $mpdf->Ln(26);
        $mpdf->SetTextColor(117, 168, 218);
        $mpdf->SetFont('dejavusans', 'B', 20);
        $mpdf->Cell($s3CenterTextW, 9, 'Rubro C', 0, 1);
        $mpdf->Ln(2);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', '', 13);
        $mpdf->MultiCell($s3CenterTextW, 6, $lorem, 0, 'L');
        $mpdf->Ln(6);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Panel derecho: imagen centrada en el espacio azul + métricas debajo (más grandes)
        $s3PanelLeft = $s3CenterX + $s3CenterW;
        $s3PanelRight = $wMm - 15;
        $s3PanelW = $s3PanelRight - $s3PanelLeft;
        $s3ImgSize = 105;
        $s3ImgX = $s3PanelLeft + ($s3PanelW - $s3ImgSize) / 2; // centrado horizontal
        $s3MetricsY = $hMm - 52; // métricas fijas cerca del borde inferior
        $s3GapAboveMetrics = 8;
        $s3SpaceForImg = $s3MetricsY - $s3CenterTop - $s3GapAboveMetrics;
        $s3ImgY = $s3CenterTop + ($s3SpaceForImg - $s3ImgSize) / 2 - 10; // centrado vertical, un poco más arriba
        if (file_exists($imgSlide3Path)) {
            $mpdf->Image($imgSlide3Path, $s3ImgX, $s3ImgY, $s3ImgSize, $s3ImgSize);
        }
        $s3MetricsPad = 12;
        $s3MetricsLeft = $s3PanelLeft + $s3MetricsPad;
        $s3MetricsW = $s3PanelRight - $s3MetricsLeft;
        $mpdf->SetLeftMargin($s3MetricsLeft);
        $mpdf->SetRightMargin(15);
        $mpdf->SetXY($s3MetricsLeft, $s3MetricsY);
        $mpdf->SetTextColor(0, 51, 153); // #003399
        $mpdf->SetFont('dejavusans', 'B', 18);
        $mpdf->Cell($s3MetricsW, 9, 'Empresas registradas:', 0, 1);
        $mpdf->SetFont('dejavusans', '', 16);
        $mpdf->Cell($s3MetricsW, 8, (string) $metrics['empresas'] . ' con oferta exportable cargada', 0, 1);
        $mpdf->Ln(4);
        $mpdf->SetFont('dejavusans', 'B', 18);
        $mpdf->Cell($s3MetricsW, 9, 'Productos y servicios cargados:', 0, 1);
        $mpdf->SetFont('dejavusans', '', 16);
        $mpdf->Cell($s3MetricsW, 8, (string) $metrics['productos'] . ' declarados en el registro', 0, 1);
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 4) {
        // Slide(s) Empresas destacadas: 3 empresas por página, dibujado por API
        $empresaSlidesChunks = array_chunk($companies, 3);
        foreach ($empresaSlidesChunks as $idx => $chunk) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $s4LeftW = 125;
            $s4CenterW = 140;
            $s4RightW = $wMm - $s4LeftW - $s4CenterW; // 185
            $s4Pad = 12;
            $s4TopMargin = 28; // margen superior de los bloques de empresa
            $mpdf->SetFillColor(117, 168, 218); // #75A8DA
            $mpdf->Rect(0, 0, $s4LeftW + $s4CenterW, $hMm, 'F');
            $mpdf->SetFillColor(0, 51, 153);   // #003399
            $mpdf->Rect($s4LeftW + $s4CenterW, 0, $s4RightW, $hMm, 'F');
            $blockH = ($hMm - $s4TopMargin - 24) / 3;
            $s4TextW = $s4LeftW - 2 * $s4Pad;
            foreach ($chunk as $k => $emp) {
                $cid = (int) $emp['id'];
                $y0 = $s4TopMargin + $k * ($blockH + 4);
                $mpdf->SetLeftMargin($s4Pad);
                $mpdf->SetRightMargin($wMm - $s4LeftW + $s4Pad);
                $mpdf->SetXY($s4Pad, $y0);
                $mpdf->SetTextColor(196, 52, 59); // #C4343B acento 1
                $mpdf->SetFont('dejavusans', 'B', 16);
                $mpdf->Cell($s4TextW, 7, $emp['name'] ?? '', 0, 1);
                $mpdf->Ln(3);
                $mpdf->SetTextColor(0, 0, 102);  // #000066 primario oscuro
                $mpdf->SetFont('dejavusans', 'B', 13);
                $mpdf->Cell($s4TextW, 6, $emp['main_activity'] ?? '', 0, 1);
                $mpdf->Ln(3);
                if (!empty($localidadPorEmpresa[$cid])) {
                    $mpdf->SetTextColor(80, 80, 100);
                    $mpdf->SetFont('dejavusans', '', 11);
                    $mpdf->Cell($s4TextW, 5, $localidadPorEmpresa[$cid], 0, 1);
                    $mpdf->Ln(1);
                }
                if (!empty($descripcionPorEmpresa[$cid])) {
                    $mpdf->SetFont('dejavusans', '', 10);
                    $mpdf->MultiCell($s4TextW, 4, $descripcionPorEmpresa[$cid], 0, 'L');
                    $mpdf->Ln(2);
                }
                $mpdf->Ln(5);
            }
            $mpdf->SetLeftMargin(0);
            $mpdf->SetRightMargin(0);
            $s4ImgSize = 58;
            $s4ImgGap = 8;
            $s4ImgX = $s4LeftW + ($s4CenterW - $s4ImgSize) / 2;
            $s4ImgY0 = 22;
            foreach ($chunk as $k => $emp) {
                $cid = (int) $emp['id'];
                $imgPath = $logosPorEmpresa[$cid] ?? $imagenesPorEmpresa[$cid] ?? null;
                $yImg = $s4ImgY0 + $k * ($s4ImgSize + $s4ImgGap);
                if ($imgPath && file_exists($imgPath)) {
                    $mpdf->Image($imgPath, $s4ImgX, $yImg, $s4ImgSize, $s4ImgSize);
                }
            }
            $s4RightX = $s4LeftW + $s4CenterW;
            $s4RightPad = 18;
            $s4RightTop = 34;
            $s4RightTextW = 100; // ancho fijo de la columna de texto (mm)
            $mpdf->SetLeftMargin($s4RightX + $s4RightPad);
            $mpdf->SetRightMargin(15);
            $mpdf->SetXY($s4RightX + $s4RightPad, $s4RightTop);
            $mpdf->SetTextColor(117, 168, 218);
            $mpdf->SetFont('dejavusans', 'B', 34);
            $mpdf->Cell($s4RightTextW, 12, 'Empresas', 0, 1);
            $mpdf->Ln(4);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->Cell($s4RightTextW, 12, 'destacadas', 0, 1);
            $mpdf->Ln(10);
            $mpdf->SetFont('dejavusans', '', 15);
            $mpdf->MultiCell($s4RightTextW, 6, 'Selección representativa de empresas con oferta exportable registrada, organizada para facilitar su promoción internacional.', 0, 'L');
            $mpdf->SetLeftMargin(0);
            $mpdf->SetRightMargin(0);
            if (file_exists($pdfLogoPath)) {
                $lbc = $logoBlockConfig;
                $logoBoxX = $wMm - $lbc['rect_w_mm'] - 20;
                $logoBoxY = $hMm - $lbc['rect_h_mm'] - 20;
                $mpdf->SetFillColor(255, 255, 255);
                $mpdf->Rect($logoBoxX, $logoBoxY, $lbc['rect_w_mm'], $lbc['rect_h_mm'], 'F');
                $imgSize = @getimagesize($pdfLogoPath);
                if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                    $logoWidthMm = $lbc['height_mm'] * ($imgSize[0] / $imgSize[1]);
                    $logoX = $logoBoxX + ($lbc['rect_w_mm'] - $logoWidthMm) / 2;
                    $logoY = $logoBoxY + ($lbc['rect_h_mm'] - $lbc['height_mm']) / 2;
                } else {
                    $logoX = $logoBoxX + $lbc['pad_mm'];
                    $logoY = $logoBoxY + $lbc['pad_mm'];
                }
                $mpdf->Image($pdfLogoPath, $logoX, $logoY, 0, $lbc['height_mm']);
            }
        }
    } elseif ($i === 5) {
        // Slide(s) Productos exportables: grilla 2×3, 6 productos por página, dibujado por API
        $productoSlidesChunks = array_chunk($productosParaSlides, 6);
        $s5CardW = 132;
        $s5CardH = 98;
        $s5GapH = 10;
        $s5GapV = 10;
        $s5Pad = 12;
        $s5TitleH = 46; // margen inferior del título incluido
        $s5ImgH = 42;
        foreach ($productoSlidesChunks as $idx => $chunk) {
            $mpdf->AddPage();
            $mpdf->SetXY(0, 0);
            $s5ContentH = $hMm - $redBarH; // igual que slide 1: zona sin franja roja
            $mpdf->SetFillColor(0, 51, 153); // #003399
            $mpdf->Rect(0, 0, $wMm, $s5ContentH, 'F');
            $mpdf->SetFillColor(255, 0, 0); // #FF0000 franja roja inferior como slide 1
            $mpdf->Rect(0, $s5ContentH, $wMm, $redBarH, 'F');
            // Título: azul, margen inferior (sin sombra)
            $mpdf->SetFont('dejavusans', 'B', 32);
            $mpdf->SetXY(0, 15);
            $mpdf->SetTextColor(150, 200, 255); // azul claro
            $mpdf->Cell($wMm, 12, 'Productos exportables', 0, 0, 'C');
            $mpdf->SetTextColor(255, 255, 255);
            foreach ($chunk as $k => $prod) {
                $pid = (int) $prod['id'];
                $col = $k % 3;
                $row = (int) floor($k / 3);
                $cardX = $s5Pad + $col * ($s5CardW + $s5GapH);
                $cardY = $s5TitleH + $row * ($s5CardH + $s5GapV);
                $imgPath = $imagenesPorProducto[$pid] ?? null;
                if ($imgPath && file_exists($imgPath)) {
                    $imgSize = @getimagesize($imgPath);
                    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                        $pxToMm = 25.4 / 96;
                        $imgWmm = $imgSize[0] * $pxToMm;
                        $imgHmm = $imgSize[1] * $pxToMm;
                        $scale = min($s5CardW / $imgWmm, $s5ImgH / $imgHmm);
                        $drawW = $imgWmm * $scale;
                        $drawH = $imgHmm * $scale;
                        $imgX = $cardX + ($s5CardW - $drawW) / 2;
                        $imgY = $cardY + ($s5ImgH - $drawH) / 2;
                        $mpdf->Image($imgPath, $imgX, $imgY, $drawW, $drawH);
                    } else {
                        $mpdf->Image($imgPath, $cardX, $cardY, $s5CardW, $s5ImgH);
                    }
                }
                $txtY = $cardY + $s5ImgH + 4;
                $mpdf->SetLeftMargin($cardX);
                $mpdf->SetRightMargin($wMm - $cardX - $s5CardW);
                $mpdf->SetXY($cardX, $txtY);
                $mpdf->SetFont('dejavusans', 'B', 12);
                $mpdf->Cell($s5CardW, 6, $prod['name'] ?? '', 0, 1, 'C');
                $mpdf->Ln(1);
                $mpdf->SetFont('dejavusans', '', 10);
                $mpdf->Cell($s5CardW, 5, $prod['activity'] ?? '', 0, 1, 'C');
                if (!empty(trim($prod['description'] ?? ''))) {
                    $mpdf->Ln(0.5);
                    $mpdf->SetFont('dejavusans', '', 9);
                    $mpdf->MultiCell($s5CardW, 4, mb_substr(trim($prod['description']), 0, 80) . (mb_strlen(trim($prod['description'])) > 80 ? '…' : ''), 0, 'C');
                }
                $mpdf->SetLeftMargin(0);
                $mpdf->SetRightMargin(0);
            }
        }
    } elseif ($i === 6) {
        // Slide Mercados objetivo por API: fondo azul (sin franjas rojas), imagen pegada al borde izquierdo (tamaño reducido), overlay azul, logo sobre imagen, texto a la derecha
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        $s6LeftW = round($wMm * 0.38);   // imagen más ancha
        $s6ImgMarginLeft = 12;
        $s6ImgMarginTop = 12;
        $s6ImgMarginBottom = 12;
        $s6ImgX = $s6ImgMarginLeft;
        $s6ImgY = $s6ImgMarginTop;
        $s6ImgW = $s6LeftW - $s6ImgMarginLeft - 2;
        $s6ImgH = $hMm - $s6ImgMarginTop - $s6ImgMarginBottom;
        $mpdf->SetFillColor(0, 51, 153); // #003399 fondo azul completo
        $mpdf->Rect(0, 0, $wMm, $hMm, 'F');
        if (file_exists($imgSlide2Path)) {
            $mpdf->Image($imgSlide2Path, $s6ImgX, $s6ImgY, $s6ImgW, $s6ImgH);
            // Overlay azul semitransparente para oscurecer la imagen
            $mpdf->SetAlpha(0.45, 'Normal', false, 'F');
            $mpdf->SetFillColor(0, 51, 153);
            $mpdf->Rect($s6ImgX, $s6ImgY, $s6ImgW, $s6ImgH, 'F');
            $mpdf->SetAlpha(1);
        }
        if (file_exists($pdfLogoPath)) {
            $lbc = $logoBlockConfig;
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($lbc['x_mm'], $lbc['y_mm'], $lbc['rect_w_mm'], $lbc['rect_h_mm'], 'F');
            $imgSize = @getimagesize($pdfLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $logoWidthMm = $lbc['height_mm'] * ($imgSize[0] / $imgSize[1]);
                $logoX = $lbc['x_mm'] + ($lbc['rect_w_mm'] - $logoWidthMm) / 2;
                $logoY = $lbc['y_mm'] + ($lbc['rect_h_mm'] - $lbc['height_mm']) / 2;
            } else {
                $logoX = $lbc['x_mm'] + $lbc['pad_mm'];
                $logoY = $lbc['y_mm'] + $lbc['pad_mm'];
            }
            $mpdf->Image($pdfLogoPath, $logoX, $logoY, 0, $lbc['height_mm']);
        }
        // Panel derecho: texto un poco más a la derecha
        $s6RightX = $s6LeftW + 34;
        $s6RightW = $wMm - $s6RightX - 24;
        $s6TitleY = 32;
        $mpdf->SetFont('dejavusans', 'B', 40);
        $mpdf->SetXY($s6RightX, $s6TitleY);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Cell($s6RightW, 14, 'MERCADOS', 0, 1, 'L');
        $mpdf->Ln(5);  // отступ снизу после MERCADOS
        $s6ObjetivosY = $s6TitleY + 14 + 5; // под MERCADOS с отступом
        $mpdf->SetXY($s6RightX, $s6ObjetivosY);
        $mpdf->SetTextColor(117, 168, 218); // #75A8DA
        $mpdf->Cell($s6RightW, 14, 'OBJETIVOS', 0, 1, 'L');
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->Ln(15); // отступ снизу после OBJETIVOS
        // Bloques de región (01 REGIÓN + Lorem ipsum)
        $s6BlockW = ($s6RightW - 16) / 2;
        $s6Gap = 16;
        $s6BulletH = 6;
        $s6Lorem = [
            'Lorem ipsum dolor sit amet,',
            'consectetur adipiscing elit, sed do',
            'eiusmod tempor incididunt ut labore',
            'et dolore magna aliqua.',
        ];
        for ($row = 0; $row < 2; $row++) {
            for ($col = 0; $col < 2; $col++) {
                $idx = $row * 2 + $col;
                $blockX = $s6RightX + $col * ($s6BlockW + $s6Gap);
                $blockY = $s6TitleY + 58 + $row * 98;
                $mpdf->SetXY($blockX, $blockY);
                $mpdf->SetTextColor(196, 52, 59); // #C4343B acento
                $mpdf->SetFont('dejavusans', 'B', 19);
                $mpdf->Cell($s6BlockW, 8, sprintf('%02d', $idx + 1) . ' REGIÓN', 0, 1, 'L');
                $mpdf->SetTextColor(255, 255, 255);
                $mpdf->SetFont('dejavusans', '', 13);
                foreach ($s6Lorem as $lineIndex => $line) {
                    $lineY = $blockY + 8 + $lineIndex * $s6BulletH;
                    // Ячейка выше: в mPDF текст в Cell по нижнему краю, сдвиг вверх чтобы точка по центру строки
                    $mpdf->SetXY($blockX, $lineY - 1.5);
                    $mpdf->Cell(4, $s6BulletH, '.', 0, 0);
                    $mpdf->SetXY($blockX + 4, $lineY);
                    $mpdf->MultiCell($s6BlockW - 4, $s6BulletH, $line, 0, 'L');
                }
            }
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
    } elseif ($i === 7) {
        // Slide Contacto institucional: fondo a todo el slide; bloque rojo #FF0000 pegado a izquierda/arriba, sin tocar el borde inferior; sin "Área de Comercio Exterior" en rojo; franja azul "Área responsable"
        $mpdf->AddPage();
        $mpdf->SetXY(0, 0);
        if (file_exists($backgroundSlide1Path)) {
            $mpdf->Image($backgroundSlide1Path, 0, 0, $wMm, $hMm, '', '', true, false);
        }
        $s7RedW = 155;
        $s7RedX = 0;
        $s7RedY = 0;
        $s7RedBottomMargin = 78;
        $s7RedH = $hMm - $s7RedBottomMargin;
        $mpdf->SetFillColor(255, 0, 0); // #FF0000
        $mpdf->Rect($s7RedX, $s7RedY, $s7RedW, $s7RedH, 'F');
        $lbc = $logoBlockConfig;
        $s7LogoBoxX = $s7RedX + 15;
        $s7LogoBoxY = $s7RedY + 15;
        if (file_exists($pdfLogoPath)) {
            $mpdf->SetFillColor(255, 255, 255);
            $mpdf->Rect($s7LogoBoxX, $s7LogoBoxY, $lbc['rect_w_mm'], $lbc['rect_h_mm'], 'F');
            $imgSize = @getimagesize($pdfLogoPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $logoWidthMm = $lbc['height_mm'] * ($imgSize[0] / $imgSize[1]);
                $logoX = $s7LogoBoxX + ($lbc['rect_w_mm'] - $logoWidthMm) / 2;
                $logoY = $s7LogoBoxY + ($lbc['rect_h_mm'] - $lbc['height_mm']) / 2;
            } else {
                $logoX = $s7LogoBoxX + $lbc['pad_mm'];
                $logoY = $s7LogoBoxY + $lbc['pad_mm'];
            }
            $mpdf->Image($pdfLogoPath, $logoX, $logoY, 0, $lbc['height_mm']);
        }
        $s7Pad = $s7RedX + 15;
        $s7LineH = 8;
        $s7IconSize = 6;
        $s7LineGap = 2;
        $contacto = $configInstitucional;
        $s7Lines = [
            ['telefono', $iconTelefonoPath, 'Tel: ' . ($contacto['telefono'] ?? '')],
            ['web', $iconWebPath, 'Web: ' . ($contacto['sitio_web'] ?? '')],
            ['mail', $iconMailPath, 'Mail: ' . ($contacto['mail'] ?? '')],
            ['direccion', $iconDireccionPath, $contacto['localidad_direccion'] ?? ''],
        ];
        $s7LinesFiltered = array_filter($s7Lines, function ($l) { return trim($l[2] ?? '') !== ''; });
        $s7ContactBlockH = count($s7LinesFiltered) * ($s7LineH + $s7LineGap) - $s7LineGap;
        $s7ContactY = $s7RedY + $s7RedH - $s7ContactBlockH - 18;
        $s7Y = $s7ContactY;
        foreach ($s7Lines as $line) {
            $iconPath = $line[1];
            $text = trim($line[2]);
            if ($text === '') continue;
            $mpdf->SetXY($s7Pad, $s7Y);
            if ($iconPath && file_exists($iconPath)) {
                $mpdf->Image($iconPath, $s7Pad, $s7Y, $s7IconSize, $s7IconSize);
                $mpdf->SetXY($s7Pad + $s7IconSize + 4, $s7Y);
            }
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetFont('dejavusans', '', 12);
            $mpdf->MultiCell($s7RedW - 30 - $s7IconSize - 4, $s7LineH, $text, 0, 'L');
            $s7Y += $s7LineH + $s7LineGap;
        }
        $mpdf->SetLeftMargin(0);
        $mpdf->SetRightMargin(0);
        // Título a la derecha del bloque rojo: "Contacto" / "institucional" con márgenes inferiores
        $s7TitleX = $s7RedX + $s7RedW + 28;
        $s7TitleW = $wMm - $s7TitleX;
        $s7TitleY = $hMm / 2 - 50;
        $s7Line1H = 20;
        $s7Line2H = 20;
        $s7GapBetweenLines = 8;
        $mpdf->SetTextColor(0, 51, 153); // #003399
        $mpdf->SetFont('dejavusans', '', 58);
        $mpdf->SetXY($s7TitleX, $s7TitleY);
        $mpdf->Cell($s7TitleW, $s7Line1H, 'Contacto', 0, 1, 'L');
        $mpdf->SetXY($s7TitleX, $s7TitleY + $s7Line1H + $s7GapBetweenLines);
        $mpdf->Cell($s7TitleW, $s7Line2H, 'institucional', 0, 1, 'L');
        // Franja azul "Área responsable": un poco más abajo y empezando más a la derecha
        $s7StripeH = 12;
        $s7StripeY = $s7TitleY + $s7Line1H + $s7GapBetweenLines + $s7Line2H + 38;
        $s7StripeX = $s7RedX + $s7RedW + 22;
        $s7StripeW = $wMm - $s7StripeX;
        $mpdf->SetFillColor(0, 51, 153);
        $mpdf->Rect($s7StripeX, $s7StripeY, $s7StripeW, $s7StripeH, 'F');
        $mpdf->SetTextColor(117, 168, 218); // #75A8DA голубой
        $mpdf->SetFont('dejavusans', '', 14);
        $mpdf->SetXY($s7StripeX + 14, $s7StripeY + ($s7StripeH - 6) / 2);
        $mpdf->Cell($s7StripeW - 28, 6, 'Área responsable', 0, 0, 'L');
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

    $bloquesMercados = '';
    $labels = ['01', '02', '03', '04'];
    foreach ($mercados as $idx => $paises) {
        $lista = array_slice(is_array($paises) ? $paises : [], 0, 5);
        $bloquesMercados .= '<div style="margin-bottom:16px;"><strong>' . ($labels[$idx] ?? ($idx+1)) . ':</strong> ' . htmlspecialchars(implode(', ', $lista)) . '</div>';
    }
    $s6 = '
    <div class="slide" style="display:flex;">
        <div style="width:35%;position:relative;">
            <div style="width:100%;height:100%;background:#000066;"></div>
            <div class="logo-caja" style="position:absolute;top:20px;left:20px;">' . ($logoDataUri ? '<img src="' . $logoDataUri . '" alt="Logo" />' : '') . '</div>
        </div>
        <div class="primario" style="width:65%;padding:40px;">
            <h1 class="slide-title">Mercados objetivo</h1>
            <div class="texto">' . $bloquesMercados . '</div>
        </div>
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
    return [$header, $s1, $s2, $s3, $s4, $s5, $s6, $s7 . '</body></html>'];
}
