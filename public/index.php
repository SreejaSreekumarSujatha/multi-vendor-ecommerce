<?php
// Start session
session_start();

// Include controllers
require_once '../app/controllers/UserController.php';
require_once '../app/controllers/AuthController.php';
require_once '../app/controllers/ProductController.php';
require_once '../app/controllers/OrderController.php';


// Get action from URL
$action = $_GET['action'] ?? 'products';
$id = $_GET['id'] ?? null;

// Create controllers
$userController = new UserController();
$authController = new AuthController();
$productController = new ProductController();

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
    
    // Product routes
    case 'products':
    case 'shop':
        $productController->index();
        break;
    
    case 'search':
        $productController->search();
        break;
    
    case 'view-product':
        if ($id) {
            $productController->view($id);
        } else {
            $productController->index();
        }
        break;
    
    // Vendor product management
    case 'my-products':
        $productController->myProducts();
        break;
    
    case 'add-product':
        $productController->addProduct();
        break;
    
    case 'store-product':
        $productController->storeProduct();
        break;
    
    case 'edit-product':
        if ($id) {
            $productController->editProduct($id);
        } else {
            header('Location: ?action=my-products');
        }
        break;
    
    case 'update-product':
        $productController->updateProduct();
        break;
    
    case 'delete-product':
        if ($id) {
            $productController->deleteProduct($id);
        } else {
            header('Location: ?action=my-products');
        }
        break;
    
    // User management routes (admin only)
    case 'users':
    case 'manage-users':
        $userController->index();
        break;
    
    case 'view-user':
        if ($id) {
            $userController->view($id);
        } else {
            $userController->index();
        }
        break;
    
    case 'create-user':
        $userController->create();
        break;
    
    case 'store-user':
        $userController->store();
        break;
    
    case 'delete-user':
        if ($id) {
            $userController->delete($id);
        } else {
            $userController->index();
        }
        break;
    
    // Default route
    default:
        $productController->index();
        break;



        // Cart routes
case 'add-to-cart':
    $productController->addToCart();
    break;

case 'cart':
case 'view-cart':
    $productController->viewCart();
    break;

case 'update-cart-item':
    $productController->updateCartItem();
    break;

case 'remove-cart-item':
    $productController->removeCartItem();
    break;

case 'clear-cart':
    $productController->clearCart();
    break;

    case 'get-cart-data':
    $productController->getCartData();
    break;

    case 'checkout':
    $orderController = new OrderController();
    $orderController->checkout();
    break;
case 'place-order':
    $orderController = new OrderController();
    $orderController->placeOrder();
    break;
case 'order-confirmation':
    $orderController = new OrderController();
    $orderController->orderConfirmation();
    break;

// Vendor order management
case 'my-orders':
    if ($_SESSION['user_type'] === 'vendor' || $_SESSION['user_type'] === 'admin') {
        $productController->myOrders();
    } else {
        $orderController = new OrderController();
        $orderController->myOrders();
    }
    break;
case 'view-order':
    $productController->viewOrder();
    break;
case 'update-order-status':
    $productController->updateOrderStatus();
    break;
case 'earnings':
    $productController->earnings();
    break;

    // Add these cases to your switch statement
case 'profile':
case 'edit-profile':
    $authController->editProfile();
    break;
case 'update-profile':
    $authController->updateProfile();
    break;
case 'change-password':
    $authController->changePassword();
    break;
case 'update-password':
    $authController->updatePassword();
    break;
case 'paypal-cancel':
    session_start();
    $_SESSION['error'] = 'PayPal payment was cancelled. Your order has not been placed.';
    header('Location: ?action=checkout');
    exit;

case 'paypal-success':
    session_start();
    $_SESSION['success'] = 'Payment completed! Please check your email for order confirmation.';
    header('Location: ?action=dashboard');
    exit;

case 'paypal-ipn':
    // Simple IPN handler
    http_response_code(200);
    echo "OK";
    exit;
}



?>