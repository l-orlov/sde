<?php
/**
 * PDF presentación de empresa (Modelo Empresa — diseño D1).
 * Una diapositiva por producto/servicio. Datos solo desde formulario/BD.
 * Entrada: sesión (empresa del usuario) o company_id (solo propia).
 */
session_start();
set_time_limit(120);
@ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$webRoot = dirname(dirname(__DIR__)); // web/
$vendorAutoload = $webRoot . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Para generar el PDF, ejecute en la raíz del proyecto: composer install\n";
    exit;
}
require_once $vendorAutoload;
require_once $webRoot . '/includes/functions.php';
DBconnect();

global $link;

// ——— Autorización: solo empresa del usuario en sesión ———
if (empty($_SESSION['uid'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Debe iniciar sesión para descargar la presentación.';
    exit;
}
$userId = (int) $_SESSION['uid'];
$companyId = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
$design = isset($_GET['design']) ? strtoupper(trim($_GET['design'])) : 'D1';
if (!in_array($design, ['D1', 'D2', 'D3'], true)) {
    $design = 'D1';
}

// Obtener company_id: si se pasó, verificar que sea de este usuario; si no, tomar la del usuario
if ($companyId > 0) {
    $stmt = mysqli_prepare($link, "SELECT id FROM companies WHERE id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $companyId, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (!$res || mysqli_num_rows($res) === 0) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Acceso denegado a esta empresa.';
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    $res = mysqli_query($link, "SELECT id FROM companies WHERE user_id = $userId LIMIT 1");
    if (!$res || mysqli_num_rows($res) === 0) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No hay empresa asociada a su usuario. Complete el registro.';
        exit;
    }
    $row = mysqli_fetch_assoc($res);
    $companyId = (int) $row['id'];
}

// ——— Rutas ———
$assetsDir = __DIR__ . '/assets';
$storageUploadsDir = $webRoot . '/uploads';
if (is_file($webRoot . '/includes/config/config.php')) {
    $storageConfig = @include $webRoot . '/includes/config/config.php';
    if (!empty($storageConfig['storage']['local']['base_path'])) {
        $storageUploadsDir = $storageConfig['storage']['local']['base_path'];
    }
}
$logoOficialPath = $assetsDir . '/logo.png';
if (!file_exists($logoOficialPath)) {
    $logoOficialPath = dirname(__DIR__) . '/oferta/assets/logo.png';
}

// ——— Cargar datos de la empresa ———
$company = null;
$q = "SELECT c.id, c.name, c.main_activity, c.website FROM companies c WHERE c.id = ? LIMIT 1";
$stmt = mysqli_prepare($link, $q);
mysqli_stmt_bind_param($stmt, 'i', $companyId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res && $row = mysqli_fetch_assoc($res)) {
    $company = $row;
}
mysqli_stmt_close($stmt);
if (!$company) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Empresa no encontrada.';
    exit;
}

// Localidad (primera dirección)
$locality = '';
$addressLine = '';
$stmt = mysqli_prepare($link, "SELECT locality, street, street_number, postal_code FROM company_addresses WHERE company_id = ? ORDER BY id ASC LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $companyId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res && $row = mysqli_fetch_assoc($res)) {
    $locality = trim($row['locality'] ?? '');
    $parts = array_filter([$row['street'] ?? '', $row['street_number'] ?? '', $row['postal_code'] ?? '']);
    $addressLine = trim(implode(' ', $parts));
}
mysqli_stmt_close($stmt);

// Contacto (tel, email, web)
$contactPhone = '';
$contactEmail = '';
$contactPerson = '';
$stmt = mysqli_prepare($link, "SELECT phone, area_code, email, contact_person FROM company_contacts WHERE company_id = ? ORDER BY id ASC LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $companyId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res && $row = mysqli_fetch_assoc($res)) {
    $contactPhone = trim(($row['area_code'] ?? '') . ' ' . ($row['phone'] ?? ''));
    $contactEmail = trim($row['email'] ?? '');
    $contactPerson = trim($row['contact_person'] ?? '');
}
mysqli_stmt_close($stmt);
if ($contactEmail === '' && isset($_SESSION['uid'])) {
    $r = mysqli_query($link, "SELECT email FROM users WHERE id = $userId LIMIT 1");
    if ($r && $u = mysqli_fetch_assoc($r)) {
        $contactEmail = trim($u['email'] ?? '');
    }
}
$companyWebsite = trim($company['website'] ?? '');

// company_data: target_markets, differentiation_factors (ventajas)
$targetMarkets = [];
$ventajas = [];
$stmt = mysqli_prepare($link, "SELECT target_markets, differentiation_factors FROM company_data WHERE company_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $companyId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res && $row = mysqli_fetch_assoc($res)) {
    if (!empty($row['target_markets'])) {
        $dec = json_decode($row['target_markets'], true);
        if (is_array($dec)) {
            foreach ($dec as $p) {
                if (is_string($p)) {
                    $targetMarkets[] = trim($p);
                } elseif (is_array($p) && isset($p['nombre'])) {
                    $targetMarkets[] = trim($p['nombre']);
                }
            }
        }
    }
    if (!empty($row['differentiation_factors'])) {
        $dec = json_decode($row['differentiation_factors'], true);
        if (is_array($dec)) {
            $ventajas = array_values(array_filter(array_map('trim', $dec)));
        }
    }
}
mysqli_stmt_close($stmt);

// Productos/servicios (con activity y certifications)
$products = [];
$checkTarget = @mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'target_markets'");
$hasTargetMarkets = $checkTarget && mysqli_num_rows($checkTarget) > 0;
$cols = "id, type, activity, name, description, certifications";
if ($hasTargetMarkets) {
    $cols .= ", target_markets";
}
$q = "SELECT $cols FROM products WHERE company_id = ? ORDER BY is_main DESC, id ASC";
$stmt = mysqli_prepare($link, $q);
mysqli_stmt_bind_param($stmt, 'i', $companyId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $p = [
        'id' => (int) $row['id'],
        'type' => $row['type'] ?? 'product',
        'activity' => trim($row['activity'] ?? ''),
        'name' => trim($row['name'] ?? ''),
        'description' => trim($row['description'] ?? ''),
        'certifications' => trim($row['certifications'] ?? ''),
    ];
    if ($hasTargetMarkets && !empty($row['target_markets'])) {
        $dec = json_decode($row['target_markets'], true);
        if (is_array($dec)) {
            foreach ($dec as $m) {
                if (is_string($m)) {
                    $targetMarkets[] = trim($m);
                } elseif (is_array($m) && isset($m['nombre'])) {
                    $targetMarkets[] = trim($m['nombre']);
                }
            }
        }
    }
    $products[] = $p;
}
mysqli_stmt_close($stmt);
$targetMarkets = array_values(array_unique(array_filter($targetMarkets)));
$targetMarkets = array_slice($targetMarkets, 0, 4);

// Certificaciones: listar las declaradas (por producto, sin duplicar texto)
$certificacionesList = [];
foreach ($products as $p) {
    $c = $p['certifications'];
    if ($c !== '' && !in_array($c, $certificacionesList, true)) {
        $certificacionesList[] = $c;
    }
}

// Logo de la empresa (primera no temporal)
$companyLogoPath = null;
$stmt = mysqli_prepare($link, "SELECT file_path FROM files WHERE user_id = ? AND file_type = 'logo' AND (product_id IS NULL OR product_id = 0) AND (is_temporary = 0 OR is_temporary IS NULL) ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res && $row = mysqli_fetch_assoc($res)) {
    $rel = ltrim($row['file_path'], '/');
    $full = $storageUploadsDir . '/' . $rel;
    if (!file_exists($full)) {
        $full = $webRoot . '/' . $rel;
    }
    if (file_exists($full)) {
        $companyLogoPath = $full;
    }
}
mysqli_stmt_close($stmt);

// Imagen por producto (primera product_photo o service_photo)
$productImagePaths = [];
if (!empty($products)) {
    $pids = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($pids), '?'));
    $types = str_repeat('i', count($pids));
    $q = "SELECT f.product_id, f.file_path FROM files f
          WHERE f.user_id = ? AND f.product_id IN ($placeholders)
          AND f.file_type IN ('product_photo','product_photo_sec','service_photo')
          AND (f.is_temporary = 0 OR f.is_temporary IS NULL)
          ORDER BY f.product_id, f.id ASC";
    $stmt = mysqli_prepare($link, $q);
    $params = array_merge([$userId], $pids);
    $typesStr = 'i' . $types;
    $refs = [];
    foreach ($params as $k => $v) {
        $refs[$k] = $v;
    }
    $bindArgs = [$typesStr];
    foreach ($refs as $k => $v) {
        $bindArgs[] = &$refs[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $pid = (int) $row['product_id'];
        if (!isset($productImagePaths[$pid])) {
            $rel = ltrim($row['file_path'], '/');
            $full = $storageUploadsDir . '/' . $rel;
            if (!file_exists($full)) {
                $full = $webRoot . '/' . $rel;
            }
            $productImagePaths[$pid] = file_exists($full) ? $full : null;
        }
    }
    mysqli_stmt_close($stmt);
}

