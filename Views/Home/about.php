<?php

assert(isset($app_version) && is_string($app_version), 'var $app_version not valid');
assert(isset($php_version) && is_string($php_version), 'var $php_version not valid');
assert(isset($domain) && is_string($domain), 'var $domain not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($packages) && is_array($packages), 'var $packages not valid');

?>

<h1>Informazioni sul portale</h1>
<dl class="row">
    <dt class="col-sm-6">Portale Staff</dt>
    <dd class="col-sm-6"><?= htmlspecialchars($app_version) ?></dd>
    <dt class="col-sm-6">PHP</dt>
    <dd class="col-sm-6"><?= htmlspecialchars($php_version) ?></dd>
    <dt class="col-sm-6">Dominio</dt>
    <dd class="col-sm-6"><?= htmlspecialchars($domain) ?></dd>
    <dt class="col-sm-6">Percorso di installazione</dt>
    <dd class="col-sm-6"><?= $P !== '' ? $P : '/' ?></dd>
</dl>

<h2>Librerie installate</h2>
<dl class="row">
    <?php foreach ($packages as $name => $version) { ?>
        <dt class="col-sm-6"><?= htmlspecialchars($name) ?></dt>
        <dd class="col-sm-6"><?= htmlspecialchars($version) ?></dd>
    <?php } ?>
</dl>
