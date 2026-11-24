const $cf = $('#cf');
const $compleanno = $('#compleanno');
const $provenienza = $('#provenienza');
const $resp = $('#error-body');
const $btn = $('#submit');

function checkCF()
{
    $btn.prop('disabled', false);
    $resp.parent().addClass('d-none');
    $resp.text('');
    $cf.removeClass('is-valid', 'is-invalid');
    const code = $cf.val().toUpperCase().trim();
    if (code == '')
        return;
    
    if (!CodiceFiscale.check(code))
    {
        $btn.prop('disabled', true);
        $cf.addClass('is-invalid');
        $resp.parent().removeClass('d-none');
        $resp.text('Codice fiscale non valido. Controlla di non aver inserito spazi o caratteri non validi.');
        return;
    }
    $cf.addClass('is-valid');
    
    const inverse = CodiceFiscale.computeInverse(code);
    if (!$compleanno.val()) $compleanno.val(inverse.birthday);
    if (!$provenienza.val()) $provenienza.val(inverse.birthplace + ', ' + inverse.birthplaceProvincia);
    const message = [];
    if ($compleanno.val() != '' && $compleanno.val() !== inverse.birthday) {
        $btn.prop('disabled', true);
        message.push('Data di nascita non corrispondente a quella nel codice fiscale!');
    }
    if ($provenienza.val() != '') {
        const p = $provenienza.val().trim().toLowerCase();
        const invBirthPlace1 = inverse.birthplace + ', ' + inverse.birthplaceProvincia;
        const invBirthPlace2 = inverse.birthplace + ',' + inverse.birthplaceProvincia;
        if (p !== invBirthPlace1.toLowerCase() &&
            p !== invBirthPlace2.toLowerCase() && 
            inverse.birthplaceProvincia.toUpperCase() !== 'EE')
        {
            $btn.prop('disabled', true);
            message.push('Luogo di nascita non corrispondente a quello nel codice fiscale!');
            message.push('Comune di nascita del codice fiscale: ' + 
                inverse.birthplace);
            message.push('Provincia di nascita del codice fiscale: ' + 
                inverse.birthplaceProvincia);
            if (p.includes(','))
            {
                const [comune, provincia] = p.split(',');

                message.push('Comune di nascita inserito: ' + 
                    comune.trim().toUpperCase());
                message.push('Provincia di nascita inserita: ' + 
                    provincia.trim().toUpperCase());
            } else {
                message.push('Provincia non inserita!');
                message.push('Inserisci i dati nel seguente formato:');
                message.push('Comune, PROVINCIA (due lettere)');
                message.push('Esempi:');
                message.push('Livorno, LI');
                message.push('La Spezia, SP');
            }
        }
    }
    if (message.length > 0)
    {
        $resp.parent().removeClass('d-none');
        $resp.text(message.join('\n'));
    }
}
$cf.add($compleanno).add($provenienza).on('input', checkCF);
