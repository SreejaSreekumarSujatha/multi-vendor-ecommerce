<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../../core/Security.php';

class ProductController {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new Product();
        require_once __DIR__ . '/../models/Order.php';
    $this->orderModel = new Order();
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
    
    // Helper method to display navigation with cart counter
    private function displayNavigation() {
        if (isset($_SESSION['is_logged_in'])) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new Cart();
            $cartCount = 0;
            
            if ($_SESSION['user_type'] === 'customer') {
                $cartCount = $cartModel->getCartItemCount($_SESSION['user_id']);
            }
            
            echo '<div class="user-nav">';
            echo '<span>Welcome, ' . $_SESSION['user_name'] . '</span>';
            echo '<a href="?action=dashboard" class="btn btn-primary">Dashboard</a>';
            
       
if ($_SESSION['user_type'] === 'customer') {
    echo '<div class="header-cart">';
    echo '<a href="?action=cart" class="btn btn-secondary" id="cart-btn">';
    echo '🛒 Cart';
    if ($cartCount > 0) {
        echo '<span class="cart-counter">' . $cartCount . '</span>';
    }
    echo '</a>';
    
    echo '<div class="cart-popup" id="cart-popup">';
    echo '<div class="cart-popup-header">Shopping Cart</div>';
    echo '<div class="cart-popup-items" id="cart-popup-items">';
    echo '<div style="padding: 20px; text-align: center; color: #6c757d;">Loading...</div>';
    echo '</div>';
    echo '<div class="cart-popup-footer">';
    echo '<div class="cart-popup-total" id="cart-popup-total">Total: $0.00</div>';
    echo '<a href="?action=cart" class="btn btn-primary btn-small">View Full Cart</a>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
}
            
            echo '<a href="?action=logout" class="btn btn-logout">Logout</a>';
            echo '</div>';
        } else {
            echo '<div class="user-nav">';
            echo '<a href="?action=show-login" class="btn btn-primary">Login</a>';
            echo '<a href="?action=show-register" class="btn btn-secondary">Register</a>';
            echo '</div>';
        }
    }
    
    // Display all products (public view)
    public function index() {
        $products = $this->productModel->getAllProducts();
        $categories = $this->productModel->getCategories();
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<script src="js/cart.js"></script>';
        echo '<div class="shop-container">';
        
        // Header
        echo '<div class="shop-header">';
        echo '<h1>Product Catalog</h1>';
        $this->displayNavigation();
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
        echo '<script src="js/cart.js"></script>';
        echo '<div class="shop-container">';
        
        // Header
        echo '<div class="shop-header">';
        echo '<h1>Search Results</h1>';
        $this->displayNavigation();
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
        echo '<script src="js/cart.js"></script>';
        echo '<div class="product-detail-container">';
        
        // Show success/error messages
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        
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
    
    // Add to cart
    public function addToCart() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'customer') {
            die('Only customers can add items to cart');
        }
        
        if ($_POST) {
            try {
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                $productId = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                $customerId = $_SESSION['user_id'];
                
                if ($quantity <= 0) {
                    throw new Exception("Invalid quantity");
                }
                
                $product = $this->productModel->getProductById($productId);
                if (!$product) {
                    throw new Exception("Product not found");
                }
                
                if ($quantity > $product['stock_quantity']) {
                    throw new Exception("Not enough stock available");
                }
                
                require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new Cart();
                
                if ($cartModel->addItem($customerId, $productId, $quantity)) {
                    $_SESSION['success'] = "✅ {$product['name']} added to cart successfully!";
                } else {
                    throw new Exception("Failed to add product to cart");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
            
            header('Location: ?action=view-product&id=' . ($_POST['product_id'] ?? ''));
            exit;
        }
    }
    
    // View cart
    public function viewCart() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'customer') {
            die('Only customers can view cart');
        }
        
        require_once __DIR__ . '/../models/Cart.php';
        $cartModel = new Cart();
        
        $customerId = $_SESSION['user_id'];
        $cartItems = $cartModel->getCartItems($customerId);
        $cartTotal = $cartModel->getCartTotal($customerId);
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="cart-container">';
        
        // Show success/error messages
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        
        // Header
        echo '<div class="cart-header">';
        echo '<h1>Shopping Cart</h1>';
        echo '<div class="cart-nav">';
        echo '<a href="?action=products" class="btn btn-secondary">← Continue Shopping</a>';
        echo '<a href="?action=dashboard" class="btn btn-primary">Dashboard</a>';
        echo '</div>';
        echo '</div>';
        
        if (empty($cartItems)) {
            echo '<div class="empty-cart">';
            echo '<h2>Your cart is empty</h2>';
            echo '<p>Add some products to your cart to see them here.</p>';
            echo '<a href="?action=products" class="btn btn-primary">Start Shopping</a>';
            echo '</div>';
        } else {
            // Cart items table
            echo '<div class="cart-items">';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Product</th>';
            echo '<th>Price</th>';
            echo '<th>Quantity</th>';
            echo '<th>Total</th>';
            echo '<th>Actions</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($cartItems as $item) {
                echo '<tr>';
                
                // Product info
                echo '<td>';
                echo '<div class="cart-product">';
                if ($item['image_url']) {
                    echo '<img src="' . $item['image_url'] . '" alt="' . htmlspecialchars($item['name']) . '" class="cart-product-image">';
                }
                echo '<div class="cart-product-info">';
                echo '<h4>' . htmlspecialchars($item['name']) . '</h4>';
                echo '<p>Stock: ' . $item['stock_quantity'] . ' available</p>';
                echo '</div>';
                echo '</div>';
                echo '</td>';
                
                // Price
                echo '<td>$' . number_format($item['price'], 2) . '</td>';
                
                // Quantity with update form
                echo '<td>';
                echo '<form method="POST" action="?action=update-cart-item" class="quantity-form">';
                echo '<input type="hidden" name="cart_item_id" value="' . $item['id'] . '">';
                echo '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
                echo '<input type="number" name="quantity" value="' . $item['quantity'] . '" min="1" max="' . $item['stock_quantity'] . '">';
                echo '<button type="submit" class="btn btn-small">Update</button>';
                echo '</form>';
                echo '</td>';
                
                // Total price
                echo '<td>$' . number_format($item['total_price'], 2) . '</td>';
                
                // Remove button
                echo '<td>';
                echo '<a href="?action=remove-cart-item&id=' . $item['id'] . '" class="btn btn-danger btn-small" onclick="return confirm(\'Remove this item?\')">Remove</a>';
                echo '</td>';
                
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            
            // Cart summary
            echo '<div class="cart-summary">';
            echo '<h3>Cart Summary</h3>';
            echo '<p>Total Items: ' . count($cartItems) . '</p>';
            echo '<p class="cart-total">Total: $' . number_format($cartTotal, 2) . '</p>';
           
echo '<div class="cart-actions">';
echo '<a href="?action=checkout" class="btn btn-primary btn-large">Proceed to Checkout</a>';
echo '<a href="?action=clear-cart" class="btn btn-danger" onclick="return confirm(\'Clear entire cart?\')">Clear Cart</a>';
echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    // Update cart item
    public function updateCartItem() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'customer') {
            die('Access denied');
        }
        
        if ($_POST) {
            try {
                if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception("Invalid CSRF token");
                }
                
                $cartItemId = intval($_POST['cart_item_id']);
                $quantity = intval($_POST['quantity']);
                
                require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new Cart();
                
                if ($cartModel->updateItemQuantity($cartItemId, $quantity)) {
                    $_SESSION['success'] = 'Cart updated successfully!';
                } else {
                    throw new Exception("Failed to update cart");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }
        
        header('Location: ?action=cart');
        exit;
    }
    
    // Remove cart item
    public function removeCartItem() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'customer') {
            die('Access denied');
        }
        
        $cartItemId = $_GET['id'] ?? 0;
        
        require_once __DIR__ . '/../models/Cart.php';
        $cartModel = new Cart();
        
        if ($cartModel->removeItem($cartItemId)) {
            $_SESSION['success'] = 'Item removed from cart!';
        } else {
            $_SESSION['error'] = 'Failed to remove item';
        }
        
        header('Location: ?action=cart');
        exit;
    }
    
    // Clear cart
    public function clearCart() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'customer') {
            die('Access denied');
        }
        
        require_once __DIR__ . '/../models/Cart.php';
        $cartModel = new Cart();
        
        $customerId = $_SESSION['user_id'];
        
        if ($cartModel->clearCart($customerId)) {
            $_SESSION['success'] = 'Cart cleared successfully!';
        } else {
            $_SESSION['error'] = 'Failed to clear cart';
        }
        
        header('Location: ?action=cart');
        exit;
    }
    
