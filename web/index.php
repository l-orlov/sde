<?
session_start();
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "includes/functions.php";
DBconnect();

$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
// Лендинг — без кэша, чтобы карусель при обновлении всегда подгружала актуальные данные из БД
if ($page === 'landing' || ($page === '' && !isset($_SESSION['uid']))) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sde</title>
    <link rel="stylesheet" href="css/style.css?v=<?= asset_version('css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/icons/logo_icon.png">
    <link rel="shortcut icon" type="image/png" href="img/icons/logo_icon.png">
    <link rel="apple-touch-icon" href="img/icons/logo_icon.png">
</head>
<body>

<?
$page = isset($_REQUEST['page']) ? htmlspecialchars($_REQUEST['page']) : '';
if ($page === 'logout') include 'includes/logout.php';

if ( !isset($_SESSION['uid']) ) {
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
