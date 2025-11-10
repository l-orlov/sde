<?php
/**
 * ПРИМЕР обработчика загрузки файлов
 * 
 * Этот файл показывает, как интегрировать FileManager в ваши формы.
 * Адаптируйте под ваши нужды.
 */

require_once __DIR__ . '/FileManager.php';

// Начинаем сессию (если еще не начата)
session_start();

// Проверка авторизации (адаптируйте под вашу систему)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$fileManager = new FileManager();

// Обработка загрузки файла товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'upload_product_photo':
                // Получаем product_id из формы или создаем новый товар
                $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
                
                if (!$productId) {
                    // Создаем новый товар (пример)
                    // TODO: Адаптируйте под вашу логику создания товара
                    // $productId = createProduct($userId, $_POST);
                }
                
                if (!isset($_FILES['product_photo']) || $_FILES['product_photo']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded');
                }
                
                $fileId = $fileManager->upload(
                    $_FILES['product_photo'],
                    $productId,
                    $userId,
                    'product_photo'
                );
                
                $fileInfo = $fileManager->getFileInfo($fileId);
                
                echo json_encode([
                    'success' => true,
                    'file_id' => $fileId,
                    'url' => $fileInfo['url'],
                    'file_name' => $fileInfo['file_name'],
                ]);
                break;
                
            case 'upload_multiple':
                // Загрузка нескольких файлов (например, логотипы, фото процессов)
                $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
                $fileType = $_POST['file_type'] ?? 'product_photo';
                
                if (!isset($_FILES['files'])) {
                    throw new Exception('No files uploaded');
                }
                
                $fileIds = $fileManager->uploadMultiple(
                    $_FILES['files'],
                    $productId,
                    $userId,
                    $fileType
                );
                
                $files = [];
                foreach ($fileIds as $id) {
                    $fileInfo = $fileManager->getFileInfo($id);
                    $files[] = [
                        'id' => $id,
                        'url' => $fileInfo['url'],
                        'file_name' => $fileInfo['file_name'],
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'files' => $files,
                ]);
                break;
                
            case 'delete':
                $fileId = (int)$_POST['file_id'];
                $fileManager->delete($fileId, $userId);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'get_product_files':
                $productId = (int)$_POST['product_id'];
                $fileType = $_POST['file_type'] ?? null;
                
                $files = $fileManager->getProductFiles($productId, $fileType);
                
                echo json_encode([
                    'success' => true,
                    'files' => $files,
                ]);
                break;
                
            default:
                throw new Exception('Unknown action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'error' => $e->getMessage(),
        ]);
    }
    exit;
}

// Если это не POST запрос
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

