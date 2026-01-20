<?php
use Amichiamoci\Models\UserActivity;

if (!($login instanceof UserActivity)) {
    throw new \Exception(message: 'Variabile $login non corretta');
}
?>
<div class="d-flex flex-row align-items-center">
    <i class="fa fa-check-circle checkicon"></i>
    <div class="ms-2">
        <h6 class="mb-0">
            <a  href="<?= $P ?>/user/username?u=<?= htmlspecialchars(string: $login->UserName) ?>"
                class="link-underline link-underline-opacity-0 text-reset"
                title="Vedi utente"
            >
                <?= htmlspecialchars(string: $login->UserName) ?>
            </a>
        </h6>
        <div class="d-flex flex-row mt-1">
            <div>
                <i class="bi bi-calendar-week"></i>
                
                <?php if (isset($login->Start)) { ?>
                    <span class="ms-1">
                        <?= $login->Start->format(format: 'd/m/Y H:i') ?>
                    </span>
                <?php } else { ?>
                    <span class="ms-1 text-muted user-select-none">?</span>
                <?php } ?>
            </div>
            <?php if ($login->Duration() != null) { ?>
                <div class="ms-3">
                    <i class="bi bi-clock-history"></i>
                    <span class="ms-1">
                        <?= $login->Duration()->i ?> minuti
                    </span>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<div class="d-flex flex-row align-items-center">
    <div class="d-flex flex-column me-2">
        <output class="font-monospace text-muted text-truncate overflow-hidden">
            <?= htmlspecialchars(string: $login->Flag) ?>
        </output>
        <?php if (isset($login->Ip)) { ?>
            <a  href="https://www.infobyip.com/ip-<?= htmlspecialchars(string: $login->Ip) ?>.html" 
                class="text-reset link-underline link-underline-opacity-0"
                target="blank"
            >
                <?= htmlspecialchars(string: $login->Ip) ?>
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
        <?php } else { ?>
            <span class="user-select-none">
                Ip sconosciuto
            </span>
        <?php } ?>
    </div>
</div>