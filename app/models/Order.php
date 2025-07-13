<?php
require_once __DIR__ . '/../../core/Database.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Create new order from cart
 // In Order.php - REPLACE the createOrderFromCart method
public function createOrderFromCart($customerId, $shippingAddress, $paymentMethod = 'cash_on_delivery') {
    try {
        // Get cart items
        require_once __DIR__ . '/Cart.php';
        $cartModel = new Cart();
        $cartItems = $cartModel->getCartItems($customerId);
        
        if (empty($cartItems)) {
            throw new Exception("Cart is empty");
        }
        
        // Calculate total
        $totalAmount = array_sum(array_column($cartItems, 'total_price'));
        
        // Generate order number
        $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // Create order
        $sql = "INSERT INTO orders (customer_id, order_number, total_amount, shipping_address, payment_method) 
                VALUES (?, ?, ?, ?, ?)";
        
        $result = $this->db->execute($sql, [
            $customerId, 
            $orderNumber, 
            $totalAmount, 
            $shippingAddress, 
            $paymentMethod
        ]);
        
        if (!$result) {
            throw new Exception("Failed to create order");
        }
        
        // Get the order ID (you might need to adjust this based on your Database class)
        $orderId = $this->getLastOrderId($customerId, $orderNumber);
        
        // Create order items
        foreach ($cartItems as $item) {
            $sql = "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price_per_item, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            // Get vendor_id from product
            $product = $this->getProductById($item['product_id']);
            $vendorId = $product['vendor_id'];
            
            $this->db->execute($sql, [
                $orderId,
                $item['product_id'],
                $vendorId,
                $item['quantity'],
                $item['price'],
                $item['total_price']
            ]);
            
            // Update product stock
            $this->updateProductStock($item['product_id'], $item['quantity']);
        }
        
        // Clear cart after successful order
        $cartModel->clearCart($customerId);
        
        return $this->getOrderById($orderId);
        
    } catch (Exception $e) {
        error_log("Order creation error: " . $e->getMessage());
        throw $e;
    }
}

// Add this helper method to get the last inserted order ID
private function getLastOrderId($customerId, $orderNumber) {
    $sql = "SELECT id FROM orders WHERE customer_id = ? AND order_number = ? ORDER BY created_at DESC LIMIT 1";
    $result = $this->db->fetch($sql, [$customerId, $orderNumber]);
    return $result ? $result['id'] : null;
}
    
    // Get order by ID with items
    public function getOrderById($orderId) {
        $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ?";
        
        $order = $this->db->fetch($sql, [$orderId]);
        
        if ($order) {
            $order['items'] = $this->getOrderItems($orderId);
        }
        
        return $order;
    }
    
    // Get order items
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.image_url,
                       u.name as vendor_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN users u ON oi.vendor_id = u.id
                WHERE oi.order_id = ?";
        
        return $this->db->fetchAll($sql, [$orderId]);
    }
    
    // Get orders by customer
    public function getOrdersByCustomer($customerId, $limit = 20) {
        $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT " . intval($limit);
    return $this->db->fetchAll($sql, [$customerId]);
    }
    
    // Get orders by vendor
    public function getOrdersByVendor($vendorId, $limit = 50) {
      $sql = "SELECT DISTINCT o.*, u.name as customer_name, u.email as customer_email
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN users u ON o.customer_id = u.id
            WHERE oi.vendor_id = ?
            ORDER BY o.created_at DESC
            LIMIT " . intval($limit);
        
        return $this->db->fetchAll($sql, [$vendorId]);
    }
    
    // Get vendor earnings
    public function getVendorEarnings($vendorId) {
        $sql = "SELECT 
                    COUNT(DISTINCT oi.order_id) as total_orders,
                    SUM(oi.total_price) as total_revenue,
                    SUM(CASE WHEN o.status = 'delivered' THEN oi.total_price ELSE 0 END) as delivered_revenue,
                    COUNT(DISTINCT oi.product_id) as products_sold
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.vendor_id = ?";
        
        return $this->db->fetch($sql, [$vendorId]);
    }
    
    // Update order status
    public function updateOrderStatus($orderId, $status) {
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception("Invalid order status");
        }
        
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$status, $orderId]);
    }
    
    // Helper methods
    private function getProductById($productId) {
        $sql = "SELECT * FROM products WHERE id = ?";
        return $this->db->fetch($sql, [$productId]);
    }
    
    private function updateProductStock($productId, $quantity) {
        $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
        return $this->db->execute($sql, [$quantity, $productId]);
    }


    // Add to Order.php
public function updatePaymentStatus($orderId, $status, $transactionId = null) {
    $sql = "UPDATE orders SET payment_status = ?, transaction_id = ?, updated_at = NOW() WHERE id = ?";
    return $this->db->execute($sql, [$status, $transactionId, $orderId]);
}
}
?>