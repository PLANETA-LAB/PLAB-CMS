# Требования к хостингу для PLAB CMS

## Минимальные требования

| Компонент | Минимальная версия | Рекомендуемая |
|-----------|-------------------|---------------|
| **PHP** | 7.4.0 | 8.1+ |
| **MySQL** | 5.7.0 | 8.0+ |
| **Память** | 128MB | 256MB |
| **Загрузка файлов** | 64MB | 128MB |

## Расширения PHP (обязательные)

- ✅ pdo_mysql
- ✅ gd
- ✅ json
- ✅ mbstring
- ✅ openssl
- ✅ session

## Рекомендуемые хостинги

| Хостинг | PHP | MySQL | Особенности |
|---------|-----|-------|-------------|
| **BeGet** | 8.2 | 8.0 | от 199₽/мес |
| **Timeweb** | 8.2 | 8.0 | от 299₽/мес |
| **Reg.ru** | 8.1 | 8.0 | от 199₽/мес |
| **Jino** | 8.2 | 8.0 | от 499₽/мес |

## Проверка совместимости

Создайте файл `check.php`:

```php
<?php
echo "PHP версия: " . PHP_VERSION . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "\n";
echo "GD: " . (extension_loaded('gd') ? '✅' : '❌') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? '✅' : '❌') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
?>
