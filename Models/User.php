<?php
namespace Amichiamoci\Models;
use Amichiamoci\Utils\Cookie;
use Amichiamoci\Utils\Email;
use Amichiamoci\Utils\Security;
use Amichiamoci\Models\Templates\DbEntity;

class User implements DbEntity
{
    public static string $COOKIE_NAME = "LoginSession";
    public static string $USER_NAME = "user_name";
    public static string $USER_ID = "user_id";
    public static string $LOGIN_TIME = "login_time";
    public static string $SESSION_FLAG = "login_flag";
    public static string $SESSION_DB_ID = "login_db_id";
    public static string $USER_IS_ADMIN = "is_admin";
    public static string $GIVEN_NAME = "given_name";
    public static string $ID_ANAGRAFICA = "id_anagrafica";
    public static string $ID_STAFF = "id_staff";

    
    public int $Id;
    public string $Name;
    public int $LoginTime = 0;
    public string $SessionFlag = "";
    public int $SessionDbId = 0;
    public bool $IsAdmin = false;
    public bool $IsBanned = false;

    // Additional data
    public ?string $RealName = null;
    public ?int $IdStaff = null;
    public ?int $IdAnagrafica = null;


    public function __construct(
        string|int $id, 
        string $name, 
        string|int|\DateTime|null $login_time, 
        bool $admin = false,
        bool $is_blocked = false
    ) {
        $this->Id = (int)$id;
        $this->Name = $name;
        if (isset($login_time)) {
            if ($login_time instanceof \DateTime) {
                $this->LoginTime = $login_time->getTimestamp();
            } elseif (is_string(value: $login_time)) {
                $this->LoginTime = (new \DateTime(datetime: $login_time))->getTimestamp();
            } else {
                $this->LoginTime = $login_time;
            }
        }
        $this->IsAdmin = $admin;
        $this->IsBanned = $is_blocked;
    }
    public function Logout() : bool
    {
        session_unset();
        session_destroy();
        return Cookie::DeleteIfItIs(self::$COOKIE_NAME, (string)$this->Id);
    }
    private static function SessionStart() : bool
    {
        return session_start(options: [
            'name' => self::$COOKIE_NAME,
            'cookie_lifetime' => '172800', // 48h
            'cookie_domain' => DOMAIN,
            'cookie_path' => defined(constant_name: 'INSTALLATION_PATH') ? INSTALLATION_PATH : '',
            'cookie_httponly' => '1'
        ]);
    }
    private static function LogSessionStart(
        \mysqli $connection,
        int $id,
        string $flag,
        string $user_ip
    ) : int {
        $flag = $connection->real_escape_string(string: $flag);
        $user_ip = $connection->real_escape_string(string: $user_ip);
        $query = "CALL StartSession($id, '$flag', '$user_ip');";
        $result = $connection->query(query: $query);
        if (!$result)
        {
            $connection->next_result();
            //echo "Bad session query";
            return 0;
        }
        $ret = 0;
        if ($row = $result->fetch_assoc())
        {
            $ret = (int)$row["session_id"];
        }
        $result->close();
        $connection->next_result();
        return $ret;
    }
    
    public static function Login(
        \mysqli $connection, 
        string $username,
        string $password,
        string $user_agent,
        string $user_ip) : bool
    {
        if (!$connection || 
            !isset($username) || empty($username) || 
            !isset($password) || empty($password))
            return false;
        if (!isset($user_agent)) $user_agent = "";
        if (!isset($user_ip)) $user_ip = "";

        $query = "CALL `GetUserPassword`(?);";
        $result = $connection->execute_query(query: $query, params: [ $username ]);
        if (!$result)
        {
            $connection->next_result();
            return false;
        }
        $passed = false;
        $id = 0;
        $admin = false;
        if ($row = $result->fetch_assoc())
        {
            $id = (int)$row["id"];
            $hash = $row["password"];
            $admin = isset($row["is_admin"]) && (int)$row["is_admin"] === 1;
            $passed = $id !== 0 && Security::TestPassword(password: $password, hash: $hash);
        }
        $result->close();
        $connection->next_result();
        if (!$passed)
        {
            //echo "Password verify";
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            if (!self::SessionStart())
            {
                //echo "Could not start session";
                return false;
            }
            //echo "Session was not active but is now";
        }
        $flag = sha1(string: $user_agent . $user_ip);
        $_SESSION[self::$USER_ID] = $id;
        $_SESSION[self::$USER_NAME] = $username;
        $_SESSION[self::$LOGIN_TIME] = time();
        $_SESSION[self::$USER_IS_ADMIN] = $admin;
        $_SESSION[self::$SESSION_FLAG] = $flag;
        $_SESSION[self::$SESSION_DB_ID] = self::LogSessionStart(connection: $connection, id: $id, flag: $flag, user_ip: $user_ip);
        /*
        if ($_SESSION[self::$SESSION_DB_ID] === 0)
        {
            echo "Session id appears to be 0";
        }
        */
        return $_SESSION[self::$SESSION_DB_ID] !== 0;
    }

