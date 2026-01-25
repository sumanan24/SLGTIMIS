<?php
/**
 * Database Connection Class
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Don't connect immediately - connect lazily when needed
    }
    
    private function connect() {
        if ($this->connection === null) {
            try {
                // Set connection timeout for nginx servers
                ini_set('default_socket_timeout', 10);
                
                $this->connection = @new mysqli(
                    DB_HOST,
                    DB_USER,
                    DB_PASS,
                    DB_NAME
                );
                
                if ($this->connection->connect_error) {
                    $errorMsg = "Database connection failed: " . $this->connection->connect_error;
                    $errorCode = $this->connection->connect_errno;
                    error_log("Database::connect - {$errorMsg} [Code: {$errorCode}]");
                    error_log("Database::connect - Host: " . DB_HOST . ", User: " . DB_USER . ", DB: " . DB_NAME);
                    throw new Exception($errorMsg);
                }
                
                // Set connection options for better compatibility
                $this->connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
                $this->connection->options(MYSQLI_OPT_READ_TIMEOUT, 10);
                
                if (!$this->connection->set_charset(DB_CHARSET)) {
                    error_log("Database::connect - Failed to set charset: " . $this->connection->error);
                }
                
                error_log("Database::connect - Connection successful to " . DB_NAME);
            } catch (Exception $e) {
                error_log("Database::connect - Exception: " . $e->getMessage());
                throw new Exception("Database Error: " . $e->getMessage());
            } catch (Error $e) {
                error_log("Database::connect - Fatal Error: " . $e->getMessage());
                throw new Exception("Database Fatal Error: " . $e->getMessage());
            }
        }
        return $this->connection;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connect();
    }
    
    public function query($sql) {
        return $this->connect()->query($sql);
    }
    
    public function prepare($sql) {
        return $this->connect()->prepare($sql);
    }
    
    public function escape($string) {
        return $this->connect()->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->connect()->insert_id;
    }
    
    public function affectedRows() {
        return $this->connect()->affected_rows;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

