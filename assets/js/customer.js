/**
 * Customer-side JavaScript
 * Handles menu filtering, cart functionality, and order placement
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let cart = [];
    const cartButton = document.getElementById('cart-button');
    const cartModal = document.getElementById('cart-modal');
    const cartCount = document.getElementById('cart-count');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const closeModalButton = document.getElementById('close-modal');
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const closeSidebar = document.getElementById('close-sidebar');
    const placeOrderForm = document.getElementById('place-order-form');
    
    // Category filter functionality
    const categoryButtons = document.querySelectorAll('.category-nav button, .sidebar-category');
    const menuSections = document.querySelectorAll('.menu-section');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            const category = button.getAttribute('data-category');
            
            // Update active class on buttons
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll(`[data-category="${category}"]`).forEach(btn => {
                btn.classList.add('active');
            });
            
            // Show/hide menu sections
            if (category === 'all') {
                menuSections.forEach(section => section.style.display = 'block');
            } else {
                menuSections.forEach(section => {
                    if (section.getAttribute('data-category') === category) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }
            
            // Close sidebar on mobile after selection
            closeSidebarMenu();
        });
    });
    
    // Mobile sidebar menu functionality
    menuToggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('open');
    });
    
    closeSidebar.addEventListener('click', closeSidebarMenu);
    overlay.addEventListener('click', closeSidebarMenu);
    
    function closeSidebarMenu() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }
    
    // Cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', () => {
            const menuItem = button.closest('.menu-item');
            const id = menuItem.getAttribute('data-id');
            const name = menuItem.querySelector('.menu-item-name').textContent;
            const price = parseFloat(menuItem.getAttribute('data-price'));
            
            addToCart(id, name, price);
            showToast(`${name} added to cart`, 'success');
        });
    });
    
    // Add to cart function
    function addToCart(id, name, price) {
        // Check if item is already in cart
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({
                id: id,
                name: name,
                price: price,
                quantity: 1
            });
        }
        
        // Update cart count
        updateCartCount();
        
        // Save cart to local storage
        saveCart();
    }
    
    // Update cart items count
    function updateCartCount() {
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = count;
        
        if (count > 0) {
            cartButton.classList.add('has-items');
        } else {
            cartButton.classList.remove('has-items');
        }
    }
    
    // Save cart to localStorage
    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
    }
    
    // Load cart from localStorage
    function loadCart() {
        const savedCart = localStorage.getItem('cart');
        
        if (savedCart) {
            cart = JSON.parse(savedCart);
            updateCartCount();
        }
    }
    
    // Initialize cart from localStorage
    loadCart();
    
    // Open cart modal
    cartButton.addEventListener('click', () => {
        updateCartUI();
        cartModal.classList.add('show');
    });
    
    // Close cart modal
    closeModalButton.addEventListener('click', () => {
        cartModal.classList.remove('show');
    });
    
    // Close modal when clicking outside
    cartModal.addEventListener('click', event => {
        if (event.target === cartModal) {
            cartModal.classList.remove('show');
        }
    });
    
    // Update cart UI
    function updateCartUI() {
        cartItems.innerHTML = '';
        
        if (cart.length === 0) {
            cartItems.innerHTML = '<div class="empty-cart-message">Your cart is empty</div>';
            document.getElementById('checkout-section').style.display = 'none';
        } else {
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                const cartItemElement = document.createElement('div');
                cartItemElement.className = 'cart-item';
                cartItemElement.innerHTML = `
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn decrease" data-index="${index}">-</button>
                        <span class="quantity-value">${item.quantity}</span>
                        <button class="quantity-btn increase" data-index="${index}">+</button>
                        <button class="remove-item" data-index="${index}">×</button>
                    </div>
                `;
                
                cartItems.appendChild(cartItemElement);
            });
            
            cartTotal.textContent = `$${total.toFixed(2)}`;
            document.getElementById('checkout-section').style.display = 'block';
        }
        
        // Add event listeners to quantity buttons
        attachCartItemEvents();
    }
    
    // Attach event listeners to cart item buttons
    function attachCartItemEvents() {
        // Increase quantity
        document.querySelectorAll('.quantity-btn.increase').forEach(button => {
            button.addEventListener('click', () => {
                const index = button.getAttribute('data-index');
                cart[index].quantity++;
                updateCartUI();
                updateCartCount();
                saveCart();
            });
        });
        
        // Decrease quantity
        document.querySelectorAll('.quantity-btn.decrease').forEach(button => {
            button.addEventListener('click', () => {
                const index = button.getAttribute('data-index');
                if (cart[index].quantity > 1) {
                    cart[index].quantity--;
                } else {
                    cart.splice(index, 1);
                }
                updateCartUI();
                updateCartCount();
                saveCart();
            });
        });
        
        // Remove item
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', () => {
                const index = button.getAttribute('data-index');
                const itemName = cart[index].name;
                cart.splice(index, 1);
                updateCartUI();
                updateCartCount();
                saveCart();
                showToast(`${itemName} removed from cart`, 'error');
            });
        });
    }
    
    // Place order form submission
    if (placeOrderForm) {
        placeOrderForm.addEventListener('submit', event => {
            event.preventDefault();
            
            const tableNumber = document.getElementById('table-number').value;
            
            if (!tableNumber) {
                showToast('Please enter your table number', 'error');
                return;
            }
            
            if (cart.length === 0) {
                showToast('Your cart is empty', 'error');
                return;
            }
            
            // Show loading spinner
            document.getElementById('loading').classList.add('show');
            
            // Prepare order data
            const orderData = {
                table_number: tableNumber,
                items: cart,
                total: parseFloat(cartTotal.textContent.replace('$', ''))
            };
            
            // Send order to server
            fetch('place_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').classList.remove('show');
                
                if (data.success) {
                    showToast('Order placed successfully!', 'success');
                    cart = [];
                    updateCartCount();
                    updateCartUI();
                    saveCart();
                    cartModal.classList.remove('show');
                } else {
                    showToast(data.message || 'Error placing order', 'error');
                }
            })
            .catch(error => {
                document.getElementById('loading').classList.remove('show');
                showToast('Error placing order. Please try again.', 'error');
                console.error('Error:', error);
            });
        });
    }
    
    // Toast notification function
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container');
        
        if (!toastContainer) {
            // Create toast container if it doesn't exist
            const newToastContainer = document.createElement('div');
            newToastContainer.id = 'toast-container';
            newToastContainer.className = 'toast-container';
            document.body.appendChild(newToastContainer);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        document.getElementById('toast-container').appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}); 