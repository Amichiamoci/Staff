<?php if ($user->Admin) { ?>
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

<h2>
    Punteggi per parrocchia <?= $anno ?>
</h2>

<table class="table table-striped border border-1">
    <thead>
        <tr>  
            <th>
                Parrocchia
            </th>
            <th>
                Punti
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($classifica as $punteggio) { ?>
            <tr>
                <td>
                    <?= htmlspecialchars(string: $punteggio->Parrocchia->Nome) ?>
                </td>
                <td>
                    <?php if ($user->Admin) { ?>
                        <input 
                            type="number" 
                            data-church="<?= $punteggio->Parrocchia->Id ?>"
                            data-edition="<?= $punteggio->Edizione ?>"
                            value="<?= empty($punteggio->Punteggio) ? '' : htmlspecialchars(string: $punteggio->Punteggio) ?>"
                            class="form-control church-score-update"
                        >
                    <?php } else { ?>
                        <output class="font-monospace">
                            <?= empty($punteggio->Punteggio) ? '' : htmlspecialchars(string: $punteggio->Punteggio) ?>
                        </output>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<script>
    $('.church-score-update').on('input', function () {
        const $input = $(this);
        const result = $input.val();
        const church = $input.attr('data-church');
        const edition = $input.attr('data-edition');
        
        if ($input.attr('data-timeout') != '')
        {
            clearTimeout($input.attr('data-timeout'));
        }

        const id = setTimeout(function () {
            $input.prop('disabled', true);
            $.ajax({
                method: 'POST',
                url: BasePath + '/staff/church_leaderboard_edit',
                data: {
                    'score': result,
                    'church': church,
                    'edition': edition,
                },
            }).done(function() {
                $input.attr('data-timeout', '');
                $input.prop('disabled', false);

                // Signal the user of the sync ended
                $input.addClass('is-valid');
                $input.removeClass('is-invalid');
                setTimeout(function () {
                    $input.removeClass('is-valid');
                }, 2000);
            }).fail(function(err) {
                $input.attr('data-timeout', '');
                $input.prop('disabled', false);
                console.log(`Something went wrong: ${err.status}`);
                
                const response = err.responseJSON;
                if (response && response.message)
                {
                    alert(response.message);
                }
            });
        }, 500);
        $input.attr('data-timeout', id);
    });
</script>