<h1>
    <?= htmlspecialchars(string: $title) ?>
    (<?= count(value: $tokens) ?>)
</h1>

<?php if (isset($new_key)) { ?>
    <div class="alert alert-info user-select-text alert-dismissible fade show d-flex" role="alert">
        <div>
            <span class="user-select-none">
                Token generato:
            </span>
            <strong>
                <?= htmlspecialchars(string: $new_key) ?>
            </strong>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>

<ul class="list-group">
    <?php foreach ($tokens as $token) { ?>
        <li class="list-group-item">
            #<?= $token->Id ?>
            -
            <?= htmlspecialchars(string: $token->Nome) ?>

            <form action="<?= $P ?>/api/delete_token" method="post" class="p-0 d-inline">
                <input type="hidden" name="id" value="<?= $token->Id ?>">
                <button
                    type="submit"
                    class="btn btn-link link-danger link-underline link-underline-opacity-0 p-0"
                    title="Elimina il token"
                    data-confirm="Sei sicuro di voler eliminare il token?"
                    data-confirm-btn="Sì, elimina"
                    data-cancel-btn="Annulla"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </form>
        </li>
    <?php } ?>
</ul>

<form action="<?= $P ?>/api/admin" method="post" class="m-2 p-2 border border-1 rounded">
    <h3>
        Genera nuovo token
    </h3>
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="name" name="token_name">
        <label for="name">Nome</label>
        <div class="invalid-feedback">
            Per favore, immetti un nome per il token
        </div>
    </div>

    <button 
        type="submit"
        class="btn btn-outline-primary"
    >
        Genera
    </button>
</form>