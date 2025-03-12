<div class="container">
    <div class="row">
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/StaffCard.php'; ?>
        </div>
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/T-Shirts.php'; ?>
        </div>
        <?php if ($user->IsAdmin) { ?>
            <div class="col col-xs-6 col-sm-4 mb-2">
                <?php require_once __DIR__ . '/Partial/CronCard.php'; ?>
            </div>
        <?php } ?>
        <?php if ($user->IsAdmin || isset($staff)) { ?>
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
            <?php require_once __DIR__ . '/Partial/BirthDays.php'; ?>
        </div>
        <div class="col col-xs-6 col-sm-4 mb-2">
            <?php require_once __DIR__ . '/Partial/FromStatsCard.php'; ?>
        </div>
    </div>
</div>