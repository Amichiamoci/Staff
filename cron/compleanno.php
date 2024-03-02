<?php 

$file_name = 'last-compleanni-oggi.txt';
require "./check-file.php";
function send_compleanni()
{
    global $connection;
    if (!$connection)
        return;
    $query = "SELECT nome, email FROM compleanni_oggi WHERE email IS NOT NULL";

    $result = mysqli_query($connection, $query);
    $compleanni_oggi = array();
    if ($result) {
        while ($row = $result->fetch_assoc())
        {
            $compleanni_oggi[] = array(
                'nome' => $row["nome"],
                'email' => $row["email"]
            );
        }
        $result->close();
    }
    
    $email_inviate = 0;
    
    foreach ($compleanni_oggi as $persona)
    {
        $nome = acc($persona['nome']);
        $email = $persona['email'];
        $mail_text = join("\r\n", array(
            "<h3>Tanti auguri a te</h3>",
            "<h3>Tanti auguri a te</h3>",
            "<h3>Tanti auguri a $nome</h3>",
            "<h3>Tanti auguri a te!</h3>",
            "<p>Ciao $nome, lo staff di <a href=\"https://www.amichiamoci.it\">amichiamoci</a> ti augura un buon compleanno, passa questo giorno al meglio.</p>",
            "<br />",
            "<p><small>Ti preghiamo di non rispondere a questa email</small></p>"));
        $subject = "Buon compleanno";
        if (send_email($email, $subject, $mail_text, $connection))
        {
            $email_inviate++;
        }
    }
    $count_compleanni_oggi = count($compleanni_oggi);
    echo "Oggi compiono gli anni $count_compleanni_oggi persone. <br>\n";
    echo "Inviate $email_inviate/$count_compleanni_oggi con successo.<br>\n";
}
if (isset($do_op) && $do_op)
{
    send_compleanni();
}