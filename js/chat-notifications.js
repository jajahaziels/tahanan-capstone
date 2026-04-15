// ============================================
// CHAT + LISTING NOTIFICATION SYSTEM
// Handles both chat messages AND listing status
// notifications (approved / rejected / etc.)
// ============================================

class ChatNotificationSystem {
    constructor() {
        this.unreadMessages      = new Map();
        this.notificationPermission = 'default';
        this.isPageVisible       = true;
        this.currentConversationId = null;
        this.seenMessages        = this.loadSeenMessages();
        this.seenNotifications   = this.loadSeenNotifications(); // ← NEW: tracks system notifications
        this.apiBasePath         = this.detectApiPath();
        this.initialized         = false;

        this.init();
    }

    // ── Persistence helpers ──────────────────────────────────────────────────

    loadSeenMessages() {
        try {
            const stored = localStorage.getItem('chatSeenMessages');
            return stored ? new Set(JSON.parse(stored)) : new Set();
        } catch { return new Set(); }
    }

    saveSeenMessages() {
        try { localStorage.setItem('chatSeenMessages', JSON.stringify([...this.seenMessages])); } catch {}
    }

    loadSeenNotifications() {
        try {
            const stored = localStorage.getItem('seenSystemNotifications');
            return stored ? new Set(JSON.parse(stored)) : new Set();
        } catch { return new Set(); }
    }

    saveSeenNotifications() {
        try { localStorage.setItem('seenSystemNotifications', JSON.stringify([...this.seenNotifications])); } catch {}
    }

    detectApiPath() {
        return window.location.pathname.includes('/TAHANAN/') ? '/TAHANAN/API' : '/API';
    }

    // ── Startup ──────────────────────────────────────────────────────────────

    init() {
        console.log('🔔 Notification system starting…');
        this.requestNotificationPermission();
        this.setupVisibilityTracking();

        setTimeout(() => {
            this.initialized = true;
            this.startNotificationPolling();
        }, 1000);
    }

    // ── Sound ────────────────────────────────────────────────────────────────

