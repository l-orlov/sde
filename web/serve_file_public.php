<?php
/**
 * Публичная раздача изображений товаров/услуг для лендинга.
 * Отдаёт только файлы product_photo/service_photo товаров из одобренных компаний.
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

include __DIR__ . '/includes/functions.php';
DBconnect();

require_once __DIR__ . '/includes/storage/StorageInterface.php';
require_once __DIR__ . '/includes/storage/FileSystemStorage.php';
require_once __DIR__ . '/includes/storage/StorageFactory.php';

$fileId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($fileId <= 0) {
    http_response_code(400);
    exit('Invalid file id');
}

global $link;
$stmt = $link->prepare("
    SELECT f.file_path, f.storage_type, f.mime_type, f.file_type, f.product_id
    FROM files f
    INNER JOIN products p ON p.id = f.product_id
    INNER JOIN companies c ON c.id = p.company_id AND c.user_id = p.user_id
    INNER JOIN users u ON u.id = c.user_id
    WHERE f.id = ? 
      AND f.file_type IN ('product_photo', 'product_photo_sec', 'service_photo')
      AND (f.is_temporary = 0 OR f.is_temporary IS NULL)
      AND c.moderation_status = 'approved'
      AND u.include_in_business_exports = 1
");
$stmt->bind_param('i', $fileId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    exit('Not found');
}

try {
    $storage = StorageFactory::createByType($row['storage_type'] ?? 'local');
} catch (Exception $e) {
    http_response_code(500);
    exit('Storage error');
}

if (!$storage->exists($row['file_path'])) {
    http_response_code(404);
    exit('File not found');
}

$mime = $row['mime_type'] ?: 'image/jpeg';

header('Content-Type: ' . $mime);
header('Content-Length: ' . $storage->getSize($row['file_path']));
header('Cache-Control: public, max-age=86400');
header('Content-Disposition: inline');

echo $storage->getContent($row['file_path']);
