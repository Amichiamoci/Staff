<?php
use Models\User;
?>

<nav class="navbar navbar-expand-sm navbar-toggleable-sm border-bottom box-shadow mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">
            <img src="/Public/images/banner.png" 
                height="48"
                alt="Logo di Amichiamoci" title="Vai alla pagina principale" 
                class="d-inline-block align-text-top"/>
        </a>
        <button class="navbar-toggler" 
                type="button"
                role="button"
                data-toggle="collapse"
                data-bs-toggle="collapse"
                data-target="#navbar-collapse"
                data-bs-target="#navbar-collapse" 
                aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="Toggle navigation">

            
        </button>
        <div id="navbar-collapse"
            class="navbar-collapse collapse d-sm-inline-flex justify-content-between">
            <ul class="navbar-nav flex-grow-1">
                
                
                <li class="nav-item dropdown">
                    <a class="nav-link text-reset dropdown-toggle"
                        href="#" 
                        id="themeDropdownMenuLink" 
                        role="button" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                        Tema
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="themeDropdownMenuLink">
                        <li>
                            <a class="dropdown-item" 
                                href="javascript:setLightTheme()">
                                
                                Chiaro
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" 
                                href="javascript:setDarkTheme()">
                                
                                Scuro 
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <?php if (isset($user)) { ?>
                <a href="/user/me" 
                    class="nav-link text-reset"
                    title="Vai alla pagina del profilo">


                    <?= htmlspecialchars(string: $user->Label()) ?>
                </a>
            <?php } ?>
        </div>
    </div>
</nav>