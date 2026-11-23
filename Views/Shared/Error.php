<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($status_code) && is_int($status_code), 'var $status_code not valid');

if ($status_code !== 404 && $status_code !== 500) {
    assert(isset($main_banner) && is_string($main_banner), 'var $main_banner not valid');
}

if ($status_code === 500 && isset($exception)) {
    \Amichiamoci\Utils\ErrorLog::Report($exception);
}
?>
<h1>
    <?= match ($status_code) {
        404 => 'Pagina non trovata',
        500 => 'Errore interno',
        default => htmlspecialchars($main_banner),
    } ?>
</h1>
<p>
    Ci dispiace, sembra che qualcosa sia andato storto.<br />
    Prova a andare alla 
    <a  href="<?= $P ?>/"
        title="Home"
        class="link-underline-opacity-0 text-reset"
    >pagina principale</a>
</p>
