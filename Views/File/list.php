<?php

assert(!isset($source) || is_string($source), 'var $source not valid');
assert(isset($available) && is_bool($available), 'var $available not valid');
assert(isset($folder) && is_string($folder), 'var $folder not valid');
assert(isset($entries) && is_array($entries), 'var $entries not valid');
assert(isset($sources) && is_array($sources), 'var $sources not valid');
assert(isset($P) && is_string($P), 'var $P not valid');

$browse = static fn(string $uri, string $path = ''): string =>
    $P . '/file/list?' . http_build_query(['source' => $uri, 'folder' => $path], arg_separator: '&amp;');

$print_size = static function(int $size): string {
    $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB'];
    $unit = 0;
    while ($size >= 1024 && $unit < array_key_last($units)) {
        $size /= 1024;
        $unit++;
    }
    return number_format($size, $unit === 0 ? 0 : 1, ',', '.') . ' ' . $units[$unit];
};
?>

<h1>Sorgenti file</h1>
<?php if ($sources === []) { ?>
    <p>Nessuna sorgente configurata.</p>
<?php } ?>
<ul class="list-group mb-3">
    <?php foreach ($sources as $uri => $label) { ?>
        <li class="list-group-item">
            <a  href="<?= $browse($uri) ?>"
                class="link-underline link-underline-opacity-0"
                title="Espandi"
            >
                <?= htmlspecialchars($label) ?>
            </a>
            <code><?= htmlspecialchars($uri) ?></code>
        </li>
    <?php } ?>
</ul>

<?php if ($source !== null) { ?>
    <?php if (!$available) { ?>
        <p><?= htmlspecialchars($sources[$source]) ?> non disponibile.</p>
    <?php } else { ?>
        <?php
        $trail = [['label' => $sources[$source], 'path' => '']];
        $path = '';
        foreach ($folder === '' ? [] : explode('/', trim($folder, '/')) as $part) {
            $path = $path === '' ? $part : $path . '/' . $part;
            $trail[] = ['label' => $part, 'path' => $path];
        }
        ?>
        <nav aria-label="Percorso cartella">
            <ol class="breadcrumb">
                <?php foreach ($trail as $index => $crumb) { ?>
                    <li class="breadcrumb-item">
                        <a  href="<?= $browse($source, $crumb['path']) ?>"
                            <?= $index === array_key_last($trail) ? ' aria-current="page"' : '' ?>
                            title="Apri cartella"
                        >
                            <?= htmlspecialchars($crumb['label']) ?>
                        </a>
                    </li>
                <?php } ?>
            </ol>
        </nav>
        <?php if ($entries === []) { ?>
            <p>Questa cartella è vuota.</p>
        <?php } else { ?>
            <ul class="list-group">
                <?php foreach ($entries as $entry) {
                    $path = $entry->path();
                    $uri = $source . $path;
                    $size = $entry->isFile() ? $entry->fileSize() : null;
                ?>
                    <li class="list-group-item">
                        <?php if ($entry->isDir()) { ?>
                            <a  href="<?= $browse($source, $path) ?>"
                                class="link-underline link-underline-opacity-0"
                                title="Espandi"
                            >
                                <?= htmlspecialchars(basename($path)) ?>/
                            </a>
                        <?php } else { ?>
                            <a  href="<?= $P ?>/file/download?name=<?= rawurlencode($uri) ?>"
                                data-no-swup
                                class="link-underline link-underline-opacity-0 link-body-emphasis"
                                title="Scarica"
                            >
                                <?= htmlspecialchars(basename($path)) ?>
                            </a>
                        <?php } ?>
                        <?php if ($size !== null) { ?>
                            <span class="user-select-none"> - </span>
                            <code class="text-body-secondary"><?= $print_size($size) ?></code>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    <?php } ?>
<?php } ?>
