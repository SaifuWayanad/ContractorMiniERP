<?php
// Storage must be at the same level as this file (public_html/storage/)
$base = __DIR__ . '/';

// Remove broken symlink if it exists
$storageLink = $base . 'storage';
if (is_link($storageLink)) {
    unlink($storageLink);
    echo "Removed broken storage symlink<br>";
}

$dirs = [
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'storage/logs',
];

foreach ($dirs as $dir) {
    $path = $base . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
        echo "Created: $dir<br>";
    } else {
        echo "Exists:  $dir<br>";
    }
}

// Verify Laravel can write
$testFile = $base . 'storage/logs/laravel.log';
if (file_put_contents($testFile, '[' . date('Y-m-d H:i:s') . '] storage fix applied' . PHP_EOL, FILE_APPEND)) {
    echo "<br><strong style='color:green'>Storage is writable. Delete this file now.</strong>";
} else {
    echo "<br><strong style='color:red'>Storage created but not writable — check permissions.</strong>";
}
