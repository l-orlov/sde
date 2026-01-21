<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include __DIR__ . '/includes/path_helper.php';
include getIncludesFilePath('functions.php');
DBconnect();

$basePath = getAdminBasePath();

if ( !$_SESSION['admid'] ) {
	header('Location: ' . $basePath . 'login.php');
    exit();
}

$page = isset($_REQUEST['page']) ? htmlspecialchars($_REQUEST['page']) : '';
if ($page =='logout' ) include __DIR__ . '/includes/logout.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin</title>
    <link rel="icon" type="image/png" href="img/icons/logo_icon.png">
    <link rel="shortcut icon" type="image/png" href="img/icons/logo_icon.png">
    <link rel="apple-touch-icon" href="img/icons/logo_icon.png">

    <!-- Custom fonts for this template-->
    <link href="<?= $basePath ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link rel="stylesheet" href="<?= $basePath ?>css/style.css?v=1.0.3">
    <link rel="stylesheet" href="<?= $basePath ?>css/styleA.css?v=1.0.3">
</head>

<body id="page-top">

<div class="debug" id="debug"></div>
<!-- Page Wrapper -->
<div id="wrapper">


<? include 'includes/nav.php'; ?> 
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->

            <div id="content">
                <!-- Topbar -->
                <?php include 'includes/topbar.php'; ?>
                <!-- End of Topbar -->

<?
SWITCH ( $page ) {
    case 'users':               include __DIR__ . '/includes/users.php';                  break;
    default:                    include __DIR__ . '/includes/users.php';                    break;
}
?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="<?= $basePath ?>vendor/jquery/jquery.min.js"></script>
    <script src="<?= $basePath ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="<?= $basePath ?>vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?= $basePath ?>js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="<?= $basePath ?>vendor/chart.js/Chart.min.js"></script>

</body>

</html>
