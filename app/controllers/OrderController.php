<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../../core/Security.php';

class OrderController {
    private $orderModel;
    
    public function __construct() {
        $this->orderModel = new Order();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    private function requireAuth() {
        if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
            header('Location: ?action=show-login');
            exit;
        }
    }
    
    // Checkout page
    public function checkout() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'customer') {
            die('Only customers can checkout');
        }
        
        require_once __DIR__ . '/../models/Cart.php';
        $cartModel = new Cart();
        
        $customerId = $_SESSION['user_id'];
        $cartItems = $cartModel->getCartItems($customerId);
        $cartTotal = $cartModel->getCartTotal($customerId);
        
        if (empty($cartItems)) {
            $_SESSION['error'] = 'Your cart is empty';
            header('Location: ?action=cart');
            exit;
        }
        
        $csrfToken = Security::generateCSRFToken();
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="checkout-container">';
        
        // Show messages
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        
        echo '<h1>Checkout</h1>';
        
        // Order Summary
        echo '<div class="checkout-sections">';
        echo '<div class="order-summary">';
        echo '<h2>Order Summary</h2>';
        
        foreach ($cartItems as $item) {
            echo '<div class="checkout-item">';
            echo '<span>' . htmlspecialchars($item['name']) . ' x ' . $item['quantity'] . '</span>';
            echo '<span>$' . number_format($item['total_price'], 2) . '</span>';
            echo '</div>';
        }
        
        echo '<div class="checkout-total">';
        echo '<strong>Total: $' . number_format($cartTotal, 2) . '</strong>';
        echo '</div>';
        echo '</div>';
        
        // Checkout Form
        echo '<div class="checkout-form">';
        echo '<h2>Shipping Information</h2>';
        echo '<form method="POST" action="?action=place-order">';
        echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
        
        echo '<div class="form-group">';
        echo '<label for="full_name">Full Name:</label>';
        echo '<input type="text" id="full_name" name="full_name" required value="' . htmlspecialchars($_SESSION['user_name']) . '">';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="phone">Phone Number:</label>';
        echo '<input type="tel" id="phone" name="phone" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="address">Street Address:</label>';
        echo '<textarea id="address" name="address" required placeholder="Enter your full address"></textarea>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group">';
        echo '<label for="city">City:</label>';
        echo '<input type="text" id="city" name="city" required>';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="postal_code">Postal Code:</label>';
        echo '<input type="text" id="postal_code" name="postal_code" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="payment_method">Payment Method:</label>';
        echo '<select id="payment_method" name="payment_method" required>';
        echo '<option value="cash_on_delivery">Cash on Delivery</option>';
        echo '<option value="bank_transfer">Bank Transfer</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<button type="submit" class="btn btn-primary btn-large">Place Order</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="checkout-nav">';
        echo '<a href="?action=cart" class="btn btn-secondary">← Back to Cart</a>';
        echo '</div>';
        
        echo '</div>';
    }
    
    // Place order
    public function placeOrder() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'customer') {
            die('Only customers can place orders');
        }
        
        if ($_POST) {
            try {
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                $customerId = $_SESSION['user_id'];
                
                // Build shipping address
                $shippingAddress = [
                    'name' => Security::sanitizeInput($_POST['full_name']),
                    'phone' => Security::sanitizeInput($_POST['phone']),
                    'address' => Security::sanitizeInput($_POST['address']),
                    'city' => Security::sanitizeInput($_POST['city']),
                    'postal_code' => Security::sanitizeInput($_POST['postal_code'])
                ];
                
                $shippingAddressText = implode(', ', $shippingAddress);
                $paymentMethod = Security::sanitizeInput($_POST['payment_method']);
                
                // Create order
                $order = $this->orderModel->createOrderFromCart($customerId, $shippingAddressText, $paymentMethod);
                
                if ($order) {
                    $_SESSION['success'] = 'Order placed successfully! Order #' . $order['order_number'];
                    header('Location: ?action=order-confirmation&id=' . $order['id']);
                } else {
                    throw new Exception("Failed to place order");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?action=checkout');
            }
            exit;
        }
    }
    
    // Order confirmation page
    public function orderConfirmation() {
        $this->requireAuth();
        
        $orderId = $_GET['id'] ?? 0;
        $order = $this->orderModel->getOrderById($orderId);
        
        if (!$order || $order['customer_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'Order not found';
            header('Location: ?action=dashboard');
            exit;
        }
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="order-confirmation-container">';
        
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        
        echo '<h1>Order Confirmation</h1>';
        echo '<div class="order-details">';
        echo '<h2>Order #' . $order['order_number'] . '</h2>';
        echo '<p><strong>Status:</strong> ' . ucfirst($order['status']) . '</p>';
        echo '<p><strong>Total:</strong> $' . number_format($order['total_amount'], 2) . '</p>';
        echo '<p><strong>Payment:</strong> ' . ucfirst(str_replace('_', ' ', $order['payment_method'])) . '</p>';
        echo '<p><strong>Order Date:</strong> ' . date('M d, Y', strtotime($order['created_at'])) . '</p>';
        
        echo '<h3>Items Ordered:</h3>';
        echo '<div class="order-items">';
        foreach ($order['items'] as $item) {
            echo '<div class="order-item">';
            echo '<span>' . htmlspecialchars($item['product_name']) . '</span>';
            echo '<span>Qty: ' . $item['quantity'] . '</span>';
            echo '<span>$' . number_format($item['total_price'], 2) . '</span>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div class="order-actions">';
        echo '<a href="?action=dashboard" class="btn btn-primary">Back to Dashboard</a>';
        echo '<a href="?action=products" class="btn btn-secondary">Continue Shopping</a>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    // Customer's orders
    public function myOrders() {
        $this->requireAuth();
        
        $customerId = $_SESSION['user_id'];
        $orders = $this->orderModel->getOrdersByCustomer($customerId);
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="my-orders-container">';
        
        echo '<h1>My Orders</h1>';
        
        if (empty($orders)) {
            echo '<div class="no-orders">';
            echo '<h2>No orders yet</h2>';
            echo '<p>You haven\'t placed any orders yet.</p>';
            echo '<a href="?action=products" class="btn btn-primary">Start Shopping</a>';
            echo '</div>';
        } else {
            echo '<div class="orders-list">';
            foreach ($orders as $order) {
                echo '<div class="order-card">';
                echo '<div class="order-header">';
                echo '<h3>Order #' . $order['order_number'] . '</h3>';
                echo '<span class="order-status status-' . $order['status'] . '">' . ucfirst($order['status']) . '</span>';
                echo '</div>';
                echo '<div class="order-info">';
                echo '<p>Date: ' . date('M d, Y', strtotime($order['created_at'])) . '</p>';
                echo '<p>Total: $' . number_format($order['total_amount'], 2) . '</p>';
                echo '<p>Payment: ' . ucfirst(str_replace('_', ' ', $order['payment_method'])) . '</p>';
                echo '</div>';
                echo '<div class="order-actions">';
                echo '<a href="?action=view-order&id=' . $order['id'] . '" class="btn btn-primary btn-small">View Details</a>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
}
?>