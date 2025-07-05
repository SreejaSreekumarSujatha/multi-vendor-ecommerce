<?php
// Include our controller
require_once '../app/controllers/UserController.php';

// Basic routing
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Create controller instance
$userController = new UserController();

// Handle different actions
switch ($action) {
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
    
    default:
        $userController->index();
        break;
}
?>