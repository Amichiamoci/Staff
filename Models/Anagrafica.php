<?php

namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;

class Anagrafica extends AnagraficaBase
{
    public string $BirthDay = "";
    public string $From = "";
    public ?string $Phone;
    public ?string $Email;
    public string $FiscalCode = "";
    public string $Sex = "?";

    public TipoDocumento $DocumentType;
    public string $DocumentCode = "";
    public string $DocumentExpiration = "";
    public string $DocumentFileName = "";
    
    public static function Create(
        \mysqli $connection, 
        string $nome, string $cognome, 
        string $compleanno, 
        string $provenienza, 
        ?string $tel, 
        ?string $email,
        string $cf, 
        int $doc_type, string $doc_code, 
        string $doc_expires, string $nome_file, 
        bool $is_extern = false
    ) : int {
        if (!$connection)
            return 0;
        $nome = $connection->real_escape_string($nome);
        $cognome = $connection->real_escape_string($cognome);
        $compleanno = $connection->real_escape_string($compleanno);
        $provenienza = $connection->real_escape_string($provenienza);
        $tel = empty($tel) ? "NULL" : "'" . $connection->real_escape_string($tel) . "'";
        $email = empty($email) ? "NULL" : "'" . $connection->real_escape_string($email) . "'";
        $cf = $connection->real_escape_string($cf);
        $doc_code = $connection->real_escape_string($doc_code);
        $doc_expires = $connection->real_escape_string($doc_expires);
        $nome_file = $connection->real_escape_string($nome_file);
        $extern = $is_extern ? "1" : "0";

        $query = "CALL CreaAnagrafica('$nome', '$cognome', '$compleanno', '$provenienza', $tel, $email, '$cf', $doc_type, '$doc_code', '$doc_expires', '$nome_file', $extern);";
        
        $result = $connection->query($query);
        $id = 0;
        if ($result && $row = $result->fetch_assoc())
        {
            if (isset($row["id"]))
            {
                $id = (int)$row["id"];
            }
            $result->close();
        }
        $connection->next_result();
        return $id;
    }

    public static function ById(\mysqli $connection, int $id) : ?self
    {
        if (!$connection || $id === 0)
            return null;
        $query = "SELECT * FROM `anagrafiche_espanse` WHERE `id` = $id";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }

        if ($row = $result->fetch_assoc())
        {
            return self::FromDbRow(row: $row);
        }
        return null;
    }

    public static function All(\mysqli $connection, ?callable $filter = null) : array
    {
        if (!$connection)
            return [];
        
        $result = $connection->query(
            query: "SELECT * FROM `anagrafiche_espanse` ORDER BY `cognome`, `nome`"
        );
        if (!$result)
        {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = self::FromDbRow(row: $row);
        }
        if (!is_null(value: $filter))
            $arr = array_filter(array: $arr, callback: $filter);
        return $arr;
    }
    
    public static function FromFiscalCode(\mysqli $connection, string $cf) : ?self
    {
        if (!$connection || strlen(string: trim(string: $cf)) === 0)
            return null;

        $cf = $connection->real_escape_string(string: $cf);
        $query = "SELECT * FROM `anagrafiche_espanse` WHERE LOWER(`cf`) = LOWER('$cf')";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return self::FromDbRow(row: $row);
        }
        return null;       
    }

    protected static function FromDbRow(array $row): self
    {
        $a = new self(
            id: $row["id"], 
            nome: $row["nome"], 
            cognome: $row["cognome"], 
            eta: $row["eta"]
        );
        $a->FiscalCode = $row["cf"];
        if (array_key_exists(key: 'data_nascita_italiana', array: $row) && is_string(value: $row['data_nascita_italiana'])) {
            $a->BirthDay = $row['data_nascita_italiana'];
        } else {
            $a->BirthDay = $row['data_nascita'];
        }
        $a->From = $row["luogo_nascita"];
        if (array_key_exists(key: 'sesso', array: $row) && is_string(value: $row['sesso'])) {
            $a->Sex = $row['sesso'];
        }
        if (array_key_exists(key: 'email', array: $row) && is_string(value: $row['email'])) {
            $a->Email = $row['email'];
        }
        if (array_key_exists(key: 'telefono', array: $row) && is_string(value: $row['telefono'])) {
            $a->Phone = $row['telefono'];
        }

        $a->DocumentCode = $row["codice_documento"];
        $a->DocumentType = new TipoDocumento(
            id: (int)$row["tipo_documento"],
            nome: array_key_exists(key: 'tipo_documento_nome', array: $row) ? $row['tipo_documento_nome'] : 'Documento' 
        );
        if (array_key_exists(key: 'scadenza', array: $row) && is_string(value: $row['scadenza'])) {
            $a->DocumentExpiration = $row['scadenza'];
        }
        if (array_key_exists(key: 'documento', array: $row) && is_string(value: $row['documento'])) {
            $a->DocumentFileName = $row['documento'];
        }
        return $a;
    }

    public function KeyWords(): string {
        $a = [
            $this->Nome,
            $this->Cognome,
            $this->Nome . " " . $this->Cognome,
        ];
        if (isset($this->Email)) {
            $a[] = $this->Email;
        }
        if (isset($this->Phone)) {
            $a[] = $this->Phone;
        }
        return join(separator: ' ', array: $a);
    }
}