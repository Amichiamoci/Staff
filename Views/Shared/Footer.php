<div class="container text-center w-100 user-select-none">
    &copy; 
    <?= date(format: "Y") ?>
    - <?= htmlspecialchars(SITE_NAME) ?>

    <?php if (isset($user)) { ?>
        - 
        <a  href="<?= $P ?>/credits"
            title="Vai alla pagina dei crediti" 
            class="text-decoration-none text-reset"
        >
            Crediti
        </a>
    <?php } ?>

    <?php if (defined(constant_name: 'POWERED_BY')) { ?>
        - Alimentato da
        <a  href="<?= POWERED_BY ?>" 
            title="Apri in un'altra scheda" 
            target="_blank"
            class="text-decoration-none text-reset fst-italic"
        >
            Amichiamoci Staff
        </a>
    <?php } ?>
</div>