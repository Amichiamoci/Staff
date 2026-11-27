function updateMatchWarning($card) {
    const date = $card.attr('data-match-date');
    const $scores = $card.find('[data-match-results] > ul');
    $card.toggleClass('border-warning', Boolean(date) && date < $card.attr('data-match-today')
        && $scores.length > 0 && !$scores.children('[data-saved-score]').length);
}

$(document).on('change', '.match-field-selector, .match-date-selector, .match-time-selector', function () {
    const $input = $(this);
    const field = this.type === 'select-one' ? 'field' : this.type;
    const value = $input.val();
    $input.prop('disabled', true).removeClass('is-invalid is-valid');
    $.ajax({
        method: 'POST',
        url: BasePath + '/sport/match_' + field,
        data: {
            match: $input.attr('data-match'),
            [field]: value,
            csrf_token: $input.closest('[data-match-csrf]').attr('data-match-csrf'),
        },
    }).done(function () {
        if (field === 'date') {
            const $card = $input.closest('[data-match-csrf]').attr('data-match-date', value);
            const $opener = $card.find('[data-board-open]');
            $opener.prop('hidden', value !== $card.attr('data-match-today'));
            updateMatchWarning($card);
        }
        if (field === 'field') {
            $input.closest('[data-match-csrf]').find('[data-board-template]').remove();
        }
        $input.addClass('is-valid');
        setTimeout(() => $input.removeClass('is-valid'), 2000);
    }).fail(function (error) {
        $input.addClass('is-invalid');
        alert(error.responseJSON?.message ?? 'Modifica non salvata.');
    }).always(function () {
        $input.prop('disabled', false);
    });
});

//
// Results handling
//

$(document).on('click', '.match-forfeit-trigger', function () {
    const dialog = document.getElementById($(this).attr('aria-controls'));
    $(dialog).find('form')[0].reset();
    dialog.showModal();
});

$(document).on('click', '.match-forfeit-cancel', function () {
    $(this).closest('dialog')[0].close();
});

function hasResult($list) {
    return $list.children('[data-result]').length > 0;
}

const remove_this_result = function() {
    const $btn = $(this);
    const result = $btn.attr('data-result');
    const $input = $(`input[data-result="${result}"]`);
    $btn.prop('disabled', true);
    $input.prop('disabled', true);

    if ($input.val() !== '')
    {
        if (!confirm('Sicuro di voler cancellare il risultato? Non sarà possibile tornare indietro'))
        {
            $btn.prop('disabled', false);
            $input.prop('disabled', false);
            return;
        }
    }
    $.ajax({
        method: 'POST',
        url: BasePath + '/sport/match_remove_score',
        data: {
            'score': result,
            'csrf_token': $btn.closest('[data-match-csrf]').attr('data-match-csrf'),
        },
    }).done(function() {
        const $card = $btn.closest('[data-match-csrf]');
        $card.find('[data-board-template]').remove();
        const $list = $btn.closest('ul');
        $btn.closest('li').remove();
        updateMatchWarning($card);
        $list.find('.match-result-add[data-single-result]').prop('disabled', hasResult($list));
    }).fail(function(err) {
        $btn.prop('disabled', false);
        $input.prop('disabled', false);
        console.log(`Something went wrong: ${err.status}`);
        
        const response = err.responseJSON;
        if (response && response.message)
        {
            alert(response.message);
        }
    })
};

const edit_this_result = function () {
    const $input = $(this);
    const result = $input.attr('data-result');
    const $btn = $(`button[data-result="${result}"]`);
    if (!(new RegExp('^' + $input.attr('pattern') + '$')).test($input.val()))
    {
        return;
    }

    if ($input.attr('data-timeout') != '')
    {
        clearTimeout($input.attr('data-timeout'));
    }

    const id = setTimeout(function () {
        $input.prop('disabled', true);
        $btn.prop('disabled', true);
        $.ajax({
            method: 'POST',
            url: BasePath + '/sport/match_edit_score',
            data: {
                'score': $input.attr('data-result'),
                'csrf_token': $input.closest('[data-match-csrf]').attr('data-match-csrf'),
                'home': $input.val().split('-')[0],
                'guest': $input.val().split('-')[1],
            },
        }).done(function() {
            const $card = $input.closest('[data-match-csrf]');
            $card.find('[data-board-template]').remove();
            $input.closest('li').attr('data-saved-score', '');
            updateMatchWarning($card);
            $input.attr('data-timeout', '');
            $input.prop('disabled', false);
            $btn.prop('disabled', false);

            // Signal the user of the sync ended
            $input.addClass('is-valid');
            $input.removeClass('is-invalid');
            setTimeout(function () {
                $input.removeClass('is-valid');
            }, 2000);
        }).fail(function(err) {
            $input.attr('data-timeout', '');
            $input.prop('disabled', false);
            $btn.prop('disabled', false);
            console.log(`Something went wrong: ${err.status}`);
            
            const response = err.responseJSON;
            if (response && response.message)
            {
                alert(response.message);
            }
        });
    }, 500);
    $input.attr('data-timeout', id);
};

$(document).on('click', '.match-result-remove', remove_this_result);
$(document).on('input', '.match-result-edit', edit_this_result);

$(document).on('click', '.match-result-add', function() {
    const $btn = $(this);
    const match = $btn.attr('data-match');
    const $list = $btn.closest('ul');
    const singleResult = $btn.is('[data-single-result]');
    if ($btn.prop('disabled') || (singleResult && hasResult($list))) {
        return;
    }
    $btn.prop('disabled', true);
    $.ajax({
        method: 'POST',
        url: BasePath + '/sport/match_add_score',
        data: {
            'match': match,
            'csrf_token': $btn.closest('[data-match-csrf]').attr('data-match-csrf'),
        },
    }).done(function(resp) {
        $btn.closest('[data-match-csrf]').find('[data-board-template]').remove();
        if (resp)
        {
            console.log('Add result with id ' + resp.id);
        }

        const $input = $('<input type="text">')
            .attr('data-result', resp.id)
            .attr('pattern', '[0-9]{1,8}\\s{0,}-\\s{0,}[0-9]{1,8}')
            .attr('placeholder', '0 - 0')
            .addClass('form-control match-result-edit');
        $input.attr('aria-label', 'Punteggio casa e ospiti');

        const $remove_btn = $('<button>', {type: 'button', 'aria-label': 'Rimuovi punteggio'})
            .append($('<i>', {class: 'bi bi-trash3', 'aria-hidden': 'true'}))
            .attr('data-result', resp.id)
            .attr('title', 'Rimuovi punteggio')
            .addClass('btn btn-outline-danger match-result-remove');

        const $group = $('<div class="input-group mb-2"></div>');
        $group.append($input);
        $group.append($remove_btn);

        const $li = $('<li class="list-group-item"></li>')
            .attr('data-result', resp.id);
        $li.append($group);
        $li.insertBefore($btn.closest('li'));
        $btn.prop('disabled', singleResult && hasResult($list));

        $input.focus();
    }).fail(function(err) {
        $btn.prop('disabled', false);
        console.log(`Something went wrong: ${err.status}`);
        
        const response = err.responseJSON;
        if (response && response.message)
        {
            alert(response.message);
        }
    });
});
