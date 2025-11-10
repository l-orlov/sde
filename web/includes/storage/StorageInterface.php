<?php
/**
 * Интерфейс для работы с хранилищами файлов
 * Позволяет легко переключаться между файловой системой и MinIO/S3
 */
interface StorageInterface {
    /**
     * Сохраняет файл в хранилище
     * 
     * @param array $file Массив из $_FILES
     * @param string $path Логический путь (например: "user_123/product_456/photo.jpg")
     * @param array $metadata Дополнительные метаданные (mime_type, size и т.д.)
     * @return string Возвращает путь, который будет сохранен в БД
     * @throws Exception При ошибке сохранения
     */
    public function save($file, $path, $metadata = []): string;
    
    /**
     * Получает публичный URL для доступа к файлу
     * 
     * @param string $path Путь к файлу (из БД)
     * @return string Публичный URL
     */
    public function getUrl($path): string;
    
    /**
     * Удаляет файл из хранилища
     * 
     * @param string $path Путь к файлу (из БД)
     * @return bool true если успешно удален
     */
    public function delete($path): bool;
    
    /**
     * Проверяет существование файла
     * 
     * @param string $path Путь к файлу (из БД)
     * @return bool true если файл существует
     */
    public function exists($path): bool;
    
    /**
     * Получает содержимое файла (для скачивания)
     * 
     * @param string $path Путь к файлу (из БД)
     * @return string|resource Содержимое файла
     */
    public function getContent($path);
    
    /**
     * Получает размер файла в байтах
     * 
     * @param string $path Путь к файлу (из БД)
     * @return int Размер в байтах
     */
    public function getSize($path): int;
}
