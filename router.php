<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (file_exists($index)) {
        require $index;
        return true;
    }
}

if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}

if (file_exists($file . '.php')) {
    require $file . '.php';
    return true;
}

require __DIR__ . '/index.php';
return true;
