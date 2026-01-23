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
                $this->connection = new mysqli(
                    DB_HOST,
                    DB_USER,
                    DB_PASS,
                    DB_NAME
                );
                
                if ($this->connection->connect_error) {
                    throw new Exception("Database connection failed: " . $this->connection->connect_error);
                }
                
                $this->connection->set_charset(DB_CHARSET);
            } catch (Exception $e) {
                throw new Exception("Database Error: " . $e->getMessage());
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

