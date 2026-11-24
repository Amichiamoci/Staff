<?php

assert(isset($compleanni) && is_array($compleanni), 'var $compleanni not valid');

?>

<section
    class="rounded-3 border px-3 py-2 mb-3"
    aria-labelledby="today-birthdays"
>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h2
            id="today-birthdays"
            class="fs-6 d-flex align-items-center gap-2 mb-0"
        >
            <i  class="bi bi-cake2 text-info-emphasis"
                aria-hidden="true"
            ></i>
            Compleanni di oggi
        </h2>

        <?php if ($compleanni === []) { ?>
            <p class="small mb-0">Nessun compleanno oggi.</p>
        <?php } else { ?>
            <ul class="list-unstyled d-flex flex-wrap align-items-center gap-2 mb-0">
                <?php foreach ($compleanni as $compleanno) { ?>
                    <li class="small border rounded-pill px-2 text-break">
                        <?= htmlspecialchars($compleanno) ?>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</section>
