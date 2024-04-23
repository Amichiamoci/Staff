<?php
    if (!isset($anagrafica) || !($anagrafica instanceof Anagrafica))
    {
        $anagrafica = new Anagrafica(null, null, null, null);
    }
    $is_editing = isset($is_editing) && $is_editing;
    $is_extern = isset($is_extern) && $is_extern || !isset(User::$Current);
?>
<div class="grid">
    <div class="column col-100">
        <form method="post" enctype="multipart/form-data">
            <?php if (isset($year) && is_int($year) && $year !== 0) { ?>
                <input type="hidden" name="year" value="<?= $year ?>">
            <?php } ?>
            <?php if ($is_extern) { ?>
                <h3>Stai creando la tua scheda anagrafica</h3>
                <h4>
                    Attenzione: il form utilizza dei cookie per funzionare internamente
                </h4>
                <h5>
                    Una volta conclusa l'operazione saranno tutti cancellati in automatico.
                </h5>
                <p class="text" style="text-align: justify;">
                    Una volta registrato i tuoi dati rimarranno permanentemente nel sistema, 
                    in modo che tu non abbia bisogno di compilare questo form pi&ugrave; volte.<br>
                    In caso tu abbia inserito i tuoi dati in passato non c'&egrave; necessit&agrave;
                    di compilare questo form: comunica semplicemente agli staffisti di riferimento
                    la tua volont&agrave; di partecipare a questa edizione, dando loro:
                </p>
                <ul>
                    <li>
                        Taglia della maglia
                    </li>
                    <li>
                        Soldi dell'iscrizione
                    </li>
                    <li>
                        Certificato medico almeno non agonistico (invia loro foto o pdf)
                    </li>
                    <li>
                        Lista delle attivit&agrave; a cui vuoi partecipare: Tornei, Maratona, etc.
                    </li>
                </ul>
            <?php } else { ?>

                <?php if (isset(User::$Current) && User::$Current->staff_id !== 0) { ?>
                    <h3>
                        Stai creando una scheda anagrafica
                    </h3>
                    <p class="text">
                        <strong>ATTENZIONE:</strong> In caso un utente col medesimo codice fiscale esista gi&agrave; nel sistema,
                        i dati parziali che inserirai sovrascriveranno quelli esistenti. Questa funzionalit&agrave;
                        &egrave; utile per aggiornare un documento scaduto o correggere eventuali dati scorretti.
                    </p>
                    <?php if ($is_editing) { ?>
                        <h3>
                            Stai modificando i dati (gi&agrave;) presenti di
                            <output style="user-select: none; text-transform: uppercase">
                                <?= htmlspecialchars($cf) ?>
                            </output>
                        </h3>
                        <p class="text">
                            Non abusare di questa funzionalit&agrave;.<br>
                            Inserisci solo i dati che vuoi modificare, lascia gli altri bianchi e rimarranno invariati.
                        </p>
                    <?php } ?>
                <?php } else { ?>
                    <h3>
                        <?= htmlspecialchars(User::$Current->name) ?>, stai creando la TUA scheda anagrafica
                    </h3>
                <?php } ?>
            <?php } ?>
            <h4>
                Altre due raccomandazioni
            </h4>
            <ul>
                <li>
                    Il file che invii del documento deve avere sia fronte che retro,
                    consigliamo di fare una foto/scansione a entrambe le facce e di concatenarle in un unico WORD/PDF
                </li>
                <li>
                    <strong>
                        La tessera sanitaria non &egrave; un documento di riconoscimento!
                    </strong>
                </li>
            </ul>
            <?php if (isset($errore) && !empty($errore)) { ?>
                <p class="error">
                    <strong><?= $errore ?></strong>
                </p>
            <?php } ?>
            <div class="input-box flex v-center wrap">

                <label for="nome">Nome</label>
                <input type="text" required name="nome" id="nome" value="<?= htmlspecialchars($anagrafica->nome) ?>" placeholder="Mario">

                <label for="cognome">Cognome</label>
                <input type="text" required name="cognome" id="cognome" value="<?= htmlspecialchars($anagrafica->cognome) ?>" placeholder="Rossi">

                <label for="compleanno">Data di nascita</label>
                <input type="date" required name="compleanno" id="compleanno" value="<?= htmlspecialchars($anagrafica->compleanno) ?>">

                <label for="provenienza">Luogo di nascita</label>
                <input type="text" required name="provenienza" value="<?= htmlspecialchars($anagrafica->proveninenza) ?>" id="provenienza"
                    list="suggerimenti-provenienza" pattern="[A-Za-z'\-\s]+,\s[A-Z]{2}"
                    title="Inserisci più informazioni che hai" placeholder="Comune, Codice Provincia (due lettere). Es: Livorno, LI">
                <datalist id="suggerimenti-provenienza">

                    <optgroup label="Provincia di Livorno">
                        <option value="Livorno, LI">Livorno, LI</option>
                        <option value="Cecina, LI">Cecina, LI</option>
                        <option value="Collesalvetti, LI">Collesalvetti, LI</option>
                        <option value="Rosignano Marittimo, LI">Rosignano Marittimo, LI</option>
                        <option value="Castagneto Carducci, LI">Castagneto Carducci, LI</option>
                        <option value="Campiglia Marittima, LI">Campiglia Marittima, LI</option>
                        <option value="San Vincenzo, LI">San Vincenzo, LI</option>
                        <option value="Piombino, LI">Piombino, LI</option>
                        <option value="Portoferraio, LI">Portoferraio, LI</option>
                        <option value="Bibbona, LI">Bibbona, LI</option>
                    </optgroup>

                    <optgroup label="Provincia di Pisa">
                        <option value="Pisa, PI">Pisa, PI</option>
                        <option value="Cascina, PI">Cascina, PI</option>
                        <option value="San Giuliano Terme, PI">San Giuliano Terme, PI</option>
                        <option value="Pontedera, PI">Pontedera, PI</option>
                        <option value="San Miniato, PI">San Miniato, PI</option>
                        <option value="Ponsacco, PI">Ponsacco, PI</option>
                    </optgroup>
                    
                    <optgroup label="Provincia di Lucca">
                        <option value="Lucca, LU">Lucca, LU</option>
                        <option value="Viareggio, LU">Viareggio, LU</option>
                        <option value="Capannori, LU">Capannori, LU</option>
                        <option value="Forte dei Marmi, LU">Forte dei Marmi, LU</option>
                        <option value="Camaiore, LU">Camaiore, LU</option>
                        <option value="Pietrasanta, LU">Pietrasanta, LU</option>
                        <option value="Massarosa, LU">Massarosa, LU</option>
                        <option value="Altopascio, LU">Altopascio, LU</option>
                    </optgroup>

                    <optgroup label="Provincia di Massa-Carrara">
                        <option value="Massa, MS">Massa, MS</option>
                        <option value="Carrara, MS">Carrara, MS</option>
                        <option value="Aulla, MS">Aulla, MS</option>
                        <option value="Montignoso, MS">Montignoso, MS</option>
                        <option value="Fivizzano, MS">Fivizzano, MS</option>
                        <option value="Pontremoli, MS">Pontremoli, MS</option>
                    </optgroup>

                    <optgroup label="Altre province toscane">
                        <option value="Firenze, FI">Firenze, FI</option>
                        <option value="Arezzo, AR">Arezzo, AR</option>
                        <option value="Siena, SI">Siena, SI</option>
                        <option value="Prato, PO">Prato, PO</option>
                        <option value="Pistoia, PT">Pistoia, PT</option>
                        <option value="Grosseto, GR">Grosseto, GR</option>
                    </optgroup>
                    
                    <optgroup label="Capoluoghi Liguria">
                        <option value="Genova, GE">Genova, GE</option>
                        <option value="La Spezia, SP">La Spezia, SP</option>
                        <option value="Savona, SV">Savona, SV</option>
                        <option value="Imperia, IM">Imperia, IM</option>
                    </optgroup>

                    <optgroup label="Altre città popolose">
                        <option value="Roma, RM">Roma, RM</option>
                        <option value="Milano, MI">Milano, MI</option>
                        <option value="Napoli, NA">Napoli, NA</option>
                        <option value="Torino, TO">Torino, TO</option>
                        <option value="Palermo, PA">Palermo, PA</option>
                        <option value="Bologna, BO">Bologna, BO</option>
                        <option value="Bari, BA">Bari, BA</option>
                        <option value="Catania, CT">Catania, CT</option>
                        <option value="Verona, VR">Verona, VR</option>
                        <option value="Venezia, VE">Venezia, VE</option>
                        <option value="Messina, ME">Messina, ME</option>
                        <option value="Padova, PD">Padova, PD</option>
                        <option value="Trieste, TS">Trieste, TS</option>
                        <option value="Parma, PR">Parma, PR</option>
                        <option value="Brescia, BS">Brescia, BS</option>
                    </optgroup>
                </datalist>

                <label for="telefono">Telefono</label>
                <input type="tel" name="tel" id="telefono" value="<?= isset($anagrafica->telefono) ? htmlspecialchars($anagrafica->telefono) : "" ?>"
                    title="Numero di telefono"
                    placeholder="3141592653">

                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($anagrafica->email) ?>"
                    title="Email qui"
                    placeholder="esempio@mail.com"
                    required>

                <label for="cf" title="Codice fiscale">Codice Fiscale</label>
                <input type="text" name="cf" id="cf" required value="<?= htmlspecialchars($anagrafica->cf) ?>" 
                    pattern="[a-zA-Z]{6}[0-9]{2}[a-zA-Z][0-9]{2}[a-zA-Z][0-9]{3}[a-zA-Z]"
                    title="Codice fiscale corretto"
                    placeholder="aaaaaa11a11a111a">

                <?php include __DIR__ . "/form_documento.php"; ?>
            </div>
                <?php if ($is_extern) { ?>
                    <input name="extern" type="hidden" value="Esterno">
                    <p class="text" style="text-align:justify;">
                        Premendo "conferma" autorizzi l'Associazione Amichiamoci A.S.D. (Diocesi di Livorno)
                        a memorizzare i tuoi dati. Tali dati sono tenuti solo a scopo assicurativo.<br>
                        Autorizzi inoltre l'associazione a mostrare il tuo nome e cognome nelle varie classifiche 
                        (parziali e finali) della manifestazione.<br>
                        I dati memorizzati saranno memorizzati anche al fine di non dover ricompilare questo form per partecipare ad edizioni future.<br>
                        I dati che stai immettendo possono essere rimossi in futuro scrivendo a 
                        <a href="mailto:<?= CONTACT_EMAIL ?>" class="link"><?= CONTACT_EMAIL ?></a>.
                    </p>
                <?php } ?>

            <p class="text" id="codice-fiscale-check-result" style="font-weight: bold"></p>
            <input class="button rounded" type="submit" id="submit-btn" name="anagrafica_submit" value="Conferma">
        </form>
    </div>
</div>
<script src="<?= ADMIN_URL ?>/assets/js/codice.fiscale.var.js" type="text/javascript" defer></script>
<script src="<?= ADMIN_URL ?>/assets/js/anagrafica.js" type="text/javascript" defer></script>