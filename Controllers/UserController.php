<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Message;
use Amichiamoci\Models\MessageType;
use Amichiamoci\Models\User;
use Amichiamoci\Utils\Cookie;
use Amichiamoci\Utils\Email;
use Amichiamoci\Utils\Security;

class UserController extends Controller {
    public function logout(): int
    {
        $this->RequireLogin()->Logout();
        Cookie::DeleteIfExists(name: "login_forward");
        return $this->Redirect(url: '/');
    }

    public function delete(?int $target_id): int {
        $this->RequireLogin(require_admin: true);

        if (!isset($target_id)) {
            return $this->NotFound();
        }
        
        $user = User::ById(connection: $this->DB, id: $target_id);
        if (!isset($user)) {
            return $this->NotFound();
        }

        if (User::Delete(connection: $this->DB, target: $target_id)) {
            $this->Message(message: new Message(type: MessageType::Success, content: 'Utente cancellato'));
            return $this->all();
        }
        $this->Message(message: new Message(type: MessageType::Error, content: 'Qualcosa è andato storto'));
        return $this->view(id: $target_id);
    }

    public function reset(?int $target_id): int {
        $this->RequireLogin(require_admin: true);
        
        if (empty($target_id)) {
            return $this->BadRequest();
        }

        if (self::IsPost()) {
            // $new_password = User::ResetPassword(connection: $this->DB, target: $target_id);
            $this->Message(message: Message::Success(content: 'Password cambiata con successo'));
        }
        return $this->view(id: $target_id);
    }

    public function ban(?int $target_id): int {
        $this->RequireLogin(require_admin: true);
        
        if (User::Ban(connection: $this->DB, id: $target_id)) {
            $this->Message(message: new Message(type: MessageType::Success, content: 'Utente bloccato'));
        } else {
            $this->Message(message: new Message(type: MessageType::Error, content: 'Qualcosa è andato storto'));
        }
        return $this->view(id: $target_id);
    }

    public function restore(?int $target_id): int {
        $this->RequireLogin(require_admin: true);
        
        if (User::Restore(connection: $this->DB, id: $target_id)) {
            $this->Message(message: new Message(type: MessageType::Success, content: 'Utente sbloccato'));
        } else {
            $this->Message(message: new Message(type: MessageType::Error, content: 'Qualcosa è andato storto'));
        }
        return $this->view(id: $target_id);
    }

    public function password_recover(?string $username) {
        
    }

    public function update(
        ?string $new_username, 
        ?string $current_password, 
        ?string $new_password = null,
    ): int {
        $user = $this->RequireLogin();
        if (self::IsPost() && !empty($new_username) && !empty($current_password))
        {
            if ($user->Name !== $new_username)
            {
                if ($user->ChangeUserName(
                    connection: $this->DB, 
                    password: $current_password, 
                    new_username: $new_username)
                ) {
                    $this->Message(message: Message::Success(content: 'Nome utente cambiato correttamente'));
                } else {
                    $this->Message(message: Message::Error(content: 'Non è stato possibile cambiare il nome utente'));
                }
            }
            if (!empty($new_password))
            {
                if ($user->ChangePassword(
                    connection: $this->DB, 
                    password: $current_password, 
                    new_password: $new_password)
                ) {
                    $this->Message(message: Message::Success(content: 'Password cambiata correttamente'));
                } else {
                    $this->Message(message: Message::Error(content: 'Non è stato possibile cambiare la password'));
                }
            }
        }
        return $this->me();
    }

    public function view(?int $id): int {
        if (!isset($id)) {
            return $this->BadRequest();
        }
        
        $user = $this->RequireLogin();
        $target = User::ById(connection: $this->DB, id: $id);
        if (!isset($target)) {
            return $this->NotFound();
        }

        if ($target->Id !== $user->Id && !$user->IsAdmin) { 
            // Admins can view personal pages of others
            return $this->NotAuthorized();
        }
        return $this->Render(
            view: 'User/view',
            title: $target->Name,
            data: [
                'target' => $target,
            ],
        );
    }
    public function username(?string $u): int {
        if (!isset($u)) {
            return $this->BadRequest();
        }

        $target = User::ByName(connection: $this->DB, username: $u);
        if (!isset($target)) {
            $id = 0;
        } else {
            $id = $target->Id;
        }
        return $this->view(id: $id);
    }

    public function me(): int {
        $user = $this->RequireLogin();
        return $this->view(id: $user->Id);
    }

    public function all(): int {
        $this->RequireLogin(require_admin: true);
        return $this->Render(
            view: 'User/all', 
            title: 'Utenti',
            data: ['users' => User::All(connection: $this->DB)]
        );
    }

    public function activity(): int {
        $this->RequireLogin(require_admin: true);
        return $this->Render(
            view: 'User/activity',
            title: 'Attività utenti',
            data: ['activity' => User::Activity(connection: $this->DB)]
        );
    }

    public function new(?string $email = null, bool $admin = false): int {
        $this->RequireLogin(require_admin: true);
        if ($this->IsPost() && !empty($email)) {
            $password = Security::RandomPassword();
            $hashed_password = Security::Hash(str: $password);
            $user_name = explode(separator: '@', string: $email)[0];

            $created_user = User::Create(connection: $this->DB, username: $user_name, password: $hashed_password, is_admin: $admin);
            if (!isset($created_user) || $created_user->Id == 0) {
                return $this->InternalError();
            }
            $generated_id = $created_user->Id;

            $mail_text = join(separator: "\r\n", array: array(
                "<h3>Benvenuto/a</h3>",
                "<p>&Egrave; appena stato creato un utente sul sito con questa email.",
                "Ti chiediamo di loggarti sul sito nella sezione <a href=\"https://www.amichiamoci.it/admin\">admin</a>,\r\n" .
                    "utilizzando come <strong style=\"user-select: none;\">nome utente: </strong><code style=\"font-family: monospace;\">$user_name</code>\r\n" .
                    "<span style=\"user-select: none;\"> e come </span><strong style=\"user-select: none;\">password: </strong><code style='font-family: monospace;'>$password</code><br>",
                "Una volta loggato potrai cambiare sia nome utente che password.</p>",
                "<p>Nel caso tu non riesca a loggarti con le credenziali appena fornite prova a cancellare i cookie e riprovare dopo qualche minuto.</p>",
                "<hr>",
                "<p>Dal portale ti sar&agrave; possibile inserire i tuoi dati anagrafici, scegliendo la mail con cui gestire l'account\r\n" .
                "(puoi quindi non scegliere questa) e anno per anno registrarti come staffista all'edizione corrente.</p>",
                "<hr>",
                "<p>In caso di problemi scrivi tempestivamente a <a href=\"mailto:info@amichiamoci.it\">info@amichiamoci.it</a></p>",
                "Ecco una serie di informazioni che non ti interessaranno, ma io le metto ugualmente:<br>",
                "Hash della password: <output style=\"user-select:none;\">$hashed_password</output><br>",
                "User id: <output style=\"user-select:none;\">$generated_id</output>"));
            $subject = "Creazione utente";
            
            if (!Email::Send(
                to: $email, 
                subject: $subject, 
                body: $mail_text, 
                connection: $this->DB, 
                hide_output: true,
            )) {
                $this->Message(message: new Message(type: MessageType::Error, content: 'Errore durante l\'invio dell\'email'));
            }
            return $this->view(id: $generated_id);
        }

        return $this->Render(
            view: 'User/new', 
            title: 'Crea nuovo utente',
            data: [
                'email' => $email,
                'admin' => $admin,
            ],
        );
    }
}