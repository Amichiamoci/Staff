<?php

class User
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
    public static User|null $Current = null;
    public int $id = 0;
    public string $name = "";
    public int $login_time = 0;
    public string $session_flag = "";
    public int $session_db_id = 0;
    public bool $is_admin = false;

    // Additional data
    public string|null $nome_vero = null;
    public int $staff_id = 0;
    public int $anagrafica_id = 0;


    public function __construct(
        string|int $id, 
        string $name, 
        string|int|DateTime $login_time, 
         bool $admin = false)
    {
        $this->id = (int)$id;
        $this->name = $name;
        if ($login_time instanceof DateTime) {
            $this->login_time = $login_time->getTimestamp();
        } else {
            $this->login_time = (int)$login_time;
        }
        $this->is_admin = $admin;
    }
    public function Logout() : bool
    {
        session_unset();
        session_destroy();
        return Cookie::DeleteIfItIs(self::$COOKIE_NAME, (string)$this->id);
    }
    private static function SessionStart() : bool
    {
        return session_start([
            'name' => self::$COOKIE_NAME,
            'cookie_lifetime' => '172800', // 48h
            'cookie_domain' => DOMAIN,
            'cookie_path' => ADMIN_PATH,
            'cookie_httponly' => '1'
        ]);
    }
    private static function LogSessionStart(
        mysqli $connection,
        int $id,
        string $flag,
        string $user_ip
    ) : int {
        $query = "CALL StartSession($id, '$flag', '" . $connection->real_escape_string($user_ip) . "');";
        $result = $connection->query($query);
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
        mysqli $connection, 
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

        $query = "CALL GetUserPassword('" . $connection->real_escape_string($username) . "');";
        $result = $connection->query($query);
        if (!$result)
        {
            $connection->next_result();
            //echo "Bad result";
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
            $passed = $id !== 0 && Security::TestPassword($password, $hash);
        }
        $result->close();
        $connection->next_result();
        if (!$passed)
        {
            echo "Password verify";
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
        $flag = sha1($user_agent . $user_ip);
        $_SESSION[self::$USER_ID] = $id;
        $_SESSION[self::$USER_NAME] = $username;
        $_SESSION[self::$LOGIN_TIME] = time();
        $_SESSION[self::$USER_IS_ADMIN] = $admin;
        $_SESSION[self::$SESSION_FLAG] = $flag;
        $_SESSION[self::$SESSION_DB_ID] = self::LogSessionStart($connection, $id, $flag, $user_ip);
        /*
        if ($_SESSION[self::$SESSION_DB_ID] === 0)
        {
            echo "Session id appears to be 0";
        }
        */
        return $_SESSION[self::$SESSION_DB_ID] !== 0;
    }

    public static function LoadFromSession() : User|null
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
        $user = new User(
            $_SESSION[self::$USER_ID], 
            $_SESSION[self::$USER_NAME], 
            $_SESSION[self::$LOGIN_TIME], 
            isset($_SESSION[self::$USER_IS_ADMIN]) && (bool)$_SESSION[self::$USER_IS_ADMIN]);
        $user->session_db_id = (int)$_SESSION[self::$SESSION_DB_ID];
        $user->session_flag = $_SESSION[self::$SESSION_FLAG];

        // Load optional paramters from db, if present
        if (isset($_SESSION[self::$ID_ANAGRAFICA]) && $_SESSION[self::$ID_ANAGRAFICA] != 0)
        {
            $user->anagrafica_id = (int)$_SESSION[self::$ID_ANAGRAFICA];
        }
        if (isset($_SESSION[self::$ID_STAFF]) && $_SESSION[self::$ID_STAFF] != 0)
        {
            $user->staff_id = (int)$_SESSION[self::$ID_STAFF];
        }
        if (isset($_SESSION[self::$GIVEN_NAME]) && !empty($_SESSION[self::$GIVEN_NAME]))
        {
            $user->nome_vero = $_SESSION[self::$GIVEN_NAME];
        }

        return $user;
    }
    public function LoadAdditionalData(mysqli $connection) : bool
    {
        if (!$connection || $this->id === 0)
            return false;
        $get_anagrafica_query = "CALL GetStaffFromUserId($this->id)";
        $result = $connection->query($get_anagrafica_query);
        if (!$result)
        {
            $connection->next_result();
            return false;
        }
        if ($anagrafica = $result->fetch_assoc())
        {
            $this->nome_vero = $anagrafica["nome"];
            $this->anagrafica_id = (int)$anagrafica["id_anagrafica"];
            $this->staff_id = (int)$anagrafica["staffista"];
        }
        $result->close();
        $connection->next_result();
        return $this->anagrafica_id !== 0;
    }
    public function HasAdditionalData(): bool
    {
        return $this->anagrafica_id !== 0;
    }
    public function PutAdditionalInSession() : bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !$this->HasAdditionalData())
            return false;
        $_SESSION[self::$ID_ANAGRAFICA] = $this->anagrafica_id;
        $_SESSION[self::$ID_STAFF] = $this->staff_id;
        $_SESSION[self::$GIVEN_NAME] = $this->nome_vero;
        return true;
    }
    public function TimeLogged() : int
    {
        return time() - $this->login_time;
    }
    public function UpdateLogTs()
    {
        $this->login_time = time();
    }
    public function PutLogTsInSession() : bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
            return false;
        $_SESSION[self::$LOGIN_TIME] = $this->login_time;
        return $this->login_time !== 0;
    }
    public function UploadDbLog(mysqli $connection) : bool
    {
        if (!$connection || empty($this->session_flag) || $this->session_db_id === 0)
            return false;
        $query = "CALL UpdateSession($this->session_db_id)";
        $result = (bool)$connection->query($query);
        $connection->next_result();
        return $result;
    }

    private function TestPassword(mysqli $connection, string $password) : bool
    {
        if (!$connection || !isset($password) || empty($password))
            return false;
        $query = "CALL GetUserPasswordFromId($this->id)";
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
    public function ChangePassword(mysqli $connection, string|null $password, string|null $new_password) : bool
    {
        if (!$connection || !isset($new_password) || empty($new_password))
            return false;
        if (!$this->TestPassword($connection, $password))
        {
            return false;
        }
        $new_hash = Security::Hash($new_password);
        $query = "CALL SetUserPassword($this->id, '$new_hash')";
        $result = $connection->query($query);
        $ret = (bool)$result;
        $connection->next_result();
        return $ret;
    }
    public function ChangeUserName(mysqli $connection, string $password, string $new_username) : bool
    {
        if (!$connection || !isset($new_username) || empty($new_username))
            return false;
        if (!$this->TestPassword($connection, $password))
        {
            return false;
        }
        $query = "CALL SetUserName($this->id, '" . $connection->real_escape_string($new_username) . "')";
        $result = $connection->query($query);
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
        return $ret;
    }
    public static function Create(
        mysqli $connection,
        string $username, 
        string $password, 
        bool $is_admin
    ) : User|null
    {
        if (!$connection || !isset($username) || empty($username)|| !isset($password) || empty($password))
            return null;
        $query = "INSERT INTO `utenti` (`user_name`, `password`, `is_admin`) VALUES (?, ?, ?)";
        $admin = $is_admin ? 1 : 0;
        $stmt = $connection->prepare($query);
        if (!$stmt)
            return null;
        if (!$stmt->bind_param("ssi", $username, $password, $admin))
            return null;
        if (!$stmt->execute() || $stmt->affected_rows !== 1)
            return null;

        $query = 'SELECT LAST_INSERT_ID() AS "id" ';
        $result = $connection->query($query);

        if (!$result)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["id"]))
            {
                return new User($row["id"], $username, 0, $is_admin);
            }
        }
        return null;
    }
    public static function Ban(mysqli $connection, string|int|null $id) : bool
    {
        if (!$connection || !isset($id))
            return false;
        $target = (int)$id;
        if ($target === 0)
            return false;
        $query = "CALL BanUser($target)";
        $result = (bool)$connection->query($query);
        $connection->next_result();
        return $result;
    }
    public static function Restore(mysqli $connection, string|int|null $id) : bool
    {
        if (!$connection || !isset($id))
            return false;
        $target = (int)$id;
        if ($target === 0)
            return false;
        $query = "CALL RestoreUser($target)";
        $result = (bool)$connection->query($query);
        $connection->next_result();
        return $result;
    }
    public static function Delete(mysqli $connection, int $target) : bool
    {
        if (!$connection || $target === 0)
            return false;
        try {
            
            $ret = (bool)$connection->query("CALL DeleteUser($target)");
            $ret = $ret && $connection->affected_rows > 0;
            $connection->next_result();
            return $ret;
        } catch (Exception $ex) {
            return false;
        }
    }
    public static function ResetPassword(mysqli $connection, int $target = 0) : string
    {
        if (!$connection || $target === 0)
            return "";
        $new_password = Security::RandomPassword();
        $hashed = Security::Hash($new_password);
        $result = $connection->query("CALL SetUserPassword($target, '$hashed')");
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
    public function label() : string
    {
        if (!empty($this->nome_vero))
            return $this->nome_vero;
        return $this->name;
    }
}