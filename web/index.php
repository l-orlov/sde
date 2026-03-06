<?
session_start();
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "includes/functions.php";

// Если запрос к статическому файлу (css, js, img и т.д.) попал в index.php — отдать файл и выйти (чтобы не возвращать HTML gate)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
if ($requestPath !== '' && $requestPath !== false && preg_match('/\.(css|js|json|ico|png|jpe?g|gif|svg|woff2?|ttf|eot)(\?|$)/i', $requestPath)) {
    $config = file_exists(__DIR__ . '/includes/config/config.php') ? (require __DIR__ . '/includes/config/config.php') : [];
    $web_base = rtrim($config['web_base'] ?? '', '/');
    $basePath = $requestPath;
    if ($web_base !== '' && strpos($basePath, $web_base) === 0) {
        $basePath = substr($basePath, strlen($web_base)) ?: '/';
    }
    $basePath = '/' . trim($basePath, '/');
    $localFile = __DIR__ . str_replace(['../', '..\\'], '', $basePath);
    $ext = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));
    $mimes = ['css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json', 'ico' => 'image/x-icon', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'eot' => 'application/vnd.ms-fontobject'];
    if (is_file($localFile) && isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext] . '; charset=utf-8');
        header('Content-Length: ' . filesize($localFile));
        readfile($localFile);
        exit;
    }
}

DBconnect();

$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';

// Заглушка: проверка логина до любого вывода (ответ должен быть только JSON)
if ($page === 'site_gate_check' && !isset($_SESSION['uid'])) {
    require __DIR__ . '/includes/site_gate_check.php';
    exit;
}
// Сброс gate (для теста): снова покажет форму входа при следующем заходе
if ($page === 'gate_logout' && !isset($_SESSION['uid'])) {
    unset($_SESSION['gate_remember'], $_SESSION['gate_one_time']);
    header('Location: index.php?page=landing');
    exit;
}
// Recomendaciones de mercados con IA (JSON)
if ($page === 'gemini_markets') {
    require __DIR__ . '/includes/gemini_markets_js.php';
    exit;
}
// Логин (JSON) — чтобы запрос шёл через index.php и возвращал JSON, а не HTML
if ($page === 'login_submit') {
    require __DIR__ . '/includes/login_js.php';
    exit;
}
// Обновление профиля (JSON)
if ($page === 'home_update_profile') {
    require __DIR__ . '/includes/home_update_profile_js.php';
    exit;
}

