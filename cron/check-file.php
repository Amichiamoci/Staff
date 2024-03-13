<?php

require_once dirname(__DIR__) . "/load/db_manager";

if (!$connection)
{
    echo "Non &egarve; possibile connettersi al DB!";
    die();
}
$curr_date = date("d-m-Y");
$do_op = true;

$myfile = fopen($file_name, "r");
if (!$myFile)
{
    $myfile = fopen($file_name, "w") or die("Unable to open file!");
    fwrite($myfile, $curr_date);
    fclose($myfile);
    $myfile = fopen($file_name, "r");
}
if ($myfile)
{
    if ($data = fread($myfile, 10))
    {
        $file_date = date('d-m-Y', strtotime($data));
        echo "Lettura precedente: $file_date<br>\n";
        echo "Data corrente: $curr_date<br>\n";
        $do_op = date_diff(date_create($curr_date), date_create($file_date))->days >= 1;
    }
    fclose($myfile);
}

if ($do_op)
{
    $myfile = fopen($file_name, "w") or die("Unable to open file!");
    fwrite($myfile, $curr_date);
    fclose($myfile);
    echo "File aggiornato<br>\n";
} else {
    echo "Mail gi&agrave; inviate oggi!<br>\n";
}