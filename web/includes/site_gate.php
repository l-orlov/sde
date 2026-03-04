<?php
/**
 * Страница-заглушка: запрос логина/пароля для доступа к публичным страницам.
 * Стиль как у admin/login.php. Пути к админ-стилям относительные от index.php.
 */
$gate_return_page = isset($gate_return_page) ? $gate_return_page : 'landing';
$gate_return_page = preg_replace('/[^a-z_]/', '', $gate_return_page);
if (!in_array($gate_return_page, ['search', 'landing', 'regfull', 'regnew', 'login', 'reset_password'], true)) {
    $gate_return_page = 'landing';
}
$config = file_exists(__DIR__ . '/config/config.php') ? (require __DIR__ . '/config/config.php') : [];
$web_base = rtrim($config['web_base'] ?? '', '/');
// Относительные пути от текущего документа (index.php), чтобы стили грузились при любом web_base
$base_assets = 'admin/';
$base_url = $web_base ? $web_base . '/' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Acceso al sitio</title>
    <link rel="icon" type="image/png" href="<?= $base_url ?>img/icons/logo_icon.png">
    <link rel="shortcut icon" type="image/png" href="<?= $base_url ?>img/icons/logo_icon.png">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $base_assets ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="<?= $base_assets ?>css/style.css?v=<?= asset_version('admin/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="text-center">
                                    <img class="pt-5" src="<?= $base_assets ?>img/logo.svg" style="max-width: 300px; width: 100%;" alt="Santiago del Estero">
                                </div>
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Acceso al sitio</h1>
                                        <p class="text-muted small">Introduzca el usuario y la contraseña para continuar.</p>
                                    </div>
                                    <form class="user" id="gateForm" onsubmit="return submitGate(event)">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user" id="inputLogin" placeholder="Usuario" autocomplete="username">
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user" id="inputPassword" placeholder="Contraseña" autocomplete="current-password">
                                        </div>
                                        <input type="hidden" id="inputReturn" value="<?= htmlspecialchars($gate_return_page) ?>">
                                        <div id="gate_mensaje"></div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">Entrar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= $base_assets ?>vendor/jquery/jquery.min.js"></script>
    <script src="<?= $base_assets ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    var GATE_BASE = '<?= addslashes($base_url) ?>';
    function submitGate(e) {
        e.preventDefault();
        var login = document.getElementById('inputLogin').value.trim();
        var pass = document.getElementById('inputPassword').value.trim();
        var returnPage = document.getElementById('inputReturn').value || 'landing';
        var mensajeDiv = document.getElementById('gate_mensaje');
        mensajeDiv.innerHTML = '';
        if (!login || !pass) {
            mensajeDiv.innerHTML = '<p class="mt-2 mb-0" style="color:red;">Por favor, complete ambos campos.</p>';
            return false;
        }
        var url = GATE_BASE + 'index.php?page=site_gate_check';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login: login, pass: pass, return: returnPage })
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(function(text) {
            var data;
            try { data = JSON.parse(text); } catch (err) { throw new Error('Respuesta no válida'); }
            if (data.ok === 1 && data.redirect) {
                window.location.href = GATE_BASE + data.redirect;
            } else {
                mensajeDiv.innerHTML = '<p class="mt-2 mb-0" style="color:red;">' + (data.err || 'Usuario o contraseña incorrectos.') + '</p>';
            }
        })
        .catch(function(err) {
            mensajeDiv.innerHTML = '<p class="mt-2 mb-0" style="color:red;">Error de conexión. Inténtelo de nuevo.</p>';
        });
        return false;
    }
    </script>
</body>
</html>
