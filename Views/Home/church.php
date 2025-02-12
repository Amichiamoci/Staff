<h1>
    <?= htmlspecialchars(string: $title)?>
</h1>

<div class="container">
    <div class="row">
        <div class="col col-xs-6 col-sm-4 overflow-y-auto" style="max-height: 300px;">
            <h3>
                Iscritti correnti
            </h3>

        </div>
        <div class="col col-xs-6 col-sm-4 overflow-y-auto" style="max-height: 300px;">
            <h3>
                Tutti gli staffisti
            </h3>
            <ul class="list-group">
                <?php foreach ($staffs as $s) { ?>
                    <li class="list-group-item">
                        <a
                            href="staff/view?id=<?= $s->Id ?>"
                            class="link-secondary link-underline link-underline-opacity-0">
                            <?= htmlspecialchars(string: $s->Nome) ?>
                        </a>
                        <?php if ($s->Referente) { ?>
                            <span class="user-select-none">
                                - Referente
                            </span>
                        <?php } ?>
                        <?php if (count(value: $s->Commissioni) > 0) { ?>
                            <span class="user-select-none text-reset">
                                (<?=htmlspecialchars(
                                    string: implode(separator: ', ', array: $s->Commissioni))?>)
                            </span>
                        <?php } else { ?>
                            <small class="user-select-none text-secondary">
                                <br>
                                Nessuna commissione per il <?= date(format: 'Y') ?>
                            </small>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>