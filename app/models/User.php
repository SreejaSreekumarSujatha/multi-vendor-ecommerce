<?php
// Include our database and base model
require_once __DIR__ . '/../../core/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all users
    public function getAllUsers() {
        $sql = "SELECT id, name, email, user_type, created_at FROM users";
        return $this->db->fetchAll($sql);
    }
    
    // Get user by ID
    public function getUserById($id) {
        $sql = "SELECT id, name, email, user_type, created_at FROM users WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    // Get user by email
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }
    
    // Create new user
    public function createUser($name, $email, $password, $userType = 'customer') {
        // Check if email already exists
        if ($this->getUserByEmail($email)) {
            return false; // Email already exists
        }
        
        // Hash password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)";
        $result = $this->db->execute($sql, [$name, $email, $hashedPassword, $userType]);
        
        if ($result) {
            return $this->getUserByEmail($email);
        }
        return false;
    }
    
    // Update user
    public function updateUser($id, $name, $email) {
        $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        return $this->db->execute($sql, [$name, $email, $id]);
    }
    
    // Delete user
    public function deleteUser($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    // Verify user login
    public function verifyLogin($email, $password) {
        $user = $this->getUserByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password from returned data
            unset($user['password']);
            return $user;
        }
        return false;
    }
}
?>