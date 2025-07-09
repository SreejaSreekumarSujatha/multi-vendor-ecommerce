<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../core/Security.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    // Show create user form with CSRF token
    public function create() {
        $csrfToken = Security::generateCSRFToken();
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo "<h1>Add New User</h1>";
        echo "<form method='POST' action='?action=store'>";
        
        // CSRF token field
        echo "<input type='hidden' name='csrf_token' value='$csrfToken'>";
        
        echo "<p>";
        echo "<label>Name:</label><br>";
        echo "<input type='text' name='name' required>";
        echo "</p>";
        echo "<p>";
        echo "<label>Email:</label><br>";
        echo "<input type='email' name='email' required>";
        echo "</p>";
        echo "<p>";
        echo "<label>Password:</label><br>";
        echo "<input type='password' name='password' required>";
        echo "<small>Must be at least 8 characters with uppercase, lowercase, and number</small>";
        echo "</p>";
        echo "<p>";
        echo "<label>User Type:</label><br>";
        echo "<select name='user_type'>";
        echo "<option value='customer'>Customer</option>";
        echo "<option value='vendor'>Vendor</option>";
        echo "<option value='admin'>Admin</option>";
        echo "</select>";
        echo "</p>";
        echo "<p>";
        echo "<input type='submit' value='Create User'>";
        echo "</p>";
        echo "</form>";
        echo "<a href='?action=index'>Back to Users</a>";
    }
    
    // Store new user with CSRF protection
    public function store() {
        if ($_POST) {
            try {
                // Verify CSRF token
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                $name = $_POST['name'];
                $email = $_POST['email'];
                $password = $_POST['password'];
                $userType = $_POST['user_type'];
                
                $user = $this->userModel->createUser($name, $email, $password, $userType);
                
                echo '<link rel="stylesheet" href="css/style.css">';
                echo "<h1>✅ User Created Successfully!</h1>";
                echo "<p>User <strong>" . htmlspecialchars($user['name']) . "</strong> has been created.</p>";
                echo "<a href='?action=index'>Back to Users</a>";
                
            } catch (Exception $e) {
                echo '<link rel="stylesheet" href="css/style.css">';
                echo "<h1>❌ Error Creating User</h1>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<a href='?action=create'>Try Again</a>";
            }
        }
    }
    
    // Other methods remain the same...
}
?>