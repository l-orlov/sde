<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/functions.php';

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
    $stmt = mysqli_prepare($link, "SELECT current_markets, target_markets, differentiation_factors, needs, competitiveness, logistics, expectations FROM company_data WHERE company_id = ? LIMIT 1");
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

// Build {{DATOS_EMPRESA_INPUT}} per requirements (Fuente de datos)
$parts = [];
$parts[] = 'Nombre de la empresa: ' . (isset($company['name']) && $company['name'] !== '' ? $company['name'] : '(no indicado)');
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
    $parts[] = 'Historia / diferencial: ' . mb_substr($company['nuestra_historia'], 0, 600);
}

$jsonFields = [
    'current_markets' => 'Mercados actuales',
    'target_markets' => 'Mercados de interés',
    'differentiation_factors' => 'Factores de diferenciación',
    'needs' => 'Necesidades',
    'competitiveness' => 'Competitividad',
    'logistics' => 'Logística',
    'expectations' => 'Expectativas',
];
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

$exportExperience = [];
foreach ($products as $p) {
    if (!empty($p['annual_export'])) {
        $exportExperience[] = $p['annual_export'];
    }
}
if (!empty($exportExperience)) {
    $parts[] = 'Experiencia exportadora: ' . implode('; ', array_slice(array_unique($exportExperience), 0, 5));
}

$parts[] = '';
$parts[] = 'Productos o servicios:';
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
        $parts[] = '   Exportación anual / experiencia: ' . $p['annual_export'];
    }
    if (!empty($p['certifications'])) {
        $parts[] = '   Certificaciones: ' . mb_substr($p['certifications'], 0, 200);
    }
}

$datosEmpresaInput = implode("\n", $parts);

$prompt = "Actúa como un Consultor Senior de Comercio Exterior especializado en la oferta productiva y exportable de Santiago del Estero, Argentina.\n\n"
. "Tu misión es analizar la ficha de empresa proporcionada y generar una estrategia de internacionalización coherente, específica y accionable, orientada a identificar oportunidades reales de inserción en mercados externos.\n\n"
. "Reglas de ejecución:\n"
. "- Identifica el sector de la empresa analizada (agroindustria, alimentos, industria manufacturera, servicios, tecnología, turismo u otro) y adapta completamente el vocabulario técnico y las recomendaciones a esa actividad. No utilices conceptos, analogías ni sugerencias de sectores ajenos al de la empresa.\n"
. "- Basa el análisis exclusivamente en los datos incluidos a continuación. No inventes productos, certificaciones, capacidades exportadoras, mercados actuales, ventajas competitivas, experiencia internacional ni canales comerciales no sustentados por la ficha. Cuando falte información clave, indícalo de forma explícita.\n"
. "- Si la empresa comercializa bienes físicos, puedes incluir sugerencias sobre empaque, etiquetado, certificaciones, requisitos sanitarios o técnicos y logística. Si la empresa presta servicios, no menciones empaque ni logística física; enfoca en compliance, capacidades técnicas, localización idiomática, documentación comercial, certificaciones y modalidad de exportación del servicio.\n"
. "- Puedes incorporar ventajas competitivas de Santiago del Estero (clima, suelo, ubicación, perfil agroindustrial, sostenibilidad) sólo cuando sean pertinentes para el producto o servicio analizado.\n\n"
. "Formato de respuesta: Responde solo en TEXTO PLANO. No uses Markdown (sin **, #, ---). Usa saltos de línea y títulos en MAYÚSCULAS seguidos de dos puntos para estructurar.\n\n"
. "Estructura obligatoria de tu respuesta (incluye todas las secciones):\n\n"
. "1. PERFIL EXPORTABLE DE LA EMPRESA\nBreve síntesis de la empresa, su oferta principal y su potencial de internacionalización según los datos cargados.\n\n"
. "2. ANÁLISIS DE MERCADOS RECOMENDADOS\nSugiere exactamente 3 países o regiones con potencial comercial real. Para cada uno indica: Mercado recomendado; Nivel de prioridad; Oportunidad detectada; Justificación estratégica; Canal o modalidad de ingreso sugerida.\n\n"
. "3. JUSTIFICACIÓN ESTRATÉGICA GENERAL\nExplicación global de por qué esos mercados resultan convenientes para la empresa analizada.\n\n"
. "4. SUGERENCIAS DE ADAPTACIÓN\nRequisitos o ajustes recomendados según corresponda al tipo de oferta: empaque, etiquetado, idioma, certificaciones, habilitaciones, documentación comercial, compliance, presentación comercial (solo los pertinentes al sector).\n\n"
. "5. RECOMENDACIONES PARA LA VENTA EN MERCADOS EXTERNOS\nPor qué mercado conviene comenzar; pasos iniciales sugeridos; tipo de cliente objetivo a buscar; materiales, validaciones o requisitos a preparar previamente.\n\n"
. "Ficha de empresa (datos exclusivos para el análisis):\n\n"
. $datosEmpresaInput;

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 4096,
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
