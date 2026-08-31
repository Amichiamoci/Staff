<?php

use Amichiamoci\Models\Distinta;
use Amichiamoci\Models\User;

assert(isset($user) && $user instanceof User);
assert(isset($P) && is_string(value: $P));
assert(isset($team) && $team instanceof Distinta);

$members = $team->MembriDistinta;
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">
                Distinta squadra: <?= htmlspecialchars(string: $team->Nome) ?>
            </h2>
            <small class="text-muted">
                <?= htmlspecialchars(string: $team->Parrocchia->Nome) ?> ·
                <?= htmlspecialchars(string: $team->Sport->Nome) ?>
            </small>
        </div>
        <?php if ($user->Admin) { ?>
            <a href="<?= $P ?>/teams/edit?id=<?= $team->Id ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square"></i>
                Modifica squadra
            </a>
        <?php } ?>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Membri della squadra</strong>
        </div>
        <ul class="list-group list-group-flush">
            <?php if (count(value: $members) === 0) { ?>
                <li class="list-group-item text-muted">Nessun membro associato a questa squadra.</li>
            <?php } else { ?>
                <?php foreach ($members as $member) { ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-start gap-2">
                                <?php if (!empty($member['HaProblemi'])) { ?>
                                    <i class="bi bi-x-circle-fill text-danger fs-5" title="Problemi"></i>
                                <?php } else { ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5" title="Nessun problema"></i>
                                <?php } ?>
                                <div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars(string: $member['Nome']) ?>
                                    </div>
                                    <?php if (!empty($member['Problemi'])) { ?>
                                        <ul class="small mb-0 mt-2 ps-3 text-danger">
                                            <?php foreach ($member['Problemi'] as $problem) { ?>
                                                <li><?= htmlspecialchars(string: $problem) ?></li>
                                            <?php } ?>
                                        </ul>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
    </div>
</div>
