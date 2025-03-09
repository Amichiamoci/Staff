<div class="card">
    <div class="card-body">
        <div class="card-title">
            <strong>
                Compleanni di oggi
            </strong>
        </div>
        <ul class="list-group-flush p-0 overflow-y-auto" style="max-height: 250px;">
            <?php foreach ($compleanni as $compleanno) { ?>
                <li class="list-group-item">
                    <?= htmlspecialchars(string: $compleanno) ?>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>