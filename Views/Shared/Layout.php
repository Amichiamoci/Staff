<?php

assert(isset($RenderBody) && is_callable($RenderBody), 'var $RenderBody not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($title) && is_string($title), 'var $title not valid');

?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >
        <meta
            name="color-scheme"
            content="light dark"
        >
        <meta
            name="robots"
            content="noindex, nofollow"
        >
        <title>
            <?= htmlspecialchars(SITE_NAME) ?> - <?= htmlspecialchars(string: $title) ?>
        </title>
        <link
            rel="preconnect"
            href="https://cdn.jsdelivr.net/"
            crossorigin
        >
    
        <link
            rel="icon"
            href="<?= $P ?>/images/icon.png"
            type="image/png"
        >
        <link
            rel="stylesheet"
            href="<?= $P ?>/css/staff.css"
        >
        <link
            rel="manifest"
            href="<?= $P ?>/web_manifest"
        >

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        >
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css"
            rel="stylesheet"
            integrity="sha384-nWN3KqaaVcbEsCjLSV+2jMjh62Iny/+mN3srM8TZeM8N6aj7eDl5wwYggjAJ4/sJ"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        >
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
        <script
            src="https://cdn.jsdelivr.net/npm/jquery@4.1.0/dist/jquery.min.js"
            integrity="sha384-UGSi5euAzE1p4sK41+yKs3vtdPcidmaW6fY2NUITZkjIPyMVUUYB/BV4A5Oa8uu2"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        ></script>

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

        <script
            src="https://cdn.jsdelivr.net/npm/jquery-validation@1.22.1/dist/jquery.validate.min.js"
            integrity="sha384-DIFfDxcYkhbAXYdxOYFZshXsis24zK4HtbU7qI30u9/eP7JtiRIGuOaLsoYL5QTs"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-WyOxWvTyr1QaMPfiPJSv76iReVthorl5uWlFtZPyK0IEB0hGzo9y5Bp9mYanr8Bu"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        ></script>
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
        <script
            src="<?= $P ?>/js/match-handling.js"
            defer
        ></script>
        <script
            src="<?= $P ?>/js/copy-ip.js"
            defer
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/swup@4.10.0/dist/Swup.umd.js"
            integrity="sha384-rHeYt38qWf9UmFaUXj3OKA3CqUD0E5hbtwK0lTQQ/YXHyP8t4BvujfDAZKUagHz7"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
            defer
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/@swup/progress-plugin@3.2.0/dist/index.umd.js"
            integrity="sha384-qGoIb27EpcCt2InX00nzAUlU+g/6TKcGXAsVpafUT5IOmEwpmMiN6MyKyGo+2zeu"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
            defer
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/@swup/a11y-plugin@5.2.1/dist/index.umd.js"
            integrity="sha384-/ehE7pXGqdMd2qf4r2PG99PhEeqcRV1Javfrsxby1H5QH5UdTj2KJ6+28CjEsMEB"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
            defer
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/@swup/head-plugin@2.3.1/dist/index.umd.js"
            integrity="sha384-6Ai7SRlZ50QYQ/dc9y6MRZwhlK74Cwab40Sb63ZxrVuoJL9DG9eNpi70b4zL+qvP"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
            defer
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/@swup/scripts-plugin@2.1.0/dist/index.umd.js"
            integrity="sha384-0OOd/P3xw2RWbA6Dv7vjhsqkMf5b+ak1ekw6K+JNstuXFnzUio9JORhTVZD+xcWH"
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
            defer
        ></script>
        <script
            src="<?= $P ?>/js/navigation.js"
            defer
        ></script>
    </body>
</html>