<?php
/**
 * PLAB CMS Installer v1.0
 * 
 * @package    PLAB CMS
 * @version    1.0.0
 * @author     PLANETA LAB
 * @copyright  2025 PLANETA LAB. All rights reserved.
 * @license    MIT License - https://opensource.org/licenses/MIT
 * @link       https://planetalab.ru
 * 
 * @wordpress-foundation
 * This file is part of PLAB CMS - A cosmic content management system
 * for music distribution. Inspired by WordPress philosophy.
 */

// Защита от прямого доступа
if (!defined('PLAB_INSTALL')) {
    define('PLAB_INSTALL', true);
}

// Защита от копирования - проверка лицензии
$licenseKey = $_POST['license_key'] ?? '';
if (!empty($licenseKey)) {
    // Проверка лицензии (локальная, без внешних запросов)
    if (!preg_match('/^PLAB-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $licenseKey)) {
        die('Неверный лицензионный ключ. Пожалуйста, используйте ключ, полученный при скачивании.');
    }
}

// Версия CMS
define('PLAB_VERSION', '1.0.0');
define('PLAB_BUILD', '20250401');
define('PLAB_DB_VERSION', '100');

// Минимальные требования
$minPhpVersion = '7.4';
$minMemoryLimit = '128M';
$minUploadSize = '64M';

// Отпечаток PLAB CMS (как у WordPress)
function plab_footer() {
    echo '<!-- PLAB CMS v' . PLAB_VERSION . ' | https://planetalab.ru -->';
    echo "\n";
    echo '<!-- Generated in ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . ' seconds -->';
}
register_shutdown_function('plab_footer');

// Защита от копирования - генерация уникального идентификатора
$installId = md5(__DIR__ . $_SERVER['HTTP_HOST'] . time());

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
session_start();

$step = (int)($_GET['step'] ?? 1);
$error = null;
$success = null;

// Проверка установки
if (file_exists(__DIR__ . '/config.php') && file_exists(__DIR__ . '/install.lock')) {
    die('<h1>PLAB CMS уже установлена</h1><p>Для переустановки удалите файлы config.php и install.lock</p><p><a href="https://planetalab.ru/docs">Документация</a></p>');
}

// Шаг 1: Проверка сервера
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['install_step'] = 2;
    header('Location: ?step=2');
    exit;
}

// Шаг 2: Настройка БД
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['db_host'] = $_POST['db_host'] ?? 'localhost';
    $_SESSION['db_name'] = $_POST['db_name'] ?? '';
    $_SESSION['db_user'] = $_POST['db_user'] ?? '';
    $_SESSION['db_pass'] = $_POST['db_pass'] ?? '';
    $_SESSION['db_prefix'] = $_POST['db_prefix'] ?? 'plab_';
    
    if (empty($_SESSION['db_name']) || empty($_SESSION['db_user'])) {
        $error = 'Заполните все поля базы данных';
    } else {
        try {
            $dsn = "mysql:host=" . $_SESSION['db_host'] . ";dbname=" . $_SESSION['db_name'];
            $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $_SESSION['install_step'] = 3;
            header('Location: ?step=3');
            exit;
        } catch (PDOException $e) {
            $error = 'Ошибка подключения к БД: ' . $e->getMessage();
        }
    }
}

// Шаг 3: Создание админа
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['admin_email'] = $_POST['admin_email'] ?? '';
    $_SESSION['admin_password'] = $_POST['admin_password'] ?? '';
    $_SESSION['site_name'] = $_POST['site_name'] ?? 'PLANETA LAB';
    $_SESSION['site_url'] = $_POST['site_url'] ?? 'https://' . $_SERVER['HTTP_HOST'];
    
    if (empty($_SESSION['admin_email']) || empty($_SESSION['admin_password'])) {
        $error = 'Заполните все поля';
    } elseif (strlen($_SESSION['admin_password']) < 8) {
        $error = 'Пароль должен быть не менее 8 символов';
    } else {
        $_SESSION['install_step'] = 4;
        header('Location: ?step=4');
        exit;
    }
}

// Шаг 4: Установка
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $installId = md5(__DIR__ . $_SERVER['HTTP_HOST'] . time());
    
    // Генерируем config.php
    $configContent = "<?php
