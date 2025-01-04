<div class="container text-center w-100">
    &copy; 
    <?= date(format: "Y") ?> - 
    <?= SITE_NAME ?>
    <?php if (defined(constant_name: 'POWERED_BY')) { ?>
        - Alimentato da
        <a href="<?= POWERED_BY ?>" 
            title="Apri in un'altra scheda" 
            target="_blank"
            class="text-decoration-none text-reset fst-italic">
            Amichiamoci Staff
        </a>
    <?php } ?>
</div>