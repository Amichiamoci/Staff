<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Amichiamoci\Utils\File;

$root = sys_get_temp_dir() . '/staff-file-test-' . bin2hex(random_bytes(8));
$storage = $root . '/data';
mkdir($storage, 0700, true);
$_SERVER['DOCUMENT_ROOT'] = $root;
mkdir($root . '/Utils');
copy(dirname(__DIR__) . '/Utils/File.php', $root . '/Utils/File.php');
require $root . '/Utils/File.php';

try {
    file_put_contents($storage . '/sample & report.txt', 'fixture');
    file_put_contents($root . '/private.txt', 'fixture');
    symlink($root . '/private.txt', $storage . '/link.txt');

    $checks = [
        'virtual file' => File::PhysicalPath('staff:///sample & report.txt') === $storage . '/sample & report.txt',
        'absolute file' => File::PhysicalPath($storage . '/sample & report.txt') === $storage . '/sample & report.txt',
        'external path' => File::PhysicalPath('https://example.invalid/report') === false,
        'outside storage' => File::PhysicalPath('staff:///../private.txt') === false,
        'outside symlink' => File::PhysicalPath('staff:///link.txt') === false,
        'outside creation' => File::PhysicalPath('staff:///../new.txt', true) === false && !file_exists($root . '/new.txt'),
        'inside creation' => File::PhysicalPath('staff:///new.txt', true) === $storage . '/new.txt',
        'encoded link' => str_ends_with(File::GetExportUrl('staff:///sample & report.txt'), 'sample%20%26%20report.txt'),
    ];
    foreach ($checks as $label => $passed) {
        if (!$passed) {
            throw new RuntimeException($label);
        }
    }
} finally {
    foreach (glob($storage . '/*') as $path) {
        unlink($path);
    }
    unlink($root . '/private.txt');
    rmdir($storage);
    unlink($root . '/Utils/File.php');
    rmdir($root . '/Utils');
    rmdir($root);
}

echo "File path checks passed.\n";
