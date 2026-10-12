<?php

declare(strict_types=1);

// Local-only preview helper. Never deploy this router to a public server.
$root = dirname(__DIR__);
foreach ([
    'APP_ENV' => 'qa',
    'APP_DEBUG' => '0',
    'APP_SECRET' => 'local-qa-preview-only-20261011',
    'DATABASE_URL' => 'sqlite:///' . str_replace('\\', '/', $root . '/var/qa/preview.sqlite'),
] as $name => $value) {
    $_SERVER[$name] = $_ENV[$name] = $value;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/__qa/viewports') {
    $page = $_GET['page'] ?? '/shop';
    $allowed = ['/', '/shop', '/login', '/register', '/account/', '/checkout', '/admin/', '/admin/products/', '/admin/orders/', '/admin/users/', '/admin/messages/'];
    if (!in_array($page, $allowed, true)) {
        http_response_code(400);
        exit('Unsupported preview page.');
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><title>QA responsive viewports</title><style>body{background:#eee;font-family:Arial;margin:20px}section{margin-bottom:30px}iframe{display:block;border:1px solid #777;height:850px}h2{margin:10px 0}</style></head><body><h1>CSS viewport QA: ' . htmlspecialchars($page) . '</h1>';
    foreach ([320, 375, 425, 768, 1024, 1280, 1440, 1920] as $width) {
        echo '<section><h2>' . $width . 'px</h2><iframe title="' . $width . 'px preview" width="' . $width . '" src="' . htmlspecialchars($page) . '"></iframe></section>';
    }
    exit('</body></html>');
}
$resolved = realpath($root . '/public' . $path);
if ($resolved && is_file($resolved) && str_starts_with(str_replace('\\', '/', $resolved), str_replace('\\', '/', $root . '/public/'))) {
    return false;
}
$_SERVER['SCRIPT_FILENAME'] = $root . '/public/index.php';
return require $root . '/public/index.php';
