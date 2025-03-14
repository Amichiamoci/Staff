<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<?php 
    $staff = $target;
    require dirname(path: __DIR__) . "/Shared/Staff.php";
?>