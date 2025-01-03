<?php
class Database
{
    private static $pdo, $db_connected, $host, $user, $password, $name, $charset;

    public static function authenticate($host, $user, $password, $name, $charset = "utf8"): void
    {
        self::$host = $host;
        self::$user = $user;
        self::$password = $password;
        self::$name = $name;
        self::$charset = $charset;
    }

    public static function connect(): void
    {
        try
        {
            self::$pdo = new PDO("mysql:host=" . self::$host . ";dbname=" . self::$name . ";charset=" . self::$charset, self::$user, self::$password);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$db_connected = true;
        }
        catch(PDOException $e)
        {
            self::$db_connected = false;

            if(ECS_DEBUG)
            {
                die($e->getMessage());
            }
        }
    }

    // New database query

    public static function run($sql, $args = NULL)
    {
        if(ECS_DEBUG)
        {
            if(!self::$db_connected)
            {
                die("Cannot connect to the database. Excuse us for the inconvenience. Please try again later.");
            }
        }

        try
        {
            if(!$args)
            {
                return self::$pdo->query($sql);
            }

            $stmt = self::$pdo->prepare($sql);

            // In order to keep high performance, we should always pass $args as array, even if we only need 1 arg
            // Usage:
            // Database::run("SQL QUERY with :args", [":arg1" => $var1, ":arg2" => $var2]);
            // OR
            // Database::run("SQL QUERY with ? and ?", [$var1, $var2]);

            $stmt->execute($args);

            return $stmt;
        }
        catch(PDOException $e)
        {
            Debug::add($e->getMessage());

            if(ECS_DEBUG)
            {
                return "An error occured while processing the SQL request. " . $e->getMessage();
            }

            return "An error occured while processing the SQL request.";
        }
    }

    // Disconnect from database

    public static function disconnect(): void
    {
        self::$pdo = null;
        self::$db_connected = false;
    }
}
?>