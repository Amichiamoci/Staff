<?php
$__current_url = $_SERVER['REQUEST_URI'];
if (str_contains($__current_url, "/staff/") || str_ends_with($__current_url, "/staff"))
{
	$__link_href = ADMIN_URL . "/staff/";
} else {
	$__link_href = ADMIN_URL . "/";
}
if ($is_extern)
	$__link_href = DOMAIN_URL;
?>
<!-- Scroll top ------------------------------------------------------------ -->

<div id="top"></div>

<a class="scroll-top flex center vertical" href="#top">
	<i class="fa-solid fa-caret-up"></i>
	<p>TOP</p>
</a>

<!-- Header ---------------------------------------------------------------- -->

<header class="flex center">
	<?php if (isset(User::$Current) && !$is_extern) { ?>
		<span style="user-select: none">
			Ciao, <?= htmlspecialchars(User::$Current->label()) ?>
			&nbsp;
		</span>
	<?php } ?>
	<?php if (!Security::IsFromApp()) { ?>
		<div class="logo">
			<a href="<?= $__link_href ?>">
				<img src="<?= ADMIN_URL . "/assets/logo.png" ?>">
			</a>
		</div>
	<?php } ?>
	<?php if (!$is_extern) { ?>
		<a class="logout" href="<?= ADMIN_URL . "/manage/logout.php" ?>">
			<span>Logout</span>
			<i class="fa-solid fa-sign-out-alt"></i>
		</a>
	<?php } ?>
</header>
<noscript>
	<h2>
		La pagina necessita di Javascript per funzionare.
	</h2>
	<p class="text">
		Abilitalo nel tuo browser.<br>
		Gli script sono necessari solo al funzionamento interno della pagina e non catturano dati degli utenti
	</p>
</noscript>