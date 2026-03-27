<?php
header('Content-Type: application/json; charset=utf-8');
if (!isset($link)) {
    require __DIR__ . '/functions.php';
    DBconnect();
}
global $link;

$out = function ($suggestions = null, $items = null) {
    if ($suggestions !== null) {
        echo json_encode(['suggestions' => $suggestions]);
    } else {
        echo json_encode(['items' => $items !== null ? $items : []]);
    }
    exit;
};

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$suggest = isset($_GET['suggest']) && $_GET['suggest'] === '1';

if ($q === '') {
    $out($suggest ? [] : null, $suggest ? null : []);
}

if (!$link) {
    $out($suggest ? [] : null, $suggest ? null : []);
}

// Подсказки и результаты поиска — только товары/услуги компаний с moderation_status = 'approved'
$likeArg = '%' . $q . '%';

if ($suggest) {
    $stmt = @mysqli_prepare($link, "SELECT DISTINCT p.tariff_code
        FROM products p
        INNER JOIN companies c ON c.id = p.company_id AND c.user_id = p.user_id
        INNER JOIN users u ON u.id = c.user_id
        WHERE c.moderation_status = 'approved'
          AND u.include_in_business_exports = 1
          AND (p.deleted_at IS NULL OR p.deleted_at = 0)
          AND p.tariff_code IS NOT NULL AND p.tariff_code != ''
          AND (
            p.tariff_code LIKE ? OR p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR c.organization_type LIKE ? OR c.main_activity LIKE ?
            OR p.name_en LIKE ? OR p.description_en LIKE ? OR c.name_en LIKE ? OR c.organization_type_en LIKE ? OR c.main_activity_en LIKE ?
          )
        ORDER BY p.tariff_code
        LIMIT 10");
    if (!$stmt) {
        $out([]);
    }
    $like11 = array_fill(0, 11, $likeArg);
    mysqli_stmt_bind_param($stmt, str_repeat('s', 11), ...$like11);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $out([]);
    }
    $res = mysqli_stmt_get_result($stmt);
    $suggestions = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $code = $row['tariff_code'] ?? '';
            if ($code !== '') {
                $suggestions[] = ['value' => $code, 'label' => $code];
            }
        }
        mysqli_stmt_close($stmt);
    }
    $out($suggestions);
}

// Full search: producto, descripción, empresa, sector, industria, tariff_code (+ campos EN)
$stmt = @mysqli_prepare($link, "SELECT p.id, p.name, p.name_en, p.tariff_code, p.company_id, p.type, c.name AS company_name, c.name_en AS company_name_en, c.website
    FROM products p
    INNER JOIN companies c ON c.id = p.company_id AND c.user_id = p.user_id
    INNER JOIN users u ON u.id = c.user_id
    WHERE c.moderation_status = 'approved'
      AND u.include_in_business_exports = 1
      AND (p.deleted_at IS NULL OR p.deleted_at = 0)
      AND (
        p.tariff_code LIKE ?
        OR p.name LIKE ?
        OR p.description LIKE ?
        OR c.name LIKE ?
        OR c.organization_type LIKE ?
        OR c.main_activity LIKE ?
        OR p.name_en LIKE ?
        OR p.description_en LIKE ?
        OR c.name_en LIKE ?
        OR c.organization_type_en LIKE ?
        OR c.main_activity_en LIKE ?
      )
    ORDER BY p.tariff_code, p.id
    LIMIT 50");
if (!$stmt) {
    $out(null, []);
}
$like11b = array_fill(0, 11, $likeArg);
mysqli_stmt_bind_param($stmt, str_repeat('s', 11), ...$like11b);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    $out(null, []);
}
$res = mysqli_stmt_get_result($stmt);
if (!$res) {
    mysqli_stmt_close($stmt);
    $out(null, []);
}
$items = [];
$companyIds = [];
$productIds = [];
while ($row = mysqli_fetch_assoc($res)) {
    $productIds[] = (int) $row['id'];
    $companyIds[(int) $row['id']] = (int) $row['company_id'];
    $name = (string) ($row['name'] ?? '');
    $nameEn = trim((string) ($row['name_en'] ?? '')) !== '' ? (string) ($row['name_en'] ?? '') : $name;
    $coName = (string) ($row['company_name'] ?? '');
    $coNameEn = trim((string) ($row['company_name_en'] ?? '')) !== '' ? (string) ($row['company_name_en'] ?? '') : $coName;
    $items[] = [
        'id' => (int) $row['id'],
        'name' => $name,
        'name_en' => $nameEn,
        'tariff_code' => $row['tariff_code'] ?? '',
        'company_id' => (int) $row['company_id'],
        'type' => $row['type'] ?? 'product',
        'company_name' => $coName,
        'company_name_en' => $coNameEn,
        'website' => $row['website'] ?? '',
        'email' => '',
        'phone' => '',
        'locality' => '',
        'locality_en' => '',
        'image_url' => '',
        'ficha_url' => '',
    ];
}
mysqli_stmt_close($stmt);