    playNotificationSound() {
        try {
            const ctx  = new (window.AudioContext || window.webkitAudioContext)();
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 800;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch {}
    }

    // ── Browser + visibility ─────────────────────────────────────────────────

    async requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            try { this.notificationPermission = await Notification.requestPermission(); }
            catch {}
        } else if ('Notification' in window) {
            this.notificationPermission = Notification.permission;
        }
    }

    setupVisibilityTracking() {
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
        });
    }

    showBrowserNotification(title, body) {
        if (this.notificationPermission === 'granted' && !this.isPageVisible) {
            try {
                const n = new Notification(title, { body, icon: '../img/logo.png' });
                n.onclick = () => { window.focus(); n.close(); };
                setTimeout(() => n.close(), 5000);
            } catch {}
        }
    }

    // ── Toast ────────────────────────────────────────────────────────────────

    /**
     * @param {string} message        - Body text
     * @param {string} senderName     - Displayed name
     * @param {string|null} href      - Where to go on click (null = no navigation)
     * @param {string} iconClass      - Font Awesome icon class
     */
    showToastNotification(message, senderName, href = null, iconClass = 'fa-solid fa-comment-dots') {
        const existing = document.querySelector('.chat-toast-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'chat-toast-notification';
        if (href) toast.style.cursor = 'pointer';

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${iconClass}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-sender">${this.escapeHtml(senderName)}</div>
                <div class="toast-message">${this.escapeHtml(message.substring(0, 80))}${message.length > 80 ? '…' : ''}</div>
            </div>
            <button class="toast-close" onclick="event.stopPropagation(); this.parentElement.remove()">
                <i class="fa-solid fa-times"></i>
            </button>
        `;

        if (href) {
            toast.addEventListener('click', () => { window.location.href = href; });
        }

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }
        }, 6000);
    }

    // ── Badge ────────────────────────────────────────────────────────────────

    updateNotificationBadge(count) {
        const badge = document.querySelector('.count');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }

    // ── Dropdown list ────────────────────────────────────────────────────────

    /**
     * Rebuilds the full notification dropdown from an array returned by
     * get_notifications.php, replacing the old "No notifications" placeholder.
     */
    renderNotificationDropdown(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;

        if (!notifications || notifications.length === 0) {
            list.innerHTML = '<li><span class="dropdown-item text-muted text-center py-3">No notifications</span></li>';
            return;
        }

        list.innerHTML = notifications.map(n => {
            const icon = n.type === 'system'
                ? (n.message.startsWith('✅') ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger')
                : 'fa-comment-dots text-primary';
            return `
                <li>
                    <span class="dropdown-item notification-item d-flex align-items-start gap-2 py-2 ${n.is_read ? '' : 'fw-semibold'}"
                          style="white-space:normal; font-size:13px; ${!n.is_read ? 'background:rgba(141,11,65,0.07);' : ''}">
                        <i class="fa-solid ${icon} mt-1 flex-shrink-0"></i>
                        <span>
                            <span>${this.escapeHtml(n.message.substring(0, 80))}${n.message.length > 80 ? '…' : ''}</span>
                            <br><small class="text-muted">${n.time_ago}</small>
                        </span>
                    </span>
                </li>`;
        }).join('');
    }

    addChatToDropdown(message, senderName) {
        const list = document.getElementById('notificationList');
        if (!list) return;

        if (list.innerText.includes('No notifications')) list.innerHTML = '';

        const li = document.createElement('li');
        li.innerHTML = `
            <span class="dropdown-item notification-item d-flex align-items-start gap-2 py-2 fw-semibold"
                  style="white-space:normal; font-size:13px; background:rgba(141,11,65,0.07);">
                <i class="fa-solid fa-comment-dots text-primary mt-1 flex-shrink-0"></i>
                <span><strong>${this.escapeHtml(senderName)}</strong>: ${this.escapeHtml(message.substring(0, 50))}${message.length > 50 ? '…' : ''}</span>
            </span>`;

        list.insertBefore(li, list.firstChild);
        while (list.children.length > 15) list.removeChild(list.lastChild);
    }

    // ── Polling ──────────────────────────────────────────────────────────────

    startNotificationPolling() {
        if (!window.currentUser) {
            console.log('❌ No current user – skipping notifications');
            return;
        }

        console.log('✅ Starting notification polling for:', window.currentUser.id, window.currentUser.type);

        // Immediate first run
        setTimeout(() => this.pollAll(), 1000);

        // Chat: every 5 s  |  System notifications: every 10 s
        this.chatInterval   = setInterval(() => this.pollChatMessages(), 5000);
        this.systemInterval = setInterval(() => this.pollSystemNotifications(), 10000);
    }

    async pollAll() {
        await this.pollChatMessages();
        await this.pollSystemNotifications();
    }

    // ── Chat polling (unchanged logic) ───────────────────────────────────────

    async pollChatMessages() {
        try {
            const url  = `${this.apiBasePath}/get_conversations.php?user_id=${window.currentUser.id}&user_type=${window.currentUser.type}`;
            const resp = await fetch(url);
            if (!resp.ok) return;

            const data = await resp.json();
            if (data.success && data.conversations) {
                await this.checkForNewChatMessages(data.conversations);
            }
        } catch (e) { console.error('Chat poll error:', e); }
    }

    async checkForNewChatMessages(conversations) {
        for (const conv of conversations) {
            try {
                const resp = await fetch(`${this.apiBasePath}/get_messages.php?conversation_id=${conv.conversation_id}`);
                if (!resp.ok) continue;

                const data = await resp.json();
                if (!data.success || !data.messages?.length) continue;

                const last = data.messages[data.messages.length - 1];
                const isDifferentUser = (
                    String(last.sender_id) !== String(window.currentUser.id) ||
                    last.sender_type !== window.currentUser.type
                );
                const msgId    = parseInt(last.id);
                const isUnseen = !this.seenMessages.has(msgId);

                if (isDifferentUser && isUnseen) {
                    this.handleNewChatMessage(last, conv.other_user_name, msgId, conv.conversation_id);
                }
            } catch {}
        }

        await this.updateBadgeFromDatabase();
    }

    handleNewChatMessage(message, senderName, messageId, conversationId) {
        this.seenMessages.add(messageId);
        this.saveSeenMessages();

        this.playNotificationSound();

        const text      = message.content || message.message || 'New message';
        const userType  = window.currentUser.type;
        const href      = `${userType === 'tenant' ? 'tenant-messages.php' : 'landlord-message.php'}?conversation_id=${conversationId}`;

        this.showToastNotification(text, senderName, href, 'fa-solid fa-comment-dots');
        this.showBrowserNotification(`New message from ${senderName}`, text);
        this.addChatToDropdown(text, senderName);
    }

    async updateBadgeFromDatabase() {
        try {
            const url  = `${this.apiBasePath}/get_unread_count.php?user_id=${window.currentUser.id}&user_type=${window.currentUser.type}`;
            const resp = await fetch(url);
            if (!resp.ok) return;
            const data = await resp.json();
            if (data.success) this.updateNotificationBadge(data.unread_count);
        } catch {}
    }

    // ── System / listing notifications ───────────────────────────────────────

    async pollSystemNotifications() {
        try {
            const url  = `${this.apiBasePath}/get_notifications.php?user_id=${window.currentUser.id}&user_type=${window.currentUser.type}`;
            const resp = await fetch(url);
            if (!resp.ok) return;

            const data = await resp.json();
            if (!data.success) return;

            // Re-render dropdown with fresh data
            this.renderNotificationDropdown(data.notifications);

            // Add unread count from system notifications on top of chat unread count
            // (badge is managed separately by updateBadgeFromDatabase for chat;
            //  here we just make sure system unreads bump the badge too)
            const chatBadge = parseInt(document.querySelector('.count')?.textContent || '0');
            const totalUnread = data.unread_count + (isNaN(chatBadge) ? 0 : chatBadge);
            this.updateNotificationBadge(totalUnread);

            // Toast any newly arrived unread system notifications
            for (const n of data.notifications) {
                if (n.is_read) continue;                          // already read
                if (this.seenNotifications.has(n.id)) continue;  // already toasted

                this.seenNotifications.add(n.id);
                this.saveSeenNotifications();

                this.playNotificationSound();

                const icon = n.message.startsWith('✅')
                    ? 'fa-solid fa-circle-check'
                    : 'fa-solid fa-circle-xmark';

                this.showToastNotification(n.message, 'Property Update', null, icon);
                this.showBrowserNotification('Property Update', n.message);
            }
        } catch (e) { console.error('System notification poll error:', e); }
    }

    // ── Conversation helpers ──────────────────────────────────────────────────

    setCurrentConversation(conversationId) {
        this.currentConversationId = conversationId;
        this.markConversationAsRead(conversationId);
    }

    async markConversationAsRead(conversationId) {
        try {
            await fetch(`${this.apiBasePath}/mark_messages_read.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `conversation_id=${conversationId}`
            });

            const resp = await fetch(`${this.apiBasePath}/get_messages.php?conversation_id=${conversationId}`);
            if (resp.ok) {
                const data = await resp.json();
                if (data.success && data.messages) {
                    data.messages.forEach(m => this.seenMessages.add(parseInt(m.id)));
                    this.saveSeenMessages();
                }
            }
        } catch {}
    }

    // ── Util ─────────────────────────────────────────────────────────────────

    escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }
}

