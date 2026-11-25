<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($title) && is_string($title), 'var $title not valid');
assert(isset($tokens) && is_array($tokens), 'var $tokens not valid');

?>

<header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <h1 class="mb-0"><?= htmlspecialchars($title) ?></h1>
    <a  href="<?= $P ?>/api/logins"
        class="btn btn-outline-primary"
        title="Consulta gli accessi delle applicazioni"
    >
        Accessi Web App
    </a>
</header>

<?php if (isset($new_key)) { ?>
    <?php assert(is_string($new_key)); ?>
    <div
        class="alert alert-info alert-dismissible fade show"
        role="alert"
    >
        <p class="fw-semibold mb-1">Token generato</p>
        <code class="d-block text-body-emphasis text-break user-select-all"><?= htmlspecialchars($new_key) ?></code>
        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Chiudi"
            title="Chiudi l’avviso del token generato"
        ></button>
    </div>
<?php } ?>

<div class="row g-4">
    <section
        class="col-lg-5"
        aria-labelledby="new-token-heading"
    >
        <form
            action="<?= $P ?>/api/admin"
            method="post"
            class="border rounded-3 p-3 p-sm-4"
            aria-labelledby="new-token-heading"
        >
            <h2
                id="new-token-heading"
                class="h5 mb-3"
            >
                Genera un token
            </h2>
            <div class="form-floating mb-3">
                <input
                    type="text"
                    class="form-control"
                    id="token-name"
                    name="token_name"
                    placeholder="Nome applicazione"
                    maxlength="256"
                    required
                >
                <label for="token-name">Nome applicazione</label>
                <div class="invalid-feedback">Inserisci il nome dell’applicazione.</div>
            </div>
            <button
                type="submit"
                class="btn btn-primary"
                title="Genera un nuovo token Web App"
            >
                Genera token
            </button>
        </form>
    </section>

    <section
        class="col-lg-7"
        aria-labelledby="active-tokens-heading"
    >
        <h2
            id="active-tokens-heading"
            class="h5 mb-3"
        >
            Token attivi (<?= count($tokens) ?>)
        </h2>
        <?php if ($tokens === []) { ?>
            <p class="border rounded-3 p-3 mb-0">Nessun token attivo.</p>
        <?php } else { ?>
            <ul class="list-group">
                <?php foreach ($tokens as $token) { ?>
                    <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3 p-3">
                        <div class="text-break">
                            <span
                                id="token-<?= $token->Id ?>"
                                class="fw-semibold"
                            >
                                <?= htmlspecialchars($token->Nome) ?>
                            </span>
                            <span class="d-block small text-body-emphasis">#<?= $token->Id ?></span>
                        </div>
                        <form
                            action="<?= $P ?>/api/delete_token"
                            method="post"
                            aria-labelledby="revoke-token-<?= $token->Id ?> token-<?= $token->Id ?>"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $token->Id ?>"
                            >
                            <button
                                id="revoke-token-<?= $token->Id ?>"
                                type="submit"
                                class="btn btn-outline-danger"
                                title="Revoca il token di <?= htmlspecialchars($token->Nome) ?>"
                                data-confirm="Vuoi revocare questo token?"
                                data-confirm-btn="Sì, revoca"
                                data-cancel-btn="Annulla"
                            >
                                Revoca
                            </button>
                        </form>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </section>
</div>