// Texto perfil (institucional): placeholder Lorem ipsum (sustituir por datos/IA cuando corresponda)
$perfilText = "Lorem ipsum dolor sit amet, consectet adipiscin elit, sed do eiusmod tempor incididunt ut labore et dolore magna. Lorem ipsum dolor sit amet, consectet adipiscin elit, sed do eiusmod tempor incididunt ut labore et dolore magna";

$empresaNombre = $company['name'] ?? '';
$sectorRubro = $company['main_activity'] ?? '';
$provincia = 'Santiago del Estero';
$ano = date('Y');

// ——— Generar PDF (D1) ———
$wMm = 450;
$hMm = 253;
$redBarH = 5;
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => [$wMm, $hMm],
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
]);
$mpdf->SetDisplayMode('fullpage');
$mpdf->SetTitle($empresaNombre . ' - Presentación');

$logoPath = file_exists($companyLogoPath) ? $companyLogoPath : $logoOficialPath;
$logoBlock = ['x_mm' => 30, 'y_mm' => 25, 'rect_w_mm' => 70, 'rect_h_mm' => 25, 'height_mm' => 14, 'pad_mm' => 5];

// Helper: dibujar logo en posición fija
$drawLogo = function ($mpdf, $logoPath, $lbc, $x = null, $y = null) {
    if (!file_exists($logoPath)) return;
    $x = $x ?? $lbc['x_mm'];
    $y = $y ?? $lbc['y_mm'];
    $mpdf->SetFillColor(255, 255, 255);
    $mpdf->Rect($x, $y, $lbc['rect_w_mm'], $lbc['rect_h_mm'], 'F');
    $imgSize = @getimagesize($logoPath);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $logoWidthMm = $lbc['height_mm'] * ($imgSize[0] / $imgSize[1]);
        $logoX = $x + ($lbc['rect_w_mm'] - $logoWidthMm) / 2;
        $logoY = $y + ($lbc['rect_h_mm'] - $lbc['height_mm']) / 2;
    } else {
        $logoX = $x + $lbc['pad_mm'];
        $logoY = $y + $lbc['pad_mm'];
    }
    $mpdf->Image($logoPath, $logoX, $logoY, 0, $lbc['height_mm']);
};

