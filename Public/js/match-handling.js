//
// Update matches results, date, time and whereabout
//
$('.match-field-selector').on('input', function() {
    const $selector = $(this);
    const match = $selector.attr('data-match');
    const field = $selector.val();
    $selector.prop('disabled', true);
    $.ajax({
        method: 'POST',
        url:BasePath + '/sport/match_field',
        data: {
            'match': match,
            'field': field,
        },
    }).done(function() {
        $selector.prop('disabled', false);
        console.log(`Field of match #${match} set to ${(field === '' ? 'null' : field)}`);
        $selector.addClass('is-valid');
        $selector.removeClass('is-invalid');
        setTimeout(function (){
            $selector.removeClass('is-valid');
        }, 2000);
    }).fail(function(err) {
        $selector.prop('disabled', false);
        console.log(`Something went wrong: ${err.status}`);
        $selector.addClass('is-invalid');
        $selector.removeClass('is-valid');
        setTimeout(function (){
            $selector.removeClass('is-invalid');
        }, 4000);
        
        const response = err.responseJSON;
        if (response.message)
        {
            alert(response.message);
        }
    })
});
$('.match-date-selector').on('input', function() {
    const $selector = $(this);
    const match = $selector.attr('data-match');
    const date = $selector.val();
    $selector.prop('disabled', true);
    $.ajax({
        method: 'POST',
        url:BasePath + '/sport/match_date',
        data: {
            'match': match,
            'date': date,
        },
    }).done(function() {
        $selector.prop('disabled', false);
        console.log(`Date of match #${match} set to ${(date === '' ? 'null' : date)}`);
        $selector.addClass('is-valid');
        $selector.removeClass('is-invalid');
        setTimeout(function (){
            $selector.removeClass('is-valid');
        }, 2000);
    }).fail(function(err) {
        $selector.prop('disabled', false);
        console.log(`Something went wrong: ${err.status}`);
        $selector.addClass('is-invalid');
        $selector.removeClass('is-valid');
        setTimeout(function (){
            $selector.removeClass('is-invalid');
        }, 4000);
        
        const response = err.responseJSON;
        if (response.message)
        {
            alert(response.message);
        }
    })
});
$('.match-time-selector').on('input', function() {
    const $selector = $(this);
    const match = $selector.attr('data-match');
    const time = $selector.val();
    $selector.prop('disabled', true);
    $.ajax({
        method: 'POST',
        url:BasePath + '/sport/match_time',
        data: {
            'match': match,
            'time': time,
        },
    }).done(function() {
        $selector.prop('disabled', false);
        console.log(`Time of match #${match} set to ${(time === '' ? 'null' : time)}`);
        $selector.addClass('is-valid');
        $selector.removeClass('is-invalid');
        setTimeout(function (){
            $selector.removeClass('is-valid');
        }, 2000);
    }).fail(function(err) {
        $selector.prop('disabled', false);
        console.log(`Something went wrong: ${err.status}`);
        $selector.addClass('is-invalid');
        $selector.removeClass('is-valid');
        setTimeout(function (){
            $selector.removeClass('is-invalid');
        }, 4000);
        
        const response = err.responseJSON;
        if (response.message)
        {
            alert(response.message);
        }
    })
});

//
// Results handling
//

const remove_this_result = function() {
    const $btn = $(this);
    const result = $btn.attr('data-result');
    const $input = $(`input[data-result="${result}"]`);
    $btn.prop('disabled', true);
    $input.prop('disabled', true);

    if ($input.val() !== '')
    {
        if (!confirm('Sicuro di voler cancellare il risultato? Non sarà possibile tornre indietro'))
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
        },
    }).done(function() {
        $(`li[data-result="${result}"]`).remove();
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
    if (!(new RegExp($input.attr('pattern'))).test($input.val()))
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
                'home': $input.val().split('-')[0],
                'guest': $input.val().split('-')[1],
            },
        }).done(function() {
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

$('.match-result-remove').click(remove_this_result);
$('.match-result-edit').on('input', edit_this_result);

$('.match-result-add').click(function() {
    const $btn = $(this);
    const match = $btn.attr('data-match');
    $btn.prop('disabled', true);
    $.ajax({
        method: 'POST',
        url: BasePath + '/sport/match_add_score',
        data: {
            'match': match,
        },
    }).done(function(resp) {
        $btn.prop('disabled', false);
        if (resp)
        {
            console.log('Add result with id ' + resp.id);
        }

        const $input = $('<input type="text">')
            .attr('data-result', resp.id)
            .attr('pattern', '[0-9]{1,2}\\s{0,}-\\s{0,}[0-9]{1,2}')
            .attr('placeholder', '0 - 0')
            .addClass('form-control match-result-edit');
        $input.on('input', edit_this_result);

        const $remove_btn = $('<button type="button"><i class="bi bi-trash3"></i></button>')
            .attr('data-result', resp.id)
            .attr('title', 'Rimuovi punteggio')
            .addClass('btn btn-outline-danger match-result-remove')
            .click(remove_this_result);

        const $group = $('<div class="input-group mb-2"></div>');
        $group.append($input);
        $group.append($remove_btn);

        const $li = $('<li class="list-group-item"></li>')
            .attr('data-result', resp.id);
        $li.append($group);
        $li.insertBefore($(`ul[data-match="${match}"] > li:last-child`));

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
