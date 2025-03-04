<?php
    $card_name= 't-shirts';
    $card_title = 'Magliette';
    $card_json_base = '/t_shirts';
    $card_js_mapper = 't_shirts_js_decoder';
    $card_nothing_found = '<li class="list-group-item user-select-none">Nessuna maglia trovata</li>'
?>

<script>
    function t_shirts_js_decoder(i) {
        const li =document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = `<strong>${i.taglia}</strong>: <output class="font-monospace">${i.numero}</output>`;
        return li;
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>