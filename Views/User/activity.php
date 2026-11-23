<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($activity) && is_array($activity), 'var $activity not valid');

?>

<h1>
    Attività degli Utenti
</h1>

<div class="row">
    <div class="col-md-12">
        <ul class="list list-inline mt-3">
            <?php foreach ($activity as $login) { ?>
                <li class="d-flex justify-content-between m-2 border border-1 p-1"
                    style="max-width: 800px;"
                >
                    <?php require __DIR__ . '/LoginRender.php'; ?>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>