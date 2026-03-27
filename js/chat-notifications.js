// ============================================
// CHAT NOTIFICATION SYSTEM - FINAL VERSION
// Real-time notifications with sound alerts
// Fixed: Badge counter and user type comparison
// Fixed: Auto-mark messages as read when conversation already open
// ============================================

class ChatNotificationSystem {
    constructor() {
        this.unreadMessages = new Map();
        this.notificationPermission = 'default';
        this.isPageVisible = true;
        this.currentConversationId = null;
        this.seenMessages = this.loadSeenMessages();
        this.apiBasePath = this.detectApiPath();
        this.initialized = false;
        
        this.init();
    }

    loadSeenMessages() {
        try {
            const stored = localStorage.getItem('chatSeenMessages');
            if (stored) {
                const arr = JSON.parse(stored);
                return new Set(arr);
            }
        } catch (error) {
            console.error('Error loading seen messages:', error);
        }
        return new Set();
    }

    saveSeenMessages() {
        try {
            const arr = Array.from(this.seenMessages);
            localStorage.setItem('chatSeenMessages', JSON.stringify(arr));
        } catch (error) {
            console.error('Error saving seen messages:', error);
        }
    }

    detectApiPath() {
        const path = window.location.pathname;
        if (path.includes('/TAHANAN/')) {
            return '/TAHANAN/API';
        }
        return '/API';
    }

    init() {
        console.log('🔔 Notification system V2 starting...');
        console.log('API Base Path:', this.apiBasePath);
        
        this.requestNotificationPermission();
        this.setupVisibilityTracking();
        
        // Wait a bit for the page to fully load before starting polling
        setTimeout(() => {
            this.initialized = true;
            this.startNotificationPolling();
        }, 1000);
    }

    playNotificationSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (error) {
            console.log('Audio not supported');
        }
    }

    async requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            try {
                const permission = await Notification.requestPermission();
                this.notificationPermission = permission;
            } catch (error) {
                console.log('Notification permission denied');
            }
        } else if ('Notification' in window) {
            this.notificationPermission = Notification.permission;
        }
    }

    setupVisibilityTracking() {
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
        });
    }

    showBrowserNotification(title, message) {
        if (this.notificationPermission === 'granted' && !this.isPageVisible) {
            try {
                const notification = new Notification(title, {
                    body: message,
                    icon: '../img/logo.png'
                });

                notification.onclick = () => {
                    window.focus();
                    notification.close();
                };

                setTimeout(() => notification.close(), 5000);
            } catch (error) {
                console.log('Notification error:', error);
            }
        }
    }

    showToastNotification(message, senderName, conversationId) {
        const existingToast = document.querySelector('.chat-toast-notification');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.className = 'chat-toast-notification';
        toast.style.cursor = 'pointer';
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fa-solid fa-comment-dots"></i>
            </div>
            <div class="toast-content">
                <div class="toast-sender">${this.escapeHtml(senderName)}</div>
                <div class="toast-message">${this.escapeHtml(message.substring(0, 60))}${message.length > 60 ? '...' : ''}</div>
            </div>
            <button class="toast-close" onclick="event.stopPropagation(); this.parentElement.remove()">
                <i class="fa-solid fa-times"></i>
            </button>
        `;

        // Make toast clickable to open conversation
        toast.addEventListener('click', () => {
            const userType = window.currentUser.type;
            const messagesPage = userType === 'tenant' ? 'tenant-messages.php' : 'landlord-message.php';
            window.location.href = `${messagesPage}?conversation_id=${conversationId}`;
        });

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }

    updateNotificationBadge(count) {
        const badge = document.querySelector('.count');
        
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    addToNotificationDropdown(message, senderName) {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;

        if (notificationList.innerText.includes('No notifications')) {
            notificationList.innerHTML = '';
        }

        const li = document.createElement('li');
        li.innerHTML = `
            <span class="dropdown-item notification-item">
                <strong>${this.escapeHtml(senderName)}</strong>: ${this.escapeHtml(message.substring(0, 40))}${message.length > 40 ? '...' : ''}
            </span>
        `;
        
        notificationList.insertBefore(li, notificationList.firstChild);

        while (notificationList.children.length > 10) {
            notificationList.removeChild(notificationList.lastChild);
        }
    }

    startNotificationPolling() {
        if (!window.currentUser) {
            console.log('❌ No current user, skipping notifications');
            return;
        }

        console.log('✅ Starting message polling for user:', window.currentUser.id);

        // Do first check immediately
        setTimeout(() => this.pollOnce(), 1000);

        // Then poll every 5 seconds
        this.pollingInterval = setInterval(() => {
            this.pollOnce();
        }, 5000);
    }

    async pollOnce() {
        console.log('🔄 Polling now...');
        try {
            const url = `${this.apiBasePath}/get_conversations.php?user_id=${window.currentUser.id}&user_type=${window.currentUser.type}`;
            
            const response = await fetch(url);
            console.log('📊 Response status:', response.status);
            
            if (!response.ok) {
                console.error('❌ API response not OK:', response.status);
                return;
            }

            const data = await response.json();

            if (data.success && data.conversations) {
                this.checkForNewMessages(data.conversations);
            }
        } catch (error) {
            console.error('❌ Notification polling error:', error);
        }
    }

    async checkForNewMessages(conversations) {
        let conversationsWithUnread = 0;

        for (const conv of conversations) {
            try {
                const url = `${this.apiBasePath}/get_messages.php?conversation_id=${conv.conversation_id}`;
                const response = await fetch(url);
                
                if (!response.ok) continue;

                const data = await response.json();

                if (data.success && data.messages && data.messages.length > 0) {
                    const messages = data.messages;
                    const lastMessage = messages[messages.length - 1];

                    // Check BOTH sender_id AND sender_type
                    const isDifferentUser = (
                        String(lastMessage.sender_id) !== String(window.currentUser.id) ||
                        lastMessage.sender_type !== window.currentUser.type
                    );
                    
                    // Only check if NOT seen (convert ID to number for consistency)
                    const messageId = parseInt(lastMessage.id);
                    const isNotSeen = !this.seenMessages.has(messageId);

                    // Trigger notification ONLY for NEW unseen messages from others
                    if (lastMessage && isDifferentUser && isNotSeen) {
                        this.handleNewMessage(lastMessage, conv.other_user_name, messageId, conv.conversation_id);
                    }
                }
            } catch (error) {
                console.error('❌ Error checking messages:', error);
            }
        }

        // Get unread count from database instead of calculating locally
        await this.updateBadgeFromDatabase();
    }

    async updateBadgeFromDatabase() {
        try {
            const url = `${this.apiBasePath}/get_unread_count.php?user_id=${window.currentUser.id}&user_type=${window.currentUser.type}`;
            const response = await fetch(url);
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.updateNotificationBadge(data.unread_count);
                }
            }
        } catch (error) {
            console.error('Error updating badge:', error);
        }
    }

    handleNewMessage(message, senderName, messageId, conversationId) {
        console.log('🔔 NEW MESSAGE from:', senderName, 'Message ID:', messageId);

        // Mark as seen FIRST (store as number)
        this.seenMessages.add(messageId);
        this.saveSeenMessages(); // Persist to localStorage
        
        // Then trigger notifications
        this.playNotificationSound();

        const messageText = message.content || message.message || 'New message';
        this.showToastNotification(messageText, senderName, conversationId);
        this.showBrowserNotification(`New message from ${senderName}`, messageText);
        this.addToNotificationDropdown(messageText, senderName);
    }

    setCurrentConversation(conversationId) {
        console.log('🎯 setCurrentConversation CALLED with ID:', conversationId);
        this.currentConversationId = conversationId;
        console.log('💬 Current conversation NOW SET TO:', this.currentConversationId);
        
        // Immediately mark all messages in this conversation as seen
        this.markConversationAsRead(conversationId);
    }

    async markConversationAsRead(conversationId) {
        try {
            // Call API to mark messages as read in database
            const response = await fetch(`${this.apiBasePath}/mark_messages_read.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `conversation_id=${conversationId}`
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    console.log('✅ Marked', data.marked_read, 'messages as read in database');
                }
            }
            
            // Also fetch and add to seenMessages for immediate effect
            const messagesResponse = await fetch(`${this.apiBasePath}/get_messages.php?conversation_id=${conversationId}`);
            
            if (messagesResponse.ok) {
                const messagesData = await messagesResponse.json();
                if (messagesData.success && messagesData.messages) {
                    messagesData.messages.forEach(msg => {
                        this.seenMessages.add(parseInt(msg.id));
                    });
                    this.saveSeenMessages();
                    console.log('✅ Added', messagesData.messages.length, 'messages to seenMessages');
                }
            }
        } catch (error) {
            console.error('Error marking conversation as read:', error);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
}

// Initialize notification system
console.log('🚀 Loading notification system V2...');
const chatNotifications = new ChatNotificationSystem();
window.chatNotifications = chatNotifications;

console.log('✅ window.chatNotifications is now available:', !!window.chatNotifications);

// CSS
const style = document.createElement('style');
style.textContent = `
.chat-toast-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    min-width: 320px;
    max-width: 400px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    animation: slideInRight 0.3s ease;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chat-toast-notification:hover {
    transform: translateX(-5px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
}

.chat-toast-notification.fade-out {
    animation: slideOutRight 0.3s ease;
    opacity: 0;
}

@keyframes slideInRight {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
}

.toast-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.toast-icon i {
    color: white;
    font-size: 18px;
}

.toast-content {
    flex: 1;
}

.toast-sender {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 4px;
    font-size: 14px;
}

.toast-message {
    color: #4a5568;
    font-size: 13px;
    line-height: 1.4;
    margin: 0;
}

.toast-close {
    background: none;
    border: none;
    color: #a0aec0;
    cursor: pointer;
    padding: 4px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.toast-close:hover {
    background: #f7fafc;
    color: #2d3748;
}

@media (max-width: 768px) {
    .chat-toast-notification {
        right: 10px;
        left: 10px;
        min-width: auto;
        max-width: none;
    }
}
`;
document.head.appendChild(style);