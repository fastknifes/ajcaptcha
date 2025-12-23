<?php
declare(strict_types=1);
(function () {
    $found = false;
    foreach ([
                 'vendor/autoload.php',
                 '../vendor/autoload.php',
                 '../../vendor/autoload.php',
                 '../../../vendor/autoload.php',
                 '../../../../vendor/autoload.php'
             ] as $file) {
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include $file;
            $found = true;
            break;
        }
    }

    // 如果没有找到 composer 的 autoloader，则手动注册一个简单的 PSR-4 加载器
    if (!$found) {
        spl_autoload_register(function ($class) {
            $prefix = 'Fastknife\\';
            $base_dir = dirname(__DIR__) . '/src/';
            
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            if (file_exists($file)) {
                require $file;
            }
        });
    }
})();
