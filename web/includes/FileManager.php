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
        
        // Сжимаем файл перед сохранением
        $compressedFile = $this->compressFile($file);
        
        // Генерируем путь для файла (используем оригинальное расширение)
        $path = $this->generatePath($userId, $productId ?? 0, $compressedFile, $fileType);
        
        // Сохраняем файл
        $savedPath = $this->storage->save($compressedFile, $path, [
            'mime_type' => $compressedFile['type'],
            'size' => $compressedFile['size'],
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
                $compressedFile['name'],
                $fileType,
                $compressedFile['type'],
                $compressedFile['size'],
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
                $compressedFile['name'],
                $fileType,
                $compressedFile['type'],
                $compressedFile['size'],
                $storageType
            );
        }
        
        if (!$stmt->execute()) {
            // Если не удалось сохранить в БД, удаляем файл
            $this->storage->delete($savedPath);
            // Удаляем временный файл после сжатия, если он был создан
            if ($compressedFile['tmp_name'] !== $file['tmp_name'] && file_exists($compressedFile['tmp_name'])) {
                unlink($compressedFile['tmp_name']);
            }
            throw new Exception("Failed to save file metadata: " . $this->db->error);
        }
        
        // Удаляем временный файл после сжатия, если он был создан
        if ($compressedFile['tmp_name'] !== $file['tmp_name'] && file_exists($compressedFile['tmp_name'])) {
            unlink($compressedFile['tmp_name']);
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
        
        // Проверка размера (максимум зависит от типа файла)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Для видео разрешаем больший размер (будет сжат)
        if (strpos($mimeType, 'video/') === 0) {
            $maxSize = 200 * 1024 * 1024; // 200MB для видео (до сжатия)
        } else {
            $maxSize = 10 * 1024 * 1024; // 10MB для остальных файлов
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds maximum allowed size (" . ($maxSize / 1024 / 1024) . "MB)");
        }
        
        // Проверка типа файла
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'video/mp4',
            'video/x-matroska',
            'video/x-msvideo',
        ];
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("File type not allowed: " . $mimeType);
        }
    }
    
    /**
     * Сжимает файл (изображение или видео)
     * 
     * @param array $file Массив из $_FILES
     * @return array Массив файла (может быть изменен после сжатия)
     */
    private function compressFile($file): array {
        $config = require __DIR__ . '/config/config.php';
        $compressionConfig = $config['compression'] ?? [];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Сжатие изображений
        if (strpos($mimeType, 'image/') === 0) {
            if (isset($compressionConfig['images']['enabled']) && $compressionConfig['images']['enabled']) {
                return $this->compressImage($file, $mimeType, $compressionConfig['images']);
            }
        }
        
        // Сжатие видео
        if (strpos($mimeType, 'video/') === 0) {
            if (isset($compressionConfig['videos']['enabled']) && $compressionConfig['videos']['enabled']) {
                return $this->compressVideo($file, $mimeType, $compressionConfig['videos']);
            }
        }
        
        // Если сжатие отключено или тип не поддерживается, возвращаем оригинал
        return $file;
    }
    
    /**
     * Сжимает изображение
     * 
     * @param array $file Массив из $_FILES
     * @param string $mimeType MIME тип изображения
     * @param array $config Настройки сжатия
     * @return array Массив файла после сжатия
     */
    private function compressImage($file, $mimeType, $config): array {
        if (!extension_loaded('gd')) {
            error_log("GD extension not loaded, skipping image compression");
            return $file;
        }
        
        $maxWidth = $config['max_width'] ?? 1920;
        $maxHeight = $config['max_height'] ?? 1920;
        $quality = $config['quality'] ?? 85;
        
        // Загружаем изображение
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($file['tmp_name']);
                }
                break;
        }
        
        if (!$sourceImage) {
            return $file;
        }
        
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        
        // Вычисляем новые размеры с сохранением пропорций
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Если изображение уже меньше максимального размера, не сжимаем
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            imagedestroy($sourceImage);
            return $file;
        }
        
        // Создаем новое изображение
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Для PNG сохраняем прозрачность
        if ($mimeType === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Масштабируем изображение
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Сохраняем в временный файл
        $tempFile = tempnam(sys_get_temp_dir(), 'compressed_');
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        $saved = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $saved = imagejpeg($newImage, $tempFile, $quality);
                $mimeType = 'image/jpeg';
                $extension = 'jpg';
                break;
            case 'image/png':
                $pngCompression = $config['png_compression'] ?? 6;
                $saved = imagepng($newImage, $tempFile, $pngCompression);
                $mimeType = 'image/png';
                $extension = 'png';
                break;
            case 'image/gif':
                $saved = imagegif($newImage, $tempFile);
                $mimeType = 'image/gif';
                $extension = 'gif';
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($newImage, $tempFile, $quality);
                    $mimeType = 'image/webp';
                    $extension = 'webp';
                }
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        if (!$saved) {
            unlink($tempFile);
            return $file;
        }
        
        // Обновляем информацию о файле
        $newFileName = pathinfo($file['name'], PATHINFO_FILENAME) . '.' . $extension;
        $newSize = filesize($tempFile);
        
        return [
            'name' => $newFileName,
            'type' => $mimeType,
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => $newSize,
        ];
    }
    
    /**
     * Сжимает видео (требует ffmpeg)
     * 
     * @param array $file Массив из $_FILES
     * @param string $mimeType MIME тип видео
     * @param array $config Настройки сжатия
     * @return array Массив файла после сжатия
     */
    private function compressVideo($file, $mimeType, $config): array {
        // Проверяем наличие ffmpeg
        $ffmpegPath = $this->findFFmpeg();
        if (!$ffmpegPath) {
            error_log("FFmpeg not found, skipping video compression");
            // Если видео слишком большое, отклоняем его
            $maxSize = ($config['max_size_mb'] ?? 50) * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new Exception("Video file is too large. Please compress it manually or install FFmpeg.");
            }
            return $file;
        }
        
        $maxSize = ($config['max_size_mb'] ?? 50) * 1024 * 1024;
        $maxWidth = $config['max_width'] ?? 1920;
        $maxHeight = $config['max_height'] ?? 1080;
        $bitrate = $config['bitrate'] ?? '2000k';
        
        // Если видео уже меньше лимита, не сжимаем
        if ($file['size'] <= $maxSize) {
            return $file;
        }
        
        // Создаем временный файл для сжатого видео
        $tempFile = tempnam(sys_get_temp_dir(), 'compressed_video_') . '.mp4';
        
        // Команда ffmpeg для сжатия
        $command = escapeshellarg($ffmpegPath) . ' -i ' . escapeshellarg($file['tmp_name']) . 
                   ' -vf scale=' . $maxWidth . ':' . $maxHeight . ':force_original_aspect_ratio=decrease' .
                   ' -b:v ' . escapeshellarg($bitrate) .
                   ' -c:v libx264 -preset medium -crf 23' .
                   ' -c:a aac -b:a 128k' .
                   ' -movflags +faststart' .
                   ' -y ' . escapeshellarg($tempFile) . ' 2>&1';
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($tempFile)) {
            error_log("FFmpeg compression failed: " . implode("\n", $output));
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            // Если сжатие не удалось, но файл не слишком большой, возвращаем оригинал
            if ($file['size'] <= $maxSize * 2) {
                return $file;
            }
            throw new Exception("Failed to compress video. File is too large.");
        }
        
        $newSize = filesize($tempFile);
        
        // Если сжатое видео все еще слишком большое, удаляем и возвращаем ошибку
        if ($newSize > $maxSize) {
            unlink($tempFile);
            throw new Exception("Video file is too large even after compression. Maximum size: " . ($maxSize / 1024 / 1024) . "MB");
        }
        
        return [
            'name' => pathinfo($file['name'], PATHINFO_FILENAME) . '.mp4',
            'type' => 'video/mp4',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => $newSize,
        ];
    }
    
    /**
     * Ищет путь к ffmpeg
     * 
     * @return string|null Путь к ffmpeg или null если не найден
     */
    private function findFFmpeg(): ?string {
        $possiblePaths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'ffmpeg', // В PATH
        ];
        
        foreach ($possiblePaths as $path) {
            if ($path === 'ffmpeg') {
                // Проверяем через which/whereis
                $output = [];
                exec('which ffmpeg 2>/dev/null', $output);
                if (!empty($output) && file_exists($output[0])) {
                    return $output[0];
                }
            } else {
                if (file_exists($path) && is_executable($path)) {
                    return $path;
                }
            }
        }
        
        return null;
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
