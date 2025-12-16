<?
/**
 * Вспомогательные функции для определения путей
 */

/**
 * Получает базовый путь админки (например, /public/sde/admin/ или /admin/)
 * Это веб-путь для использования в HTML/CSS/JS
 */
function getAdminBasePath() {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptPath);
    
    if (strpos($scriptDir, '/admin') !== false) {
        $adminPos = strrpos($scriptDir, '/admin');
        $basePath = substr($scriptDir, 0, $adminPos + 6); // +6 для '/admin'
    } else {
        $basePath = $scriptDir;
    }
    
    if (substr($basePath, -1) !== '/') {
        $basePath .= '/';
    }
    
    return $basePath;
}

/**
 * Получает путь к общим includes файлам (файловый путь на диске)
 * Используется для include/require в PHP
 */
function getIncludesPath() {
    // Используем __DIR__ для определения реального файлового пути
    // path_helper.php находится в web/admin/includes/
    $helperDir = __DIR__; // /Users/vladimir/projects/cadipel/sde/web/admin/includes/
    $adminDir = dirname($helperDir); // /Users/vladimir/projects/cadipel/sde/web/admin/
    $webDir = dirname($adminDir); // /Users/vladimir/projects/cadipel/sde/web/
    
    // includes находится в web/includes/
    $includesPath = $webDir . '/includes/';
    
    // Нормализуем путь (убираем двойные слеши)
    $includesPath = str_replace('//', '/', $includesPath);
    
    return $includesPath;
}

/**
 * Получает полный путь к файлу в includes
 */
function getIncludesFilePath($filename) {
    return getIncludesPath() . $filename;
}
?>
