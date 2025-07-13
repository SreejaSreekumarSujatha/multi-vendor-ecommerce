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

    // Add these methods to User.php

public function updateProfile($userId, $data) {
    try {
        // Sanitize inputs
        $name = Security::sanitizeInput($data['name']);
        $email = Security::sanitizeInput($data['email']);
        
        // Validate email
        if (!Security::validateEmail($email)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email is taken by another user
        $existingUser = $this->getUserByEmail($email);
        if ($existingUser && $existingUser['id'] != $userId) {
            throw new Exception("Email already exists");
        }
        
        $sql = "UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$name, $email, $userId]);
        
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        throw $e;
    }
}

public function updatePassword($userId, $currentPassword, $newPassword) {
    try {
        // Get user with password
        $sql = "SELECT password FROM users WHERE id = ? AND is_active = 1";
        $user = $this->db->fetch($sql, [$userId]);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }
        
        // Validate new password
        if (!Security::validatePassword($newPassword)) {
            throw new Exception("New password does not meet requirements");
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        
        return $this->db->execute($sql, [$hashedPassword, $userId]);
        
    } catch (Exception $e) {
        error_log("Password update error: " . $e->getMessage());
        throw $e;
    }
}
}
?>