// —— Slide 1: Portada (fondo blanco, 4 imágenes, título EMPRESA, sector/rubro, provincia/localidad) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
// Fondo blanco
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$portadaBg = $assetsDir . '/background_slide1.jpg';
$portadaImg2 = $assetsDir . '/img_slide2.png';
$portadaImgSmall = $assetsDir . '/img_small.png';
if (!file_exists($portadaBg)) {
    $portadaBg = dirname(__DIR__) . '/oferta/assets/background_slide1.jpg';
}
if (!file_exists($portadaImg2)) {
    $portadaImg2 = dirname(__DIR__) . '/oferta/assets/img_slide2.png';
}
$topLeftX = 15;
$topLeftY = 18;
$blockW = ($wMm - $topLeftX) / 2;
$blockH = ($hMm - $topLeftY) / 2;
$imgSmallW = 24;
$imgSmallH = 18;
$imgVertW = 65;
$imgVertH = 95;
if (file_exists($portadaBg)) {
    $mpdf->Image($portadaBg, $topLeftX, $topLeftY, $blockW, $blockH);
}
if (file_exists($portadaImgSmall)) {
    $imgSize = @getimagesize($portadaImgSmall);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $smallScaleH = $imgSmallH;
        $smallScaleW = $smallScaleH * ($imgSize[0] / $imgSize[1]);
        $mpdf->Image($portadaImgSmall, $wMm - 15 - $smallScaleW, 22, $smallScaleW, $smallScaleH);
    } else {
        $mpdf->Image($portadaImgSmall, $wMm - 15 - $imgSmallW, 22, $imgSmallW, $imgSmallH);
    }
}
$bottomRightMarginX = 28;
$bottomRightW = $blockW - $bottomRightMarginX;
$bottomRightH = $blockH;
if (file_exists($portadaImg2)) {
    $mpdf->Image($portadaImg2, 20, $hMm - $imgVertH, $imgVertW, $imgVertH);
}
if (file_exists($portadaBg)) {
    $mpdf->Image($portadaBg, $topLeftX + $blockW, $topLeftY + $blockH, $bottomRightW, $bottomRightH);
}

// Título: nombre de la empresa (azul oscuro, fuente más grande)
$mpdf->SetTextColor(0, 51, 153);
$mpdf->SetFont('dejavusans', 'B', 92);
$titleY = 88;
$mpdf->SetXY(0, $titleY);
$portadaTitulo = $empresaNombre !== '' ? mb_strtoupper($empresaNombre) : 'EMPRESA';
$mpdf->Cell($wMm, 28, $portadaTitulo, 0, 1, 'C');

// Sector / rubro (rojo), más a la izquierda
$mpdf->SetTextColor(196, 52, 59);
$mpdf->SetFont('dejavusans', 'B', 22);
$sectorTexto = $sectorRubro !== '' ? $sectorRubro : 'sector / rubro';
$sectorX = 55;
$mpdf->SetXY($sectorX, $topLeftY + $blockH - 14);
$mpdf->Cell($wMm - $sectorX - 15, 10, $sectorTexto, 0, 1, 'R');

// Bloque provincia/localidad: alineado (mismo margen para etiqueta, texto y línea)
$blockY = $titleY + 52;
$provinciaLeft = 80;
$lineW = 180;
$provinciaLocalidadTexto = trim($provincia . ($locality !== '' ? ' / ' . $locality : ''));
if ($provinciaLocalidadTexto === '') {
    $provinciaLocalidadTexto = '—';
}
$mpdf->SetTextColor(0, 51, 153);
$mpdf->SetFont('dejavusans', '', 12);
$mpdf->SetXY($provinciaLeft, $blockY);
$mpdf->Cell($lineW, 7, 'Provincia / Localidad', 0, 1, 'L');
$mpdf->SetTextColor(0, 0, 0);
$mpdf->SetFont('dejavusans', '', 14);
$mpdf->SetXY($provinciaLeft, $blockY + 8);
$mpdf->Cell($lineW, 8, $provinciaLocalidadTexto, 0, 1, 'L');
$lineY = $blockY + 8 + 8;
$mpdf->SetDrawColor(0, 51, 153);
$mpdf->SetLineWidth(0.4);
$mpdf->Line($provinciaLeft, $lineY, $provinciaLeft + $lineW, $lineY);

$mpdf->SetLeftMargin(0);
$mpdf->SetRightMargin(0);

// —— Slide 2: Perfil de la empresa (fondo blanco, texto izquierda, imágenes y barras derecha) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$colLeftW = round($wMm * 0.42);
$padLeft = 22;
$padTop = 24;
$textW = $colLeftW - $padLeft - 10;

$mpdf->SetTextColor(0, 51, 153);
$mpdf->SetFont('dejavusans', 'B', 28);
$mpdf->SetXY($padLeft, $padTop);
$mpdf->Cell($textW, 10, 'Perfil de la', 0, 1, 'L');
$mpdf->SetXY($padLeft, $padTop + 10);
$mpdf->Cell($textW, 12, 'Empresa', 0, 1, 'L');

