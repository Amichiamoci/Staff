<?php

namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\NomeIdSemplice;

class Commissione extends NomeIdSemplice
{
    public static function Table(): string { return "commissioni"; }
};