<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../core/Security.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
// Show login form - FIXED VERSION
public function showLogin() {
    // If already logged in, redirect to dashboard
    if ($this->isLoggedIn()) {
        $this->redirect('dashboard');
        return;
    }
    
    $csrfToken = Security::generateCSRFToken();
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="auth-container">';
    echo '<h1>Login</h1>';
    
    // Show error message if exists
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    
    // Show success message if exists
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    
    echo '<form method="POST" action="?action=login" class="auth-form">';
    echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
    
    echo '<div class="form-group">';
    echo '<label for="email">Email:</label>';
    echo '<input type="email" id="email" name="email" required placeholder="Enter your email">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="password">Password:</label>';
    echo '<input type="password" id="password" name="password" required placeholder="Enter your password">';
    echo '</div>';
    
    echo '<div class="form-group">';
    // CHANGED: Using button instead of input type="submit"
    echo '<button type="submit" class="btn btn-primary btn-large">LOGIN</button>';
    echo '</div>';
    
    echo '</form>';
    
    echo '<div class="auth-links">';
    echo '<p>Don\'t have an account? <a href="?action=show-register" class="auth-link">Register here</a></p>';
    echo '</div>';
    echo '</div>';
}
    
    // Handle login
    public function login() {
        if ($_POST) {
            try {
                // Verify CSRF token
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                $email = Security::sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                
                // Verify credentials
                $user = $this->userModel->verifyLogin($email, $password);
                
                if ($user) {
                    // Store user in session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['is_logged_in'] = true;
                    
                    // Redirect based on user type
                    switch ($user['user_type']) {
                        case 'admin':
                            $this->redirect('admin-dashboard');
                            break;
                        case 'vendor':
                            $this->redirect('vendor-dashboard');
                            break;
                        default:
                            $this->redirect('dashboard');
                            break;
                    }
                } else {
                    $_SESSION['error'] = 'Invalid email or password';
                    $this->redirect('show-login');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect('show-login');
            }
        }
    }
    
  // Show registration form - FIXED VERSION
public function showRegister() {
    // If already logged in, redirect to dashboard
    if ($this->isLoggedIn()) {
        $this->redirect('dashboard');
        return;
   }
   
   $csrfToken = Security::generateCSRFToken();
   
   echo '<link rel="stylesheet" href="css/style.css">';
   echo '<div class="auth-container">';
   echo '<h1>Register</h1>';
   
   // Show error message if exists
   if (isset($_SESSION['error'])) {
       echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
       unset($_SESSION['error']);
   }
   
   // Show success message if exists
   if (isset($_SESSION['success'])) {
       echo '<div class="success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
       unset($_SESSION['success']);
   }
   
   echo '<form method="POST" action="?action=register" class="auth-form">';
   echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
   
   echo '<div class="form-group">';
   echo '<label for="name">Full Name:</label>';
   echo '<input type="text" id="name" name="name" required placeholder="Enter your full name">';
   echo '</div>';
   
   echo '<div class="form-group">';
   echo '<label for="email">Email:</label>';
   echo '<input type="email" id="email" name="email" required placeholder="Enter your email">';
   echo '</div>';
   
   echo '<div class="form-group">';
   echo '<label for="password">Password:</label>';
   echo '<input type="password" id="password" name="password" required placeholder="Enter your password">';
   echo '<small>Must be at least 8 characters with uppercase, lowercase, and number</small>';
   echo '</div>';
   
   echo '<div class="form-group">';
   echo '<label for="confirm_password">Confirm Password:</label>';
   echo '<input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">';
   echo '</div>';
   
   echo '<div class="form-group">';
   echo '<label for="user_type">I want to:</label>';
   echo '<select id="user_type" name="user_type" required>';
   echo '<option value="">Select an option</option>';
   echo '<option value="customer">Buy Products (Customer)</option>';
   echo '<option value="vendor">Sell Products (Vendor)</option>';
   echo '</select>';
   echo '</div>';
   
   echo '<div class="form-group">';
   // CHANGED: Using button instead of input type="submit"
   echo '<button type="submit" class="btn btn-primary btn-large">REGISTER</button>';
   echo '</div>';
   
   echo '</form>';
   
   echo '<div class="auth-links">';
   echo '<p>Already have an account? <a href="?action=show-login" class="auth-link">Login here</a></p>';
   echo '</div>';
   echo '</div>';
}
    
    // Handle registration
    public function register() {
        if ($_POST) {
            try {
                // Verify CSRF token
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                $name = Security::sanitizeInput($_POST['name']);
                $email = Security::sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];
                $userType = Security::sanitizeInput($_POST['user_type']);
                
                // Validate passwords match
                if ($password !== $confirmPassword) {
                    throw new Exception("Passwords do not match");
                }
                
                // Create user
                $user = $this->userModel->createUser($name, $email, $password, $userType);
                
                if ($user) {
                    $_SESSION['success'] = 'Registration successful! Please login.';
                    $this->redirect('show-login');
                } else {
                    throw new Exception("Registration failed. Please try again.");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect('show-register');
            }
        }
    }
    
    // Logout
    public function logout() {
        $this->startSession();
        
        // Clear all session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        // Redirect to home
        $this->redirect('show-login');
    }
    
    // Dashboard (after login)
    public function dashboard() {
        if (!$this->isLoggedIn()) {
            $this->redirect('show-login');
            return;
        }
        
        $userType = $_SESSION['user_type'];
        $userName = $_SESSION['user_name'];
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="dashboard-container">';
        echo '<div class="dashboard-header">';
        echo '<h1>Welcome, ' . htmlspecialchars($userName) . '!</h1>';
        echo '<div class="user-info">';
        echo '<span>Logged in as: ' . ucfirst($userType) . '</span>';
        echo '<a href="?action=logout" class="btn btn-secondary">Logout</a>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="dashboard-content">';
        
        // Show different content based on user type
        switch ($userType) {
            case 'admin':
                $this->showAdminDashboard();
                break;
            case 'vendor':
                $this->showVendorDashboard();
                break;
            case 'customer':
                $this->showCustomerDashboard();
                break;
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // Admin Dashboard
    private function showAdminDashboard() {
        echo '<h2>Admin Dashboard</h2>';
        echo '<div class="dashboard-grid">';
        echo '<div class="dashboard-card">';
        echo '<h3>User Management</h3>';
        echo '<p>Manage all users in the system</p>';
        echo '<a href="?action=users" class="btn btn-primary">Manage Users</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>Product Management</h3>';
        echo '<p>Manage all products and categories</p>';
        echo '<a href="?action=products" class="btn btn-primary">Manage Products</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>Orders</h3>';
        echo '<p>View and manage all orders</p>';
        echo '<a href="?action=orders" class="btn btn-primary">View Orders</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>Analytics</h3>';
        echo '<p>View sales and performance reports</p>';
        echo '<a href="?action=analytics" class="btn btn-primary">View Analytics</a>';
        echo '</div>';
        echo '</div>';
    }
    
    // Vendor Dashboard
    private function showVendorDashboard() {
        echo '<h2>Vendor Dashboard</h2>';
        echo '<div class="dashboard-grid">';
        echo '<div class="dashboard-card">';
        echo '<h3>My Products</h3>';
        echo '<p>Manage your product listings</p>';
        echo '<a href="?action=my-products" class="btn btn-primary">My Products</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>Add New Product</h3>';
        echo '<p>List a new product for sale</p>';
        echo '<a href="?action=add-product" class="btn btn-primary">Add Product</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>My Orders</h3>';
        echo '<p>View orders for your products</p>';
        echo '<a href="?action=my-orders" class="btn btn-primary">My Orders</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>Earnings</h3>';
        echo '<p>Track your sales and commissions</p>';
        echo '<a href="?action=earnings" class="btn btn-primary">View Earnings</a>';
        echo '</div>';
        echo '</div>';
    }
    
    // Customer Dashboard
    private function showCustomerDashboard() {
        echo '<h2>Customer Dashboard</h2>';
        echo '<div class="dashboard-grid">';
        echo '<div class="dashboard-card">';
        echo '<h3>Browse Products</h3>';
        echo '<p>Explore our product catalog</p>';
        echo '<a href="?action=shop" class="btn btn-primary">Shop Now</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>My Orders</h3>';
        echo '<p>View your order history</p>';
        echo '<a href="?action=my-orders" class="btn btn-primary">My Orders</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>Shopping Cart</h3>';
        echo '<p>Review items in your cart</p>';
        echo '<a href="?action=cart" class="btn btn-primary">View Cart</a>';
        echo '</div>';
        
        echo '<div class="dashboard-card">';
        echo '<h3>Account Settings</h3>';
        echo '<p>Update your profile information</p>';
       echo '<a href="?action=edit-profile" class="btn btn-primary">Edit Profile</a>';
        echo '</div>';
        echo '</div>';
    }
    
    // Helper methods
    private function isLoggedIn() {
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }
    
    private function redirect($action) {
        header("Location: ?action=$action");
        exit;
    }

    // Add these methods to AuthController.php

// Show profile edit form
public function editProfile() {
    $this->requireAuth();
    
    $userId = $_SESSION['user_id'];
    
    // Get current user data
    $user = $this->userModel->getUserById($userId);
    if (!$user) {
        $_SESSION['error'] = 'User not found';
        header('Location: ?action=dashboard');
        exit;
    }
    
    $csrfToken = Security::generateCSRFToken();
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="profile-container">';
    
    // Show messages
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    // Header
    echo '<div class="profile-header">';
    echo '<h1>Edit Profile</h1>';
    echo '<a href="?action=dashboard" class="btn btn-secondary">← Back to Dashboard</a>';
    echo '</div>';
    
    // Profile Form
    echo '<div class="profile-form-container">';
    echo '<form method="POST" action="?action=update-profile" class="profile-form">';
    echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
    
    echo '<div class="form-section">';
    echo '<h2>Personal Information</h2>';
    
    echo '<div class="form-group">';
    echo '<label for="name">Full Name:</label>';
    echo '<input type="text" id="name" name="name" required value="' . htmlspecialchars($user['name']) . '">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="email">Email Address:</label>';
    echo '<input type="email" id="email" name="email" required value="' . htmlspecialchars($user['email']) . '">';
    echo '<small>This will be your login username</small>';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="user_type">Account Type:</label>';
    echo '<select id="user_type" name="user_type" disabled>';
    echo '<option value="customer"' . ($user['user_type'] === 'customer' ? ' selected' : '') . '>Customer</option>';
    echo '<option value="vendor"' . ($user['user_type'] === 'vendor' ? ' selected' : '') . '>Vendor</option>';
    echo '<option value="admin"' . ($user['user_type'] === 'admin' ? ' selected' : '') . '>Admin</option>';
    echo '</select>';
    echo '<small>Account type cannot be changed. Contact support if needed.</small>';
    echo '</div>';
    
    echo '</div>';
    
    echo '<div class="form-actions">';
    echo '<button type="submit" class="btn btn-primary btn-large">Update Profile</button>';
    echo '<a href="?action=change-password" class="btn btn-secondary">Change Password</a>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
    
    echo '</div>';
}

// Update profile
public function updateProfile() {
    $this->requireAuth();
    
    if ($_POST) {
        try {
            // Verify CSRF token
            if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception("Invalid CSRF token");
            }
            
            $userId = $_SESSION['user_id'];
            
            $data = [
                'name' => Security::sanitizeInput($_POST['name']),
                'email' => Security::sanitizeInput($_POST['email'])
            ];
            
            // Update profile
            if ($this->userModel->updateProfile($userId, $data)) {
                // Update session data
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['user_email'] = $data['email'];
                
                $_SESSION['success'] = 'Profile updated successfully!';
            } else {
                throw new Exception("Failed to update profile");
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: ?action=edit-profile');
        exit;
    }
}

// Show change password form
public function changePassword() {
    $this->requireAuth();
    
    $csrfToken = Security::generateCSRFToken();
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="password-container">';
    
    // Show messages
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    // Header
    echo '<div class="password-header">';
    echo '<h1>Change Password</h1>';
    echo '<a href="?action=edit-profile" class="btn btn-secondary">← Back to Profile</a>';
    echo '</div>';
    
    // Password Form
    echo '<div class="password-form-container">';
    echo '<form method="POST" action="?action=update-password" class="password-form">';
    echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
    
    echo '<div class="form-section">';
    echo '<h2>Update Password</h2>';
    
    echo '<div class="form-group">';
    echo '<label for="current_password">Current Password:</label>';
    echo '<input type="password" id="current_password" name="current_password" required>';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="new_password">New Password:</label>';
    echo '<input type="password" id="new_password" name="new_password" required>';
    echo '<small>Must be at least 8 characters with uppercase, lowercase, and number</small>';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="confirm_password">Confirm New Password:</label>';
    echo '<input type="password" id="confirm_password" name="confirm_password" required>';
    echo '</div>';
    
    echo '</div>';
    
    echo '<div class="form-actions">';
    echo '<button type="submit" class="btn btn-primary btn-large">Update Password</button>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
    
    echo '</div>';
}

// Update password
public function updatePassword() {
    $this->requireAuth();
    
    if ($_POST) {
        try {
            // Verify CSRF token
            if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception("Invalid CSRF token");
            }
            
            $userId = $_SESSION['user_id'];
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate passwords match
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }
            
            // Update password
            if ($this->userModel->updatePassword($userId, $currentPassword, $newPassword)) {
                $_SESSION['success'] = 'Password updated successfully!';
                header('Location: ?action=edit-profile');
            } else {
                throw new Exception("Failed to update password");
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ?action=change-password');
        }
        exit;
    }
}

// Helper method to check if user is logged in
private function requireAuth() {
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        header('Location: ?action=show-login');
        exit;
    }
}
}
?>