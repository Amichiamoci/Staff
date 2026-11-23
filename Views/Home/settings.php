<?php

assert(isset($P) && is_string($P));
assert(isset($csrf_token) && is_string($csrf_token));
assert(isset($subscriptions_enabled) && is_bool($subscriptions_enabled));
assert(isset($teams_enabled) && is_bool($teams_enabled));
assert(isset($tournaments_enabled) && is_bool($tournaments_enabled));
assert(isset($capture_address) && is_string($capture_address));
assert(isset($recaptcha_enabled) && is_bool($recaptcha_enabled));
assert(isset($api_enabled) && is_bool($api_enabled));
assert(isset($session_cookie_secure) && is_bool($session_cookie_secure));
assert(isset($php_info) && is_string($php_info));
?>
<header class="bg-primary-subtle text-primary-emphasis rounded-4 p-4 mb-4">
    <div class="d-flex align-items-center gap-3">
        <span class="bg-body rounded-3 px-3 py-2 fs-4">
            <i
                class="bi bi-sliders"
                aria-hidden="true"
            ></i>
        </span>
        <h1 class="h2 mb-0">Configurazione generale</h1>
    </div>
</header>

<div class="row g-4 align-items-start">
    <div class="col-12 col-lg-7">
        <form
            class="card shadow-sm"
            method="post"
            action="<?= $P ?>/settings"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >
            <fieldset class="card-body p-4">
                <legend class="h5 float-none mb-2">Funzionalità</legend>
                <p class="text-body-secondary mb-3">
                    Con i relativi interruttori spenti, la gestione di iscrizioni, squadre e tornei è riservata agli amministratori.
                </p>
                <div class="list-group">
                    <?php foreach ([
                        'subscriptions_enabled' => [
                            'Accetta nuove iscrizioni',
                            'Gli staffisti possono caricare i certificati per la propria parrocchia anche a iscrizioni chiuse.',
                            $subscriptions_enabled,
                        ],
                        'teams_enabled' => [
                            'Consenti la creazione e la modifica delle squadre',
                            null,
                            $teams_enabled,
                        ],
                        'tournaments_enabled' => [
                            'Consenti la creazione e la modifica dei tornei',
                            'Gli staffisti possono anche aggiungere o rimuovere squadre e generare il calendario.',
                            $tournaments_enabled,
                        ],
                        'api_enabled' => [
                            'Abilita API',
                            'Attiva gli endpoint API e la gestione dei token.',
                            $api_enabled,
                        ],
                    ] as $name => [$label, $description, $enabled]) { ?>
                        <div class="list-group-item px-3 py-0">
                            <div class="form-check form-switch mb-0">
                                <input
                                    class="form-check-input mt-3"
                                    type="checkbox"
                                    role="switch"
                                    id="<?= htmlspecialchars($name) ?>"
                                    name="<?= htmlspecialchars($name) ?>"
                                    value="1"
                                    <?php if ($description !== null) { ?>
                                        aria-describedby="<?= htmlspecialchars($name) ?>-description"
                                    <?php } ?>
                                    <?= $enabled ? 'checked' : '' ?>
                                >
                                <label
                                    class="form-check-label d-block fw-semibold py-3"
                                    for="<?= htmlspecialchars($name) ?>"
                                >
                                    <?= htmlspecialchars($label) ?>
                                </label>
                                <?php if ($description !== null) { ?>
                                    <p
                                        class="small text-body-secondary mb-3"
                                        id="<?= htmlspecialchars($name) ?>-description"
                                    >
                                        <?= htmlspecialchars($description) ?>
                                    </p>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </fieldset>
            <div class="card-footer bg-body-tertiary px-4 py-3">
                <button
                    type="submit"
                    class="btn btn-primary"
                    title="Salva la configurazione"
                >
                    <i
                        class="bi bi-check-lg me-1"
                        aria-hidden="true"
                    ></i>
                    Salva configurazione
                </button>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-5 d-grid gap-4">
        <section class="card shadow-sm">
            <h2 class="card-header h5 bg-body-tertiary px-4 py-3">Stato del sistema</h2>
            <dl class="list-group list-group-flush mb-0">
                <div class="list-group-item px-4 py-3">
                    <dt>Indirizzo di prova per le email di compleanno</dt>
                    <dd class="mb-0 mt-1 text-break">
                        <?php if ($capture_address !== '') { ?>
                            <?= htmlspecialchars($capture_address) ?>
                        <?php } else { ?>
                            <span class="badge rounded-pill text-bg-secondary">Non configurato</span>
                        <?php } ?>
                    </dd>
                </div>
                <div class="list-group-item px-4 py-3 d-flex align-items-center justify-content-between gap-2">
                    <dt>reCAPTCHA</dt>
                    <dd class="mb-0">
                        <span class="badge rounded-pill <?= $recaptcha_enabled ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= $recaptcha_enabled ? 'Attivo' : 'Disattivato' ?>
                        </span>
                    </dd>
                </div>
                <div class="list-group-item px-4 py-3">
                    <dt>Reverse proxy HTTPS</dt>
                    <dd class="mb-0 mt-1">
                        <span class="badge rounded-pill <?= $session_cookie_secure ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= $session_cookie_secure ? 'Cookie sicuri forzati' : 'Cookie sicuri non forzati' ?>
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="card shadow-sm">
            <h2 class="card-header h5 bg-body-tertiary px-4 py-3">Strumenti di amministrazione</h2>
            <ul class="list-group list-group-flush">
                <?php foreach ([
                    ['/file/list', 'Sorgenti file', 'Sfoglia le sorgenti file', 'bi-folder2-open'],
                    ...($api_enabled ? [['/api/admin', 'Token API', 'Gestisci i token API', 'bi-key']] : []),
                ] as [$path, $label, $title, $icon]) { ?>
                    <li class="list-group-item p-0">
                        <a  href="<?= $P ?><?= htmlspecialchars($path) ?>"
                            class="list-group-item-action d-flex align-items-center gap-3 text-decoration-none px-4 py-3"
                            title="<?= htmlspecialchars($title) ?>"
                        >
                            <i
                                class="bi <?= htmlspecialchars($icon) ?> text-primary"
                                aria-hidden="true"
                            ></i>
                            <span class="fw-semibold"><?= htmlspecialchars($label) ?></span>
                            <i
                                class="bi bi-chevron-right ms-auto text-body-secondary"
                                aria-hidden="true"
                            ></i>
                        </a>
                    </li>
                <?php } ?>
                <li class="list-group-item p-0">
                    <button
                        type="button"
                        class="list-group-item-action d-flex align-items-center gap-3 px-4 py-3 border-0 bg-transparent text-start"
                        id="open-php-info"
                        aria-haspopup="dialog"
                        title="Mostra le informazioni generali di PHP"
                    >
                        <i
                            class="bi bi-info-circle text-primary"
                            aria-hidden="true"
                        ></i>
                        <span class="fw-semibold">Informazioni PHP</span>
                    </button>
                </li>
            </ul>
        </section>
    </div>
</div>

<dialog
    class="bg-body text-body border rounded-3 shadow p-0"
    id="php-info-dialog"
    aria-labelledby="php-info-title"
>
    <header class="d-flex align-items-center justify-content-between gap-3 border-bottom px-4 py-3">
        <h1
            class="h5 mb-0"
            id="php-info-title"
        >
            Informazioni generali di PHP
        </h1>
        <form
            method="dialog"
            class="m-0"
        >
            <button
                type="submit"
                class="btn-close"
                aria-label="Chiudi"
                title="Chiudi le informazioni PHP"
                autofocus
            ></button>
        </form>
    </header>
    <?php if ($php_info !== '') { ?>
        <iframe
            class="d-block w-100 border-0"
            title="Output di phpinfo()"
            sandbox
            srcdoc="<?= htmlspecialchars(
                '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:">'
                . $php_info
            ) ?>"
        ></iframe>
    <?php } else { ?>
        <p class="m-0 p-4">phpinfo() non è disponibile su questo server.</p>
    <?php } ?>
</dialog>

<script>
    document.getElementById('open-php-info').addEventListener('click', () => {
        document.getElementById('php-info-dialog').showModal();
    });
</script>
