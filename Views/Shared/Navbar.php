<?php

assert(isset($P) && is_string($P), 'var $P not valid');
?>
<nav
    id="portal-nav"
    class="navbar navbar-expand-sm navbar-toggleable-sm border-bottom box-shadow"
>
    <div class="container-fluid">
        <a  class="navbar-brand"
            href="<?= $P ?>/"
            title="Torna alla pagina principale"
        >
            <img
                src="<?= $P ?>/images/banner.png"
                height="48"
                alt="Logo di Amichiamoci"
                title="Vai alla pagina principale"
                class="d-inline-block align-text-top"
            />
        </a>
        <button
            class="navbar-toggler"
            type="button"
            data-toggle="collapse"
            data-bs-toggle="collapse"
            data-target="#navbar-collapse"
            data-bs-target="#navbar-collapse"
            aria-expanded="false"
            aria-label="Mostra o nascondi la navigazione"
            title="Mostra o nascondi la navigazione"
        >
            <i  class="bi bi-list text-secondary"
                style="pointer-events: none;"
            ></i>
        </button>
        <div
            id="navbar-collapse"
            class="navbar-collapse collapse d-sm-inline-flex justify-content-between"
        >
            <ul class="navbar-nav flex-grow-1">
                <?php if (isset($user)) { ?>
                    <li class="nav-item dropdown">
                        <button
                            class="nav-link text-reset dropdown-toggle"
                            type="button"
                            id="subscriptionDropdownMenuLink"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Iscrizioni"
                        >
                            Iscrizioni
                        </button>
                        <ul class="dropdown-menu dropdown-menu-lg-start"
                            aria-labelledby="subscriptionDropdownMenuLink"
                        >
                            <?php if ($user->Admin || isset($staff)) { ?>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/staff/index"
                                        title="La mia parrocchia"
                                    >
                                        <i  class="bi bi-house me-2"
                                            aria-hidden="true"
                                        ></i>
                                        La mia parrocchia
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/teams"
                                        title="Le squadre"
                                    >
                                        <i  class="bi bi-people me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Le squadre
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/teams/new"
                                        title="Crea squadra"
                                    >
                                        <i  class="bi bi-person-fill-add me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Crea squadra
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            <?php } ?>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/anagrafiche?year=<?= date(format: "Y") ?>"
                                    title="Tutti gli iscritti"
                                >
                                    <i  class="bi bi-clipboard-check me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Tutti gli iscritti
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/anagrafiche"
                                    title="Tutte le anagrafiche"
                                >
                                    <i  class="bi bi-person-vcard me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Tutte le anagrafiche
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/new_anagrafica"
                                    title="Nuova anagrafica"
                                >
                                    <i  class="bi bi-person-plus me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Nuova anagrafica
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <button
                            class="nav-link text-reset dropdown-toggle"
                            type="button"
                            id="matchesDropdownMenuLink"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Partite"
                        >
                            Partite
                        </button>
                        <ul class="dropdown-menu dropdown-menu-lg-start"
                            aria-labelledby="matchesDropdownMenuLink"
                        >
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/sport"
                                    title="Tutti i Tornei"
                                >
                                    <i  class="bi bi-trophy me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Tutti i Tornei
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/sport/matches"
                                    title="Partite e risultati"
                                >
                                    <i  class="bi bi-calendar-week me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Partite e risultati
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/sport/campi"
                                    title="Campi sportivi"
                                >
                                    <i  class="bi bi-geo-alt me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Campi
                                </a>
                            </li>
                            <?php if ($user->Admin) { ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/sport/tournament_create"
                                        title="Nuovo torneo"
                                    >
                                        <i  class="bi bi-calendar-plus me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Nuovo torneo
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <button
                            class="nav-link text-reset dropdown-toggle"
                            type="button"
                            id="userDropdownMenuLink"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Utente"
                        >
                            Utente
                        </button>
                        <ul class="dropdown-menu dropdown-menu-lg-start"
                            aria-labelledby="userDropdownMenuLink"
                        >
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/user/me"
                                    title="Il mio account"
                                >
                                    <i  class="bi bi-person-circle me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Il mio account
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff"
                                    title="Menù Staff"
                                >
                                    <i  class="bi bi-person-badge me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Menù Staff
                                </a>
                            </li>
                            <?php if ($user->Admin) { ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/user/all"
                                        title="Tutti gli utenti"
                                    >
                                        <i  class="bi bi-people me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Tutti gli utenti
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/user/new"
                                        title="Nuovo utente"
                                    >
                                        <i  class="bi bi-person-plus me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Nuovo utente
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/user/activity"
                                        title="Accessi"
                                    >
                                        <i  class="bi bi-box-arrow-in-right me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Accessi
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php if ($user->Admin) { ?>
                        <li class="nav-item dropdown">
                            <button
                                class="nav-link text-reset dropdown-toggle"
                                type="button"
                                id="serverDropdownMenuLink"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                title="Server"
                            >
                                Server
                            </button>
                            <ul class="dropdown-menu dropdown-menu-lg-start"
                                aria-labelledby="serverDropdownMenuLink"
                            >
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/settings"
                                        title="Configurazione generale"
                                    >
                                        <i  class="bi bi-sliders me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Configurazione generale
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/file/list"
                                        title="Sorgenti file"
                                    >
                                        <i  class="bi bi-folder2-open me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Sorgenti file
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/email"
                                        title="Ultime 500 email"
                                    >
                                        <i  class="bi bi-envelope me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Ultime 500 email
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/email/history"
                                        title="Storico per destinatario"
                                    >
                                        <i  class="bi bi-envelope-at me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Storico per destinatario
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/email/send"
                                        title="Invia Email"
                                    >
                                        <i  class="bi bi-envelope-arrow-up-fill me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Invia Email
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/docs/index.html"
                                        data-no-swup
                                        title="Documentazione"
                                    >
                                        <i  class="bi bi-book me-2"
                                            aria-hidden="true"
                                        ></i>
                                        Documentazione
                                    </a>
                                </li>
                                <?php if ($api_routes_enabled ?? false) { ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a  class="dropdown-item"
                                            href="<?= $P ?>/api/admin"
                                            title="Api"
                                        >
                                            <i  class="bi bi-key me-2"
                                                aria-hidden="true"
                                            ></i>
                                            Api
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </li>
                    <?php } ?>
                    <li class="nav-item dropdown">
                        <button
                            class="nav-link text-reset dropdown-toggle"
                            type="button"
                            id="listsDropdownMenuLink"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Liste"
                        >
                            Liste
                        </button>
                        <ul class="dropdown-menu dropdown-menu-lg-start"
                            aria-labelledby="listsDropdownMenuLink"
                        >
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/all"
                                    title="Tutti gli Staff"
                                >
                                    <i  class="bi bi-people me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Tutti gli Staff
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/current"
                                    title="Mostra lo staff dell’anno corrente"
                                >
                                    <i  class="bi bi-calendar-check me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Staff per il <?= date(format: "Y") ?>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/csi"
                                    title="Tesseramenti per C.S.I."
                                >
                                    <i  class="bi bi-card-checklist me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Tesseramenti per C.S.I.
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/t_shirts"
                                    title="Tutte le maglie"
                                >
                                    <i  class="bi bi-tag me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Tutte le maglie
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/church_leaderboard"
                                    title="Classifica parrocchiale"
                                >
                                    <i  class="bi bi-list-ol me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Classifica parrocchiale
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/edizione"
                                    title="Tutte le edizioni"
                                >
                                    <i  class="bi bi-calendar3 me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Tutte le edizioni
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/credits"
                                    title="Crediti"
                                >
                                    <i  class="bi bi-info-circle me-2"
                                        aria-hidden="true"
                                    ></i>
                                    Crediti
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                
                <li class="nav-item dropdown"
                    data-swup-persist="portal-theme"
                >
                    <button
                        class="nav-link text-reset dropdown-toggle"
                        type="button"
                        id="themeDropdownMenuLink"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        title="Tema"
                    >
                        Tema
                    </button>
                    <ul class="dropdown-menu dropdown-menu-lg-start"
                        aria-labelledby="themeDropdownMenuLink"
                    >
                        <li>
                            <button
                                class="dropdown-item"
                                type="button"
                                data-bs-theme-value="light"
                                title="Imposta il tema chiaro"
                            >
                                <i  class="bi bi-sun me-2"
                                    aria-hidden="true"
                                ></i>
                                Chiaro
                            </button>
                        </li>
                        <li>
                            <button
                                class="dropdown-item"
                                type="button"
                                data-bs-theme-value="dark"
                                title="Imposta il tema scuro"
                            >
                                <i  class="bi bi-moon me-2"
                                    aria-hidden="true"
                                ></i>
                                Scuro
                            </button>
                        </li>
                    </ul>
                </li>
            </ul>

            <?php if (isset($user)) { ?>
                <a  href="<?= $P ?>/user/me"
                    class="nav-link text-reset"
                    title="Vai alla pagina del profilo"
                >
                    <?= htmlspecialchars(string: $user->Label()) ?>
                </a>
            <?php } ?>
        </div>
    </div>
</nav>
