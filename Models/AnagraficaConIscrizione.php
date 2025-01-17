<?php

namespace Amichiamoci\Models;

class AnagraficaConIscrizione extends Anagrafica
{
    public Iscrizione $Iscrizione;

    public static function All(\mysqli $connection, ?callable $filter = null): array
    {
        if (!$connection) return [];
        $query = "CALL IscrizioniList(NULL, NULL)";
        
        $result = $connection->query(query: $query);
        if (!$result) {
            $connection->next_result();
            return [];
        }

        $arr = [];
        while($row = $result->fetch_assoc())
        {
            $arr[] = self::FromDbRow(row: $row);
        }
        if (!is_null(value: $filter))
            $arr = array_filter(array: $arr, callback: $filter);
        return $arr;
    }

    public static function FromChurchId(\mysqli $connection, int $church_id): array {
        if (!$connection) return [];
        $query = "CALL IscrizioniList(YEAR(CURRENT_DATE), $church_id";
        
        $result = $connection->query(query: $query);
        if (!$result) {
            $connection->next_result();
            return [];
        }

        $arr = [];
        while($row = $result->fetch_assoc())
        {
            $arr[] = self::FromDbRow(row: $row);
        }
        return $arr;
    }

    public static function FromYear(\mysqli $connection, int $year): array {
        if (!$connection) return [];
        $query = "CALL IscrizioniList($year, NULL)";
        
        $result = $connection->query(query: $query);
        if (!$result) {
            $connection->next_result();
            return [];
        }

        $arr = [];
        while($row = $result->fetch_assoc())
        {
            $arr[] = self::FromDbRow(row: $row);
        }
        return $arr;
    }

    protected static function FromDbRow(array $row): parent|self
    {
        $a = parent::FromDbRow(row: $row);
        if (!array_key_exists(key: 'id_iscrizione', array: $row) || empty($row['id_iscrizione'])) {
            return $a;
        }
        $ai = new self(
            id: $a->Id,
            nome: $a->Nome,
            cognome: $a->Cognome,
            eta: $a->Eta,
        );

        // Copy the parameters from the original object
        // https://stackoverflow.com/questions/2226103/how-to-cast-objects-in-php
        $sourceReflection = new \ReflectionObject(object: $a);
        $destinationReflection = new \ReflectionObject(object: $ai);
        $sourceProperties = $sourceReflection->getProperties();
        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(true);
            $name = $sourceProperty->getName();
            $value = $sourceProperty->getValue($a);
            if ($destinationReflection->hasProperty(name: $name)) {
                $propDest = $destinationReflection->getProperty(name: $name);
                $propDest->setAccessible(accessible: true);
                $propDest->setValue($ai, value: $value);
            } else {
                $ai->$name = $value;
            }
        }

        $ai->Iscrizione = new Iscrizione(
            id: $row["id_iscrizione"],
            nome: $row["nome"] . " " . $row["cognome"],
            parrocchia: $row["parrocchia"],
            id_parrocchia: $row["id_parrocchia"],
            anno_edizione: $row["anno"],
            taglia: $row["maglia"], 
            id_tutore: $row["id_tutore"],
            certificato: $row["certificato_medico"],
        );
        return $ai;
    }
}