<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="robots" content="noindex, nofollow">
    <title>
        <?= SITE_NAME ?> - <?= htmlspecialchars(string: $title) ?>
    </title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net/" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net/">
    
    <link rel="icon" href="<?= $B ?>/Public/images/icon.png" type="image/png">
    <link rel="shortcut icon" href="<?= $B ?>/Public/images/icon.png" type="image/png">
    <link rel="manifest" href="<?= $B ?>/web_manifest">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet" 
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
        crossorigin="anonymous">
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
        crossorigin="anonymous">
    <script>const BasePath = '<?= $B ?>';</script>
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
        src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
        crossorigin="anonymous"></script>

    <div class="container">
        <?php foreach ($alerts as $message) { ?>
            <?php require __DIR__ . '/Message.php'; ?>
        <?php } ?>
        <main class="pb-3">
            <?php include_once $view_file; ?>
        </main>
    </div>
    
    <button type="button"
            class="btn btn-secondary btn-floating btn-lg d-none position-fixed z-3"
            data-btn-to-top="true"
            style="bottom: 20px; right: 20px;"
            title="Torna su"
            id="scroll-top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <footer class="border-top footer text-muted user-select-none w-100 p-1 mt-auto mb-0">
        <?php include_once __DIR__ . '/Footer.php'; ?>
    </footer>

    <script 
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"
        crossorigin="anonymous"></script>
    <script 
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
        crossorigin="anonymous"></script>
    <script src="<?= $B ?>/Public/js/darkmode.js"></script>
    <script src="<?= $B ?>/Public/js/jQuery.bsConfirm.js"></script>
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
            $('html, body').animate({
                scrollTop: 0,
            }, '300');
        });

        //
        // jQuery BootStrap Confirm plugin (https://github.com/tropotek/bsConfirm)
        //
        jQuery(function($) {
            $('[data-confirm]').bsConfirm();
        });
    </script>
    <script src="<?= $B ?>/Public/js/match-handling.js" defer></script>
</body>
</html>