<?php $edizione = Edizione::Current($connection); ?>
<div class="edizione">
	<?php if ($edizione->ok()) { ?>
		<h3>Amichiamoci <?= $edizione->year ?></h3>
		<div class="grid">
			<div class="column col-10 flex center">
				<img src="/<?=$edizione->imgpath?>"
					title="Logo attuale"
					alt="Logo Amichiamoci <?= $edizione->year ?>"
					class="logo-edizione">
			</div>
			<div class="column col-90">
				<p class="text center">
					<em style="user-select: none;">
						&quot;
						<?= acc($edizione->motto) ?>
						&quot;
					</em>
					<br>
					Link per upload autonomo dati personali:
					<br>
					&rarr;
					<a 
						href="<?= $DOMAIN ?>/admin/form-iscrizione.php"
						data-share-title="Iscriviti ad Amichiamoci <?= $edizione->year ?>"
						data-share-text="Form di upload dati Amichiamoci"
						target="_blank"
						class="link share">
						Clicca per condividere
					</a>
					&larr;
				</p>
			</div>
		</div>
	<?php } else { ?>
		<h2>Nessuna edizione attiva!</h2>
	<?php } ?>
</div>