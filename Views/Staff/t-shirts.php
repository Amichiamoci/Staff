<?php if ($user->IsAdmin) { ?>
    <div class="input-group mb-2">
        <select id="anno-selector" class="form-control">
            <?php foreach ($edizioni as $e) { ?>
                <option value="<?= $e->Year ?>" <?= ($e->Year === $anno) ? 'selected' : '' ?>>
                    <?= $e->Year ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <script>
        (() => {
            const select_a = document.getElementById('anno-selector');
            const f = () => {
                const a = Number(select_a.value);
                select_a.removeEventListener('change', f);
                window.location.replace(`?year=${a}`);
            };
            select_a.addEventListener('change', f);
        })();
    </script>
<?php } ?>

<h1>
    Lista delle maglie <?= $anno ?>
</h1>
<p class="ps-1 pe-1">
    La lista è indicativa solo delle iscrizioni pervenute ad ora, non tiene quindi conto di eventuali 
    maglie "aggiuntive" che potrebbero essere necessarie.
</p>
<?php
    function canonical_columns(): array
    {
        return [
            "cognome", 
            "nome", 
            "parrocchia", 
            "colore",
            "taglia", 
            'xxs',
            'xs', 
            's', 
            'm',
            'l',
            'xl',
            'xxl',
            '3xl'
        ];
    }

    function column_score(string $str): int
    {
        if ($index = array_search(needle: strtolower(string: $str), haystack: canonical_columns()))
        {
            return (int)$index;
        }
        return -strlen(string: $str) - 1;
    }
    function render_table(array $data): void { ?>    
    <table class="table table-striped border border-1">
        <thead>
            <?php 
                if (count(value: $data) > 0)
                { 
                    $columns = array_keys($data[0]);
                    uasort(array: $columns, callback: function (string|int $left, string|int $right): int
                    {
                        return column_score(str: (string)$left) - column_score(str: (string)$right);
                    });
                ?>
                    <tr>
                        <?php foreach ($columns as $col) { ?>
                            <th>
                                <?= htmlspecialchars(string: $col) ?>
                            </th>
                        <?php } ?>
                    </tr>
                <?php
                } 
            ?>
        </thead>
        <tbody>
            <?php foreach ($data as $row) { ?>
                <tr>
                    <?php foreach ($columns as $col) { ?>
                        <td 
                            spellcheck="false" 
                            contenteditable="true"
                            <?= (is_numeric(value: $row[$col])) ? 'pattern="[0-9]+"' : '' ?>
                        >
                            <?= htmlspecialchars(string: $row[$col]) ?>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php }

render_table(data: $riepilogo);
render_table(data: $lista_completa);