$subtitleY = $padTop + 26;
$mpdf->SetTextColor(0, 0, 0);
$mpdf->SetFont('dejavusans', 'B', 13);
$subtitle = $sectorRubro !== '' ? $sectorRubro : '';
if ($subtitle !== '') {
    $mpdf->SetXY($padLeft, $subtitleY);
    $mpdf->Cell($textW, 7, $subtitle, 0, 1, 'L');
}
$perfilTextW = round($textW * 0.78);
$perfilStartY = $subtitleY + 18;
$mpdf->SetFont('dejavusans', '', 12);
$mpdf->SetXY($padLeft, $perfilStartY);
if ($perfilText !== '') {
    $mpdf->MultiCell($perfilTextW, 6, $perfilText, 0, 'L');
}
$bulletY = $perfilStartY + 40;
$bulletChar = "\xE2\x80\xA2";
foreach (array_slice($ventajas, 0, 4) as $v) {
    $mpdf->SetXY($padLeft, $bulletY);
    $mpdf->Cell(4, 5, $bulletChar, 0, 0, 'C');
    $mpdf->MultiCell($textW - 4, 5, $v, 0, 'L');
    $bulletY += 8;
}

$imgColX = $colLeftW + 15;
$imgSmallW2 = 24;
$imgSmallH2 = 18;
if (file_exists($portadaImgSmall)) {
    $imgSize = @getimagesize($portadaImgSmall);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $sw = $imgSmallH2 * ($imgSize[0] / $imgSize[1]);
        $mpdf->Image($portadaImgSmall, $wMm - 15 - $sw, $padTop, $sw, $imgSmallH2);
    } else {
        $mpdf->Image($portadaImgSmall, $wMm - 15 - $imgSmallW2, $padTop, $imgSmallW2, $imgSmallH2);
    }
}
$vertMaxH = 120;
$vertMaxW = 85;
$vertGap = 14;
$barH = 6;
$barGap = 4;
$mpdf->SetFillColor(117, 168, 218);
$slide2ImgPath = file_exists($portadaImg2) ? $portadaImg2 : (file_exists($assetsDir . '/img_slide2.png') ? $assetsDir . '/img_slide2.png' : null);
if ($slide2ImgPath) {
    $imgSize = @getimagesize($slide2ImgPath);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $ratio = $imgSize[0] / $imgSize[1];
        $w = min($vertMaxW, $vertMaxH * $ratio);
        $h = $w / $ratio;
        if ($h > $vertMaxH) {
            $h = $vertMaxH;
            $w = $h * $ratio;
        }
        $vert1X = $imgColX;
        $vert1Y = 72;
        $mpdf->Image($slide2ImgPath, $vert1X, $vert1Y, $w, $h);
        $mpdf->Rect($vert1X, $vert1Y + $h + $barGap, $w, $barH, 'F');
        $vert2X = $vert1X + $w + $vertGap;
        $vert2Y = 88;
        $mpdf->Rect($vert2X, $vert2Y - $barGap - $barH, $w, $barH, 'F');
        $mpdf->Image($slide2ImgPath, $vert2X, $vert2Y, $w, $h);
    }
}
$mpdf->SetLeftMargin(0);
$mpdf->SetRightMargin(0);

// —— Slides 3..3+N: Productos Exportables y Servicios (un slide por producto) ——
$prodSlidePadLeft = 22;
$prodSlidePadTop = 24;
$prodColLeftW = round($wMm * 0.55);
$prodColRightX = $prodColLeftW + 18;
$prodImgRightMargin = 20;
$prodImgAreaW = $wMm - $prodColRightX - $prodImgRightMargin;
$prodImgBarH = 6;
$prodImgAreaH = $hMm;
$prodBarFraction = 0.5;
$prodTitleColor = [0, 31, 96];      // #001f60
$prodNameColor = [51, 51, 51];       // #333333
$prodSectorColor = [102, 153, 204];  // #6699CC (solo para servicios)
$prodNumBoxW = 16;
$prodNumBoxH = 11;
$prodSmallImgW = 75;
$prodSmallImgH = 42;
$prodSmallImgY = $hMm - $prodSmallImgH - 22;

