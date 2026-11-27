<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($title) && is_string($title), 'var $title not valid');
assert(isset($site_name) && is_string($site_name), 'var $site_name not valid');
assert(isset($site_url) && is_string($site_url), 'var $site_url not valid');
assert(isset($content) && is_string($content), 'var $content not valid');

?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >
        <title>
            <?= htmlspecialchars(string: $title) ?>
        </title>
        <style type="text/css">
            body {
                font-family: Arial, sans-serif;
                background-color: rgb(248, 139, 37);
                padding: 0;
                margin: 0;
                width: 100%;
            }
            .container {
                background-color: rgb(237, 237, 237);
                border-radius: 8px;
                box-shadow: 0px 0px 10px rgba(206, 202, 202, 0.5);
                padding: 20px;

                margin-block: 2em;
                margin-inline: auto;

                min-width: 300px;
                width: auto;
                max-width: 600px;

                height: auto;
            }
            .nav {
                width: 100%;
                margin-top: 0;
                padding: 0;
                background-color: transparent;
            }
            .nav > img {
                width: 100%;
                height: auto;
                max-height: 150px;
                user-select: none;
            }
            .content {
                width: 100%;
            }
            .footer {
                width: 100%;
                color: #6c757d;
                font-size: small;
                text-align: center;
                margin-top: 20px;
            }
            .no-underline {
                text-decoration: none;
            }
            a.no-underline:hover {
                text-decoration: underline;
            }
            dl {
                display: grid;
                grid-gap: 4px 16px;
                grid-template-columns: max-content;
            }
            dt {
                font-weight: bold;
            }
            dd {
                margin: 0;
                grid-column-start: 2;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="nav">
                <img
                    src="data:image/png;base64,<?= base64_encode(
                        string: file_get_contents(filename: dirname(path: __DIR__, levels: 2) . "/Public/images/banner.png")) ?>"
                    alt="Logo <?= htmlspecialchars(string: $site_name) ?>"
                    title="<?= htmlspecialchars(string: $site_name) ?>"
                >
            </div>
            <div class="content">
                <?= $content ?>
            </div>
            <div class="footer">
                <p>
                    Messaggio inviato automaticamente da
                    <a  href="<?= $site_url ?>"
                        class="no-underline"
                        target="_blank"
                        title="Vai al sito"
                    >
                        <?= htmlspecialchars(string: $site_name) ?>
                    </a>
                </p>
                <p>
                    Si prega cortesemente di non rispondere a questa email
                </p>
                <?php if (isset($email_id)) { ?>
                    <img
                        src="https://<?= htmlspecialchars(DOMAIN) ?><?= $P ?>/email/heartbeat?id=<?= $email_id ?>"
                        alt=""
                        width="1"
                        height="1"
                        style="width: 1px; height: 1px;"
                        loading="eager"
                    >
                <?php } ?>
            </div>
        </div>
    </body>
</html>