// Get cart data for AJAX - FIXED VERSION
public function getCartData() {
    // Start output buffering to catch any unwanted output
    ob_start();
    
    try {
        // Check if user is logged in
        if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not logged in', 'total' => 0, 'count' => 0, 'items' => []]);
            exit;
        }
        
        // Check if user is customer
        if ($_SESSION['user_type'] !== 'customer') {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied', 'total' => 0, 'count' => 0, 'items' => []]);
            exit;
        }
        
        require_once __DIR__ . '/../models/Cart.php';
        $cartModel = new Cart();
        
        $customerId = $_SESSION['user_id'];
        
        // Get cart data with error handling
        $cartItems = $cartModel->getCartItems($customerId);
        $cartTotal = $cartModel->getCartTotal($customerId);
        $cartCount = $cartModel->getCartItemCount($customerId);
        
        // Clean any unwanted output
        ob_clean();
        
        // Set proper headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Return data
        echo json_encode([
            'success' => true,
            'items' => $cartItems ?: [],
            'total' => floatval($cartTotal ?: 0),
            'count' => intval($cartCount ?: 0)
        ]);
        
    } catch (Exception $e) {
        // Clean any output
        ob_clean();
        
        // Log error
        error_log("Cart data error: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Failed to load cart data',
            'message' => $e->getMessage(),
            'total' => 0,
            'count' => 0,
            'items' => []
        ]);
    }
    exit;
}
    
    // Helper method to render product card
    private function renderProductCard($product) {
        echo '<div class="product-card">';
        echo '<div class="product-image">';
        
        if ($product['image_url']) {
            if (filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                echo '<img src="' . $product['image_url'] . '" alt="' . htmlspecialchars($product['name']) . '">';
            } else {
                echo '<img src="' . $product['image_url'] . '" alt="' . htmlspecialchars($product['name']) . '">';
            }
        } else {
            echo '<div class="no-image">No Image Available</div>';
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
    
    // Show vendor's products
    public function myProducts() {
        $this->requireVendorOrAdmin();
        
        $vendorId = $_SESSION['user_id'];
        $products = $this->productModel->getProductsByVendor($vendorId);
        
        echo '<link rel="stylesheet" href="css/style.css">';
        echo '<div class="vendor-products-container">';
        
        // Show success/error messages
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        
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
if (!empty($product['image_url'])) {
    echo '<img src="' . htmlspecialchars($product['image_url']) . '" 
          alt="' . htmlspecialchars($product['name']) . '" 
          class="product-thumb"
          onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">';
    echo '<div class="no-image-thumb" style="display:none;">No Image</div>';
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
    
    // Add this method to ProductController.php

// Delete product (soft delete)
public function deleteProduct() {
    $this->requireVendorOrAdmin();
    
    $productId = $_GET['id'] ?? 0;
    
    if (!$productId) {
        $_SESSION['error'] = 'Invalid product ID';
        header('Location: ?action=my-products');
        exit;
    }
    
    try {
        // Check if user owns this product (unless admin)
        if ($_SESSION['user_type'] !== 'admin') {
            $product = $this->productModel->getProductById($productId);
            if (!$product || $product['vendor_id'] != $_SESSION['user_id']) {
                throw new Exception("You don't have permission to delete this product");
            }
        }
        
        // Soft delete the product
        if ($this->productModel->deleteProduct($productId)) {
            $_SESSION['success'] = 'Product deleted successfully!';
        } else {
            throw new Exception('Failed to delete product');
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: ?action=my-products');
    exit;
}

// You'll also need these additional methods that are referenced in myProducts():

// Show add product form
public function addProduct() {
    $this->requireVendorOrAdmin();
    
    $csrfToken = Security::generateCSRFToken();
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="add-product-container">';
    
    // Show success/error messages
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    // Header
    echo '<div class="form-header">';
    echo '<h1>Add New Product</h1>';
    echo '<a href="?action=my-products" class="btn btn-secondary">← Back to My Products</a>';
    echo '</div>';
    
    // Form
   echo '<form method="POST" action="?action=store-product" class="product-form" enctype="multipart/form-data">';
echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
    
    echo '<div class="form-group">';
    echo '<label for="name">Product Name:</label>';
    echo '<input type="text" id="name" name="name" required placeholder="Enter product name">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="description">Description:</label>';
    echo '<textarea id="description" name="description" required placeholder="Enter product description" rows="4"></textarea>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="form-group">';
    echo '<label for="price">Price ($):</label>';
    echo '<input type="number" id="price" name="price" step="0.01" min="0" required placeholder="0.00">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="stock_quantity">Stock Quantity:</label>';
    echo '<input type="number" id="stock_quantity" name="stock_quantity" min="0" required placeholder="0">';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="category">Category:</label>';
    echo '<select id="category" name="category" required>';
    echo '<option value="">Select Category</option>';
    echo '<option value="Electronics">Electronics</option>';
    echo '<option value="Clothing">Clothing</option>';
    echo '<option value="Books">Books</option>';
    echo '<option value="Home & Garden">Home & Garden</option>';
    echo '<option value="Sports">Sports</option>';
    echo '<option value="Toys">Toys</option>';
    echo '<option value="Other">Other</option>';
    echo '</select>';
    echo '</div>';
    
    echo '<div class="form-group">';
echo '<label for="product_image">Product Image:</label>';
echo '<input type="file" id="product_image" name="product_image" accept="image/*">';
echo '<small>Accepted formats: JPG, PNG, GIF. Max size: 2MB</small>';
echo '</div>';
    
    echo '<div class="form-group">';
    echo '<button type="submit" class="btn btn-primary btn-large">Add Product</button>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
}

// Store new product
public function storeProduct() {
    $this->requireVendorOrAdmin();
    
    if ($_POST) {
        try {
            // Debug logging
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            // Verify CSRF token
            if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception("Invalid CSRF token");
            }
            
            // Sanitize inputs
            $name = Security::sanitizeInput($_POST['name']);
            $description = Security::sanitizeInput($_POST['description']);
            $price = floatval($_POST['price']);
            $category = Security::sanitizeInput($_POST['category']);
            $stockQuantity = intval($_POST['stock_quantity']);
            
            // Handle file upload first
            $finalImageUrl = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $finalImageUrl = $this->handleImageUpload($_FILES['product_image']);
            }
            
            // Validation
            if (empty($name) || strlen($name) < 3) {
                throw new Exception("Product name must be at least 3 characters");
            }
            
            if ($price <= 0) {
                throw new Exception("Price must be greater than 0");
            }
            
            if ($stockQuantity < 0) {
                throw new Exception("Stock quantity cannot be negative");
            }
            
            // Create product data
            $productData = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'category' => $category,
                'vendor_id' => $_SESSION['user_id'],
                'image_url' => $finalImageUrl,
                'stock_quantity' => $stockQuantity
            ];
            
            // Debug: Log the product data
            error_log("Product data: " . print_r($productData, true));
            
            // Create product
            $product = $this->productModel->createProduct($productData);
            
            if ($product) {
                $_SESSION['success'] = 'Product added successfully!';
                header('Location: ?action=my-products');
            } else {
                throw new Exception("Failed to create product");
            }
            
        } catch (Exception $e) {
            error_log("Store product error: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ?action=add-product');
        }
        exit;
    }
}

// Edit product form
public function editProduct() {
    $this->requireVendorOrAdmin();
    
    $productId = $_GET['id'] ?? 0;
    
    if (!$productId) {
        $_SESSION['error'] = 'Invalid product ID';
        header('Location: ?action=my-products');
        exit;
    }
    
    $product = $this->productModel->getProductById($productId);
    
    if (!$product) {
        $_SESSION['error'] = 'Product not found';
        header('Location: ?action=my-products');
        exit;
    }
    
    // Check if user owns this product (unless admin)
    if ($_SESSION['user_type'] !== 'admin' && $product['vendor_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = "You don't have permission to edit this product";
        header('Location: ?action=my-products');
        exit;
    }
    
    $csrfToken = Security::generateCSRFToken();
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="edit-product-container">';
    
    // Show success/error messages
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    // Header
    echo '<div class="form-header">';
    echo '<h1>Edit Product</h1>';
    echo '<a href="?action=my-products" class="btn btn-secondary">← Back to My Products</a>';
    echo '</div>';
    
    // Form with existing data
   echo '<form method="POST" action="?action=update-product" class="product-form" enctype="multipart/form-data">';
    echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
    echo '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
    
    echo '<div class="form-group">';
    echo '<label for="name">Product Name:</label>';
    echo '<input type="text" id="name" name="name" required value="' . htmlspecialchars($product['name']) . '">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="description">Description:</label>';
    echo '<textarea id="description" name="description" required rows="4">' . htmlspecialchars($product['description']) . '</textarea>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="form-group">';
    echo '<label for="price">Price ($):</label>';
    echo '<input type="number" id="price" name="price" step="0.01" min="0" required value="' . $product['price'] . '">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="stock_quantity">Stock Quantity:</label>';
    echo '<input type="number" id="stock_quantity" name="stock_quantity" min="0" required value="' . $product['stock_quantity'] . '">';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label for="category">Category:</label>';
    echo '<select id="category" name="category" required>';
    $categories = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Toys', 'Other'];
    foreach ($categories as $cat) {
        $selected = ($product['category'] === $cat) ? 'selected' : '';
        echo '<option value="' . $cat . '" ' . $selected . '>' . $cat . '</option>';
    }
    echo '</select>';
    echo '</div>';
    
   echo '<div class="form-group">';
echo '<label for="product_image">Update Image:</label>';
echo '<input type="file" id="product_image" name="product_image" accept="image/*">';
echo '<small>Leave empty to keep current image</small>';
echo '</div>';
    
    echo '<div class="form-group">';
    echo '<button type="submit" class="btn btn-primary btn-large">Update Product</button>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
}

// Update product
public function updateProduct() {
    $this->requireVendorOrAdmin();
    
    if ($_POST) {
        try {
            // Debug logging
            error_log("UPDATE POST data: " . print_r($_POST, true));
            error_log("UPDATE FILES data: " . print_r($_FILES, true));
            
            // Verify CSRF token
            if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception("Invalid CSRF token");
            }
            
            $productId = intval($_POST['product_id']);
            
            // Check if product exists and user owns it
            $product = $this->productModel->getProductById($productId);
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            if ($_SESSION['user_type'] !== 'admin' && $product['vendor_id'] != $_SESSION['user_id']) {
                throw new Exception("You don't have permission to edit this product");
            }
            
            // Sanitize and validate inputs
            $name = Security::sanitizeInput($_POST['name']);
            $description = Security::sanitizeInput($_POST['description']);
            $price = floatval($_POST['price']);
            $category = Security::sanitizeInput($_POST['category']);
            $stockQuantity = intval($_POST['stock_quantity']);
            
            // Handle image upload
            $newImagePath = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $newImagePath = $this->handleImageUpload($_FILES['product_image']);
                
                // Delete old image if it exists and is a local file
                if ($product['image_url'] && strpos($product['image_url'], 'uploads/') === 0) {
                    if (file_exists($product['image_url'])) {
                        unlink($product['image_url']);
                        error_log("Deleted old image: " . $product['image_url']);
                    }
                }
            }
            
            // Use new image if uploaded, otherwise keep existing image
            $finalImageUrl = $newImagePath ?: $product['image_url'];
            
            // Validation
            if (empty($name) || strlen($name) < 3) {
                throw new Exception("Product name must be at least 3 characters");
            }
            
            if ($price <= 0) {
                throw new Exception("Price must be greater than 0");
            }
            
            if ($stockQuantity < 0) {
                throw new Exception("Stock quantity cannot be negative");
            }
            
            // Update product data
            $productData = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'category' => $category,
                'stock_quantity' => $stockQuantity,
                'image_url' => $finalImageUrl
            ];
            
            // Debug: Log the product data
            error_log("UPDATE Product data: " . print_r($productData, true));
            
            // Update product
            if ($this->productModel->updateProduct($productId, $productData)) {
                $_SESSION['success'] = 'Product updated successfully!';
            } else {
                throw new Exception("Failed to update product");
            }
            
        } catch (Exception $e) {
            error_log("Update product error: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: ?action=my-products');
        exit;
    }
}


private function handleImageUpload($file) {
    try {
        // Debug: Log the upload attempt
        error_log("Upload attempt - File: " . print_r($file, true));
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File too large (php.ini limit)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)', 
                UPLOAD_ERR_PARTIAL => 'File partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'No temp directory',
                UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];
            throw new Exception("Upload error: " . ($uploadErrors[$file['error']] ?? 'Unknown error'));
        }
        
        // Get actual MIME type (more reliable)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Invalid file type: $mimeType. Only JPG, PNG, and GIF are allowed.");
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large: " . round($file['size']/1024/1024, 2) . "MB. Maximum size is 2MB.");
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = 'uploads/products/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory: $uploadDir");
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            throw new Exception("Upload directory is not writable: $uploadDir");
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_' . time() . '_') . '.' . strtolower($extension);
        $uploadPath = $uploadDir . $filename;
        
        // Debug: Log the paths
        error_log("Upload path: $uploadPath");
        error_log("Temp file: " . $file['tmp_name']);
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            error_log("File uploaded successfully: $uploadPath");
            return $uploadPath;
        } else {
            throw new Exception("Failed to move uploaded file to: $uploadPath");
        }
        
    } catch (Exception $e) {
        error_log("Image upload error: " . $e->getMessage());
        throw $e;
    }
}

