/**
 * Notifications JavaScript
 * Handles AJAX interactions for the notification system
 */

// Verificar se estamos executando como PWA
function isPwa() {
    return window.matchMedia('(display-mode: standalone)').matches || 
           window.navigator.standalone || 
           document.referrer.includes('android-app://');
}

// Garantir que a sessão seja mantida em PWA
function checkSession() {
    if (isPwa()) {
        fetch('index.php?route=dashboard', { 
            method: 'HEAD',
            credentials: 'include'
        })
        .catch(error => {
            console.error('Erro ao verificar sessão:', error);
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Verificar sessão a cada 5 minutos para manter ativa em modo PWA
    if (isPwa()) {
        setInterval(checkSession, 300000); // 5 minutos
        checkSession(); // Verificar imediatamente
    }
    // Add has-unread class to notification bell if there are unread notifications
    const notificationBadge = document.querySelector('.notification-badge');
    const notificationBell = document.querySelector('.notification-bell');
    
    if (notificationBadge) {
        notificationBell.classList.add('has-unread');
    }
    
    // Mark individual notification as read when clicked
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            const notificationId = this.getAttribute('data-notification-id');
            const userId = this.getAttribute('data-user-id');
            
            if (notificationId && userId) {
                markNotificationAsRead(notificationId, userId);
            }
        });
    });
    
    // Mark all notifications as read
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            
            if (userId) {
                markAllNotificationsAsRead(userId);
            }
        });
    }
    
    // Function to mark a notification as read
    function markNotificationAsRead(notificationId, userId) {
        fetch('index.php?route=api-mark-notification-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Notification marked as read');
            } else {
                console.error('Error marking notification as read:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Function to mark all notifications as read
    function markAllNotificationsAsRead(userId) {
        fetch('index.php?route=api-mark-all-notifications-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('All notifications marked as read');
                
                // Update UI
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Remove badge and animation
                if (notificationBadge) {
                    notificationBadge.style.display = 'none';
                }
                
                if (notificationBell) {
                    notificationBell.classList.remove('has-unread');
                }
                
                // Remove the mark all as read button
                if (markAllReadBtn) {
                    markAllReadBtn.style.display = 'none';
                }
            } else {
                console.error('Error marking all notifications as read:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Optional: Check for new notifications every 30 seconds
    function checkForNewNotifications() {
        const userId = document.querySelector('[data-user-id]')?.getAttribute('data-user-id');
        
        if (userId) {
            fetch(`index.php?route=api-get-unread-notifications-count&user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const unreadCount = data.count;
                    
                    // Update badge count
                    if (unreadCount > 0) {
                        if (notificationBadge) {
                            notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                            notificationBadge.style.display = 'block';
                        } else {
                            // Create badge if it doesn't exist
                            const newBadge = document.createElement('span');
                            newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge';
                            newBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                            
                            const bellParent = notificationBell.parentElement;
                            bellParent.appendChild(newBadge);
                        }
                        
                        notificationBell.classList.add('has-unread');
                    }
                }
            })
            .catch(error => {
                console.error('Error checking for new notifications:', error);
            });
        }
    }
    
    // Run notification check every 30 seconds
    setInterval(checkForNewNotifications, 30000);
});