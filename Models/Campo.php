<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Campo extends NomeIdSemplice
{
    public static function Table(): string { return "campi"; }
}