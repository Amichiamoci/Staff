<?php

namespace Amichiamoci\Models;

class ProblemaIscrizione
{
    public int $Id;
    public int $Iscrizione;
    public string $Nome;
    public string $CodiceFiscale;
    public string $Sesso;


    public ?string $CodiceDocumento;
    public ?string $Documento;
    public ?string $ScadenzaDocumento;

    public ?string $Certificato;
    public ?string $Tutore;


    public ?string $Eta;
    public ?string $Taglia;


    public ?string $Email;
    public ?string $Telefono;

    public function __construct(
        string|int $id,
        string|int $iscrizione,
        string $chi,
        string $cf,
        string $sesso,

        ?string $codice_documento = null,
        ?string $documento = null,
        ?string $scadenza_documento = null,
        ?string $certificato = null,
        ?string $tutore = null,
        ?string $eta = null,
        ?string $taglia = null,
        ?string $email = null,
        ?string $telefono = null,
    ) {
        $this->Id = (int)$id;
        $this->Iscrizione = (int)$iscrizione;
        $this->Nome = $chi;
        $this->CodiceFiscale = $cf;
        $this->Sesso = $sesso;

        $this->CodiceDocumento = $codice_documento;
        $this->Documento = $documento;
        $this->ScadenzaDocumento = $scadenza_documento;
        $this->Certificato = $certificato;
        $this->Tutore = $tutore;
        $this->Eta = $eta;
        $this->Taglia = $taglia;
        $this->Email = $email;
        $this->Telefono = $telefono;
    }

    public static function Parrocchia(
        \mysqli $connection, 
        int $year, 
        int $parrocchia,
    ): array
    {
        if (!$connection)
        {
            return [];
        }
        $query = "CALL `ProblemiParrocchia`($parrocchia, $year)";
        $res = $connection->query(query: $query);
        if (!$res) {
            $connection->next_result();
            return [];
        }

        $arr = [];
        while ($row = $res->fetch_assoc())
        {
            $arr[] = new self(
                id: $row['id'],
                iscrizione: $row['iscrizione'],
                chi: $row['chi'],
                cf: $row['cf'],
                sesso: $row['sesso'],

                codice_documento: $row['doc_code'],
                documento: $row['doc'],
                scadenza_documento: $row['scadenza'],
                certificato: $row['certificato'],
                tutore: $row['tutore'],
                eta: $row['eta'],
                taglia: $row['maglia'],
                email: $row['email'],
                telefono: $row['telefono'],
            );
        }
        $connection->next_result();
        return $arr;
    }

    public function ProblemCount(): int
    {
        $filtered = array_filter(
            array: [
                $this->Documento,
                $this->ScadenzaDocumento,
                $this->Certificato,
                $this->Tutore,
                $this->Eta,
                $this->Email,
            ], 
            callback: function (?string $s): bool { return !empty($s); }
        );
        return count(value: $filtered);
    }
}