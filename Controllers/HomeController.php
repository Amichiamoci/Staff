<?php

namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Http\{StatusCode, Method};
use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Utils\Cookie;

use Amichiamoci\Models\{
    Anagrafica,
    AnagraficaConIscrizione,
    Cron,
    DashboardStatistics,
    Edizione,
    Settings,
    User,
    Parrocchia,
    Partita,
    Staff,
};
use Amichiamoci\Utils\Security;

class HomeController
extends Controller
{
    public function web_manifest(): StatusCode
    {
        return $this->Json(object: [
            'name' => 'Portale Staff ' . SITE_NAME,
            'short_name' => 'Portale Staff',
            'start_url' => $this->PathPrefix . '/',
            'display' => 'standalone',
            'background_color' => '#fff',
            'description' => 'Portale per staffisti di ' . SITE_NAME,
            'icons' => [
                [
                    'src' => $this->PathPrefix . '/images/icon.png',
                    'type' => 'image/png',
                    'sizes' => '256x256',
                ]
            ]
        ]);
    }

    #[RequireLogin]
    public function index(): StatusCode
    {
        $editions = Edizione::All($this->DB);
        $year = (int)date('Y');
        $currentEdition = array_find($editions, static fn(Edizione $edition): bool => $edition->Year === $year);

        return $this->Render(
            view: 'Home/index',
            title: 'Portale',
            data: [
                'churches' => Parrocchia::All(connection: $this->DB),
                'editions' => $editions,
                'current_edition' => $currentEdition,
                'show_double_subscriptions' => $this->getUser()->Admin && Settings::Read($this->DB)['subscriptions_enabled'],
                'live_matches' => Partita::Live($this->DB),
                'compleanni' => Anagrafica::NomiCompleannati(connection: $this->DB),
            ]
        );
    }

    #[RequireLogin]
    public function dashboard_chart(?string $chart = null, ?int $year = null): StatusCode
    {
        if ($chart === 'history') {
            return $this->Json(object: ['rows' => DashboardStatistics::Participation($this->DB, (int)date('Y'))]);
        }

        if ($year === null || !in_array($chart, ['readiness', 'shirts', 'tournaments'], true)) {
            return $this->BadRequest();
        }

        $admin = $this->getUser()->Admin;
        $parish = $admin ? null : $this->Staff()?->Parrocchia->Id;
        if ($chart === 'readiness' && !$admin && $parish === null) {
            return $this->NotAuthorized();
        }

        $edition = Edizione::FromYear($this->DB, $year);
        if ($edition === null) {
            return $this->NotFound();
        }

        $data = match ($chart) {
            'readiness' => DashboardStatistics::RegistrationReadiness($this->DB, $year, $parish),
            'shirts' => ['rows' => DashboardStatistics::ShirtDemand($this->DB, $edition->Id, $parish)],
            'tournaments' => ['groups' => DashboardStatistics::TournamentReadiness($this->DB, $edition->Id)],
        };

        return $this->Json(object: $data);
    }

    #[RequireLogin]
    public function statistics(): StatusCode
    {
        return $this->Render(
            view: 'Home/statistics',
            title: 'Statistiche',
            data: ['editions' => Edizione::All($this->DB)],
        );
    }

    #[RequireLogin]
    public function stats(): StatusCode
    {
        return $this->Json(
            object: Anagrafica::GroupFrom(connection: $this->DB)
        );
    }

    #[RequireLogin]
    public function email_stats(): StatusCode
    {
        return $this->Json(
            object: Anagrafica::EmailProviders(connection: $this->DB)
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
        return $this->Json(object: array_map(
            callback: function (Cron $cron): array {
                return [
                    'name' => $cron->Name,
                    'lastRun' => $cron->LastRun->format(format: 'Y-m-d H:i:s'),
                ];
            },
            array: $crons
        ));
    }
    
    #[RequireLogin(requireAdmin: true)]
    public function settings(
        ?string $csrf_token = null,
        ?string $subscriptions_enabled = null,
        ?string $teams_enabled = null,
        ?string $tournaments_enabled = null,
        ?string $api_enabled = null,
        ?string $email_tracking_enabled = null,
    ): StatusCode {
        if ($this->IsPost()) {
            if (!$this->ValidAccountToken($csrf_token)
                || !in_array($subscriptions_enabled, [null, '1'], true)
                || !in_array($teams_enabled, [null, '1'], true)
                || !in_array($tournaments_enabled, [null, '1'], true)
                || !in_array($api_enabled, [null, '1'], true)
                || !in_array($email_tracking_enabled, [null, '1'], true)
            ) {
                return $this->BadRequest();
            }

            Settings::Save(
                $this->DB,
                $subscriptions_enabled === '1',
                $teams_enabled === '1',
                $tournaments_enabled === '1',
                $api_enabled === '1',
                $email_tracking_enabled === '1',
            );

            return $this->RedirectWithStatus($this->PathPrefix . '/settings', StatusCode::SeeOther);
        }

        $phpInfo = '';
        if (function_exists('phpinfo')) {
            ob_start();
            phpinfo(INFO_GENERAL);
            $phpInfo = ob_get_clean();
        }

        return $this->Render(
            view: 'Home/settings',
            title: 'Configurazione generale',
            data: Settings::Read($this->DB) + [
                'php_info' => $phpInfo,
                'csrf_token' => $this->AccountToken(),
                'capture_address' => Security::LoadEnvironmentOrFromFile('CRON_CAPTURE_OUTGOING_ADDRESS') ?? '',
                'recaptcha_enabled' => !empty(RECAPTCHA_PUBLIC_KEY),
                'session_cookie_secure' => filter_var(
                    Security::LoadEnvironmentOrFromFile('SESSION_COOKIE_SECURE'),
                    FILTER_VALIDATE_BOOL,
                ),
            ],
        );
    }

    public function login(
        ?string $username = null, 
        #[\SensitiveParameter] ?string $password = null,
        ?string $g_recaptcha_response = null,
    ): StatusCode
    {
        if ($this->User !== null)
            return $this->Redirect(url: $this->PathPrefix . '/');

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
                    g_recaptcha_response: $g_recaptcha_response,
                    action: 'login',
                );
            }

            if (empty($message))
            {
                if (User::Login(
                    connection: $this->DB, 
                    username: $username, 
                    password: $password, 
                    user_agent: $_SERVER['HTTP_USER_AGENT'] ?? '', 
                    user_ip: Security::GetIpAddress()
                )) {
                    // Login successful
                    $redirect_url = $this->PathPrefix . '/';
                    if (Cookie::Exists(name: 'Redirect'))
                    {
                        $redirect_url = Cookie::Get(name: 'Redirect');
                        Cookie::Delete(name: 'Redirect');
                        if (!is_string($redirect_url) || !preg_match('~^/(?![/\\\\])[^\x00-\x20\x7f\\\\]*$~D', $redirect_url)) {
                            $redirect_url = $this->PathPrefix . '/';
                        }
                    }
        
                    return $this->RedirectWithStatus($redirect_url, StatusCode::SeeOther);
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
            ]
        );
    }
    
    #[RequireLogin]
    public function about(): StatusCode
    {
        $root = dirname(__DIR__);
        $project = json_decode(file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $installed = json_decode(file_get_contents($root . '/vendor/composer/installed.json'), true, flags: JSON_THROW_ON_ERROR);
        $packages = array_intersect_key(array_column($installed['packages'], 'version', 'name'), $project['require']);

        return $this->Render(
            view: 'Home/about',
            title: 'Informazioni sul portale',
            data: [
                'app_version' => $project['version'],
                'php_version' => PHP_VERSION,
                'domain' => DOMAIN,
                'packages' => $packages,
            ],
        );
    }

    #[RequireLogin]
    public function credits(): StatusCode
    {
        $root = dirname(__DIR__);
        $project = json_decode(file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $lock = json_decode(file_get_contents($root . '/composer.lock'), true, flags: JSON_THROW_ON_ERROR);

        return $this->Render(
            view: 'Home/credits',
            title: 'Crediti',
            data: [
                'repository' => $project['homepage'],
                'projectLicense' => $project['license'],
                'contributors' => $project['authors'],
                'technologies' => $project['extra']['credits'],
                'packages' => $lock['packages'],
            ],
        );
    }
}