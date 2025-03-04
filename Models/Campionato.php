<?php
namespace Amichiamoci\Models;

class Campionato
{
    public int $Sport;
    public int $Edizione;

    public function __construct(int|Sport $sport, int|Edizione $edizione)
    {
        if ($sport instanceof Sport)
        {
            $this->Sport = $sport->Id;
        } else {
            $this->Sport = $sport;
        }

        if ($edizione instanceof Edizione)
        {
            $this->Edizione = $edizione->Id;
        } else {
            $this->Edizione = $edizione;
        }
    }
}