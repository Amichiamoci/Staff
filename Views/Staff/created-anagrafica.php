<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<a 
    href="<?= $B ?>/staff/iscrivi?id=<?= $id ?>"
    class="btn btn-primary m-2">
    Iscrivi <?= htmlspecialchars(string: $nome) ?>
</a>

<a 
    href="<?= $B ?>/staff/new_anagrafica"
    class="btn btn-secondary m-2">
    Registra nuova persona
</a>