foreach ($products as $idx => $prod) {
    $mpdf->AddPage();
    $mpdf->SetXY(0, 0);
    $mpdf->SetFillColor(255, 255, 255);
    $mpdf->Rect(0, 0, $wMm, $hMm, 'F');

    // Título "Productos Exportables y Servicios"
    $mpdf->SetTextColor($prodTitleColor[0], $prodTitleColor[1], $prodTitleColor[2]);
    $mpdf->SetFont('dejavusans', 'B', 28);
    $mpdf->SetXY($prodSlidePadLeft, $prodSlidePadTop);
    $mpdf->Cell($prodColLeftW - $prodSlidePadLeft, 11, 'Productos Exportables y', 0, 1, 'L');
    $mpdf->SetXY($prodSlidePadLeft, $prodSlidePadTop + 11);
    $mpdf->Cell($prodColLeftW - $prodSlidePadLeft, 12, 'Servicios', 0, 1, 'L');

    // Bloque producto: número en caja azul + nombre + sector/rubro
    $blockY = $prodSlidePadTop + 52;
    $num = sprintf('%02d.', $idx + 1);
    $mpdf->SetFillColor($prodTitleColor[0], $prodTitleColor[1], $prodTitleColor[2]);
    $mpdf->Rect($prodSlidePadLeft, $blockY, $prodNumBoxW, $prodNumBoxH, 'F');
    $mpdf->SetTextColor(255, 255, 255);
    $mpdf->SetFont('dejavusans', 'B', 12);
    $mpdf->SetXY($prodSlidePadLeft, $blockY + 2);
    $mpdf->Cell($prodNumBoxW, 7, $num, 0, 0, 'C');

    $nameX = $prodSlidePadLeft + $prodNumBoxW + 6;
    $nameW = $prodColRightX - $nameX - 10;
    $mpdf->SetTextColor($prodNameColor[0], $prodNameColor[1], $prodNameColor[2]);
    $mpdf->SetFont('dejavusans', 'B', 16);
    $mpdf->SetXY($nameX, $blockY);
    $mpdf->MultiCell($nameW, 7, $prod['name'], 0, 'L');
    if (isset($prod['type']) && strtolower($prod['type']) === 'service') {
        $sectorY = $blockY + $prodNumBoxH + 4;
        $mpdf->SetTextColor($prodSectorColor[0], $prodSectorColor[1], $prodSectorColor[2]);
        $mpdf->SetFont('dejavusans', '', 12);
        $mpdf->SetXY($prodSlidePadLeft, $sectorY);
        $mpdf->Cell($prodColLeftW - $prodSlidePadLeft, 6, $prod['activity'] !== '' ? $prod['activity'] : 'Sector / Rubro', 0, 1, 'L');
    }
    // Imagen pequeña abajo a la izquierda (decorativa)
    $smallImgPath = $slide2ImgPath ?? $portadaImg2;
    if ($smallImgPath && file_exists($smallImgPath)) {
        $imgSize = @getimagesize($smallImgPath);
        if (!empty($imgSize[0]) && !empty($imgSize[1])) {
            $ratio = $imgSize[0] / $imgSize[1];
            $sw = $prodSmallImgW;
            $sh = $prodSmallImgH;
            if ($ratio > $sw / $sh) {
                $sh = $sw / $ratio;
            } else {
                $sw = $sh * $ratio;
            }
            $sx = $prodSlidePadLeft;
            $sy = $prodSmallImgY + ($prodSmallImgH - $sh) / 2;
            $mpdf->Image($smallImgPath, $sx, $sy, $sw, $sh);
        } else {
            $mpdf->Image($smallImgPath, $prodSlidePadLeft, $prodSmallImgY, $prodSmallImgW, $prodSmallImgH);
        }
    }

    // Columna derecha: imagen del producto; barras azules encima de la imagen (arriba derecha, abajo izquierda)
    $ix = $prodColRightX;
    $iy = 0;
    $iw = $prodImgAreaW;
    $ih = $prodImgAreaH;
    $imgPath = $productImagePaths[$prod['id']] ?? null;
    if ($imgPath && file_exists($imgPath)) {
        $imgSize = @getimagesize($imgPath);
        if (!empty($imgSize[0]) && !empty($imgSize[1])) {
            $ratio = $imgSize[0] / $imgSize[1];
            $iw = $prodImgAreaW;
            $ih = $prodImgAreaH;
            if ($ratio > $iw / $ih) {
                $ih = $iw / $ratio;
            } else {
                $iw = $ih * $ratio;
            }
            $ix = $prodColRightX + ($prodImgAreaW - $iw) / 2;
            $iy = ($prodImgAreaH - $ih) / 2;
            $mpdf->Image($imgPath, $ix, $iy, $iw, $ih);
        } else {
            $mpdf->Image($imgPath, $ix, $iy, $iw, $ih);
        }
    } else {
        $phPath = $slide2ImgPath ?? (file_exists($portadaImg2) ? $portadaImg2 : null);
        if ($phPath && file_exists($phPath)) {
            $imgSize = @getimagesize($phPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $ratio = $imgSize[0] / $imgSize[1];
                $iw = min($prodImgAreaW, $prodImgAreaH * $ratio);
                $ih = $iw / $ratio;
                if ($ih > $prodImgAreaH) {
                    $ih = $prodImgAreaH;
                    $iw = $ih * $ratio;
                }
                $ix = $prodColRightX + ($prodImgAreaW - $iw) / 2;
                $iy = ($prodImgAreaH - $ih) / 2;
                $mpdf->Image($phPath, $ix, $iy, $iw, $ih);
            }
        }
    }
    $barW = $iw * $prodBarFraction;
    $mpdf->SetFillColor(117, 168, 218);
    $mpdf->Rect($ix + $iw - $barW, $iy, $barW, $prodImgBarH, 'F');
    $mpdf->Rect($ix, $iy + $ih - $prodImgBarH, $barW, $prodImgBarH, 'F');
}

// —— Slide: Ventajas competitivas (fondo blanco, título + párrafo + lista numerada izquierda, imágenes derecha) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$ventColLeftW = round($wMm * 0.36);
$ventPadLeft = 22;
$ventPadTop = 24;
$ventTextW = round(($ventColLeftW - $ventPadLeft - 10) * 0.72);
$ventTitleColor = [26, 62, 125];   // #1A3E7D
$ventTextColor = [51, 51, 51];
$ventNumBoxW = 16;
$ventNumBoxH = 10;

$mpdf->SetTextColor($ventTitleColor[0], $ventTitleColor[1], $ventTitleColor[2]);
$mpdf->SetFont('dejavusans', 'B', 28);
$mpdf->SetXY($ventPadLeft, $ventPadTop);
$mpdf->Cell($ventTextW, 11, 'Ventajas', 0, 1, 'L');
$mpdf->SetXY($ventPadLeft, $ventPadTop + 11);
$mpdf->Cell($ventTextW, 12, 'competitivas', 0, 1, 'L');

