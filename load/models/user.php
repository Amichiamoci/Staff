<?php

class User
{
    public static string $COOKIE_NAME = "user_id";
    public static string $USER_NAME = "user_name";
    public static string $USER_ID = "user_id";
    public static string $LOGIN_TIME = "login_time";
    public static string $USER_IS_ADMIN = "is_admin";
    public static User|null $Current = null;
    public int $id = 0;
    public string $name = "";
    public int $login_time = 0;
    public bool $is_admin = false;
    public function __construct(string|int $id, string $name, string|int|DateTime $login_time, bool $admin = false)
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
        return Cookie::DeleteIfItIs(User::$COOKIE_NAME, (string)$this->id);
    }
    private static function LogSessionStart(
        mysqli $connection,
        int $id,
        string $user_agent,
        string $user_ip
    ) : bool {
        $user_flag = sha1($user_agent . $user_ip);
        $query = "CALL StartSession($id, '$user_flag', '" . $connection->real_escape_string($user_ip) . "');";
        $result = $connection->query($query);
        if (!$result)
        {
            $connection->next_result();
            return false;
        }
        $ret = false;
        if ($row = $result->fetch_assoc())
        {
            $ret = isset($row["session_id"]) && (int)$row["session_id"] != 0;
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
            !isset($password) || empty($passwod))
            return false;
        if (!isset($user_agent)) $user_agent = "";
        if (!isset($user_ip)) $user_ip = "";

        $query = "CALL GetUserPassword('" . $connection->real_escape_string($username) . "');";
        $result = $connection->query($query);
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
            $passed = $id != 0 && Security::TestPassword($password, $hash);
        }
        $result->close();
        $connection->next_result();
        if (!$passed)
        {
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            if (!session_start())
                return false;
        }
        $_SESSION[User::$USER_ID] = $id;
        $_SESSION[User::$USER_NAME] = $username;
        $_SESSION[User::$LOGIN_TIME] = time();
        $_SESSION[User::$USER_IS_ADMIN] = $admin;
        return User::LogSessionStart($connection, $id, $user_agent, $user_ip);
    }

    public static function LoadFromSession() : User|null
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            return null;
        }
        if (!isset($_SESSION[User::$USER_ID]) || empty($_SESSION[User::$USER_ID]) ||
            !isset($_SESSION[User::$USER_NAME]) || empty($_SESSION[User::$USER_NAME]) ||
            !isset($_SESSION[User::$LOGIN_TIME]))
        {
            return null;
        }
        return new User(
            $_SESSION[User::$USER_ID], 
            $_SESSION[User::$USER_NAME], 
            $_SESSION[User::$LOGIN_TIME], 
            isset($_SESSION[User::$USER_IS_ADMIN]) && (bool)$_SESSION[User::$USER_IS_ADMIN]);
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
}