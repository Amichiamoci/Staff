<?php
namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Controllers\Controller as BaseController;
use Richie314\SimpleMvc\Http\Method;

use Amichiamoci\Models\Message;
use Amichiamoci\Models\MessageType;
use Amichiamoci\Models\Staff;
use Amichiamoci\Models\User;

class Controller
extends BaseController
{
    protected function Staff(): ?Staff
    {
        if (!array_key_exists(key: 'staff', array: $this->ViewBag))
            $this->ViewBag['staff'] = null;

        return $this->ViewBag['staff'];
    }
    protected function Alerts(): array
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
     * Returns the associated staff object. If none exists interrupts the flow and calls
     * NotAuthorized(). Returns null if there is no staff associated with the user but the user
     * is admin.
     * @return Staff|null
     */
    public static function RequireStaff(
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

        if ($controller->Staff() !== null)
        {
            if ($commissione !== null && 
                !$controller->Staff()->InCommissione(commissione: $commissione)
            ) {
                $controller->NotAuthorized(
                    message: "Accesso consentito solo agli staffisti della commissione $commissione o agli amministratori");
                exit;
            }

            return $controller->Staff();
        }

        if ($user->Admin)
            return null; // Admin property bypasses staff claim

        $controller->NotAuthorized(message: "Accesso consentito solo agli staffisti o agli amministratori");
        exit;
    }

    /**
     * Adds a message to display in the current flow.
     * Does not work with redirects
     * @param \Amichiamoci\Models\Message|string|null $message
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

    protected function IsPost(): bool
    {
        return $this->RequestMethod === Method::Post;
    }
}