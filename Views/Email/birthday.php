<?php

assert(isset($name) && is_string($name), 'var $name not valid');
assert(isset($site_url) && is_string($site_url), 'var $site_url not valid');

?>

<h3>Tanti auguri a te</h3>
<h3>Tanti auguri a te</h3>
<h3>Tanti auguri a <?= htmlspecialchars(string: $name) ?></h3>
<h3>Tanti auguri a te!</h3>

<p>
    Ciao <?= htmlspecialchars(string: $name) ?>, lo staff di 
    <a  href="<?= $site_url ?>"
        target="_blank"
        class="link"
        title="Amichiamoci"
    >Amichiamoci</a>
    ti augura un buon compleanno, passa questo giorno al meglio.
</p>