$ventIntroY = $ventPadTop + 36;
$ventIntroText = "Lorem ipsum dolor sit amet, consectet adipiscin elit, sed do eiusmod tempor incididunt ut labore et dolore magna.";
$mpdf->SetTextColor($ventTextColor[0], $ventTextColor[1], $ventTextColor[2]);
$mpdf->SetFont('dejavusans', '', 12);
$mpdf->SetXY($ventPadLeft, $ventIntroY);
$mpdf->MultiCell($ventTextW, 6, $ventIntroText, 0, 'L');

$items = array_slice($ventajas, 0, 4);
if (empty($items)) {
    $items = ['—'];
}
$ventListY = $ventIntroY + 28;
foreach ($items as $i => $v) {
    $num = sprintf('%02d.', $i + 1);
    $by = $ventListY + $i * 18;
    $mpdf->SetFillColor($ventTitleColor[0], $ventTitleColor[1], $ventTitleColor[2]);
    $mpdf->Rect($ventPadLeft, $by, $ventNumBoxW, $ventNumBoxH, 'F');
    $mpdf->SetTextColor(255, 255, 255);
    $mpdf->SetFont('dejavusans', 'B', 11);
    $mpdf->SetXY($ventPadLeft, $by + 2);
    $mpdf->Cell($ventNumBoxW, 6, $num, 0, 0, 'C');
    $mpdf->SetTextColor($ventTitleColor[0], $ventTitleColor[1], $ventTitleColor[2]);
    $mpdf->SetFont('dejavusans', 'B', 14);
    $mpdf->SetXY($ventPadLeft + $ventNumBoxW + 6, $by);
    $mpdf->MultiCell($ventTextW - $ventNumBoxW - 6, 7, $v, 0, 'L');
}

$ventImgColX = $ventColLeftW + 40;
if (file_exists($portadaImgSmall)) {
    $imgSize = @getimagesize($portadaImgSmall);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $smallScaleH = 18;
        $smallScaleW = $smallScaleH * ($imgSize[0] / $imgSize[1]);
        $mpdf->Image($portadaImgSmall, $wMm - 15 - $smallScaleW, 22, $smallScaleW, $smallScaleH);
    } else {
        $mpdf->Image($portadaImgSmall, $wMm - 15 - 24, 22, 24, 18);
    }
}
$ventVertMaxH1 = 140;
$ventVertMaxW1 = 122;
$ventVertMaxH2 = 140;
$ventVertMaxW2 = 122;
$ventVertGap = 10;
$ventBarH = 6;
$ventStaggerY = 22;
$mpdf->SetFillColor(117, 168, 218);
if ($slide2ImgPath && file_exists($slide2ImgPath)) {
    $imgSize = @getimagesize($slide2ImgPath);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $ratio = $imgSize[0] / $imgSize[1];
        $w1 = min($ventVertMaxW1, $ventVertMaxH1 * $ratio);
        $h1 = $w1 / $ratio;
        if ($h1 > $ventVertMaxH1) {
            $h1 = $ventVertMaxH1;
            $w1 = $h1 * $ratio;
        }
        $v1X = $ventImgColX;
        $v1Y = 68;
        $mpdf->Rect($v1X, $v1Y, $w1, $ventBarH, 'F');
        $mpdf->Image($slide2ImgPath, $v1X, $v1Y + $ventBarH, $w1, $h1);
        $w2 = min($ventVertMaxW2, $ventVertMaxH2 * $ratio);
        $h2 = $w2 / $ratio;
        if ($h2 > $ventVertMaxH2) {
            $h2 = $ventVertMaxH2;
            $w2 = $h2 * $ratio;
        }
        $v2X = $v1X + $w1 + $ventVertGap;
        $v2Y = $v1Y + $ventStaggerY;
        $mpdf->Image($slide2ImgPath, $v2X, $v2Y, $w2, $h2);
        $mpdf->Rect($v2X, $v2Y + $h2, $w2, $ventBarH, 'F');
    }
}

// —— Slides: Certificaciones (5 ítems por slide; total = min(products, certs); collage suave con fotos) ——
$certCollageW = round($wMm * 0.62);
$certPlaceholderPath = $slide2ImgPath ?? (file_exists($portadaImg2) ? $portadaImg2 : null);
$certCollageSlots = [
    ['x' => 22, 'y' => 28, 'w' => 95, 'h' => 95],
    ['x' => 125, 'y' => 32, 'w' => 85, 'h' => 85],
    ['x' => 35, 'y' => 132, 'w' => 80, 'h' => 80],
    ['x' => 122, 'y' => 128, 'w' => 88, 'h' => 88],
];
$certCollagePaths = [];
foreach (array_slice($products, 0, 4) as $p) {
    $path = $productImagePaths[$p['id']] ?? null;
    if ($path && file_exists($path)) {
        $certCollagePaths[] = $path;
    }
}

