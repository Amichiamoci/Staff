<?php

include "./check_login.php";

$cf = "";
if (isset($_POST["cf"]) && is_string($_POST["cf"]))
{
    $cf = $_POST["cf"];
    $a = Anagrafica::FromCF($connection, $cf);
    if (isset($a) && $a->id !== 0)
    {
        Cookie::Set("id_anagrafica", "$a->id", 3600);
        header("Location: ./diventa-staff.php");
        exit;
    }
}
$cf = htmlspecialchars($cf);
?>

<!DOCTYPE html>
<html>

<head>

	<?php include "./parts/head.php";?>
	<title>Amichiamoci | Area Admin</title>

</head>

<body>

<?php include "./parts/nav.php";?>

<div class="container">

<!-- Admin ----------------------------------------------------------------- -->

<section id="admin" class="admin flex center">
    <div class="grid baseline">

        <div class="column col-50 flex vertical">
			<div class="admin-card flex vertical top">
				<h3>
                    Ho gi&agrave; partecipato ad Amichiamoci dal 2023 in poi
                </h3>
                <form method="post">
                    <label for="cf">
                        Inserisci il tuo codice fiscale
                    </label>
                    <input id="cf" value="<?= $cf ?>" placeholder="Codice fiscale qui" name="cf">

                    <button type="submit">
                        Invia
                    </button>
                </form>
			</div>
        </div>

        <div class="column col-50 flex vertical">
			<div class="admin-card flex vertical top">
				<h3>
                    Non ho partecipato ad Amichiamoci dal 2023 in poi
                </h3>

				<a class="button" href="./diventa-staff.php">Clicca qui</a>
			</div>
        </div>
    </div>

</section>

</div>

</body>

</html>