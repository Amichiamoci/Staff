<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($activity) && is_array($activity), 'var $activity not valid');

?>

<h1>Ultimi accessi</h1>
<p>
    <a  href="<?= $P ?>/user/all"
        title="Apri la lista degli account"
    >
        Tutti gli utenti
    </a>
</p>
<?php
$show_accounts = true;
require __DIR__ . '/LoginRender.php';
?>
