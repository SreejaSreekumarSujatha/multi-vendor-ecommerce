<?php
require_once __DIR__ . '/../../core/Database.php';

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Add item to cart
    public function addItem($customerId, $productId, $quantity) {
        // Check if item already in cart
        $existingItem = $this->getCartItem($customerId, $productId);
        
        if ($existingItem) {
            // Update quantity if item exists
            $newQuantity = $existingItem['quantity'] + $quantity;
            return $this->updateItemQuantity($existingItem['id'], $newQuantity);
        } else {
            // Add new item to cart
            $sql = "INSERT INTO cart_items (customer_id, product_id, quantity) VALUES (?, ?, ?)";
            return $this->db->execute($sql, [$customerId, $productId, $quantity]);
        }
    }
    
    // Get cart item
    private function getCartItem($customerId, $productId) {
        $sql = "SELECT * FROM cart_items WHERE customer_id = ? AND product_id = ?";
        return $this->db->fetch($sql, [$customerId, $productId]);
    }
    
    // Update item quantity
    public function updateItemQuantity($cartItemId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($cartItemId);
        }
        
        $sql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        return $this->db->execute($sql, [$quantity, $cartItemId]);
    }
    
    // Remove item from cart
    public function removeItem($cartItemId) {
        $sql = "DELETE FROM cart_items WHERE id = ?";
        return $this->db->execute($sql, [$cartItemId]);
    }
    
    // Get all cart items for customer
    public function getCartItems($customerId) {
        $sql = "SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity,
                       (ci.quantity * p.price) as total_price
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.customer_id = ?
                ORDER BY ci.created_at DESC";
        
        return $this->db->fetchAll($sql, [$customerId]);
    }
    
    // Get cart total
    public function getCartTotal($customerId) {
        $sql = "SELECT SUM(ci.quantity * p.price) as total
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.customer_id = ?";
        
        $result = $this->db->fetch($sql, [$customerId]);
        return $result ? $result['total'] : 0;
    }
    
    // Get cart item count
    public function getCartItemCount($customerId) {
        $sql = "SELECT SUM(quantity) as count FROM cart_items WHERE customer_id = ?";
        $result = $this->db->fetch($sql, [$customerId]);
        return $result ? $result['count'] : 0;
    }
    
    // Clear cart
    public function clearCart($customerId) {
        $sql = "DELETE FROM cart_items WHERE customer_id = ?";
        return $this->db->execute($sql, [$customerId]);
    }
}
?>