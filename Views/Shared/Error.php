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
    <a href="<?= $P ?>/" title="Home" class="link-underline-opacity-0 text-reset">pagina principale</a>
</p>
