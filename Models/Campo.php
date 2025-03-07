<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Campo extends NomeIdSemplice
{
    public static function Table(): string { return "campi"; }

    public ?float $Latitudine = null;
    public ?float $Longitudine = null;
    public ?string $Indirizzo = null;

    public function __construct(
        int|string $id, 
        string $nome, 
        string|float|null $latitudine = null,
        string|float|null $longitudine = null,
        string|null $indirizzo = null,
    ) {
        parent::__construct(id: $id, nome: $nome);
        if (isset($latitudine))
        {
            $this->Latitudine = (float)$latitudine;
        }
        if (isset($longitudine))
        {
            $this->Longitudine = (float)$longitudine;
        }
        $this->Indirizzo = $indirizzo;
    }
}