<?php 
use Amichiamoci\Models\Staff;

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($user) && $user instanceof \Amichiamoci\Models\User, 'var $user not valid');

if (isset($staff) && $staff instanceof Staff) { 
?>
    <div
        class="card"
        id="staff-<?= $staff->Id ?>"
    >
        <div class="card-body">
            <div class="card-title">
                <strong>
                    <?= htmlspecialchars(string: $staff->Nome) ?>
                </strong>
                <a  href="<?= $P ?>/staff/me"
                    class="link-underline link-underline-opacity-0 link-primary"
                    title="Modifica i miei dati"
                >
                    <i class="bi bi-pencil-square"></i>
                </a>
            </div>

            <dl class="row">
                <dt class="col-sm-4">
                    Parrocchia
                </dt>
                <dd class="col-sm-8">
                    <?= htmlspecialchars(string: $staff->Parrocchia->Nome) ?>
                </dd>

                <?php if (isset($staff->Taglia)) { ?>
                    <dt class="col-sm-4">
                        Taglia
                    </dt>
                    <dd class="col-sm-8">
                        <?= htmlspecialchars(string: $staff->Taglia->value) ?>
                    </dd>

                    <dt class="col-sm-4">
                        <a  href="<?= $P ?>/staff/get_involved"
                            class="text-reset"
                            title="Cambia le commissioni a cui partecipi"
                        >
                            Commissioni
                        </a>
                    </dt>
                    <dd class="col-sm-8">
                        <ul class="list-group-flush p-0">
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
                    <?= htmlspecialchars(SITE_NAME) ?> <?= date(format: "Y") ?>, fallo subito!
                </p>
                <a  href="<?= $P ?>/staff/get_involved"
                    class="btn btn-primary btn-lg"
                    title="Partecipa"
                >
                    Partecipa ad
                    <?= htmlspecialchars(SITE_NAME) ?> <?= date(format: "Y") ?>
                </a>
            <?php } ?>
        </div>
    </div>

<?php } else { ?>
    <div
        class="card"
        id="staff-to-create"
    >
        <div class="card-body">
            <div class="card-title">
                <strong>
                    <?= htmlspecialchars(string: $user->Label())?>, diventa uno staffista!
                </strong>
            </div>

            <p>
                Al tuo account non risultano ancora associati dei dati anagrafici.<br>
                Se hai partecipato alla manifestazione in passato ti dovrebbe bastare la
                procedura seguente per diventare uno staffista.
            </p>
            <a  href="<?= $P ?>/staff/me"
                class="link-underline link-underline-opacity-0 link-primary"
                title="Diventa staff"
            >
                Clicca qui
            </a>
        </div>
    </div>
<?php } ?>