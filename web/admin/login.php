<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include __DIR__ . '/includes/path_helper.php';
$basePath = getAdminBasePath();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin</title>

    <!-- Custom fonts for this template-->
    <link href="<?= $basePath ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="<?= $basePath ?>css/style.css" rel="stylesheet">
</head>

<body class="bg-primary">
    <div class="container">
        <!-- Outer Row -->
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="text-center">
                                 <img class="pt-5" src="<?= $basePath ?>img/logo.png" style="max-width: 300px; width: 100%;">
                             </div>
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Bienvenido</h1>
                                    </div>
                                    <form class="user">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                                id="inputLogin" placeholder="Email o CUIL/CUIT">
                                        </div>

                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user"
                                                id="inputPassword" placeholder="Contraseña">
                                        </div>

                                        <div id="mensaje"></div>

										<div onclick="loginAdm()" class="btn btn-primary btn-user btn-block">
                                            Iniciar sesión
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="<?= $basePath ?>vendor/jquery/jquery.min.js"></script>
    <script src="<?= $basePath ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="<?= $basePath ?>vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?= $basePath ?>js/sb-admin-2.min.js"></script>
</body>
</html>

<script>
function loginAdm() {
    const login = document.getElementById('inputLogin').value.trim();
    const pass = document.getElementById('inputPassword').value.trim();
    const mensajeDiv = document.getElementById('mensaje');

    mensajeDiv.innerHTML = '';

    if (login === "" || pass === "") {
        mensajeDiv.innerHTML = '<p style="color:red;">Por favor, complete ambos campos.</p>';
        return;
    }

    // Определяем базовый путь автоматически
    var basePath = window.location.pathname;
    var adminPos = basePath.lastIndexOf('/admin');
    if (adminPos !== -1) {
        basePath = basePath.substring(0, adminPos + 6); // +6 для '/admin'
    } else {
        basePath = basePath.substring(0, basePath.lastIndexOf('/') + 1);
    }
    if (basePath[basePath.length - 1] !== '/') {
        basePath += '/';
    }

    fetch(basePath + 'includes/login_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ login, pass })
    })
    .then(response => response.json()) // - poluchaem v vide JSON
    //.then(response => response.text()) // - text poluchaet splashnoy tekst
    .then(data => {
        console.log(data);
        let res_json = JSON.stringify(data);
        let res_arr = JSON.parse(res_json);
        let ok = res_arr['ok'];
        let err = res_arr['err'];
        if (ok === 1) {
            window.location.href = basePath + "index.php";
        } else {
            mensajeDiv.innerHTML = `<p style='color:red;'>Error: ${data.err}</p>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensajeDiv.innerHTML = '<p style="color:red;">No se pudo entrar. Inténtelo de nuevo más tarde.</p>';
    });
}
</script>
