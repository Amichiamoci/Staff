<?php

namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\StatusCode;

use Amichiamoci\Models\{Email, Message};
use Amichiamoci\Utils\Email as EmailSender;

class EmailController
extends Controller
{
    #[RequireLogin(requireAdmin: true)]
    public function index(): StatusCode
    {
        if (!empty($_SESSION['email_sent'])) {
            unset($_SESSION['email_sent']);
            $this->Message(Message::Success(content: 'Email inviata.'));
        }

        return $this->Render(
            view: 'Email/index',
            data: ['heading' => 'Ultime 500 email', 'emails' => Email::All($this->DB)],
            title: 'Ultime 500 email',
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function history(?string $recipient = null): StatusCode
    {
        return $this->Render(
            view: 'Email/index',
            data: [
                'heading' => 'Storico per destinatario',
                'recipients' => Email::Recipients($this->DB),
                'recipient' => $recipient,
                'emails' => $recipient === null || $recipient === '' ? [] : Email::All($this->DB, $recipient),
            ],
            title: 'Storico per destinatario',
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function view(?int $id = null): StatusCode
    {
        if ($id === null) {
            return $this->NotFound();
        }

        $email = Email::ById(connection: $this->DB, id: $id);
        if ($email === null)
            return $this->NotFound();

        return $this->Render(
            view: 'Email/view',
            data: [
                'email' => $email,
                'email_preview' => EmailSender::Render(title: $email->Subject, content: $email->Content),
            ],
            title: 'Email #' . $id,
        );
    }

    public function heartbeat(?int $id = null): StatusCode
    {
        if ($id === null || $id <= 0)
            return $this->NotFound();

        Email::HeartBeat(connection: $this->DB, id: $id);

        return $this->File(
            file_path: dirname(path: __DIR__) . '/Public/images/blank_dot.svg',
            additional_headers: false
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function send(
        ?string $to = null,
        ?string $subject = null,
        ?string $body = null,
        ?string $csrf_token = null,
    ): StatusCode
    {
        $token = $_SESSION['email_csrf_token'] ??= bin2hex(random_bytes(32));

        if ($this->IsPost()) {
            if ($csrf_token === null || !hash_equals($token, $csrf_token)) {
                return $this->BadRequest();
            }

            $to = trim($to ?? '');
            $subject = trim($subject ?? '');
            $body = trim($body ?? '');
            if (!filter_var($to, FILTER_VALIDATE_EMAIL) || strlen($to) > 64
                || $subject === '' || mb_strlen($subject) > 128 || $body === ''
            ) {
                $this->Message(Message::Error(content: 'Controlla destinatario, oggetto e corpo del messaggio.'));
            } elseif (EmailSender::Send(to: $to, subject: $subject, body: $body, connection: $this->DB)) {
                $_SESSION['email_sent'] = true;
                return $this->RedirectWithStatus($this->PathPrefix . '/email', StatusCode::SeeOther);
            } else {
                $this->Message(Message::Error(content: "Impossibile inviare l'email."));
            }
        }

        return $this->Render(
            view: 'Email/send',
            title: 'Invia email',
            data: [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'csrfToken' => $token,
            ],
        );
    }
}