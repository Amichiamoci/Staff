<?php

use Amichiamoci\Models\StaffBase;
use Amichiamoci\Models\Staff;

if (!isset($staff) || !($staff instanceof StaffBase)) {
    throw new \Exception(message: '$staff variable not set!');
}

?>
<div class="card" id="staff-<?= $staff->Id ?>">
    <div class="card-header font-monospace user-select-none">
        #<?= $staff->Id ?>
    </div>
    <div class="card-body">
        <div class="card-title">
            <?= htmlspecialchars(string: $staff->Nome) ?>
        </div>

        <?php if ($staff instanceof Staff) { ?>

            <?php if ($user->IsAdmin && !empty($staff->CodiceFiscale)) { ?>
                <h6 class="card-subtitle mb-2 user-select-none text-secondary font-monospace">
                    <?= htmlspecialchars(string: $staff->CodiceFiscale) ?>
                </h6>
            <?php } ?>

            <dl class="row">
                <dt class="col-sm-4 text-nowrap">
                    <?php if ($staff->Referente) { ?>
                        <strong>Referente</strong>
                    <?php } else { ?>
                        Parrocchia
                    <?php } ?>
                </dt>
                <dd class="col-sm-8">
                    <a 
                        href="<?= $B ?>/church?id=<?= $staff->Parrocchia->Id ?>"
                        class="link-underline-opacity-0 link-secondary"
                        title="Vedi la parrocchia">
                        <?= htmlspecialchars(string: $staff->Parrocchia->Nome) ?>
                    </a>
                </dd>

                <?php if (isset($staff->Taglia)) { ?>
                    <dt class="col-sm-4">
                        Taglia
                    </dt>
                    <dd class="col-sm-8">
                        <?= htmlspecialchars(string: $staff->Taglia->value) ?>
                    </dd>
                <?php } ?>
                
                <?php if (count(value: $staff->Commissioni) > 0) { ?>
                    <dt class="col-sm-4">
                        <?= count(value: $staff->Commissioni) ?> Commissioni
                    </dt>
                    <dd class="col-sm-8 mb-0">
                        <ul class="list-group list-group-flush p-0 m-0">
                            <?php foreach ($staff->Commissioni as $commissione)  { ?>
                                <li class="list-group-item p-0 border-0">
                                    <?= htmlspecialchars(string: $commissione) ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </dd>
                <?php } else { ?>
                    <p class="col-12 text-warning m-0">
                        <strong>Nessuna commissione!</strong><br>
                        Buuuuu
                    </p>
                <?php } ?>
            </dl>
        <?php } ?>
    </div>
</div>