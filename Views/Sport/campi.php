<?php
assert(isset($P) && is_string($P));
assert(isset($campi) && is_array($campi));
assert(isset($can_manage) && is_bool($can_manage));
?>

<h1>Campi sportivi</h1>

<?php if ($can_manage) { ?>
    <a  href="<?= $P ?>/sport/campo_edit"
        class="btn btn-primary mb-3"
        title="Aggiungi un campo sportivo"
    >
        Aggiungi campo
    </a>
<?php } ?>

<?php require __DIR__ . '/campi-map.php'; ?>

<?php if ($campi === []) { ?>
    <p>Nessun campo registrato.</p>
<?php } else { ?>
    <div
        class="table-responsive border rounded"
        tabindex="0"
        role="region"
        aria-labelledby="campi-table-caption"
    >
        <table class="table table-striped table-hover align-middle mb-0">
            <caption
                id="campi-table-caption"
                class="visually-hidden"
            >
                Elenco dei campi sportivi
            </caption>
            <thead class="table-light">
                <tr>
                    <th
                        scope="col"
                        class="p-3"
                    >
                        Campo
                    </th>
                    <th scope="col">Indirizzo</th>
                    <th scope="col">Posizione</th>
                    <?php if ($can_manage) { ?>
                        <th
                            scope="col"
                            class="p-3 text-end"
                        >
                            Azioni
                        </th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campi as $campo) { ?>
                    <tr>
                        <th
                            scope="row"
                            class="p-3"
                        >
                            <?= htmlspecialchars($campo->Nome) ?>
                        </th>
                        <td><?= htmlspecialchars($campo->Indirizzo ?? '') ?></td>
                        <td>
                            <?php if ($campo->Latitudine !== null && $campo->Longitudine !== null) { ?>
                                <span
                                    class="font-monospace small text-nowrap"
                                    data-campo-name="<?= htmlspecialchars($campo->Nome) ?>"
                                    data-campo-lat="<?= htmlspecialchars((string)$campo->Latitudine) ?>"
                                    data-campo-lon="<?= htmlspecialchars((string)$campo->Longitudine) ?>"
                                >
                                    <?= htmlspecialchars($campo->Latitudine . ', ' . $campo->Longitudine) ?>
                                </span>
                            <?php } else { ?>
                                <span class="text-body-secondary">Non impostata</span>
                            <?php } ?>
                        </td>
                        <?php if ($can_manage) { ?>
                            <td class="pe-3 text-end">
                                <a  href="<?= $P ?>/sport/campo_edit?id=<?= $campo->Id ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Modifica <?= htmlspecialchars($campo->Nome) ?>"
                                    aria-label="Modifica <?= htmlspecialchars($campo->Nome) ?>"
                                >
                                    Modifica
                                </a>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>
