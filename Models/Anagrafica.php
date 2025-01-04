<?php

namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;

class Anagrafica extends AnagraficaBase
{
    public string $compleanno = "";
    public string $proveninenza = "";
    public ?string $telefono;
    public ?string $email;
    public string $cf = "";
    public int $doc_type = 1;
    public string $doc_code = "";
    public string $doc_expires = "";
    public string $nome_file = "";
    
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
        $result = $connection->query($query);
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
    
    public static function FromCF(\mysqli $connection, string $cf) : ?self
    {
        if (!$connection || strlen(string: trim(string: $cf)) === 0)
            return null;

        $cf = $connection->real_escape_string($cf);
        $query = "SELECT * FROM `anagrafiche_espanse` WHERE LOWER(`cf`) = LOWER('$cf')";
        $result = $connection->query($query);
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

    private static function FromDbRow(array $row): self
    {
        $a = new self(
            id: $row["id"], 
            nome: $row["nome"], 
            cognome: $row["cognome"], 
            eta: $row["eta"]
        );
        $a->cf = $row["cf"];
        $a->compleanno = $row["data_nascita_italiana"];
        $a->doc_code = $row["codice_documento"];
        $a->doc_type = (int)$row["tipo_documento"];
        $a->email = isset($row["email"]) ? $row["email"] : "";
        $a->doc_expires = isset($row["scadenza"]) ? $row["scadenza"] : "";
        $a->nome_file = isset($row["documento"]) ? $row["documento"] : "";
        $a->proveninenza = $row["luogo_nascita"];
        $a->telefono = isset($row["telefono"]) ? $row["telefono"] : "";
        return $a;
    }
}