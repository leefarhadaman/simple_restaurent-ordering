/**
 * Enhanced Customer-side JavaScript for Restaurant Ordering System
 * Modern animations and interactions with improved cart functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const closeSidebar = document.getElementById('close-sidebar');
    const categoryButtons = document.querySelectorAll('.category-nav button');
    const sidebarCategories = document.querySelectorAll('.sidebar-category');
    const menuSections = document.querySelectorAll('.menu-section');
    const cartButton = document.getElementById('cart-button');
    const cartModal = document.getElementById('cart-modal');
    const closeModal = document.getElementById('close-modal');
    const cartItems = document.getElementById('cart-items');
    const cartCount = document.getElementById('cart-count');
    const cartTotal = document.getElementById('cart-total');
    const placeOrderForm = document.getElementById('place-order-form');
    const loading = document.getElementById('loading');
    const checkoutSection = document.getElementById('checkout-section');
    const tableNumberInput = document.getElementById('table-number');

    // Cart State
    let cart = [];
    let savedTableNumber = localStorage.getItem('table_number') || '';

    // Initialize App
    initializeApp();
    loadCartFromLocalStorage();
    updateCartDisplay();
    attachAddToCartListeners();
    initCategoryNavigation();
    initScrollEffects(); // Add scroll effects for category nav

    // If we have a saved table number, populate it
    if (tableNumberInput && savedTableNumber) {
        tableNumberInput.value = savedTableNumber;
    }

    // Initialize App function
    function initializeApp() {
        // Add animation classes to menu items with delay
        const menuItems = document.querySelectorAll('[data-id]');
        menuItems.forEach((item, index) => {
            const delay = index % 8 * 0.1;
            item.style.animationDelay = `${delay}s`;
        });

        // Add scroll reveal animation to menu sections
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeIn');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        menuSections.forEach(section => {
            observer.observe(section);
        });

        // Show all sections initially with animation
        initMenuSections();
    }

    // Event Listeners
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebarMenu);
    }
    
    if (closeSidebar) {
        closeSidebar.addEventListener('click', closeSidebarMenu);
    }

    // Save table number when changed
    if (tableNumberInput) {
        tableNumberInput.addEventListener('change', function() {
            localStorage.setItem('table_number', this.value);
        });
    }

    // Category navigation
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetCategory = this.dataset.category;
            
            // 1. Update active state
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // 2. Filter the menu
            filterMenuByCategory(targetCategory);
            
            // 3. Schedule the scrolling after animations are DEFINITELY complete
            // We need a longer delay to ensure CSS transitions finish and DOM updates
            setTimeout(() => {
                // First attempt - works in most cases
                scrollToCategory(targetCategory);
                
                // Second attempt as backup - handles edge cases
                setTimeout(() => {
                    scrollToCategory(targetCategory);
                }, 300);
            }, 500); // Longer initial delay to ensure DOM is definitely updated
        });
    });

    // Mobile sidebar categories
    sidebarCategories.forEach(category => {
        category.addEventListener('click', () => {
            const categoryName = category.dataset.category;
            filterMenuByCategory(categoryName);
            
            // Update active states
            sidebarCategories.forEach(cat => cat.classList.remove('active'));
            categoryButtons.forEach(btn => {
                if (btn.dataset.category === categoryName) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            category.classList.add('active');
            
            // Close sidebar after selection
            closeSidebarMenu();
        });
    });

    // REPLACE the existing add-to-cart event listeners with this function
    function attachAddToCartListeners() {
        // Use document event delegation for better performance and to handle dynamically added elements
        document.addEventListener('click', function(event) {
            // Check if the clicked element or any of its parents is an add-to-cart button
            const button = event.target.closest('.add-to-cart');
            
            if (button) {
                // Find menu item element
                const menuItem = button.closest('[data-id]');
                
                // Check if menuItem exists before accessing its attributes
                if (!menuItem) {
                    console.error('Could not find parent element with data-id attribute');
                    return;
                }
                
                const id = menuItem.getAttribute('data-id');
                const name = menuItem.querySelector('.menu-item-name')?.textContent || 'Unknown Item';
                const price = parseFloat(menuItem.getAttribute('data-price') || '0');
                const description = menuItem.querySelector('.menu-item-description')?.textContent || '';
                
                // Add to cart with visual feedback
                addToCart(id, name, price, description);
                
                // Visual feedback
                const originalText = button.innerHTML;
                const originalBgColor = button.style.backgroundColor;
                const originalColor = button.style.color;
                
                button.innerHTML = '<i class="fas fa-check"></i>ADDED';
                button.style.backgroundColor = '#4CAF50';
                button.style.color = 'white';
                button.style.borderColor = '#4CAF50';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = originalBgColor;
                    button.style.color = originalColor;
                    button.style.borderColor = '';
                }, 1500);
                
                showToast(`${name} added to cart`, 'success');
            }
        });
    }

    // Modal events
    if (cartButton) {
        cartButton.addEventListener('click', () => {
            if (cartModal) {
                updateCartUI();
                openCartModal();
            }
        });
    }

    if (closeModal) {
        closeModal.addEventListener('click', closeCartModal);
    }

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === cartModal) {
            closeCartModal();
        }
    });

    // Place order form submission
    if (placeOrderForm) {
        placeOrderForm.addEventListener('submit', handleOrderSubmission);
    }

    // Helper Functions
    function initMenuSections() {
        menuSections.forEach(section => {
            section.style.opacity = '0';
            section.style.display = 'block';
            setTimeout(() => {
                section.style.transition = 'opacity 0.5s ease';
                section.style.opacity = '1';
            }, 100);
        });
    }

    function toggleSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closeSidebarMenu() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = ''; // Re-enable scrolling
    }

    // Handle category filtering separately from scrolling
    function filterMenuByCategory(category) {
        if (category === 'all') {
            menuSections.forEach(section => {
                section.style.opacity = '0';
                section.style.display = 'block';
                setTimeout(() => {
                    section.style.opacity = '1';
                }, 50);
            });
        } else {
            menuSections.forEach(section => {
                if (section.getAttribute('data-category') === category) {
                    section.style.opacity = '0';
                    section.style.display = 'block';
                    setTimeout(() => {
                        section.style.opacity = '1';
                    }, 50);
                } else {
                    section.style.opacity = '0';
                    setTimeout(() => {
                        section.style.display = 'none';
                    }, 300);
                }
            });
        }
    }
    
    // Improved scrolling function with requestAnimationFrame for better timing
    function scrollToCategory(category) {
        // Use requestAnimationFrame to ensure we're working after browser paint
        requestAnimationFrame(() => {
            try {
                // Find the target element to scroll to
                let targetElement;
                
                if (category === 'all') {
                    // For "all", use the first visible section
                    targetElement = document.querySelector('.menu-section[style*="display: block"]');
                    if (!targetElement) {
                        targetElement = document.querySelector('.menu-section');
                    }
                } else {
                    // For specific category, find that section
                    targetElement = document.querySelector(`.menu-section[data-category="${category}"]`);
                }
                
                if (!targetElement) {
                    console.error('Target section not found for', category);
                    return;
                }
                
                // Get heights and calculate offset
                const header = document.querySelector('header');
                const navContainer = document.querySelector('.category-nav-container');
                
                const headerHeight = header ? header.offsetHeight : 80;
                const navHeight = navContainer ? navContainer.offsetHeight : 70;
                const offset = headerHeight + navHeight + 15; // Extra padding
                
                // Get element position
                const targetTop = targetElement.getBoundingClientRect().top + window.pageYOffset;
                
                // Execute the scroll
                window.scrollTo({
                    top: targetTop - offset,
                    behavior: 'smooth'
                });
                
                // Optional: Add a secondary backup scroll after a delay
                setTimeout(() => {
                    const updatedTop = targetElement.getBoundingClientRect().top + window.pageYOffset;
                    if (Math.abs(window.pageYOffset - (updatedTop - offset)) > 20) {
                        window.scrollTo({
                            top: updatedTop - offset,
                            behavior: 'smooth'
                        });
                    }
                }, 300);
            } catch (error) {
                console.error('Scrolling error:', error);
            }
        });
    }

    function addToCart(id, name, price, description = '') {
        // Check if item is already in cart
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({
                id: id,
                name: name,
                price: price,
                description: description,
                quantity: 1
            });
        }
        
        // Update cart count with animation
        updateCartCount();
        
        // Animate cart button
        cartButton.classList.add('pulse');
        setTimeout(() => {
            cartButton.classList.remove('pulse');
        }, 300);
        
        // Save cart to local storage
        saveCart();
    }

    function updateCartCount() {
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = count;
        
        if (count > 0) {
            cartButton.classList.add('has-items');
        } else {
            cartButton.classList.remove('has-items');
        }
    }

    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    function loadCartFromLocalStorage() {
        const savedCart = localStorage.getItem('cart');
        
        if (savedCart) {
            cart = JSON.parse(savedCart);
            updateCartCount();
        }
    }

    function updateCartDisplay() {
        if (cartCount) {
            updateCartCount();
        }
    }

    function openCartModal() {
        if (!cartModal) return;
        
        // Make sure the modal is not hidden
        cartModal.style.display = 'flex';
        cartModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Animate modal entrance
        setTimeout(() => {
            const modalContent = cartModal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.add('scale-100', 'opacity-100');
                modalContent.classList.remove('scale-95', 'opacity-0');
            }
        }, 10);
    }

    function closeCartModal() {
        if (!cartModal) return;
        
        const modalContent = cartModal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
        }
        
        document.body.style.overflow = '';
        
        setTimeout(() => {
            cartModal.style.display = 'none';
            cartModal.classList.add('hidden');
        }, 300);
    }

    function updateCartUI() {
        cartItems.innerHTML = '';
        
        if (cart.length === 0) {
            cartItems.innerHTML = `
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <small>Add some delicious items from our menu</small>
                </div>
            `;
            checkoutSection.style.display = 'none';
        } else {
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                const cartItemElement = document.createElement('div');
                cartItemElement.className = 'cart-item';
                cartItemElement.innerHTML = `
                    <div class="cart-item-content">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            ${item.description ? `<div class="cart-item-description">${item.description}</div>` : ''}
                            <div class="cart-item-price">${item.price.toFixed(2)}</div>
                        </div>
                        <div class="cart-item-quantity">
                            <button class="quantity-btn decrease" data-index="${index}">-</button>
                            <span class="quantity-value">${item.quantity}</span>
                            <button class="quantity-btn increase" data-index="${index}">+</button>
                        </div>
                    </div>
                    <button class="remove-item" data-index="${index}">Remove</button>
                `;
                
                // Add with animation
                cartItemElement.style.opacity = '0';
                cartItemElement.style.transform = 'translateY(10px)';
                cartItems.appendChild(cartItemElement);
                
                setTimeout(() => {
                    cartItemElement.style.transition = 'all 0.3s ease';
                    cartItemElement.style.opacity = '1';
                    cartItemElement.style.transform = 'translateY(0)';
                }, 50 * index); // Stagger animation for each item
            });
            
            cartTotal.textContent = `₹${total.toFixed(2)}`;
            checkoutSection.style.display = 'block';
            
            // Focus table number input if empty
            if (tableNumberInput && !tableNumberInput.value) {
                setTimeout(() => {
                    tableNumberInput.focus();
                }, 500);
            }
        }
        
        // Add event listeners to quantity buttons
        attachCartItemEvents();
    }

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
                
                // Animate removal
                const cartItem = button.closest('.cart-item');
                if (cartItem) {
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'translateX(20px)';
                
                    setTimeout(() => {
                        cart.splice(index, 1);
                        updateCartUI();
                        updateCartCount();
                        saveCart();
                    }, 300);
                } else {
                    // If animation doesn't work, just remove it immediately
                    cart.splice(index, 1);
                    updateCartUI();
                    updateCartCount();
                    saveCart();
                }
                
                showToast(`${itemName} removed from cart`, 'error');
            });
        });
    }

    function handleOrderSubmission(event) {
        event.preventDefault();
        
        const tableNumber = tableNumberInput.value.trim();
        
        if (!tableNumber) {
            showToast('Please enter your table number', 'error');
            tableNumberInput.focus();
            return;
        }
        
        if (cart.length === 0) {
            showToast('Your cart is empty', 'error');
            return;
        }
        
        // Show loading spinner
        loading.classList.add('show');
        
        // Prepare order data
        const orderData = {
            table_number: parseInt(tableNumber, 10),
            items: cart,
            total: parseFloat(cartTotal.textContent.replace('₹', ''))
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
            loading.classList.remove('show');
            
            if (data.success) {
                showToast('Order placed successfully!', 'success');
                cart = [];
                updateCartCount();
                updateCartUI();
                saveCart();
                
                // Save the table number for future use
                localStorage.setItem('table_number', tableNumber);
                
                // Show order confirmation animation
                showOrderConfirmation(data.orderId);
                
                // Close modal after delay
                setTimeout(() => {
                    closeCartModal();
                }, 3000);
            } else {
                showToast(data.message || 'Error placing order', 'error');
            }
        })
        .catch(error => {
            loading.classList.remove('show');
            showToast('Error placing order. Please try again.', 'error');
            console.error('Error:', error);
        });
    }

    function showOrderConfirmation(orderId) {
        const confirmation = document.createElement('div');
        confirmation.className = 'order-confirmation';
        confirmation.innerHTML = `
            <div class="success-animation">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h3>Order Placed!</h3>
            <p>Your food will be ready soon.</p>
            ${orderId ? `<p class="order-number">Order #${orderId}</p>` : ''}
        `;
        
        document.body.appendChild(confirmation);
        
        setTimeout(() => {
            confirmation.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            confirmation.classList.remove('show');
            setTimeout(() => {
                confirmation.remove();
            }, 500);
        }, 3000);
    }

    // Toast notification function
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        let bgColor, textColor, icon;
        
        switch(type) {
            case 'success':
                bgColor = 'bg-green-500';
                textColor = 'text-white';
                icon = '<i class="fas fa-check-circle mr-2"></i>';
                break;
            case 'error':
                bgColor = 'bg-red-500';
                textColor = 'text-white';
                icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
                break;
            default:
                bgColor = 'bg-blue-500';
                textColor = 'text-white';
                icon = '<i class="fas fa-info-circle mr-2"></i>';
        }
        
        toast.className = `toast ${type} fixed bottom-4 right-4 transform translate-x-full opacity-0 transition-all duration-300 py-2 px-4 rounded-md shadow-lg flex items-center max-w-xs`;
        toast.innerHTML = `${icon}${message}`;
        
        toastContainer.appendChild(toast);
        
        // Show toast with animation
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 10);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-0 right-0 p-4 z-50 flex flex-col-reverse gap-2';
        document.body.appendChild(container);
        return container;
    }

    // Category Navigation Scroll
    function initCategoryNavigation() {
        const categoryNav = document.getElementById('category-nav');
        const scrollLeftBtn = document.getElementById('scroll-left');
        const scrollRightBtn = document.getElementById('scroll-right');
        
        if (!categoryNav || !scrollLeftBtn || !scrollRightBtn) return;
        
        // Check if scroll is needed and show/hide buttons accordingly
        function updateScrollButtons() {
            const isScrollable = categoryNav.scrollWidth > categoryNav.clientWidth;
            
            if (isScrollable) {
                scrollLeftBtn.style.display = categoryNav.scrollLeft > 0 ? 'flex' : 'none';
                scrollRightBtn.style.display = 
                    categoryNav.scrollLeft < (categoryNav.scrollWidth - categoryNav.clientWidth - 5) ? 'flex' : 'none';
            } else {
                scrollLeftBtn.style.display = 'none';
                scrollRightBtn.style.display = 'none';
            }
        }
        
        // Scroll left when button is clicked
        scrollLeftBtn.addEventListener('click', () => {
            categoryNav.scrollBy({
                left: -200,
                behavior: 'smooth'
            });
        });
        
        // Scroll right when button is clicked
        scrollRightBtn.addEventListener('click', () => {
            categoryNav.scrollBy({
                left: 200,
                behavior: 'smooth'
            });
        });
        
        // Listen for scroll events to update button visibility
        categoryNav.addEventListener('scroll', updateScrollButtons);
        
        // Update on resize
        window.addEventListener('resize', updateScrollButtons);
        
        // Initial check
        updateScrollButtons();
    }

    // Add scroll effects to the category nav
    function initScrollEffects() {
        const categoryNavContainer = document.querySelector('.category-nav-container');
        if (categoryNavContainer) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    categoryNavContainer.classList.add('scrolled');
                } else {
                    categoryNavContainer.classList.remove('scrolled');
                }
            });
        }
    }
});