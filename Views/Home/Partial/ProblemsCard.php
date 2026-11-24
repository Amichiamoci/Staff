<?php
    $card_name= 'problems';
    $card_title = 'Problemi iscrizioni';
    $card_link = '/staff';
    $card_json_base = '/staff/problems';
    $card_js_mapper = 'problems_js_decoder';
    $card_nothing_found = '<li class="list-group-item user-select-none text-success">Nessun problema con le iscrizioni!</li>';
?>

<script>
    function problems_js_decoder(i) {
        return $('<li>').addClass('list-group-item text-warning')
            .toggleClass('text-danger', Number(i.count) > 1).text(`${i.name}: ${i.count}`);
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>