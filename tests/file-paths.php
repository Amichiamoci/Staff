<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleVirtualFS\VirtualFilesystem;

$root = sys_get_temp_dir() . '/staff-file-test-' . bin2hex(random_bytes(8));
$storage = $root . '/data';
mkdir($storage, 0700, true);
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

$fs = new VirtualFilesystem();
$fs->mountLocal('staff', $storage, name: 'Locale (Portale)');
$sourceKey = random_bytes(32);

$rejects = static function (callable $operation): bool {
    try {
        $operation();
        return false;
    } catch (InvalidArgumentException | RuntimeException) {
        return true;
    }
};

try {
    file_put_contents($storage . '/sample & report.txt', 'fixture');
    file_put_contents($root . '/private.txt', 'fixture');
    symlink($root . '/private.txt', $storage . '/link.txt');

    $local = 'staff:///local.pdf';
    $fs->write($local, '%PDF-synthetic');
    $checks = [
        'local writes when Drive is absent' => $local === 'staff:///local.pdf' && $fs->read($local) === '%PDF-synthetic',
        'unregistered Drive' => $rejects(fn() => $fs->read('drive:///documenti/42/sample.pdf')),
        'unregistered App' => $rejects(fn() => $fs->listContents('app:///')),
    ];

    $driveStorage = $root . '/drive';
    mkdir($driveStorage, 0700);
    $fs->mountLocal('drive', $driveStorage, key: $sourceKey);
    $fs->write('drive:///document.pdf', '%PDF-synthetic');
    $fs->write('drive:///certificate.pdf', '%PDF-another-fixture');
    $ciphertext = file_get_contents($driveStorage . '/document.pdf');
    $unkeyed = new VirtualFilesystem();
    $unkeyed->mountLocal('drive', $driveStorage);
    $wrongKey = new VirtualFilesystem();
    $wrongKey->mountLocal('drive', $driveStorage, key: random_bytes(32));
    $checks += [
        'local virtual read' => $fs->read('staff:///sample & report.txt') === 'fixture',
        'both files round-trip with the source key' => $fs->read('drive:///document.pdf') === '%PDF-synthetic'
            && $fs->read('drive:///certificate.pdf') === '%PDF-another-fixture',
        'stored bytes are encrypted' => $ciphertext !== '%PDF-synthetic',
        'encrypted bytes rejected without a key' => $rejects(fn() => $unkeyed->read('drive:///document.pdf')),
        'wrong source key' => $rejects(fn() => $wrongKey->read('drive:///document.pdf')),
        'absolute file' => $rejects(fn() => $fs->read($storage . '/sample & report.txt')),
        'external path' => $rejects(fn() => $fs->read('https://example.invalid/report')),
        'outside storage' => $rejects(fn() => $fs->read('staff:///../private.txt')),
        'outside symlink' => $rejects(fn() => $fs->read('staff:///link.txt')),
        'outside creation' => $rejects(fn() => $fs->write('staff:///../new.txt', 'fixture')) && !file_exists($root . '/new.txt'),
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
    if (isset($driveStorage)) {
        foreach (glob($driveStorage . '/*') as $path) {
            unlink($path);
        }
        rmdir($driveStorage);
    }
    unlink($root . '/private.txt');
    rmdir($storage);
    rmdir($root);
}

echo "File path checks passed.\n";
