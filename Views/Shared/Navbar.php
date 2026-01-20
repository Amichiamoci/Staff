<?php
    use Amichiamoci\Utils\Security;
?>
<nav class="navbar navbar-expand-sm navbar-toggleable-sm border-bottom box-shadow mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $P ?>/">
            <img src="<?= $P ?>/Public/images/banner.png" 
                 height="48"
                 alt="Logo di Amichiamoci" title="Vai alla pagina principale" 
                 class="d-inline-block align-text-top"
            />
        </a>
        <button class="navbar-toggler" 
                type="button"
                data-toggle="collapse"
                data-bs-toggle="collapse"
                data-target="#navbar-collapse"
                data-bs-target="#navbar-collapse" 
                aria-expanded="false" aria-label="Toggle navigation"
        >
            <i class="bi bi-list text-secondary" style="pointer-events: none;"></i>
        </button>
        <div id="navbar-collapse"
             class="navbar-collapse collapse d-sm-inline-flex justify-content-between"
        >
            <ul class="navbar-nav flex-grow-1">
                <?php if (isset($user)) { ?>
                    <li class="nav-item dropdown">
                        <a  class="nav-link text-reset dropdown-toggle"
                            href="#" 
                            id="subscriptionDropdownMenuLink" 
                            role="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false"
                        >
                            Iscrizioni
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="subscriptionDropdownMenuLink">
                            <?php if ($user->Admin || isset($staff)) { ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/staff/index">
                                        La mia parrocchia
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/teams">
                                        Le squadre
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/teams/new">
                                        Crea squadra
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            <?php } ?>
                            <li>
                                <a class="dropdown-item" href="<?= $P ?>/staff/anagrafiche?year=<?= date(format: "Y") ?>">
                                    Tutti gli iscritti
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $P ?>/staff/anagrafiche">
                                    Tutte le anagrafiche
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $P ?>/staff/new_anagrafica">
                                    Nuova anagrafica
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a  class="nav-link text-reset dropdown-toggle"
                            href="#" 
                            id="matchesDropdownMenuLink" 
                            role="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false"
                        >
                            Partite
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="matchesDropdownMenuLink">
                            <li>
                                <a class="dropdown-item" href="<?= $P ?>/sport">
                                    Tutti i Tornei
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $P ?>/sport/matches">
                                    Partite e risultati
                                </a>
                            </li>
                            <?php if ($user->Admin || (isset($staff) && $staff->InCommissione(commissione: 'Tornei'))) { ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/sport/tournament_create">
                                        Nuovo torneo
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a  class="nav-link text-reset dropdown-toggle"
                            href="#" 
                            id="userDropdownMenuLink" 
                            role="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false"
                        >
                            Utente
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="userDropdownMenuLink">
                            <li>
                                <a class="dropdown-item" href="<?= $P ?>/user/me">
                                    Menù Utente
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $P ?>/staff">
                                    Menù Staff
                                </a>
                            </li>
                            <?php if ($user->Admin) { ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/user/all">
                                        Tutti gli utenti
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/user/new">
                                        Nuovo utente
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/user/activity">
                                        Accessi
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php if ($user->Admin) { ?>
                        <li class="nav-item dropdown">
                            <a  class="nav-link text-reset dropdown-toggle"
                                href="#" 
                                id="emailDropdownMenuLink" 
                                role="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false"
                            >
                                Server
                            </a>
                            <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="emailDropdownMenuLink">
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/email">
                                        Lista email
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/email/send">
                                        Invia Email
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/file/list">
                                        Tutti gli uploads
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $P ?>/file/unreferenced">
                                        File non usati
                                    </a>
                                </li>
                                <?php if (Security::ApiEnabled()) { ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= $P ?>/api/admin">
                                            Api
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </li>
                    <?php } ?>
                    <li class="nav-item dropdown">
                        <a  class="nav-link text-reset dropdown-toggle"
                            href="#" 
                            id="listsDropdownMenuLink" 
                            role="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false"
                        >
                            Liste
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="listsDropdownMenuLink">
                            <li>
                                <a  class="dropdown-item" 
                                    href="<?= $P ?>/staff/all"
                                >
                                    Tutti gli Staff
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item" 
                                    href="<?= $P ?>/staff/current"
                                >
                                    Staff per il <?= date(format: "Y") ?>
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item" 
                                    href="<?= $P ?>/staff/csi"
                                >
                                    Tesseramenti per C.S.I.
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item" 
                                    href="<?= $P ?>/staff/t_shirts"
                                >
                                    Tutte le maglie
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item" 
                                    href="<?= $P ?>/staff/church_leaderboard"
                                >
                                    Classifica parrocchiale
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item" 
                                    href="<?= $P ?>/staff/edizione"
                                >
                                    Tutte le edizioni
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                
                <li class="nav-item dropdown">
                    <a  class="nav-link text-reset dropdown-toggle"
                        href="#" 
                        id="themeDropdownMenuLink" 
                        role="button" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                    >
                        Tema
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="themeDropdownMenuLink">
                        <li>
                            <a  class="dropdown-item" 
                                href="#" 
                                data-bs-theme-value="light"
                                title="Imposta il tema chiaro"
                            >
                                <i class="bi bi-sun"></i>
                                Chiaro
                            </a>
                        </li>
                        <li>
                            <a  class="dropdown-item" 
                                href="#" 
                                data-bs-theme-value="dark"
                                title="Imposta il tema scuro"
                            >
                                <i class="bi bi-moon"></i>
                                Scuro 
                            </a>
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