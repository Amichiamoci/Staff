<?php
namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Sport extends NomeIdSemplice
{
    public static function Table(): string { return "sport"; }
}
