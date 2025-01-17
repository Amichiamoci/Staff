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
                        <?= htmlspecialchars(string: (string)$staff->Taglia) ?>
                    </dd>
                <?php } ?>
                
                <dt class="col-sm-3">
                    Commissioni (<?= count(value: $staff->Commissioni) ?>)
                </dt>
                <dd class="col-sm-9">
                    <ul class="list-group">
                        <?php foreach ($staff->Commissioni as $commissione)  { ?>
                            <li class="list-group-item">
                                <?= htmlspecialchars(string: $commissione) ?>
                            </li>
                        <?php } ?>
                    </ul>
                </dd>
            </dl>
        <?php } ?>
    </div>
</div>