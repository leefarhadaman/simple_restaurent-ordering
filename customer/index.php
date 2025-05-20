<?php
/**
 * Customer-facing menu page
 */
// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Wrap all code in try-catch for better error handling
try {
    require_once '../includes/config.php';

    // Get restaurant settings
    $settings = getThemeSettings();
    $restaurantName = $settings['restaurant_name'];
    $restaurantLogo = $settings['restaurant_logo'] ? '../uploads/' . $settings['restaurant_logo'] : '../assets/images/restaurant-logo.png';
    $themeColors = getThemeColors($settings['theme_color']);

    // Get all categories
    $categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $restaurantName; ?> - Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <style>
        :root {
            --primary: <?php echo $themeColors['primary']; ?>;
            --secondary: <?php echo $themeColors['secondary']; ?>;
            --text: <?php echo $themeColors['text']; ?>;
            --accent: <?php echo $themeColors['accent']; ?>;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="<?php echo $restaurantLogo; ?>" alt="<?php echo $restaurantName; ?> Logo">
            <h1><?php echo $restaurantName; ?></h1>
        </div>
        <button class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </header>
    
    <!-- Sidebar Menu (Mobile) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Categories</h2>
            <button class="close-sidebar" id="close-sidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sidebar-categories">
            <a href="#" class="sidebar-category active" data-category="all">All Items</a>
            <?php foreach ($categories as $category): ?>
                <a href="#" class="sidebar-category" data-category="<?php echo $category['id']; ?>">
                    <?php echo $category['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="overlay" id="overlay"></div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Category Navigation -->
        <div class="category-nav">
            <button class="active" data-category="all">All Items</button>
            <?php foreach ($categories as $category): ?>
                <button data-category="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></button>
            <?php endforeach; ?>
        </div>
        
        <!-- Menu Sections -->
        <?php foreach ($categories as $category): ?>
            <div class="menu-section" data-category="<?php echo $category['id']; ?>">
                <h2><?php echo $category['name']; ?></h2>
                <div class="menu-items">
                    <?php 
                    $menuItems = getMenuItemsByCategory($category['id']);
                    foreach ($menuItems as $item): 
                        $itemImage = $item['image'] ? '../uploads/' . $item['image'] : DEFAULT_IMAGE;
                    ?>
                        <div class="menu-item" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
                            <img src="<?php echo $itemImage; ?>" alt="<?php echo $item['name']; ?>" class="menu-item-image">
                            <div class="menu-item-info">
                                <h3 class="menu-item-name"><?php echo $item['name']; ?></h3>
                                <p class="menu-item-description"><?php echo $item['description']; ?></p>
                                <div class="menu-item-price"><?php echo formatPrice($item['price']); ?></div>
                                <button class="add-to-cart">
                                    <i class="fas fa-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Cart Button -->
    <button class="cart-button" id="cart-button">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cart-count">0</span>
    </button>
    
    <!-- Cart Modal -->
    <div class="modal" id="cart-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Your Order</h2>
                <button class="close-modal" id="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="cart-items" id="cart-items">
                    <!-- Cart items will be inserted here -->
                </div>
                <div id="checkout-section" style="display: none;">
                    <div class="cart-total">
                        <span>Total:</span>
                        <span id="cart-total">$0.00</span>
                    </div>
                    <form id="place-order-form" class="place-order-form">
                        <div class="form-group">
                            <label for="table-number">Table Number</label>
                            <input type="number" id="table-number" required min="1" max="99">
                        </div>
                        <button type="submit" class="place-order-btn">
                            Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Spinner -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>
    
    <script src="../assets/js/customer.js"></script>
</body>
</html>
<?php
} catch (Exception $e) {
    // Display error message
    echo '<div style="color: red; padding: 20px; background-color: #ffe6e6; border: 1px solid #ff0000; margin: 20px;">';
    echo '<h2>Error:</h2>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '</div>';
}
?>