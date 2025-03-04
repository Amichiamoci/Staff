<?php

namespace Amichiamoci\Controllers;

class ApiController extends Controller
{
    private function handle_query(
        string $query, 
        callable $row_parser,
        bool $next_result = false
    ): int {
        $response = $this->DB->execute_query(query: $query);
        if (!$response) {
            return $this->InternalError();
        }

        $result = ($response === true) ? [] : $response->fetch_all(mode: MYSQLI_ASSOC);
        if (!isset($result) || !$result)
        {
            $result = [];
        }

        if ($next_result)
            $this->DB->next_result();

        return $this->Json(
            object: array_map(callback: $row_parser, array: $result)
        );
    }
    public function get(?string $resource = null): int {
        if (empty($resource)) {
            return $this->BadRequest();
        }

        // TODO: check bearer token

        if (!array_key_exists(key: $resource, array: $this->avaible_methods)) {
            return $this->NotFound();
        }

        // Get the parameters for the query
        $f = $this->avaible_methods[$resource];
        $params = $this->$f();

        return $this->handle_query(
            query: $params['query'],
            row_parser: $params['row_parser'],
            next_result: array_key_exists(key: 'next_result', array: $params) ? 
                $params['next_result'] : false,
        );
    }
    private array $avaible_methods = [
        'teams-members' => 'teams_members',
        'teams-info' => 'teams_info',

    ];
    private function teams_members(): array {
        return [
            'query' => 'SELECT * FROM `distinte`',
            'row_parser' => function (array $row): array {
                $semi_parsed =  [
                    'Id' => (int)$row['id'],
                    'TeamId' => (int)$row['squadra_id'],
                    'SubscriptionId' => (int)$row['iscrizione'],
                    'FullName' => $row['chi'],
                    'Sex' => is_string($row['sesso']) ? $row['sesso'] : '?',
                    'Problems' => array()
                ];
    
                // Other problems
                if (is_string($row['tutore_problem']))
                {
                    $semi_parsed['Problems'][] = 'Tutore ' . $row['tutore_problem'];
                }
                if (is_string($row['certificato_problem']))
                {
                    $semi_parsed['Problems'][] = $row['certificato_problem'];
                }
    
                // Document related problems
                if (is_string($row['doc_problem']))
                {
                    $semi_parsed['Problems'][] = $row['doc_problem'];
                }
                //if (is_string($row['doc_code_problem']))
                //{
                //    $semi_parsed['Problems'][] = $row['doc_code_problem'];
                //}
                //if (is_string($row['scadenza_problem']))
                //{
                //    $semi_parsed['Problems'][] = $row['scadenza_problem'];
                //}
                return $semi_parsed;
            },
        ];
    }

    private function teams_info(): array {
        return [
            'query' => 'SELECT * FROM `squadre_attuali`',
            'row_parser' => function (array $row): array {
                return [
                    'Name' => $row['nome'],
                    'Id' => (int)$row['id'],
                    
                    'Church' => $row['parrocchia'],
                    'ChurchId' => (int)$row['id_parrocchia'],
    
                    'Sport' => $row['sport'],
                    'SportId' => (int)$row['id_sport'],
    
                    'MemberCount' => (int)$row['membri']
                ];
            },
        ];
    }
}