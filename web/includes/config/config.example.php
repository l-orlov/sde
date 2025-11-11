<?php
return [
    'db_host' => '127.0.0.1',
    'db_user' => 'user',
    'db_pass' => 'password',
    'db_database' => 'db',
    'db_port' => 3306,
    
    // Конфигурация хранилища файлов
    'storage' => [
        // Тип хранилища: 'local' или 'minio'
        'type' => 'local',
        
        // Конфигурация для локального хранилища (файловая система)
        'local' => [
            'base_path' => __DIR__ . '/../../uploads',
            'base_url' => '/uploads',
        ],
        
        // Конфигурация для MinIO (S3-совместимое хранилище)
        // Заполните при переходе на MinIO
        'minio' => [
            'endpoint' => 'localhost:9000',
            'access_key' => '',
            'secret_key' => '',
            'bucket' => 'user-uploads',
            'use_ssl' => false,
        ],
    ],
    
    // Настройки сжатия файлов
    'compression' => [
        // Сжатие изображений
        'images' => [
            'enabled' => true,
            'max_width' => 1920,      // Максимальная ширина в пикселях
            'max_height' => 1920,     // Максимальная высота в пикселях
            'quality' => 85,           // Качество JPEG (1-100)
            'png_compression' => 6,   // Сжатие PNG (0-9, 9 = максимальное)
        ],
        // Сжатие видео
        'videos' => [
            'enabled' => true,
            'max_size_mb' => 50,      // Максимальный размер видео в MB (после сжатия)
            'max_width' => 1920,      // Максимальная ширина
            'max_height' => 1080,     // Максимальная высота
            'bitrate' => '2000k',     // Битрейт для сжатия
        ],
    ],
];
