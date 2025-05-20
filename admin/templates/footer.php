        </div> <!-- End of .content-container -->
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $settings['restaurant_name']; ?> - Admin Dashboard</p>
                <div class="footer-links">
                    <a href="../customer/" target="_blank">View Store</a>
                    <span class="separator">|</span>
                    <a href="settings.php">Settings</a>
                    <span class="separator">|</span>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </footer>
    </div> <!-- End of .main-content -->
    
    <script src="../assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile responsive sidebar toggle
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
            
            // Modal functionality for all pages
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
        });
    </script>
</body>
</html> 