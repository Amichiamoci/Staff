<?php
    include "../check_login.php";
    
    if (!User::$Current->is_admin)
    {
        header("Location: index.php");
        exit;
    }

    $emails = Iscrizione::EmailNonSubscribed($connection, (int)date("Y"));
?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Email inviate</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<section class="flex center">
    <?= var_dump($emails) ?>
</section>

</div>

</body>

</html>