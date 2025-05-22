<?php
/**
 * Customer-facing menu page - Enhanced modern UI with ad footer
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
    $restaurantLogo = $settings['restaurant_logo'] ? '../Uploads/' . $settings['restaurant_logo'] : '../assets/images/restaurant-logo.png';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/ad-footer.css">
    <link rel="stylesheet" href="../assets/css/order-confirmation.css">
    <style>
        :root {
            --primary: <?php echo $themeColors['primary']; ?>;
            --secondary: <?php echo $themeColors['secondary']; ?>;
            --text: <?php echo $themeColors['text']; ?>;
            --accent: <?php echo $themeColors['accent']; ?>;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            padding-bottom: 120px;
        }
        
        /* Enhanced header */
        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo img {
            height: 40px;
            border-radius: 8px;
        }
        
        .logo h1 {
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--text);
        }
        
        /* Container styling */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
        }
        
        /* Main Content Layout */
        .menu-section {
            margin-bottom: 2.5rem;
        }
        
        /* Enhanced category nav */
        .category-nav {
            background: white;
            border-radius: 10px;
            padding: 10px;
            position: sticky;
            top: 70px;
            z-index: 90;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            overflow-x: auto;
            white-space: nowrap;
            display: flex;
            gap: 8px;
        }
        
        .category-nav button {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            color: #555;
            border: none;
            background: #f5f5f5;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }
        
        .category-nav button.active {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }
        
        .category-nav button:hover:not(.active) {
            background-color: #eaeaea;
        }
        
        /* Enhanced menu item */
        .menu-item {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            background: white;
        }
        
        .menu-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .menu-item-image {
            position: relative;
            overflow: hidden;
        }
        
        .menu-item-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.2), transparent);
        }
        
        .add-to-cart {
            border-radius: 30px;
            padding: 10px 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .add-to-cart::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--primary);
            transition: all 0.3s ease;
            z-index: -1;
        }
        
        .add-to-cart:hover {
            transform: scale(1.05);
            color: white;
        }
        
        .add-to-cart:hover::before {
            left: 0;
        }
        
        .menu-section h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid rgba(var(--accent-rgb), 0.3);
        }
        
        /* Smooth animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .menu-item {
            animation: fadeInUp 0.6s ease backwards;
        }
        
        /* Add animation delay to each item to create a cascade effect */
        .menu-items .menu-item:nth-child(1) { animation-delay: 0.1s; }
        .menu-items .menu-item:nth-child(2) { animation-delay: 0.2s; }
        .menu-items .menu-item:nth-child(3) { animation-delay: 0.3s; }
        .menu-items .menu-item:nth-child(4) { animation-delay: 0.4s; }
        .menu-items .menu-item:nth-child(5) { animation-delay: 0.5s; }
        .menu-items .menu-item:nth-child(6) { animation-delay: 0.6s; }
        
        /* Additional cart styling */
        .order-confirmation {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background-color: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            z-index: 2000;
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-width: 90%;
            width: 400px;
            border: 1px solid rgba(var(--accent-rgb), 0.2);
        }
        
        .order-confirmation.show {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        
        .success-animation {
            margin-bottom: 20px;
        }
        
        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: var(--success);
            stroke-miterlimit: 10;
            margin: 0 auto;
            box-shadow: inset 0 0 0 var(--success);
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }
        
        .checkmark-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: var(--success);
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        
        .checkmark-check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        
        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }
        
        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }
        
        @keyframes fill {
            100% {
                box-shadow: inset 0 0 0 30px var(--success);
            }
        }
        
        .order-confirmation h3 {
            font-size: 24px;
            color: var(--text);
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .order-confirmation p {
            color: var(--dark-gray);
            margin-bottom: 5px;
        }
        
        .order-number {
            background: rgba(var(--success), 0.1);
            color: var(--success) !important;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }
        
        .empty-cart-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark-gray);
        }
        
        .empty-cart-message i {
            font-size: 50px;
            margin-bottom: 15px;
            opacity: 0.5;
            color: var(--accent);
        }
        
        .empty-cart-message p {
            font-size: 18px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .empty-cart-message small {
            font-size: 14px;
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
    </header>
    
    <!-- Category Navigation (now a separate fixed element) -->
    <div class="header-spacer"></div>
    <div class="category-nav-container">
        <div class="category-nav" id="category-nav">
            <button class="active" data-category="all">
                All Items
                <?php $totalItems = 0; foreach ($categories as $cat) { $totalItems += count(getMenuItemsByCategory($cat['id'])); } ?>
                <span class="category-count"><?php echo $totalItems; ?></span>
            </button>
            <?php foreach ($categories as $category): 
                $itemCount = count(getMenuItemsByCategory($category['id']));
            ?>
                <button data-category="<?php echo $category['id']; ?>">
                    <?php echo $category['name']; ?>
                    <span class="category-count"><?php echo $itemCount; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Menu Sections -->
        <?php foreach ($categories as $category): ?>
            <div class="menu-section" data-category="<?php echo $category['id']; ?>">
                <h2>
                    <?php echo $category['name']; ?>
                    <?php $itemCount = count(getMenuItemsByCategory($category['id'])); ?>
                </h2>
                <div class="menu-items">
                    <?php 
                    $menuItems = getMenuItemsByCategory($category['id']);
                    foreach ($menuItems as $item): 
                        $itemImage = $item['image'] ? '../Uploads/' . $item['image'] : DEFAULT_IMAGE;
                        $isVeg = isset($item['is_vegetarian']) && $item['is_vegetarian'];
                        $isPopular = isset($item['is_popular']) && $item['is_popular'];
                    ?>
                        <div class="menu-item" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
                            <div class="menu-item-image">
                                <img src="<?php echo $itemImage; ?>" alt="<?php echo $item['name']; ?>">
                                <?php if (isset($item['is_vegetarian']) && $item['is_vegetarian']): ?>
                                <div class="food-type-indicator veg-indicator" title="Vegetarian"></div>
                                <?php else: ?>
                                <div class="food-type-indicator non-veg-indicator" title="Non-Vegetarian"></div>
                                <?php endif; ?>
                                <?php if (isset($item['is_popular']) && $item['is_popular']): ?>
                                <div class="bestseller-tag">Bestseller</div>
                                <?php endif; ?>
                            </div>
                            <div class="menu-item-info">
                                <?php if (isset($item['weight'])): ?>
                                <div class="menu-item-meta">
                                    <span class="menu-item-weight"><?php echo $item['weight']; ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <h3 class="menu-item-name"><?php echo $item['name']; ?></h3>
                                
                                <?php if (isset($item['description']) && !empty($item['description'])): ?>
                                <p class="menu-item-description"><?php echo $item['description']; ?></p>
                                <?php endif; ?>
                                
                                <?php if (isset($item['rating'])): ?>
                                <div class="rating-container">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-count">(<?php echo $item['rating_count'] ?? '24'; ?>)</span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($item['delivery_time'])): ?>
                                <div class="delivery-time">
                                    <i class="fas fa-clock"></i> <?php echo $item['delivery_time']; ?> min
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($item['discount']) && $item['discount'] > 0): ?>
                                <div class="discount"><?php echo $item['discount']; ?>% OFF</div>
                                <div class="price-container">
                                    <span class="current-price"><?php echo number_format($item['price'], 2); ?></span>
                                    <span class="original-price"><?php echo number_format(round($item['price'] * (100 / (100 - $item['discount'])), 2), 2); ?></span>
                                </div>
                                <?php else: ?>
                                <div class="price-container">
                                    <span class="current-price"><?php echo number_format($item['price'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($item['unit_price'])): ?>
                                <div class="unit-price"><?php echo $item['unit_price']; ?> per kg</div>
                                <?php endif; ?>
                            </div>
                            <div class="menu-item-footer">
                                <button class="add-to-cart">
                                    <i class="fas fa-plus"></i>ADD
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
                        <span>Total Amount:</span>
                        <span id="cart-total">₹0.00</span>
                    </div>
                    <form id="place-order-form" class="place-order-form">
                        <div class="form-group">
                            <label for="table-number"><i class="fas fa-utensils"></i> Table Number</label>
                            <input type="number" id="table-number" required min="1" max="99" placeholder="Enter your table number">
                        </div>
                        <button type="submit" class="place-order-btn">
                            <i class="fas fa-check-circle"></i> Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ad Footer -->
    <div class="ad-footer">
        <div class="ad-container">
            <div class="ad-slider">
                <div class="ad-slide">
                    <img src="../assets/images/ads/ad1.png" alt="Special offer - Zero9communication.com">
                </div>
                <div class="ad-slide">
                    <img src="../assets/images/ads/ad2.png" alt="Visit Zero9communication.com">
                </div>
                <div class="ad-slide">
                    <img src="../assets/images/ads/ad3.png" alt="Zero9communication.com services">
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
    <script src="../assets/js/ad-slider.js"></script>
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