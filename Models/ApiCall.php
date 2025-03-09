<?php

namespace Amichiamoci\Models;

class ApiCall
{
    public string $Query;

    public $RowParser;

    public bool $IsProcedure;

    public function __construct(
        string $query,
        ?callable $row_parser = null,
        bool $is_procedure = false,
    ) {
        $this->Query = $query;
        $this->RowParser = isset($$row_parser) ? 
            $row_parser : 
            function (array $r): array { return $r; };
        $this->IsProcedure = $is_procedure;
    }

    public function Execute(\mysqli $connection): ?array
    {
        $response = $connection->query(query: $this->Query);
        if (!$response) {
            return null;
        }

        $result = ($response === true) ? [] : $response->fetch_all(mode: MYSQLI_ASSOC);
        if (!isset($result) || !$result)
        {
            $result = [];
        }

        if ($this->IsProcedure) {
            $connection->next_result();
        }
        
        return array_map(callback: $this->RowParser, array: $result);
    }
}