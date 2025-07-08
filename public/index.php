<?php
// Start session
session_start();

// Include controllers
require_once '../app/controllers/UserController.php';
require_once '../app/controllers/AuthController.php';

// Get action from URL
$action = $_GET['action'] ?? 'show-login';
$id = $_GET['id'] ?? null;

// Create controllers
$userController = new UserController();
$authController = new AuthController();

// Route to appropriate controller and method
switch ($action) {
    // Authentication routes
    case 'show-login':
        $authController->showLogin();
        break;
    
    case 'login':
        $authController->login();
        break;
    
    case 'show-register':
        $authController->showRegister();
        break;
    
    case 'register':
        $authController->register();
        break;
    
    case 'logout':
        $authController->logout();
        break;
    
    case 'dashboard':
    case 'admin-dashboard':
    case 'vendor-dashboard':
        $authController->dashboard();
        break;
    
    // User management routes (admin only)
    case 'users':
    case 'index':
        $userController->index();
        break;
    
    case 'view':
        if ($id) {
            $userController->view($id);
        } else {
            $userController->index();
        }
        break;
    
    case 'create':
        $userController->create();
        break;
    
    case 'store':
        $userController->store();
        break;
    
    case 'delete':
        if ($id) {
            $userController->delete($id);
        } else {
            $userController->index();
        }
        break;
    
    // Default route
    default:
        $authController->showLogin();
        break;
}
?>