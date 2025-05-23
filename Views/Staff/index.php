<?php
use Amichiamoci\Models\ProblemaIscrizione;
?>

<?php if ($user->IsAdmin) { ?>
    <div class="input-group mb-2">
        <select id="parrocchia-selector" class="form-control">
            <?php foreach ($parrocchie as $parrocchia) { ?>
                <option value="<?= $parrocchia->Id ?>" 
                    <?= ($parrocchia->Id === $id_parrocchia) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(string: $parrocchia->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <select id="anno-selector" class="form-control">
            <?php foreach ($edizioni as $e) { ?>
                <option value="<?= $e->Year ?>" <?= ($e->Year === $anno) ? 'selected' : '' ?>>
                    <?= $e->Year ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <script>
        (() => {
            const select_p = document.getElementById('parrocchia-selector');
            const select_a = document.getElementById('anno-selector');
            const f = () => {
                const p = Number(select_p.value);
                const a = Number(select_a.value);
                select_p.removeEventListener('change', f);
                select_a.removeEventListener('change', f);
                window.location.replace(`?church=${p}&year=${a}`);
            };
            select_p.addEventListener('change', f);
            select_a.addEventListener('change', f);
        })();
    </script>
<?php } ?>

<h1>
    Problemi iscrizioni (<?= count(value: $iscritti_problemi) ?>)
</h1>
<div class="row">
    <?php foreach ($iscritti_problemi as $problema) { 
        if (!($problema instanceof ProblemaIscrizione)) 
            continue;    
    ?>
        <div class="col col-xs-12 col-sm-6 col-lg-4 mb-2">
            <div class="card">
                <div class="card-header">
                    <strong>
                        <?= htmlspecialchars(string: $problema->Nome) ?>
                    </strong>

                    <?php if ($problema->Sesso === 'M') { ?>
                        <i class="bi bi-gender-male text-end"></i>
                    <?php } elseif ($problema->Sesso === 'F') { ?>
                        <i class="bi bi-gender-female text-end"></i>
                    <?php } ?>

                    <a 
                        href="<?= $B ?>/staff/edit_anagrafica?id=<?= $problema->Id ?>"
                        class="link-underline link-underline-opacity-0 link-primary text-end"
                        title="Modifica <?= htmlspecialchars(string: $problema->Nome) ?>">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <?php if (isset($problema->Documento)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Documento
                            </dt>
                            <dd class="col-sm-8 text-danger">
                                <?= htmlspecialchars(string: $problema->Documento) ?>
                                <a 
                                    class="link-primary link-underline link-underline-opacity-0"
                                    title="Aggiungi documento"
                                    href="<?= $B ?>/staff/edit_anagrafica?id=<?= $problema->Id ?>">
                                    Inserisci
                                </a>
                            </dd>
                        <?php } ?>
                        <?php if (isset($problema->ScadenzaDocumento)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Scadenza
                            </dt>
                            <dd class="col-sm-8 text-danger">
                                <?= htmlspecialchars(string: $problema->ScadenzaDocumento) ?>
                            </dd>
                        <?php } ?>
                        <?php if (isset($problema->CodiceDocumento)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Documento
                            </dt>
                            <dd class="col-sm-8 text-warning">
                                <?= htmlspecialchars(string: $problema->CodiceDocumento) ?>
                            </dd>
                        <?php } ?>


                        <?php if (isset($problema->Tutore)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Tutore
                            </dt>
                            <dd class="col-sm-8 text-danger">
                                <?= htmlspecialchars(string: $problema->Tutore) ?>
                                <a 
                                    class="link-primary link-underline link-underline-opacity-0"
                                    title="Aggiungi certificato"
                                    href="<?= $B ?>/staff/modifica_iscrizione?id=<?= $problema->Iscrizione ?>">
                                    Inserisci
                                </a>
                            </dd>
                        <?php } ?>
                        <?php if (isset($problema->Eta)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Età
                            </dt>
                            <dd class="col-sm-8 text-danger">
                                <?= htmlspecialchars(string: $problema->Eta) ?>
                            </dd>
                        <?php } ?>
                        <?php if (isset($problema->Certificato)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Certificato
                            </dt>
                            <dd class="col-sm-8 text-danger">
                                <?= htmlspecialchars(string: $problema->Certificato) ?>
                                <a 
                                    class="link-primary link-underline link-underline-opacity-0"
                                    title="Aggiungi certificato"
                                    href="<?= $B ?>/staff/modifica_iscrizione?id=<?= $problema->Iscrizione ?>">
                                    Inserisci
                                </a>
                            </dd>
                        <?php } ?>
                        <?php if (isset($problema->Taglia)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Maglia
                            </dt>
                            <dd class="col-sm-8 text-warning">
                                <?= htmlspecialchars(string: $problema->Taglia) ?>
                            </dd>
                        <?php } ?>

                        
                        <?php if (isset($problema->Email)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Email
                            </dt>
                            <dd class="col-sm-8 text-warning">
                                <?= htmlspecialchars(string: $problema->Email) ?>
                                <br>
                                <a 
                                    class="link-primary link-underline link-underline-opacity-0"
                                    title="Aggiungi email"
                                    href="<?= $B ?>/staff/edit_anagrafica?id=<?= $problema->Id ?>">
                                    Aggiungi
                                </a>
                            </dd>
                        <?php } ?>
                        <?php if (isset($problema->VerificaEmail)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Email
                            </dt>
                            <dd class="col-sm-8 text-warning">
                                <?= htmlspecialchars(string: $problema->VerificaEmail) ?>
                            </dd>
                        <?php } ?>
                        <?php if (isset($problema->Telefono)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Telefono
                            </dt>
                            <dd class="col-sm-8 text-warning">
                                <?= htmlspecialchars(string: $problema->Telefono) ?>
                            </dd>
                        <?php } ?>
                    </dl>
                </div>
            </div>
        </div>
    <?php } ?>
</div>