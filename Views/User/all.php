<h1>
    Lista utenti
</h1>
<div class="container">
    <?php foreach($users as $user_info) { ?>
        <div class="col col-4" id="user-<?= $user_info->Id ?>">
            <div class="card" style="width: 18rem;">
                <div class="card-header">
                    <?= htmlspecialchars(string: $user_info->Name) ?>
                    &nbsp;
                    <span class="user-select-none text-body-secondary">
                        #<?= $user_info->Id ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($user_info->RealName) && !empty($user_info->IdStaff)) { ?>
                        <h5 class="card-title mb-2">
                            <a 
                                href="/staff/view?id=<?= $user_info->IdStaff ?>" 
                                class="text-reset link-underline-opacity-0" 
                                title="Vai">
                                <?= htmlspecialchars(string: $user_info->RealName) ?>
                            </a>
                        </h5>
                    <?php } ?>
                    <?php if ($user_info->IsAdmin) { ?>
                        <h6 class="card-subtitle mb-2 user-select-none text-body-secondary">
                            Amministratore
                        </h6>
                    <?php } ?>
                    <?php if ($user_info->IsBanned) { ?>
                        <h6 class="card-subtitle mb-2 user-select-none text-body-secondary">
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
                    
                    <a href="/user/view?id=<?= htmlspecialchars(string: $user_info->Id) ?>" class="card-link">
                        Dettagli
                    </a>
                </div>
            </div>
        </div>
    <?php } ?>
</div>