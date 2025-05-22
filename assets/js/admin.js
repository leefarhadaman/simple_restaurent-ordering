/**
 * Admin Panel JavaScript
 * Modern UI Implementation - Redesigned 2023
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle for responsive design
    const mobileToggle = document.getElementById('mobile-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const closeSidebar = document.querySelector('.close-sidebar');
    
    if (mobileToggle && sidebar && mainContent) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.add('mobile-open');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when sidebar is open
        });
        
        if (closeSidebar) {
            closeSidebar.addEventListener('click', function() {
                sidebar.classList.remove('mobile-open');
                document.body.style.overflow = ''; // Restore scrolling
            });
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInside = sidebar.contains(event.target) || 
                                 mobileToggle.contains(event.target);
            
            if (!isClickInside && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
                document.body.style.overflow = ''; // Restore scrolling
            }
        });
    }
    
    // User dropdown functionality
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdown && userDropdownMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if (userDropdownMenu.classList.contains('show')) {
                userDropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Modal functionality
    const modalTogglers = document.querySelectorAll('[data-toggle="modal"]');
    const modalClosers = document.querySelectorAll('[data-dismiss="modal"]');
    
    modalTogglers.forEach(toggler => {
        toggler.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            document.querySelector(target).classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        });
    });
    
    modalClosers.forEach(closer => {
        closer.addEventListener('click', function() {
            this.closest('.modal').classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        });
    });
    
    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Medium screen sidebar toggle (tablet view)
    if (window.matchMedia('(min-width: 768px) and (max-width: 991px)').matches) {
        const sidebarToggle = document.createElement('button');
        sidebarToggle.className = 'sidebar-expand-toggle';
        sidebarToggle.innerHTML = '<i class="fas fa-angle-right"></i>';
        sidebar.appendChild(sidebarToggle);
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('expanded');
            if (sidebar.classList.contains('expanded')) {
                this.innerHTML = '<i class="fas fa-angle-left"></i>';
            } else {
                this.innerHTML = '<i class="fas fa-angle-right"></i>';
            }
        });
    }
    
    // Image upload preview
    const imageUpload = document.getElementById('image');
    const imagePreview = document.querySelector('.image-preview');
    
    if (imageUpload && imagePreview) {
        imageUpload.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    // Clear existing content
                    imagePreview.innerHTML = '';
                    
                    // Create image element
                    const img = document.createElement('img');
                    img.src = this.result;
                    imagePreview.appendChild(img);
                });
                
                reader.readAsDataURL(file);
                
                // Check file size
                const maxSize = 500 * 1024; // 500KB
                if (file.size > maxSize) {
                    showAlert('Image size is larger than 500KB. It will be compressed upon upload.', 'warning');
                }
            }
        });
    }
    
    // Theme selection preview
    const themeOptions = document.querySelectorAll('.theme-option');
    const themeInput = document.getElementById('theme_color');
    
    if (themeOptions.length && themeInput) {
        themeOptions.forEach(option => {
            option.addEventListener('click', function() {
                const themeName = this.getAttribute('data-theme');
                
                // Update radio input
                themeInput.value = themeName;
                
                // Update active class
                themeOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                // Show save notification
                showAlert('Theme selected. Don\'t forget to save your changes.', 'info');
            });
        });
    }
    
    // Item availability toggle
    const availabilityToggles = document.querySelectorAll('.availability-toggle');
    
    if (availabilityToggles.length) {
        availabilityToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const itemId = this.getAttribute('data-id');
                const isAvailable = this.checked ? 1 : 0;
                const itemName = this.getAttribute('data-name') || 'Item';
                
                // Add loading indicator
                const row = this.closest('tr');
                if (row) row.classList.add('updating');
                
                // Send AJAX request to update availability
                fetch('update_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${itemId}&available=${isAvailable}`
                })
                .then(response => response.json())
                .then(data => {
                    if (row) row.classList.remove('updating');
                    
                    if (data.success) {
                        showAlert(`${itemName} availability updated successfully`, 'success');
                    } else {
                        showAlert(`Error updating ${itemName} availability`, 'danger');
                        // Revert toggle if failed
                        this.checked = !this.checked;
                    }
                })
                .catch(error => {
                    if (row) row.classList.remove('updating');
                    
                    console.error('Error:', error);
                    showAlert(`Error updating ${itemName} availability`, 'danger');
                    // Revert toggle if failed
                    this.checked = !this.checked;
                });
            });
        });
    }
    
    // Order status update
    const completeOrderButtons = document.querySelectorAll('.complete-order');
    
    completeOrderButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Mark this order as completed?')) {
                const orderId = this.getAttribute('data-id');
                const row = this.closest('tr');
                
                // Add loading state
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Send AJAX request
                fetch('update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}&status=completed`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row with animation
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        
                        setTimeout(() => {
                            row.remove();
                            
                            // Check if there are no more orders
                            const tbody = document.querySelector('tbody');
                            if (tbody && tbody.children.length === 0) {
                                const table = tbody.closest('table');
                                if (table) {
                                    table.innerHTML = `
                                        <div class="text-center p-8">
                                            <i class="fas fa-clipboard-check text-5xl text-slate-300 mb-4"></i>
                                            <p class="text-slate-500">No active orders at the moment.</p>
                                        </div>
                                    `;
                                }
                            }
                        }, 300);
                        
                        showAlert('Order marked as completed', 'success');
                    } else {
                        // Revert button state
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        showAlert(data.message || 'Error updating order status', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert button state
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    showAlert('Error updating order status', 'danger');
                });
            }
        });
    });
    
    // Password visibility toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    if (passwordToggles.length) {
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const passwordField = this.previousElementSibling;
                
                if (passwordField && passwordField.type) {
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        passwordField.type = 'password';
                        this.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                }
            });
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    if (forms.length) {
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Find the first invalid field and focus it
                    const invalidField = this.querySelector(':invalid');
                    if (invalidField) invalidField.focus();
                    
                    showAlert('Please check the form for errors', 'danger');
                }
                
                this.classList.add('was-validated');
            });
        });
    }
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete, [data-action="delete"]');
    
    if (deleteButtons.length) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                const itemName = this.getAttribute('data-name') || 'item';
                
                if (!confirm(`Are you sure you want to delete this ${itemName}? This action cannot be undone.`)) {
                    event.preventDefault();
                }
            });
        });
    }
    
    // Initialize any charts if Chart.js is available
    if (typeof Chart !== 'undefined' && document.getElementById('ordersChart')) {
        initializeCharts();
    }
    
    // Initialize datepickers if available
    if (typeof flatpickr !== 'undefined') {
        initializeDatepickers();
    }
});

// Initialize dashboard charts
function initializeCharts() {
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    
    // Sample data - in a real app, this would come from the backend
    const ordersChart = new Chart(ordersCtx, {
        type: 'line',
        data: {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            datasets: [{
                label: 'Orders',
                data: [12, 19, 15, 17, 21, 25, 18],
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    // If revenue chart exists
    if (document.getElementById('revenueChart')) {
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        
        const revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                datasets: [{
                    label: 'Revenue',
                    data: [120, 190, 150, 170, 210, 250, 180],
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

// Initialize datepickers
function initializeDatepickers() {
    flatpickr('.datepicker', {
        dateFormat: 'Y-m-d',
        allowInput: true
    });
    
    flatpickr('.datetimepicker', {
        dateFormat: 'Y-m-d H:i',
        enableTime: true,
        allowInput: true
    });
}

// Alert function
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alert-container');
    
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas ${getAlertIcon(type)}"></i>
        <span>${message}</span>
        <button type="button" class="close-alert">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Add show class after a small delay for animation
    setTimeout(() => {
        alert.classList.add('show');
    }, 10);
    
    // Close button functionality
    const closeBtn = alert.querySelector('.close-alert');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            closeAlert(alert);
        });
    }
    
    // Auto close after duration
    if (duration > 0) {
        setTimeout(() => {
            closeAlert(alert);
        }, duration);
    }
}

// Helper function to close alerts
function closeAlert(alert) {
    alert.classList.remove('show');
    
    // Remove from DOM after animation
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 300);
}

// Get appropriate icon for alert type
function getAlertIcon(type) {
    switch (type) {
        case 'success':
            return 'fa-check-circle';
        case 'danger':
            return 'fa-exclamation-circle';
        case 'warning':
            return 'fa-exclamation-triangle';
        case 'info':
        default:
            return 'fa-info-circle';
    }
} 