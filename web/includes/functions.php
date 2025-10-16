<?
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
