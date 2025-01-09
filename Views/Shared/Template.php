<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>
        <?= SITE_NAME ?> - <?= htmlspecialchars(string: $title) ?>
    </title>

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet" 
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
        crossorigin="anonymous">
    <link rel="stylesheet" 
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
        crossorigin="anonymous">

    <link rel="icon" href="/Public/images/icon.png" />
</head>
<body>
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
        <main role="main" class="pb-3">
            <?php include_once $view_file; ?>
        </main>
    </div>
    
    <button type="button"
            class="btn btn-secondary btn-floating btn-lg d-none position-fixed z-3"
            data-btn-to-top="true"
            style="bottom: 20px; right: 20px;"
            title="Torna su">
        <i class="bi bi-arrow-up"></i>
    </button>

    <footer class="border-top position-absolute bottom-0 footer text-muted user-select-none w-100 p-1">
        <?php include_once __DIR__ . '/Footer.php'; ?>
    </footer>

    <script 
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"
        crossorigin="anonymous"></script>
    <script 
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
        crossorigin="anonymous"></script>
</body>
</html>