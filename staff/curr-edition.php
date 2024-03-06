<?php 
$edizione = Edizione::Current($connection);
include dirname(__DIR__) . "/parts/edition.php";