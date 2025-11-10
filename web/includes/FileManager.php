<?php
require_once __DIR__ . '/storage/StorageInterface.php';
require_once __DIR__ . '/storage/FileSystemStorage.php';
require_once __DIR__ . '/storage/MinIOStorage.php';
require_once __DIR__ . '/storage/StorageFactory.php';
require_once __DIR__ . '/functions.php';

/**
 * Высокоуровневый API для работы с файлами
 * Упрощает работу с файлами, скрывая детали хранилища
 */
class FileManager {
    private $storage;
    private $db;
    
    public function __construct($storageType = null) {
        $this->storage = StorageFactory::create($storageType);
        DBconnect();
        global $link;
        $this->db = $link;
    }
    
    /**
     * Загружает файл и сохраняет метаданные в БД
     * 
     * @param array $file Массив из $_FILES
     * @param int|null $productId ID товара (может быть null для файлов компании)
     * @param int $userId ID пользователя
     * @param string $fileType Тип файла ('product_photo', 'logo', 'process_photo', etc.)
     * @return int ID созданной записи в БД
     * @throws Exception При ошибке
     */
    public function upload($file, $productId, $userId, $fileType = 'product_photo'): int {
        // Валидация
        $this->validateFile($file);
        
        // Генерируем путь для файла
        $path = $this->generatePath($userId, $productId ?? 0, $file, $fileType);
        
        // Сохраняем файл
        $savedPath = $this->storage->save($file, $path, [
            'mime_type' => $file['type'],
            'size' => $file['size'],
        ]);
        
        // Получаем тип хранилища из конфига
        $config = require __DIR__ . '/config/config.php';
        $storageType = $config['storage']['type'] ?? 'local';
        
        // Сохраняем метаданные в БД
        // Для nullable product_id используем условный запрос
        if ($productId === null) {
            $stmt = $this->db->prepare("
                INSERT INTO files (
                    product_id, user_id, file_path, file_name, 
                    file_type, mime_type, file_size, storage_type, created_at
                ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())
            ");
            $stmt->bind_param("issssis",
                $userId,
                $savedPath,
                $file['name'],
                $fileType,
                $file['type'],
                $file['size'],
                $storageType
            );
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO files (
                    product_id, user_id, file_path, file_name, 
                    file_type, mime_type, file_size, storage_type, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())
            ");
            $stmt->bind_param("iissssis",
                $productId,
                $userId,
                $savedPath,
                $file['name'],
                $fileType,
                $file['type'],
                $file['size'],
                $storageType
            );
        }
        
        if (!$stmt->execute()) {
            // Если не удалось сохранить в БД, удаляем файл
            $this->storage->delete($savedPath);
            throw new Exception("Failed to save file metadata: " . $this->db->error);
        }
        
        return $this->db->insert_id;
    }
    
    /**
     * Загружает несколько файлов
     * 
     * @param array $files Массив файлов из $_FILES['field_name']
     * @param int $productId ID товара
     * @param int $userId ID пользователя
     * @param string $fileType Тип файла
     * @return array Массив ID созданных записей
     */
    public function uploadMultiple($files, $productId, $userId, $fileType = 'product_photo'): array {
        $ids = [];
        
        // Нормализуем массив файлов
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']],
            ];
        }
        
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                
                try {
                    $ids[] = $this->upload($file, $productId, $userId, $fileType);
                } catch (Exception $e) {
                    // Логируем ошибку, но продолжаем загрузку остальных
                    error_log("Failed to upload file {$file['name']}: " . $e->getMessage());
                }
            }
        }
        
        return $ids;
    }
    
    /**
     * Получает URL файла по ID
     * 
     * @param int $fileId ID файла в БД
     * @return string|null URL файла или null если не найден
     */
    public function getUrl($fileId): ?string {
        $stmt = $this->db->prepare("SELECT file_path, storage_type FROM files WHERE id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $storage = StorageFactory::createByType($row['storage_type']);
            return $storage->getUrl($row['file_path']);
        }
        
        return null;
    }
    
    /**
     * Получает информацию о файле
     * 
     * @param int $fileId ID файла
     * @return array|null Массив с информацией о файле
     */
    public function getFileInfo($fileId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, product_id, user_id, file_path, file_name, 
                   file_type, mime_type, file_size, storage_type, created_at
            FROM files WHERE id = ?
        ");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $storage = StorageFactory::createByType($row['storage_type']);
            $row['url'] = $storage->getUrl($row['file_path']);
            return $row;
        }
        
        return null;
    }
    
    /**
     * Получает все файлы товара
     * 
     * @param int $productId ID товара
     * @param string|null $fileType Тип файла (опционально)
     * @return array Массив файлов
     */
    public function getProductFiles($productId, $fileType = null): array {
        $sql = "SELECT id, file_path, file_name, file_type, mime_type, 
                       file_size, storage_type, created_at
                FROM files WHERE product_id = ?";
        
        if ($fileType) {
            $sql .= " AND file_type = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("is", $productId, $fileType);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $productId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $files = [];
        while ($row = $result->fetch_assoc()) {
            $storage = StorageFactory::createByType($row['storage_type']);
            $row['url'] = $storage->getUrl($row['file_path']);
            $files[] = $row;
        }
        
        return $files;
    }
    
    /**
     * Удаляет файл
     * 
     * @param int $fileId ID файла
     * @param int|null $userId ID пользователя (для проверки прав)
     * @return bool true если успешно удален
     */
    public function delete($fileId, $userId = null): bool {
        // Получаем информацию о файле
        $stmt = $this->db->prepare("SELECT file_path, storage_type, user_id FROM files WHERE id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$row = $result->fetch_assoc()) {
            return false;
        }
        
        // Проверка прав доступа
        if ($userId !== null && $row['user_id'] != $userId) {
            throw new Exception("Access denied: user does not own this file");
        }
        
        // Удаляем файл из хранилища
        $storage = StorageFactory::createByType($row['storage_type']);
        $storage->delete($row['file_path']);
        
        // Удаляем запись из БД
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = ?");
        $stmt->bind_param("i", $fileId);
        
        return $stmt->execute();
    }
    
    /**
     * Удаляет все файлы товара
     * 
     * @param int $productId ID товара
     * @param int|null $userId ID пользователя (для проверки прав)
     * @return int Количество удаленных файлов
     */
    public function deleteProductFiles($productId, $userId = null): int {
        $files = $this->getProductFiles($productId);
        $deleted = 0;
        
        foreach ($files as $file) {
            try {
                if ($this->delete($file['id'], $userId)) {
                    $deleted++;
                }
            } catch (Exception $e) {
                error_log("Failed to delete file {$file['id']}: " . $e->getMessage());
            }
        }
        
        return $deleted;
    }
    
    /**
     * Валидация файла
     */
    private function validateFile($file): void {
        // Проверка ошибки загрузки
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            ];
            throw new Exception($errors[$file['error']] ?? 'Unknown upload error');
        }
        
        // Проверка размера (максимум 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds maximum allowed size (10MB)");
        }
        
        // Проверка типа файла
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("File type not allowed: " . $mimeType);
        }
    }
    
    /**
     * Генерирует путь для файла
     */
    private function generatePath($userId, $productId, $file, $fileType): string {
        // Генерируем уникальное имя файла
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $hash = md5($userId . ($productId ?? 'company') . time() . rand());
        $fileName = $hash . '.' . $extension;
        
        // Если productId = null (файлы компании), используем структуру company
        if ($productId === null) {
            return "user_{$userId}/company/{$fileType}_{$fileName}";
        }
        
        // Структура для файлов товаров: user_{id}/product_{id}/{type}_{filename}
        return "user_{$userId}/product_{$productId}/{$fileType}_{$fileName}";
    }
}
