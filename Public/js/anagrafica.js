/**
 * @type {HTMLInputElement}
 */
const cf = document.getElementById('cf');
/**
 * @type {HTMLInputElement}
 */
const compleanno = document.getElementById('compleanno');
/**
 * @type {HTMLInputElement}
 */
const provenienza = document.getElementById('provenienza');
/**
 * @type {HTMLParagraphElement}
 */
const resp = document.getElementById('error-body');
/**
 * @type {HTMLInputElement}
 */
const btn = document.getElementById('submit');

function checkCF()
{
    btn.disabled = false;
    resp.parentElement.classList.add('d-none');
    resp.innerHTML = '';
    const code = cf.value.toUpperCase().trim();
    if (code == '')
        return;
    
    if (!CodiceFiscale.check(code))
    {
        btn.disabled = true;
        resp.parentElement.classList.remove('d-none');
        resp.innerHTML = 'Codice fiscale non valido!<br>Controlla di non aver inserito spazi o caratteri non validi';
        return;
    }
    
    const inverse = CodiceFiscale.computeInverse(code);
    compleanno.value = inverse.birthday;
    provenienza.value = inverse.birthplace + ', ' + inverse.birthplaceProvincia;
    let message = [];
    if (compleanno.value != '' && compleanno.value !== inverse.birthday) {
        btn.disabled = true;
        message.push('Data di nascita non corrispondente a quella nel codice fiscale!');
    }
    if (provenienza.value != '') {
        const p = provenienza.value.trim().toLowerCase();
        const invBirthPlace1 = inverse.birthplace + ', ' + inverse.birthplaceProvincia;
        const invBirthPlace2 = inverse.birthplace + ',' + inverse.birthplaceProvincia;
        if (p !== invBirthPlace1.toLowerCase() &&
            p !== invBirthPlace2.toLowerCase() && 
            inverse.birthplaceProvincia.toUpperCase() !== 'EE')
        {
            btn.disabled = true;
            message.push('Luogo di nascita non corrispondente a quello nel codice fiscale!');
            message.push('Comune di nascita del codice fiscale: <code style="display:inline-block;font-family:monospace;">' + 
                inverse.birthplace.replace(/ /g, '_') + '</code>');
            message.push('Provincia di nascita del codice fiscale: <code style="display:inline-block;font-family:monospace;">' + 
                inverse.birthplaceProvincia.replace(/ /g, '_') + '</code>');
            if (p.includes(','))
            {
                const [comune, provincia] = p.split(',');

                message.push('Comune di nascita inserito: <code style="display:inline-block;font-family:monospace;">' + 
                    comune.trim().toUpperCase().replace(/ /g, '_') +'</code>');
                message.push('Provincia di nascita inserita: <code style="display:inline-block;font-family:monospace;">' + 
                    provincia.trim().toUpperCase().replace(/ /g, '_') +'</code>');
            } else {
                message.push('Provincia non inserita!');
                message.push('Inserisci i dati nel segunete formato:');
                message.push('Comune, PROVINCIA (due lettere)');
                message.push('Esempi:');
                message.push('Livorno, LI');
                message.push('La Spezia, SP');
            }
        }
    }
    if (message.length > 0)
    {
        resp.parentElement.classList.remove('d-none');
        resp.innerHTML = message.join('<br>');
    }
}
compleanno.addEventListener('input', checkCF);
provenienza.addEventListener('input', checkCF);
cf.addEventListener('input', checkCF);