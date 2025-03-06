<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<?php 
    $show_sport = 'yes'; 
    $hide_edit_icon = 'yes';
    require dirname(path: __DIR__) . '/Shared/Torneo.php';
?>

<?php if (count(value: $partite) === 0) { ?>
    <h2>
        Nessuna partita prevista, per ora.
    </h2>

    <p>
        Attendi che la commissione Tornei generi il calendario
    </p>
<?php } ?>
<div class="row">
    <?php foreach ($partite as $partita) { ?>
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require dirname(path: __DIR__) . '/Shared/Partita.php'; ?>
        </div>
    <?php } ?>
</div>
