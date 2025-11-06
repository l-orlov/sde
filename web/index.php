<?
session_start();
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "includes/functions.php";
DBconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sde</title>
    <link rel="stylesheet" href="css/style.css?t=<?=time()?>">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?
$page = isset($_REQUEST['page']) ? htmlspecialchars($_REQUEST['page']) : '';
if ($page=='logout') include 'includes/logout.php';

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
        case 'home':                 include "includes/home.php";                break;
        default:                     include "includes/home.php";                break;
    }
}
?>

</body>
</html>
