<h1>Crediti</h1>
<p>
    <a href="<?= htmlspecialchars($repository) ?>">
        Repository ufficiale di Amichiamoci Staff
    </a>
</p>

<h2>Contributori</h2>
<ul>
    <?php foreach ($contributors as $contributor) { ?>
        <li>
            <?= htmlspecialchars($contributor['name']) ?>
            <?php if (!empty($contributor['homepage'])) { ?>
                <a  href="<?= htmlspecialchars($contributor['homepage']) ?>" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    title="Vai alla pagina di <?= htmlspecialchars($contributor['name']) ?>"
                    class="link-underline link-underline-opacity-0"
                >
                    <i class="bi bi-link-45deg"></i>
                </a>
            <?php } ?>
        </li>
    <?php } ?>
</ul>
<p>
    <a href="<?= htmlspecialchars(rtrim($repository, '/')) ?>/graphs/contributors">
        Tutti i contributi su GitHub
    </a>
</p>

<h2>Tecnologie e licenze</h2>
<dl class="row">
    <?php foreach ($technologies as $technology => $license) { ?>
        <dt class="col-sm-6"><?= htmlspecialchars($technology) ?></dt>
        <dd class="col-sm-6"><?= htmlspecialchars($license) ?></dd>
    <?php } ?>
</dl>

<details>
    <summary class="h5">Pacchetti PHP e relative licenze</summary>
    <dl class="row mt-3">
        <?php foreach ($packages as $package) { ?>
            <dt class="col-sm-6">
                <?= htmlspecialchars($package['name']) ?>
            </dt>
            <dd class="col-sm-6">
                <?= htmlspecialchars(implode(', ', $package['license'] ?? []) ?: 'Non dichiarata') ?>
            </dd>
        <?php } ?>
    </dl>
</details>
