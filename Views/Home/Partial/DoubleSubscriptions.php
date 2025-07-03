<?php
    $card_name= 'double_subscriptions';
    $card_title = 'Iscrizioni doppie';
    $card_link = '/staff/anagrafiche?year=' . date(format: 'Y');
    $card_json_base = '/staff/double_subscriptions';
    $card_js_mapper = 'double_subscription_js_decoder';
    $card_church_year_ignore = true;
    $card_nothing_found = '<li class="list-group-item user-select-none text-success">Nessuna iscrizione doppia!</li>';
?>

<script>
    function double_subscription_js_decoder(i) {
        const li = document.createElement('li');
        li.className = 'list-group-item text-warning';
        li.innerText = `${i.name}: ${i.church}`;
        return li;
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>