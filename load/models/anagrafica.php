<?php
class TipoDocumento
{
    public int $id = 0;
    public string $label = "";
    public function __construct(
        string|int|null $id,
        string|null $label
    )
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($label) && is_string($label))
            $this->label = $label;
    }
    public static function GetAll(mysqli $connection) : array
    {
        if (!$connection) return array();
        $query = "SELECT id, label FROM tipi_documento";
        $result = $connection->query($query);
        if (!$result)
            return array();
        $tipi = array();
        while ($row = $result->fetch_assoc())
        {
            $doc = new TipoDocumento($row["id"], $row["label"]);
            $tipi[] = $doc;
        }
        return $tipi;
    }
}
class AnagraficaBase
{
    public int $id = 0;
    public string $nome = "";
    public string $cognome = "";
    public int $eta = 0;
    public function __construct(
        string|int|null $id,
        string|null $nome,
        string|null $cognome,
        string|int|null $eta
    )
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($nome) && is_string($nome))
            $this->nome = $nome;
        if (isset($cognome) && is_string($cognome))
            $this->cognome = $cognome;
        if (isset($eta))
            $this->eta = (int)$eta;
    }

    public static function GetAll(mysqli $connection, $filter = null) : array
    {
        if (!$connection)
            return array();
        $query = "SELECT id, nome, cognome, Eta(data_nascita) AS eta FROM anagrafiche ORDER BY cognome, nome ASC";
        $result = $connection->query($query);
        if (!$result)
            return array();
        $arr = array();
        while ($row = $result->fetch_assoc())
        {
            $anagrafica = new AnagraficaBase(
                $row["id"],
                $row["nome"],
                $row["cognome"],
                $row["eta"]
            );
            $arr[] = $anagrafica;
        }
        if (!is_null($filter))
            $arr = array_filter($arr, $filter);
        return $arr;
    }
    public static function NomeDaId(mysqli $connection, int $id) : string
    {
        if (!$connection)
            return "";
        $query = "CALL NomeDaAnagrafica($id)";
        $result = $connection->query($query);
        if (!$result)
        {
            $connection->next_result();
            return "";
            
        }
        if ($row = $result->fetch_assoc())
        {
            $result->close();
            $connection->next_result();
            return $row["nome_completo"];
        }
        $result->close();
        return "";
    }
}

class Anagrafica extends AnagraficaBase
{
    public string $compleanno;
    public string $provenineza;
    public string $telefono;
    public string $email;
    public string $cf;
    public int $doc_type;
    public string $doc_code;
    public string $doc_expires;
    public string $nome_file;
    
    public static function Create(
        mysqli $connection, 
        string $nome, string $cognome, 
        string $compleanno, 
        string $provenienza, 
        string $tel, 
        string $email,
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
}