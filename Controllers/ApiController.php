<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Api\Call as ApiCall;
use Amichiamoci\Models\Api\Token as ApiToken;
use Amichiamoci\Models\Message;
use Amichiamoci\Models\Api\Traits\Anagrafica as AnagraficaTrait;
use Amichiamoci\Models\Api\Traits\Parrocchia as ParrocchiaTrait;
use Amichiamoci\Models\Api\Traits\Staff as StaffTrait;
use Amichiamoci\Models\Api\Traits\Squadra as SquadraTrait;
use Amichiamoci\Models\Api\Traits\Torneo as TorneoTrait;
use Amichiamoci\Models\Api\Traits\Partita as PartitaTrait;
use Amichiamoci\Utils\Security;
use ReflectionClass;

class ApiController extends Controller
{
    public function delete_token(?int $id = null): int
    {
        $this->RequireLogin(require_admin: true);
        if (empty($id))
        {
            return $this->admin();
        }

        if (ApiToken::Delete(connection: $this->DB, id: $id))
        {
            $this->Message(message: Message::Success(content: 'Token correttamente eliminato'));
        } else {
            $this->Message(message: Message::Error(content: 'Non è stato possibile disabilitare il token'));
        }

        return $this->admin();
    }
    public function admin(?string $token_name = null): int
    {
        $this->RequireLogin(require_admin: true);
        $generated_key = null;

        if (self::IsPost() && isset($token_name))
        {
            $new_token = ApiToken::New(connection: $this->DB, name: $token_name);
            if (isset($new_token))
            {
                $this->Message(
                    message: Message::Success(
                        content: "Token correttamente generato.")
                );
                $generated_key = $new_token->Key;
            } else {
                $this->Message(
                    message: Message::Error(content: "Impossibile generare il token!")
                );
            }
        }

        return $this->Render(
            view: 'Api/all',
            title: 'Applicazioni attive',
            data: [
                'tokens' => ApiToken::All(connection: $this->DB),
                'new_key' => $generated_key,
            ],
        );
    }

    public function index(?string $resource = null): int {
        if (!isset($resource))
        {
            return $this->Json(
                object: [
                    'Message' => 'Invalid resource (null)',
                    'Stack' => [
                        __METHOD__,
                    ],
                    'Line' => __LINE__, 
                    'File' => __FILE__,
                ],
                status_code: 400,
            );
        }

        $bearer = $this->get_bearer();
        if (!isset($bearer))
        {
            return $this->Json(
                object: [
                    'Message' => "Header 'App-Bearer' missing",
                    'Stack' => [
                        __METHOD__,
                    ],
                    'Line' => __LINE__,
                    'File' => __FILE__,
                ],
                status_code: 400,
            );
        }

        if (!ApiToken::Test(
            connection: $this->DB, 
            key: $bearer, 
            ip: Security::GetIpAddress(),
        )) {
            return $this->Json(
                object: [
                    'Message' => 'Invalid bearer token',
                    'Stack' => [
                        __METHOD__,
                    ],
                    'Line' => __LINE__,
                    'File' => __FILE__,
                ],
                status_code: 401,
            );
        }

        if (!array_key_exists(key: $resource, array: $this->avaible_methods))
        {
            return $this->Json(
                object: [
                    'Message' => "Action '$resource' not found",
                    'Stack' => [
                        __METHOD__,
                    ],
                    'Line' => __LINE__,
                    'File' => __FILE__,
                ],
                status_code: 404,
            );
        }

        // Get the parameters for the query
        $parameters = self::get_parameters();

        $dummy_controller = new ReflectionClass(objectOrClass: $this);
        $method = $dummy_controller->getMethod(name: $this->avaible_methods[$resource]);
        $method->setAccessible(accessible: true);

        try { 
            // $call_object = call_user_func_array(callback: [$dummy_controller, $f], args: $parameters);
            $call_object = $method->invokeArgs(object: $this, args: $parameters);
            $result = $call_object->Execute($this->DB);

            if (!isset($result))
            {
                return $this->Json(
                    object: [
                        'Message' => 'Could not obtain result from action',
                        'Stack' => [
                            __METHOD__,
                        ],
                        'Line' => __LINE__,
                        'File' => __FILE__,
                    ],
                    status_code: 500,
                );
            }
        } catch (\Throwable $ex) {
            return $this->Json(
                object: [
                    'Message' => $ex->getMessage(),
                    'Stack' => $ex->getTrace(),
                    'Line' => $ex->getLine(),
                    'File' => $ex->getFile(),
                ],
                status_code: 500,
            );
        }

        return $this->Json(object: $result);
    }

    private function get_bearer(): ?string
    {
        $headers = getallheaders();
        if (!array_key_exists(key: "App-Bearer", array: $headers))
        {
            return null;
        }
        return $headers["App-Bearer"];
    }
    private static function get_parameters(): array
    {
        $arr = [];
        foreach (getallheaders() as $key => $value)
        {
            if (!str_starts_with(haystack: $key, needle: 'Data-Param-'))
            {
                continue;
            }

            $base_name = substr(string: $key, offset: strlen(string: 'Data-Param-'));
            $arr[str_replace(search: '-', replace: '_', subject: $base_name)] = $value;
        }
        return $arr;
    }

    private array $avaible_methods = [
        'teams-members' => 'teams_members',
        'teams-info' => 'teams_info',

        'staff-list' => 'staff_list',
        'get-user-claims' => 'get_user_claims',

        'today-matches-of' => 'today_matches_of',
        'today-matches-sport' => 'today_matches_sport',
        'today-yesterday-matches' => 'today_and_yesterday_matchest',

        'tournament' => 'tournament',
        'tournament-matches' => 'tournament_matches',
        'tournament-leaderboard' => 'tournament_leaderboard',
        'tournament-sport' => 'tournament_sport',

        'new-match-result' => 'add_result',
        'delete-match-result' => 'delete_result',

        'church' => 'church',
        'churches' => 'churches',
        'leaderboard' => 'leaderboard',

        'document-types' => 'document_types',
        'managed-anagraphicals' => 'managed_anagraphicals',
        'subscribe' => 'subscribe',
        'anagraphical' => 'anagraphical',
    ];
    
    use AnagraficaTrait;

    use ParrocchiaTrait, StaffTrait;

    use SquadraTrait;

    use TorneoTrait, PartitaTrait;
}