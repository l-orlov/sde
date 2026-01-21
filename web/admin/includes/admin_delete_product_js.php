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
    
    if (!$input || !isset($input['product_id']) || !is_numeric($input['product_id'])) {
        throw new Exception('ID de producto/servicio no válido');
    }
    
    $productId = intval($input['product_id']);
    global $link;
    
    // Проверяем, что продукт/услуга существует
    $query = "SELECT id, user_id FROM products WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($link));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($link);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar consulta: ' . $error);
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$product) {
        throw new Exception('Producto/Servicio no encontrado');
    }
    
    // Загружаем все необходимые классы для работы с хранилищем
    require_once getIncludesFilePath('storage/StorageInterface.php');
    require_once getIncludesFilePath('storage/FileSystemStorage.php');
    require_once getIncludesFilePath('storage/MinIOStorage.php');
    require_once getIncludesFilePath('storage/StorageFactory.php');
    
    // Удаляем все файлы продукта/услуги
    $query = "SELECT id, file_path, storage_type FROM files WHERE product_id = ?";
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de archivos: ' . mysqli_error($link));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($link);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar consulta de archivos: ' . $error);
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    while ($file = mysqli_fetch_assoc($result)) {
        try {
            // Удаляем файл из хранилища
            $storage = StorageFactory::createByType($file['storage_type']);
            $storage->delete($file['file_path']);
        } catch (Exception $e) {
            error_log("Error deleting file from storage: " . $e->getMessage());
            // Продолжаем удаление даже если файл не удалось удалить из хранилища
        }
    }
    mysqli_stmt_close($stmt);
    
    // Удаляем записи файлов из БД
    $query = "DELETE FROM files WHERE product_id = ?";
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de eliminación de archivos: ' . mysqli_error($link));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($link);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al eliminar archivos de la base de datos: ' . $error);
    }
    mysqli_stmt_close($stmt);
    
    // Удаляем продукт/услугу из БД
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de eliminación: ' . mysqli_error($link));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($link);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al eliminar producto/servicio de la base de datos: ' . $error);
    }
    
    mysqli_stmt_close($stmt);
    $return['ok'] = 1;
    $return['res'] = 'Producto/Servicio eliminado correctamente';
    
} catch (Exception $e) {
    error_log("Error deleting product/service in admin: " . $e->getMessage());
    $return['err'] = $e->getMessage();
} catch (Error $e) {
    error_log("Fatal error deleting product/service in admin: " . $e->getMessage());
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
