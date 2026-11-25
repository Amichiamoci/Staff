<?php
namespace Amichiamoci\Models;

use Richie314\SimpleMvc\Users\User as BaseUser;
use Richie314\SimpleMvc\Utils\Cookie;

use Amichiamoci\Utils\{Security, Link};
use Amichiamoci\Models\Templates\DbEntity;


class User
extends BaseUser
implements DbEntity
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

    
    public int $LoginTime = 0;
    public string $SessionFlag = "";
    public int $SessionDbId = 0;
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
        $this->Admin = $admin;

        if ($login_time !== null)
        {
            if ($login_time instanceof \DateTime) {
                $this->LoginTime = $login_time->getTimestamp();
            } elseif (is_string(value: $login_time)) {
                $this->LoginTime = (new \DateTime(datetime: $login_time))->getTimestamp();
            } else {
                $this->LoginTime = $login_time;
            }
        }
        $this->IsBanned = $is_blocked;
    }
    

    /** Ends the local login session and clears the session cookie. */
    public function Logout() : bool
    {
        $cookie = session_get_cookie_params();
        $_SESSION = [];
        $destroyed = session_destroy();
        $expired = Cookie::SetWithOptions(
            name: self::$COOKIE_NAME,
            value: '',
            exp: -3600,
            path: $cookie['path'],
            domain: $cookie['domain'],
            secure: $cookie['secure'],
            httpOnly: $cookie['httponly'],
            sameSite: $cookie['samesite'],
        );
        return $destroyed && $expired;
    }

    /** Starts the PHP session with the portal’s cookie and HTTPS settings. */
    private static function SessionStart() : bool
    {
        return session_start(options: [
            'name'            => self::$COOKIE_NAME,
            'cookie_lifetime' => '172800', // 48h
            'cookie_path'     => Link::InstallationPath() ?: '/',
            'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || filter_var(Security::LoadEnvironmentOrFromFile(var: 'SESSION_COOKIE_SECURE'), FILTER_VALIDATE_BOOL),
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    }

    /** Inserts the database session record and returns its ID, or zero on failure. */
    private static function LogSessionStart(
        \mysqli $connection,
        int $id,
        string $flag,
        string $user_ip
    ) : int
    {
        if (!$connection)
            return 0;

        $result = $connection->execute_query(
            query: 'CALL StartSession(?, ?, ?);',
            params: [
                $id,
                $flag,
                $user_ip,
            ],
        );

        if (!$result)
        {
            $connection->next_result();
            return 0;
        }

        $ret = 0;
        if ($row = $result->fetch_assoc())
            $ret = (int)$row["session_id"];

        $result->close();
        $connection->next_result();
        return $ret;
    }
    

    /**
     * Verifies the credentials of an active account and starts a new recorded session.
     * Writes authenticated session values only after the database session is created.
     */
    public static function Login(
        \mysqli $connection, 
        string $username,
        string $password,
        string $user_agent,
        string $user_ip,
    ) : bool
    {
        if (!$connection || 
            empty($username) || 
            empty($password)
        )
            return false;

        // Compare against a bound value so the column's collation controls the lookup.
        $result = $connection->execute_query(
            query: 'SELECT `id`, `password`, `is_admin`
                FROM `utenti`
                WHERE LOWER(`user_name`) = LOWER(?) AND `is_blocked` = 0',
            params: [trim($username)],
        );
        $row = $result->fetch_assoc();
        $result->close();

        $id = (int)($row['id'] ?? 0);
        $admin = (int)($row['is_admin'] ?? 0) === 1;
        $passed = $id !== 0 && Security::TestPassword(
            password: trim($password),
            hash: $row['password'],
        );

        if (!$passed)
            return false;

        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            if (!self::SessionStart())
                return false;
            
            // Session was not active but it is now
        }

        if (!session_regenerate_id(true))
            return false;

        $flag = sha1(string: $user_agent . $user_ip);
        $sessionId = self::LogSessionStart(connection: $connection, id: $id, flag: $flag, user_ip: $user_ip);
        if ($sessionId === 0) {
            return false;
        }

        $_SESSION[self::$USER_ID] = $id;
        $_SESSION[self::$USER_NAME] = $username;
        $_SESSION[self::$LOGIN_TIME] = time();
        $_SESSION[self::$USER_IS_ADMIN] = $admin;
        $_SESSION[self::$SESSION_FLAG] = $flag;
        $_SESSION[self::$SESSION_DB_ID] = $sessionId;

        return true;
    }

    /** Reconstructs the user from the existing PHP session, including cached staff data when present. */
    public static function LoadFromSession(): ?self
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !self::SessionStart())
            return null;

        if (!isset($_SESSION[self::$USER_ID]) || empty($_SESSION[self::$USER_ID]) ||
            !isset($_SESSION[self::$USER_NAME]) || empty($_SESSION[self::$USER_NAME]) ||
            !isset($_SESSION[self::$SESSION_FLAG]) || empty($_SESSION[self::$SESSION_FLAG]) ||
            !isset($_SESSION[self::$SESSION_DB_ID]) || $_SESSION[self::$SESSION_DB_ID] == 0 ||
            !isset($_SESSION[self::$LOGIN_TIME])
        )
            return null;

        $user = new self(
            id: $_SESSION[self::$USER_ID], 
            name: $_SESSION[self::$USER_NAME], 
            login_time: $_SESSION[self::$LOGIN_TIME], 
            admin: isset($_SESSION[self::$USER_IS_ADMIN]) && (bool)$_SESSION[self::$USER_IS_ADMIN],
        );
        $user->SessionDbId = (int)$_SESSION[self::$SESSION_DB_ID];
        $user->SessionFlag = $_SESSION[self::$SESSION_FLAG];

        // Load optional paramters from db, if present
        if (isset($_SESSION[self::$ID_ANAGRAFICA]) && $_SESSION[self::$ID_ANAGRAFICA] != 0)
            $user->IdAnagrafica = (int)$_SESSION[self::$ID_ANAGRAFICA];

        if (isset($_SESSION[self::$ID_STAFF]) && $_SESSION[self::$ID_STAFF] != 0)
            $user->IdStaff = (int)$_SESSION[self::$ID_STAFF];

        if (isset($_SESSION[self::$GIVEN_NAME]) && !empty($_SESSION[self::$GIVEN_NAME]))
            $user->RealName = $_SESSION[self::$GIVEN_NAME];

        return $user;
    }

    /** Loads the linked personal and staff IDs and the user’s display name from the database. */
    public function LoadAdditionalData(\mysqli $connection): bool
    {
        if (!$connection || $this->Id === 0)
            return false;

        $result = $connection->execute_query(
            query: 'CALL `GetStaffFromUserId`(?)',
            params: [
                $this->Id,
            ],
        );

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

    /** Checks whether the account has a known linked personal record. */
    public function HasAdditionalData(): bool
    {
        return !empty($this->IdAnagrafica);
    }

    /** Caches the linked staff and personal data in the active PHP session. */
    public function PutAdditionalInSession() : bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || 
            !$this->HasAdditionalData()
        )
            return false;

        $_SESSION[self::$ID_ANAGRAFICA] = $this->IdAnagrafica;
        $_SESSION[self::$ID_STAFF] = $this->IdStaff;
        $_SESSION[self::$GIVEN_NAME] = $this->RealName;

        return true;
    }
    

    /** Returns the elapsed time since login in seconds. */
    public function TimeLogged() : int
    {
        return time() - $this->LoginTime;
    }

    /** Formats the elapsed login time for display. */
    public function TimeLoggedMessage(): string
    {
        $diff = (int)($this->TimeLogged() / 60);
        if ($diff < 3)
            return "adesso";

        if ($diff < 120)
            return "$diff minuti fa";

        $diff = (int)($diff / 60);
        if ($diff < 24)
            return "$diff ore fa";

        $diff = (int)($diff / 24);
        if ($diff === 1)
            return "ieri";

        return "$diff giorni fa";
    }

    /** Updates the in-memory activity timestamp. */
    public function UpdateLogTs(): void
    {
        $this->LoginTime = time();
    }

    /** Copies the activity timestamp into the active PHP session. */
    public function PutLogTsInSession() : bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
            return false;

        $_SESSION[self::$LOGIN_TIME] = $this->LoginTime;
        return $this->LoginTime !== 0;
    }

    /** Refreshes the last activity time of the stored session. */
    public function UploadDbLog(\mysqli $connection): bool
    {
        if (!$connection || 
            $this->SessionDbId === 0 || 
            strlen(string: $this->SessionFlag) === 0
        )
            return false;

        $result = (bool)$connection->execute_query(
            query: 'CALL `UpdateSession`(?)',
            params: [ $this->SessionDbId ],
        );

        $connection->next_result();
        return $result;
    }

    /** Checks an account’s current password against the hash stored for its ID. */
    private function TestPassword(
        \mysqli $connection, 
        #[\SensitiveParameter] string $password,
    ) : bool
    {
        if (!$connection || empty($password))
            return false;

        $result = $connection->execute_query(
            query: 'CALL `GetUserPasswordFromId`(?)',
            params: [ $this->Id ],
        );

        if (!$result)
        {
            $connection->next_result();
            return false;
        }

        $test_ok = false;
        if ($row = $result->fetch_assoc())
        {
            $hash = $row["password"];
            $test_ok = Security::TestPassword(password: $password, hash: $hash);
        }

        $result->close();
        $connection->next_result();
        return $test_ok;
    }


    /** Hashes and stores a new account password without requiring the current password. */
    public function ForceSetNewPassword(
        \mysqli $connection, 
        #[\SensitiveParameter] ?string $new_password,
    ): bool {
        if (!$connection || !isset($new_password) || strlen(string: $new_password) < 6)
            return false;

        $new_hash = Security::Hash(str: $new_password);
        
        $result = $connection->execute_query(
            query: "CALL `SetUserPassword`(?, ?)",
            params:[$this->Id, $new_hash],
        );
        $ret = (bool)$result;

        $connection->next_result();
        return $ret;
    }

    /** Changes the account password after verifying the current password. */
    public function ChangePassword(
        \mysqli $connection, 
        #[\SensitiveParameter] ?string $password, 
        #[\SensitiveParameter] ?string $new_password,
    ): bool
    {
        if (!$connection)
            return false;

        if (!$this->TestPassword(connection: $connection, password: $password))
            return false;
        
        return $this->ForceSetNewPassword(connection: $connection, new_password: $new_password);
    }

    /** Updates the username after verifying the account password. */
    public function ChangeUserName(
        \mysqli $connection, 
        #[\SensitiveParameter] string $password, 
        #[\SensitiveParameter] string $new_username,
    ): bool
    {
        if (!$connection || empty($new_username))
            return false;

        if (!$this->TestPassword(connection: $connection, password: $password))
            return false;

        $result = $connection->execute_query(
            query: 'CALL `SetUserName`(?, ?)',
            params: [
                $this->Id,
                $new_username,
            ],
        );
        
        $ret = false;
        if ($result)
        {
            if ($row = $result->fetch_assoc())
                $ret = isset($row["result"]) && (int)$row["result"] !== 0;

            $result->close();
        }
        
        $connection->next_result();
        if ($ret) 
            $this->Name = $new_username;
        
        return $ret;
    }

    /** Creates a user with a hashed password and the requested administrator status. */
    public static function Create(
        \mysqli $connection,
        string $username, 
        #[\SensitiveParameter] string $password, 
        bool $is_admin,
    ): ?self
    {
        if (
            !$connection || 
            strlen(string: $username) === 0 || 
            strlen(string: $password) < 6
        ) {
            return null;
        }

        $hash = Security::Hash(str: $password);
        $result = $connection->execute_query(
            query: 'INSERT INTO `utenti` (`user_name`, `password`, `is_admin`)
                VALUES (?, ?, ?)',
            params: [
                $username,
                $hash,
                $is_admin ? '1' : '0',
            ],
        );

        if (!$result || $connection->affected_rows !== 1)
            return null;

        $id = (int)$connection->insert_id;
        if (empty($id))
            return null;

        return new self(
            id: $id, 
            name: $username, 
            login_time: 0, 
            admin: $is_admin,
        );
    }

    /** Blocks a user account. */
    public static function Ban(\mysqli $connection, string|int|null $id) : bool
    {
        if (!$connection || empty($id))
            return false;

        $result = (bool)$connection->execute_query(
            query: 'CALL `BanUser`(?)',
            params: [ $id ],
        );

        $connection->next_result();
        return $result;
    }

    /** Unblocks a user account. */
    public static function Restore(\mysqli $connection, string|int|null $id) : bool
    {
        if (!$connection || empty($id))
            return false;

        $result = (bool)$connection->execute_query(
            query: 'CALL `RestoreUser`(?)',
            params: [ $id ],
        );

        $connection->next_result();
        return $result;
    }

    /** Deletes a user through the database routine. */
    public static function Delete(\mysqli $connection, int $target) : bool
    {
        if (!$connection || $target === 0)
            return false;

        try {
            $ret = (bool)$connection->query(query: "CALL `DeleteUser`($target)");
            $ret = $ret && $connection->affected_rows > 0;
            $connection->next_result();
            return $ret;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Uses the linked person’s name when available and otherwise returns the username. */
    public function Label() : string
    {
        if (!empty($this->RealName))
            return $this->RealName;
        
        return $this->Name;
    }

    /** Looks up an account by its ID. */
    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;

        $result = $connection->query(query: "SELECT *
            FROM `utenti`
            WHERE `id` = $id");
        if (!$result || $result->num_rows === 0)
            return null;

        $row = $result->fetch_assoc();
        return new self(
            id: $id, 
            name: $row["user_name"], 
            login_time: 0, 
            admin: $row["is_admin"] == 1,
            is_blocked: $row["is_blocked"] == 1,
        );
    }

    /** Looks up an account by its username. */
    public static function ByName(\mysqli $connection, string $username): ?self
    {
        if (!$connection)
            return null;

        $result = $connection->execute_query(
            query: "SELECT *
                FROM `utenti`
                WHERE `user_name` = ?",
            params: [ $username ]
        );
        if (!$result || $result->num_rows === 0)
            return null;

        $row = $result->fetch_assoc();
        return new self(
            id: $row['id'],
            name: $row["user_name"], 
            login_time: 0, 
            admin: $row["is_admin"] == 1,
            is_blocked: $row["is_blocked"] == 1,
        );
    }

    /** Lists user accounts along with the linked staff information available in the expanded view. */
    public static function All(\mysqli $connection) : array
    {
        if (!$connection)
            return [];

        $query = "SELECT * FROM `users_extended`";
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

            if (!empty($row["staff_id"])) 
                $user->IdStaff = (int)$row["staff_id"];

            if (!empty($row["anagrafica_id"]))
                $user->IdAnagrafica = (int)$row["anagrafica_id"];

            if (!empty($row["full_name"])) 
                $user->RealName = $row["full_name"];

            $arr[] = $user;
        }

        return $arr;
    }

    /** Returns the 500 latest login sessions for the administrator activity page. */
    public static function Activity(\mysqli $connection) : array
    {
        if (!$connection)
            return [];

        $result = $connection->query(
            'SELECT `user_id`, `user_name`, `time_start`, `time_log`, `user_flag`, `device_ip`
             FROM `user_sessions`
             ORDER BY `time_start` DESC, `id` DESC
             LIMIT 500'
        );
        if (!$result)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new UserActivity(
                user_name: $row["user_name"],
                time_start: $row["time_start"],
                time_log: $row["time_log"],
                flag: $row["user_flag"],
                ip: $row["device_ip"],
                user_id: (int)$row["user_id"],
            );
        }

        $result->close();
        return $arr;
    }
    

    /** Lists this account’s sessions with the most recent login first. */
    public function LoginList(\mysqli $connection) : array 
    {
        if (!$connection)
            return [];

        $result = $connection->execute_query(
            query: "SELECT *
                FROM `sessioni`
                WHERE `user_id` = ?
                ORDER BY `time_start` DESC, `id` DESC",
            params: [$this->Id],
        );
        if (!$result)
            return [];
        
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