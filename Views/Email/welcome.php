<?php

assert(isset($domain) && is_string($domain), 'var $domain not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($user) && $user instanceof \Amichiamoci\Models\User, 'var $user not valid');
assert(isset($password) && is_string($password), 'var $password not valid');

?>

<h3>Benvenuto/a</h3>
<h5>
    È sempre bello vedere dei nuovi staffisti!
</h5>
<p>
    È appena stato creato un utente sul portale con questa email.
    Ti chiediamo di loggarti nella sezione
    <a  href="https://<?= htmlspecialchars(string: $domain) ?><?= $P ?>"
        class="link"
        title="Accedi al portale staff"
    >admin</a>
    utilizzando come nome utente:
    <code style="font-family: monospace;"><?= htmlspecialchars(string: $user->Name) ?></code>
    e come <strong>password: </strong>
    <code style="font-family: monospace;"><?= htmlspecialchars(string: $password) ?></code>
</p>
<p>
    Una volta loggato potrai cambiare sia nome utente che password.
    <br>
    Nel caso tu non riesca ad accedere con le credenziali appena fornite
    prova a cancellare i cookie e riprovare dopo qualche minuto.
    <br>
    Se il problema dovesse persistere, provare a contattare un amministratore.
</p>
<hr>
<p>
    Dal portale ti sarà possibile inserire i tuoi dati anagrafici, scegliendo la mail con cui gestire l'account
    (puoi quindi non scegliere questa) e, anno per anno, registrarti come staffista all'edizione corrente.
</p>
<p>
    In caso di problemi contatta tempestivamente gli staffisti adibiti alla gestione del portale.
</p>
<br>
<p style="font-size: smaller; user-select: none;">
    Ecco una serie di informazioni che non ti interesseranno, ma io le metto ugualmente:
</p>
<ul>
    <li>
        Il tuo Id utente è <output style="user-select:none;"><?= $user->Id ?></output>
    </li>
</ul>
