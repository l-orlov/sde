<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

include __DIR__ . '/functions.php';

DBconnect();

$out = ['ok' => 0, 'text' => '', 'error' => ''];

if (!isset($_SESSION['uid'])) {
    $out['error'] = 'No autorizado. Inicie sesión.';
    echo json_encode($out);
    exit;
}

$userId = (int) $_SESSION['uid'];
if ($userId <= 0) {
    $out['error'] = 'Sesión inválida.';
    echo json_encode($out);
    exit;
}

$configPath = __DIR__ . '/config/config.php';
if (!is_file($configPath)) {
    $out['error'] = 'Configuración no disponible.';
    echo json_encode($out);
    exit;
}
$config = require $configPath;
$apiKey = isset($config['gemini_api_key']) ? trim((string) $config['gemini_api_key']) : '';
if ($apiKey === '') {
    $out['error'] = 'API de recomendaciones no configurada.';
    echo json_encode($out);
    exit;
}

global $link;

// Company: name, main_activity, website, nuestra_historia, organization_type
$company = [];
$stmt = mysqli_prepare($link, "SELECT COALESCE(c.name, u.company_name) AS name, c.main_activity, c.website, c.nuestra_historia, c.organization_type
    FROM users u
    LEFT JOIN companies c ON c.user_id = u.id
    WHERE u.id = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $company = mysqli_fetch_assoc($res);
    }
    mysqli_stmt_close($stmt);
}

$companyId = null;
$stmt = mysqli_prepare($link, "SELECT id FROM companies WHERE user_id = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $companyId = (int) $row['id'];
    }
    mysqli_stmt_close($stmt);
}

$companyData = [];
if ($companyId) {
    $stmt = mysqli_prepare($link, "SELECT current_markets, target_markets, differentiation_factors FROM company_data WHERE company_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && mysqli_num_rows($res) > 0) {
            $companyData = mysqli_fetch_assoc($res);
        }
        mysqli_stmt_close($stmt);
    }
}

$products = [];
$stmt = mysqli_prepare($link, "SELECT name, description, type, activity, tariff_code, annual_export, certifications
    FROM products WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = 0)
    ORDER BY is_main DESC, id ASC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $products[] = $row;
    }
    mysqli_stmt_close($stmt);
}

if (empty($products)) {
    $out['error'] = 'Agregue al menos un producto o servicio para obtener recomendaciones.';
    echo json_encode($out);
    exit;
}

$parts = [];

$parts[] = 'EMPRESA:';
$parts[] = 'Nombre: ' . (isset($company['name']) && $company['name'] !== '' ? $company['name'] : '(no indicado)');
if (!empty($company['main_activity'])) {
    $parts[] = 'Actividad principal: ' . $company['main_activity'];
}
if (!empty($company['organization_type'])) {
    $parts[] = 'Tipo de organización: ' . $company['organization_type'];
}
if (!empty($company['website'])) {
    $parts[] = 'Sitio web: ' . $company['website'];
}
if (!empty($company['nuestra_historia'])) {
    $parts[] = 'Historia / descripción: ' . mb_substr($company['nuestra_historia'], 0, 600);
}

$jsonFields = ['current_markets' => 'Mercados actuales', 'target_markets' => 'Mercados objetivo', 'differentiation_factors' => 'Factores de diferenciación'];
foreach ($jsonFields as $key => $label) {
    if (!empty($companyData[$key])) {
        $dec = json_decode($companyData[$key], true);
        if (is_array($dec)) {
            $parts[] = $label . ': ' . implode(', ', array_slice($dec, 0, 15));
        } else {
            $parts[] = $label . ': ' . mb_substr($companyData[$key], 0, 300);
        }
    }
}

$parts[] = '';
$parts[] = 'PRODUCTOS Y SERVICIOS:';
foreach ($products as $i => $p) {
    $type = (isset($p['type']) && $p['type'] === 'service') ? 'Servicio' : 'Producto';
    $parts[] = ($i + 1) . '. [' . $type . '] ' . (isset($p['name']) ? $p['name'] : '');
    if (!empty($p['activity'])) {
        $parts[] = '   Actividad: ' . $p['activity'];
    }
    if (!empty($p['description'])) {
        $parts[] = '   Descripción: ' . mb_substr($p['description'], 0, 400);
    }
    if (!empty($p['tariff_code'])) {
        $parts[] = '   Código arancelario: ' . $p['tariff_code'];
    }
    if (!empty($p['annual_export'])) {
        $parts[] = '   Exportación anual: ' . $p['annual_export'];
    }
    if (!empty($p['certifications'])) {
        $parts[] = '   Certificaciones: ' . mb_substr($p['certifications'], 0, 200);
    }
}

$context = implode("\n", $parts);

$prompt = "Con base únicamente en los siguientes datos de una empresa y sus productos/servicios, responde en el mismo idioma (español o inglés según el usuario):\n\n"
. "¿En qué países del mundo sería más conveniente exportar o vender estos productos o servicios, y qué recomendaciones concretas darías (canales, requisitos, mercados prioritarios)? Responde de forma clara y estructurada (por ejemplo: países recomendados, breve justificación, y 2-4 consejos prácticos).\n\n"
. "Datos:\n" . $context;

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 2048,
    ]
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . urlencode($apiKey);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr !== '') {
    $out['error'] = 'Error de conexión con el servicio: ' . $curlErr;
    echo json_encode($out);
    exit;
}

if ($httpCode !== 200) {
    $out['error'] = 'El servicio no está disponible. Intente más tarde.';
    if ($response !== false && $response !== '') {
        $dec = json_decode($response, true);
        if (isset($dec['error']['message'])) {
            $out['error'] = $dec['error']['message'];
        }
    }
    echo json_encode($out);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    $out['error'] = 'Respuesta inválida del servicio.';
    echo json_encode($out);
    exit;
}

$text = '';
if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $text = trim($data['candidates'][0]['content']['parts'][0]['text']);
}

if ($text === '') {
    $out['error'] = 'No se obtuvo respuesta. Intente de nuevo.';
    echo json_encode($out);
    exit;
}

$out['ok'] = 1;
$out['text'] = $text;
echo json_encode($out);
