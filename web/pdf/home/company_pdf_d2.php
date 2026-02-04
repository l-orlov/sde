<?php
/**
 * PDF presentación de empresa — diseño D2 (company_pdf_d2.php).
 * Variante de diseño; misma estructura de datos que D1.
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

// ——— Generar PDF (D2) ———
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

$portadaBgPath = $assetsDir . '/background_slide1.jpg';
if (!file_exists($portadaBgPath)) {
    $portadaBgPath = dirname(__DIR__) . '/oferta/assets/background_slide1.jpg';
}
// Helper: crear imagen de fondo con bordes redondeados vía GD (Mpdf no expone writer para clipping)
$d2_roundedBgPath = null;
if (extension_loaded('gd') && file_exists($portadaBgPath)) {
    $portadaBgPathForGd = $portadaBgPath;
} else {
    $portadaBgPathForGd = null;
}
if ($portadaBgPathForGd !== null) {
    $info = @getimagesize($portadaBgPathForGd);
    if ($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        $src = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($portadaBgPathForGd) : @imagecreatefrompng($portadaBgPathForGd);
        if ($src) {
            $sw = imagesx($src);
            $sh = imagesy($src);
            $r = (int) min($sw, $sh) * 0.06;
            if ($r < 8) {
                $r = 8;
            }
            $dst = imagecreatetruecolor($sw, $sh);
            if ($dst) {
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefill($dst, 0, 0, $white);
                imagecopy($dst, $src, 0, 0, 0, 0, $sw, $sh);
                for ($py = 0; $py < $r; $py++) {
                    for ($px = 0; $px < $r; $px++) {
                        if (($px - $r) * ($px - $r) + ($py - $r) * ($py - $r) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                for ($py = 0; $py < $r; $py++) {
                    for ($px = $sw - $r; $px < $sw; $px++) {
                        if (($px - ($sw - $r)) * ($px - ($sw - $r)) + ($py - $r) * ($py - $r) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                for ($py = $sh - $r; $py < $sh; $py++) {
                    for ($px = 0; $px < $r; $px++) {
                        if (($px - $r) * ($px - $r) + ($py - ($sh - $r)) * ($py - ($sh - $r)) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                for ($py = $sh - $r; $py < $sh; $py++) {
                    for ($px = $sw - $r; $px < $sw; $px++) {
                        if (($px - ($sw - $r)) * ($px - ($sw - $r)) + ($py - ($sh - $r)) * ($py - ($sh - $r)) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                $tmpBg = sys_get_temp_dir() . '/bg_round_' . uniqid() . '.png';
                if (imagepng($dst, $tmpBg)) {
                    $d2_roundedBgPath = $tmpBg;
                }
                imagedestroy($dst);
            }
            imagedestroy($src);
        }
    }
}

// Helper: crear imagen PNG del logo recortada en círculo (para que se vea redondeada)
$d2_logoCircularPath = null;
if (file_exists($logoPath) && extension_loaded('gd')) {
    $srcImg = @imagecreatefromstring(file_get_contents($logoPath));
    if ($srcImg) {
        $size = 160;
        $dst = imagecreatetruecolor($size, $size);
        if ($dst) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $trans);
            $sw = imagesx($srcImg);
            $sh = imagesy($srcImg);
            imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $size, $size, $sw, $sh);
            $cx = $size / 2;
            $r = $size / 2 - 1;
            for ($py = 0; $py < $size; $py++) {
                for ($px = 0; $px < $size; $px++) {
                    if (($px - $cx) * ($px - $cx) + ($py - $cx) * ($py - $cx) > $r * $r) {
                        imagealphablending($dst, false);
                        imagesetpixel($dst, $px, $py, $trans);
                    }
                }
            }
            $tmpFile = sys_get_temp_dir() . '/logo_circle_' . uniqid() . '.png';
            if (imagepng($dst, $tmpFile)) {
                $d2_logoCircularPath = $tmpFile;
            }
            imagedestroy($dst);
        }
        imagedestroy($srcImg);
    }
}

// —— Slide 1 D2: Portada con fondo background_slide1.jpg (bordes redondeados), texto a la izquierda, logo circular abajo a la derecha ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$d2_margin = 32;
$d2_bgX = $d2_margin;
$d2_bgY = $d2_margin;
$d2_bgW = $wMm - 2 * $d2_margin;
$d2_bgH = $hMm - 2 * $d2_margin;
$d2_bgToUse = ($d2_roundedBgPath !== null && file_exists($d2_roundedBgPath)) ? $d2_roundedBgPath : $portadaBgPath;
if (file_exists($d2_bgToUse)) {
    $mpdf->Image($d2_bgToUse, $d2_bgX, $d2_bgY, $d2_bgW, $d2_bgH);
    if ($d2_roundedBgPath !== null && file_exists($d2_roundedBgPath)) {
        @unlink($d2_roundedBgPath);
    }
}

// Bloque azul #003399 con nombre de la empresa: menor altura y anchura, solo la parte derecha con esquinas redondeadas
$d2_blueX = 0;
$d2_blueW = round($wMm * 0.36);
$d2_blueH = 34;
$d2_blueY = 94;
$d2_blueRadius = 12;
$d2_blueColor = [0, 51, 153];
$mpdf->SetFillColor($d2_blueColor[0], $d2_blueColor[1], $d2_blueColor[2]);
$mpdf->RoundedRect($d2_blueX, $d2_blueY, $d2_blueW, $d2_blueH, $d2_blueRadius, 'F');
$mpdf->Rect($d2_blueX, $d2_blueY, $d2_blueRadius, $d2_blueH, 'F');
$portadaTituloD2 = $empresaNombre !== '' ? mb_strtoupper($empresaNombre) : 'EMPRESA';
$mpdf->SetTextColor(255, 255, 255);
$mpdf->SetFont('dejavusans', 'B', 28);
$d2_titlePadLeft = 12;
$d2_titleLineH = 10;
$mpdf->SetXY($d2_blueX + $d2_titlePadLeft, $d2_blueY + ($d2_blueH - $d2_titleLineH) / 2);
$mpdf->Cell($d2_blueW - $d2_titlePadLeft - 8, $d2_titleLineH, $portadaTituloD2, 0, 1, 'L');

// Texto abajo a la izquierda (provincia, año): un poco a la derecha, un poco más arriba, en negrita; año en rojo
$d2_textLeftX = 40;
$d2_textLeftY = $hMm - 60;
$mpdf->SetTextColor(0, 0, 0);
$mpdf->SetFont('dejavusans', 'B', 16);
$provinciaLocalidadD2 = trim($provincia . ($locality !== '' ? ' / ' . $locality : ''));
if ($provinciaLocalidadD2 !== '') {
    $mpdf->SetXY($d2_textLeftX, $d2_textLeftY);
    $mpdf->Cell(120, 9, $provinciaLocalidadD2, 0, 1, 'L');
}
$mpdf->SetTextColor(196, 52, 59);
$mpdf->SetFont('dejavusans', 'B', 14);
$mpdf->SetXY($d2_textLeftX, $d2_textLeftY + 12);
$mpdf->Cell(120, 9, 'Año ' . $ano, 0, 1, 'L');
$mpdf->SetTextColor(0, 0, 0);

// Logo en la esquina inferior derecha (sin fondo azul, solo imagen circular o círculo blanco)
$d2_logoMargin = 22;
$d2_logoSize = 38;
$d2_logoX = $wMm - $d2_logoMargin - $d2_logoSize;
$d2_logoY = $hMm - $d2_logoMargin - $d2_logoSize;
$logoToUse = ($d2_logoCircularPath !== null && file_exists($d2_logoCircularPath)) ? $d2_logoCircularPath : $logoPath;
if (file_exists($logoToUse)) {
    $mpdf->Image($logoToUse, $d2_logoX, $d2_logoY, $d2_logoSize, $d2_logoSize);
    if ($d2_logoCircularPath !== null && file_exists($d2_logoCircularPath)) {
        @unlink($d2_logoCircularPath);
    }
} else {
    $cx = $d2_logoX + $d2_logoSize / 2;
    $cy = $d2_logoY + $d2_logoSize / 2;
    $mpdf->SetFillColor(248, 248, 248);
    $mpdf->Circle($cx, $cy, $d2_logoSize / 2, 'F');
}

$portadaImg2 = $assetsDir . '/img_slide2.png';
if (!file_exists($portadaImg2)) {
    $portadaImg2 = dirname(__DIR__) . '/oferta/assets/img_slide2.png';
}
$portadaImgSmall = $assetsDir . '/img_small.png';
if (!file_exists($portadaImgSmall)) {
    $portadaImgSmall = dirname(__DIR__) . '/oferta/assets/img_small.png';
}

$mpdf->SetLeftMargin(0);
$mpdf->SetRightMargin(0);

// —— Slide 2: Perfil de la empresa (como en diseño: nombre empresa arriba rojo, título negro, texto, lista, bloque azul abajo izq., 3 círculos paisaje a la derecha) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$colLeftW = round($wMm * 0.45);
$padLeft = 24;
$padTop = 14;
$textW = $colLeftW - $padLeft - 8;

// Encabezado: "Nombre de la Empresa" en rojo oscuro, más arriba y fuente más grande
$mpdf->SetTextColor(160, 40, 45);
$mpdf->SetFont('dejavusans', 'B', 14);
$slide2CompanyName = $empresaNombre !== '' ? $empresaNombre : 'Nombre de la Empresa';
$mpdf->SetXY($padLeft, $padTop);
$mpdf->Cell($textW, 7, $slide2CompanyName, 0, 1, 'L');

// Título y texto más abajo, fuentes más grandes
$titleY = $padTop + 55;
$mpdf->SetTextColor(0, 0, 0);
$mpdf->SetFont('dejavusans', 'B', 34);
$mpdf->SetXY($padLeft, $titleY);
$mpdf->Cell($textW, 12, 'Perfil de la', 0, 1, 'L');
$mpdf->SetXY($padLeft, $titleY + 12);
$mpdf->Cell($textW, 13, 'Empresa', 0, 1, 'L');

// Párrafos Lorem
$perfilTextW = round($textW * 0.92);
$perfilStartY = $titleY + 38;
$mpdf->SetFont('dejavusans', '', 13);
$mpdf->SetXY($padLeft, $perfilStartY);
if ($perfilText !== '') {
    $mpdf->MultiCell($perfilTextW, 6.5, $perfilText, 0, 'L');
}
$bulletY = $perfilStartY + 32;
$bulletChar = "\xE2\x80\xA2";
$mpdf->SetFont('dejavusans', '', 13);
foreach (array_slice($ventajas, 0, 4) as $v) {
    $mpdf->SetXY($padLeft, $bulletY);
    $mpdf->Cell(5, 6, $bulletChar, 0, 0, 'C');
    $mpdf->MultiCell($textW - 5, 6, $v, 0, 'L');
    $bulletY += 8;
}

// Tres círculos img_circule.png con recorte circular (fondo redondo): diagonal desde abajo-izquierda hacia arriba-derecha
$imgCirculePath = $assetsDir . '/img_circule.png';
if (!file_exists($imgCirculePath)) {
    $imgCirculePath = dirname(__DIR__) . '/oferta/assets/img_circule.png';
}
$s2_circuleCircularPath = null;
if (file_exists($imgCirculePath) && extension_loaded('gd')) {
    $srcImg = @imagecreatefromstring(file_get_contents($imgCirculePath));
    if ($srcImg) {
        $size = 220;
        $dst = imagecreatetruecolor($size, $size);
        if ($dst) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $trans);
            $sw = imagesx($srcImg);
            $sh = imagesy($srcImg);
            imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $size, $size, $sw, $sh);
            $cx = $size / 2;
            $r = $size / 2 - 1;
            for ($py = 0; $py < $size; $py++) {
                for ($px = 0; $px < $size; $px++) {
                    if (($px - $cx) * ($px - $cx) + ($py - $cx) * ($py - $cx) > $r * $r) {
                        imagesetpixel($dst, $px, $py, $trans);
                    }
                }
            }
            $tmpFile = sys_get_temp_dir() . '/circule_circle_' . uniqid() . '.png';
            if (imagepng($dst, $tmpFile)) {
                $s2_circuleCircularPath = $tmpFile;
            }
            imagedestroy($dst);
        }
        imagedestroy($srcImg);
    }
}
$s2_circleSize = 100;
$s2_startX = $colLeftW + 14;
$s2_startY = $hMm - $s2_circleSize - 42;
$s2_stepX = 52;
$s2_stepY = -48;
$s2_imageToUse = ($s2_circuleCircularPath !== null && file_exists($s2_circuleCircularPath)) ? $s2_circuleCircularPath : $imgCirculePath;
$s2_contourWidth = 3;
$s2_contourColor = [255, 255, 255];
if (file_exists($s2_imageToUse)) {
    $mpdf->SetDrawColor($s2_contourColor[0], $s2_contourColor[1], $s2_contourColor[2]);
    $mpdf->SetLineWidth($s2_contourWidth);
    foreach ([[0, 0], [1, 1], [2, 2]] as $idx) {
        $s2_x = $s2_startX + $idx[0] * $s2_stepX;
        $s2_y = $s2_startY + $idx[1] * $s2_stepY;
        $mpdf->Image($s2_imageToUse, $s2_x, $s2_y, $s2_circleSize, $s2_circleSize);
        $s2_cx = $s2_x + $s2_circleSize / 2;
        $s2_cy = $s2_y + $s2_circleSize / 2;
        $mpdf->Circle($s2_cx, $s2_cy, $s2_circleSize / 2, 'S');
    }
    $mpdf->SetLineWidth(0.2);
    $mpdf->SetDrawColor(0, 0, 0);
    if ($s2_circuleCircularPath !== null && file_exists($s2_circuleCircularPath)) {
        @unlink($s2_circuleCircularPath);
    }
}

// Miniatura paisaje en la esquina inferior derecha
$s2SmallW = 38;
$s2SmallH = 26;
$s2SmallX = $wMm - $s2SmallW - 16;
$s2SmallY = $hMm - $s2SmallH - 16;
if (file_exists($portadaImgSmall)) {
    $imgSize = @getimagesize($portadaImgSmall);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $ratio = $imgSize[0] / $imgSize[1];
        $w = $s2SmallW;
        $h = $s2SmallH;
        if ($ratio > $w / $h) {
            $h = $w / $ratio;
        } else {
            $w = $h * $ratio;
        }
        $mpdf->Image($portadaImgSmall, $s2SmallX + ($s2SmallW - $w) / 2, $s2SmallY + ($s2SmallH - $h) / 2, $w, $h);
    } else {
        $mpdf->Image($portadaImgSmall, $s2SmallX, $s2SmallY, $s2SmallW, $s2SmallH);
    }
}

$mpdf->SetLeftMargin(0);
$mpdf->SetRightMargin(0);

// —— Slide 3: Productos / Servicios exportables (semi-círculo azul arriba centrado, contenedor con paisaje redondeado, caja azul con título más abajo y fuente mayor, miniatura) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$s3_padLeft = 24;
$s3_padTop = 10;
$s3_companyName = $empresaNombre !== '' ? $empresaNombre : 'Nombre de la Empresa';
$mpdf->SetTextColor(160, 40, 45);
$mpdf->SetFont('dejavusans', 'B', 14);
$mpdf->SetXY($s3_padLeft, $s3_padTop);
$mpdf->Cell(200, 7, $s3_companyName, 0, 1, 'L');

// Bloque azul como en diseño: semi-círculo centrado, más estrecho
$s3_blueCenterX = $wMm / 2;
$s3_blueRx = 29;
$s3_blueRy = 35;
$mpdf->SetFillColor(0, 51, 153);
$mpdf->Ellipse($s3_blueCenterX, 0, $s3_blueRx, $s3_blueRy, 'F');

$s3_contX = 18;
$s3_contY = 52;
$s3_contW = $wMm - 2 * $s3_contX;
$s3_contH = $hMm - $s3_contY - 20;
$s3_contR = 200;
$mpdf->SetFillColor(255, 255, 255);
$mpdf->RoundedRect($s3_contX, $s3_contY, $s3_contW, $s3_contH, $s3_contR, 'F');
$s3_imgMargin = 6;
$s3_imgX = $s3_contX + $s3_imgMargin;
$s3_imgY = $s3_contY + $s3_imgMargin;
$s3_imgW = $s3_contW - 2 * $s3_imgMargin;
$s3_imgH = $s3_contH - 2 * $s3_imgMargin;
$s3_roundedBgPath = null;
if (extension_loaded('gd') && file_exists($portadaBgPath)) {
    $info = @getimagesize($portadaBgPath);
    if ($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        $src = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($portadaBgPath) : @imagecreatefrompng($portadaBgPath);
        if ($src) {
            $sw = imagesx($src);
            $sh = imagesy($src);
            $r = (int) min($sw, $sh) * 0.45;
            if ($r < 65) {
                $r = 65;
            }
            $dst = imagecreatetruecolor($sw, $sh);
            if ($dst) {
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefill($dst, 0, 0, $white);
                imagecopy($dst, $src, 0, 0, 0, 0, $sw, $sh);
                for ($py = 0; $py < $r; $py++) {
                    for ($px = 0; $px < $r; $px++) {
                        if (($px - $r) * ($px - $r) + ($py - $r) * ($py - $r) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                for ($py = 0; $py < $r; $py++) {
                    for ($px = $sw - $r; $px < $sw; $px++) {
                        if (($px - ($sw - $r)) * ($px - ($sw - $r)) + ($py - $r) * ($py - $r) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                for ($py = $sh - $r; $py < $sh; $py++) {
                    for ($px = 0; $px < $r; $px++) {
                        if (($px - $r) * ($px - $r) + ($py - ($sh - $r)) * ($py - ($sh - $r)) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                for ($py = $sh - $r; $py < $sh; $py++) {
                    for ($px = $sw - $r; $px < $sw; $px++) {
                        if (($px - ($sw - $r)) * ($px - ($sw - $r)) + ($py - ($sh - $r)) * ($py - ($sh - $r)) > $r * $r) {
                            imagesetpixel($dst, $px, $py, $white);
                        }
                    }
                }
                $tmpS3 = sys_get_temp_dir() . '/s3_bg_round_' . uniqid() . '.png';
                if (imagepng($dst, $tmpS3)) {
                    $s3_roundedBgPath = $tmpS3;
                }
                imagedestroy($dst);
            }
            imagedestroy($src);
        }
    }
}
$s3_bgToUse = ($s3_roundedBgPath !== null && file_exists($s3_roundedBgPath)) ? $s3_roundedBgPath : $portadaBgPath;
if (file_exists($s3_bgToUse)) {
    $mpdf->Image($s3_bgToUse, $s3_imgX, $s3_imgY, $s3_imgW, $s3_imgH);
    if ($s3_roundedBgPath !== null && file_exists($s3_roundedBgPath)) {
        @unlink($s3_roundedBgPath);
    }
}

$s3_blueBoxW = 300;
$s3_blueBoxH = 48;
$s3_blueBoxX = $s3_contX + ($s3_contW - $s3_blueBoxW) / 2;
$s3_blueBoxY = $s3_contY + $s3_contH - $s3_blueBoxH - 2;
// Pill/capsule shape: radius = half of height so ends are semi-circles
$s3_blueBoxR = $s3_blueBoxH / 2;
$mpdf->SetFillColor(0, 51, 153);
$mpdf->RoundedRect($s3_blueBoxX, $s3_blueBoxY, $s3_blueBoxW, $s3_blueBoxH, $s3_blueBoxR, 'F');
$mpdf->SetTextColor(255, 255, 255);
$mpdf->SetFont('dejavusans', 'B', 26);
$mpdf->SetXY($s3_blueBoxX, $s3_blueBoxY + 10);
$mpdf->Cell($s3_blueBoxW, 12, 'Productos / Servicios', 0, 1, 'C');
$mpdf->SetXY($s3_blueBoxX, $s3_blueBoxY + 24);
$mpdf->Cell($s3_blueBoxW, 12, 'exportables', 0, 1, 'C');
$mpdf->SetTextColor(0, 0, 0);

$s3_thumbW = 36;
$s3_thumbH = 24;
$s3_thumbX = $wMm - $s3_thumbW - 16;
$s3_thumbY = $hMm - $s3_thumbH - 16;
if (file_exists($portadaImgSmall)) {
    $imgSize = @getimagesize($portadaImgSmall);
    if (!empty($imgSize[0]) && !empty($imgSize[1])) {
        $ratio = $imgSize[0] / $imgSize[1];
        $tw = $s3_thumbW;
        $th = $s3_thumbH;
        if ($ratio > $tw / $th) {
            $th = $tw / $ratio;
        } else {
            $tw = $th * $ratio;
        }
        $mpdf->Image($portadaImgSmall, $s3_thumbX + ($s3_thumbW - $tw) / 2, $s3_thumbY + ($s3_thumbH - $th) / 2, $tw, $th);
    } else {
        $mpdf->Image($portadaImgSmall, $s3_thumbX, $s3_thumbY, $s3_thumbW, $s3_thumbH);
    }
}

$mpdf->SetLeftMargin(0);
$mpdf->SetRightMargin(0);

// —— Slides 4..4+N: Productos / Servicios (un slide por producto: imagen centrada, pill con nombre y sector abajo derecha, miniatura arriba derecha) ——
$prodHeaderTop = 14;
$prodHeaderLeft = 24;
$prodThumbW = 36;
$prodThumbH = 24;
$prodThumbTop = 14;
$prodThumbRight = 16;
$prodImgMarginH = 24;
$prodImgMarginTop = 42;
$prodImgMarginBottom = 58;
$prodPillW = 280;
$prodPillH = 44;
$prodPillRight = 20;
$prodPillBottom = 20;
$prodPillPad = 16;
$prodCompanyName = $empresaNombre !== '' ? $empresaNombre : 'Nombre de la Empresa';

foreach ($products as $idx => $prod) {
    $mpdf->AddPage();
    $mpdf->SetXY(0, 0);
    $mpdf->SetFillColor(255, 255, 255);
    $mpdf->Rect(0, 0, $wMm, $hMm, 'F');

    // Encabezado arriba izquierda: Nombre de la Empresa (rojo oscuro, 14pt)
    $mpdf->SetTextColor(160, 40, 45);
    $mpdf->SetFont('dejavusans', 'B', 14);
    $mpdf->SetXY($prodHeaderLeft, $prodHeaderTop);
    $mpdf->Cell($wMm - $prodHeaderLeft - $prodThumbW - $prodThumbRight - 8, 7, $prodCompanyName, 0, 1, 'L');

    // Miniatura arriba derecha (paisaje)
    $thumbX = $wMm - $prodThumbW - $prodThumbRight;
    $thumbY = $prodThumbTop;
    if (file_exists($portadaImgSmall)) {
        $imgSize = @getimagesize($portadaImgSmall);
        if (!empty($imgSize[0]) && !empty($imgSize[1])) {
            $ratio = $imgSize[0] / $imgSize[1];
            $tw = $prodThumbW;
            $th = $prodThumbH;
            if ($ratio > $tw / $th) {
                $th = $tw / $ratio;
            } else {
                $tw = $th * $ratio;
            }
            $mpdf->Image($portadaImgSmall, $thumbX + ($prodThumbW - $tw) / 2, $thumbY + ($prodThumbH - $th) / 2, $tw, $th);
        } else {
            $mpdf->Image($portadaImgSmall, $thumbX, $thumbY, $prodThumbW, $prodThumbH);
        }
    }

    // Área central: una sola imagen del producto/servicio (contain, centrada)
    $prodImgAreaW = $wMm - 2 * $prodImgMarginH;
    $prodImgAreaH = $hMm - $prodImgMarginTop - $prodImgMarginBottom;
    $prodImgAreaX = $prodImgMarginH;
    $prodImgAreaY = $prodImgMarginTop;
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
            $ix = $prodImgAreaX + ($prodImgAreaW - $iw) / 2;
            $iy = $prodImgAreaY + ($prodImgAreaH - $ih) / 2;
            $mpdf->Image($imgPath, $ix, $iy, $iw, $ih);
        } else {
            $mpdf->Image($imgPath, $prodImgAreaX, $prodImgAreaY, $prodImgAreaW, $prodImgAreaH);
        }
    } else {
        $phPath = $slide2ImgPath ?? (file_exists($portadaImg2) ? $portadaImg2 : null);
        if ($phPath && file_exists($phPath)) {
            $imgSize = @getimagesize($phPath);
            if (!empty($imgSize[0]) && !empty($imgSize[1])) {
                $ratio = $imgSize[0] / $imgSize[1];
                $iw = $prodImgAreaW;
                $ih = $prodImgAreaH;
                if ($ratio > $iw / $ih) {
                    $ih = $iw / $ratio;
                } else {
                    $iw = $ih * $ratio;
                }
                $ix = $prodImgAreaX + ($prodImgAreaW - $iw) / 2;
                $iy = $prodImgAreaY + ($prodImgAreaH - $ih) / 2;
                $mpdf->Image($phPath, $ix, $iy, $iw, $ih);
            } else {
                $mpdf->Image($phPath, $prodImgAreaX, $prodImgAreaY, $prodImgAreaW, $prodImgAreaH);
            }
        }
    }

    // Pill abajo derecha: Nombre del Producto + Sector / Rubro (azul #003399, texto blanco, alineado a la izquierda)
    $pillX = $wMm - $prodPillW - $prodPillRight;
    $pillY = $hMm - $prodPillH - $prodPillBottom;
    $pillR = $prodPillH / 2;
    $mpdf->SetFillColor(0, 51, 153);
    $mpdf->RoundedRect($pillX, $pillY, $prodPillW, $prodPillH, $pillR, 'F');
    $mpdf->SetTextColor(255, 255, 255);
    $mpdf->SetFont('dejavusans', 'B', 22);
    $mpdf->SetXY($pillX + $prodPillPad, $pillY + 6);
    $mpdf->Cell($prodPillW - 2 * $prodPillPad, 10, $prod['name'], 0, 1, 'L');
    $sectorText = (isset($prod['activity']) && $prod['activity'] !== '') ? $prod['activity'] : 'Sector / Rubro';
    $mpdf->SetFont('dejavusans', 'B', 14);
    $mpdf->SetXY($pillX + $prodPillPad, $pillY + 22);
    $mpdf->Cell($prodPillW - 2 * $prodPillPad, 8, $sectorText, 0, 1, 'L');
    $mpdf->SetTextColor(0, 0, 0);
}

// —— Slide: Ventajas competitivas (izq: bloque blanco + acento azul derecho + paisaje redondeado + miniatura; der: título + párrafo + 4 pills “Diferencial”) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$ventHeaderLeft = 24;
$ventHeaderTop = 14;
$ventCompanyName = $empresaNombre !== '' ? $empresaNombre : 'Nombre de la Empresa';
$mpdf->SetTextColor(160, 40, 45);
$mpdf->SetFont('dejavusans', 'B', 14);
$mpdf->SetXY($ventHeaderLeft, $ventHeaderTop);
$mpdf->Cell($wMm - $ventHeaderLeft - 20, 7, $ventCompanyName, 0, 1, 'L');

// Bloque izquierdo: imagen más pequeña; acento azul solo a la derecha de la imagen, sin invadir el área de texto
$ventColLeftW = round($wMm * 0.36);
$ventImgX = 24;
$ventImgY = 42;
$ventImgW = $ventColLeftW - $ventImgX - 8;
$ventImgH = $hMm - $ventImgY - 28;
$ventImgR = 20;
$ventImgMargin = 0;

// Contenedor blanco redondeado + imagen (sin acento azul)
$mpdf->SetFillColor(255, 255, 255);
$mpdf->RoundedRect($ventImgX, $ventImgY, $ventImgW, $ventImgH, $ventImgR, 'F');

// 3) Imagen de paisaje con esquinas redondeadas (GD)
$ventImgInnerX = $ventImgX + $ventImgMargin;
$ventImgInnerY = $ventImgY + $ventImgMargin;
$ventImgInnerW = $ventImgW - 2 * $ventImgMargin;
$ventImgInnerH = $ventImgH - 2 * $ventImgMargin;
$ventLandscapePath = file_exists($portadaImg2) ? $portadaImg2 : $portadaBgPath;
$ventRoundedPath = null;
if (extension_loaded('gd') && $ventLandscapePath && file_exists($ventLandscapePath)) {
    $vinfo = @getimagesize($ventLandscapePath);
    if ($vinfo && in_array($vinfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        $vsrc = $vinfo[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($ventLandscapePath) : @imagecreatefrompng($ventLandscapePath);
        if ($vsrc) {
            $vw = imagesx($vsrc);
            $vh = imagesy($vsrc);
            $vr = (int) min($vw, $vh) * 0.14;
            if ($vr < 18) $vr = 18;
            $vdst = imagecreatetruecolor($vw, $vh);
            if ($vdst) {
                $vwhite = imagecolorallocate($vdst, 255, 255, 255);
                imagefill($vdst, 0, 0, $vwhite);
                imagecopy($vdst, $vsrc, 0, 0, 0, 0, $vw, $vh);
                for ($vpy = 0; $vpy < $vr; $vpy++) {
                    for ($vpx = 0; $vpx < $vr; $vpx++) {
                        if (($vpx - $vr) * ($vpx - $vr) + ($vpy - $vr) * ($vpy - $vr) > $vr * $vr) {
                            imagesetpixel($vdst, $vpx, $vpy, $vwhite);
                        }
                    }
                }
                for ($vpy = 0; $vpy < $vr; $vpy++) {
                    for ($vpx = $vw - $vr; $vpx < $vw; $vpx++) {
                        if (($vpx - ($vw - $vr)) * ($vpx - ($vw - $vr)) + ($vpy - $vr) * ($vpy - $vr) > $vr * $vr) {
                            imagesetpixel($vdst, $vpx, $vpy, $vwhite);
                        }
                    }
                }
                for ($vpy = $vh - $vr; $vpy < $vh; $vpy++) {
                    for ($vpx = 0; $vpx < $vr; $vpx++) {
                        if (($vpx - $vr) * ($vpx - $vr) + ($vpy - ($vh - $vr)) * ($vpy - ($vh - $vr)) > $vr * $vr) {
                            imagesetpixel($vdst, $vpx, $vpy, $vwhite);
                        }
                    }
                }
                for ($vpy = $vh - $vr; $vpy < $vh; $vpy++) {
                    for ($vpx = $vw - $vr; $vpx < $vw; $vpx++) {
                        if (($vpx - ($vw - $vr)) * ($vpx - ($vw - $vr)) + ($vpy - ($vh - $vr)) * ($vpy - ($vh - $vr)) > $vr * $vr) {
                            imagesetpixel($vdst, $vpx, $vpy, $vwhite);
                        }
                    }
                }
                $ventRoundedPath = sys_get_temp_dir() . '/vent_round_' . uniqid() . '.png';
                if (imagepng($vdst, $ventRoundedPath)) {
                    // use below
                } else {
                    $ventRoundedPath = null;
                }
                imagedestroy($vdst);
            }
            imagedestroy($vsrc);
        }
    }
}
$ventBgToUse = ($ventRoundedPath !== null && file_exists($ventRoundedPath)) ? $ventRoundedPath : $ventLandscapePath;
if (file_exists($ventBgToUse)) {
    $mpdf->Image($ventBgToUse, $ventImgInnerX, $ventImgInnerY, $ventImgInnerW, $ventImgInnerH);
    if ($ventRoundedPath !== null && file_exists($ventRoundedPath)) {
        @unlink($ventRoundedPath);
    }
}
// Columna derecha: título + párrafo + 4 pills en diagonal
$ventRightX = $ventImgX + $ventImgW + 45;
$ventRightW = $wMm - $ventRightX - 24;
$ventTitleY = 38;
$mpdf->SetTextColor(0, 0, 0);
$mpdf->SetFont('dejavusans', 'B', 40);
$mpdf->SetXY($ventRightX, $ventTitleY);
$mpdf->Cell($ventRightW, 14, 'Ventajas competitivas', 0, 1, 'L');
$ventIntroY = $ventTitleY + 22;
$ventIntroText = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit.";
$mpdf->SetTextColor(51, 51, 51);
$mpdf->SetFont('dejavusans', '', 11);
$mpdf->SetXY($ventRightX, $ventIntroY);
$mpdf->MultiCell($ventRightW, 5.5, $ventIntroText, 0, 'L');

$ventItems = array_slice($ventajas, 0, 4);
if (empty($ventItems)) {
    $ventItems = ['Diferencial 1', 'Diferencial 2', 'Diferencial 3', 'Diferencial 4'];
} else {
    $ventItems = array_map(function ($v, $i) {
        return (trim((string) $v) !== '') ? $v : 'Diferencial ' . ($i + 1);
    }, $ventItems, array_keys($ventItems));
}
$ventPillH = 12;
$ventPillR = $ventPillH / 2;
$ventPillPadH = 14;
$ventPillPositions = [
    ['x' => 0,  'y' => 0],
    ['x' => 48, 'y' => 26],
    ['x' => 12, 'y' => 52],
    ['x' => 56, 'y' => 78],
];
$mpdf->SetFillColor(0, 51, 153);
$mpdf->SetTextColor(255, 255, 255);
$mpdf->SetFont('dejavusans', 'B', 16);
$ventPillStartY = $ventIntroY + 36;
foreach ($ventItems as $vi => $vtext) {
    $px = $ventRightX + $ventPillPositions[$vi]['x'];
    $py = $ventPillStartY + $ventPillPositions[$vi]['y'];
    $pw = min($ventRightW - $ventPillPositions[$vi]['x'], 85);
    $mpdf->RoundedRect($px, $py, $pw, $ventPillH, $ventPillR, 'F');
    $label = (mb_strlen($vtext) > 28) ? mb_substr($vtext, 0, 25) . '…' : $vtext;
    $mpdf->SetXY($px + 10, $py + 1.5);
    $mpdf->Cell($pw - 20, 9, $label, 0, 1, 'C');
}
$mpdf->SetTextColor(0, 0, 0);

// —— Slide: Certificaciones (como en diseño: pirula azul horizontal, 4 placeholders paisaje, título derecha, certs izq rojo + derecha en azul) ——
$certLandscapePath = file_exists($portadaImg2) ? $portadaImg2 : $portadaBgPath;
$certIconPath = $assetsDir . '/certificacion_icon.png';
if (!file_exists($certIconPath)) {
    $certIconPath = dirname(__DIR__) . '/oferta/assets/certificacion_icon.png';
}
$certIconWhitePath = $assetsDir . '/certificacion_white_icon.png';
if (!file_exists($certIconWhitePath)) {
    $certIconWhitePath = dirname(__DIR__) . '/oferta/assets/certificacion_white_icon.png';
}

$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$certHeaderTop = 14;
$certCompanyName = $empresaNombre !== '' ? $empresaNombre : 'Nombre de la Empresa';
$mpdf->SetTextColor(160, 40, 45);
$mpdf->SetFont('dejavusans', 'B', 14);
$mpdf->SetXY(0, $certHeaderTop);
$mpdf->Cell($wMm - 24, 7, $certCompanyName, 0, 1, 'R');

// Título "Certificaciones" alineado al borde derecho
$certTitleY = 32;
$certTitleW = 180;
$certTitleX = $wMm - 24 - $certTitleW;
$mpdf->SetTextColor(0, 0, 0);
$mpdf->SetFont('dejavusans', 'B', 36);
$mpdf->SetXY($certTitleX, $certTitleY);
$mpdf->Cell($certTitleW, 14, 'Certificaciones', 0, 1, 'R');

// Bloque azul como en slide 1: menos alto, más ancho, más bajo
$certPillY = 130;
$certPillH = 54;
$certPillW = round($wMm * 0.92);
$certPillRadius = 12;
$mpdf->SetFillColor(0, 51, 153);
$mpdf->RoundedRect(0, $certPillY, $certPillW, $certPillH, $certPillRadius, 'F');
$mpdf->Rect(0, $certPillY, $certPillRadius, $certPillH, 'F');

// Dos círculos en disposición diagonal: izquierda arriba, derecha abajo (como en скриншоте)
$certCirculePath = $assetsDir . '/img_circule.png';
if (!file_exists($certCirculePath)) {
    $certCirculePath = dirname(__DIR__) . '/oferta/assets/img_circule.png';
}
$cert_circleSize = 130;
$cert_circle1X = 40;
$cert_circle1Y = $certPillY - 110;
$cert_circle2X = $cert_circle1X + 120;
$cert_circle2Y = $cert_circle1Y + 60;
$cert_smallW = 36;
$cert_smallH = 24;
$cert_smallX = $wMm - $cert_smallW - 22;
$cert_smallY = $hMm - $cert_smallH - 22;

$certCirculeCircularPath = null;
if (file_exists($certCirculePath) && extension_loaded('gd')) {
    $srcImg = @imagecreatefromstring(file_get_contents($certCirculePath));
    if ($srcImg) {
        $size = (int) round($cert_circleSize * 2.9);
        if ($size < 100) $size = 100;
        $dst = imagecreatetruecolor($size, $size);
        if ($dst) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $trans);
            $sw = imagesx($srcImg);
            $sh = imagesy($srcImg);
            imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $size, $size, $sw, $sh);
            $cx = $size / 2;
            $r = $size / 2 - 1;
            for ($py = 0; $py < $size; $py++) {
                for ($px = 0; $px < $size; $px++) {
                    if (($px - $cx) * ($px - $cx) + ($py - $cx) * ($py - $cx) > $r * $r) {
                        imagesetpixel($dst, $px, $py, $trans);
                    }
                }
            }
            $tmpFile = sys_get_temp_dir() . '/cert_circule_' . uniqid() . '.png';
            if (imagepng($dst, $tmpFile)) {
                $certCirculeCircularPath = $tmpFile;
            }
            imagedestroy($dst);
        }
        imagedestroy($srcImg);
    }
}
$certCircleImage = ($certCirculeCircularPath !== null && file_exists($certCirculeCircularPath)) ? $certCirculeCircularPath : $certCirculePath;
$cert_contourWidth = 2.8;
$cert_contourColor = [255, 255, 255];
if (file_exists($certCircleImage)) {
    $mpdf->SetDrawColor($cert_contourColor[0], $cert_contourColor[1], $cert_contourColor[2]);
    $mpdf->SetLineWidth($cert_contourWidth);
    $mpdf->Image($certCircleImage, $cert_circle1X, $cert_circle1Y, $cert_circleSize, $cert_circleSize);
    $mpdf->Circle($cert_circle1X + $cert_circleSize / 2, $cert_circle1Y + $cert_circleSize / 2, $cert_circleSize / 2, 'S');
    $mpdf->Image($certCircleImage, $cert_circle2X, $cert_circle2Y, $cert_circleSize, $cert_circleSize);
    $mpdf->Circle($cert_circle2X + $cert_circleSize / 2, $cert_circle2Y + $cert_circleSize / 2, $cert_circleSize / 2, 'S');
    $mpdf->SetLineWidth(0.2);
    $mpdf->SetDrawColor(0, 0, 0);
    if ($certCirculeCircularPath !== null && file_exists($certCirculeCircularPath)) {
        @unlink($certCirculeCircularPath);
    }
}
if (file_exists($portadaImgSmall)) {
    $ciz = @getimagesize($portadaImgSmall);
    if (!empty($ciz[0]) && !empty($ciz[1])) {
        $ratio = $ciz[0] / $ciz[1];
        $tw = $cert_smallW;
        $th = $cert_smallH;
        if ($ratio > $tw / $th) { $th = $tw / $ratio; } else { $tw = $th * $ratio; }
        $mpdf->Image($portadaImgSmall, $cert_smallX + ($cert_smallW - $tw) / 2, $cert_smallY + ($cert_smallH - $th) / 2, $tw, $th);
    } else {
        $mpdf->Image($portadaImgSmall, $cert_smallX, $cert_smallY, $cert_smallW, $cert_smallH);
    }
}

// Certificaciones: izquierda (texto rojo + icono azul), derecha dentro de la pirula (texto blanco + icono blanco)
$certListTotal = array_slice($certificacionesList, 0, 4);
while (count($certListTotal) < 4) {
    $certListTotal[] = 'Certificación';
}
$certLeftX = 28;
$certLeftY = $cert_circle1Y + $cert_circleSize + 50;
$certIconSize = 14;
$certLineH = 20;
$certFontSize = 18;
$mpdf->SetFont('dejavusans', '', $certFontSize);
for ($i = 0; $i < 2; $i++) {
    $cy = $certLeftY + $i * $certLineH;
    if (file_exists($certIconPath)) {
        $mpdf->Image($certIconPath, $certLeftX, $cy, $certIconSize, $certIconSize);
    }
    $mpdf->SetTextColor(160, 40, 45);
    $mpdf->SetXY($certLeftX + $certIconSize + 5, $cy);
    $mpdf->Cell(120, 12, $certListTotal[$i] !== '' ? $certListTotal[$i] : 'Certificación', 0, 1, 'L');
}
$certRightMargin = 14;
$certRightX = $certPillW - $certRightMargin;
$certRightY = $certPillY + 10;
$certRightTextW = 72;
$certTextShiftLeft = 1;
$certRightTextEdge = $certRightX - $certTextShiftLeft;
$certIconGapRight = 5;
$certIconRight = file_exists($certIconWhitePath) ? $certIconWhitePath : $certIconPath;
$mpdf->SetTextColor(255, 255, 255);
for ($i = 2; $i < 4; $i++) {
    $cy = $certRightY + ($i - 2) * $certLineH;
    $iconX = $certRightTextEdge - $certRightTextW - $certIconSize - $certIconGapRight;
    if (file_exists($certIconRight)) {
        $mpdf->Image($certIconRight, $iconX, $cy, $certIconSize, $certIconSize);
    }
    $mpdf->SetXY($iconX + $certIconSize + $certIconGapRight, $cy);
    $mpdf->Cell($certRightTextW, 10, $certListTotal[$i] !== '' ? $certListTotal[$i] : 'Certificación', 0, 1, 'L');
}
$mpdf->SetTextColor(0, 0, 0);

// —— Slide: Mercados Objetivo (bloque azul izq con image 8/9, bloque azul der "Mercados Objetivo", 4 bloques Región/País con worldwide.png, miniatura abajo derecha) ——
$mercImg8 = $assetsDir . '/image 8.png';
$mercImg9 = $assetsDir . '/image 9.png';
$mercWorldwide = $assetsDir . '/worldwide.png';
if (!file_exists($mercWorldwide)) {
    $mercWorldwide = dirname(__DIR__) . '/oferta/assets/worldwide.png';
}

$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$mercHeaderTop = 14;
$mercCompanyName = $empresaNombre !== '' ? $empresaNombre : 'Nombre de la Empresa';
$mpdf->SetTextColor(160, 40, 45);
$mpdf->SetFont('dejavusans', 'B', 14);
$mpdf->SetXY(24, $mercHeaderTop);
$mpdf->Cell($wMm - 48, 7, $mercCompanyName, 0, 1, 'L');

// Bloque azul izquierdo: menor ancho, sin redondeo a la izquierda, pegado al borde izquierdo; iconos con proporción correcta
$mercLeftBlockW = 78;
$mercLeftBlockX = 0;
$mercLeftBlockY = 36;
$mercLeftBlockH = 125;
$mercLeftBlockR = 14;
$mpdf->SetFillColor(0, 51, 153);
$mpdf->RoundedRect($mercLeftBlockX, $mercLeftBlockY, $mercLeftBlockW, $mercLeftBlockH, $mercLeftBlockR, 'F');
$mpdf->Rect($mercLeftBlockX, $mercLeftBlockY, $mercLeftBlockR, $mercLeftBlockH, 'F');
$mercLeftIconMax = 32;
$mercLeftIcon1Y = $mercLeftBlockY + ($mercLeftBlockH - 2 * $mercLeftIconMax - 20) / 2;
$mercLeftIcon2Y = $mercLeftIcon1Y + $mercLeftIconMax + 20;
$mercLeftIconX = $mercLeftBlockX + ($mercLeftBlockW - $mercLeftIconMax) / 2;
if (file_exists($mercImg8)) {
    $m8 = @getimagesize($mercImg8);
    if (!empty($m8[0]) && !empty($m8[1])) {
        $r8 = $m8[0] / $m8[1];
        $w8 = $mercLeftIconMax;
        $h8 = $mercLeftIconMax;
        if ($r8 > 1) { $h8 = $w8 / $r8; } else { $w8 = $h8 * $r8; }
        $mpdf->Image($mercImg8, $mercLeftIconX + ($mercLeftIconMax - $w8) / 2, $mercLeftIcon1Y, $w8, $h8);
    } else {
        $mpdf->Image($mercImg8, $mercLeftIconX, $mercLeftIcon1Y, $mercLeftIconMax, $mercLeftIconMax);
    }
}
if (file_exists($mercImg9)) {
    $m9 = @getimagesize($mercImg9);
    if (!empty($m9[0]) && !empty($m9[1])) {
        $r9 = $m9[0] / $m9[1];
        $w9 = $mercLeftIconMax;
        $h9 = $mercLeftIconMax;
        if ($r9 > 1) { $h9 = $w9 / $r9; } else { $w9 = $h9 * $r9; }
        $mpdf->Image($mercImg9, $mercLeftIconX + ($mercLeftIconMax - $w9) / 2, $mercLeftIcon2Y, $w9, $h9);
    } else {
        $mpdf->Image($mercImg9, $mercLeftIconX, $mercLeftIcon2Y, $mercLeftIconMax, $mercLeftIconMax);
    }
}

// Bloque azul derecho "Mercados Objetivo"
$mercTitleBlockX = $wMm - 175;
$mercTitleBlockY = 88;
$mercTitleBlockW = 158;
$mercTitleBlockH = 52;
$mercTitleBlockR = 14;
$mpdf->SetFillColor(0, 51, 153);
$mpdf->RoundedRect($mercTitleBlockX, $mercTitleBlockY, $mercTitleBlockW, $mercTitleBlockH, $mercTitleBlockR, 'F');
$mpdf->SetTextColor(255, 255, 255);
$mpdf->SetFont('dejavusans', 'B', 28);
$mpdf->SetXY($mercTitleBlockX + 16, $mercTitleBlockY + 8);
$mpdf->Cell($mercTitleBlockW - 32, 12, 'Mercados', 0, 1, 'L');
$mpdf->SetXY($mercTitleBlockX + 16, $mercTitleBlockY + 24);
$mpdf->Cell($mercTitleBlockW - 32, 12, 'Objetivo', 0, 1, 'L');

// Imagen central img_slide2.png
$mercCenterImgW = 250;
$mercCenterImgH = 150;
$mercCenterImgX = ($wMm - $mercCenterImgW) / 2 - 40;
$mercCenterImgY = ($hMm - $mercCenterImgH) / 2 - 32;
if (file_exists($portadaImg2)) {
    $mci = @getimagesize($portadaImg2);
    if (!empty($mci[0]) && !empty($mci[1])) {
        $mcr = $mci[0] / $mci[1];
        $mcw = $mercCenterImgW;
        $mch = $mercCenterImgH;
        if ($mcr > $mcw / $mch) { $mch = $mcw / $mcr; } else { $mcw = $mch * $mcr; }
        $mpdf->Image($portadaImg2, $mercCenterImgX + ($mercCenterImgW - $mcw) / 2, $mercCenterImgY + ($mercCenterImgH - $mch) / 2, $mcw, $mch);
    } else {
        $mpdf->Image($portadaImg2, $mercCenterImgX, $mercCenterImgY, $mercCenterImgW, $mercCenterImgH);
    }
}

// Cuatro bloques Región/País: dos arriba-derecha, dos bajo el bloque azul izq, pegados al borde inferior (uno un poco más alto)
$mercMarkets = array_slice(array_pad($targetMarkets, 4, ''), 0, 4);
$mercGlobeSize = 14;
$mercRegGap = 5;
$mercRegTitle = 'Región/País';
$mercRegSlots = [
    ['x' => 295, 'y' => 48],
    ['x' => 295, 'y' => 158],
    ['x' => 145, 'y' => $hMm - 68],
    ['x' => 22, 'y' => $hMm - 42],
];
$mercTitleColor = [0, 31, 96];
$mercTextColor = [0, 0, 0];
$mpdf->SetFont('dejavusans', '', 10);
foreach ($mercRegSlots as $idx => $slot) {
    $rx = $slot['x'];
    $ry = $slot['y'];
    if (file_exists($mercWorldwide)) {
        $mpdf->Image($mercWorldwide, $rx, $ry, $mercGlobeSize, $mercGlobeSize);
    }
    $mpdf->SetTextColor($mercTitleColor[0], $mercTitleColor[1], $mercTitleColor[2]);
    $mpdf->SetFont('dejavusans', 'B', 12);
    $mpdf->SetXY($rx + $mercGlobeSize + $mercRegGap, $ry);
    $mpdf->Cell(70, 6, $mercRegTitle, 0, 1, 'L');
    $mpdf->SetTextColor($mercTextColor[0], $mercTextColor[1], $mercTextColor[2]);
    $mpdf->SetFont('dejavusans', '', 9);
    $bulletText = isset($mercMarkets[$idx]) && $mercMarkets[$idx] !== '' ? $mercMarkets[$idx] : 'Lorem ipsum dolor.';
    for ($b = 0; $b < 3; $b++) {
        $mpdf->SetXY($rx + $mercGlobeSize + $mercRegGap, $ry + 8 + $b * 6);
        $mpdf->Cell(75, 5, '• ' . $bulletText, 0, 1, 'L');
    }
}

// Miniatura paisaje abajo a la derecha
$mercSmallW = 36;
$mercSmallH = 24;
$mercSmallX = $wMm - $mercSmallW - 20;
$mercSmallY = $hMm - $mercSmallH - 20;
if (file_exists($portadaImgSmall)) {
    $msiz = @getimagesize($portadaImgSmall);
    if (!empty($msiz[0]) && !empty($msiz[1])) {
        $mrat = $msiz[0] / $msiz[1];
        $mw = $mercSmallW;
        $mh = $mercSmallH;
        if ($mrat > $mw / $mh) { $mh = $mw / $mrat; } else { $mw = $mh * $mrat; }
        $mpdf->Image($portadaImgSmall, $mercSmallX + ($mercSmallW - $mw) / 2, $mercSmallY + ($mercSmallH - $mh) / 2, $mw, $mh);
    } else {
        $mpdf->Image($portadaImgSmall, $mercSmallX, $mercSmallY, $mercSmallW, $mercSmallH);
    }
}
$mpdf->SetTextColor(0, 0, 0);

// —— Slide final: Contacto (como en diseño: izq título + datos contacto + miniatura; der formas + barra azul vertical) ——
$mpdf->AddPage();
$mpdf->SetXY(0, 0);
$mpdf->SetFillColor(255, 255, 255);
$mpdf->Rect(0, 0, $wMm, $hMm, 'F');

$contColLeftW = round($wMm * 0.38);
$contColRightX = $contColLeftW + 10;
$contRightMargin = 22; // margen para que la imagen derecha no toque el borde
$contColRightW = $wMm - $contColRightX - $contRightMargin;
$contPadLeft = 22;
$contPadTop = 20;
$contHeaderColor = [0, 51, 153];   // azul "Nombre de la Empresa"
$contTitleColor = [0, 0, 0];       // negro "Contacto"
$contDataColor = [0, 51, 153];     // azul datos contacto
$contBarBlue = [0, 51, 153];       // barra vertical
$contShapeLight = [173, 216, 230]; // celeste forma superior
$contShapeGreen = [85, 107, 47];   // verde oliva forma inferior

// Encabezado: "Nombre de la Empresa" pequeño, azul, arriba a la izquierda
$contCompanyName = $empresaNombre !== '' ? $empresaNombre : 'Nombre de la Empresa';
$mpdf->SetTextColor($contHeaderColor[0], $contHeaderColor[1], $contHeaderColor[2]);
$mpdf->SetFont('dejavusans', 'B', 11);
$mpdf->SetXY($contPadLeft, $contPadTop);
$mpdf->Cell($contColLeftW - $contPadLeft - 5, 6, $contCompanyName, 0, 1, 'L');

// Título "Contacto" más grande, negro (más abajo)
$contTitleY = $contPadTop + 45;
$mpdf->SetTextColor($contTitleColor[0], $contTitleColor[1], $contTitleColor[2]);
$mpdf->SetFont('dejavusans', 'B', 40);
$mpdf->SetXY($contPadLeft, $contTitleY);
$mpdf->Cell($contColLeftW - $contPadLeft - 5, 16, 'Contacto', 0, 1, 'L');

// Cuatro líneas de contacto en azul, más abajo y fuente más grande
$contDataY = $contTitleY + 74;
$contDataFont = 20;
$contLineH = 13;
$mpdf->SetTextColor($contDataColor[0], $contDataColor[1], $contDataColor[2]);
$mpdf->SetFont('dejavusans', '', $contDataFont);
$contPhone = $contactPhone !== '' ? $contactPhone : '+123-456-7890';
$contWeb = $companyWebsite !== '' ? $companyWebsite : 'www.reallygreatsite.com';
$contEmail = $contactEmail !== '' ? $contactEmail : 'hello@reallygreatsite.com';
$contAddr = trim($addressLine . ($addressLine !== '' && $locality !== '' ? ', ' : '') . $locality);
if ($contAddr === '') {
    $contAddr = '123 Anywhere ST., Any City, ST 12345';
}
$mpdf->SetXY($contPadLeft, $contDataY);
$mpdf->Cell($contColLeftW - $contPadLeft - 5, $contLineH, $contPhone, 0, 1, 'L');
$mpdf->SetXY($contPadLeft, $contDataY + $contLineH);
$mpdf->Cell($contColLeftW - $contPadLeft - 5, $contLineH, $contWeb, 0, 1, 'L');
$mpdf->SetXY($contPadLeft, $contDataY + 2 * $contLineH);
$mpdf->Cell($contColLeftW - $contPadLeft - 5, $contLineH, $contEmail, 0, 1, 'L');
$mpdf->SetXY($contPadLeft, $contDataY + 3 * $contLineH);
$mpdf->Cell($contColLeftW - $contPadLeft - 5, $contLineH, $contAddr, 0, 1, 'L');

// Miniatura paisaje abajo a la izquierda
$contThumbW = 70;
$contThumbH = 46;
$contThumbX = $contPadLeft;
$contThumbY = $hMm - $contThumbH - 22;
$contThumbImg = file_exists($portadaImgSmall) ? $portadaImgSmall : $portadaBg;
if ($contThumbImg !== null && file_exists($contThumbImg)) {
    $cts = @getimagesize($contThumbImg);
    if (!empty($cts[0]) && !empty($cts[1])) {
        $ctr = $cts[0] / $cts[1];
        $cw = $contThumbW;
        $ch = $contThumbH;
        if ($ctr > $cw / $ch) { $ch = $cw / $ctr; } else { $cw = $ch * $ctr; }
        $mpdf->Image($contThumbImg, $contThumbX + ($contThumbW - $cw) / 2, $contThumbY + ($contThumbH - $ch) / 2, $cw, $ch);
    } else {
        $mpdf->Image($contThumbImg, $contThumbX, $contThumbY, $contThumbW, $contThumbH);
    }
}

// —— Derecha: dos imágenes con la barra azul vertical entre ellas (imagen | pill | imagen) ——
$contBarW = 28;
$contBarH = $hMm - 36;
$contBarY = 18;
$contGap = 10;
$contImgW = (int) (($contColRightW - $contBarW - 2 * $contGap) / 2);
$contImgH = $contBarH;
$contImgLeftX = $contColRightX;
$contBarX = $contColRightX + $contImgW + $contGap;
$contImgRightX = $contBarX + $contBarW + $contGap;
$contBarR = 5;

// Barra azul vertical con esquinas redondeadas
$mpdf->SetFillColor($contBarBlue[0], $contBarBlue[1], $contBarBlue[2]);
$mpdf->RoundedRect($contBarX, $contBarY, $contBarW, $contBarH, $contBarR, 'F');

// Texto en la pill girado 90°: dibujar con GD como imagen rotada (mPDF no rota bien el texto)
$contBarCharW = 5.5;
$contBarCharH = 8;
$contPillFontPath = dirname(dirname(dirname(__DIR__))) . '/vendor/mpdf/mpdf/ttfonts/DejaVuSans-Bold.ttf';
if (!file_exists($contPillFontPath)) {
    $contPillFontPath = __DIR__ . '/../../../vendor/mpdf/mpdf/ttfonts/DejaVuSans-Bold.ttf';
}
$contPillPxPerMm = 3.78;
$contPillCharPx = (int) round(max($contBarCharW, $contBarCharH) * $contPillPxPerMm);
$contPillFontSize = (int) round($contPillCharPx * 0.92); // más grande y ya Bold (DejaVuSans-Bold)
$contNameLen = mb_strlen($contCompanyName);
$contGraciasLen = mb_strlen('Gracias');
// Margenes grandes: texto bien dentro del rectángulo para que no se recorte en ningún viewer
$contPillMarginTop = 18;
$contPillMarginBottom = 30;
// Texto más cerca del borde izquierdo de la pill
$contNameStartX = $contBarX + 5;
// Nombre empresa: justo debajo del borde superior de la pill (top del bloque = contBarY + margin)
$contNameStartY = $contBarY + $contPillMarginTop + ($contNameLen > 0 ? ($contNameLen - 1) * $contBarCharW : 0);
// Gracias: justo encima del borde inferior de la pill (bottom del bloque = contBarY + contBarH - margin)
$contGraciasStartY = $contBarY + $contBarH - $contPillMarginBottom - $contBarCharW;
$contPillTextWhite = [255, 255, 255];
$contPillDrawChar = function ($ch) use ($contPillFontPath, $contPillCharPx, $contPillFontSize, $contPillTextWhite, $contBarBlue) {
    if (!function_exists('imagettftext') || !file_exists($contPillFontPath)) return null;
    $sz = $contPillCharPx + 32;
    $img = imagecreatetruecolor($sz, $sz);
    if (!$img) return null;
    $bg = imagecolorallocate($img, $contBarBlue[0], $contBarBlue[1], $contBarBlue[2]);
    imagefill($img, 0, 0, $bg);
    $white = imagecolorallocate($img, $contPillTextWhite[0], $contPillTextWhite[1], $contPillTextWhite[2]);
    $fs = (int) round($contPillFontSize * 0.85);
    $box = imagettfbbox($fs, 0, $contPillFontPath, $ch);
    if (!$box) { imagedestroy($img); return null; }
    $x = (int) (($sz - ($box[2] - $box[0])) / 2 - $box[0]);
    $y = (int) (($sz + ($box[1] - $box[7])) / 2 - $box[7]);
    imagettftext($img, $fs, 0, $x, $y, $white, $contPillFontPath, $ch);
    $rot = imagerotate($img, 90, $bg);
    imagedestroy($img);
    if (!$rot) return null;
    $tmp = sys_get_temp_dir() . '/pill_ch_' . uniqid() . '.png';
    imagepng($rot, $tmp);
    imagedestroy($rot);
    return $tmp;
};
for ($i = 0; $i < $contNameLen; $i++) {
    $ch = mb_substr($contCompanyName, $i, 1);
    $cy = $contNameStartY - $i * $contBarCharW;
    $tmpPath = $contPillDrawChar($ch);
    if ($tmpPath !== null && file_exists($tmpPath)) {
        $mpdf->Image($tmpPath, $contNameStartX, $cy, $contBarCharH + 0.5, $contBarCharW + 0.5);
        @unlink($tmpPath);
    } else {
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 18);
        $mpdf->SetXY($contNameStartX, $cy);
        $mpdf->Cell($contBarCharH, $contBarCharW, $ch, 0, 0, 'C');
    }
}
for ($i = 0; $i < $contGraciasLen; $i++) {
    $ch = mb_substr('Gracias', $i, 1);
    $cy = $contGraciasStartY - $i * $contBarCharW;
    $tmpPath = $contPillDrawChar($ch);
    if ($tmpPath !== null && file_exists($tmpPath)) {
        $mpdf->Image($tmpPath, $contNameStartX, $cy, $contBarCharH + 0.5, $contBarCharW + 0.5);
        @unlink($tmpPath);
    } else {
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetFont('dejavusans', 'B', 18);
        $mpdf->SetXY($contNameStartX, $cy);
        $mpdf->Cell($contBarCharH, $contBarCharW, $ch, 0, 0, 'C');
    }
}

// Dos imágenes enteras: img_slide2 a la izquierda y a la derecha de la pill (escala proporcional, centrada)
$contImgPath = file_exists($portadaImg2) ? $portadaImg2 : null;
if ($contImgPath !== null) {
    $contImgSize = @getimagesize($contImgPath);
    if (!empty($contImgSize[0]) && !empty($contImgSize[1])) {
        $contImgRatio = $contImgSize[0] / $contImgSize[1];
        $contBlockRatio = $contImgW / $contImgH;
        if ($contImgRatio > $contBlockRatio) {
            $contDrawW = $contImgW;
            $contDrawH = $contImgW / $contImgRatio;
        } else {
            $contDrawH = $contImgH;
            $contDrawW = $contImgH * $contImgRatio;
        }
        $contOffX = ($contImgW - $contDrawW) / 2;
        $contOffY = ($contImgH - $contDrawH) / 2;
        $mpdf->Image($contImgPath, $contImgLeftX + $contOffX, $contBarY + $contOffY, $contDrawW, $contDrawH);
        $mpdf->Image($contImgPath, $contImgRightX + $contOffX, $contBarY + $contOffY, $contDrawW, $contDrawH);
    } else {
        $mpdf->Image($contImgPath, $contImgLeftX, $contBarY, $contImgW, $contImgH);
        $mpdf->Image($contImgPath, $contImgRightX, $contBarY, $contImgW, $contImgH);
    }
} else {
    $mpdf->SetFillColor($contShapeLight[0], $contShapeLight[1], $contShapeLight[2]);
    $mpdf->RoundedRect($contImgLeftX, $contBarY, $contImgW, $contImgH, 12, 'F');
    $mpdf->SetFillColor($contShapeGreen[0], $contShapeGreen[1], $contShapeGreen[2]);
    $mpdf->RoundedRect($contImgRightX, $contBarY, $contImgW, $contImgH, 12, 'F');
}

$mpdf->SetTextColor(0, 0, 0);

$filename = 'Presentacion_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $empresaNombre) . '_' . $ano . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo $mpdf->Output('', 'S');
exit;
