<?php

assert(isset($P) && is_string($P));
assert(isset($live_matches) && is_array($live_matches));

?>

<?php if (count($live_matches) > 0) { ?>

    <section
        class="rounded-3 border px-3 py-2 mb-3"
        aria-labelledby="live-matches"
    >
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h2
                id="live-matches"
                class="fs-6 d-flex align-items-center gap-2 mb-0"
            >
                <i  class="bi bi-broadcast text-danger-emphasis"
                    aria-hidden="true"
                ></i>
                Partite ADESSO 
            </h2>

            <ul class="list-unstyled d-flex flex-wrap align-items-center gap-2 mb-0">
                <?php foreach ($live_matches as $match) { ?>
                    <li class="small border rounded-pill px-2 text-break">
                        <a  href="<?= $P ?>/sport/tournament?id=<?= $match->Torneo ?>#match-<?= $match->Id ?>"
                            class="link-body-emphasis"
                            title="Vedi la partita di <?= htmlspecialchars($match->getSport()->Nome) ?>"
                        >
                            <span aria-hidden="true">
                                <?= htmlspecialchars($match->getSport()->getIcon()) ?>
                            </span>

                            <span class="visually-hidden">
                                <?= htmlspecialchars($match->getSport()->Nome) ?>
                            </span>

                            <time datetime="<?= htmlspecialchars($match->Data . 'T' . $match->Orario) ?>">
                                <?= htmlspecialchars(substr($match->Orario, 0, 5)) ?>
                            </time>

                            <?= htmlspecialchars($match->Casa->Nome) ?> 
                            – 
                            <?= htmlspecialchars($match->Ospiti->Nome) ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </section>

<?php } ?>
