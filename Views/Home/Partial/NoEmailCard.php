<?php
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

        const a = document.createElement('a');
        a.href = `<?= $P ?>/staff/edit_anagrafica?id=${i.id}`;
        a.title = `Modifica i dati di ${i.nome_completo}`;
        a.innerText = `${i.nome_completo} (${i.eta})`;
        a.className = 'link-underline-opacity-0 link-secondary';

        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.appendChild(a);

        return li;
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>
