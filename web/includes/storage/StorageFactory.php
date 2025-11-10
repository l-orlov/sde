<?php
/**
 * Фабрика для создания экземпляров хранилищ
 * Выбирает нужную реализацию на основе конфигурации
 */
class StorageFactory {
    /**
     * Создает экземпляр хранилища
     * 
     * @param string|null $type Тип хранилища ('local' или 'minio'). Если null, берется из конфига
     * @return StorageInterface Экземпляр хранилища
     * @throws Exception Если тип хранилища неизвестен
     */
    public static function create($type = null): StorageInterface {
        $configPath = __DIR__ . '/../config/config.php';
        if (!file_exists($configPath)) {
            throw new Exception("Configuration file not found");
        }
        
        $config = require $configPath;
        
        // Определяем тип хранилища
        $storageType = $type ?? $config['storage']['type'] ?? 'local';
        
        // Получаем конфигурацию для выбранного хранилища
        $storageConfig = $config['storage'][$storageType] ?? [];
        
        if (empty($storageConfig)) {
            throw new Exception("Storage configuration for '{$storageType}' not found");
        }
        
        // Создаем экземпляр хранилища
        switch ($storageType) {
            case 'local':
                return new FileSystemStorage($storageConfig);
                
            case 'minio':
                return new MinIOStorage($storageConfig);
                
            default:
                throw new Exception("Unknown storage type: {$storageType}");
        }
    }
    
    /**
     * Создает хранилище на основе типа из БД
     * Полезно когда файлы могут храниться в разных хранилищах
     * 
     * @param string $storageType Тип хранилища из БД ('local' или 'minio')
     * @return StorageInterface
     */
    public static function createByType($storageType): StorageInterface {
        return self::create($storageType);
    }
}
