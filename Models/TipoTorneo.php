<?php
namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class TipoTorneo extends NomeIdSemplice
{
    public static function Table(): string { return "tipi_torneo"; }
}