$certColX = $certCollageW + 12;
$certColW = $wMm - $certColX - 20;
$certTitleColor = [51, 51, 51];
$certTextColor = [60, 60, 60];
$certIconPath = $assetsDir . '/certificacion_icon.png';
if (!file_exists($certIconPath)) {
    $certIconPath = dirname(__DIR__) . '/oferta/assets/certificacion_icon.png';
}
$certIconSize = 10;
$certPadTop = 38;
$certListY = $certPadTop + 28;
$certLineH = 18;
$certPerSlide = 5;
$productCount = count($products);
$certListTotal = array_slice($certificacionesList, 0, $productCount);
$certChunks = array_chunk($certListTotal, $certPerSlide);
foreach ($certChunks as $certItems) {
    $mpdf->AddPage();
    $mpdf->SetXY(0, 0);
    $mpdf->SetFillColor(255, 255, 255);
    $mpdf->Rect(0, 0, $wMm, $hMm, 'F');

    foreach ($certCollageSlots as $idx => $cr) {
        $imgPath = $certCollagePaths[$idx] ?? null;
        if ($imgPath && file_exists($imgPath)) {
            $mpdf->Image($imgPath, $cr['x'], $cr['y'], $cr['w'], $cr['h']);
        }
    }

    $mpdf->SetTextColor($certTitleColor[0], $certTitleColor[1], $certTitleColor[2]);
    $mpdf->SetFont('dejavusans', 'B', 28);
    $mpdf->SetXY($certColX, $certPadTop);
    $mpdf->Cell($certColW, 12, 'Certificaciones', 0, 1, 'L');

    foreach ($certItems as $i => $c) {
        if ($c === '') continue;
        $cy = $certListY + $i * $certLineH;
        if (file_exists($certIconPath)) {
            $mpdf->Image($certIconPath, $certColX, $cy, $certIconSize, $certIconSize);
        }
        $mpdf->SetTextColor($certTextColor[0], $certTextColor[1], $certTextColor[2]);
        $mpdf->SetFont('dejavusans', '', 12);
        $mpdf->SetXY($certColX + $certIconSize + 6, $cy + 1);
        $mpdf->Cell($certColW - $certIconSize - 6, 8, $c, 0, 1, 'L');
    }
}

// —— Slide: Mercados Objetivo (fondo blanco, lista numerada izquierda, título + texto + grid 2x2 derecha) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$mercColLeftW = round($wMm * 0.34);
$mercPadLeft = 22;
$mercPadTop = 36;
$mercTitleColor = [0, 31, 96];
$mercTextColor = [0, 0, 0];
$mercBulletChar = "\xE2\x80\xA2";
$mercMarkets = array_slice(array_pad($targetMarkets, 4, ''), 0, 4);
$mercNumX = 14;
$mercColStartX = 38;
$mercTitleX = $mercColStartX;
$mercBulletX = $mercColStartX;
$mercBulletW = 5;
$mercBulletTextW = $mercColLeftW - $mercBulletX - $mercBulletW - 5;
$mercGapNumTitle = 2;
$mercGapTitleBullets = 3;
$mercGapBullets = 4;
$mercGapBetweenItems = 5;
$mercNumY = $mercPadTop;
$mercNumOffsetY = 6;
foreach ($mercMarkets as $i => $m) {
    $num = sprintf('%02d.', $i + 1);
    $mpdf->SetTextColor($mercTitleColor[0], $mercTitleColor[1], $mercTitleColor[2]);
    $mpdf->SetFont('dejavusans', 'B', 24);
    $mpdf->SetXY($mercNumX, $mercNumY + $mercNumOffsetY);
    $mpdf->Cell(34, 10, $num, 0, 0, 'L');
    $heading = 'Región/País';
    $mpdf->SetFont('dejavusans', 'B', 16);
    $mpdf->SetXY($mercColStartX, $mercNumY + $mercGapNumTitle);
    $mpdf->Cell($mercColLeftW - $mercColStartX - 5, 6, $heading, 0, 1, 'L');
    $mpdf->SetTextColor($mercTextColor[0], $mercTextColor[1], $mercTextColor[2]);
    $mpdf->SetFont('dejavusans', '', 13);
    $bulletY = $mercNumY + $mercGapNumTitle + 6 + $mercGapTitleBullets;
    for ($b = 0; $b < 3; $b++) {
        $mpdf->SetXY($mercBulletX, $bulletY);
        $mpdf->Cell($mercBulletW, 5, $mercBulletChar, 0, 0, 'C');
        $mpdf->SetXY($mercBulletX + $mercBulletW, $bulletY);
        $mpdf->MultiCell($mercBulletTextW, 5, 'Lorem ipsum dolor.', 0, 'L');
        $bulletY += 5 + $mercGapBullets;
    }
    $mercNumY = $bulletY + $mercGapBetweenItems;
}

$mercColRightX = $mercColLeftW + 18;
$mercColRightW = $wMm - $mercColRightX - 20;
$mercGridY = 60;
$mercGridGap = 10;
$mercGridBottomMargin = 14;
$mercCellW = ($mercColRightW - $mercGridGap) / 2;
$mercCellWRow1 = $mercCellW * 0.78;
$mercCellH = ($hMm - $mercGridY - $mercGridBottomMargin - $mercGridGap) / 2;
$mercImgPath = $slide2ImgPath ?? (file_exists($portadaImg2) ? $portadaImg2 : null);
if ($mercImgPath && file_exists($mercImgPath)) {
    $mpdf->Image($mercImgPath, $mercColRightX, $mercGridY, $mercCellWRow1, $mercCellH);
    $mpdf->Image($mercImgPath, $mercColRightX, $mercGridY + $mercCellH + $mercGridGap, $mercCellW, $mercCellH);
    $mpdf->Image($mercImgPath, $mercColRightX + $mercCellW + $mercGridGap, $mercGridY + $mercCellH + $mercGridGap, $mercCellW, $mercCellH);
}
$mercTextCellX = $mercColRightX + $mercCellWRow1 + $mercGridGap;
$mercTextCellY = $mercGridY;
$mercTextCellW = $mercColRightW - $mercCellWRow1 - $mercGridGap;
$mercTextCellH = $mercCellH;
$mpdf->SetTextColor($mercTitleColor[0], $mercTitleColor[1], $mercTitleColor[2]);
$mpdf->SetFont('dejavusans', 'B', 22);
$mpdf->SetXY($mercTextCellX + 8, $mercTextCellY + 10);
$mpdf->Cell($mercTextCellW - 16, 10, 'Mercados Objetivo', 0, 1, 'L');
$mercIntroText = "Lorem ipsum dolor sit amet, consectet adipiscin elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliq enim minim veniam, quis nostrud exercit ullamco laboris nisi ut aliquip commodo consequat aute irure tempor.";
$mpdf->SetTextColor($mercTextColor[0], $mercTextColor[1], $mercTextColor[2]);
$mpdf->SetFont('dejavusans', '', 11);
$mpdf->SetXY($mercTextCellX + 8, $mercTextCellY + 24);
$mpdf->MultiCell($mercTextCellW - 16, 6, $mercIntroText, 0, 'L');

