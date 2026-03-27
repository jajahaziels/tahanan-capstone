document.addEventListener("DOMContentLoaded", async () => {
  const chatMessages = document.querySelector(".chat-messages");
  const chatInput = document.querySelector(".chat-input input[type='text']");
  const sendBtn = document.querySelector(".chat-input button[type='submit']");
  const conversationsList = document.querySelector(".side1");
  const chatHeader = document.querySelector(".chat-header");
  const fileInput = document.getElementById('file-input');

  let currentUserId = null;
  let currentConversationId = null;
  let currentUserType = null;
  let currentUserProfilePic = null;
  let loadMessagesInterval = null;
  let selectedFile = null;
  let lastMessagesHash = '';
  let currentConversationData = null;
  
  const urlParams = new URLSearchParams(window.location.search);
  let targetConversationId = urlParams.get('conversation_id');

  try {
    const sessionRes = await fetch("../api/session_check.php");
    if (!sessionRes.ok) throw new Error(`HTTP ${sessionRes.status}`);
    
    const sessionData = await JSON.parse(await sessionRes.text());
    
    if (!sessionData.success) {
      window.location.href = sessionData.redirect || "../LOGIN/login.php";
      return;
    }
    
    currentUserId = sessionData.user_id;
    currentUserType = sessionData.user_type;
    currentUserProfilePic = sessionData.profile_pic || null;
    
    await loadConversations();
    
  } catch (err) {
    if (window.currentUser) {
      currentUserId = window.currentUser.id;
      currentUserType = window.currentUser.type;
      currentUserProfilePic = window.currentUser.profilePic || null;
      await loadConversations();
    } else {
      showNotification('Session expired. Please login again.', 'error');
      setTimeout(() => window.location.href = "../LOGIN/login.php", 2000);
    }
  }

  async function loadConversations() {
    try {
      const res = await fetch(
        `../api/get_conversations.php?user_id=${currentUserId}&user_type=${currentUserType}&t=${Date.now()}`,
        { cache: 'no-cache' }
      );
      
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      if (data.success) {
        displayConversations(data.conversations);
        
        const loadingEl = document.getElementById('conversations-loading');
        if (loadingEl) loadingEl.style.display = 'none';
        
        if (data.conversations.length === 0) {
          const noConvEl = document.getElementById('no-conversations');
          if (noConvEl) noConvEl.style.display = 'block';
        } else {
          const noConvEl = document.getElementById('no-conversations');
          if (noConvEl) noConvEl.style.display = 'none';
          
          if (targetConversationId) {
            const targetConv = data.conversations.find(c => c.conversation_id == targetConversationId);
            if (targetConv) {
              selectConversation(targetConv);
              window.history.replaceState({}, document.title, window.location.pathname);
            } else {
              selectConversation(data.conversations[0]);
            }
          } else if (!currentConversationId) {
            selectConversation(data.conversations[0]);
          }
        }
      }
    } catch (err) {
      console.error("Load conversations error:", err);
      showNotification('Failed to load conversations', 'error');
    }
  }

  function displayConversations(conversations) {
    const searchInput = document.querySelector(".search-chats");
    const existingConvos = conversationsList.querySelectorAll('.convo');
    existingConvos.forEach(conv => conv.remove());

    conversations.forEach((conv, index) => {
      const convDiv = document.createElement("div");
      convDiv.className = "convo d-flex align-items-center";
      convDiv.setAttribute("data-conversation-id", conv.conversation_id);
      convDiv.setAttribute("data-user-name", conv.other_user_name || '');
      convDiv.setAttribute("data-last-message", conv.last_message || '');
      convDiv.setAttribute("data-profile-pic", conv.other_user_profile_pic || '');
      convDiv.style.opacity = '0';
      convDiv.style.animation = `fadeIn 0.4s ease forwards ${index * 0.05}s`;
      
      let profilePicSrc = "../img/home.png";
      if (conv.other_user_profile_pic && conv.other_user_profile_pic.trim() !== '') {
        profilePicSrc = `../uploads/${conv.other_user_profile_pic}`;
      }
      
      const lastMessage = conv.last_message || 'No messages yet';
      const truncatedMessage = lastMessage.length > 40 ? lastMessage.substring(0, 40) + '...' : lastMessage;
      
      convDiv.innerHTML = `
        <img src="${profilePicSrc}" 
             alt="Profile" 
             style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 12px;"
             onerror="this.onerror=null; this.src='../img/home.png';">
        <div style="flex: 1; min-width: 0;">
          <h4 style="margin: 0; font-size: 14px; font-weight: 600;">${escapeHtml(conv.other_user_name || conv.other_user_type)}</h4>
          <small style="font-size: 12px; color: #888;">${escapeHtml(truncatedMessage)}</small>
        </div>
      `;

      convDiv.addEventListener("click", () => selectConversation(conv));
      conversationsList.appendChild(convDiv);
    });
    
    if (searchInput && searchInput.parentNode === conversationsList) {
      conversationsList.insertBefore(searchInput, conversationsList.firstChild);
    }
  }

   function selectConversation(conversation) {
    document.querySelectorAll(".convo").forEach(conv => conv.classList.remove("active"));

    const selectedConv = document.querySelector(`[data-conversation-id="${conversation.conversation_id}"]`);
    if (selectedConv) selectedConv.classList.add("active");

    currentConversationId = conversation.conversation_id;
    
    if (window.chatNotifications) {
        window.chatNotifications.setCurrentConversation(conversation.conversation_id);
    }
    
    currentConversationData = conversation;
    lastMessagesHash = '';
    
    chatHeader.textContent = `Chat with ${conversation.other_user_name || conversation.other_user_type}`;
    
    if (chatInput) {
      chatInput.disabled = false;
      chatInput.placeholder = "Type a message...";
      chatInput.focus();
    }
    if (sendBtn) sendBtn.disabled = false;
    
    if (loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
      loadMessagesInterval = null;
    }
    
    loadMessages();
    loadMessagesInterval = setInterval(() => {
      if (!document.hidden) loadMessages();
    }, 3000);
  }

  if (sendBtn) sendBtn.addEventListener("click", sendMessage);
  
  if (chatInput) {
    chatInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }

  async function sendMessage() {
    const message = chatInput ? chatInput.value.trim() : '';
    
    if (!message && !selectedFile) return;
    if (!currentConversationId) {
      showNotification('Please select a conversation first', 'warning');
      return;
    }

    const originalButtonHTML = sendBtn.innerHTML;
    if (chatInput) chatInput.disabled = true;
    if (sendBtn) {
      sendBtn.disabled = true;
      sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }

    const formData = new FormData();
    formData.append("conversation_id", currentConversationId);
    if (message) formData.append("message", message);
    if (selectedFile) formData.append("file", selectedFile);

    try {
      const res = await fetch("../api/send_message.php", {
        method: "POST",
        body: formData
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      
      if (data.success) {
        if (chatInput) chatInput.value = "";
        if (selectedFile) removeFile();
        
        lastMessagesHash = '';
        await loadMessages();
        
        if (sendBtn) {
          sendBtn.innerHTML = '<i class="fa-solid fa-check"></i>';
          setTimeout(() => {
            if (sendBtn) sendBtn.innerHTML = originalButtonHTML;
          }, 1000);
        }
      } else {
        throw new Error(data.error || 'Failed to send message');
      }
    } catch (err) {
      console.error("Send error:", err);
      showNotification(err.message || "Failed to send message", 'error');
      if (sendBtn) sendBtn.innerHTML = originalButtonHTML;
    } finally {
      if (chatInput) {
        chatInput.disabled = false;
        chatInput.focus();
      }
      if (sendBtn) sendBtn.disabled = false;
    }
  }

  async function loadMessages() {
    if (!currentConversationId || !chatMessages) return;

    try {
      const res = await fetch(
        `../api/get_messages.php?conversation_id=${currentConversationId}&t=${Date.now()}`,
        { cache: 'no-cache' }
      );
      
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      if (data.success) {
        const messagesHash = JSON.stringify(data.messages.map(m => m.id + m.created_at));
        
        if (messagesHash === lastMessagesHash && data.messages.length > 0) {
          return;
        }
        
        lastMessagesHash = messagesHash;
        const wasAtBottom = isScrolledToBottom();
        
        if (data.messages.length === 0) {
          chatMessages.innerHTML = `
            <div class="empty-chat">
              <div class="empty-chat-content">
                <i class="fa-solid fa-comment-dots"></i>
                <p>No messages yet</p>
                <small>Start the conversation!</small>
              </div>
            </div>
          `;
          return;
        }

        chatMessages.innerHTML = "";
        data.messages.forEach((msg, index) => {
          const messageEl = createMessageElement(msg, index);
          chatMessages.appendChild(messageEl);
        });

        if (wasAtBottom) chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    } catch (err) {
      console.error("Load messages error:", err);
    }
  }

  function createMessageElement(msg, index) {
    const isMine = (parseInt(msg.sender_id) === parseInt(currentUserId)) && (msg.sender_type === currentUserType);
    
    const messageDiv = document.createElement("div");
    messageDiv.classList.add("message");
    messageDiv.classList.add(isMine ? "sent" : "received");
    
    const avatar = document.createElement("img");
    avatar.classList.add("message-avatar");
    
    if (isMine) {
      if (currentUserProfilePic && currentUserProfilePic.trim() !== '') {
        avatar.src = `../uploads/${currentUserProfilePic}`;
      } else {
        avatar.src = "../img/home.png";
      }
    } else {
      if (currentConversationData && currentConversationData.other_user_profile_pic) {
        avatar.src = `../uploads/${currentConversationData.other_user_profile_pic}`;
      } else {
        avatar.src = "../img/home.png";
      }
    }
    
    avatar.onerror = function() { this.src = "../img/home.png"; };
    
    const bubbleDiv = document.createElement("div");
    bubbleDiv.classList.add("message-bubble");
    
    const contentDiv = document.createElement("div");
    contentDiv.classList.add("message-content");
    
    if (msg.file_path) {
      const fileDiv = document.createElement("div");
      fileDiv.classList.add("message-file");
      
      if (msg.file_type === 'image') {
        const img = document.createElement("img");
        img.src = msg.file_path;
        img.onclick = () => openImageModal(msg.file_path);
        img.style.maxWidth = '300px';
        img.style.maxHeight = '300px';
        img.style.cursor = 'pointer';
        fileDiv.appendChild(img);
        contentDiv.appendChild(fileDiv);
        contentDiv.style.background = 'transparent';
        contentDiv.style.padding = '0';
      } else {
        const link = document.createElement("a");
        link.href = msg.file_path;
        link.download = msg.message;
        link.innerHTML = `
          <i class="fa-solid ${getFileIcon(msg.file_path)}"></i>
          <span>${escapeHtml(msg.message)}</span>
          <i class="fa-solid fa-download"></i>
        `;
        fileDiv.appendChild(link);
        contentDiv.appendChild(fileDiv);
      }
    } else {
      contentDiv.textContent = msg.message;
    }
    
    const metaDiv = document.createElement("div");
    metaDiv.classList.add("message-meta");
    
    const timeSpan = document.createElement("span");
    timeSpan.classList.add("message-time");
    timeSpan.textContent = formatMessageTime(msg.created_at);
    metaDiv.appendChild(timeSpan);
    
    if (isMine) {
      const statusSpan = document.createElement("span");
      statusSpan.classList.add("message-status");
      statusSpan.innerHTML = '<i class="fa-solid fa-check-double status-read"></i>';
      metaDiv.appendChild(statusSpan);
    }
    
    bubbleDiv.appendChild(contentDiv);
    bubbleDiv.appendChild(metaDiv);
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(bubbleDiv);
    
    return messageDiv;
  }

  if (fileInput) {
    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (!file) return;
      
      if (file.size > 10485760) {
        showNotification("File too large. Maximum size is 10MB.", 'error');
        fileInput.value = '';
        return;
      }
      
      selectedFile = file;
      showFilePreview(file);
    });
  }

  function showFilePreview(file) {
    const preview = document.getElementById('file-preview');
    if (!preview) return;
    
    const icon = preview.querySelector('.file-preview-icon i');
    const nameElem = preview.querySelector('.file-preview-name');
    const sizeElem = preview.querySelector('.file-preview-size');
    
    if (!icon || !nameElem || !sizeElem) return;
    
    icon.className = `fa-solid ${getFileIcon(file.name)}`;
    nameElem.textContent = file.name;
    sizeElem.textContent = formatFileSize(file.size);
    preview.classList.add('show');
  }

  window.removeFile = function() {
    selectedFile = null;
    if (fileInput) fileInput.value = '';
    const preview = document.getElementById('file-preview');
    if (preview) preview.classList.remove('show');
  }

  const searchInput = document.querySelector(".search-chats");
  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      const searchTerm = e.target.value.toLowerCase().trim();
      const conversations = document.querySelectorAll(".convo");
      let visibleCount = 0;
      
      conversations.forEach((conv) => {
        const userName = (conv.getAttribute('data-user-name') || '').toLowerCase();
        const lastMessage = (conv.getAttribute('data-last-message') || '').toLowerCase();
        
        if (searchTerm === '' || userName.includes(searchTerm) || lastMessage.includes(searchTerm)) {
          conv.style.display = "flex";
          visibleCount++;
        } else {
          conv.style.display = "none";
        }
      });
      
      const noConvEl = document.getElementById('no-conversations');
      if (noConvEl) {
        if (searchTerm && visibleCount === 0) {
          noConvEl.innerHTML = '<i class="fa-solid fa-search"></i><p>No conversations found</p><small>Try searching for "' + escapeHtml(searchTerm) + '"</small>';
          noConvEl.style.display = 'block';
        } else if (!searchTerm && conversations.length === 0) {
          noConvEl.innerHTML = '<i class="fa-solid fa-comments"></i><p>No conversations yet</p><small>Start a conversation</small>';
          noConvEl.style.display = 'block';
        } else {
          noConvEl.style.display = 'none';
        }
      }
    });
  }

  function formatMessageTime(timestamp) {
    const messageDate = new Date(timestamp);
    const now = new Date();
    const diffMs = now - messageDate;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return "Yesterday";
    if (diffDays < 7) return `${diffDays}d ago`;
    
    return messageDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function formatFileSize(bytes) {
    if (!bytes) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const iconMap = {
      'pdf': 'fa-file-pdf', 'doc': 'fa-file-word', 'docx': 'fa-file-word',
      'xls': 'fa-file-excel', 'xlsx': 'fa-file-excel', 'txt': 'fa-file-lines',
      'jpg': 'fa-file-image', 'jpeg': 'fa-file-image', 'png': 'fa-file-image',
      'gif': 'fa-file-image', 'webp': 'fa-file-image'
    };
    return iconMap[ext] || 'fa-file';
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function isScrolledToBottom() {
    if (!chatMessages) return false;
    return chatMessages.scrollTop + chatMessages.clientHeight >= chatMessages.scrollHeight - 50;
  }

  function openImageModal(imageSrc) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:10000;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    
    const img = document.createElement('img');
    img.src = imageSrc;
    img.style.cssText = 'max-width:90%;max-height:90%;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,0.5);';
    
    modal.appendChild(img);
    document.body.appendChild(modal);
    modal.onclick = () => document.body.removeChild(modal);
  }

  function showNotification(message, type = 'info') {
    const colors = {
      success: { bg: '#48bb78', icon: 'check-circle' },
      error: { bg: '#f56565', icon: 'exclamation-circle' },
      warning: { bg: '#ed8936', icon: 'exclamation-triangle' },
      info: { bg: '#667eea', icon: 'info-circle' }
    };
    const color = colors[type] || colors.info;
    const notification = document.createElement("div");
    notification.style.cssText = `position:fixed;top:20px;right:20px;background:${color.bg};color:white;padding:16px 24px;border-radius:12px;z-index:10000;box-shadow:0 10px 30px rgba(0,0,0,0.3);display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;`;
    notification.innerHTML = `<i class="fa-solid fa-${color.icon}"></i><span>${escapeHtml(message)}</span>`;
    document.body.appendChild(notification);
    setTimeout(() => {
      if (document.body.contains(notification)) document.body.removeChild(notification);
    }, 4000);
  }

  window.addEventListener("beforeunload", () => {
    if (loadMessagesInterval) clearInterval(loadMessagesInterval);
  });
  
  document.addEventListener("visibilitychange", () => {
    if (document.hidden && loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
    } else if (!document.hidden && currentConversationId) {
      if (loadMessagesInterval) clearInterval(loadMessagesInterval);
      loadMessagesInterval = setInterval(() => {
        if (!document.hidden) loadMessages();
      }, 3000);
      loadMessages();
    }
  });
});