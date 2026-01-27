<?php
    $card_name= 'email_duplicates';
    $card_title = 'Email duplicate';
    $card_json_base = '/duplicate_emails';
    $card_js_mapper = 'email_duplicates_js_decoder';
    $card_church_year_ignore = true;
    $card_nothing_found = '<li class="list-group-item user-select-none text-success">Nessuna email duplicata!</li>';
?>

<script>
    function email_duplicates_js_decoder(i) {
        const li = document.createElement('li');
        li.className = 'list-group-item';

        const details = document.createElement('details');
        li.appendChild(details);

        const summary = document.createElement('summary');
        summary.innerText = `${i.email} (${i.total})`;
        if (i.total > 2)
            summary.classList.add('text-danger');
        else
            summary.classList.add('text-warning');
        details.appendChild(summary);

        const ul = document.createElement('ul');
        ul.className = 'list-group-flush d-inline-block ms-1';
        details.appendChild(ul);

        for (const a of i.who)
        {
            const inner_li = document.createElement('li');
            inner_li.className = 'list-group-item p-0';
            ul.appendChild(inner_li);

            const a_el = document.createElement('a');
            a_el.href = `<?= $P ?>/staff/edit_anagrafica?id=${a.id}`;
            a_el.innerText = a.name;
            a_el.className = 'link-underline-opacity-0 link-secondary p-0 m-0';
            inner_li.appendChild(a_el);
        }

        return li;
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>
