<?php
class Database {
    private static $instance = null;
    private $connection;
    
    // Constructor (private - only this class can create instance)
    private function __construct() {
        $this->connect();
    }
    
    // Get single instance (Singleton pattern)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Connect to database
    private function connect() {
        $config = require_once __DIR__ . '/../config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $this->connection = new PDO($dsn, $config['username'], $config['password']);
            
            // Set error mode to exception
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "Database connected successfully!<br>";
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    // Get database connection
    public function getConnection() {
        return $this->connection;
    }
    
    // Execute query and get all results
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
    
    // Execute query and get one result
    public function fetch($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
    
    // Execute query (insert, update, delete)
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }

    public function lastInsertId() {
    return $this->connection->lastInsertId();
    }
}
?>