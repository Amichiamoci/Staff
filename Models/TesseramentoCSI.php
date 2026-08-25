<?php

namespace Amichiamoci\Models;

class TesseramentoCSI
{
    public string $Nome;
    public string $Cognome;
    public string $Sesso;
    public string $LuogoNascita;
    public string $DataNascita;
    public ?string $Telefono;
    public string $CodiceFiscale;
    public ?string $Email;

    public function __construct(
        string $nome, 
        string $cognome,
        string $sesso,
        string $luogo_nascita,
        string $data_nascita,
        ?string $telefono,
        string $codice_fiscale,
        ?string $email,
    ) {
        $this->Nome = $nome;
        $this->Cognome = $cognome;
        $this->Sesso = $sesso;
        $this->LuogoNascita = $luogo_nascita;
        $this->DataNascita = $data_nascita;
        $this->Telefono = $telefono;
        if (empty($this->Telefono))
            $this->Telefono = null;
        $this->CodiceFiscale = $codice_fiscale;
        $this->Email = $email;
        if (empty($this->Email))
            $this->Email = null;
    }

    public static function All(\mysqli $connection): array
    {
        if (!$connection) return [];
        $query = "SELECT * FROM `iscrizioni_per_csi`";

        $result = $connection->query(query: $query);
        if (!$result) return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                nome: $row['nome'],
                cognome: $row['cognome'],
                sesso: $row['sesso'],
                luogo_nascita: $row['luogo_nascita'],
                data_nascita: $row['data_nascita_americana'],
                telefono: $row['telefono'],
                codice_fiscale: $row['codice_fiscale'],
                email: $row['email']
            );
        }
        return $arr;
    }
}