<?php
setlocale(LC_TIME, 'ita', 'it_IT.utf8');
define("DATABASE_ADDRESS", "DB_ADDR");
define("DATABASE_USER", "DB_USER");
define("DATABASE_CREDENTIALS", "DB_CREDENTIALS");
define("DATABASE_NAME", "DB_CONTEXT");

if (
    !isset($_SERVER[constant("DATABASE_ADDRESS")]) ||
    !isset($_SERVER[constant("DATABASE_USER")]) ||
    !isset($_SERVER[constant("DATABASE_CREDENTIALS")]) ||
    !isset($_SERVER[constant("DATABASE_NAME")]))
{
    die("Impossibile caricare impostazioni per la connessione al database!");
}

$connection = mysqli_connect(
    $_SERVER[constant("DATABASE_ADDRESS")], 
    $_SERVER[constant("DATABASE_USER")], 
    $_SERVER[constant("DATABASE_CREDENTIALS")],
    $_SERVER[constant("DATABASE_NAME")]);

if (!$connection)
{
    die("Connessione al server fallita: " . mysqli_connect_error());
}
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$DOMAIN = "https://" . $_SERVER['HTTP_HOST'];