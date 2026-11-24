<?php
    $card_name= 't-shirts';
    $card_title = 'Magliette';
    $card_json_base = '/t_shirts';
    $card_js_mapper = 't_shirts_js_decoder';
    $card_nothing_found = '<li class="list-group-item user-select-none">Nessuna maglia trovata</li>'
?>

<script>
    function t_shirts_js_decoder(i) {
        return $('<li>').addClass('list-group-item').append(
            $('<strong>').text(i.taglia),
            ': ',
            $('<output>').addClass('font-monospace').text(i.numero),
        );
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>