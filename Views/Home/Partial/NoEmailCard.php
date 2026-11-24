<?php

assert(isset($P) && is_string($P), 'var $P not valid');

    $card_name= 'no_email';
    $card_title = 'Anagrafiche senza email';
    $card_subtitle = 
        'Coloro che non hanno un\'email non possono iscriversi in autonomia tramite l\'app. ' . 
        'Questo comporta che siano gli staff a dover gestire le iscrizioni per queste persone.';
    $card_json_base = '/no_email';
    $card_js_mapper = 'no_email_js_decoder';
    $card_church_year_ignore = true;
    $card_nothing_found = '<li class="list-group-item user-select-none text-success">Nessuna anagrafica senza email!</li>';
?>

<script>
    function no_email_js_decoder(i) {
        const $link = $('<a>').attr({
            href: `<?= $P ?>/staff/edit_anagrafica?id=${i.id}`,
            title: `Modifica i dati di ${i.nome_completo}`,
        }).addClass('link-underline-opacity-0 link-secondary').text(`${i.nome_completo} (${i.eta})`);
        return $('<li>').addClass('list-group-item').append($link);
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>
