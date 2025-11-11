# Система работы с файлами

Архитектура с абстракцией хранилища, позволяющая легко переключаться между файловой системой и MinIO/S3.

## Структура

- `StorageInterface.php` - Интерфейс для всех хранилищ
- `FileSystemStorage.php` - Реализация для локальной файловой системы
- `MinIOStorage.php` - Реализация для MinIO (заглушка для будущего)
- `StorageFactory.php` - Фабрика для создания экземпляров хранилищ

## Использование

### Через FileManager (рекомендуется)

```php
require_once __DIR__ . '/FileManager.php';

$fileManager = new FileManager();

// Загрузка одного файла
$fileId = $fileManager->upload(
    $_FILES['product_photo'],
    $productId,
    $userId,
    'product_photo'
);

// Загрузка нескольких файлов
$fileIds = $fileManager->uploadMultiple(
    $_FILES['company_logo'],
    $productId,
    $userId,
    'logo'
);

// Получение URL файла
$url = $fileManager->getUrl($fileId);

// Получение всех файлов товара
$files = $fileManager->getProductFiles($productId, 'product_photo');

// Удаление файла
$fileManager->delete($fileId, $userId);
```

### Напрямую через Storage (для продвинутых случаев)

```php
require_once __DIR__ . '/storage/StorageFactory.php';

$storage = StorageFactory::create(); // Использует тип из конфига
// или
$storage = StorageFactory::create('local'); // Явно указываем тип

// Сохранение
$path = $storage->save($_FILES['photo'], 'user_123/product_456/photo.jpg');

// Получение URL
$url = $storage->getUrl($path);

// Удаление
$storage->delete($path);
```

## Конфигурация

В `config/config.php`:

```php
'storage' => [
    'type' => 'local', // или 'minio'
    
    'local' => [
        'base_path' => __DIR__ . '/../../uploads',
        'base_url' => 'uploads',
    ],
    
    'minio' => [
        'endpoint' => 'localhost:9000',
        'access_key' => 'your-key',
        'secret_key' => 'your-secret',
        'bucket' => 'user-uploads',
        'use_ssl' => false,
    ],
],
```

## Типы файлов

- `product_photo` - Фото товара
- `logo` - Логотип компании
- `process_photo` - Фото процесса
- `digital_catalog` - Цифровой каталог
- `institutional_video` - Видео

## Переход на MinIO

1. Установите и настройте MinIO
2. Заполните конфигурацию `minio` в `config.php`
3. Измените `'type' => 'minio'` в конфиге
4. Реализуйте методы в `MinIOStorage.php` (сейчас заглушка)

Код приложения менять не нужно!
