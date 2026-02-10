<?php

namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\Method;
use Richie314\SimpleMvc\Utils\Cookie;

use Amichiamoci\Models\Anagrafica;
use Amichiamoci\Models\AnagraficaConIscrizione;
use Amichiamoci\Models\Cron;
use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\User;
use Amichiamoci\Models\Parrocchia;
use Amichiamoci\Models\Staff;
use Amichiamoci\Utils\File;
use Amichiamoci\Utils\Security;

class HomeController
extends Controller
{
    public function web_manifest(): StatusCode
    {
        return $this->Json(object: [
            'name' => SITE_NAME,
            'short_name' => SITE_NAME,
            'start_url' => File::getInstallationPath() . '/',
            'display' => 'standalone',
            'background_color' => '#fff',
            'description' => 'Portale staff',
            'icons' => [
                [
                    'src' => File::getInstallationPath() . '/images/icon.png',
                    'type' => 'image/png',
                    'sizes' => '256x256',
                ]
            ]
        ]);
    }

    #[RequireLogin]
    public function index(): StatusCode
    {
        return $this->Render(
            view: 'Home/index',
            title: 'Portale',
            data: [
                'churches' => Parrocchia::All(connection: $this->DB),
                'editions' => Edizione::All(connection: $this->DB),
                'compleanni' => Anagrafica::NomiCompleannati(connection: $this->DB),
            ]
        );
    }

    #[RequireLogin]
    public function stats(): StatusCode
    {
        return $this->Json(
            object: Anagrafica::GroupFrom(connection: $this->DB)
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function duplicate_emails(): StatusCode
    {
        return $this->Json(
            object: Anagrafica::DulicateEmails(connection: $this->DB)
        );
    }

    #[RequireLogin]
    public function no_email(): StatusCode
    {
        return $this->Json(
            object: Anagrafica::WithoutEmail(connection: $this->DB)
        );
    }

    #[RequireLogin]
    public function church_stats(?int $year = null): StatusCode
    {
        if (empty($year))
            $year = (int)date(format: 'Y');

        return $this->Json(
            object: AnagraficaConIscrizione::GroupByChurch(
                connection: $this->DB, 
                year: $year
            ),
        );
    }

    #[RequireLogin]
    public function t_shirts(int $church, ?int $year = null): StatusCode
    {
        if (empty($year))
            $year = (int)date(format: "Y");

        return $this->Json(
            object: Staff::MaglieDellaParrocchia(
                connection: $this->DB, 
                parrocchia: $church, 
                anno: $year,
            )
        );
    }

    #[RequireLogin]
    public function ages(?int $edition = null): StatusCode
    {
        if (empty($edition))
        {
            $edition = Edizione::Current(connection: $this->DB);
            if (isset($edition))
            {
                $edition = $edition->Id;
                return $this->NotFound();
            }
        }
        if (empty($edition))
            return $this->NotFound();

        $ages = Edizione::EtaPartecipanti(connection: $this->DB, id: $edition);
        return $this->Json(object: $ages);
    }

    #[RequireLogin(requireAdmin: true)]
    public function cron(): StatusCode
    {
        $crons = Cron::fetchAllFromDb(connection: $this->DB);
        return $this->Json(object: $crons);
    }

    public function login(
        ?string $username = null, 
        #[\SensitiveParameter] ?string $password = null,
        ?string $g_recaptcha_response = null,
    ): StatusCode
    {
        if ($this->User !== null)
            return $this->Redirect(url: File::getInstallationPath() . '/');

        $message = '';

        if (
            $this->RequestMethod == Method::Post && 
            !empty($username) &&
            !empty($password)
        ) {
            // Check captcha first (if enabled)
            if (!empty(RECAPTCHA_PUBLIC_KEY))
            {
                $message = Security::Recaptcha3Validation(
                    g_recaptcha_response: $g_recaptcha_response);       
            }

            if (empty($message))
            {
                if (User::Login(
                    connection: $this->DB, 
                    username: $username, 
                    password: $password, 
                    user_agent: $_SERVER['HTTP_USER_AGENT'], 
                    user_ip: Security::GetIpAddress()
                )) {
                    // Login successful
                    $redirect_url = File::getInstallationPath() . '/';
                    if (Cookie::Exists(name: 'Redirect'))
                    {
                        $redirect_url = Cookie::Get(name: 'Redirect');
                        Cookie::Delete(name: 'Redirect');
                        if (empty($redirect_url)) {
                            $redirect_url = File::getInstallationPath() . '/';
                        }
                    }
        
                    return $this->Redirect(url: $redirect_url);
                } 
                
                // return $this->NotAuthorized();
                $message = 'Utente non trovato o credenziali non valide';
            }
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
    
    #[RequireLogin]
    public function church(?int $id = null, ?int $year = null): StatusCode
    {
        if (empty($id))
            return $this->BadRequest();

        if (!isset($year))
            $year = (int)date(format: 'Y');

        $church = Parrocchia::ById(connection: $this->DB, id: $id);
        if ($church === null)
            return $this->NotFound();

        $staffs = Staff::FromParrocchia(connection: $this->DB, id: $id, anno: $year);
        //$iscritti = Iscrizione::FromParrocchia($this->DB, id: $id);
        
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