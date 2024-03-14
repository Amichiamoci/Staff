<?php

include "./check_login.php";

Cookie::DeleteIfExists("form");
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
		<?php if (User::$Current->staff_id === 0) { ?>
			
			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">
					<h3>
						<?= htmlspecialchars(User::$Current->label()) ?>, diventa staffista!
					</h3>

					<a class="button" href="./staff/registrati.php">Vai</a>
				</div>
			</div>

		<?php } else { ?>

			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">
					<h3>Men&ugrave; staffista</h3>

					<a class="button" href="./staff/index.php">Vai</a>
				</div>
			</div>

		<?php } ?>

		<?php if (User::$Current->is_admin) { ?>

			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">
					<h3>Sistema: <?= getSystemStatus($connection) ?></h3>

					<a class="button" href="maintenance.php">Cambia</a>
					<br>

					<a class="button" href="./manage/email_list.php">Lista Email</a>
					<br>

					<p class="text">
						<strong>Ultimo controllo di</strong>
						<br>

						<a class="link" href="javascript:TestLinkAndReload('./cron/compleanno.php')">
							Compleanni oggi
						</a>: <output data-load-url="./cron/last-compleanni-oggi.txt"></output>
						<br>
						
						<a class="link" href="javascript:TestLinkAndReload('./cron/partite_oggi.php')">
							Partite oggi
						</a>: <output data-load-url="./cron/last-partite-oggi.txt"></output>
					</p>
				</div>
			</div>

			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">
					<h3>UTENTI</h3>

					<a class="button" href="all-users.php">Vedi Lista</a>
					<br>

					<a class="button" href="users-activity.php">Vedi attivit&agrave;</a>
					<br>

					<a class="button" href="create-user.php">Crea nuovo</a>
				</div>
			</div>

		<?php } ?>

        <div class="column col-50 flex vertical">
			<div class="admin-card flex vertical top">
				<h3>Modifica utente</h3>

				<a class="button" href="manage/cambia.php?action=username">Cambia Username</a>
				<br>

				<a class="button" href="manage/cambia.php?action=password">Cambia password</a>
			</div>
        </div>

        <div class="column col-50 flex vertical">
			<div class="admin-card flex vertical top">
				<h3>Elenchi</h3>

				<a class="button" href="./anagrafiche.php">Anagrafiche presenti</a>
				<br>

				<a class="button" href="./staff-list.php">Staffisti attuali (<?= date("Y")?>)</a>
				<br>

				<a class="button" href="./staff-list.php?tutti">Tutti gli staffisti</a>
				<br>

				<a class="button" href="./csi.php">Lista tesseramenti C.S.I.</a>
			</div>
        </div>

    </div>

</section>

</div>

</body>

</html>