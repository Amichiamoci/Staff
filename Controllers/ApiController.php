<?php

namespace Amichiamoci\Controllers;

use DomainException;
use InvalidArgumentException;
use Throwable;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\{Method, StatusCode};
use Richie314\SimpleMvc\Utils\Security;

use Amichiamoci\Api\{Iscrizioni, Login, Sport, System};
use Amichiamoci\Models\Api\Token as ApiToken;
use Amichiamoci\Models\Message;

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
            $token_name = trim($token_name);
            if ($token_name === '' || mb_strlen($token_name) > 256) {
                return $this->BadRequest('Inserisci un nome applicazione da 1 a 256 caratteri.');
            }

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
            title: 'Token Web App',
            data: [
                'tokens' => ApiToken::All(connection: $this->DB),
                'new_key' => $generated_key,
            ],
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function logins(int|string|null $page = null): StatusCode
    {
        try {
            $page = $page === null ? 1 : $this->checkId($page, 'page');
        } catch (InvalidArgumentException) {
            return $this->BadRequest();
        }

        header('Cache-Control: no-store');
        $pageSize = 100;
        $logins = Login::recent($this->DB, ($page - 1) * $pageSize, $pageSize + 1);
        $next = count($logins) > $pageSize ? $page + 1 : null;
        return $this->Render(
            view: 'Api/logins',
            title: 'Accessi Web App',
            data: ['logins' => array_slice($logins, 0, $pageSize), 'next' => $next, 'page' => $page],
        );
    }

    public function login(?string $email = null, ?string $ipAddress = null): StatusCode
    {
        return $this->respond(Method::Post, function (int $tokenId, string $clientIp) use ($email, $ipAddress): array {
            $email = $this->verifiedEmail($email);
            $ip = $ipAddress ?? $clientIp;
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                throw new InvalidArgumentException('ipAddress must be a valid IPv4 or IPv6 address.');
            }

            return ['isAdmin' => Login::record($this->DB, $email, $tokenId, $ip)];
        });
    }

    public function index(): StatusCode
    {
        return $this->respond(Method::Get, null);
    }

    public function event(): StatusCode
    {
        $system = new System($this->DB, $this->Files());
        return $this->respond(Method::Get, fn() => $system->details());
    }

    public function leaderboard(): StatusCode
    {
        $sport = new Sport($this->DB);
        return $this->respond(Method::Get, fn() => $sport->leaderboard());
    }

    public function tournaments(): StatusCode
    {
        $sport = new Sport($this->DB);
        return $this->respond(Method::Get, fn() => $sport->tournaments());
    }

    public function tournament_standings(int|string|null $tournament_id = null): StatusCode
    {
        $sport = new Sport($this->DB);
        return $this->respond(Method::Get, fn() => $sport->standings(
            $this->checkId($tournament_id, 'tournament_id'),
        ));
    }

    public function matches(int|string|null $tournament_id = null, int|string|null $team_id = null): StatusCode
    {
        $sport = new Sport($this->DB);
        return $this->respond(Method::Get, fn() => $sport->matches(
            $tournament_id === null ? null : $this->checkId($tournament_id, 'tournament_id'),
            $team_id === null ? null : $this->checkId($team_id, 'team_id'),
        ));
    }

    public function today_matches(int|string|null $parish_id = null): StatusCode
    {
        $sport = new Sport($this->DB);
        return $this->respond(Method::Get, fn() => $sport->todayMatches(
            $this->checkId($parish_id, 'parish_id'),
        ));
    }

    public function people(?string $email = null): StatusCode
    {
        return $this->withIscrizioni($email, fn(Iscrizioni $iscrizioni) => $iscrizioni->people());
    }

    public function save_person(
        ?string $email = null,
        int|string|null $id = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $birthDate = null,
        ?string $birthPlace = null,
        ?string $phone = null,
        ?string $taxCode = null,
        int|string|null $documentTypeId = null,
        ?string $documentExpiry = null,
        ?string $documentUri = null,
    ): StatusCode {
        return $this->withIscrizioni($email, fn(Iscrizioni $iscrizioni) => $iscrizioni->savePerson(
            firstName: $firstName ?? '',
            lastName: $lastName ?? '',
            birthDate: $birthDate ?? '',
            birthPlace: $birthPlace ?? '',
            taxCode: $taxCode ?? '',
            documentTypeId: $this->checkId($documentTypeId, 'documentTypeId'),
            documentExpiry: $documentExpiry ?? '',
            id: $id === null ? null : $this->checkId($id, 'id'),
            phone: $phone,
            documentUri: $documentUri,
        ));
    }

    public function subscriptions(?string $email = null): StatusCode
    {
        return $this->withIscrizioni($email, fn(Iscrizioni $iscrizioni) => $iscrizioni->subscriptions());
    }

    public function subscribe(
        ?string $email = null,
        int|string|null $personId = null,
        int|string|null $editionId = null,
        int|string|null $parishId = null,
        ?string $shirtSize = null,
        int|string|null $guardianId = null,
        ?string $certificateUri = null,
    ): StatusCode {
        return $this->withIscrizioni($email, fn(Iscrizioni $iscrizioni) => $iscrizioni->subscribe(
            personId: $this->checkId($personId, 'personId'),
            editionId: $this->checkId($editionId, 'editionId'),
            parishId: $this->checkId($parishId, 'parishId'),
            shirtSize: $shirtSize ?? '',
            guardianId: $guardianId === null ? null : $this->checkId($guardianId, 'guardianId'),
            certificateUri: $certificateUri,
        ));
    }

    public function my_teams(?string $email = null): StatusCode
    {
        $sport = new Sport($this->DB);
        return $this->respond(Method::Post, fn() => $sport->teams($this->verifiedEmail($email)));
    }

    private function withIscrizioni(?string $email, callable $action): StatusCode
    {
        $iscrizioni = new Iscrizioni($this->DB, $this->verifiedEmail($email));
        return $this->respond(Method::Post, fn() => $action($iscrizioni));
    }

    private function verifiedEmail(?string $value): string
    {
        $email = strtolower(trim($value ?? ''));
        if (strlen($email) > 64 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('email must be a valid email address of at most 64 bytes.');
        }
        return $email;
    }

    private function checkId(int|string|null $value, string $name): int
    {
        if (is_string($value) && ctype_digit($value)) {
            $value = (int)$value;
        }
        if (!is_int($value) || $value < 1 || $value > 2147483647) {
            throw new InvalidArgumentException("$name must be a positive integer ID.");
        }
        return $value;
    }

    private function respond(Method $method, ?callable $action): StatusCode
    {
        header('Cache-Control: no-store');

        try {
            $token = $_SERVER['HTTP_APP_BEARER'] ?? '';
            $clientIp = Security::GetIpAddress();
            $tokenId = ApiToken::Authenticate($this->DB, $token, $clientIp);
            if ($tokenId === null) {
                header('WWW-Authenticate: App-Bearer');
                return $this->Json(['error' => 'A valid App-Bearer token is required.'], StatusCode::NotAuthorized);
            }

            if ($action === null) {
                return $this->Json(['error' => 'Unknown API route.'], StatusCode::NotFound);
            }
            if ($this->RequestMethod !== $method) {
                return $this->Json(['error' => 'Unsupported method for this route.'], StatusCode::BadRequest);
            }

            return $this->Json($action($tokenId, $clientIp));
        } catch (InvalidArgumentException $error) {
            return $this->Json(['error' => $error->getMessage()], StatusCode::BadRequest);
        } catch (DomainException $error) {
            return $this->Json(['error' => $error->getMessage()], StatusCode::Forbidden);
        } catch (Throwable) {
            return $this->Json(['error' => 'The request could not be completed.'], StatusCode::ServerError);
        }
    }
}
