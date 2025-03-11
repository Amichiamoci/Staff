<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Anagrafica;
use Amichiamoci\Models\Message;
use Amichiamoci\Models\MessageType;
use Amichiamoci\Models\Token;
use Amichiamoci\Models\User;
use Amichiamoci\Utils\Cookie;
use Amichiamoci\Utils\Email;
use Amichiamoci\Utils\Security;

class UserController extends Controller {
    public function logout(): int
    {
        $this->RequireLogin()->Logout();
        Cookie::DeleteIfExists(name: "login_forward");
        return $this->Redirect(url: INSTALLATION_PATH . '/');
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

    private static function reset_password_email(
        User $user, 
        #[\SensitiveParameter]  string $new_password
    ): string {
        ob_start();
        ?>
        <h3>Ciao <?= htmlspecialchars(string: $user->Label()) ?></h3>
        <p>
            La tua password è appena stata cambiata da un amministratore.
            D'ora in poi, per accedere, utilizza 
            <span style="user-select: none;"> come password: </span><code style="font-family: monospace;"><?= htmlspecialchars(string: $new_password) ?></code>
        </p>
        <p>
            In caso di altri problemi, contattare nuovamente gli amministratori.
        </p>
        <?php
        $mail_body = ob_get_contents();
        ob_end_clean();
        return $mail_body;
    }

    public function reset(?int $target_id): int {
        $this->RequireLogin(require_admin: true);
        
        if (empty($target_id)) {
            return $this->BadRequest();
        }

        $target = User::ById(connection: $this->DB, id: $target_id);
        if (!isset($target)) {
            return $this->NotFound();
        }

        if (self::IsPost()) {
            $new_password = Security::RandomPassword();

            $result = $target->ForceSetNewPassword(
                connection: $this->DB, 
                new_password: $new_password
            );
            if (!$result) {
                return $this->InternalError();
            }

            $email = Email::GetByUserId(connection: $this->DB, user: $target->Id);
            $result = isset($email);
            if ($result)
            {
                $mail_body = $this->reset_password_email(user: $target, new_password: $new_password);
                $result = Email::Send(
                    to: $email, 
                    subject: "Nuova password", 
                    body: $mail_body, 
                    connection: $this->DB, 
                    hide_output: true,
                );
            }

            if ($result)
            {
                $this->Message(message: Message::Success(
                    content: "Password cambiata ed inviata per mail all'utente con successo"
                ));
            } else
            {
                $this->Message(message: Message::Warn(content: 'Errore durante l\'invio dell\'email'));
                $this->Message(message: Message::Warn(content: "Comunica all'utente la seguente password: $new_password"));
            }
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

    private function password_recover_logic(
        ?string $username,
        ?string $g_recaptcha_response = null,
    ): ?Token
    {
        if (RECAPTCHA_PUBLIC_KEY != NULL)
        {
            $message = Security::Recaptcha3Validation(
                g_recaptcha_response: $g_recaptcha_response,
            );
            if (isset($message)) {
                return null;
            }
        }

        if (!isset($username) || strlen(string: $username) === 0) {
            return null;
        }

        $user = User::ByName(connection: $this->DB, username: $username);
        if (!isset($user)) {
            return null;
        }

        $user->LoadAdditionalData(connection: $this->DB);
        if (!$user->HasAdditionalData() || empty($user->IdAnagrafica)) {
            return null;
        }

        $anagrafica = Anagrafica::ById(connection: $this->DB, id: $user->IdAnagrafica);
        if (!isset($anagrafica) || empty($anagrafica->Email)) {
            return null;
        }

        return Token::Generate(
            connection: $this->DB,
            duration_mins: 120,
            user_id: $user->Id,
            email: $anagrafica->Email,
            requesting_ip: Security::GetIpAddress(),
            requesting_browser: $_SERVER['HTTP_USER_AGENT'],
        );
    }

    private function password_recover_send_mail(Token $token): bool
    {
        ob_start();
        ?>
        <p>
            È stata effettuata una richiesta di recupero password.<br>
            Se non sei stato tu puoi ignorare questa email.
        </p>
        <p style="user-select: none">
            Per reimpostare la password clicca su
        </p>
        <div style="margin: 1em;" class="border">
            <a 
                href="https://<?= DOMAIN . INSTALLATION_PATH ?>/user/token?value=<?= $token->Value ?>&secret=<?= $token->Secret ?>"
                class="no-underline"
                title="Clicca qui">
                <strong>
                    https://<?= DOMAIN . INSTALLATION_PATH  ?>/user/token?value=<?= $token->Value ?>&secret=<?= $token->Secret ?>
                </strong>
            </a>
        </div>
        <p style="user-select: none">
            Non condividere questo link! Consentirebbe a maleintenzionati di prendere il controllo del tuo account.
        </p>
        <p>
            Questo link scadrà il
            <?= $token->ExpirationDate->format(format: 'm/d/Y') ?>
            alle
            <?= $token->ExpirationDate->format(format: 'H:i') ?>
        </p>
        <?php if (isset($token->RequestingBrowser) || isset($token->RequestingIp)) { ?>
            <hr>
            <p>
                <?php if (isset($token->RequestingBrowser)) { ?>
                    Browser: 
                    <span style="font-style: monospace; text-decoration: none !important;">
                        <?= htmlspecialchars(string: $token->RequestingBrowser) ?>
                    </span>
                    <br>
                <?php } ?>
                <?php if (isset($token->RequestingIp)) { ?>
                    Indirizzo Ip: 
                    <span style="font-style: monospace; text-decoration: none !important;">
                        <?= htmlspecialchars(string: $token->RequestingIp) ?>
                    </span>
                <?php } ?>
            </p>
        <?php } ?>
        <?php
        $mail_body = ob_get_contents();
        ob_end_clean();

        return Email::Send(
            to: $token->Email,
            subject: 'Recupero password',
            body: $mail_body,
            connection: $this->DB,
            hide_output: true,
        );
    }

    public function password_recover(
        ?string $username = null,
        ?string $g_recaptcha_response = null,
    ): int {
        if (self::IsLoggedIn()) {
            $this->Message(message: Message::Warn(content: 'Perché recuperare la tua password se sei già loggato?'));
        }
        if (self::IsPost())
        {
            $token = $this->password_recover_logic(
                username: $username, 
                g_recaptcha_response: $g_recaptcha_response
            );
            if (isset($token))
            {
                $this->password_recover_send_mail(token: $token);
            }

            $this->Message(
                message: 
                    "Se esiste un account con lo username '$username', con email associata, riceverà presto un link per reimpostare la password. " .
                    "Tale link è valido per 120 minuti. " .
                    "Nel caso l'account non esistesse, o la richiesta risultati sospetta, nulla accadrà."
            );
        }
        return $this->Render(
            view: 'User/password-recover',
            title: 'Recupera la tua password',
        );
    }

    private function token_submit_logic(
        string $value,
        string $secret,
        #[\SensitiveParameter] string $password,
        ?User &$user = null,
    ): ?string {
        
        $user = null;
        $token = Token::Load(connection: $this->DB, value: $value);

        if (
            !isset($token) || 
            $token->IsExpired() || 
            $token->IsUsed() || 
            !$token->Matches(secret: $secret)
        ) {
            return 'Token non valido, scaduto o già usato';
        }

        if (!$token->Use(connection: $this->DB)) {
            return 'Impossibile reclamare il token';
        }

        $user = User::ById(connection: $this->DB, id: $token->UserId);
        if (!isset($user)) {
            return 'Dati del server non sincronizzati';
        }

        if (!$user->ForceSetNewPassword(connection: $this->DB, new_password: $password)) {
            return 'Impossibile impostare la nuova password. Contattare un amministratore';
        }

        return null;
    }

    public function token(
        ?string $value = null, 
        ?string $secret = null,
        #[\SensitiveParameter] ?string $password = null,
    ): int
    {
        if (!isset($value) || strlen(string: $value) === 0) {
            return $this->BadRequest();
        }
        if (self::IsPost()) {
            if (
                !isset($secret) || 
                !isset($password) ||
                strlen(string: $password) < 8
            ) {
                return $this->BadRequest();
            }

            $user = null;
            $message = $this->token_submit_logic(
                value: $value, 
                secret: $secret, 
                password: $password,
                user: $user
            );
            if (isset($message)) {
                $this->Message(message: Message::Warn(content: $message));
            } else {
                $this->Message(message: Message::Success(content: 'Password reimpostata correttamente'));
                
                // Automatic login for the user
                if (isset($user) && $user instanceof User)
                {
                    $login = User::Login(
                        connection: $this->DB,
                        username: $user->Name,
                        password: $password,
                        user_agent: $_SERVER['HTTP_USER_AGENT'],
                        user_ip: Security::GetIpAddress(),
                    );

                    if ($login) {
                        return $this->me();
                    }
                    $this->Message(message: Message::Error(content: 'Non è stato possibile effettuare il login in automatico'));
                }
            }
        }
        return $this->Render(
            view: 'User/token',
            title: 'Reimposta la password',
            data: [
                'value' => $value,
                'secret' => $secret,
            ]
        );
    }

    public function update(
        ?string $new_username, 
        #[\SensitiveParameter] ?string $current_password, 
        #[\SensitiveParameter] ?string $new_password = null,
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

        $activity = $target->LoginList(connection: $this->DB);
        return $this->Render(
            view: 'User/view',
            title: $target->Name,
            data: [
                'target' => $target,
                'activity' => $activity,
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

    private static function welcome_email(
        User $user, 
        #[\SensitiveParameter] string $password, 
    ): string
    {
        ob_start();
        ?>
            <h3>Benvenuto/a</h3>
            <h5>
                È sempre bello vedere dei nuovi staffisti!
            </h5>
            <p>
                È appena stato creato un utente sul portale con questa email.
                Ti chiediamo di loggarti nella sezione 
                <a href="https://<?= DOMAIN . INSTALLATION_PATH ?>" class="link">admin</a>
                utilizzando come nome utente: 
                <code style="font-family: monospace;"><?= htmlspecialchars(string: $user->Name) ?></code>
                e come <strong>password: </strong>
                <code style="font-family: monospace;"><?= htmlspecialchars(string: $password) ?></code>
            </p>
            <p>
                Una volta loggato potrai cambiare sia nome utente che password.
                <br>
                Nel caso tu non riesca ad accedere con le credenziali appena fornite 
                prova a cancellare i cookie e riprovare dopo qualche minuto.
                <br>
                Se il problema dovesse persistere, provare a contattare un amministratore.
            </p>
            <hr>
            <p>
                Dal portale ti sarà possibile inserire i tuoi dati anagrafici, scegliendo la mail con cui gestire l'account
                (puoi quindi non scegliere questa) e, anno per anno, registrarti come staffista all'edizione corrente.
            </p>
            <p>
                In caso di problemi contatta tempestivamente gli staffisti adiniti alla gestione del portale.
            </p>
            <br>
            <p style="font-size: smaller; user-select: none;">
                Ecco una serie di informazioni che non ti interessaranno, ma io le metto ugualmente:<br>
                <ul>
                    <li>
                        Il tuo Id utente è <output style="user-select:none;"><?= $user->Id ?></output>
                    </li>
                </ul>
            </p>
        <?php
        $mail_body = ob_get_contents();
        ob_end_clean();
        return $mail_body;
    }

    public function new(
        ?string $email = null, 
        bool $admin = false,
    ): int
    {
        $this->RequireLogin(require_admin: true);
        if ($this->IsPost() && !empty($email)) {
            $password = Security::RandomPassword();
            $user_name = explode(separator: '@', string: $email)[0];

            $created_user = User::Create(
                connection: $this->DB, 
                username: $user_name, 
                password: $password, 
                is_admin: $admin
            );
            if (!isset($created_user))
            {
                return $this->InternalError();
            }

            $mail_text = $this->welcome_email(user: $created_user, password: $password);
            
            if (!Email::Send(
                to: $email, 
                subject: "Creazione utente", 
                body: $mail_text, 
                connection: $this->DB, 
                hide_output: true,
            )) {
                $this->Message(message: Message::Error(content: 'Errore durante l\'invio dell\'email'));
            }
            $this->Message(message: Message::Success(content: 'Utente creato correttamente'));
            return $this->view(id: $created_user->Id);
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