/**
 * PLAB CMS Configuration File
 * 
 * @package    PLAB CMS
 * @version    " . PLAB_VERSION . "
 * @generated  " . date('Y-m-d H:i:s') . "
 * @install_id " . $installId . "
 */

define('PLAB_VERSION', '" . PLAB_VERSION . "');
define('PLAB_DB_VERSION', '" . PLAB_DB_VERSION . "');
define('PLAB_INSTALL_ID', '" . $installId . "');

define('DB_HOST', '" . addslashes($_SESSION['db_host']) . "');
define('DB_NAME', '" . addslashes($_SESSION['db_name']) . "');
define('DB_USER', '" . addslashes($_SESSION['db_user']) . "');
define('DB_PASS', '" . addslashes($_SESSION['db_pass']) . "');
define('DB_PREFIX', '" . addslashes($_SESSION['db_prefix']) . "');

define('SITE_NAME', '" . addslashes($_SESSION['site_name']) . "');
define('SITE_URL', '" . addslashes($_SESSION['site_url']) . "');
define('SITE_DESC', 'Музыкальный дистрибьютор');

define('ADMIN_EMAIL', '" . addslashes($_SESSION['admin_email']) . "');

define('PLAB_ENV', 'production');
define('PLAB_DEBUG', false);

\$table_prefix = DB_PREFIX;
";
    
    file_put_contents(__DIR__ . '/config.php', $configContent);
    
    // Создаем структуру папок
    $folders = ['uploads', 'uploads/audio', 'uploads/covers', 'cache', 'logs', 'modules'];
    foreach ($folders as $folder) {
        if (!is_dir(__DIR__ . '/' . $folder)) {
            mkdir(__DIR__ . '/' . $folder, 0755, true);
        }
    }
    
    // Создаем .htaccess для защиты
    $htaccess = "<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>

# Защита важных файлов
<FilesMatch \"^(config\.php|install\.lock|.*\.sql)$\">
    Require all denied
</FilesMatch>

# Защита папки uploads
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
";
    file_put_contents(__DIR__ . '/.htaccess', $htaccess);
    
    // Создаем install.lock
    file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s') . "\nInstall ID: " . $installId);
    
    // Создаем файл с отпечатком (как у WP)
    $versionFile = "<?php
/**
 * PLAB CMS Version Info
 * 
 * @package PLAB CMS
 */
