<?php
echo "<pre>";
$base = __DIR__ . '/';

echo "=== Root directory contents ===\n";
foreach (scandir($base) as $f) {
    if ($f === '.' || $f === '..') continue;
    $type = is_dir($base.$f) ? '[DIR] ' : '[file]';
    echo "$type $f\n";
}

echo "\n=== vendor/ check ===\n";
if (is_dir($base.'vendor')) {
    echo "vendor/ EXISTS\n";
    echo "autoload.php: " . (file_exists($base.'vendor/autoload.php') ? 'YES' : 'NO') . "\n";
    echo "Files in vendor/: " . count(scandir($base.'vendor')) . "\n";
} else {
    echo "vendor/ does NOT exist at this level\n";
    // look for vendor.* folders
    foreach (scandir($base) as $f) {
        if (str_starts_with($f, 'vendor')) echo "Found: $f\n";
    }
}
echo "</pre>";
