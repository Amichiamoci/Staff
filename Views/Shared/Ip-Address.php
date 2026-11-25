<?php 

if (isset($ip) && is_string($ip)) {
    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) { 
    ?>
        <button
            type="button"
            class="btn btn-link font-monospace p-0 text-reset link-underline link-underline-opacity-0"
            title="Copia l’indirizzo IP"
            data-copy-ip
        >
            <?= htmlspecialchars($ip) ?>
        </button>
        <a  href="https://www.infobyip.com/ip-<?= rawurlencode($ip) ?>.html"
            class="text-reset link-underline link-underline-opacity-0"
            target="_blank"
            rel="noopener noreferrer"
            title="Cerca informazioni su <?= htmlspecialchars($ip) ?> in una nuova scheda"
            aria-label="Cerca informazioni su <?= htmlspecialchars($ip) ?> in una nuova scheda"
        >
            <i
                class="bi bi-box-arrow-up-right"
                aria-hidden="true"
            ></i>
        </a>
        <span
            class="d-block small"
            role="status"
        ></span>
    <?php } else { ?>
        <span class="font-monospace">
            <?= htmlspecialchars($ip) ?>
        </span>
    <?php }
} else { ?>
    <span class="font-monospace">—</span>
<?php } ?>
