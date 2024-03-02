<div class="column col-50">
    <?= ($include_all === 1 ? "&rarr;<strong>" : "") ?>
    <a href="index.php?year=all" class="link">
        <?php if ($add_link) { ?>
            Vedi iscritti e ISCRIVI gli altri
        <?php } else { ?>
            Vedi sia iscritti che non
        <?php } ?>
    </a>
    <?= ($include_all  === 1 ? "</strong>&larr;" : "") ?>
</div>
<div class="column col-50">
    <?= ($include_all  === 2 ? "&rarr;<strong>" : "") ?>
    <a href="index.php?year=<?= date("Y") ?>" class="link">
        Vedi solo gli iscritti
    </a>
    <?= ($include_all === 2 ? "</strong>&larr;" : "") ?>
</div>

<div class="column col-50">
    <?= ($include_all === 4 ? "&rarr;<strong>" : "") ?>
    <a href="non-iscritti.php?year=<?= date("Y") ?>" class="link">
        Vedi n&eacute; iscritti n&eacute; staff per il <?= date("Y") ?>
    </a>
    <?= ($include_all  === 4 ? "</strong>&larr;" : "") ?>
</div>
<div class="column col-50">
    <?= ($include_all  === 3 ? "&rarr;<strong>" : "") ?>
    <a href="non-iscritti.php?year=all" class="link">
        Vedi persone mai iscritte e mai staff
    </a>
    <?= ($include_all === 3 ? "</strong>&larr;" : "") ?>
</div>

<div class="column col-50">
    <?= ($include_all  === 5 ? "&rarr;<strong>" : "") ?>
    <a href="parrocchia.php" class="link">
        Vedi solo gli iscritti della mia parrocchia
    </a>
    <?= ($include_all  === 5 ? "</strong>&larr;" : "") ?>
</div>
<div class="column col-50">
    <?= ($include_all  === 6 ? "&rarr;<strong>" : "") ?>
    <a href="parrocchia.php?error" class="link">
        Iscrizioni con errori della mia parrocchia
    </a>
    <?= ($include_all  === 6 ? "</strong>&larr;" : "") ?>
</div>
<div class="column col-100 flex vertical">
    <hr>
    <p class="text">
        Se stai usando un computer puoi cercare velocemente chi vuoi tramite <kbd>CTRL + F</kbd>
    </p>
</div>