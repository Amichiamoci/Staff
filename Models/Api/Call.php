<?php

namespace Amichiamoci\Models\Api;

class Call
{
    public string $Query;

    public $RowParser;

    public bool $IsProcedure;

    public function __construct(
        string $query,
        ?callable $row_parser = null,
        bool $is_procedure = false,
        private array $parameters = [],
    ) {
        $this->Query = $query;
        $this->RowParser = isset($row_parser) ? 
            $row_parser : 
            function (array $r): array { return $r; };
        $this->IsProcedure = $is_procedure;
    }

    public function Execute(\mysqli $connection): ?array
    {
        $response = $connection->execute_query($this->Query, $this->parameters);
        if (!$response) {
            return null;
        }

        $result = ($response === true) ? [] : $response->fetch_all(mode: MYSQLI_ASSOC);
        if ($response instanceof \mysqli_result) {
            $response->close();
        }

        if ($this->IsProcedure) {
            $connection->next_result();
        }
        
        return array_map(callback: $this->RowParser, array: $result);
    }
}