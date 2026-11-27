<?php

use Amichiamoci\Models\ExternalAssets;

assert(isset($RenderBody) && is_callable($RenderBody), 'var $RenderBody not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($title) && is_string($title), 'var $title not valid');

?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="color-scheme" content="light dark">
        <meta name="robots" content="noindex, nofollow">
        <title>
            <?= htmlspecialchars(SITE_NAME) ?> - <?= htmlspecialchars(string: $title) ?>
        </title>
        
        <?php ExternalAssets::preconnect(); ?>
    
        <link rel="icon"       href="<?= $P ?>/images/icon.png" type="image/png">
        <link rel="stylesheet" href="<?= $P ?>/css/staff.css">
        <link rel="manifest"   href="<?= $P ?>/web_manifest">

        <?php ExternalAssets::bootstrapCSS(); ?>
        <script>const BasePath = <?= json_encode($P, JSON_HEX_TAG | JSON_THROW_ON_ERROR) ?>;</script>
        <script src="<?= $P ?>/js/darkmode.js"></script>
    </head>
    <body class="d-flex flex-column min-vh-100">
        <header
            class="position-relative mb-3"
            id="portal-header"
        >
            <?php require __DIR__ . '/Navbar.php'; ?>
        </header>
        <noscript>
            <h2>
                La pagina necessita di Javascript per funzionare.
            </h2>
            <p>
                Abilitalo nel tuo browser.<br />
                Gli script sono necessari solo al funzionamento interno della pagina e non catturano dati degli utenti
            </p>
        </noscript>

        <?php ExternalAssets::jQuery(); ?>

        <div
            class="container"
            id="swup"
        >
            <?php if (isset($alerts) && is_array($alerts)) { ?>
                <?php foreach ($alerts as $message) { ?>
                    <?php require __DIR__ . '/Message.php'; ?>
                <?php } ?>
            <?php } ?>
            <main class="pb-3">
                <?php $RenderBody(); ?>
            </main>
        </div>
    
        <button
            type="button"
            class="btn btn-secondary btn-floating btn-lg d-none position-fixed z-3"
            data-btn-to-top="true"
            aria-label="Torna su"
            title="Torna su"
            id="scroll-top"
        >
            <i class="bi bi-arrow-up"></i>
        </button>

        <footer
            id="portal-footer"
            class="border-top footer text-muted user-select-none w-100 p-1 mt-auto mb-0"
        >
            <?php require __DIR__ . '/Footer.php'; ?>
        </footer>

        <?php 
        ExternalAssets::jQueryValidation(); 
        ExternalAssets::bootstrapJS(); 
        ?>

        <script src="<?= $P ?>/js/jQuery.bsConfirm.js"></script>
        <script>
            //
            // Form validation
            //
            $.validator.setDefaults({
                validClass: "d-none",
                errorClass: "d-none",
                highlight: function (element, errorClass, validClass) {
                    $(element).addClass("is-invalid").removeClass("is-valid");
                },
                unhighlight: function (element, errorClass, validClass) {
                    $(element).addClass("is-valid").removeClass("is-invalid");
                },
            });
            const initPageControls = () => {
                $('form').each(function () {
                    $(this).validate();
                });
                $('[data-confirm]').bsConfirm();
            };
            $(initPageControls);
            $(document).on('swup:page:view', initPageControls);

            //
            // Scroll to top
            //
            $(window).on('scroll', function() {
                if ($(window).scrollTop() > 300) {
                    $('#scroll-top').removeClass('d-none');
                } else {
                    $('#scroll-top').addClass('d-none');
                }
            });
            $('#scroll-top').on('click', function(e) {
                e.preventDefault();
                window.scrollTo({ top: 0 });
            });

        </script>

        <script src="<?= $P ?>/js/scoreboard.js"     defer></script>
        <script src="<?= $P ?>/js/match-handling.js" defer></script>
        <script src="<?= $P ?>/js/copy-ip.js"        defer></script>
        
        <?php ExternalAssets::swup(); ?>
        <script src="<?= $P ?>/js/navigation.js"     defer></script>
    </body>
</html>