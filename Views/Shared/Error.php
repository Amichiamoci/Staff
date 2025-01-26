<h1>
    <?= htmlspecialchars(string: $main_banner) ?>
</h1>
<p>
    Ci dispiace, sembra che qualcosa sia andato storto.<br />
    Prova a andare alla 
    <a href="/" title="Home" class="link-underline-opacity-0 text-reset">pagina principale</a>
</p>

<hr>
<p>
    La risorsa richiesta è 
    <code>
        <a 
            href="<?= htmlspecialchars(string: $_SERVER['REQUEST_URI']) ?>" 
            class="link-underline-opacity-0 text-reset"
            title="Ricarica la pagina">
            <?= htmlspecialchars(string: $_SERVER['REQUEST_URI']) ?>
        </a>
    </code>
</p>