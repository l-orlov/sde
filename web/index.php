<?
session_start();
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "includes/functions.php";
DBconnect();

$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
if ($page === 'clasico_pdf') {
    require __DIR__ . '/pdf/oferta/clasico_pdf.php';
    exit;
}
if ($page === 'corporativo_pdf') {
    require __DIR__ . '/pdf/oferta/corporativo_pdf.php';
    exit;
}
if ($page === 'moderno_pdf') {
    require __DIR__ . '/pdf/oferta/moderno_pdf.php';
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
        case 'landing':         include "includes/landing.php";             break;
        case 'regfull':			include "includes/regfull.php";             break;
        case 'regnew':			include "includes/regnew.php";              break;
        case 'login':			include "includes/login.php";               break;
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