// Add these methods to ProductController.php

// Vendor's orders
public function myOrders() {
    $this->requireVendorOrAdmin();
    
    $vendorId = $_SESSION['user_id'];
    $orders = $this->orderModel->getOrdersByVendor($vendorId);
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="vendor-orders-container">';
    
    // Show success/error messages
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    // Header
    echo '<div class="vendor-header">';
    echo '<h1>My Orders</h1>';
    echo '<div class="vendor-nav">';
    echo '<a href="?action=dashboard" class="btn btn-secondary">← Dashboard</a>';
    echo '</div>';
    echo '</div>';
    
    if (empty($orders)) {
        echo '<div class="no-orders">';
        echo '<h2>No orders yet</h2>';
        echo '<p>You haven\'t received any orders for your products yet.</p>';
        echo '<a href="?action=my-products" class="btn btn-primary">Manage Products</a>';
        echo '</div>';
    } else {
        // Orders Table
        echo '<div class="orders-table">';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Order #</th>';
        echo '<th>Customer</th>';
        echo '<th>Date</th>';
        echo '<th>Total</th>';
        echo '<th>Status</th>';
        echo '<th>Payment</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($orders as $order) {
            echo '<tr>';
            echo '<td>#' . $order['order_number'] . '</td>';
            echo '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
            echo '<td>' . date('M d, Y', strtotime($order['created_at'])) . '</td>';
            echo '<td>$' . number_format($order['total_amount'], 2) . '</td>';
            echo '<td><span class="status-' . $order['status'] . '">' . ucfirst($order['status']) . '</span></td>';
            echo '<td>' . ucfirst(str_replace('_', ' ', $order['payment_method'])) . '</td>';
            echo '<td>';
            echo '<a href="?action=view-order&id=' . $order['id'] . '" class="btn btn-small">View</a> ';
            if ($order['status'] === 'pending') {
                echo '<a href="?action=update-order-status&id=' . $order['id'] . '&status=processing" class="btn btn-small btn-primary">Process</a>';
            } elseif ($order['status'] === 'processing') {
                echo '<a href="?action=update-order-status&id=' . $order['id'] . '&status=shipped" class="btn btn-small btn-success">Ship</a>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    echo '</div>';
}

// View order details
public function viewOrder() {
    $this->requireAuth();
    
    $orderId = $_GET['id'] ?? 0;
    $order = $this->orderModel->getOrderById($orderId);
    
    if (!$order) {
        $_SESSION['error'] = 'Order not found';
        header('Location: ?action=dashboard');
        exit;
    }
    
    // Check permissions
    $canView = false;
    if ($_SESSION['user_type'] === 'admin') {
        $canView = true;
    } elseif ($_SESSION['user_type'] === 'customer' && $order['customer_id'] == $_SESSION['user_id']) {
        $canView = true;
    } elseif ($_SESSION['user_type'] === 'vendor') {
        // Check if vendor has products in this order
        foreach ($order['items'] as $item) {
            if ($item['vendor_id'] == $_SESSION['user_id']) {
                $canView = true;
                break;
            }
        }
    }
    
    if (!$canView) {
        $_SESSION['error'] = 'Access denied';
        header('Location: ?action=dashboard');
        exit;
    }
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="order-detail-container">';
    
    // Show messages
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    
    // Navigation
    echo '<div class="order-nav">';
    if ($_SESSION['user_type'] === 'vendor') {
        echo '<a href="?action=my-orders" class="btn btn-secondary">← Back to Orders</a>';
    } else {
        echo '<a href="?action=my-orders" class="btn btn-secondary">← Back to Orders</a>';
    }
    echo '</div>';
    
    // Order Header
    echo '<div class="order-header">';
    echo '<h1>Order #' . $order['order_number'] . '</h1>';
    echo '<div class="order-meta">';
    echo '<span class="order-status status-' . $order['status'] . '">' . ucfirst($order['status']) . '</span>';
    echo '<span class="order-date">' . date('M d, Y g:i A', strtotime($order['created_at'])) . '</span>';
    echo '</div>';
    echo '</div>';
    
    // Order Details
    echo '<div class="order-details">';
    
    // Customer Info
    echo '<div class="detail-section">';
    echo '<h3>Customer Information</h3>';
    echo '<p><strong>Name:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>';
    echo '<p><strong>Email:</strong> ' . htmlspecialchars($order['customer_email']) . '</p>';
    echo '<p><strong>Shipping Address:</strong><br>' . nl2br(htmlspecialchars($order['shipping_address'])) . '</p>';
    echo '</div>';
    
    // Payment Info
    echo '<div class="detail-section">';
    echo '<h3>Payment Information</h3>';
    echo '<p><strong>Method:</strong> ' . ucfirst(str_replace('_', ' ', $order['payment_method'])) . '</p>';
    echo '<p><strong>Status:</strong> ' . ucfirst($order['payment_status']) . '</p>';
    echo '<p><strong>Total Amount:</strong> $' . number_format($order['total_amount'], 2) . '</p>';
    echo '</div>';
    
    // Order Items
    echo '<div class="detail-section">';
    echo '<h3>Items Ordered</h3>';
    echo '<div class="order-items-table">';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Product</th>';
    echo '<th>Vendor</th>';
    echo '<th>Quantity</th>';
    echo '<th>Price</th>';
    echo '<th>Total</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($order['items'] as $item) {
        echo '<tr>';
        echo '<td>';
        echo '<div class="item-info">';
        if ($item['image_url']) {
            echo '<img src="' . $item['image_url'] . '" alt="' . htmlspecialchars($item['product_name']) . '" class="item-image">';
        }
        echo '<span>' . htmlspecialchars($item['product_name']) . '</span>';
        echo '</div>';
        echo '</td>';
        echo '<td>' . htmlspecialchars($item['vendor_name']) . '</td>';
        echo '<td>' . $item['quantity'] . '</td>';
        echo '<td>$' . number_format($item['price_per_item'], 2) . '</td>';
        echo '<td>$' . number_format($item['total_price'], 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    
    // Vendor Actions
    if ($_SESSION['user_type'] === 'vendor' && in_array($order['status'], ['pending', 'processing'])) {
        echo '<div class="vendor-actions">';
        echo '<h3>Order Actions</h3>';
        if ($order['status'] === 'pending') {
            echo '<a href="?action=update-order-status&id=' . $order['id'] . '&status=processing" class="btn btn-primary">Mark as Processing</a>';
        } elseif ($order['status'] === 'processing') {
            echo '<a href="?action=update-order-status&id=' . $order['id'] . '&status=shipped" class="btn btn-success">Mark as Shipped</a>';
        }
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

// Update order status (vendor only)
public function updateOrderStatus() {
    $this->requireVendorOrAdmin();
    
    $orderId = $_GET['id'] ?? 0;
    $status = $_GET['status'] ?? '';
    
    try {
        if (!$orderId || !$status) {
            throw new Exception('Invalid parameters');
        }
        
        // Check if vendor has items in this order
        $order = $this->orderModel->getOrderById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        $vendorHasItems = false;
        foreach ($order['items'] as $item) {
            if ($item['vendor_id'] == $_SESSION['user_id']) {
                $vendorHasItems = true;
                break;
            }
        }
        
        if (!$vendorHasItems && $_SESSION['user_type'] !== 'admin') {
            throw new Exception('Access denied');
        }
        
        // Update status
        if ($this->orderModel->updateOrderStatus($orderId, $status)) {
            $_SESSION['success'] = 'Order status updated to ' . ucfirst($status);
        } else {
            throw new Exception('Failed to update order status');
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: ?action=view-order&id=' . $orderId);
    exit;
}

// Vendor earnings
public function earnings() {
    $this->requireVendorOrAdmin();
    
    $vendorId = $_SESSION['user_id'];
    $earnings = $this->orderModel->getVendorEarnings($vendorId);
    
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="earnings-container">';
    
    // Header
    echo '<div class="vendor-header">';
    echo '<h1>Earnings Dashboard</h1>';
    echo '<div class="vendor-nav">';
    echo '<a href="?action=dashboard" class="btn btn-secondary">← Dashboard</a>';
    echo '</div>';
    echo '</div>';
    
    // Earnings Cards
    echo '<div class="earnings-grid">';
    
    echo '<div class="earnings-card">';
    echo '<h3>Total Orders</h3>';
    echo '<div class="earnings-value">' . ($earnings['total_orders'] ?? 0) . '</div>';
    echo '<p>Orders containing your products</p>';
    echo '</div>';
    
    echo '<div class="earnings-card">';
    echo '<h3>Total Revenue</h3>';
    echo '<div class="earnings-value">$' . number_format($earnings['total_revenue'] ?? 0, 2) . '</div>';
    echo '<p>From all your sales</p>';
    echo '</div>';
    
    echo '<div class="earnings-card">';
    echo '<h3>Delivered Revenue</h3>';
    echo '<div class="earnings-value">$' . number_format($earnings['delivered_revenue'] ?? 0, 2) . '</div>';
    echo '<p>From completed orders</p>';
    echo '</div>';
    
    echo '<div class="earnings-card">';
    echo '<h3>Products Sold</h3>';
    echo '<div class="earnings-value">' . ($earnings['products_sold'] ?? 0) . '</div>';
    echo '<p>Different products with sales</p>';
    echo '</div>';
    
    echo '</div>';
    
    // Recent Orders
    echo '<div class="recent-orders">';
    echo '<h2>Recent Orders</h2>';
    $recentOrders = $this->orderModel->getOrdersByVendor($vendorId, 10);
    
    if (empty($recentOrders)) {
        echo '<p>No orders yet.</p>';
    } else {
        echo '<table>';
        echo '<thead>';
        echo '<tr><th>Order #</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($recentOrders as $order) {
            echo '<tr>';
            echo '<td><a href="?action=view-order&id=' . $order['id'] . '">#' . $order['order_number'] . '</a></td>';
            echo '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
            echo '<td>' . date('M d', strtotime($order['created_at'])) . '</td>';
            echo '<td>$' . number_format($order['total_amount'], 2) . '</td>';
            echo '<td><span class="status-' . $order['status'] . '">' . ucfirst($order['status']) . '</span></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    echo '</div>';
    
    echo '</div>';
}

}
?>