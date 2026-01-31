<?php
/**
 * Раздача загруженных файлов по ID.
 * Используется для отдачи файлов из локального хранилища независимо от document root,
 * чтобы изображения (логотип и др.) всегда открывались без 404.
 */
session_start();
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

// Только авторизованный пользователь может смотреть свои файлы
if (!isset($_SESSION['uid'])) {
    http_response_code(403);
    exit('Forbidden');
}

global $link;
$stmt = $link->prepare("
    SELECT file_path, storage_type, mime_type, user_id, file_name
    FROM files WHERE id = ?
");
$stmt->bind_param('i', $fileId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || (int) $row['user_id'] !== (int) $_SESSION['uid']) {
    http_response_code(404);
    exit('Not found');
}

try {
    $storage = StorageFactory::createByType($row['storage_type']);
} catch (Exception $e) {
    http_response_code(500);
    exit('Storage error');
}

if (!$storage->exists($row['file_path'])) {
    http_response_code(404);
    exit('File not found');
}

$mime = $row['mime_type'] ?: 'application/octet-stream';
$name = $row['file_name'] ?: 'file';

header('Content-Type: ' . $mime);
header('Content-Length: ' . $storage->getSize($row['file_path']));
header('Cache-Control: private, max-age=86400');
header('Content-Disposition: inline; filename="' . basename($name) . '"');

echo $storage->getContent($row['file_path']);
