<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Email;

class EmailController extends Controller {
    public function index(): int
    {
        $this->RequireLogin(require_admin: true);
        return $this->Render(
            view: 'Email/index',
            data: ['emails' => Email::All(connection: $this->DB)],
            title: 'Lista Email',
        );
    }

    public function view(?int $id): int
    {
        $this->RequireLogin(require_admin: true);
        $email = Email::ById(connection: $this->DB, id: $id);
        if (!isset($email)) {
            return $this->NotFound();
        }

        return $this->Render(
            view: 'Email/view',
            data: ['email' => $email],
            title: 'Email #' . $id,
        );
    }

    public function heartbeat(?int $id): int {
        if (!isset($id)) {
            return $this->NotFound();
        }

        Email::HeartBeat(connection: $this->DB, id: $id);
        
        return $this->File(
            file_path: dirname(path: __DIR__) . '/Public/images/blank_dot.svg',
            additional_headers: false
        );
    }
}