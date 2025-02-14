<?php
    $card_name= 'teams';
    $card_title = 'Squadre';
    $card_json_base = '/teams/list';
    $card_js_mapper = 'team_js_decoder';
    $card_nothing_found = '<li class="list-group-item user-select-none">Nessuna squadra trovata</li>'
?>

<script>
    function team_js_decoder(i) {
        const a = document.createElement('a');
        a.href = `/teams/view?id=${i.id}`;
        a.className = 'text-reset link-underline link-underline-opacity-0';
        a.innerText = `${i.name} (${i.sport})`;

        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.appendChild(a);
        return li;
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>