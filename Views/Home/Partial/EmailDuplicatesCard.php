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
        if (i.total > 2)
            li.classList.add('text-danger');
        else
            li.classList.add('text-warning');
        
        li.innerText = `${i.email} (${i.total}):`;

        const inner_ul = document.createElement('ul');
        inner_ul.className = 'list-group-flush d-inline-block ms-3';
        li.appendChild(inner_ul);

        for (const a of i.who)
        {
            const inner_li = document.createElement('li');
            inner_li.className = 'list-group-item p-0 d-inline';

            const a_el = document.createElement('a');
            a_el.href = `<?= $P ?>/staff/edit_anagrafica?id=${a.id}`;
            a_el.innerText = a.name;
            a_el.className = 'link-underline-opacity-0 text-reset me-1';
            inner_li.appendChild(a_el);
            inner_ul.appendChild(inner_li);
        }

        return li;
    }
</script>

<?php require __DIR__ . '/AsyncCard.php'; ?>
