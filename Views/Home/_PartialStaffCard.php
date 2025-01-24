<?php 
use Amichiamoci\Models\Staff;

if (isset($staff) && $staff instanceof Staff) { 
?>
    <div class="card" id="staff-<?= $staff->Id ?>">
        <div class="card-body">
            <div class="card-title">
                <strong>
                    <?= htmlspecialchars(string: $staff->Nome) ?>
                </strong>
                <a 
                    href="/staff/me" 
                    class="link-underline link-underline-opacity-0 link-primary"
                    title="Modifica i miei dati">
                    <i class="bi bi-pencil-square"></i>
                </a>
            </div>

            <dl class="row">
                <dt class="col-sm-3">
                    <?php if ($staff->Referente) { ?>
                        <strong>Referente</strong>
                    <?php } else { ?>
                        Parrocchia
                    <?php } ?>
                </dt>
                <dd class="col-sm-9">
                    <a 
                        href="/church?id=<?= $staff->Parrocchia->Id ?>"
                        class="link-underline-opacity-0 link-secondary"
                        title="Vedi la parrocchia">
                        <?= htmlspecialchars(string: $staff->Parrocchia->Nome) ?>
                    </a>
                </dd>

                <?php if (isset($staff->Taglia)) { ?>
                    <dt class="col-sm-3">
                        Taglia
                    </dt>
                    <dd class="col-sm-9">
                        <?= htmlspecialchars(string: $staff->Taglia->value) ?>
                    </dd>

                    <dt class="col-sm-3">
                        Commissioni
                    </dt>
                    <dd class="col-sm-9">
                        <ul class="list-group-flush">
                            <?php foreach ($staff->Commissioni as $commissione) { ?>
                                <li class="list-group-item">
                                    <?= htmlspecialchars(string: $commissione) ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </dd>
                <?php } ?>
            </dl>
            <?php if (!isset($staff->Taglia)) { ?>
                <p>
                    Non ti sei ancora registrato per
                    <?= SITE_NAME ?> <?= date(format: "Y") ?>, fallo subito!
                </p>
                <a
                    href=""
                    class="btn btn-primary btn-lg"
                    title="Partecipa">
                    Partecipa ad
                    <?= SITE_NAME ?> <?= date(format: "Y") ?>
                </a>
            <?php } ?>
        </div>
    </div>

<?php } else { ?>
    <p>
        <?= htmlspecialchars(string: $user->Label())?>, diventa uno staffista!
    </p>
    <a 
        href="/staff/me" 
        class="link-underline link-underline-opacity-0 link-primary"
        title="Diventa staff">
        Clicca qui
    </a>
<?php } ?>