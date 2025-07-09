<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Security.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Create user with validation
    public function createUser($name, $email, $password, $userType = 'customer') {
        // Sanitize inputs
        $name = Security::sanitizeInput($name);
        $email = Security::sanitizeInput($email);
        $userType = Security::sanitizeInput($userType);
        
        // Validate email
        if (!Security::validateEmail($email)) {
            throw new Exception("Invalid email format");
        }
        
        // Validate password strength
        if (!Security::validatePassword($password)) {
            throw new Exception("Password must be at least 8 characters with uppercase, lowercase, and number");
        }
        
        // Check if email already exists
        if ($this->getUserByEmail($email)) {
            throw new Exception("Email already exists");
        }
        
        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        
        // Use prepared statement (already safe from SQL injection)
        $sql = "INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)";
        $result = $this->db->execute($sql, [$name, $email, $hashedPassword, $userType]);
        
        if ($result) {
            return $this->getUserByEmail($email);
        }
        throw new Exception("Failed to create user");
    }
    
    // Secure login with rate limiting
    public function verifyLogin($email, $password) {
        $email = Security::sanitizeInput($email);
        
        // Check rate limit
        if (!Security::checkRateLimit('login_' . $email)) {
            throw new Exception("Too many login attempts. Please try again later.");
        }
        
        // Use prepared statement to prevent SQL injection
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $user = $this->db->fetch($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password from returned data
            unset($user['password']);
            return $user;
        }
        
        throw new Exception("Invalid credentials");
    }
    
    // Other methods remain the same...
    public function getAllUsers() {
        $sql = "SELECT id, name, email, user_type, created_at FROM users";
        return $this->db->fetchAll($sql);
    }
    
    public function getUserById($id) {
        $sql = "SELECT id, name, email, user_type, created_at FROM users WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }
}
?>