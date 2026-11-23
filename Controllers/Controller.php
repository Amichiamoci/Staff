<?php
namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Controllers\Controller as BaseController;
use Richie314\SimpleMvc\Http\{Method, StatusCode};
use Richie314\SimpleVirtualFS\VirtualFilesystem;

use Amichiamoci\Utils\ErrorLog;
use Amichiamoci\Models\{
    Message,
    MessageType,
    Staff,
    User,
};

class Controller
extends BaseController
{
    protected function Files(): VirtualFilesystem
    {
        return $this->ViewBag['files'];
    }

    protected function Staff(): ?Staff
    {
        if (!array_key_exists(key: 'staff', array: $this->ViewBag))
            $this->ViewBag['staff'] = null;

        return $this->ViewBag['staff'];
    }
    protected function &Alerts(): array
    {
        if (!array_key_exists(key: 'alerts', array: $this->ViewBag))
            $this->ViewBag['alerts'] = [];

        return $this->ViewBag['alerts'];
    }

    protected function getUser(): ?User
    {
        return $this->User;
    }

    /**
     * Returns the associated staff object.
     * If no staff object exists, calls NotAuthorized() and exits for a non-administrator.
     * Returns null for an administrator without an associated staff object.
     * @return Staff|null
     */
    public static function _RequireStaff(
        self $controller, 
        bool $requireAdmin = false,
        string $loginPath = '/login',
        ?string $commissione = null,
    ): ?Staff {
        self::RequireLogin(
            controller: $controller, 
            requireAdmin: $requireAdmin,
            loginPath: $loginPath
        );
        $user = $controller->getUser();
        $staff = $controller->Staff();

        if ($staff !== null)
        {
            if ($commissione !== null && 
                !$staff->InCommissione(commissione: $commissione) &&
                !$user->Admin
            ) {
                $controller->NotAuthorized(
                    message: "Accesso consentito solo agli staffisti della commissione $commissione o agli amministratori");
                exit;
            }

            return $staff;
        }

        if ($user->Admin)
            return null; // Admin property bypasses staff claim entirely

        $controller->NotAuthorized(message: "Accesso consentito solo agli staffisti o agli amministratori");
        exit;
    }

    /**
     * Adds a message to display in the current flow.
     * Does not work with redirects.
     * @param Message|string|null $message
     * @return void
     */
    protected function Message(Message|string|null $message): void
    {
        if ($message === null)
            return;
        
        if (is_string(value: $message))
            $message = new Message(type: MessageType::Info, content: $message);
        
        $this->Alerts()[] = $message;
    }

    protected function InternalError(?\Throwable $ex = null, bool $render_error = true): StatusCode
    {
        ErrorLog::Report($ex);
        return parent::InternalError(render_error: $render_error);
    }

    protected function PostRequired(): never
    {
        header('Allow: POST');
        http_response_code(405);
        exit('Metodo non consentito.');
    }

    protected function AccountToken(): string
    {
        return $_SESSION['account_csrf_token'] ??= bin2hex(random_bytes(32));
    }

    protected function ValidAccountToken(?string $token): bool
    {
        return $token !== null && hash_equals($this->AccountToken(), $token);
    }

    protected function IsPost(): bool
    {
        return $this->RequestMethod === Method::Post;
    }
}