<?php
set_time_limit(120);
error_reporting(E_ALL);

// Подключаем includes из web/includes/ (fill/ находится рядом с admin/)
$webDir = dirname(__DIR__);
$includesDir = $webDir . '/includes/';

require_once $includesDir . 'functions.php';
require_once $includesDir . 'gemini_translate_en.php';

DBconnect();
global $link;

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

// ----------------------------------------------------------------
// GET ?action=list
// Возвращает список компаний с незаполненными EN-полями
// ----------------------------------------------------------------
if ($action === 'list') {
    $result = mysqli_query($link,
        "SELECT c.id, c.name
         FROM companies c
         WHERE c.name_en IS NULL OR c.name_en = ''
            OR EXISTS (
                SELECT 1 FROM products p
                WHERE p.company_id = c.id
                  AND (p.deleted_at IS NULL OR p.deleted_at = 0)
                  AND (p.name_en IS NULL OR p.name_en = '')
            )
         ORDER BY c.id ASC"
    );

    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'DB query failed: ' . mysqli_error($link)]);
        exit;
    }

    $companies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $companies[] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }

    echo json_encode(['success' => true, 'companies' => $companies, 'total' => count($companies)]);
    exit;
}

// ----------------------------------------------------------------
// POST ?action=process  +  company_id=X
// Переводит данные одной компании через Gemini и сохраняет в БД
// ----------------------------------------------------------------
if ($action === 'process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;

    if (!$companyId) {
        echo json_encode(['success' => false, 'error' => 'Invalid company_id']);
        exit;
    }

    // Получаем имя компании для отображения в UI
    $res = mysqli_query($link, "SELECT name FROM companies WHERE id = " . $companyId);
    $company = $res ? mysqli_fetch_assoc($res) : null;
    if (!$company) {
        echo json_encode(['success' => false, 'error' => 'Company not found', 'company_id' => $companyId]);
        exit;
    }

    // Запускаем перевод (та же логика что при сохранении)
    refresh_company_products_en($link, $companyId);

    // Проверяем что поле действительно заполнилось
    $checkRes = mysqli_query($link, "SELECT name_en FROM companies WHERE id = " . $companyId);
    $checkRow = $checkRes ? mysqli_fetch_assoc($checkRes) : null;
    $filled = $checkRow && !empty(trim((string)($checkRow['name_en'] ?? '')));

    echo json_encode([
        'success'      => true,
        'filled'       => $filled,
        'company_id'   => $companyId,
        'company_name' => $company['name'],
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
