<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Anagrafica;
use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\Iscrizione;
use Amichiamoci\Models\User;
use Amichiamoci\Utils\Cookie;
use Amichiamoci\Utils\Security;
use Amichiamoci\Models\Parrocchia;
use Amichiamoci\Models\Staff;

class HomeController extends Controller
{
    public function web_manifest(): int {
        return $this->Json(object: [
            'name' => SITE_NAME,
            'short_name' => SITE_NAME,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#fff',
            'description' => 'Portale staff',
            'icons' => [
                [
                    'src' => '/Public/images/icon.png',
                    'type' => 'image/png',
                    'sizes' => '256x256'

                ]
            ]
        ]);
    }

    public function index(): int {
        $this->RequireLogin();
        return $this->Render(
            view: 'Home/index',
            title: 'Portale',
            data: [
                'churches' => Parrocchia::All(connection: $this->DB),
                'editions' => Edizione::All(connection: $this->DB),
            ]
        );
    }

    public function stats(): int {
        $this->RequireLogin();
        return $this->Json(
            object: Anagrafica::GroupFrom(connection: $this->DB)
        );
    }

    public function t_shirts(int $church, ?int $year = null): int {
        $this->RequireLogin();
        if (!isset($year)) {
            $year = (int)date(format: "Y");
        }
        return $this->Json(
            object: Staff::MaglieDellaParrocchia(connection: $this->DB, parrocchia: $church, anno: $year)
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
        if (!isset($id)) {
            return $this->BadRequest();
        }

        $church = Parrocchia::ById(connection: $this->DB, id: $id);
        if (!isset($church)) {
            return $this->NotFound();
        }

        $staffs = Staff::FromParrocchia(connection: $this->DB, id: $id);
        // $iscritti = Iscrizione::FromParrocchia($this->DB, id: $id);
        
        return $this->Render(
            view: 'Home/church',
            title: 'Parrocchia ' . $church->Nome,
            data: [ 
                'church' => $church,
                'staffs' => $staffs,
            ]
        );
    }
}