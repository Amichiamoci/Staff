<?php
use Amichiamoci\Models\{Edizione, User};

assert(isset($P) && is_string($P));
assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($show_double_subscriptions) && is_bool($show_double_subscriptions));
?>

<header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <h1 class="h3 mb-0 user-select-none">
        <?php if (isset($current_edition)) { ?>
            <?php
            assert($current_edition instanceof Edizione);
            $motto = trim($current_edition->Motto ?? '');
            if ($motto !== '') {
            ?>
                <?= $current_edition->Year ?>: <em><?= htmlspecialchars($motto) ?></em>
            <?php } else { ?>
                <?= $current_edition->Year ?>
            <?php } ?>
        <?php } else { ?>
            Panoramica
        <?php } ?>
    </h1>
    <a  href="<?= $P ?>/statistics"
        class="btn btn-outline-info"
        title="Vedi le statistiche su partecipazione, magliette, luoghi di nascita e provider email"
    >
        <i  class="bi bi-bar-chart me-1"
            aria-hidden="true"
        ></i>
        Altre statistiche
    </a>
</header>

<?php require __DIR__ . '/Partial/BirthDays.php'; ?>
<?php require __DIR__ . '/Partial/LiveMatches.php'; ?>

<div class="container dashboard-cards">
    <div class="row">
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/StaffCard.php'; ?>
        </div>
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/T-Shirts.php'; ?>
        </div>
        <?php if ($show_double_subscriptions) { ?>
            <div class="col col-xs-6 col-sm-4 mb-2">
                <?php require_once __DIR__ . '/Partial/DoubleSubscriptions.php'; ?>
            </div>
        <?php } ?>
        <?php if ($user->Admin || isset($staff)) { ?>
            <div class="col col-xs-6 col-sm-4 mb-2">
                <?php require_once __DIR__ . '/Partial/TeamsCard.php'; ?>
            </div>
            <div class="col col-xs-6 col-sm-4 mb-2">
                <?php require_once __DIR__ . '/Partial/ProblemsCard.php'; ?>
            </div>
        <?php } ?>
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/ChurchStatsCard.php'; ?>
        </div>
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/AgeStatsCard.php'; ?>
        </div>
    </div>

    <?php require __DIR__ . '/Partial/DashboardCharts.php'; ?>

    <div class="row">
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/NoEmailCard.php'; ?>
        </div>
    </div>
</div>