    public static function LoadFromSession(): ?self
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !self::SessionStart())
        {
            return null;
        }
        if (!isset($_SESSION[self::$USER_ID]) || empty($_SESSION[self::$USER_ID]) ||
            !isset($_SESSION[self::$USER_NAME]) || empty($_SESSION[self::$USER_NAME]) ||
            !isset($_SESSION[self::$SESSION_FLAG]) || empty($_SESSION[self::$SESSION_FLAG]) ||
            !isset($_SESSION[self::$SESSION_DB_ID]) || $_SESSION[self::$SESSION_DB_ID] == 0 ||
            !isset($_SESSION[self::$LOGIN_TIME]))
        {
            return null;
        }
        $user = new self(
            id: $_SESSION[self::$USER_ID], 
            name: $_SESSION[self::$USER_NAME], 
            login_time: $_SESSION[self::$LOGIN_TIME], 
            admin: isset($_SESSION[self::$USER_IS_ADMIN]) && (bool)$_SESSION[self::$USER_IS_ADMIN]);
        $user->SessionDbId = (int)$_SESSION[self::$SESSION_DB_ID];
        $user->SessionFlag = $_SESSION[self::$SESSION_FLAG];

        // Load optional paramters from db, if present
        if (isset($_SESSION[self::$ID_ANAGRAFICA]) && $_SESSION[self::$ID_ANAGRAFICA] != 0)
        {
            $user->IdAnagrafica = (int)$_SESSION[self::$ID_ANAGRAFICA];
        }
        if (isset($_SESSION[self::$ID_STAFF]) && $_SESSION[self::$ID_STAFF] != 0)
        {
            $user->IdStaff = (int)$_SESSION[self::$ID_STAFF];
        }
        if (isset($_SESSION[self::$GIVEN_NAME]) && !empty($_SESSION[self::$GIVEN_NAME]))
        {
            $user->RealName = $_SESSION[self::$GIVEN_NAME];
        }

        return $user;
    }
    public function LoadAdditionalData(\mysqli $connection): bool
    {
        if (!$connection || $this->Id === 0)
            return false;
        $id = $this->Id;
        $get_anagrafica_query = "CALL GetStaffFromUserId($id)";
        $result = $connection->query(query: $get_anagrafica_query);
        if (!$result)
        {
            $connection->next_result();
            return false;
        }
        if ($anagrafica = $result->fetch_assoc())
        {
            $this->RealName = $anagrafica["nome"];
            $this->IdAnagrafica = (int)$anagrafica["id_anagrafica"];
            $this->IdStaff = (int)$anagrafica["staffista"];
        }
        $result->close();
        $connection->next_result();
        return $this->IdAnagrafica !== 0;
    }
    public function HasAdditionalData(): bool
    {
        return !empty($this->IdAnagrafica);
    }
    public function PutAdditionalInSession() : bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !$this->HasAdditionalData())
            return false;
        $_SESSION[self::$ID_ANAGRAFICA] = $this->IdAnagrafica;
        $_SESSION[self::$ID_STAFF] = $this->IdStaff;
        $_SESSION[self::$GIVEN_NAME] = $this->RealName;
        return true;
    }
    public function TimeLogged() : int
    {
        return time() - $this->LoginTime;
    }
    public function TimeLoggedMessage(): string
    {
        $diff = (int)($this->TimeLogged() / 60);
        if ($diff < 3) {
            return "adesso";
        }
        if ($diff < 120) {
            return "$diff minuti fa";
        }
        $diff = (int)($diff / 60);
        if ($diff < 24) {
            return "$diff ore fa";
        }
        $diff = (int)($diff / 24);
        if ($diff === 1) {
            return "ieri";
        }
        return "$diff giorni fa";
    }
    public function UpdateLogTs(): void
    {
        $this->LoginTime = time();
    }
    public function PutLogTsInSession() : bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
            return false;
        $_SESSION[self::$LOGIN_TIME] = $this->LoginTime;
        return $this->LoginTime !== 0;
    }
    public function UploadDbLog(\mysqli $connection): bool
    {
        if (!$connection || $this->SessionDbId === 0 || strlen(string: $this->SessionFlag) === 0)
            return false;
        $id = $this->SessionDbId;
        $query = "CALL UpdateSession($id)";
        $result = (bool)$connection->query(query: $query);
        $connection->next_result();
        return $result;
    }

    private function TestPassword(\mysqli $connection, string $password) : bool
    {
        if (!$connection || !isset($password) || empty($password))
            return false;
        $query = "CALL GetUserPasswordFromId($this->Id)";
        $result = $connection->query($query);
        if (!$result)
        {
            $connection->next_result();
            return false;
        }
        $test_ok = false;
        if ($row = $result->fetch_assoc())
        {
            $hash = $row["password"];
            $test_ok = Security::TestPassword($password, $hash);
        }
        $result->close();
        $connection->next_result();
        return $test_ok;
    }
    public function ForceSetNewPassword(\mysqli $connection, #[\SensitiveParameter] ?string $new_password): bool
    {
        if (!$connection || !isset($new_password) || empty($new_password))
            return false;
        $new_hash = Security::Hash(str: $new_password);
        $query = "CALL SetUserPassword($this->Id, '$new_hash')";
        $result = $connection->query(query: $query);
        $ret = (bool)$result;
        $connection->next_result();
        return $ret;
    }
    public function ChangePassword(
        \mysqli $connection, 
        #[\SensitiveParameter] ?string $password, 
        #[\SensitiveParameter] ?string $new_password,
    ): bool
    {
        if (!$connection)
            return false;
        if (!$this->TestPassword(connection: $connection, password: $password))
        {
            return false;
        }
        return self::ForceSetNewPassword(connection: $connection, new_password: $new_password);
    }
    public function ChangeUserName(
        \mysqli $connection, 
        #[\SensitiveParameter] string $password, 
        #[\SensitiveParameter] string $new_username,
    ): bool
    {
        if (!$connection || !isset($new_username) || empty($new_username))
            return false;
        if (!$this->TestPassword(connection: $connection, password: $password))
        {
            return false;
        }
        $query = "CALL SetUserName($this->Id, '" . $connection->real_escape_string(string: $new_username) . "')";
        $result = $connection->query(query: $query);
        $ret = false;
        if ($result)
        {
            if ($row = $result->fetch_assoc())
            {
                $ret = isset($row["result"]) && (int)$row["result"] !== 0;
            }
            $result->close();
        }
        $connection->next_result();
        if ($ret) {
            $this->Name = $new_username;
        }
        return $ret;
    }
    public static function Create(
        \mysqli $connection,
        string $username, 
        #[\SensitiveParameter] string $password, 
        bool $is_admin,
    ) : User|null
    {
        if (!$connection || !isset($username) || empty($username)|| !isset($password) || empty($password))
            return null;
        $query = "INSERT INTO `utenti` (`user_name`, `password`, `is_admin`) VALUES (?, ?, ?)";
        $admin = $is_admin ? 1 : 0;
        $hash = Security::Hash(str: $password);
        $stmt = $connection->prepare(query: $query);
        if (!$stmt)
            return null;
        if (!$stmt->bind_param("ssi", $username, $hash, $admin))
            return null;
        if (!$stmt->execute() || $stmt->affected_rows !== 1)
            return null;

        $query = 'SELECT LAST_INSERT_ID() AS "id"';
        $result = $connection->query(query: $query);

        if (!$result)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["id"]))
            {
                return new User(id: $row["id"], name: $username, login_time: 0, admin: $is_admin);
            }
        }
        return null;
    }
    public static function Ban(\mysqli $connection, string|int|null $id) : bool
    {
        if (!$connection || !isset($id))
            return false;
        $target = (int)$id;
        if ($target === 0)
            return false;
        $query = "CALL BanUser($target)";
        $result = (bool)$connection->query(query: $query);
        $connection->next_result();
        return $result;
    }
    public static function Restore(\mysqli $connection, string|int|null $id) : bool
    {
        if (!$connection || !isset($id))
            return false;
        $target = (int)$id;
        if ($target === 0)
            return false;
        $query = "CALL RestoreUser($target)";
        $result = (bool)$connection->query(query: $query);
        $connection->next_result();
        return $result;
    }
    public static function Delete(\mysqli $connection, int $target) : bool
    {
        if (!$connection || $target === 0)
            return false;
        try {
            
            $ret = (bool)$connection->query(query: "CALL DeleteUser($target)");
            $ret = $ret && $connection->affected_rows > 0;
            $connection->next_result();
            return $ret;
        } catch (\Exception $ex) {
            return false;
        }
    }
    public static function ResetPassword(\mysqli $connection, int $target = 0) : string
    {
        if (!$connection || $target === 0)
            return "";
        $new_password = Security::RandomPassword();
        $hashed = Security::Hash(str: $new_password);
        $result = $connection->query(query: "CALL SetUserPassword($target, '$hashed')");
        if (!$result)
        {
            $new_password = "";
        }
        $connection->next_result();
        if ($new_password != "")
        {
            $email = Email::GetByUserId($connection, $target);
            if (strlen($email) == 0)
                return $new_password;
            if (Email::Send($email, "Cambio password", 
                "Ciao, &egrave; appena stata cambiata la tua password.<br>\n" .
                "<span style=\"user-select: none;\">Da adesso per accedere utilizza: </span>" . 
                "<output style=\"font-family: monospace\">$new_password</output><br>\n" .
                "<hr>\n".
                "Ti preghiamo di non rispondere a questa email.<br>" . 
                "Buona giornata", 
                $connection, true))
                return "Inviata per email a <a href=\"mailto:$email\" class=\"link\">$email</a>";
            return "Errore durante invio mail";
        }
        return $new_password;
    }
    public function Label() : string
    {
        if (!empty($this->RealName))
            return $this->RealName;
        return $this->Name;
    }

    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;
        $result = $connection->query(query: "SELECT * FROM `utenti` WHERE `id` = $id");
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        $row = $result->fetch_assoc();
        return new self(
            id: $id, 
            name: $row["user_name"], 
            login_time: 0, 
            admin: $row["is_admin"] == 1,
            is_blocked: $row["is_blocked"] == 1,
        );
    }

    public static function ByName(\mysqli $connection, string $username): ?self
    {
        if (!$connection)
            return null;
        $result = $connection->query(query: 
            "SELECT * FROM `utenti` WHERE `user_name` = '" . $connection->real_escape_string(string: $username) . "'");
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        $row = $result->fetch_assoc();
        return new self(
            id: $row['id'],
            name: $row["user_name"], 
            login_time: 0, 
            admin: $row["is_admin"] == 1,
            is_blocked: $row["is_blocked"] == 1,
        );
    }

    public static function All(\mysqli $connection) : array
    {
        if (!$connection)
            return [];

        $query = "SELECT * FROM users_extended";
        $result = $connection->query(query: $query);
        if (!$result)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $user = new self(
                id: $row["id"],
                name: $row["user_name"],
                login_time: $row["last_seen_time"],
                admin: $row["is_admin"],
                is_blocked: $row["is_blocked"],
            );
            if (!empty($row["staff_id"])) {
                $user->IdStaff = (int)$row["staff_id"];
            }
            if (!empty($row["anagrafica_id"])) {
                $user->IdAnagrafica = (int)$row["anagrafica_id"];
            }
            if (!empty($row["full_name"])) {
                $user->RealName = $row["full_name"];
            }
            $arr[] = $user;
        }

        return $arr;
    }

    public static function Activity(\mysqli $connection) : array
    {
        if (!$connection)
            return [];
        $result = $connection->query(query: "SELECT * FROM `users_activity` LIMIT 500");
        
        $arr = [];
        if ($result) {
            while ($row = $result->fetch_assoc())
            {
                $arr[] = new UserActivity(
                    user_name: $row["user_name"],
                    time_start: $row["time_start"],
                    time_log: $row["time_log"],
                    flag: $row["user_flag"],
                    ip: $row["device_ip"],
                );
            }

            $result->close();
        }
        return $arr;
    }
    public function LoginList(\mysqli $connection) : array 
    {
        if (!$connection)
            return [];

        $result = $connection->query(query: "SELECT * FROM `sessioni` WHERE `user_id` = $this->Id");
        if (!$result) {
            return [];
        }
        
        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new UserActivity(
                user_name: $this->Name,
                time_start: $row['time_start'],
                time_log: $row['time_log'],
                flag: $row['user_flag'],
                ip: $row['device_ip']
            );
        }
        return $arr;
    }
}