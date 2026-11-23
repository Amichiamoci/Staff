<?php

use Amichiamoci\Models\User;

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($target) && $target instanceof User, 'var $target not valid');
assert(isset($csrf_token) && is_string($csrf_token), 'var $csrf_token not valid');

$actions = [
    $target->IsBanned ? 'restore' : 'ban' => $target->IsBanned
        ? ['Riabilita account', 'Consenti di nuovo l’accesso a questo account?', 'btn-outline-success']
        : ['Blocca account', 'Impedire l’accesso a questo account?', 'btn-outline-warning'],
    'reset' => ['Reimposta password', 'Generare una nuova password per questo account?', 'btn-outline-secondary'],
    'delete' => ['Elimina account', 'Eliminare definitivamente questo account? L’operazione non può essere annullata.', 'btn-outline-danger'],
];

?>

<section class="border rounded p-3 mb-4">
    <h2 class="h4">Gestione account</h2>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($actions as $action => [$label, $confirmation, $style]) { ?>
            <form
                action="<?= $P ?>/user/<?= htmlspecialchars($action) ?>"
                method="post"
            >
                <input
                    type="hidden"
                    name="target_id"
                    value="<?= $target->Id ?>"
                >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrf_token) ?>"
                >
                <button
                    type="submit"
                    class="btn <?= htmlspecialchars($style) ?>"
                    title="<?= htmlspecialchars($label) ?>"
                    data-confirm="<?= htmlspecialchars($confirmation) ?>"
                    data-confirm-btn="<?= htmlspecialchars($label) ?>"
                    data-cancel-btn="Annulla"
                >
                    <?= htmlspecialchars($label) ?>
                </button>
            </form>
        <?php } ?>
    </div>
</section>
