<?php
require_once __DIR__ . '/../../core/Database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all products
    public function getAllProducts($limit = null, $offset = 0) {
        $sql = "SELECT p.*, u.name as vendor_name 
                FROM products p 
                JOIN users u ON p.vendor_id = u.id 
                WHERE p.is_active = 1 
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    // Get products by vendor
    public function getProductsByVendor($vendorId) {
        $sql = "SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$vendorId]);
    }
    
    // Get product by ID
    public function getProductById($id) {
        $sql = "SELECT p.*, u.name as vendor_name 
                FROM products p 
                JOIN users u ON p.vendor_id = u.id 
                WHERE p.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    // Create new product
    public function createProduct($data) {
        $sql = "INSERT INTO products (name, description, price, category, vendor_id, image_url, stock_quantity) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->execute($sql, [
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['vendor_id'],
            $data['image_url'] ?? null,
            $data['stock_quantity']
        ]);
        
        if ($result) {
            return $this->getProductById($this->db->lastInsertId());
        }
        return false;
    }
    
    // Update product
    public function updateProduct($id, $data) {
        $sql = "UPDATE products 
                SET name = ?, description = ?, price = ?, category = ?, stock_quantity = ?, image_url = ?
                WHERE id = ?";
        
        return $this->db->execute($sql, [
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['stock_quantity'],
            $data['image_url'],
            $id
        ]);
    }
    
    // Delete product
    public function deleteProduct($id) {
        $sql = "UPDATE products SET is_active = 0 WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    // Search products
    public function searchProducts($searchTerm, $category = null) {
        $sql = "SELECT p.*, u.name as vendor_name 
                FROM products p 
                JOIN users u ON p.vendor_id = u.id 
                WHERE p.is_active = 1 
                AND (p.name LIKE ? OR p.description LIKE ?)";
        
        $params = ["%$searchTerm%", "%$searchTerm%"];
        
        if ($category) {
            $sql .= " AND p.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Get product categories
    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category";
        return $this->db->fetchAll($sql);
    }
}
?>