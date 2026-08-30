<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($title) && is_string($title), 'var $title not valid');

assert(isset($id) && is_int(value: $id), 'var $id not valid');
assert(isset($nome) && is_string($nome), 'var $nome not valid');

?>
<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<a  href="<?= $P ?>/staff/iscrivi?id=<?= $id ?>"
    class="btn btn-primary m-2"
>
    Iscrivi <?= htmlspecialchars(string: $nome) ?>
</a>

<a  href="<?= $P ?>/staff/new_anagrafica"
    class="btn btn-secondary m-2"
>
    Registra nuova persona
</a>