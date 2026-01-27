<?php

namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;
use Amichiamoci\Utils\File;

class Anagrafica extends AnagraficaBase
{
    public string $BirthDay = "";
    public string $From = "";
    public ?string $Phone = null;
    public ?string $Email = null;
    public string $FiscalCode = "";
    public string $Sex = "?";

    public TipoDocumento $DocumentType;
    public ?string $DocumentCode = null;
    public string $DocumentExpiration = "";
    public string $DocumentFileName = "";
    
    public static function CreateOrUpdate(
        \mysqli $connection, 
        string $nome, string $cognome, 
        string $compleanno, 
        string $provenienza, 
        ?string $tel, 
        ?string $email,
        string $cf, 
        int $doc_type, 
        ?string $doc_code, 
        string $doc_expires, 
        ?string $nome_file = null, 
        bool $abort_if_existing = false,
        ?bool &$already_existing = null,
    ) : int {
        if (!$connection)
            return 0;
        $result = $connection->execute_query(
            query: 'CALL `CreaAnagrafica`(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);',
            params: [
                $nome,
                $cognome,
                $compleanno,
                $provenienza,
                $tel,
                $email,
                $cf,
                $doc_type,
                $doc_code,
                $doc_expires,
                $nome_file ?? '',
                $abort_if_existing ? '1' : '0'
            ]);
        
        $id = 0;
        if ($result && $row = $result->fetch_assoc())
        {
            if (array_key_exists(key: 'id', array: $row))
            {
                $id = (int)$row['id'];
            }
            if (array_key_exists(key: 'is_existing', array: $row) && isset($already_existing))
            {
                $already_existing = (bool)$row['is_existing'];
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
        if (
            array_key_exists(key: 'data_nascita_italiana', array: $row) && 
            is_string(value: $row['data_nascita_italiana'])
        ) {
            $a->BirthDay = $row['data_nascita_italiana'];
        } else {
            $a->BirthDay = $row['data_nascita'];
        }
        $a->From = $row["luogo_nascita"];
        if (array_key_exists(key: 'sesso', array: $row) && is_string(value: $row['sesso']))
        {
            $a->Sex = $row['sesso'];
        }
        if (array_key_exists(key: 'email', array: $row) && is_string(value: $row['email']))
        {
            $a->Email = $row['email'];
        }
        if (array_key_exists(key: 'telefono', array: $row) && is_string(value: $row['telefono']))
        {
            $a->Phone = $row['telefono'];
        }

        if (array_key_exists(key: 'codice_documento', array: $row) && is_string(value: $row['codice_documento']))
        {
            $a->DocumentCode = $row["codice_documento"];
        }
        $a->DocumentType = new TipoDocumento(
            id: (int)$row["tipo_documento"],
            nome: array_key_exists(key: 'tipo_documento_nome', array: $row) ? $row['tipo_documento_nome'] : 'Documento' 
        );
        if (array_key_exists(key: 'scadenza', array: $row) && is_string(value: $row['scadenza']))
        {
            $a->DocumentExpiration = $row['scadenza'];
        }
        if (array_key_exists(key: 'documento', array: $row) && is_string(value: $row['documento']))
        {
            $a->DocumentFileName = $row['documento'];
        }
        return $a;
    }

    public function KeyWords(): string {
        $a = [
            $this->Nome,
            $this->Cognome,
            // $this->Nome . " " . $this->Cognome,
        ];
        if (isset($this->Email)) {
            $a[] = $this->Email;
        }
        if (isset($this->Phone)) {
            $a[] = $this->Phone;
        }
        return join(separator: ' ', array: $a);
    }

    public function AmericanBirthDay(): string {
        $date = \DateTime::createFromFormat(format: 'd/m/Y', datetime: $this->BirthDay);
        if (!$date) {
            return $this->BirthDay;
        }
        return $date->format(format: 'Y-m-d');
    }

    public static function GroupFrom(\mysqli $connection): array
    {
        if (!$connection) return [];
        $query = "SELECT * FROM `statistiche_nascita`";

        $result = $connection->query(query: $query);
        if (!$result) {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = [
                'where' => $row['luogo'],
                'count' => (int)$row['nati']
            ];
        }
        return $arr;
    }

    public static function UnreferencedDocuments(\mysqli $connection): array
    {
        if (!$connection) return [];

        $query = 'SELECT DISTINCT(a.`documento`) AS "doc" FROM `anagrafiche` a WHERE a.`documento` IS NOT NULL';
        $result = $connection->query(query: $query);
        if (!$result) {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            if (!File::Exists(db_path: $row['doc']))
                continue;
            $arr[] = $row['doc'];
        }
        $result->close();

        $existing_files = array_filter(
            array: File::ListDirectory(dir: SERVER_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'documenti'),
            callback: function (string $key, string|array $value): bool {
                return is_string(value: $value) && $key === $value;
        }, mode: ARRAY_FILTER_USE_BOTH);
        $existing_files = array_map(callback: function (string $key): string {
            return DIRECTORY_SEPARATOR . 'documenti' . DIRECTORY_SEPARATOR . $key;
        }, array: array_keys($existing_files));

        return array_values(array: array_diff($existing_files, $arr));
    }

    public static function DulicateEmails(\mysqli $connection): array
    {
        if (!$connection) return [];

        $query = "SELECT * FROM `email_duplicate`";
        $result = $connection->query(query: $query);
        if (!$result) {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $ids = explode(separator: ', ', string: $row['id_anagrafiche']);
            $names = explode(separator: ', ', string: $row['nomi_anagrafiche']);

            if (count(value: $ids) !== count(value: $names) || 
                (int)$row['totale'] !== count(value: $ids)
            ) {
                continue;
            }

            $anagrafiche = [];
            for ($i = 0; $i < count(value: $ids); $i++) {
                $anagrafiche[] = [
                    'id' => (int)$ids[$i],
                    'name' => $names[$i],
                ];
            }

            $arr[] = [
                'email' => $row['email'],
                'total' => (int)$row['totale'],
                'who' => $anagrafiche,
            ];
        }
        return $arr;
    }
}