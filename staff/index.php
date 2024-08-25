<?php

include "../check_login.php";

if (isset($_COOKIE["esit"]) && !is_array($_COOKIE["esit"]))
{
	$esit = $_COOKIE["esit"];
	Cookie::Delete("esit");
}

Cookie::DeleteIfExists("form");

$dati_staff = Staff::Get($connection, User::$Current->staff_id);
if (!isset($dati_staff))
{
	header("Location: ../index.php");
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>

    <?php include "../parts/head.php";?>

	<title>Amichiamoci | Area Staff</title>

	<style type="text/css">

		#cambia-parrocchia::after {
			opacity: 0;
			content: '| Clicca per cambiare';
			user-select: none;
			transition: opacity ease-in-out .8s;
		}

		#cambia-parrocchia:hover::after {
			opacity: 1;
		}

	</style>

</head>
<body>

<?php include "../parts/nav.php";?>

<div class="container">

<!-- Staff ----------------------------------------------------------------- -->

<?php if (isset($esit)) { ?>

	<p class="text" style="padding-inline: 1.5em;user-select: none;">
		<?= $esit ?>
	</p>

<?php } ?>

<?php include "./curr-edition.php"; ?>

<hr>

<?php if (!$edizione->ok()) { ?>
	<h1>
		Crea un'edizione per poter proseguire
	</h1>
	
	<!-- Close the stream to prevent errors -->
	</div>
	</body>
	</html>
<?php exit;} ?>

<div class="staff-data">
	<p class="text" style="text-align: center;">
		<?= ($dati_staff->is_subscribed() && $dati_staff->is_referente) ? "Referente" : "Staffista"?>
		per 
		<a href="diventa-staff.php?cambia-parrocchia" title="Cambia parrocchia" id="cambia-parrocchia">
			<?= htmlspecialchars($dati_staff->parrocchia) ?>
		</a>
	</p>
</div>

<hr>

<section id="staff" class="admin flex center">

    <div class="grid baseline">

		<!-- Visualizzazione stato staff -->
		<div class="column col-33 flex vertical">
			<div class="admin-card flex vertical top">

				<?php if (!$dati_staff->is_subscribed()) { ?>
				
					<h3>Registrati per il <?= $edizione->year ?></h3>
					<a class="button" href="./partecipa.php">
						Indica maglia e commissioni
					</a>
				
				<?php } else { ?>
				
					<h3>Sei staff per il <?= $edizione->year ?></h3>
					<p class="text">
						Taglia maglia: <?= $dati_staff->maglia ?> <br>
						Commissioni: 
						<a href="./partecipa.php" title="Cambia commissioni">
							<?= htmlspecialchars($dati_staff->commissioni) ?>
						</a>
					</p>
				<?php } ?>
				<br>
				<a class="button" href="../index.php">
                    Torna al Men&ugrave; utente
                </a>
			</div>
		</div>

		<!-- 
		<div class="column col-33 flex vertical">
			<div class="admin-card flex vertical top">

				<h3>Partite</h3>
				<a class="button" href="./tornei/punteggio-partite.php">
                    &rarr; Inserisci punteggio &larr;
                </a>

			</div>
        </div>
		-->
		<?php if (User::$Current->is_admin) { ?>

			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">

					<h3>Classifica</h3>
					<a class="button" href="./punteggi-parrocchie.php">
						&rarr; Punteggi parrocchie &larr;
					</a>

				</div>
			</div>

		<?php } ?>

		<?php if (($dati_staff->is_subscribed() && $dati_staff->is_referente) || User::$Current->is_admin) { ?>

			<!-- Procedure di iscrizione -->
			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">

					<h3>ISCRIZIONI</h3>
					<a class="button" href="./iscrizioni">
						Iscrivi persone gi&agrave; registrate
					</a>
					<br>
					<a class="button" href="./iscrizioni/crea-anagrafica.php?success=iscrivi.php">
						Registra e iscrivi direttamente persona
					</a>
					<br>
					<a class="button" href="./maglie.php">
						Vedi o esporta lista maglie
                	</a>
				
				</div>
			</div>

			

			<!-- Crea Squadra -->
			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">

					<h3>SQUADRE</h3>
					<a class="button" href="./squadre/crea.php">
						Crea nuova
					</a>
					<br>
					<a class="button" href="./squadre/">
						Vedi e modifica
					</a>

				</div>
			</div>

		<?php } else { ?>

			<!-- Anagrafiche con restrizioni -->

			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">

					<h3>Non sei il refrente della tua parrocchia</h3>
					<p class="text">
						Solo i ferenti possono iscrivere alla manifestazione.<br>
						Puoi comunque inserire i dati anagrafici di esterni
					</p>

				</div>
			</div>

			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">

					<h3>LISTE</h3>
					<a class="button" href="./iscrizioni">
						Lista anagrafiche
					</a>
					<br>
					<a class="button" href="./squadre">
						Lista squadre
					</a>
					<br>
					<a class="button" href="./maglie.php">
						Lista maglie
                	</a>

				</div>
			</div>

		<?php } ?>


		<!-- Tornei -->
		<?php if ($dati_staff->is_in("Tornei") || User::$Current->is_admin || $dati_staff->is_referente) { ?>

			<div class="column col-33 flex vertical">
				<div class="admin-card flex vertical top">

					<h3>TORNEI</h3>
					<a class="button" href="./tornei/crea.php">
						Crea
					</a>
					<br>
					<a class="button" href="./tornei/iscrivi.php">
						Iscrivi squadra
					</a>
					<br>
					<a class="button" href="./tornei">
						Vedi
					</a>

				</div>
			</div>

		<?php } ?>

		<!-- Anagrafica generica -->
		<div class="column col-33 flex vertical">
			<div class="admin-card flex vertical top">

				<h3>Crea anagrafica generica</h3>
				<a class="button" href="./iscrizioni/crea-anagrafica.php">
                    Vai
                </a>

				<!-- Riguardo all'account -->
				<?php if ($dati_staff->valid_cf()) { ?>
					<br>
					<a class="button" href="./iscrizioni/crea-anagrafica.php?cf=<?= $dati_staff->cf ?>">
						Modifica i miei dati
					</a>

			<?php } ?>

			</div>
        </div>

		<!-- Eventi -->
		<div class="column col-33 flex vertical">
			<div class="admin-card flex vertical top">

				<h3>Eventi</h3>
				<a class="button" href="./eventi/crea.php">
                    Crea
                </a>

			</div>
        </div>

		<!-- APP -->
        <div class="column col-33 flex vertical">
			<div class="admin-card flex vertical top">

				<h3>App</h3>
				<a class="button" href="./crea-messaggio.php">
                    Invia messaggio
                </a>
				<br>
				<a class="button" href="../../app/" target="_blank">
                    Visualizza Homepage dal Browser
                </a>

			</div>
        </div>

    </div>

</section>

</div>

</body>

</html>