<?php
require_once __DIR__ . '/../../core/Database.php';

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all cart items for customer - WITH ERROR HANDLING
    public function getCartItems($customerId) {
        try {
            $sql = "SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity,
                           (ci.quantity * p.price) as total_price
                    FROM cart_items ci
                   JOIN products p ON ci.product_id = p.id
                   WHERE ci.customer_id = ? AND p.is_active = 1
                   ORDER BY ci.created_at DESC";
           
           $result = $this->db->fetchAll($sql, [$customerId]);
           return $result ?: [];
           
       } catch (Exception $e) {
           error_log("Cart getCartItems error: " . $e->getMessage());
           return [];
       }
   }
   
   // Get cart total - WITH ERROR HANDLING
   public function getCartTotal($customerId) {
       try {
           $sql = "SELECT SUM(ci.quantity * p.price) as total
                   FROM cart_items ci
                   JOIN products p ON ci.product_id = p.id
                   WHERE ci.customer_id = ? AND p.is_active = 1";
           
           $result = $this->db->fetch($sql, [$customerId]);
           return $result ? floatval($result['total']) : 0;
           
       } catch (Exception $e) {
           error_log("Cart getCartTotal error: " . $e->getMessage());
           return 0;
       }
   }
   
   // Get cart item count - WITH ERROR HANDLING
   public function getCartItemCount($customerId) {
       try {
           $sql = "SELECT SUM(quantity) as count 
                   FROM cart_items 
                   WHERE customer_id = ?";
           
           $result = $this->db->fetch($sql, [$customerId]);
           return $result ? intval($result['count']) : 0;
           
       } catch (Exception $e) {
           error_log("Cart getCartItemCount error: " . $e->getMessage());
           return 0;
       }
   }
   
   // Add item to cart - WITH ERROR HANDLING
   public function addItem($customerId, $productId, $quantity) {
       try {
           // Check if item already in cart
           $existingItem = $this->getCartItem($customerId, $productId);
           
           if ($existingItem) {
               // Update quantity if item exists
               $newQuantity = $existingItem['quantity'] + $quantity;
               return $this->updateItemQuantity($existingItem['id'], $newQuantity);
           } else {
               // Add new item to cart
               $sql = "INSERT INTO cart_items (customer_id, product_id, quantity, created_at) 
                       VALUES (?, ?, ?, NOW())";
               return $this->db->execute($sql, [$customerId, $productId, $quantity]);
           }
           
       } catch (Exception $e) {
           error_log("Cart addItem error: " . $e->getMessage());
           return false;
       }
   }
   
   // Get cart item - PRIVATE METHOD
   private function getCartItem($customerId, $productId) {
       try {
           $sql = "SELECT * FROM cart_items WHERE customer_id = ? AND product_id = ?";
           return $this->db->fetch($sql, [$customerId, $productId]);
       } catch (Exception $e) {
           error_log("Cart getCartItem error: " . $e->getMessage());
           return false;
       }
   }
   
   // Update item quantity
   public function updateItemQuantity($cartItemId, $quantity) {
       try {
           if ($quantity <= 0) {
               return $this->removeItem($cartItemId);
           }
           
           $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
           return $this->db->execute($sql, [$quantity, $cartItemId]);
           
       } catch (Exception $e) {
           error_log("Cart updateItemQuantity error: " . $e->getMessage());
           return false;
       }
   }
   
   // Remove item from cart
   public function removeItem($cartItemId) {
       try {
           $sql = "DELETE FROM cart_items WHERE id = ?";
           return $this->db->execute($sql, [$cartItemId]);
       } catch (Exception $e) {
           error_log("Cart removeItem error: " . $e->getMessage());
           return false;
       }
   }
   
   // Clear cart
   public function clearCart($customerId) {
       try {
           $sql = "DELETE FROM cart_items WHERE customer_id = ?";
           return $this->db->execute($sql, [$customerId]);
       } catch (Exception $e) {
           error_log("Cart clearCart error: " . $e->getMessage());
           return false;
       }
   }
}
?>