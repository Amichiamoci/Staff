<?php
namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class TipoTorneo extends NomeIdSemplice
{
    public static function Table(): string { return "tipi_torneo"; }

    public static int $RoundRobin = 1;
    public static int $Elimination = 2;
}