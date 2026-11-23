<?php

assert(isset($P) && is_string($P), 'var $P not valid');

?>

<div class="container text-center w-100 user-select-none">
    &copy; 
    <?= date(format: "Y") ?>
    - <?= htmlspecialchars(SITE_NAME) ?>

    <?php if (isset($user)) { ?>
        - Alimentato da
        <a  href="<?= $P ?>/about"
            title="Vai alle informazioni sul portale"
            class="text-decoration-none text-reset fst-italic"
        >
            Portale Staff Amichiamoci
        </a>
        - 
        <a  href="<?= $P ?>/credits"
            title="Vai alla pagina dei crediti"
            class="text-decoration-none text-reset"
        >
            Crediti
        </a>
    <?php } ?>
</div>