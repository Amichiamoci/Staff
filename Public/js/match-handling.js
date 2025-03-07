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