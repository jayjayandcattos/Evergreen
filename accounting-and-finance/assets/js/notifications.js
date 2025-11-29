/**
 * Notifications Loader
 * Dynamically loads notifications from activity_logs via API
 */

(function() {
    'use strict';
    
    // Configuration - determine API path based on current location
    const currentPath = window.location.pathname;
    const isCorePage = currentPath.includes('/core/');
    const NOTIFICATIONS_API = isCorePage ? '../modules/api/notifications.php' : '../modules/api/notifications.php';
    const REFRESH_INTERVAL = 30000; // 30 seconds
    const MAX_NOTIFICATIONS = 10;
    
    let refreshTimer = null;
    
    /**
     * Initialize notifications on page load
     */
    function initNotifications() {
        console.log('Initializing notifications...');
        console.log('API Path:', NOTIFICATIONS_API);
        loadNotifications();
        
        // Set up auto-refresh
        if (refreshTimer) {
            clearInterval(refreshTimer);
        }
        refreshTimer = setInterval(loadNotifications, REFRESH_INTERVAL);
    }
    
    /**
     * Load notifications from API
     */
    function loadNotifications() {
        const url = NOTIFICATIONS_API + '?limit=' + MAX_NOTIFICATIONS;
        console.log('Loading notifications from:', url);
        
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Notifications data received:', data);
                if (data.success) {
                    updateNotificationBadge(data.count);
                    updateNotificationDropdown(data.notifications);
                } else {
                    console.error('Failed to load notifications:', data.message);
                    handleNotificationError();
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                console.error('Error details:', error.message);
                handleNotificationError();
            });
    }
    
    /**
     * Update notification badge count
     */
    function updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        }
    }
    
    /**
     * Update notification dropdown content
     */
    function updateNotificationDropdown(notifications) {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (!dropdown) {
            console.warn('Notifications dropdown not found in DOM');
            return;
        }
        
        console.log('Updating dropdown with', notifications.length, 'notifications');
        
        // Find the container (after header and divider)
        const header = dropdown.querySelector('.dropdown-header');
        const firstDivider = dropdown.querySelector('hr');
        
        // Clear existing notifications (keep header and dividers)
        // Remove all items except header and "View All" link
        const allItems = dropdown.querySelectorAll('li');
        allItems.forEach(item => {
            // Keep header
            if (item.classList.contains('dropdown-header')) {
                return;
            }
            // Keep "View All Notifications" link
            const link = item.querySelector('a');
            if (link && link.textContent.includes('View All Notifications')) {
                return;
            }
            // Remove everything else
            item.remove();
        });
        
        // Remove all dividers except the last one (before "View All")
        const dividers = dropdown.querySelectorAll('hr');
        if (dividers.length > 1) {
            // Keep first and last divider
            for (let i = 1; i < dividers.length - 1; i++) {
                dividers[i].remove();
            }
        }
        
        // Get current page path to determine correct activity-log.php path
        const currentPath = window.location.pathname;
        const isCorePage = currentPath.includes('/core/');
        const activityLogPath = isCorePage ? '../modules/activity-log.php' : 'activity-log.php';
        
        // Ensure we have a divider after header
        if (!firstDivider) {
            const divider = document.createElement('li');
            divider.innerHTML = '<hr class="dropdown-divider">';
            dropdown.appendChild(divider);
        }
        
        // Insert new notifications
        if (notifications.length === 0) {
            // Show "no notifications" message
            const noNotifications = document.createElement('li');
            noNotifications.className = 'dropdown-item text-center text-muted';
            noNotifications.innerHTML = '<small>No new notifications</small>';
            
            const divider = dropdown.querySelector('hr');
            if (divider && divider.nextSibling) {
                dropdown.insertBefore(noNotifications, divider.nextSibling);
            } else if (divider) {
                dropdown.insertBefore(noNotifications, divider);
            } else {
                dropdown.appendChild(noNotifications);
            }
        } else {
            notifications.forEach((notification, index) => {
                const listItem = document.createElement('li');
                listItem.className = 'dropdown-item notification-item';
                
                const link = document.createElement('a');
                link.href = '#';
                link.className = 'dropdown-item notification-item';
                link.style.textDecoration = 'none';
                link.onclick = function(e) {
                    e.preventDefault();
                    // Could navigate to specific activity log entry if needed
                    window.location.href = activityLogPath;
                };
                
                link.innerHTML = `
                    <i class="fas ${notification.icon} ${notification.color}"></i>
                    <div class="notification-content">
                        <strong>${escapeHtml(notification.title)}</strong>
                        <small>${escapeHtml(notification.details)}</small>
                        <br><small class="text-muted" style="font-size: 0.75rem;">${notification.time_ago}</small>
                    </div>
                `;
                
                listItem.appendChild(link);
                
                // Insert after first divider (or at the end if no divider)
                const divider = dropdown.querySelector('hr');
                if (divider && divider.nextSibling) {
                    dropdown.insertBefore(listItem, divider.nextSibling);
                } else if (divider) {
                    dropdown.insertBefore(listItem, divider);
                } else {
                    dropdown.appendChild(listItem);
                }
                
                // Add divider between notifications (except after last one)
                if (index < notifications.length - 1) {
                    const itemDivider = document.createElement('li');
                    itemDivider.innerHTML = '<hr class="dropdown-divider">';
                    dropdown.insertBefore(itemDivider, listItem.nextSibling);
                }
            });
        }
        
        // Ensure "View All Notifications" link exists
        const viewAllLink = dropdown.querySelector('a[href*="activity-log"]');
        if (!viewAllLink) {
            // Create the link if it doesn't exist
            const divider = document.createElement('li');
            divider.innerHTML = '<hr class="dropdown-divider">';
            dropdown.appendChild(divider);
            
            const viewAll = document.createElement('li');
            const link = document.createElement('a');
            link.className = 'dropdown-item text-center small';
            link.href = activityLogPath;
            link.textContent = 'View All Notifications';
            viewAll.appendChild(link);
            dropdown.appendChild(viewAll);
        } else {
            // Update existing link
            viewAllLink.href = activityLogPath;
        }
    }
    
    /**
     * Handle notification loading error
     */
    function handleNotificationError() {
        // Show error state but don't break the UI
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.style.display = 'none';
        }
        
        // Update dropdown to show error message
        const dropdown = document.querySelector('.notifications-dropdown');
        if (dropdown) {
            const loadingMsg = dropdown.querySelector('.text-muted');
            if (loadingMsg && loadingMsg.textContent.includes('Loading')) {
                loadingMsg.innerHTML = '<small>Unable to load notifications</small>';
            }
        }
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
    }
    
    /**
     * Clean up on page unload
     */
    function cleanup() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifications);
    } else {
        initNotifications();
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanup);
    
})();