$companyIdsUniq = array_values(array_unique(array_values($companyIds)));

// Contacts: first per company
$contactsByCompany = [];
if (!empty($companyIdsUniq)) {
    $placeholders = implode(',', array_fill(0, count($companyIdsUniq), '?'));
    $stmt = mysqli_prepare($link, "SELECT company_id, email, area_code, phone FROM company_contacts WHERE company_id IN ($placeholders) ORDER BY company_id, id ASC");
    if ($stmt) {
        $types = str_repeat('i', count($companyIdsUniq));
        mysqli_stmt_bind_param($stmt, $types, ...$companyIdsUniq);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($contactsByCompany[$cid])) {
                $contactsByCompany[$cid] = [
                    'email' => $row['email'] ?? '',
                    'phone' => trim(($row['area_code'] ?? '') . ' ' . ($row['phone'] ?? '')),
                ];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Addresses: first per company
$addressByCompany = [];
if (!empty($companyIdsUniq)) {
    $placeholders = implode(',', array_fill(0, count($companyIdsUniq), '?'));
    $stmt = mysqli_prepare($link, "SELECT company_id, locality, department, locality_en, department_en FROM company_addresses WHERE company_id IN ($placeholders) ORDER BY company_id, id ASC");
    if ($stmt) {
        $types = str_repeat('i', count($companyIdsUniq));
        mysqli_stmt_bind_param($stmt, $types, ...$companyIdsUniq);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($r)) {
            $cid = (int) $row['company_id'];
            if (!isset($addressByCompany[$cid])) {
                $locEs = trim((string) ($row['locality'] ?? '') . ' ' . (string) ($row['department'] ?? ''));
                if ($locEs === '') {
                    $locEs = 'Localidad/Departamento';
                }
                $locEnRaw = trim(trim((string) ($row['locality_en'] ?? '')) . ' ' . trim((string) ($row['department_en'] ?? '')));
                $locEn = $locEnRaw !== '' ? $locEnRaw : $locEs;
                $addressByCompany[$cid] = ['locality_es' => $locEs, 'locality_en' => $locEn];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// First image per product (product_photo or service_photo)
$imageByProduct = [];
if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $q = "SELECT f.id, f.product_id FROM files f
          INNER JOIN products p ON p.id = f.product_id
          INNER JOIN companies c ON c.id = p.company_id AND c.user_id = p.user_id
          INNER JOIN users u ON u.id = c.user_id
          WHERE f.product_id IN ($placeholders)
            AND f.file_type IN ('product_photo', 'product_photo_sec', 'service_photo')
            AND (f.is_temporary = 0 OR f.is_temporary IS NULL)
            AND c.moderation_status = 'approved'
            AND u.include_in_business_exports = 1
            AND (p.deleted_at IS NULL)
          ORDER BY f.product_id, f.id ASC";
    $stmt = mysqli_prepare($link, $q);
    if ($stmt) {
        $types = str_repeat('i', count($productIds));
        mysqli_stmt_bind_param($stmt, $types, ...$productIds);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($r)) {
            $pid = (int) $row['product_id'];
            if (!isset($imageByProduct[$pid])) {
                $imageByProduct[$pid] = get_serve_file_public_url((int) $row['id']);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

$config = file_exists(__DIR__ . '/config/config.php') ? (require __DIR__ . '/config/config.php') : [];
$webBase = rtrim($config['web_base'] ?? '', '/');

foreach ($items as &$item) {
    $cid = $item['company_id'];
    $item['email'] = $contactsByCompany[$cid]['email'] ?? '';
    $item['phone'] = $contactsByCompany[$cid]['phone'] ?? '';
    $addr = $addressByCompany[$cid] ?? null;
    if (is_array($addr)) {
        $item['locality'] = $addr['locality_es'];
        $item['locality_en'] = $addr['locality_en'];
    } else {
        $item['locality'] = 'Localidad/Departamento';
        $item['locality_en'] = $item['locality'];
    }
    $item['image_url'] = $imageByProduct[$item['id']] ?? '';
    $item['ficha_url'] = $webBase . '/index.php?page=landing#productos';
}
unset($item);

$out(null, $items);
