<?php

namespace Amichiamoci\Controllers;

use ReflectionClass;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\StatusCode;

use Amichiamoci\Models\Api\{Call as ApiCall, Token as ApiToken};
use Amichiamoci\Models\Message;
use Amichiamoci\Models\Api\Traits\{
    Anagrafica as AnagraficaTrait,
    Parrocchia as ParrocchiaTrait,
    Staff as StaffTrait,
    Squadra as SquadraTrait,
    Torneo as TorneoTrait,
    Partita as PartitaTrait,
};
use Amichiamoci\Utils\Security;

class ApiController
extends Controller
{
    #[RequireLogin(requireAdmin: true)]
    public function delete_token(?int $id = null): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

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

    #[RequireLogin(requireAdmin: true)]
    public function admin(?string $token_name = null): StatusCode
    {
        $generated_key = null;

        if ($this->IsPost() && isset($token_name))
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

    public function index(?string $resource = null): StatusCode
    {
        $bearer = $this->get_bearer();
        if (!isset($bearer))
        {
            return $this->Json(
                object: [
                    'Message' => "Header 'App-Bearer' missing",
                ],
                statusCode: StatusCode::BadRequest,
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
                ],
                statusCode: StatusCode::NotAuthorized,
            );
        }

        if (!isset($resource))
        {
            return $this->Json(
                object: [
                    'Message' => 'Invalid resource (null)',
                    'Stack' => [__METHOD__],
                    'Line' => __LINE__,
                    'File' => __FILE__,
                ],
                statusCode: StatusCode::BadRequest,
            );
        }

        if (!array_key_exists(key: $resource, array: $this->avaible_methods))
        {
            return $this->Json(
                object: [
                    'Message' => "Action '$resource' not found",
                    'Stack' => [__METHOD__],
                    'Line' => __LINE__,
                    'File' => __FILE__,
                ],
                statusCode: StatusCode::NotFound,
            );
        }

        try {
            $parameters = self::get_parameters();
            $controller = new ReflectionClass($this);
            $method = $controller->getMethod($this->avaible_methods[$resource]);
            $call_object = $method->invokeArgs(object: $this, args: $parameters);
            $result = $call_object->Execute($this->DB);

            if (!isset($result))
            {
                return $this->Json(
                    object: [
                        'Message' => 'Could not obtain result from action',
                        'Stack' => [__METHOD__],
                        'Line' => __LINE__,
                        'File' => __FILE__,
                    ],
                    statusCode: StatusCode::ServerError,
                );
            }
            return $this->Json(object: $result);
        } catch (\Throwable) {
            return $this->Json(
                object: [
                    'Message' => 'Could not execute action',
                    'Stack' => [__METHOD__],
                    'Line' => __LINE__,
                    'File' => __FILE__,
                ],
                statusCode: StatusCode::ServerError,
            );
        }
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
                continue;

            $base_name = substr(string: $key, offset: strlen(string: 'Data-Param-'));
            $arr[str_replace(search: '-', replace: '_', subject: $base_name)] = $value;
        }
        return $arr;
    }

    private array $avaible_methods = [
        'teams-info' => 'teams_info',

        'staff-list' => 'staff_list',
        'get-user-claims' => 'get_user_claims',
        'get-staff-email' => 'get_staff_email',

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
        'all-fields' => 'all_fields',

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