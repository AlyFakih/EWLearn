/**
 * Notifications Management
 */
 
class NotificationSystem {
    constructor() {
        this.notificationCount = 0;
        this.notifications = [];
        this.container = null;
        this.countElement = null;
        this.bellElement = null;
        this.listElement = null;
        this.isInitialized = false;
    }
    
    /**
     * Initialize the notification system
     */
    init() {
        // Only initialize once
        if (this.isInitialized) return;
        
        // Create container if it doesn't exist
        if (!document.querySelector('.notifications-container')) {
            this.createNotificationUI();
        }
        
        // Set UI references
        this.container = document.querySelector('.notifications-container');
        this.countElement = document.querySelector('.notification-count');
        this.bellElement = document.querySelector('.notification-bell');
        this.listElement = document.querySelector('.notification-list');
        
        // Add event listeners
        this.bellElement.addEventListener('click', this.toggleNotifications.bind(this));
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        
        // Bind action handlers
        document.querySelector('.notification-mark-all').addEventListener('click', this.markAllAsRead.bind(this));
        
        // Load initial data
        this.loadNotifications();
        this.updateCount();
        
        // Set up polling for new notifications (check every 30 seconds)
        setInterval(() => {
            this.updateCount();
        }, 30000);
        
        this.isInitialized = true;
    }
    
    /**
     * Create the notification UI components
     */
    createNotificationUI() {
        // Create the notification bell icon
        const bell = document.createElement('div');
        bell.className = 'notification-bell';
        bell.innerHTML = `
            <i class="fas fa-bell"></i>
            <span class="notification-count">0</span>
        `;
        
        // Create the notification container
        const container = document.createElement('div');
        container.className = 'notifications-container';
        container.innerHTML = `
            <div class="notification-header">
                <h3>Notifications</h3>
                <div class="notification-actions">
                    <a class="notification-mark-all">Mark all as read</a>
                </div>
            </div>
            <div class="notification-list">
                <!-- Notification items will be inserted here -->
            </div>
        `;
        
        // Add to the page, position relative to header or navbar
        const navbar = document.querySelector('.menu') || document.querySelector('header');
        if (navbar) {
            navbar.appendChild(bell);
            navbar.appendChild(container);
        } else {
            // Fallback to body if no navbar found
            document.body.appendChild(bell);
            document.body.appendChild(container);
        }
    }
    
    /**
     * Toggle notifications visibility
     */
    toggleNotifications(event) {
        event.stopPropagation();
        if (this.container.style.display === 'block') {
            this.container.style.display = 'none';
        } else {
            this.container.style.display = 'block';
            this.loadNotifications();
        }
    }
    
    /**
     * Handle clicks outside the notification container
     */
    handleOutsideClick(event) {
        if (this.container && 
            this.container.style.display === 'block' && 
            !this.container.contains(event.target) && 
            !this.bellElement.contains(event.target)) {
            this.container.style.display = 'none';
        }
    }
    
    /**
     * Load notifications from the server
     */
    loadNotifications() {
        fetch('../common/notification_api.php?action=get&limit=15')
            .then(response => response.json())
            .then(data => {
                this.notifications = data;
                this.renderNotifications();
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
            });
    }
    
    /**
     * Update the notification count
     */
    updateCount() {
        fetch('../common/notification_api.php?action=count')
            .then(response => response.json())
            .then(data => {
                this.notificationCount = data.count;
                this.countElement.textContent = this.notificationCount > 99 ? '99+' : this.notificationCount;
                
                // Hide count if zero
                if (this.notificationCount == 0) {
                    this.countElement.style.display = 'none';
                } else {
                    this.countElement.style.display = 'flex';
                }
            })
            .catch(error => {
                console.error('Error updating notification count:', error);
            });
    }
    
    /**
     * Render notifications in the list
     */
    renderNotifications() {
        if (this.notifications.length === 0) {
            this.listElement.innerHTML = `
                <div class="notification-empty">
                    No notifications
                </div>
            `;
            return;
        }
        
        let html = '';
        
        this.notifications.forEach(notification => {
            // Get appropriate icon based on notification type
            let iconClass = 'fas fa-bell';
            if (notification.type === 'assignment') iconClass = 'fas fa-tasks';
            else if (notification.type === 'grade') iconClass = 'fas fa-graduation-cap';
            else if (notification.type === 'attendance') iconClass = 'fas fa-user-check';
            else if (notification.type === 'announcement') iconClass = 'fas fa-bullhorn';
            
            // Format time
            const createdDate = new Date(notification.created_at);
            const timeAgo = this.getTimeAgo(createdDate);
            
            html += `
                <div class="notification-item ${notification.is_read == 0 ? 'unread' : ''}" data-id="${notification.id}">
                    <div class="notification-icon">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${timeAgo}</div>
                        <div class="notification-actions-item">
                            <span class="notification-action mark-read" data-id="${notification.id}">
                                ${notification.is_read == 0 ? 'Mark as read' : 'Mark as unread'}
                            </span>
                            <span class="notification-action delete-notification" data-id="${notification.id}">
                                Delete
                            </span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        this.listElement.innerHTML = html;
        
        // Add event listeners for actions
        document.querySelectorAll('.mark-read').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.getAttribute('data-id');
                this.markAsRead(id);
            });
        });
        
        document.querySelectorAll('.delete-notification').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.getAttribute('data-id');
                this.deleteNotification(id);
            });
        });
        
        // Add click event to notification items to mark them as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (!e.target.classList.contains('notification-action') && item.classList.contains('unread')) {
                    const id = item.getAttribute('data-id');
                    this.markAsRead(id);
                }
            });
        });
    }
    
    /**
     * Mark a notification as read
     */
    markAsRead(notificationId) {
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        
        fetch('../common/notification_api.php?action=mark_read', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadNotifications();
                this.updateCount();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }
    
    /**
     * Mark all notifications as read
     */
    markAllAsRead() {
        fetch('../common/notification_api.php?action=mark_all_read', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadNotifications();
                this.updateCount();
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
        });
    }
    
    /**
     * Delete a notification
     */
    deleteNotification(notificationId) {
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        
        fetch('../common/notification_api.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadNotifications();
                this.updateCount();
            }
        })
        .catch(error => {
            console.error('Error deleting notification:', error);
        });
    }
    
    /**
     * Get time ago string from date
     */
    getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) {
            return interval + " year" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) {
            return interval + " month" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) {
            return interval + " day" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) {
            return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 60);
        if (interval >= 1) {
            return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
        }
        
        return Math.floor(seconds) + " second" + (seconds === 1 ? "" : "s") + " ago";
    }
}

// Initialize the notification system when the page is loaded
document.addEventListener('DOMContentLoaded', function() {
    const notificationSystem = new NotificationSystem();
    notificationSystem.init();
    
    // Make it globally accessible
    window.notificationSystem = notificationSystem;
});
