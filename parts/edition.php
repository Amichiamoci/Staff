<?php 
if (!isset($edizione))
{
    ?><!-- Edizione non impostata prima di chiamare edition.php --><?php
} else {
	$hide_share_link = isset($hide_share_link) && (bool)$hide_share_link;
    ?>
<div class="edizione">
	<?php if ($edizione->ok()) { ?>
		<h3>
			Amichiamoci <?= $edizione->year ?>
		</h3>
		<div class="grid">
			<div class="column col-10 flex center">
				<?php if (isset($edizione->imgpath) && !empty($edizione->imgpath)) { ?>
					<img src="<?= ADMIN_URL ?>/<?= isset($edizione->imgpath) ? $edizione->imgpath : "" ?>"
						title="Logo attuale"
						alt="Logo <?= $edizione->year ?>"
						class="logo-edizione">
				<?php } else { ?>
					<!-- Immagine da definire -->
				<?php } ?>
			</div>
			<div class="column col-90">
				<p class="text center">
					<em style="user-select: none;">
						&quot;
						<?= htmlspecialchars($edizione->motto) ?>
						&quot;
					</em>
					<?php if (!$hide_share_link) { ?>
						<br>
						Link per upload autonomo dati personali:
						<br>
						&rarr;
						<a 
							href="<?= ISCRIZIONI_URL ?>"
							data-share-title="Iscriviti ad Amichiamoci <?= $edizione->year ?>"
							data-share-text="Form di upload dati Amichiamoci"
							target="_blank"
							class="link share">
							Clicca per condividere
						</a>
						&larr;
					<?php } ?>
				</p>
			</div>
		</div>
	<?php } else { ?>
		<h2>Nessuna edizione attiva!</h2>
	<?php } ?>
</div>
<?php
}