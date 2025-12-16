<?
if (!isset($basePath)) {
    include __DIR__ . '/path_helper.php';
    $basePath = getAdminBasePath();
}
?>
        <ul class="navbar-nav bg-white sidebar sidebar-light accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= $basePath ?>index.php">
                <div class="site-logo">
                    <img src="<?= $basePath ?>img/logo.png">
                </div>
            </a>

            <hr class="sidebar-divider my-0">
            <hr class="sidebar-divider">

            <li class="nav-item">
                <a class="nav-link" href="<?= $basePath ?>index.php?page=users">
                    <img src="<?= $basePath ?>img/db_table.png" class="nav-icon-size">
                    <span>Usuarios</span>
                </a>
            </li>
     
        </ul>
