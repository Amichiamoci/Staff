<?php 

$file_name = 'last-partite-oggi.txt';
require "./check-file.php";

function send_partite_oggi()
{
    global $connection;
    if (!$connection)
        return;
    $query = "SELECT * FROM chi_gioca_oggi WHERE email IS NOT NULL";

    $giocatori = array();
    $result = mysqli_query($connection, $query);

    if (!$result)
    {
        return;
    }

    while ($row = $result->fetch_assoc())
    {
        $email = $row["email"];
        $nome = htmlspecialchars($row["nome"]);
        $problema_certificato = isset($row["necessita_certificato"]) && $row["necessita_certificato"] == "1";

        $tornei = explode("|", $row["nomi_tornei_sport"]);

        $nomi_avversari = explode("|", $row["nomi_avversari"]);
        $orari_partite = explode("|", $row["orari_partite"]);

        $nomi_campi = explode("|", $row["nomi_campi"]);
        $indirizzi_campi = explode("|", $row["indirizzi_campi"]);
        $lat_campi = explode("|", $row["lat_campi"]);
        $lon_campi = explode("|", $row["lon_campi"]);

        $numero_partite_oggi = count($tornei);

        if (
            $numero_partite_oggi !== count($nomi_avversari) ||
            $numero_partite_oggi !== count($orari_partite) ||

            $numero_partite_oggi !== count($nomi_campi) ||
            $numero_partite_oggi !== count($indirizzi_campi) ||
            $numero_partite_oggi !== count($lat_campi) ||
            $numero_partite_oggi !== count($lon_campi) ||
            $numero_partite_oggi === 0)
        {
            //Dati non allineati
            continue;
        }

        $testo = "<html>\r\n";
        $testo .= "<head>\r\n";

        $testo .= "<style type=\"text/css\">\r\n";
        
        $testo .= "    div.partita {\r\n";
        $testo .= "        max-width: 100%; width: fit-content; height: auto; padding: 1em; border: 1px solid; border-radius: 1em;\r\n";
        $testo .= "    }\r\n";

        $testo .= "    div.partita ul {\r\n";
        $testo .= "        margin-left: 0;\r\n";
        $testo .= "    }\r\n";

        $testo .= "    div.partita li {\r\n";
        $testo .= "        padding-inline: .8em;\r\n";
        $testo .= "    }\r\n";

        $testo .= "    div.partita a {\r\n";
        $testo .= "        color: inherit; text-decoration: none;\r\n";
        $testo .= "    }\r\n";

        $testo .= "    div.partita a:hover {\r\n";
        $testo .= "        text-decoration: underline;\r\n";
        $testo .= "    }\r\n";

        $testo .= "</style>\r\n";

        $testo .= "</head>\r\n";
        $testo .= "<body>\r\n";

        $testo .= "<h2>Ciao $nome</h2>\r\n";
        $testo .= "<p class=\"text\">\r\n";
        $testo .= "    Ti scriviamo per ricordarti ";
        if ($numero_partite_oggi === 1)
        {
            $testo .= "della partita che dovrai disputare oggi.<br />\r\n";
            $oggetto = "Partita di Amichiamoci oggi";
        } else {
            $testo .= "delle $numero_partite_oggi partite che dovrai disputare oggi.<br />\r\n";
            $oggetto = "Partite di Amichiamoci oggi";
        }
        $testo .= "</p>\r\n";

        for ($i = 0; $i < $numero_partite_oggi; $i++)
        {
            $torneo = htmlspecialchars($tornei[$i]);
            $avversari = htmlspecialchars($nomi_avversari[$i]);
            $orario = htmlspecialchars($orari_partite[$i]);
            $nome_campo = htmlspecialchars($nomi_campi[$i]);
            $lat = $lat_campi[$i];
            $lon = $lon_campi[$i];
            $indirizzo = $indirizzi_campi[$i];

            $testo .= "<div class=\"partita\">\r\n";
            $testo .= "    <ul>\r\n";
            $testo .= "        <li>TORNEO: <em>$torneo</em></li>\r\n";
            $testo .= "        <li>Avversari: <em>$avversari</em></li>\r\n";
            if ($orario !== "?")
            {
                $testo .= "        <li>Ora: <time>$orario</time></li>\r\n";
            }
            if ($nome_campo !== "?")
            {
                $testo .= "        <li>Luogo: ";
                if ($lat !== "?" && $lon !== "?")
                {
                    //Possiamo fare il link con schema geo:
                    $testo .= Link::Geo($lat, $lon, $nome_campo);
                } else {
                    $testo .= "<span>$nome_campo</span>";
                }
                $testo .= "</li>\r\n";
                if ($indirizzo !== "?")
                {
                    $link = Link::Address2Maps($indirizzo);
                    $testo .= "        <li>Indirizzo: $link</li>\r\n";
                }
            }
            $testo .= "    </ul>\r\n";
            $testo .= "</div>\r\n";
        }
        
        if ($problema_certificato)
        {
            $testo .= "<p class=\"text\">\r\n";
            $testo .= "    Sembra che tu non abbia ancora consegnato il certificato medico sportivo.<br />";
            $testo .= "    <strong>NON si pu&ograve; scendere in campo senza!</strong><br />";
            $testo .= "    Invialo <strong>tempestivamente</strong> al tuo referente parrocchiale.";
            $testo .= "</p>\r\n";

            $testo .= "<hr />\r\n";
        }

        $testo .= "<p class=\"text\">\r\n";
        $testo .= "    Ti preghiamo di non rispondere a questa email.<br />";
        $testo .= "    <strong>In bocca al lupo!</strong>";
        $testo .= "</p>\r\n";

        $testo .= "</body>\r\n";
        $testo .= "</html>\r\n";

        $giocatori[] = array($email, $oggetto, $testo);
    }
    $result->close();

    $email_inviate = 0;

    for ($i = 0; $i < count($giocatori); $i++)
    {
        $email = $giocatori[$i][0];
        $oggetto = $giocatori[$i][1];
        $testo = $giocatori[$i][2];
        if (Email::Send($email, $oggetto, $testo, $connection))
        {
            $email_inviate++;
        }
    }
    $count_giocatori = count($giocatori);
    echo "Oggi giocano $count_giocatori persone. <br>\n";
    echo "Inviate $email_inviate/$count_giocatori con successo.<br>\n";
}
if (isset($do_op) && $do_op)
{
    send_partite_oggi();
}