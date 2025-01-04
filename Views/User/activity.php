<h1>
    Attività degli Utenti
</h1>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="mt-3">
                <ul class="list list-inline">

                    <?php 
                    $italian_date_format = new IntlDateFormatter(
                        locale: 'it_IT',
                        dateType: IntlDateFormatter::FULL,
                        timeType: IntlDateFormatter::FULL,
                        timezone: 'Europe/Rome',
                        calendar: IntlDateFormatter::GREGORIAN,
                        pattern: 'EEE, dd/MM/yy HH:mm:ss'
                    );
                    foreach ($activity as $login) { ?>

                    <li class="d-flex justify-content-between">
                        <div class="d-flex flex-row align-items-center"><i class="fa fa-check-circle checkicon"></i>
                            <div class="ml-2">
                                <h6 class="mb-0">
                                    <?= htmlspecialchars(string: $login->Username) ?>
                                </h6>
                                <div class="d-flex flex-row mt-1 text-black-50 date-time">
                                    <div>
                                        <i class="bi bi-calendar-week"></i>
                                        
                                        <?php if (isset($login->Start)) { ?>
                                            <span class="ml-2">
                                                <?= htmlspecialchars(
                                                    string: datefmt_format(formatter: $italian_date_format, datetime: $login->Start)) ?>
                                            </span>
                                        <?php } else { ?>
                                            <span class="ml-2 text-muted user-select-none">?</span>
                                        <?php } ?>
                                    </div>
                                    <?php if ($login->Duration() != null) { ?>
                                        <div class="ml-3">
                                            <i class="bi bi-clock-history"></i>
                                            <span class="ml-2">
                                                <?= $login->Duration()->i ?> minuti
                                            </span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-row align-items-center">
                            <div class="d-flex flex-column mr-2">
                                <output class="font-monospace text-muted  text-truncate overflow-hidden">
                                    <?= htmlspecialchars(string: $login->Flag) ?>
                                </output>
                                <a href="https://www.infobyip.com/ip-<?= htmlspecialchars(string: $login->Ip) ?>.html" 
                                    class="link-secondary link-underline-opacity-0"
                                    target="blank">
                                    <?= htmlspecialchars(string: $login->Ip) ?>
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </li>

                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>