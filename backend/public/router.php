<?php

// Router script for PHP built-in server: php -S 127.0.0.1:5000 router.php

$filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (is_file($filePath)) {
    return false;
}

require __DIR__ . '/index.php';
