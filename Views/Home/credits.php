<?php

assert(isset($repository) && is_string($repository), 'var $repository not valid');
assert(isset($contributors) && is_array($contributors), 'var $contributors not valid');
assert(isset($technologies) && is_array($technologies), 'var $technologies not valid');
assert(isset($packages) && is_array($packages), 'var $packages not valid');

?>

<header class="bg-primary-subtle text-primary-emphasis rounded-4 px-4 py-4 p-md-5 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="display-6 fw-bold mb-1">Crediti</h1>
            <p class="mb-0">Portale Staff Amichiamoci</p>
        </div>
        <a  href="<?= htmlspecialchars($repository) ?>"
            class="btn btn-primary"
            title="Repository ufficiale di Amichiamoci Staff"
        >
            <i  class="bi bi-github me-2"
                aria-hidden="true"
            ></i>
            Repository ufficiale
        </a>
    </div>
</header>

<div class="row g-4 mb-4 align-items-start">
    <div class="col-12 col-lg-4">
        <article class="card shadow-sm">
            <h2 class="card-header bg-body-tertiary h5 px-4 py-3">
                <i  class="bi bi-people text-info me-2"
                    aria-hidden="true"
                ></i>
                Contributori
            </h2>
            <ul class="list-group list-group-flush">
                <?php foreach ($contributors as $contributor) { ?>
                    <li class="list-group-item p-0">
                        <?php if (!empty($contributor['homepage'])) { ?>
                            <a  href="<?= htmlspecialchars($contributor['homepage']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="list-group-item-action d-flex justify-content-between align-items-center gap-3 text-decoration-none fw-semibold px-4 py-3"
                                title="Vai alla pagina di <?= htmlspecialchars($contributor['name']) ?>"
                            >
                                <span class="text-break"><?= htmlspecialchars($contributor['name']) ?></span>
                                <i  class="bi bi-box-arrow-up-right text-body-secondary flex-shrink-0"
                                    aria-hidden="true"
                                ></i>
                            </a>
                        <?php } else { ?>
                            <span class="d-block fw-semibold text-break px-4 py-3">
                                <?= htmlspecialchars($contributor['name']) ?>
                            </span>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
            <footer class="card-footer bg-body-tertiary px-4 py-3">
                <a  href="<?= htmlspecialchars(rtrim($repository, '/')) ?>/graphs/contributors"
                    class="link-body-emphasis link-underline-opacity-0 link-underline-opacity-100-hover"
                    title="Tutti i contributori su GitHub"
                >
                    Tutti i contributori su GitHub
                </a>
            </footer>
        </article>
    </div>

    <div class="col-12 col-lg-8">
        <article class="card shadow-sm">
            <h2 class="card-header bg-body-tertiary h5 px-4 py-3">
                <i  class="bi bi-code-slash text-info me-2"
                    aria-hidden="true"
                ></i>
                Tecnologie e licenze
            </h2>
            <dl class="row g-0 mb-0">
                <?php foreach ($technologies as $technology => $license) { ?>
                    <div class="col-12 col-sm-6 col-xl-4 px-4 py-2 border-bottom">
                        <dt class="text-break"><?= htmlspecialchars($technology) ?></dt>
                        <dd class="text-body-secondary mb-0"><?= htmlspecialchars($license) ?></dd>
                    </div>
                <?php } ?>
            </dl>
        </article>
    </div>
</div>

<details class="card shadow-sm">
    <summary class="px-4 py-3 fs-5 fw-semibold">
        <i  class="bi bi-box-seam text-info me-2"
            aria-hidden="true"
        ></i>
        Pacchetti PHP e relative licenze
    </summary>
    <dl class="row g-0 mb-0">
        <?php foreach ($packages as $package) { ?>
            <div class="col-12 col-sm-6 col-xl-4 px-4 py-3 border-top">
                <dt class="font-monospace text-break"><?= htmlspecialchars($package['name']) ?></dt>
                <dd class="text-body-secondary mb-0">
                    <?= htmlspecialchars(implode(', ', $package['license'] ?? []) ?: 'Non dichiarata') ?>
                </dd>
            </div>
        <?php } ?>
    </dl>
</details>
