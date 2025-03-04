<h1>
    Lista utenti
</h1>
<div class="row">
    <?php foreach($users as $user_info) { ?>
        <div class="col col-xs-12 col-sm-6 col-md-4 col-xl-3" id="user-<?= $user_info->Id ?>">
            <div class="card m-1">
                <div class="card-header">
                    <span class="user-select-none text-secondary">
                        #<?= $user_info->Id ?>
                    </span>
                    &nbsp;
                    <a 
                        title="Vedi nel dettaglio"
                        href="<?= $B ?>/user/view?id=<?= $user_info->Id ?>"
                        class="text-reset link-underline link-underline-opacity-0"
                    >
                        <?= htmlspecialchars(string: $user_info->Name) ?>
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($user_info->RealName) && !empty($user_info->IdStaff)) { ?>
                        <h5 class="card-title mb-2">
                            <a 
                                href="<?= $B ?>/staff/view?id=<?= $user_info->IdStaff ?>" 
                                class="text-reset link-underline-opacity-0" 
                                title="Vai">
                                <?= htmlspecialchars(string: $user_info->RealName) ?>
                            </a>
                        </h5>
                    <?php } ?>
                    <?php if ($user_info->IsAdmin) { ?>
                        <h6 class="card-subtitle mb-2 user-select-none text-secondary">
                            Amministratore
                        </h6>
                    <?php } ?>
                    <?php if ($user_info->IsBanned) { ?>
                        <h6 class="card-subtitle mb-2 user-select-none text-secondary">
                            Bloccato
                        </h6>
                    <?php } ?>
                    
                    <?php if (empty($user_info->LoginTime)) { ?>
                        <p class="card-text">
                            Mai collegato
                        </p>
                    <?php } else { ?>
                        <p class="card-text">
                            Online <?= htmlspecialchars(string: $user_info->TimeLoggedMessage()) ?>.
                        </p>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>