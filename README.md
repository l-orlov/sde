# sde

### Команда для запуска локально
```
php -d short_open_tag=On -S localhost:8888
```

### Команда для запуска локально с настройкой для приема больших файлов
```
php -d short_open_tag=On -d post_max_size=200M -d upload_max_filesize=200M -d max_file_uploads=50 -d memory_limit=512M -d max_execution_time=600 -d max_input_time=600 -S localhost:8888
```
