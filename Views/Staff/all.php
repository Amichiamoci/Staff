<h1>
    <?= htmlspecialchars(string: $title) ?> (<?= count(value: $staffs) ?>)
</h1>

<div class="row">
    <?php foreach ($staffs as $staff) { ?>
        <div class="col col-xs-12 col-sm-6 col-md-4 col-xxl-3 p-1">
            <?php require dirname(path: __DIR__) . "/Shared/Staff.php"; ?>
        </div>
    <?php } ?>
</div>