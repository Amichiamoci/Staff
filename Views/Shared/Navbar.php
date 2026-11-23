<?php
use Amichiamoci\Utils\Security;

assert(isset($P) && is_string($P), 'var $P not valid');
?>
<nav class="navbar navbar-expand-sm navbar-toggleable-sm border-bottom box-shadow mb-3">
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
                                        La mia parrocchia
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/teams"
                                        title="Le squadre"
                                    >
                                        Le squadre
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/teams/new"
                                        title="Crea squadra"
                                    >
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
                                    Tutti gli iscritti
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/anagrafiche"
                                    title="Tutte le anagrafiche"
                                >
                                    Tutte le anagrafiche
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/new_anagrafica"
                                    title="Nuova anagrafica"
                                >
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
                                    Tutti i Tornei
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/sport/matches"
                                    title="Partite e risultati"
                                >
                                    Partite e risultati
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
                                    title="Menù Utente"
                                >
                                    Menù Utente
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff"
                                    title="Menù Staff"
                                >
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
                                        Tutti gli utenti
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/user/new"
                                        title="Nuovo utente"
                                    >
                                        Nuovo utente
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/user/activity"
                                        title="Accessi"
                                    >
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
                                        href="<?= $P ?>/email"
                                        title="Ultime 500 email"
                                    >
                                        Ultime 500 email
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/email/history"
                                        title="Storico per destinatario"
                                    >
                                        Storico per destinatario
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/email/send"
                                        title="Invia Email"
                                    >
                                        Invia Email
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/file/list"
                                        title="Sorgenti file"
                                    >
                                        Sorgenti file
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a  class="dropdown-item"
                                        href="<?= $P ?>/docs/index.html"
                                        title="Documentazione"
                                    >
                                        Documentazione
                                    </a>
                                </li>
                                <?php if (Security::ApiEnabled()) { ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a  class="dropdown-item"
                                            href="<?= $P ?>/api/admin"
                                            title="Api"
                                        >
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
                                    Tutti gli Staff
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/current"
                                    title="Mostra lo staff dell’anno corrente"
                                >
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
                                    Tesseramenti per C.S.I.
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/staff/t_shirts"
                                    title="Tutte le maglie"
                                >
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
                                    Tutte le edizioni
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item"
                                    href="<?= $P ?>/credits"
                                    title="Crediti"
                                >
                                    Crediti
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                
                <li class="nav-item dropdown">
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
                                <i class="bi bi-sun"></i>
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
                                <i class="bi bi-moon"></i>
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