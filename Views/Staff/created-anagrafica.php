<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<a 
    href="/staff/subscribe?id=<?= $id ?>">
    Iscrivi <?= htmlspecialchars(string: $nome) ?>
</a>

<a 
    href="/staff/new_anagrafica">
    Registra nuova persona
</a>