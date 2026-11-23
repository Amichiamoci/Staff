<?php

namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Utils\Cookie;

use Amichiamoci\Models\{
    Anagrafica,
    Message,
    Staff,
    StaffBase,
    Token,
    User,
};
use Amichiamoci\Utils\{Email, Security};

class UserController
extends Controller
{
    #[RequireLogin]
    public function logout(): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        $this->getUser()->Logout();
        Cookie::DeleteIfExists(name: "login_forward");
        return $this->Redirect(url: $this->PathPrefix . '/');
    }

    #[RequireLogin(requireAdmin: true)]
    public function delete(?int $target_id = null, ?string $csrf_token = null): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (!$this->ValidAccountToken($csrf_token) || $target_id === null || $target_id <= 0
            || $target_id === $this->User->Id
        ) {
            return $this->BadRequest();
        }

        $user = User::ById(connection: $this->DB, id: $target_id);
        if (!isset($user))
            return $this->NotFound();

        if (User::Delete(connection: $this->DB, target: $target_id))
        {
            $this->Message(message: Message::Success(content: 'Utente cancellato'));
            return $this->all();
        }

        $this->Message(message: Message::Error(content: 'Qualcosa è andato storto'));
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

    #[RequireLogin(requireAdmin: true)]
    public function reset(?int $target_id = null, ?string $csrf_token = null): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (!$this->ValidAccountToken($csrf_token) || $target_id === null || $target_id <= 0
            || $target_id === $this->User->Id
        ) {
            return $this->BadRequest();
        }

        $target = User::ById(connection: $this->DB, id: $target_id);
        if (!isset($target)) 
            return $this->NotFound();

        $new_password = Security::RandomPassword();

        $result = $target->ForceSetNewPassword(
            connection: $this->DB,
            new_password: $new_password
        );
        if (!$result)
            return $this->InternalError();

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

        return $this->view(id: $target_id);
    }

    #[RequireLogin(requireAdmin: true)]
    public function ban(?int $target_id = null, ?string $csrf_token = null): StatusCode
    {
        return $this->setBlocked($target_id, $csrf_token, blocked: true);
    }

    #[RequireLogin(requireAdmin: true)]
    public function restore(?int $target_id = null, ?string $csrf_token = null): StatusCode
    {
        return $this->setBlocked($target_id, $csrf_token, blocked: false);
    }

    private function setBlocked(?int $target_id, ?string $csrf_token, bool $blocked): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (!$this->ValidAccountToken($csrf_token) || $target_id === null || $target_id <= 0
            || $target_id === $this->User->Id
        ) {
            return $this->BadRequest();
        }

        if (User::ById($this->DB, $target_id) === null) {
            return $this->NotFound();
        }

        $updated = $blocked ? User::Ban($this->DB, $target_id) : User::Restore($this->DB, $target_id);
        $this->Message($updated
            ? Message::Success($blocked ? 'Account bloccato' : 'Account riabilitato')
            : Message::Error('Non è stato possibile aggiornare lo stato dell’account'));

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
                action: 'password_recover',
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
            requesting_browser: $_SERVER['HTTP_USER_AGENT'] ?? '',
        );
    }

    private function password_recover_send_mail(Token $token): bool
    {
        $domain = Security::LoadEnvironmentOrFromFile(var: 'DOMAIN');
        if ($domain === null || !preg_match('/^[a-z0-9.-]+(?::[0-9]+)?$/iD', $domain)) {
            return false;
        }
        $installation_url = htmlspecialchars($domain) . $this->PathPrefix;
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
                href="https://<?= $installation_url ?>/user/token?value=<?= $token->Value ?>&secret=<?= $token->Secret ?>"
                class="no-underline"
                title="Clicca qui">
                <strong>
                    https://<?= $installation_url ?>/user/token?value=<?= $token->Value ?>&secret=<?= $token->Secret ?>
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
    ): StatusCode {
        if ($this->User !== null)
            $this->Message(message: Message::Warn(content: 'Perché recuperare la tua password se sei già loggato?'));

        if ($this->IsPost())
        {
            $token = $this->password_recover_logic(
                username: $username, 
                g_recaptcha_response: $g_recaptcha_response
            );
            if ($token !== null)
                $this->password_recover_send_mail(token: $token);

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
            $token === null || 
            $token->IsExpired() || 
            $token->IsUsed() || 
            !$token->Matches(secret: $secret)
        ) 
            return 'Token non valido, scaduto o già usato';

        $user = User::ById(connection: $this->DB, id: $token->UserId);
        if ($user === null || $user->IsBanned)
            return 'Token non valido, scaduto o già usato';

        $this->DB->begin_transaction();
        try {
            if (!$token->Use(connection: $this->DB)) {
                $this->DB->rollback();
                return 'Impossibile reclamare il token';
            }
            if (!$user->ForceSetNewPassword(connection: $this->DB, new_password: $password)) {
                $this->DB->rollback();
                return 'Impossibile impostare la nuova password. Contattare un amministratore';
            }
            $this->DB->commit();
        } catch (\Throwable $exception) {
            $this->DB->rollback();
            throw $exception;
        }
        return null;
    }

    public function token(
        ?string $value = null, 
        ?string $secret = null,
        #[\SensitiveParameter] ?string $password = null,
    ): StatusCode
    {
        if (!isset($value) || strlen(string: $value) === 0)
            return $this->BadRequest();

        if ($this->IsPost())
        {
            if (!isset($secret) || 
                !isset($password) ||
                strlen(string: $password) < 8
            ) 
                return $this->BadRequest();

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
                        user_agent: $_SERVER['HTTP_USER_AGENT'] ?? '',
                        user_ip: Security::GetIpAddress(),
                    );

                    if ($login) 
                        return $this->Redirect($this->PathPrefix . "/user/me");
                    
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

    #[RequireLogin]
    public function update(
        ?string $new_username = null,
        #[\SensitiveParameter] ?string $current_password = null,
        #[\SensitiveParameter] ?string $new_password = null,
        ?string $csrf_token = null,
    ): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (!$this->ValidAccountToken($csrf_token)) {
            return $this->BadRequest();
        }

        $user = $this->getUser();
        if (!empty($new_username) && !empty($current_password))
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

    #[RequireLogin]
    public function view(?int $id): StatusCode
    {
        if (empty($id))
            return $this->BadRequest();
        
        $user = $this->User;
        if ($id !== $user->Id && !$user->Admin) {
            return $this->NotAuthorized();
        }

        $target = User::ById(connection: $this->DB, id: $id);
        if ($target === null) {
            return $this->NotFound();
        }

        $target->LoadAdditionalData($this->DB);
        $linkedStaff = empty($target->IdStaff)
            ? null
            : Staff::ById($this->DB, $target->IdStaff) ?? StaffBase::ById($this->DB, $target->IdStaff);
        $activity = $target->LoginList(connection: $this->DB);
        return $this->Render(
            view: 'User/view',
            title: $target->Id === $user->Id ? 'Il mio account' : $target->Name,
            data: [
                'target' => $target,
                'linked_staff' => $linkedStaff,
                'csrf_token' => $this->AccountToken(),
                'activity' => $activity,
            ],
        );
    }

    #[RequireLogin]
    public function username(?string $u): StatusCode
    {
        if (!isset($u))
            return $this->BadRequest();

        $target = User::ByName(connection: $this->DB, username: $u);
        if (!isset($target)) {
            $id = 0;
        } else {
            $id = $target->Id;
        }

        return $this->view(id: $id);
    }

    #[RequireLogin]
    public function me(): StatusCode
    {
        return $this->view(id: $this->User->Id);
    }

    #[RequireLogin(requireAdmin: true)]
    public function all(): StatusCode
    {
        return $this->Render(
            view: 'User/all', 
            title: 'Utenti',
            data: ['users' => User::All(connection: $this->DB)]
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function activity(): StatusCode
    {
        return $this->Render(
            view: 'User/activity',
            title: 'Ultimi accessi',
            data: ['activity' => User::Activity(connection: $this->DB)]
        );
    }

    private function welcome_email(
        User $user, 
        #[\SensitiveParameter] string $password, 
    ): string
    {
        $domain = Security::LoadEnvironmentOrFromFile(var: 'DOMAIN');
        if ($domain === null || !preg_match('/^[a-z0-9.-]+(?::[0-9]+)?$/iD', $domain)) {
            return false;
        }
        $P = $this->PathPrefix;
        ob_start();
        require dirname(path: __DIR__) . "/Views/Email/welcome.php";
        $mail_body = ob_get_contents();
        ob_end_clean();
        return $mail_body;
    }

    #[RequireLogin(requireAdmin: true)]
    public function new(
        ?string $email = null, 
        bool $admin = false,
        ?string $csrf_token = null,
    ): StatusCode
    {
        if ($this->IsPost()) {
            if (!$this->ValidAccountToken($csrf_token)) {
                return $this->BadRequest();
            }
            if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 64) {
                $this->Message(Message::Error('Inserisci un indirizzo email valido.'));
                return $this->renderNewAccount($email, $admin);
            }

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

        return $this->renderNewAccount($email, $admin);
    }

    private function renderNewAccount(?string $email, bool $admin): StatusCode
    {
        return $this->Render(
            view: 'User/new', 
            title: 'Crea nuovo utente',
            data: [
                'email' => $email,
                'admin' => $admin,
                'csrf_token' => $this->AccountToken(),
            ],
        );
    }
}