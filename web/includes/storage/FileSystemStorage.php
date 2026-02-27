<?php
/**
 * Реализация хранилища на основе файловой системы
 * Используется для быстрого старта
 */
class FileSystemStorage implements StorageInterface {
    private $basePath;
    private $baseUrl;
    
    public function __construct($config) {
        $this->basePath = rtrim($config['base_path'], '/');
        $this->baseUrl = rtrim($config['base_url'], '/');
        
        // Создаем базовую папку если не существует
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }
    
    /**
     * Сохраняет файл на диск
     */
    public function save($file, $path, $metadata = []): string {
        // Нормализуем путь (убираем лишние слэши)
        $path = ltrim($path, '/');
        
        // Полный путь на диске
        $fullPath = $this->basePath . '/' . $path;
        
        // Создаем директории если не существуют
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Проверяем, что это загруженный файл
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception("File is not a valid uploaded file");
        }
        
        // Перемещаем файл
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception("Failed to save file: " . $file['name']);
        }
        
        // Устанавливаем права доступа
        chmod($fullPath, 0644);
        
        // Возвращаем логический путь (без basePath)
        return $path;
    }
    
    /**
     * Получает публичный URL для файла
     */
    public function getUrl($path): string {
        $path = ltrim($path, '/');
        // Убеждаемся, что baseUrl начинается с / для абсолютного пути от корня сайта
        $baseUrl = trim($this->baseUrl, '/');
        if (empty($baseUrl)) {
            // Если baseUrl пустой, возвращаем путь от корня
            return '/' . $path;
        }
        // Возвращаем абсолютный путь от корня сайта
        return '/' . $baseUrl . '/' . $path;
    }
    
    /**
     * Удаляет файл с диска
     */
    public function delete($path): bool {
        $path = ltrim($path, '/');
        $fullPath = $this->basePath . '/' . $path;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Проверяет существование файла
     */
    public function exists($path): bool {
        $path = ltrim($path, '/');
        $fullPath = $this->basePath . '/' . $path;
        return file_exists($fullPath);
    }
    
    /**
     * Получает содержимое файла
     */
    public function getContent($path) {
        $path = ltrim($path, '/');
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: " . $path);
        }
        
        return file_get_contents($fullPath);
    }
    
    /**
     * Получает размер файла
     */
    public function getSize($path): int {
        $path = ltrim($path, '/');
        $fullPath = $this->basePath . '/' . $path;
        
        if (file_exists($fullPath)) {
            return filesize($fullPath);
        }
        
        return 0;
    }
    
    /**
     * Перемещает файл в новое место (в пределах basePath)
     */
    public function move(string $oldPath, string $newPath): bool {
        $oldPath = ltrim($oldPath, '/');
        $newPath = ltrim($newPath, '/');
        $fullOld = $this->basePath . '/' . $oldPath;
        $fullNew = $this->basePath . '/' . $newPath;
        
        if (!file_exists($fullOld)) {
            return false;
        }
        $dir = dirname($fullNew);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ok = rename($fullOld, $fullNew);
        if ($ok) {
            chmod($fullNew, 0644);
        }
        return $ok;
    }
    
    /**
     * Удаляет каталог и всё содержимое рекурсивно
     */
    public function deleteDirectory(string $path): bool {
        $path = ltrim($path, '/');
        $fullPath = $this->basePath . '/' . $path;
        if (!is_dir($fullPath)) {
            return true;
        }
        $items = @scandir($fullPath);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = $fullPath . '/' . $item;
            $childPath = $path . '/' . $item;
            if (is_dir($child)) {
                $this->deleteDirectory($childPath);
            } else {
                @unlink($child);
            }
        }
        return @rmdir($fullPath);
    }

    /**
     * Создаёт каталог (и родительские при необходимости), если не существует.
     */
    public function ensureDirectory(string $path): bool {
        $path = ltrim($path, '/');
        $fullPath = $this->basePath . '/' . $path;
        if (is_dir($fullPath)) {
            return true;
        }
        return @mkdir($fullPath, 0755, true);
    }
}
