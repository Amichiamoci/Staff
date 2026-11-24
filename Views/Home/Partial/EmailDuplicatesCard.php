<?php

assert(isset($P) && is_string($P), 'var $P not valid');

    $card_name= 'email_duplicates';
    $card_title = 'Email duplicate';
    $card_json_base = '/duplicate_emails';
    $card_js_mapper = 'email_duplicates_js_decoder';
    $card_church_year_ignore = true;
    $card_nothing_found = '<li class="list-group-item user-select-none text-success">Nessuna email duplicata!</li>';
?>

<script>
    function email_duplicates_js_decoder(i) {
        const $summary = $('<summary>').addClass('text-truncate')
            .addClass(i.total > 2 ? 'text-danger' : 'text-warning').text(`${i.email} (${i.total})`);
        const $list = $('<ul>').addClass('list-group-flush d-inline-block ms-1');

        for (const person of i.who) {
            const $link = $('<a>').attr({
                href: `<?= $P ?>/staff/edit_anagrafica?id=${person.id}`,
                title: `Modifica i dati di ${person.name}`,
            }).addClass('link-underline-opacity-0 link-secondary p-0 m-0').text(person.name);
            $('<li>').addClass('list-group-item p-0').append($link).appendTo($list);
        }

        const $details = $('<details>').append($summary, $list);
        return $('<li>').addClass('list-group-item').append($details);
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>
