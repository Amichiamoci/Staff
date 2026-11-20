<?php

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
    <link rel="preconnect" href="https://cdn.jsdelivr.net/" crossorigin>
    
    <link rel="icon" href="<?= $P ?>/images/icon.png" type="image/png">
    <link rel="stylesheet" href="<?= $P ?>/css/staff.css">
    <link rel="manifest" href="<?= $P ?>/web_manifest">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" 
        rel="stylesheet"
        integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX"
        crossorigin="anonymous"
        referrerpolicy="no-referrer">
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css"
        rel="stylesheet"
        integrity="sha384-nWN3KqaaVcbEsCjLSV+2jMjh62Iny/+mN3srM8TZeM8N6aj7eDl5wwYggjAJ4/sJ"
        crossorigin="anonymous"
        referrerpolicy="no-referrer">
    <script>const BasePath = <?= json_encode($P, JSON_HEX_TAG | JSON_THROW_ON_ERROR) ?>;</script>
    <script src="<?= $P ?>/js/darkmode.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
    <header>
        <?php include_once __DIR__ . '/Navbar.php'; ?>
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
    <script 
        src="https://cdn.jsdelivr.net/npm/jquery@4.1.0/dist/jquery.min.js"
        integrity="sha384-UGSi5euAzE1p4sK41+yKs3vtdPcidmaW6fY2NUITZkjIPyMVUUYB/BV4A5Oa8uu2"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>

    <div class="container">
        <?php if (isset($alerts) && is_array($alerts)) { ?>
            <?php foreach ($alerts as $message) { ?>
                <?php require __DIR__ . '/Message.php'; ?>
            <?php } ?>
        <?php } ?>
        <main class="pb-3">
            <?php $RenderBody(); ?>
        </main>
    </div>
    
    <button type="button"
            class="btn btn-secondary btn-floating btn-lg d-none position-fixed z-3"
            data-btn-to-top="true"
            aria-label="Torna su"
            title="Torna su"
            id="scroll-top"
    >
        <i class="bi bi-arrow-up"></i>
    </button>

    <footer class="border-top footer text-muted user-select-none w-100 p-1 mt-auto mb-0">
        <?php include_once __DIR__ . '/Footer.php'; ?>
    </footer>

    <script 
        src="https://cdn.jsdelivr.net/npm/jquery-validation@1.22.1/dist/jquery.validate.min.js"
        integrity="sha384-DIFfDxcYkhbAXYdxOYFZshXsis24zK4HtbU7qI30u9/eP7JtiRIGuOaLsoYL5QTs"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script 
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-WyOxWvTyr1QaMPfiPJSv76iReVthorl5uWlFtZPyK0IEB0hGzo9y5Bp9mYanr8Bu"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
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
        $(document).ready(function() {
            $("form").each(function() {
                $(this).validate();
            });
        });

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

        //
        // jQuery BootStrap Confirm plugin (https://github.com/tropotek/bsConfirm)
        //
        jQuery(function($) {
            $('[data-confirm]').bsConfirm();
        });
    </script>
    <script src="<?= $P ?>/js/match-handling.js" defer></script>
</body>
</html>