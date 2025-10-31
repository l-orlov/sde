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

SWITCH ( $page ) {
    case 'regfull':			include "includes/regfull.php";      break;
    case 'regnew':			include "includes/regnew.php";       break;
    default:			    include "includes/landing.php";
}
?>

</body>
</html>
