<?php
if (!isset($anagrafica) || !($anagrafica instanceof Amichiamoci\Models\Anagrafica)) {
    throw new \Exception(message: '$anagrafica variable not set!');
}
use Amichiamoci\Utils\Link;
use Amichiamoci\Utils\File;
?>

<div class="card" 
    id="anagrafica-<?= $anagrafica->Id ?>">
    <div class="card-header font-monospace user-select-none">
        #<?= $anagrafica->Id ?>
    </div>
    <div class="card-body">
        <div class="card-title">
            <strong>
                <?= htmlspecialchars(string: $anagrafica->Nome) ?>
            </strong>
            <span class="user-select-none text-secondary">/</span>
            <strong>
                <?= htmlspecialchars(string: $anagrafica->Cognome) ?>
            </strong>

            <?php if ($anagrafica->Sex === 'M') { ?>
                <i class="bi bi-gender-male text-end"></i>
            <?php } elseif ($anagrafica->Sex === 'F') { ?>
                <i class="bi bi-gender-female text-end"></i>
            <?php } ?>

            <a 
                href="/staff/edit_anagrafica?id=<?= $anagrafica->Id ?>"
                class="link-underline link-underline-opacity-0 link-primary text-end"
                title="Modifica <?= htmlspecialchars(string: $anagrafica->Nome) ?>">
                <i class="bi bi-pencil-square"></i>
            </a>
        </div>

        <?php if ($user->IsAdmin) { ?>
            <h6 class="card-subtitle mb-2 user-select-none text-secondary font-monospace">
                <?= htmlspecialchars(string: $anagrafica->FiscalCode) ?>
            </h6>
        <?php } ?>

        <dl class="row">
            <dt class="col-sm-4 text-nowrap">
                <i class="bi bi-calendar4-week"></i>
                <?php if ($anagrafica->Sex === 'M') { ?>
                    Nato
                <?php } elseif ($anagrafica->Sex === 'F') { ?>
                    Nata
                <?php } else { ?>
                    Nat*
                <?php } ?>
                il
            </dt>
            <dd class="col-sm-8">
                <?= htmlspecialchars(string: $anagrafica->BirthDay) ?>
                (<?= htmlspecialchars(string: $anagrafica->Eta) ?> anni)
            </dd>

            <dt class="col-sm-4 text-nowrap">
                <i class="bi bi-geo"></i>
                <?php if ($anagrafica->Sex === 'M') { ?>
                    Nato
                <?php } elseif ($anagrafica->Sex === 'F') { ?>
                    Nata
                <?php } else { ?>
                    Nat*
                <?php } ?>
                a
            </dt>
            <dd class="col-sm-8">
                <?= htmlspecialchars(string: $anagrafica->From) ?>
            </dd>

            <dt class="col-sm-4 text-nowrap">
                <i class="bi bi-envelope-at"></i>
                Email
            </dt>
            <dd class="col-sm-8">
                <?php if (empty($anagrafica->Email)) { ?>
                    <strong class="text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Mancante!
                    </strong>
                <?php } else { ?>
                    <a 
                        href="mailto:<?= htmlspecialchars(string: $anagrafica->Email) ?>"
                        class="link-underline link-underline-opacity-0 text-reset text-nowrap overflow-x-auto">
                        <?= htmlspecialchars(string: $anagrafica->Email) ?>
                    </a>
                <?php } ?>
            </dd>

            <?php if (!empty($anagrafica->Phone) && str_starts_with(haystack: $anagrafica->Phone, needle: '0')) { ?>
                <dt class="col-sm-4 text-nowrap">
                    <i class="bi bi-telephone"></i>
                    Telefono
                </dt>
                <dd class="col-sm-8">
                    <a 
                        href="tel:<?= htmlspecialchars(string: $anagrafica->Phone) ?>"
                        class="link-underline link-underline-opacity-0 text-reset">
                        <?= htmlspecialchars(string: $anagrafica->Phone) ?>
                    </a>
                </dd>
            <?php } elseif (!empty($anagrafica->Phone)) { ?>
                <dt class="col-sm-4 text-nowrap">
                    <i class="bi bi-whatsapp"></i>
                    WhatsApp
                </dt>
                <dd class="col-sm-8">
                    <a 
                        href="<?= Link::Number2WhatsApp(number: $anagrafica->Phone) ?>"
                        class="link-underline link-underline-opacity-0 text-reset">
                        <?= htmlspecialchars(string: $anagrafica->Phone) ?>
                    </a>
                </dd>
            <?php } ?>

            <dt class="col-sm-4">
                <i class="bi bi-person-vcard"></i>
                <?= htmlspecialchars(string: $anagrafica->DocumentType->Nome) ?>
            </dt>
            <dd class="col-sm-8">
                <a 
                    href="<?= File::GetExportUrl(path: $anagrafica->DocumentFileName) ?>"
                    download
                    class="link-underline link-underline-opacity-0 text-reset font-monospace"
                    title="Scarica il documento">
                    <?= htmlspecialchars(string: $anagrafica->DocumentCode) ?>
                </a>
            </dd>
            <dt class="col-sm-4 text-nowrap">
                <i class="bi bi-calendar2-x"></i>
                Scadenza
            </dt>
            <dd class="col-sm-8">
                <?php if (empty($anagrafica->DocumentExpiration)) { ?>
                    <span class="text-danger">Non trovata</span>
                <?php } else { 
                    $date = new \DateTime(datetime: $anagrafica->DocumentExpiration);
                    $is_expired = $date < (new \DateTime());
                ?>
                    <span class="<?= $is_expired ? 'text-danger' : '' ?>">
                        <?= $is_expired ? '<i class="bi bi-exclamation-triangle"></i>' : '' ?>
                        <?= htmlspecialchars(string: $date->format(format: 'd/m/Y')) ?>
                        <?= $is_expired ? '(scaduto)' : '' ?>
                    </span>
                <?php } ?>
            </dd>

            <?php if ($anagrafica instanceof Amichiamoci\Models\AnagraficaConIscrizione)  { ?>
                <dt class="col-sm-4 text-nowrap">
                    <i class="bi bi-list-ol"></i>
                    Iscrizione
                </dt>
                <dd class="col-sm-8">
                    <span class="font-monospace">
                        <?= $anagrafica->Iscrizione->Id ?>
                    </span>
                    <?php if ($user->IsAdmin || (isset($staff) && $staff->Parrocchia->Id === $anagrafica->Iscrizione->Parrocchia->Id)) { ?>
                        <form action="/staff/delete_iscrizione" method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?= $anagrafica->Iscrizione->Id ?>">
                            <button 
                                type="submit"
                                class="btn btn-link btn-underline btn-underline-opacity-0 text-reset p-0"
                                title="Annulla iscrizione"
                                data-confirm="Vuoi annullare l'iscrizione di <?= htmlspecialchars(string: $anagrafica->Nome) ?>?"
                                data-confirm-btn="Sì, annulla"
                                data-cancel-btn="No, lascia così"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    <?php } ?>
                </dd>

                <dt class="col-sm-4 text-nowrap">
                    <i class="bi bi-house"></i>
                    Parrocchia
                </dt>
                <dd class="col-sm-8">
                    <a 
                        href="/church?id=<?= $anagrafica->Iscrizione->Parrocchia->Id ?>"
                        class="link-underline link-underline-opacity-0 link-secondary"
                        title="Vedi la parrocchia">
                        <?= htmlspecialchars(string: $anagrafica->Iscrizione->Parrocchia->Nome) ?>
                    </a>
                </dd>

                <dt class="col-sm-4 text-nowrap">
                    <?php if ($anagrafica->Sex === 'F') { ?>
                        <i class="bi bi-person-standing-dress"></i>
                    <?php } else { ?>
                        <i class="bi bi-person-standing"></i>
                    <?php } ?>
                    Taglia
                </dt>
                <dd class="col-sm-8">
                    <?= htmlspecialchars(string: $anagrafica->Iscrizione->Taglia->value) ?>
                </dd>

                <dt class="col-sm-4 text-nowrap">
                    <i class="bi bi-activity"></i>
                    Certificato
                </dt>
                <dd class="col-sm-8">
                    <?php if (isset($anagrafica->Iscrizione->Certificato)) { ?>
                        <a 
                            href="<?= File::GetExportUrl(path: $anagrafica->Iscrizione->Certificato) ?>"
                            download
                            class="link-underline link-underline-opacity-0 text-success"
                            title="Scarica il documento">
                            <i class="bi bi-check"></i>
                            Presente
                        </a>
                    <?php } else { ?>
                        <strong class="text-danger user-select-none">
                            <i class="bi bi-exclamation-triangle"></i>
                            Mancante!
                        </strong>
                    <?php } ?>
                </dd>

                <?php if ($anagrafica->Eta < 18) { ?>
                    <dt class="col-sm-4 text-nowrap">
                        <i class="bi bi-person-lock"></i>
                        Tutore
                    </dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($anagrafica->Iscrizione->IdTutore)) { ?>
                            <a 
                                href="/staff/edit_anagrafica?id=<?= $anagrafica->Iscrizione->IdTutore ?>"
                                class="link-underline link-underline-opacity-0 text-success"
                                title="Vedi il tutore">
                                <i class="bi bi-check"></i>
                                Presente
                            </a>
                        <?php } else { ?>
                            <strong class="text-danger user-select-none">
                                <i class="bi bi-exclamation-triangle"></i>
                                Mancante!
                            </strong>
                        <?php } ?>
                    </dd>

                <?php } ?>
            <?php } else { ?>
                <dt class="col-sm-4 text-nowrap">
                    <i class="bi bi-person-plus"></i>
                    Iscrivi
                </dt>
                <dd class="col-sm-8">
                    <a 
                        href="/staff/iscrivi?id=<?= $anagrafica->Id ?>"
                        class="btn btn-secondary"
                        title="Iscrivi alla manifestazione">
                        Clicca qui
                    </a>
                </dd>
            <?php } ?>
        </dl>
    </div>
</div>