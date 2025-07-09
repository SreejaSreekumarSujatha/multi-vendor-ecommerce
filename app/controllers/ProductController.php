<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../../core/Security.php';

class ProductController {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new Product();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Check if user is logged in
    private function requireAuth() {
        if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
            header('Location: ?action=show-login');
            exit;
        }
    }
    
    // Check if user is vendor or admin
    private function requireVendorOrAdmin() {
        $this->requireAuth();
        if (!in_array($_SESSION['user_type'], ['vendor', 'admin'])) {
            die('Access denied. Vendor or Admin access required.');
        }
    }
    
    // Display all products (public view)
    public function index() {
        $products = $this->productModel->getAllProducts();
        $categories = $this->productModel->getCategories();
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="shop-container">';
        
        // Header
        echo '<div class="shop-header">';
        echo '<h1>Product Catalog</h1>';
        if (isset($_SESSION['is_logged_in'])) {
            echo '<div class="user-nav">';
            echo '<span>Welcome, ' . $_SESSION['user_name'] . '</span>';
            echo '<a href="?action=dashboard" class="btn btn-primary">Dashboard</a>';
            echo '<a href="?action=cart" class="btn btn-secondary">Cart</a>';
            echo '<a href="?action=logout" class="btn btn-logout">Logout</a>';
            echo '</div>';
        } else {
            echo '<div class="user-nav">';
            echo '<a href="?action=show-login" class="btn btn-primary">Login</a>';
            echo '<a href="?action=show-register" class="btn btn-secondary">Register</a>';
            echo '</div>';
        }
        echo '</div>';
        
        // Search and Filter
        echo '<div class="search-filter">';
        echo '<form method="GET" action="?action=search" class="search-form">';
        echo '<input type="hidden" name="action" value="search">';
        echo '<input type="text" name="search" placeholder="Search products..." value="' . ($_GET['search'] ?? '') . '">';
        echo '<select name="category">';
        echo '<option value="">All Categories</option>';
        foreach ($categories as $category) {
            $selected = ($_GET['category'] ?? '') === $category['category'] ? 'selected' : '';
            echo '<option value="' . $category['category'] . '" ' . $selected . '>' . $category['category'] . '</option>';
        }
        echo '</select>';
        echo '<button type="submit" class="btn btn-primary">Search</button>';
        echo '</form>';
        echo '</div>';
        
        // Products Grid
        echo '<div class="products-grid">';
        
        if (empty($products)) {
            echo '<div class="no-products">No products found.</div>';
        } else {
            foreach ($products as $product) {
                $this->renderProductCard($product);
            }
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // Search products
    public function search() {
        $searchTerm = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? null;
        
        $products = $this->productModel->searchProducts($searchTerm, $category);
        $categories = $this->productModel->getCategories();
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="shop-container">';
        
        // Header
        echo '<div class="shop-header">';
        echo '<h1>Search Results</h1>';
        if (isset($_SESSION['is_logged_in'])) {
            echo '<div class="user-nav">';
            echo '<span>Welcome, ' . $_SESSION['user_name'] . '</span>';
            echo '<a href="?action=dashboard" class="btn btn-primary">Dashboard</a>';
            echo '<a href="?action=cart" class="btn btn-secondary">Cart</a>';
            echo '<a href="?action=logout" class="btn btn-logout">Logout</a>';
            echo '</div>';
        }
        echo '</div>';
        
        // Search Results Info
        echo '<div class="search-info">';
        echo '<p>Found ' . count($products) . ' products';
        if ($searchTerm) echo ' for "' . htmlspecialchars($searchTerm) . '"';
        if ($category) echo ' in category "' . htmlspecialchars($category) . '"';
        echo '</p>';
        echo '<a href="?action=products" class="btn btn-secondary">View All Products</a>';
        echo '</div>';
        
        // Products Grid
        echo '<div class="products-grid">';
        
        if (empty($products)) {
            echo '<div class="no-products">No products found matching your search.</div>';
        } else {
            foreach ($products as $product) {
                $this->renderProductCard($product);
            }
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // View single product
    public function view($id) {
        $product = $this->productModel->getProductById($id);
        
        if (!$product) {
            die('Product not found');
        }
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="product-detail-container">';
        
        // Navigation
        echo '<div class="product-nav">';
        echo '<a href="?action=products" class="btn btn-secondary">← Back to Products</a>';
        if (isset($_SESSION['is_logged_in'])) {
            echo '<a href="?action=dashboard" class="btn btn-primary">Dashboard</a>';
        }
        echo '</div>';
        
        // Product Details
        echo '<div class="product-detail">';
        echo '<div class="product-image">';
        if ($product['image_url']) {
            echo '<img src="' . $product['image_url'] . '" alt="' . htmlspecialchars($product['name']) . '">';
        } else {
            echo '<div class="no-image">No Image Available</div>';
        }
        echo '</div>';
        
        echo '<div class="product-info">';
        echo '<h1>' . htmlspecialchars($product['name']) . '</h1>';
        echo '<p class="price">$' . number_format($product['price'], 2) . '</p>';
        echo '<p class="category">Category: ' . htmlspecialchars($product['category']) . '</p>';
        echo '<p class="vendor">Sold by: ' . htmlspecialchars($product['vendor_name']) . '</p>';
        echo '<p class="stock">Stock: ' . $product['stock_quantity'] . ' available</p>';
        echo '<div class="description">';
        echo '<h3>Description</h3>';
        echo '<p>' . nl2br(htmlspecialchars($product['description'])) . '</p>';
        echo '</div>';
        
        // Add to Cart (only for logged in customers)
        if (isset($_SESSION['is_logged_in']) && $_SESSION['user_type'] === 'customer') {
            echo '<div class="add-to-cart">';
            echo '<form method="POST" action="?action=add-to-cart">';
            echo '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
            echo '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
            echo '<label for="quantity">Quantity:</label>';
            echo '<input type="number" id="quantity" name="quantity" value="1" min="1" max="' . $product['stock_quantity'] . '">';
            echo '<button type="submit" class="btn btn-primary btn-large">Add to Cart</button>';
            echo '</form>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    // Show vendor's products
    public function myProducts() {
        $this->requireVendorOrAdmin();
        
        $vendorId = $_SESSION['user_id'];
        $products = $this->productModel->getProductsByVendor($vendorId);
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="vendor-products-container">';
        
        // Header
        echo '<div class="vendor-header">';
        echo '<h1>My Products</h1>';
        echo '<div class="vendor-nav">';
        echo '<a href="?action=dashboard" class="btn btn-secondary">← Dashboard</a>';
        echo '<a href="?action=add-product" class="btn btn-primary">Add New Product</a>';
        echo '</div>';
        echo '</div>';
        
        // Products Table
        echo '<div class="products-table">';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Image</th>';
        echo '<th>Name</th>';
        echo '<th>Price</th>';
        echo '<th>Category</th>';
        echo '<th>Stock</th>';
        echo '<th>Status</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        if (empty($products)) {
            echo '<tr><td colspan="7" class="no-products">No products found. <a href="?action=add-product">Add your first product</a></td></tr>';
        } else {
            foreach ($products as $product) {
                echo '<tr>';
                echo '<td>';
                if ($product['image_url']) {
                    echo '<img src="' . $product['image_url'] . '" alt="' . htmlspecialchars($product['name']) . '" class="product-thumb">';
                } else {
                    echo '<div class="no-image-thumb">No Image</div>';
                }
                echo '</td>';
                echo '<td>' . htmlspecialchars($product['name']) . '</td>';
                echo '<td>$' . number_format($product['price'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($product['category']) . '</td>';
                echo '<td>' . $product['stock_quantity'] . '</td>';
                echo '<td>' . ($product['is_active'] ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>') . '</td>';
                echo '<td>';
                echo '<a href="?action=view-product&id=' . $product['id'] . '" class="btn btn-small">View</a> ';
                echo '<a href="?action=edit-product&id=' . $product['id'] . '" class="btn btn-small btn-primary">Edit</a> ';
                echo '<a href="?action=delete-product&id=' . $product['id'] . '" class="btn btn-small btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
                echo '</td>';
                echo '</tr>';
            }
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    }
    
    // Show add product form
    public function addProduct() {
        $this->requireVendorOrAdmin();
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="add-product-container">';
        
        // Header
        echo '<div class="form-header">';
        echo '<h1>Add New Product</h1>';
        echo '<a href="?action=my-products" class="btn btn-secondary">← Back to My Products</a>';
        echo '</div>';
        
        // Show error/success messages
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        
        // Form
        echo '<form method="POST" action="?action=store-product" class="product-form" enctype="multipart/form-data">';
        echo '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
        
        echo '<div class="form-row">';
        echo '<div class="form-group">';
        echo '<label for="name">Product Name *</label>';
        echo '<input type="text" id="name" name="name" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="price">Price *</label>';
        echo '<input type="number" id="price" name="price" step="0.01" min="0" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group">';
        echo '<label for="category">Category *</label>';
        echo '<select id="category" name="category" required>';
        echo '<option value="">Select Category</option>';
        echo '<option value="Electronics">Electronics</option>';
        echo '<option value="Clothing">Clothing</option>';
        echo '<option value="Home & Kitchen">Home & Kitchen</option>';
        echo '<option value="Sports">Sports</option>';
        echo '<option value="Books">Books</option>';
        echo '<option value="Beauty">Beauty</option>';
        echo '<option value="Toys">Toys</option>';
        echo '<option value="Other">Other</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="stock_quantity">Stock Quantity *</label>';
        echo '<input type="number" id="stock_quantity" name="stock_quantity" min="0" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="description">Description</label>';
        echo '<textarea id="description" name="description" rows="5" placeholder="Describe your product..."></textarea>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="image">Product Image</label>';
        echo '<input type="file" id="image" name="image" accept="image/*">';
        echo '<small>Supported formats: JPG, PNG, GIF (Max 5MB)</small>';
        echo '</div>';
        
        echo '<div class="form-actions">';
        echo '<button type="submit" class="btn btn-primary btn-large">Add Product</button>';
        echo '<a href="?action=my-products" class="btn btn-secondary">Cancel</a>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }
    
    // Store new product
    public function storeProduct() {
        $this->requireVendorOrAdmin();
        
        if ($_POST) {
            try {
                // Verify CSRF token
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                // Validate and sanitize input
                $name = Security::sanitizeInput($_POST['name']);
                $price = floatval($_POST['price']);
                $category = Security::sanitizeInput($_POST['category']);
                $stockQuantity = intval($_POST['stock_quantity']);
                $description = Security::sanitizeInput($_POST['description']);
                $vendorId = $_SESSION['user_id'];
                
                // Validate required fields
                if (empty($name) || $price <= 0 || empty($category) || $stockQuantity < 0) {
                    throw new Exception("Please fill all required fields with valid values");
                }
                
                // Handle image upload
                $imageUrl = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imageUrl = $this->handleImageUpload($_FILES['image']);
                }
                
                // Create product
                $productData = [
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'category' => $category,
                    'vendor_id' => $vendorId,
                    'image_url' => $imageUrl,
                    'stock_quantity' => $stockQuantity
                ];
                
                $product = $this->productModel->createProduct($productData);
                
                if ($product) {
                    $_SESSION['success'] = 'Product added successfully!';
                    header('Location: ?action=my-products');
                    exit;
                } else {
                    throw new Exception("Failed to add product");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?action=add-product');
                exit;
            }
        }
    }
    
    // Handle image upload
    private function handleImageUpload($file) {
        $uploadDir = 'uploads/products/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception("File too large. Maximum size is 5MB.");
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        } else {
            throw new Exception("Failed to upload image");
        }
    }
    
    // Edit product
    public function editProduct($id) {
        $this->requireVendorOrAdmin();
        
        $product = $this->productModel->getProductById($id);
        
        if (!$product) {
            die('Product not found');
        }
        
        // Check if user owns this product (vendors can only edit their own products)
        if ($_SESSION['user_type'] === 'vendor' && $product['vendor_id'] != $_SESSION['user_id']) {
            die('Access denied');
        }
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="edit-product-container">';
        
        // Header
        echo '<div class="form-header">';
        echo '<h1>Edit Product</h1>';
        echo '<a href="?action=my-products" class="btn btn-secondary">← Back to My Products</a>';
        echo '</div>';
        
        // Show error/success messages
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        
        // Form
        echo '<form method="POST" action="?action=update-product" class="product-form" enctype="multipart/form-data">';
        echo '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
        echo '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
        
        echo '<div class="form-row">';
        echo '<div class="form-group">';
        echo '<label for="name">Product Name *</label>';
        echo '<input type="text" id="name" name="name" value="' . htmlspecialchars($product['name']) . '" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="price">Price *</label>';
        echo '<input type="number" id="price" name="price" value="' . $product['price'] . '" step="0.01" min="0" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group">';
        echo '<label for="category">Category *</label>';
        echo '<select id="category" name="category" required>';
        $categories = ['Electronics', 'Clothing', 'Home & Kitchen', 'Sports', 'Books', 'Beauty', 'Toys', 'Other'];
        foreach ($categories as $cat) {
            $selected = $product['category'] === $cat ? 'selected' : '';
            echo '<option value="' . $cat . '" ' . $selected . '>' . $cat . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="stock_quantity">Stock Quantity *</label>';
        echo '<input type="number" id="stock_quantity" name="stock_quantity" value="' . $product['stock_quantity'] . '" min="0" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="description">Description</label>';
        echo '<textarea id="description" name="description" rows="5">' . htmlspecialchars($product['description']) . '</textarea>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="image">Product Image</label>';
        if ($product['image_url']) {
            echo '<div class="current-image">';
            echo '<img src="' . $product['image_url'] . '" alt="Current image" style="max-width: 200px;">';
            echo '<p>Current image</p>';
            echo '</div>';
        }
        echo '<input type="file" id="image" name="image" accept="image/*">';
        echo '<small>Leave empty to keep current image. Supported formats: JPG, PNG, GIF (Max 5MB)</small>';
        echo '</div>';
        
        echo '<div class="form-actions">';
        echo '<button type="submit" class="btn btn-primary btn-large">Update Product</button>';
        echo '<a href="?action=my-products" class="btn btn-secondary">Cancel</a>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }
    
    // Update product
    public function updateProduct() {
        $this->requireVendorOrAdmin();
        
        if ($_POST) {
            try {
                // Verify CSRF token
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                $productId = intval($_POST['product_id']);
                $product = $this->productModel->getProductById($productId);
                
                if (!$product) {
                    throw new Exception("Product not found");
                }
                
                // Check ownership
                if ($_SESSION['user_type'] === 'vendor' && $product['vendor_id'] != $_SESSION['user_id']) {
                    throw new Exception("Access denied");
                }
                
                // Validate and sanitize input
                $name = Security::sanitizeInput($_POST['name']);
                $price = floatval($_POST['price']);
                $category = Security::sanitizeInput($_POST['category']);
                $stockQuantity = intval($_POST['stock_quantity']);
                $description = Security::sanitizeInput($_POST['description']);
                
                // Validate required fields
                if (empty($name) || $price <= 0 || empty($category) || $stockQuantity < 0) {
                    throw new Exception("Please fill all required fields with valid values");
                }
                
                // Handle image upload
                $imageUrl = $product['image_url']; // Keep existing image by default
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imageUrl = $this->handleImageUpload($_FILES['image']);
                }
                
                // Update product
                $productData = [
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'category' => $category,
                    'stock_quantity' => $stockQuantity,
                    'image_url' => $imageUrl
                ];
                
                $success = $this->productModel->updateProduct($productId, $productData);
                
                if ($success) {
                    $_SESSION['success'] = 'Product updated successfully!';
                    header('Location: ?action=my-products');
                    exit;
                } else {
                    throw new Exception("Failed to update product");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?action=edit-product&id=' . ($_POST['product_id'] ?? ''));
                exit;
            }
        }
    }
    
    // Delete product
    public function deleteProduct($id) {
        $this->requireVendorOrAdmin();
        
        $product = $this->productModel->getProductById($id);
        
        if (!$product) {
            die('Product not found');
        }
        
        // Check ownership
        if ($_SESSION['user_type'] === 'vendor' && $product['vendor_id'] != $_SESSION['user_id']) {
            die('Access denied');
        }
        
        if ($this->productModel->deleteProduct($id)) {
            $_SESSION['success'] = 'Product deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete product';
        }
        
        header('Location: ?action=my-products');
        exit;
    }
    
    // Helper method to render product card
    private function renderProductCard($product) {
        echo '<div class="product-card">';
        echo '<div class="product-image">';
        if ($product['image_url']) {
            echo '<img src="' . $product['image_url'] . '" alt="' . htmlspecialchars($product['name']) . '">';
        } else {
            echo '<div class="no-image">No Image</div>';
        }
        echo '</div>';
        echo '<div class="product-info">';
        echo '<h3>' . htmlspecialchars($product['name']) . '</h3>';
        echo '<p class="price">$' . number_format($product['price'], 2) . '</p>';
        echo '<p class="category">' . htmlspecialchars($product['category']) . '</p>';
        echo '<p class="vendor">by ' . htmlspecialchars($product['vendor_name']) . '</p>';
        echo '<p class="stock">Stock: ' . $product['stock_quantity'] . '</p>';
        echo '<div class="product-actions">';
        echo '<a href="?action=view-product&id=' . $product['id'] . '" class="btn btn-primary">View Details</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
?>