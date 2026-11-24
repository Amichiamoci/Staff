<?php

assert(isset($app_version) && is_string($app_version), 'var $app_version not valid');
assert(isset($php_version) && is_string($php_version), 'var $php_version not valid');
assert(isset($domain) && is_string($domain), 'var $domain not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($packages) && is_array($packages), 'var $packages not valid');

?>

<header class="rounded-4 bg-primary-subtle bg-gradient text-primary-emphasis border border-primary-subtle shadow-sm px-4 py-4 p-md-5 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
        <div>
            <p class="fw-semibold mb-2">Portale Staff Amichiamoci</p>
            <h1 class="display-6 fw-bold mb-0">Informazioni sul portale</h1>
        </div>
        <a  href="<?= $P ?>/credits"
            class="btn btn-primary"
            title="Consulta i crediti e le licenze del portale"
        >
            <i  class="bi bi-info-circle me-2"
                aria-hidden="true"
            ></i>
            Crediti e licenze
        </a>
    </div>
</header>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <article class="card rounded-4 shadow-sm overflow-hidden">
            <h2 class="card-header bg-body-tertiary h5 px-4 py-3">
                <i  class="bi bi-hdd-network text-primary me-2"
                    aria-hidden="true"
                ></i>
                Installazione
            </h2>
            <dl class="list-group list-group-flush mb-0">
                <div class="list-group-item px-4 py-3">
                    <dt class="small text-body-secondary">Versione del portale</dt>
                    <dd class="font-monospace fw-semibold text-break mb-0"><?= htmlspecialchars($app_version) ?></dd>
                </div>
                <div class="list-group-item px-4 py-3">
                    <dt class="small text-body-secondary">Versione PHP</dt>
                    <dd class="font-monospace fw-semibold text-break mb-0"><?= htmlspecialchars($php_version) ?></dd>
                </div>
                <div class="list-group-item px-4 py-3">
                    <dt class="small text-body-secondary">Dominio</dt>
                    <dd class="font-monospace fw-semibold text-break mb-0"><?= htmlspecialchars($domain) ?></dd>
                </div>
                <div class="list-group-item px-4 py-3">
                    <dt class="small text-body-secondary">Percorso di installazione</dt>
                    <dd class="font-monospace fw-semibold text-break mb-0"><?= $P !== '' ? $P : '/' ?></dd>
                </div>
            </dl>
        </article>
    </div>

    <div class="col-12 col-lg-7">
        <article class="card rounded-4 shadow-sm overflow-hidden">
            <h2 class="card-header bg-body-tertiary h5 px-4 py-3">
                <i  class="bi bi-box-seam text-primary me-2"
                    aria-hidden="true"
                ></i>
                Librerie installate
            </h2>
            <dl class="list-group list-group-flush mb-0">
                <?php foreach ($packages as $name => $version) { ?>
                    <div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3">
                        <dt class="text-break fw-medium"><?= htmlspecialchars($name) ?></dt>
                        <dd class="badge bg-primary-subtle text-primary-emphasis rounded-pill font-monospace mb-0"><?= htmlspecialchars($version) ?></dd>
                    </div>
                <?php } ?>
            </dl>
        </article>
    </div>
</div>
