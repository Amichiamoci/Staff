<?php
    $card_name= 'cron';
    $card_title = 'Stato CRON';
    //$card_link = '';
    $card_church_year_ignore = 'yes';
    $card_json_base = '/cron';
    $card_js_mapper = 'cron_js_decoder';
    $card_nothing_found = '<li class="list-group-item user-select-none text-warn">Nessuno script CRON trovato</li>';
?>

<script>
    function cron_js_decoder(i) {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerText = `${i.name}: ${i.log}`;
        return li;
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>