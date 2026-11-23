<?php
    $card_name= 'teams';
    $card_title = 'Squadre';
    $card_link = '/teams';
    $card_json_base = '/teams/list';
    $card_js_mapper = 'team_js_decoder';
    $card_nothing_found = '<li class="list-group-item user-select-none">Nessuna squadra trovata</li>'
?>

<script>
    function team_js_decoder(i) {
        const $link = $('<a>')
            .attr({
                href: `${BasePath}/teams#squadra-${i.id}`,
                title: `Vai alla squadra ${i.name}`,
            })
            .addClass('text-reset link-underline link-underline-opacity-0')
            .text(`${i.name} (${i.sport})`);
        return $('<li>').addClass('list-group-item').append($link).get(0);
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>