// Лендинг — без кэша, чтобы карусель при обновлении всегда подгружала актуальные данные из БД
if ($page === 'landing' || ($page === '' && !isset($_SESSION['uid']))) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
if ($page === 'download_oferta_pdf') {
    require __DIR__ . '/includes/serve_static_oferta_pdf.php';
    exit;
}
if ($page === 'search_api') {
    require __DIR__ . '/includes/search_api.php';
    exit;
}
if ($page === 'clasico_pdf' || $page === 'clasico_pdf_es') {
    require __DIR__ . '/pdf/oferta/clasico_pdf_es.php';
    exit;
}
if ($page === 'clasico_pdf_en') {
    require __DIR__ . '/pdf/oferta/clasico_pdf_en.php';
    exit;
}
if ($page === 'corporativo_pdf' || $page === 'corporativo_pdf_es') {
    require __DIR__ . '/pdf/oferta/corporativo_pdf_es.php';
    exit;
}
if ($page === 'corporativo_pdf_en') {
    require __DIR__ . '/pdf/oferta/corporativo_pdf_en.php';
    exit;
}
if ($page === 'moderno_pdf' || $page === 'moderno_pdf_es') {
    require __DIR__ . '/pdf/oferta/moderno_pdf_es.php';
    exit;
}
if ($page === 'moderno_pdf_en') {
    require __DIR__ . '/pdf/oferta/moderno_pdf_en.php';
    exit;
}
// Home PDFs (perfil empresa): solo si la empresa está aprobada; si está en moderación, redirigir con motivo
$homePdfPages = ['clasico_company', 'clasico_company_es', 'clasico_company_en', 'corporativo_company', 'corporativo_company_es', 'corporativo_company_en', 'moderno_company', 'moderno_company_es', 'moderno_company_en'];
if (in_array($page, $homePdfPages, true)) {
    $userId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
    if ($userId > 0) {
        $stmt = mysqli_prepare($link, "SELECT c.moderation_status FROM companies c WHERE c.user_id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res && mysqli_num_rows($res) > 0 ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            $modStatus = $row['moderation_status'] ?? 'pending';
            if ($modStatus !== 'approved') {
                header('Location: index.php?page=home&pdf_blocked=moderation');
                exit;
            }
        }
    }
}
if ($page === 'clasico_company' || $page === 'clasico_company_es') {
    require __DIR__ . '/pdf/home/clasico_company_es.php';
    exit;
}
if ($page === 'clasico_company_en') {
    require __DIR__ . '/pdf/home/clasico_company_en.php';
    exit;
}
if ($page === 'corporativo_company' || $page === 'corporativo_company_es') {
    require __DIR__ . '/pdf/home/corporativo_company_es.php';
    exit;
}
if ($page === 'corporativo_company_en') {
    require __DIR__ . '/pdf/home/corporativo_company_en.php';
    exit;
}
if ($page === 'moderno_company' || $page === 'moderno_company_es') {
    require __DIR__ . '/pdf/home/moderno_company_es.php';
    exit;
}
if ($page === 'moderno_company_en') {
    require __DIR__ . '/pdf/home/moderno_company_en.php';
    exit;
}

$__config = file_exists(__DIR__ . '/includes/config/config.php') ? (require __DIR__ . '/includes/config/config.php') : [];
$__web_base = rtrim($__config['web_base'] ?? '', '/');
$__asset_prefix = $__web_base ? $__web_base . '/' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sde</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($__asset_prefix) ?>css/style.css?v=<?= asset_version('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($__asset_prefix) ?>img/icons/logo_icon.png">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($__asset_prefix) ?>img/icons/logo_icon.png">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($__asset_prefix) ?>img/icons/logo_icon.png">
</head>
<body>

<?
$page = isset($_REQUEST['page']) ? htmlspecialchars($_REQUEST['page']) : '';
if ($page === 'logout') include 'includes/logout.php';

if ( !isset($_SESSION['uid']) ) {
    // Пропуск: либо «не спрашивать снова» (gate_remember), либо одноразовый (gate_one_time)
    $gate_allow = (isset($_SESSION['gate_remember']) && $_SESSION['gate_remember'])
        || (isset($_SESSION['gate_one_time']) && $_SESSION['gate_one_time']);
    if (!$gate_allow) {
        $gate_return_page = ($page !== '' && in_array($page, ['search', 'landing', 'regfull', 'regnew', 'login', 'reset_password'], true)) ? $page : 'landing';
        include __DIR__ . '/includes/site_gate.php';
        exit;
    }
    // Одноразовый пропуск использован — при следующем запросе снова gate (gate_remember не сбрасываем)
    if (isset($_SESSION['gate_one_time']) && $_SESSION['gate_one_time']) {
        $_SESSION['gate_one_time'] = false;
    }
    SWITCH ( $page ) {
        case 'search':           include "includes/search.php";             break;
        case 'landing':         include "includes/landing.php";             break;
        case 'regfull':			include "includes/regfull.php";             break;
        case 'regnew':			include "includes/regnew.php";              break;
        case 'login':			include "includes/login.php";               break;
        case 'reset_password':	include "includes/reset_password.php";     break;
        default:                include "includes/landing.php";             break;
    }
} else {
    SWITCH ( $page ) {
        case 'regfull':			     include "includes/regfull.php";             break;
        case 'home':                 include "includes/home.php";                break;
        default:                     include "includes/home.php";                break;
    }
}
?>

</body>
</html>
