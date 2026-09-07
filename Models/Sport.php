<?php
namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Sport extends NomeIdSemplice
{
    public static function Table(): string { return "sport"; }

    public function getIcon(): string
    {
        $name = strtolower($this->Nome);
        
        if (str_contains($name, 'pallavolo')) 
            return '🏐';

        if (str_contains($name, 'calcio'))
            return '⚽';

        if (str_contains($name, 'basket'))
            return '🏀';
        
        return '';
    }
}