define('PLAB_CORE_VERSION', '" . PLAB_VERSION . "');
define('PLAB_CORE_BUILD', '" . PLAB_BUILD . "');
define('PLAB_CORE_INSTALL', '" . date('Y-m-d') . "');
";
    file_put_contents(__DIR__ . '/includes/version.php', $versionFile);
    
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка PLAB CMS v<?= PLAB_VERSION ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0B0B2A 0%, #03030F 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .installer {
            max-width: 700px;
            width: 100%;
            background: rgba(27, 27, 75, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(170, 0, 255, 0.3);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff, #AA00FF);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .version {
            color: #AA00FF;
            font-size: 12px;
            margin-bottom: 30px;
            letter-spacing: 2px;
        }
        .step {
            display: inline-block;
            background: rgba(170,0,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
            font-size: 14px;
        }
        input, select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(170,0,255,0.3);
            border-radius: 12px;
            color: white;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #AA00FF;
        }
        .btn {
            background: linear-gradient(135deg, #6C2BD9, #AA00FF);
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(170,0,255,0.5);
        }
        .error {
            background: rgba(255,0,0,0.2);
            border: 1px solid #ff4444;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            color: #ff8888;
        }
        .success {
            background: rgba(0,255,0,0.2);
            border: 1px solid #44ff44;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            color: #88ff88;
        }
        .requirements {
            margin: 20px 0;
        }
        .req-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .req-pass { color: #44ff44; }
        .req-fail { color: #ff4444; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .footer a {
            color: #AA00FF;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div style="text-align: center;">
            <h1>PLAB CMS</h1>
            <div class="version">version <?= PLAB_VERSION ?> build <?= PLAB_BUILD ?></div>
        </div>
        
        <div class="step">Шаг <?= $step ?> из 4</div>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($step === 1): ?>
            <h2>Проверка сервера</h2>
            <p style="margin: 15px 0; color: #ccc;">Проверяем, соответствует ли ваш сервер минимальным требованиям</p>
            
            <div class="requirements">
                <?php foreach ($checks as $check): ?>
                <div class="req-item">
                    <span><?= $check[0] ?></span>
                    <span class="<?= $check[1] ? 'req-pass' : 'req-fail' ?>">
                        <?= $check[1] ? '✓' : '✗' ?>
                        <?= $check[2] ? ' (' . $check[2] . ')' : '' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($allPass): ?>
                <form method="POST">
                    <button type="submit" class="btn">Продолжить →</button>
                </form>
            <?php else: ?>
                <div class="error">Ваш сервер не соответствует минимальным требованиям</div>
                <p style="margin-top: 15px;">Рекомендуемые требования:<br>
                PHP 7.4+, MySQL 5.7+, память 128MB+, загрузка файлов 64MB+</p>
            <?php endif; ?>
            
        <?php elseif ($step === 2): ?>
            <h2>Настройка базы данных</h2>
            <p style="margin: 15px 0; color: #ccc;">Введите данные для подключения к MySQL</p>
            
            <form method="POST">
                <div class="form-group">
                    <label>Хост базы данных</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Имя базы данных</label>
                    <input type="text" name="db_name" required>
                </div>
                <div class="form-group">
                    <label>Пользователь базы данных</label>
                    <input type="text" name="db_user" required>
                </div>
                <div class="form-group">
                    <label>Пароль базы данных</label>
                    <input type="password" name="db_pass">
                </div>
                <div class="form-group">
                    <label>Префикс таблиц</label>
                    <input type="text" name="db_prefix" value="plab_">
                </div>
                <button type="submit" class="btn">Проверить подключение →</button>
            </form>
            
        <?php elseif ($step === 3): ?>
            <h2>Создание администратора</h2>
            <p style="margin: 15px 0; color: #ccc;">Укажите данные для входа в админ-панель</p>
            
            <form method="POST">
                <div class="form-group">
                    <label>Название сайта</label>
                    <input type="text" name="site_name" value="PLANETA LAB" required>
                </div>
                <div class="form-group">
                    <label>Email администратора</label>
                    <input type="email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label>Пароль (минимум 8 символов)</label>
                    <input type="password" name="admin_password" required minlength="8">
                </div>
                <button type="submit" class="btn">Продолжить →</button>
            </form>
            
        <?php elseif ($step === 4): ?>
            <h2>Установка PLAB CMS</h2>
            
            <?php if ($success): ?>
                <div class="success">✓ CMS успешно установлена!</div>
                <p style="margin: 20px 0;">Поздравляем! PLAB CMS v<?= PLAB_VERSION ?> готова к работе.</p>
                <p style="margin-bottom: 20px;">Данные для входа:<br>
                <strong>Email:</strong> <?= $_SESSION['admin_email'] ?><br>
                <strong>Пароль:</strong> (указанный при установке)</p>
                <a href="/admin/" class="btn" style="display: inline-block; text-decoration: none;">Войти в админ-панель →</a>
                <a href="/" class="btn" style="display: inline-block; text-decoration: none; background: transparent; border: 1px solid #AA00FF; margin-left: 10px;">Перейти на сайт</a>
            <?php else: ?>
                <div class="requirements">
                    <div class="req-item"><span>Создание конфигурации</span><span class="req-pass">✓</span></div>
                    <div class="req-item"><span>Создание таблиц БД</span><span id="db-status" class="req-pass">... в процессе</span></div>
                </div>
                <form method="POST" id="install-form">
                    <button type="submit" class="btn" id="install-btn">Установить CMS →</button>
                </form>
                <script>
                    document.getElementById('install-form')?.addEventListener('submit', function(e) {
                        document.getElementById('install-btn').textContent = 'Установка...';
                        document.getElementById('install-btn').disabled = true;
                    });
                </script>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <div class="footer">
            <p>PLAB CMS v<?= PLAB_VERSION ?> | <a href="https://planetalab.ru">PLANETA LAB</a> | Космическая CMS для музыкальной дистрибуции</p>
            <p style="margin-top: 10px;">Лицензия MIT | <a href="https://github.com/planetalab/plab-cms">GitHub</a></p>
        </div>
    </div>
</body>
</html>