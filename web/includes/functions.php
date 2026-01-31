<?
/**
 * Версия ассета по времени изменения файла (для cache busting).
 * $path — путь относительно корня web/ (например 'css/style.css', 'js/i18n.js', 'admin/css/style.css').
 */
function asset_version($path) {
    $webRoot = dirname(__DIR__);
    $full = $webRoot . '/' . ltrim($path, '/');
    return file_exists($full) ? (string) filemtime($full) : (string) time();
}

function DBconnect() {
    global $link;

    // Load config
    $configPath = __DIR__ . '/config/config.php';
    if (!file_exists($configPath)) {
        die("Error: Configuration file not found.");
    }
    $config = require $configPath;

    // Check if the parameters are loaded
    if (!isset($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_database'], $config['db_port'])) {
        die("Error: The configuration file is corrupted or incomplete.");
    }

    // Extract database connection details
    $db_host = $config['db_host'];
    $db_user = $config['db_user'];
    $db_pass = $config['db_pass'];
    $db_database = $config['db_database'];
    $db_port = $config['db_port']; // Ensure we use the port

    // Connect to MySQL with port
    $link = mysqli_connect($db_host, $db_user, $db_pass, $db_database, $db_port);

    // Check connection
    if (!$link) {
        die("Unable to establish a DB connection: " . mysqli_connect_error());
    }

    mysqli_set_charset($link, "utf8");
}

// Removes all non-digit characters from a phone number
function clear_phone($phone) {
    return preg_replace('/\D/', '', $phone);
}

/**
 * URL пути к serve_file.php для раздачи загруженных файлов по ID.
 * Учитывает web_base из config (если сайт в подпапке, напр. /sde).
 * $file_id — ID записи в таблице files.
 */
function get_serve_file_url($file_id) {
    static $web_base = null;
    if ($web_base === null) {
        $configPath = __DIR__ . '/config/config.php';
        $config = file_exists($configPath) ? (require $configPath) : [];
        $web_base = rtrim($config['web_base'] ?? '', '/');
    }
    return $web_base . '/serve_file.php?id=' . (int) $file_id;
}
