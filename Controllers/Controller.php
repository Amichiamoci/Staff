<?php
namespace Amichiamoci\Controllers;

use Amichiamoci\Models\StaffBase;
use Amichiamoci\Models\User;
use Amichiamoci\Utils\Cookie;
use Amichiamoci\Utils\File;
class Controller {
    protected \mysqli|false $DB = false;
    private ?User $User;
    private ?StaffBase $Staff;
    private string $Path;

    public function __construct(
        ?\mysqli $connection,
        ?User $user = null,
        ?StaffBase $staff = null,
        string $path = '',
    ) {
        if (isset($connection)) {
            $this->DB = $connection;
        }
        $this->User = $user;
        $this->Staff = $staff;
        $this->Path = $path;
    }

    protected function RequireLogin(bool $require_admin = false): User {
        if (!isset($this->User)) {
            Cookie::Set(name: 'Redirect', value: $this->Path, exp: 3600);
            $this->Redirect(url: '/login');
            // flow interrupted by exit;
        }
        if ($require_admin && !$this->User->IsAdmin) {
            $this->NotAuthorized();
            exit;
        }
        return $this->User;
    }

    protected function IsLoggedIn(): bool {
        return isset($this->User);
    }

    protected function RequireStaff(): ?StaffBase {
        $this->RequireLogin(require_admin: false);

        if (isset($this->Staff))
        {
            return $this->Staff;
        }

        // Load staff on the fly if we can
        if ($this->DB && (
                $this->User->HasAdditionalData() || 
                $this->User->LoadAdditionalData(connection: $this->DB)
            )
        ) {
            $this->Staff = StaffBase::ById(connection: $this->DB, id: $this->User->IdStaff);
        }

        if (!isset($this->Staff) && !$this->User->IsAdmin) {
            $this->NotAuthorized();
            exit;
        }

        return $this->Staff;
    }

    protected function Render(
        string $view, 
        array $data = [], 
        string $title = '',
        int $status_code = 200,
    ): int {
        extract(array: array_merge($data, [
            'title' => $title,
            'view_file' => dirname(path: __DIR__) . "/Views/$view.php",
            'user' => $this->User,
            'staff' => $this->Staff
        ]));

        if (!file_exists(filename: $view_file)) {
            if (file_exists(filename: dirname(path: __DIR__) . "/Views/$view.html")) {
                $view_file = dirname(path: __DIR__) . "/Views/$view.html";
            } else {
                return $this->NotFound();
            }
        }

        require_once dirname(path: __DIR__) . "/Views/Shared/Template.php";
        
        return $status_code;
    }

    protected function Redirect(string $url): int {
        if (empty($url))
        {
            $url = '/';
        }
        header(header: "Location: $url");
        return 302;
    }

    protected function Content(string $type, string $content): int {
        header(header: "Content-Type: $type");
        header(header: "Content-length: " . strlen(string: $content));
        
        ob_clean();
        echo $content;
        return 200;
    }

    protected function Json(mixed $object): int {
        return $this->Content(
            type: 'application/json', 
            content: json_encode(value: $object)
        );
    }

    protected function File(string $file_path, bool $additional_headers = true): int {
        if (!file_exists(filename: $file_path))
        {
            // File not found.
            return $this->NotFound();
        }

        $mime = File::GetMimeType(filename: $file_path);

        if ($additional_headers)
        {
            $last_modified = gmdate(format: 'D, d M Y H:i:s', timestamp: filemtime(filename: $file_path)) . ' GMT';
            $name_parts = explode(
                separator: "/", 
                string: str_replace(
                    search: "\\", 
                    replace: "/", 
                    subject: $file_path
                )
            );
            $actual_file_name = basename(path: urlencode(string: end(array: $name_parts)));
            
            header(header: 'Content-Description: File Transfer');
            header(header: 'Last-Modified: ' . $last_modified);
            header(header: "Content-Disposition: attachment; filename=\"$actual_file_name\"");
        }
        
        return $this->Content(
            type: $mime,
            content: file_get_contents(filename: $file_path)
        );
    }

    protected function NotFound(): int {
        http_response_code(response_code: 404);
        return $this->Render(
            view: 'Shared/Error',
            title: 'Errore 404',
            data: [
                'main_banner' => 'Questa non è la pagina che stai cercando'
            ],
            status_code: 404,
        );
    }

    protected function NotAuthorized(): int {
        http_response_code(response_code: 401);
        return $this->Render(
            view: 'Shared/Error',
            title: 'Errore 401',
            data: [
                'main_banner' => 'Accesso non consentito'
            ],
            status_code: 401,
        );
    }

    protected function BadRequest(): int {
        http_response_code(response_code: 400);

        return 400;
    }

    protected function InternalError(): int {
        http_response_code(500);

        // TODO: show error page
        return 500;
    }

    protected static function IsPost(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'POST';
    }
    protected static function IsGet(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'GET';
    }
}