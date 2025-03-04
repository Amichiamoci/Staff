<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Email;
use Amichiamoci\Models\Message;
use Amichiamoci\Utils\Email as EmailSender;

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
            data: [
                'email' => $email,
                'content_escaped' => $this->DB->real_escape_string(
                    string: EmailSender::Render(title: $email->Subject, content: $email->Content)
                ),
            ],
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

    public function send(
        ?string $to = null,
        ?string $subject = null,
        ?string $body = null
    ): int {
        $this->RequireLogin(require_admin: true);

        if (self::IsPost())
        {
            if (empty($to) || empty($subject) || empty($body))
            {
                return $this->BadRequest();
            }

            $res = EmailSender::Send(
                to: $to,
                subject: $subject,
                body: $body,
                connection: $this->DB
            );
            if ($res) {
                $this->Message(message: Message::Success(content: "Email correttamente inviata a $to"));
                return $this->index();
            }
            $this->Message(message: Message::Error(content: "Impossibile inviare l'email"));
        }

        return $this->Render(
            view: 'Email/send',
            title: 'Invia email',
        );
    }
}