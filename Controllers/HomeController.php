<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\User;
use Amichiamoci\Utils\Cookie;
use Amichiamoci\Utils\Security;
use Amichiamoci\Models\Parrocchia;


class HomeController extends Controller
{
    public function index(): int {
        $this->RequireLogin();
        return $this->Render(
            view: 'Home/index',
            title: 'Portale'
        );
    }

    public function login(
        ?string $username = null, 
        ?string $password = null
    ): int {
        if ($this->IsLoggedIn()) {
            return $this->Redirect(url: '/');
        }

        $message = '';

        if (
            self::IsPost() && 
            !empty($username) &&
            !empty($password)
        ) {
            // TODO: load body cf-turnstile-response
            $turnstile_response = '';

            

            if (User::Login(
                connection: $this->DB, 
                username: $username, 
                password: $password, 
                user_agent: $_SERVER['HTTP_USER_AGENT'], 
                user_ip: Security::GetIpAddress()
            )) {
                // Login successful
                $redirect_url = '/';
                if (Cookie::Exists(name: 'Redirect')) {
                    $redirect_url = Cookie::Get(name: 'Redirect');
                    Cookie::Delete(name: 'Redirect');
                    if (empty($redirect_url)) {
                        $redirect_url = '/';
                    }
                }
    
                return $this->Redirect(url: $redirect_url);
            } 
            
            // return $this->NotAuthorized();
            $message = 'Utente non trovato o credenziali non valide';
        }

        return $this->Render(
            view: 'Home/login',
            title: 'Accedi',
            data: [
                'message' => $message,
                'username' => $username,
                'password' => $password,
            ]
        );
    }
    
    public function church(?int $id = null): int {
        $this->RequireLogin();

        $church = Parrocchia::ById(connection: $this->DB, id: $id);
        if (!isset($church)) {
            return $this->NotFound();
        }
        
        return $this->Render(
            view: 'Home/Churh',
            title: 'Parrocchia ' . $church->Nome,
            data: [ 'church'=> $church ]
        );
    }
}