// ── Bootstrap ────────────────────────────────────────────────────────────────
console.log('🚀 Loading notification system…');
const chatNotifications    = new ChatNotificationSystem();
window.chatNotifications   = chatNotifications;

// ── Clear all button: also marks system notifications as read ─────────────────
document.addEventListener('DOMContentLoaded', () => {
    const clearBtn = document.getElementById('clearNotifications'); // ← THIS LINE IS MISSING
    if (clearBtn) {
        clearBtn.addEventListener('click', async () => {
            document.getElementById('notificationList').innerHTML =
                '<li><span class="dropdown-item text-muted text-center py-3">No notifications</span></li>';
            document.querySelector('.count') && (document.querySelector('.count').style.display = 'none');

            if (window.currentUser) {
                console.log('Clearing for:', window.currentUser.id, window.currentUser.type);
                try {
                    const resp = await fetch(`${chatNotifications.apiBasePath}/mark_notifications_read.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `user_id=${window.currentUser.id}&user_type=${window.currentUser.type}&mark_all=1`
                    });
                    const data = await resp.json();
                    console.log('Mark read response:', data);
                } catch(e) { console.error(e); }
            }
        });
    }
});
// ── CSS ───────────────────────────────────────────────────────────────────────
const style = document.createElement('style');
style.textContent = `
.chat-toast-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    z-index: 10000;
    min-width: 320px;
    max-width: 400px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    animation: slideInRight 0.3s ease;
    transition: all 0.3s ease;
}
.chat-toast-notification:hover { transform: translateX(-5px); box-shadow: 0 12px 32px rgba(0,0,0,0.2); }
.chat-toast-notification.fade-out { animation: slideOutRight 0.3s ease; opacity: 0; }

@keyframes slideInRight  { from { transform: translateX(400px); opacity:0; } to { transform: translateX(0); opacity:1; } }
@keyframes slideOutRight { from { transform: translateX(0); opacity:1; } to { transform: translateX(400px); opacity:0; } }

.toast-icon {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.toast-icon i { color: white; font-size: 18px; }
.toast-content { flex: 1; }
.toast-sender  { font-weight: 600; color: #2d3748; margin-bottom: 4px; font-size: 14px; }
.toast-message { color: #4a5568; font-size: 13px; line-height: 1.4; margin: 0; }
.toast-close   {
    background: none; border: none; color: #a0aec0; cursor: pointer;
    padding: 4px; width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s;
}
.toast-close:hover { background: #f7fafc; color: #2d3748; }

@media (max-width: 768px) {
    .chat-toast-notification { right: 10px; left: 10px; min-width: auto; max-width: none; }
}
`;
document.head.appendChild(style);