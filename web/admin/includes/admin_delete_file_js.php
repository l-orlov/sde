<?
// Отключаем вывод ошибок на экран
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(0);

$return = ['ok' => 0, 'err' => '', 'res' => ''];

try {
    include __DIR__ . '/path_helper.php';
    include getIncludesFilePath('functions.php');
    
    DBconnect();
    
    // Проверка авторизации и прав администратора
    // В админке используется $_SESSION['admid'], а не $_SESSION['uid']
    if (!isset($_SESSION['admid'])) {
        throw new Exception('No autorizado. Sesión no encontrada.');
    }
    
    $adminId = intval($_SESSION['admid']);
    
    if ($adminId <= 0) {
        throw new Exception('No autorizado. ID de administrador inválido.');
    }
    
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (!$input || !isset($input['file_id']) || !is_numeric($input['file_id'])) {
        throw new Exception('ID de archivo no válido');
    }
    
    $fileId = intval($input['file_id']);
    global $link;
    
    // Получаем информацию о файле для проверки
    $query = "SELECT id, file_path, storage_type, user_id FROM files WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($link));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $fileId);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($link);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar consulta: ' . $error);
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $file = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$file) {
        throw new Exception('Archivo no encontrado');
    }
    
    // Удаляем файл из хранилища
    try {
        // Загружаем все необходимые классы для работы с хранилищем
        require_once getIncludesFilePath('storage/StorageInterface.php');
        require_once getIncludesFilePath('storage/FileSystemStorage.php');
        require_once getIncludesFilePath('storage/MinIOStorage.php');
        require_once getIncludesFilePath('storage/StorageFactory.php');
        
        $storage = StorageFactory::createByType($file['storage_type']);
        $storage->delete($file['file_path']);
    } catch (Exception $e) {
        error_log("Error deleting file from storage: " . $e->getMessage());
        // Продолжаем удаление из БД даже если файл не удалось удалить из хранилища
    }
    
    // Удаляем запись из БД
    $query = "DELETE FROM files WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de eliminación: ' . mysqli_error($link));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $fileId);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($link);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al eliminar archivo de la base de datos: ' . $error);
    }
    
    mysqli_stmt_close($stmt);
    $return['ok'] = 1;
    $return['res'] = 'Archivo eliminado correctamente';
    
} catch (Exception $e) {
    error_log("Error deleting file in admin: " . $e->getMessage());
    $return['err'] = $e->getMessage();
} catch (Error $e) {
    error_log("Fatal error deleting file in admin: " . $e->getMessage());
    $return['err'] = 'Error fatal: ' . $e->getMessage();
}

// Очищаем весь буфер вывода
while (ob_get_level()) {
    ob_end_clean();
}

// Устанавливаем заголовок и выводим JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($return, JSON_UNESCAPED_UNICODE);
exit;
?>
