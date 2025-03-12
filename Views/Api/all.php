<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<ul class="list-group">
    <?php foreach ($tokens as $token) { ?>
        <li class="list-group-item">
            #<?= $token->Id ?>
            -
            <?= htmlspecialchars(string: $token->Name) ?>
        </li>
    <?php } ?>
</ul>

<form action="<?= $B ?>/api/admin" method="post">
    
</form>