<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    // Display all users
    public function index() {
        $users = $this->userModel->getAllUsers();
        
        // For now, we'll just display the data
        echo "<h1>All Users</h1>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Created At</th><th>Actions</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['user_type'] . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "<td>";
            echo "<a href='?action=view&id=" . $user['id'] . "'>View</a> | ";
            echo "<a href='?action=edit&id=" . $user['id'] . "'>Edit</a> | ";
            echo "<a href='?action=delete&id=" . $user['id'] . "'>Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<br><a href='?action=create'>Add New User</a>";
    }
    
    // Show single user
    public function view($id) {
        $user = $this->userModel->getUserById($id);
        
        if ($user) {
            echo "<h1>User Details</h1>";
            echo "<p><strong>Name:</strong> " . $user['name'] . "</p>";
            echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
            echo "<p><strong>Type:</strong> " . $user['user_type'] . "</p>";
            echo "<p><strong>Created:</strong> " . $user['created_at'] . "</p>";
            echo "<a href='?action=index'>Back to Users</a>";
        } else {
            echo "<h1>User not found</h1>";
            echo "<a href='?action=index'>Back to Users</a>";
        }
    }
    
    // Show create user form
    public function create() {
        echo "<h1>Add New User</h1>";
        echo "<form method='POST' action='?action=store'>";
        echo "<p>";
        echo "<label>Name:</label><br>";
        echo "<input type='text' name='name' required style='width: 300px; padding: 5px;'>";
        echo "</p>";
        echo "<p>";
        echo "<label>Email:</label><br>";
        echo "<input type='email' name='email' required style='width: 300px; padding: 5px;'>";
        echo "</p>";
        echo "<p>";
        echo "<label>Password:</label><br>";
        echo "<input type='password' name='password' required style='width: 300px; padding: 5px;'>";
        echo "</p>";
        echo "<p>";
        echo "<label>User Type:</label><br>";
        echo "<select name='user_type' style='width: 312px; padding: 5px;'>";
        echo "<option value='customer'>Customer</option>";
        echo "<option value='vendor'>Vendor</option>";
        echo "<option value='admin'>Admin</option>";
        echo "</select>";
        echo "</p>";
        echo "<p>";
        echo "<input type='submit' value='Create User' style='background-color: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer;'>";
        echo "</p>";
        echo "</form>";
        echo "<a href='?action=index'>Back to Users</a>";
    }
    
    // Store new user
    public function store() {
        if ($_POST) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $userType = $_POST['user_type'];
            
            $user = $this->userModel->createUser($name, $email, $password, $userType);
            
            if ($user) {
                echo "<h1>User Created Successfully!</h1>";
                echo "<p>User <strong>" . $user['name'] . "</strong> has been created.</p>";
                echo "<a href='?action=index'>Back to Users</a>";
            } else {
                echo "<h1>Error Creating User</h1>";
                echo "<p>Email might already exist or there was an error.</p>";
                echo "<a href='?action=create'>Try Again</a>";
            }
        }
    }
    
    // Delete user
    public function delete($id) {
        $user = $this->userModel->getUserById($id);
        
        if ($user) {
            $this->userModel->deleteUser($id);
            echo "<h1>User Deleted</h1>";
            echo "<p>User <strong>" . $user['name'] . "</strong> has been deleted.</p>";
        } else {
            echo "<h1>User not found</h1>";
        }
        
        echo "<a href='?action=index'>Back to Users</a>";
    }
}
?>