// —— Slide final: Contacto (fondo blanco, izquierda: título + texto + bloque contacto + img; derecha: img) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$contColLeftW = round($wMm * 0.45);
$contColRightX = $contColLeftW + 8;
$contColRightW = $wMm - $contColRightX;
$contPadLeft = 22;
$contPadTop = 24;
$contTitleColor = [0, 31, 96];
$contTextColor = [0, 0, 0];

$mpdf->SetTextColor($contTitleColor[0], $contTitleColor[1], $contTitleColor[2]);
$mpdf->SetFont('dejavusans', 'B', 32);
$mpdf->SetXY($contPadLeft, $contPadTop);
$mpdf->Cell($contColLeftW - $contPadLeft - 5, 14, 'Contacto', 0, 1, 'L');

$contIntroY = $contPadTop + 20;
$contIntroText = "Lorem ipsum dolor sit amet, consectet adipiscin elit, sed do eiusmod tempor incididunt ut labore et dolore magna.";
$mpdf->SetTextColor($contTextColor[0], $contTextColor[1], $contTextColor[2]);
$mpdf->SetFont('dejavusans', '', 12);
$mpdf->SetXY($contPadLeft, $contIntroY);
$mpdf->MultiCell($contColLeftW - $contPadLeft - 5, 6, $contIntroText, 0, 'L');

$contBlockY = $contIntroY + 34;
$contBlockH = 42;
$contBlockPad = 10;
$mpdf->SetFillColor($contTitleColor[0], $contTitleColor[1], $contTitleColor[2]);
$mpdf->Rect($contPadLeft, $contBlockY, $contColLeftW - $contPadLeft - 5, $contBlockH, 'F');
$mpdf->SetTextColor(255, 255, 255);
$mpdf->SetFont('dejavusans', 'B', 13);
$halfW = ($contColLeftW - $contPadLeft - 5 - 2 * $contBlockPad) / 2;
$row1Y = $contBlockY + $contBlockPad;
$row2Y = $contBlockY + $contBlockH / 2 + 2;
$mpdf->SetXY($contPadLeft + $contBlockPad, $row1Y);
$mpdf->Cell($halfW, 8, 'Teléfono: ' . ($contactPhone !== '' ? $contactPhone : 'xxx-xxx-xxx'), 0, 1, 'L');
$mpdf->SetXY($contPadLeft + $contBlockPad + $halfW, $row1Y);
$mpdf->Cell($halfW, 8, ($companyWebsite !== '' ? $companyWebsite : 'www.xxx-xxx.com'), 0, 1, 'L');
$mpdf->SetXY($contPadLeft + $contBlockPad, $row2Y);
$mpdf->Cell($halfW, 8, 'Mail: ' . ($contactEmail !== '' ? $contactEmail : '@reallygreatsite'), 0, 1, 'L');
$mpdf->SetXY($contPadLeft + $contBlockPad + $halfW, $row2Y);
$mpdf->Cell($halfW, 8, 'Email: ' . ($contactEmail !== '' ? $contactEmail : 'xxxx@reallygreatsite.com'), 0, 1, 'L');

$contImgLeftY = $contBlockY + $contBlockH + 12;
$contImgLeftH = 58;
$contImgLeftW = $contColLeftW - $contPadLeft - 5;
if (file_exists($portadaBg)) {
    $mpdf->Image($portadaBg, $contPadLeft, $contImgLeftY, $contImgLeftW, $contImgLeftH);
}

$contImgRightMarginV = 18;
$contImgRightMarginH = 18;
$contImgRightY = $contImgRightMarginV;
$contImgRightH = $hMm - 2 * $contImgRightMarginV;
$contImgRightW = $contColRightW - $contImgRightMarginH;
$contImgRightScale = 0.88;
$contImgRightDrawW = $contImgRightW * $contImgRightScale;
$contImgRightDrawH = $contImgRightH * $contImgRightScale;
$contImgRightDrawX = $contColRightX + ($contImgRightW - $contImgRightDrawW) / 2;
$contImgRightDrawY = $contImgRightY + ($contImgRightH - $contImgRightDrawH) / 2;
if (file_exists($portadaImg2)) {
    $mpdf->Image($portadaImg2, $contImgRightDrawX, $contImgRightDrawY, $contImgRightDrawW, $contImgRightDrawH);
}

$filename = 'Presentacion_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $empresaNombre) . '_' . $ano . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo $mpdf->Output('', 'S');
exit;
