<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars(string: $title) ?>
    </title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(248, 139, 37);
            padding: 0;
            margin: 0;
            width: 100%;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            margin-inline: auto;
            box-shadow: 0px 0px 10px rgba(206, 202, 202, 0.5);
            max-width: 600px;
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
            <img src="">
        </div>
        <div class="content">
            <?= $content ?>
        </div>
        <div class="footer">
            <p>
                Messaggio inviato automaticamente da
                <a href="<?= $site_url ?>" class="no-underline" target="_blank">
                    <?= htmlspecialchars(string: $site_name) ?>
                </a>.
            </p>
            <p>
                Si prega cortesemente di non rispondere a questa email
            </p>
            <?php if (isset($email_id) && $email_id !== 0) { ?>
                <img src="/view_email" width="1" height="1" style="width: 1px; height: 1px;">
            <?php } ?> 
        </div>
    </div>
</body>
</html>