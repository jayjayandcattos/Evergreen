/**
 * Dashboard JavaScript
 * Modern interactive features for Evergreen Accounting & Finance
 */

(function() {
    'use strict';

    /**
     * Initialize all dashboard features when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initSmoothScrolling();
        initActiveNavLinks();
        initModuleCardInteractions();
        initNotifications();
        initDropdownAnimations();
        initLogoutConfirmation();
    });

    /**
     * Smooth scrolling for anchor links
     */
    function initSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                
                if (target) {
                    target.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            });
        });
    }

    /**
     * Set active state for navigation links
     */
    function initActiveNavLinks() {
        const currentLocation = window.location.pathname;
        
        document.querySelectorAll('.nav-link').forEach(link => {
            const href = link.getAttribute('href');
            
            // Check if link matches current page
            if (href && (href === currentLocation || currentLocation.includes(href))) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Add interactive features to module cards
     */
    function initModuleCardInteractions() {
        const moduleCards = document.querySelectorAll('.module-card');
        
        moduleCards.forEach((card, index) => {
            // Add ripple effect on click
            card.addEventListener('click', function(e) {
                if (!e.target.classList.contains('module-link')) {
                    const link = this.querySelector('.module-link');
                    if (link) {
                        window.location.href = link.getAttribute('href');
                    }
                }
            });

            // Add keyboard accessibility
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            
            card.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const link = this.querySelector('.module-link');
                    if (link) {
                        window.location.href = link.getAttribute('href');
                    }
                }
            });
        });
    }

    /**
     * Optional: Add loading animation for module icons
     */
    function addIconAnimation() {
        const icons = document.querySelectorAll('.module-icon i');
        
        icons.forEach(icon => {
            icon.style.transition = 'transform 0.3s ease';
            
            icon.parentElement.addEventListener('mouseenter', function() {
                icon.style.transform = 'scale(1.2) rotate(10deg)';
            });
            
            icon.parentElement.addEventListener('mouseleave', function() {
                icon.style.transform = 'scale(1) rotate(0deg)';
            });
        });
    }

    // Call optional animations
    addIconAnimation();

    /**
     * Notification badge animation
     */
    function initNotifications() {
        const notificationBadge = document.querySelector('.notification-badge');
        
        if (notificationBadge) {
            // Pulse animation for notification badge
            setInterval(function() {
                notificationBadge.style.animation = 'pulse 0.5s ease';
                setTimeout(function() {
                    notificationBadge.style.animation = '';
                }, 500);
            }, 5000); // Pulse every 5 seconds
        }
        
        // Mark notification as read on click
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                this.style.opacity = '0.6';
                
                // Update badge count
                if (notificationBadge) {
                    let count = parseInt(notificationBadge.textContent);
                    if (count > 0) {
                        notificationBadge.textContent = count - 1;
                        if (count - 1 === 0) {
                            notificationBadge.style.display = 'none';
                        }
                    }
                }
            });
        });
    }

    /**
     * Add smooth animations to dropdowns
     */
    function initDropdownAnimations() {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (toggle && menu) {
                toggle.addEventListener('show.bs.dropdown', function() {
                    menu.style.opacity = '0';
                    menu.style.transform = 'translateY(-10px)';
                    
                    setTimeout(function() {
                        menu.style.transition = 'all 0.3s ease';
                        menu.style.opacity = '1';
                        menu.style.transform = 'translateY(0)';
                    }, 10);
                });
                
                toggle.addEventListener('hide.bs.dropdown', function() {
                    menu.style.transition = 'all 0.2s ease';
                    menu.style.opacity = '0';
                    menu.style.transform = 'translateY(-10px)';
                });
            }
        });
    }

    /**
     * Add logout confirmation dialog
     */
    function initLogoutConfirmation() {
        const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
        
        logoutLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show confirmation dialog
                if (confirm('Are you sure you want to logout? Any unsaved changes will be lost.')) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    }

})();

