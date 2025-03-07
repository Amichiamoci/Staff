<?php
namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Message;
use Amichiamoci\Models\MessageType;
use Amichiamoci\Models\Staff;
use Amichiamoci\Models\User;
use Amichiamoci\Utils\Cookie;
use Amichiamoci\Utils\File;
class Controller {
    protected \mysqli|false $DB = false;
    private ?User $User;
    private ?Staff $Staff;
    private string $Path;
    private array $AlertsToDisplay = [];

    public function __construct(
        ?\mysqli $connection,
        ?User $user = null,
        ?Staff $staff = null,
        string $path = '',
    ) {
        if (isset($connection)) {
            $this->DB = $connection;
        }
        $this->User = $user;
        $this->Staff = $staff;
        $this->Path = $path;
    }

    /**
     * If the user variable is not set interrupts the flow and redirects to login page
     * @param bool $require_admin Require that the user is also an admin user. Calls NotAuthorized() if not
     * @return User The loaded user
     */
    protected function RequireLogin(bool $require_admin = false): User {
        if (!isset($this->User)) {
            Cookie::Set(name: 'Redirect', value: $this->Path, exp: 3600);
            $this->Redirect(url: INSTALLATION_PATH . '/login');
            exit;
        }
        if ($require_admin && !$this->User->IsAdmin) {
            $this->NotAuthorized();
            exit;
        }
        return $this->User;
    }

    /**
     * Checks if the user variable is set
     * @return bool
     */
    protected function IsLoggedIn(): bool {
        return isset($this->User);
    }

    /**
     * Returns the associated staff object. If none exists interrupts the flow and calls
     * NotAuthorized(). Returns null if there is no staff associated with the user but the user
     * is admin.
     * @return Staff|null
     */
    protected function RequireStaff(): ?Staff {
        $this->RequireLogin(require_admin: false);

        if (isset($this->Staff))
        {
            return $this->Staff;
        }

        if (
            $this->DB && 
            $this->User->HasAdditionalData() && 
            isset($this->User->IdStaff)
        ) {
            // Try to load staff on the fly
            $this->Staff = Staff::ById(connection: $this->DB, id: $this->User->IdStaff);
        }

        if (!isset($this->Staff) && !$this->User->IsAdmin) {
            $this->NotAuthorized();
            exit;
        }

        return $this->Staff;
    }

    /**
     * Adds a message to display in the current flow.
     * Does not work with redirects
     * @param \Amichiamoci\Models\Message|string|null $message
     * @return void
     */
    protected function Message(Message|string|null $message): void {
        if (!isset($message)) {
            return;
        }
        if (is_string(value: $message)) {
            $message = new Message(type: MessageType::Info, content: $message);
        }
        $this->AlertsToDisplay[] = $message;
    }

    /**
     * Renders a particular view.
     * If the associated file in not found calls NotFound()
     * @param string $view View file name (omit the '.php')
     * @param array $data Variables to pass to the rendering php. Variables $user and $staff are automatically loaded
     * @param string $title Title of the page, is passed also as $tile variable
     * @param int $status_code Override the status code of the response
     * @return int The status code of the response
     */
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
            'staff' => $this->Staff,
            'alerts' => $this->AlertsToDisplay,
            'B' => INSTALLATION_PATH,
        ]));

        if (!file_exists(filename: $view_file)) {
            if (file_exists(filename: dirname(path: __DIR__) . "/Views/$view.html")) {
                $view_file = dirname(path: __DIR__) . "/Views/$view.html";
            } else {
                return $this->NotFound();
            }
        }

        if ($status_code !== 200)
            http_response_code(response_code: $status_code);

        require_once dirname(path: __DIR__) . "/Views/Shared/Template.php";
        
        return $status_code;
    }

    /**
     * Redirectes to the specified url
     * @param string $url
     * @return int The status code of the response (302)
     */
    protected function Redirect(string $url): int {
        if (empty($url))
        {
            $url = '/';
        }
        header(header: "Location: $url");
        return 302;
    }

    /**
     * Raw string as response
     * @param string $type Mime type of the content
     * @param string $content Actual content
     * @param int $status_code The status code to return (200 by default)
     * @return int The status code of the response
     */
    protected function Content(
        string $type, 
        string $content,
        int $status_code = 200,
    ): int {
        if ($status_code !== 200)
        {
            http_response_code(response_code: $status_code);
        }
        header(header: "Content-Type: $type");
        header(header: "Content-length: " . strlen(string: $content));
        
        // ob_clean();
        echo $content;
        return $status_code;
    }

    /**
     * Render an object as json and print it to the stream
     * @param mixed $object The object. Will be rendered via json_encode()
     * @param int $status_code The status code to return (200 by default)
     * @return int The status code of the response (200)
     */
    protected function Json(mixed $object, int $status_code = 200): int {
        return $this->Content(
            type: 'application/json', 
            content: json_encode(value: $object),
            status_code: $status_code,
        );
    }

    /**
     * Loads a file and prints it to the stream.
     * Calls NotFound if the file can't be found
     * @param string $file_path
     * @param bool $additional_headers
     * @param int $status_code The status code to return (200 by default)
     * @return int
     */
    protected function File(
        string $file_path, 
        bool $additional_headers = true,
        int $status_code = 200,
    ): int {
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
            content: file_get_contents(filename: $file_path),
            status_code: $status_code,
        );
    }

    protected function NotFound(): int {
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
        return $this->Render(
            view: 'Shared/Error',
            title: 'Errore 400',
            data: [
                'main_banner' => 'Richiesta non valida'
            ],
            status_code: 400,
        );
    }

    protected function InternalError(?\Throwable $ex = null): int {
        return $this->Render(
            view: 'Shared/Error',
            title: 'Errore 500',
            data: [
                'main_banner' => 'Qualcosa non ha funzionato',
                'exception' => $ex
            ],
            status_code: 500,
        );
    }

    protected static function IsPost(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'POST';
    }
    protected static function IsGet(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'GET';
    }
}