            </div> <!-- End of flex-content-wrapper div -->
            </div> <!-- End of content container div -->
        </main> <!-- End of main content -->
        
        <!-- Footer -->
        <footer class="footer bg-white border-t border-slate-200 fixed bottom-0 left-256 right-0 h-14 z-30 flex items-center">
            <div class="flex flex-col sm:flex-row justify-between items-center w-full px-6">
                <p class="text-xs sm:text-sm text-slate-600 text-center sm:text-left">&copy; <?php echo date('Y'); ?> <?php echo $settings['restaurant_name']; ?> - Admin Dashboard</p>
                <div class="flex items-center gap-2 sm:gap-4 mt-2 sm:mt-0">
                    <a href="../customer/" target="_blank" class="text-xs sm:text-sm text-violet-600 hover:text-violet-800 transition-colors">View Store</a>
                    <span class="text-slate-300">|</span>
                    <a href="settings.php" class="text-xs sm:text-sm text-violet-600 hover:text-violet-800 transition-colors">Settings</a>
                    <span class="text-slate-300">|</span>
                    <a href="logout.php" class="text-xs sm:text-sm text-violet-600 hover:text-violet-800 transition-colors">Logout</a>
                </div>
            </div>
        </footer>
    </div> <!-- End of flex container div -->
    
    <style>
    @media (max-width: 768px) {
        .footer {
            left: 0 !important;
            width: 100% !important;
        }
    }
    </style>
    
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-3"></div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown functionality
            const userDropdown = document.getElementById('user-dropdown');
            const dropdownMenu = document.getElementById('dropdown-menu');
            
            if (userDropdown && dropdownMenu) {
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('hidden');
                    dropdownMenu.classList.toggle('dropdown-fade-in');
                    const chevron = userDropdown.querySelector('.fa-chevron-down');
                    if (chevron) {
                        chevron.classList.toggle('rotate-180');
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target)) {
                        dropdownMenu.classList.add('hidden');
                        const chevron = userDropdown.querySelector('.fa-chevron-down');
                        if (chevron) {
                            chevron.classList.remove('rotate-180');
                        }
                    }
                });
            }
            
            // Sidebar toggle functionality
            const toggleButton = document.getElementById('toggle-sidebar');
            const closeButton = document.getElementById('close-sidebar');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            function openSidebar() {
                if (sidebar) {
                    sidebar.classList.add('sidebar-open');
                    document.body.style.overflow = 'hidden';
                }
                
                if (overlay) {
                    overlay.classList.add('overlay-visible');
                }
            }
            
            function closeSidebar() {
                if (sidebar) {
                    sidebar.classList.remove('sidebar-open');
                    document.body.style.overflow = '';
                }
                
                if (overlay) {
                    overlay.classList.remove('overlay-visible');
                }
            }
            
            if (toggleButton) {
                toggleButton.addEventListener('click', openSidebar);
            }
            
            if (closeButton) {
                closeButton.addEventListener('click', closeSidebar);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }
            
            // Responsive behavior
            function handleResize() {
                if (window.innerWidth >= 768) {
                    if (sidebar) {
                        sidebar.classList.remove('sidebar-open');
                    }
                    if (overlay) {
                        overlay.classList.remove('overlay-visible');
                    }
                    document.body.style.overflow = '';
                }
            }
            
            window.addEventListener('resize', handleResize);
            
            // Toast notification functionality
            window.showToast = function(message, type = 'success') {
                const toastContainer = document.getElementById('toast-container');
                if (!toastContainer) return;
                
                const toast = document.createElement('div');
                toast.className = `px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 text-sm sm:text-base max-w-[90vw] sm:max-w-md ${
                    type === 'success' ? 'bg-emerald-100 text-emerald-800 border-l-4 border-emerald-500' : 
                    type === 'error' ? 'bg-red-100 text-red-800 border-l-4 border-red-500' : 
                    'bg-blue-100 text-blue-800 border-l-4 border-blue-500'
                }`;
                
                const icon = document.createElement('span');
                icon.className = 'text-lg';
                icon.innerHTML = type === 'success' ? '<i class="fas fa-check-circle"></i>' : 
                                type === 'error' ? '<i class="fas fa-times-circle"></i>' : 
                                '<i class="fas fa-info-circle"></i>';
                
                const text = document.createElement('span');
                text.className = 'flex-1';
                text.textContent = message;
                
                const closeBtn = document.createElement('button');
                closeBtn.className = 'text-slate-500 hover:text-slate-700';
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.onclick = function() {
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                };
                
                toast.appendChild(icon);
                toast.appendChild(text);
                toast.appendChild(closeBtn);
                
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                
                toastContainer.appendChild(toast);
                
                // Fade in
                setTimeout(() => {
                    toast.style.opacity = '1';
                }, 10);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 5000);
            };
        });
    </script>
</body>
</html> 