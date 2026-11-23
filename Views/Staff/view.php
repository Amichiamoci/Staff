<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($title) && is_string($title), 'var $title not valid');
assert(isset($target) && $target instanceof \Amichiamoci\Models\StaffBase, 'var $target not valid');

?>

<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<?php 
    $staff = $target;
    require dirname(path: __DIR__) . "/Shared/Staff.php";
?>