<?php
if (!isset($DOMAIN))
{
    $DOMAIN = "https://www.amichiamoci.it";
}
?>
<!-- Meta tags -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (!isset($is_extern) || !$is_extern) { ?>
    <meta http-equiv="refresh" content="108000">
    <link rel="manifest" href="<?= "$DOMAIN/admin/manifest.json" ?>">
<?php } ?>
<meta name="robots" value="noindex,nofollow">
<meta name="author" content="Leonardo Puccini, Riccardo Ciucci">

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">

<!-- Google Fonts -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap">

<!-- Custom stylesheet -->
<link rel="stylesheet" href="<?= "$DOMAIN/admin/assets/css/admin.css" ?>" media="all">

<!-- Shortcut icon & Title -->
<link rel="icon" href="<?= "$DOMAIN/assets/icons/favicon.png" ?>">
<link rel="shortcut icon" href="<?= "$DOMAIN/assets/icons/favicon.png" ?>">


<!-- Scripts --------------------------------------------------------------- -->

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js" defer></script>

<!-- Custom script -->
<script src="<?= "$DOMAIN/admin/assets/js/admin.js?date=" . date("dmY") ?>" defer></script>
