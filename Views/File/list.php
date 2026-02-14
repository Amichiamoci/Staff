<?php
use Amichiamoci\Utils\File;
?>
<h1>
    Indice di <span class="font-monospace"><?= DIRECTORY_SEPARATOR . File::VolumeName ?></span>
</h1>
<?php

function display_tree(
    array $t, 
    string $name, 
    string $base_url = '',
    bool $open = false, 
    int $depth = 1
): void {
    $id = "accord-" . htmlspecialchars(string: $name) . '-' . $depth;
?>
    <div class="accordion" id="<?= $id ?>">
        <div class="accordion-item">
            <h2 class="accordion-header" id="<?= $id ?>-heading">
                <button 
                    class="accordion-button <?= ($open ? "" : "collapsed") ?> font-monospace ps-1"
                    type="button"
                    data-bs-toggle="collapse" 
                    data-bs-target="#<?= $id ?>-body" 
                    aria-expanded="<?= ($open ? "true" : "false") ?>"
                    aria-controls="rules"
                >                    
                    <?= htmlspecialchars(string: $name . DIRECTORY_SEPARATOR) ?>
                    <span class="text-secondary user-select-none">
                        (<?= count(value: $t) ?>)
                    </span>
                </button>
            </h2>

            <div id="<?= $id ?>-body" 
                class="accordion-collapse collapse <?= ($open ? "show" : "") ?>" 
                aria-labelledby="accordion-header" 
                data-parent="#<?= $id ?>"
            >
                <div class="accordion-body">
                    <ul class="list-group">
                        <?php foreach ($t as $key => $value) { ?>
                            <li class="list-group-item ps-2"> 
                                <?php if (is_array(value: $value)) {
                                    display_tree(
                                        t: $value, 
                                        name: $key, 
                                        depth: $depth + 1,
                                        open: false,
                                        base_url: $base_url . DIRECTORY_SEPARATOR . $key
                                    );
                                } else { ?>
                                    <?php 
                                        $path = $base_url . DIRECTORY_SEPARATOR . $value;
                                        if (File::Exists(file_path: $path)) { ?>
                                            <a 
                                                href="<?= File::GetExportUrl(path: $path) ?>" 
                                                class="font-monospace link-underline-1 link-secondary"
                                                download="<?= htmlspecialchars(string: $value) ?>"
                                            ><?= htmlspecialchars(string: $value) ?></a>
                                            <span class="user-select-none">
                                                &nbsp;
                                                (<?= File::Size(file_path: $path) ?>)
                                            </span>
                                    <?php } else { ?>
                                        <span class="font-monospace">
                                            <?= htmlspecialchars(string: $value) ?>
                                        </span>
                                    <?php } ?>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php
}

display_tree(t: $tree, name: '', open: true);