<?php

use Amichiamoci\Models\ExternalAssets;

assert(isset($RenderBody) && is_callable($RenderBody));
assert(isset($P) && is_string($P));
assert(isset($title) && is_string($title));

?>
<!doctype html>
<html
    lang="it"
    data-bs-theme="dark"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>
            <?= htmlspecialchars($title) ?>
        </title>
        
        <?php ExternalAssets::preconnect(); ?>
    
        <link rel="icon" href="<?= $P ?>/images/icon.png" type="image/png">

        <?php ExternalAssets::bootstrapCSS(); ?>
        <link href="<?= $P ?>/css/staff.css" rel="stylesheet">

        <script>const BasePath = <?= json_encode($P, JSON_HEX_TAG | JSON_THROW_ON_ERROR) ?>;</script>
        <?php ExternalAssets::jQuery(); ?>
        <script src="<?= $P ?>/js/scoreboard.js" defer></script>
    </head>
    <body>
        <main
            id="scoreboard-view"
            class="match-scoreboard-view d-flex flex-column bg-body text-body"
        >
            <?php $RenderBody(); ?>
        </main>
    </body>
</html>
