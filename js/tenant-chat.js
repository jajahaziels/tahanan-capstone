

console.log('✅ tenant-chat.js (improved) is loading!');

document.addEventListener("DOMContentLoaded", async () => {
  console.log('✅ DOMContentLoaded fired');
  
  // DOM Elements
  const chatMessages = document.querySelector(".chat-messages");
  const chatInput = document.querySelector(".chat-input input[type='text']");
  const sendBtn = document.querySelector(".chat-input button[type='submit']");
  const conversationsList = document.querySelector(".side1");
  const chatHeader = document.querySelector(".chat-header");
  const fileInput = document.getElementById('file-input');

  // State Variables
  let currentUserId = null;
  let currentConversationId = null;
  let currentUserType = null;
  let loadMessagesInterval = null;
  let selectedFile = null;
  let isTyping = false;
  let typingTimeout = null;
  
  // Get conversation ID from URL
  const urlParams = new URLSearchParams(window.location.search);
  let targetConversationId = urlParams.get('conversation_id');
  
  console.log('🎯 Target Conversation ID from URL:', targetConversationId);

  /* ========================================
     INITIALIZATION
     ======================================== */
  
  // Check session and get user info
  try {
    console.log('🔍 Fetching session from API...');
    const sessionRes = await fetch("../api/session_check.php");
    
    if (!sessionRes.ok) {
      throw new Error(`HTTP ${sessionRes.status}`);
    }
    
    const sessionText = await sessionRes.text();
    console.log('📄 Session response received');
    
    let sessionData;
    try {
      sessionData = JSON.parse(sessionText);
      console.log('✅ Session validated');
    } catch (parseErr) {
      console.error('❌ JSON parse error:', parseErr);
      throw new Error('Invalid session response');
    }
    
    if (!sessionData.success) {
      console.error('❌ Session invalid');
      window.location.href = sessionData.redirect || "../LOGIN/login.php";
      return;
    }
    
    currentUserId = sessionData.user_id;
    currentUserType = sessionData.user_type;
    
    console.log('👤 User:', { id: currentUserId, type: currentUserType, name: sessionData.name });
    
    await loadConversations();
    
  } catch (err) {
    console.error("💥 Session error:", err);
    
    // Fallback to window.currentUser
    if (window.currentUser) {
      console.log('⚠️ Using fallback user data');
      currentUserId = window.currentUser.id;
      currentUserType = window.currentUser.type;
      await loadConversations();
    } else {
      showNotification('Session expired. Please login again.', 'error');
      setTimeout(() => {
        window.location.href = "../LOGIN/login.php";
      }, 2000);
    }
  }

  /* ========================================
     LOAD CONVERSATIONS
     ======================================== */
  
  async function loadConversations() {
    console.log('🔄 Loading conversations...');
    
    try {
      const res = await fetch(
        `../api/get_conversations.php?user_id=${currentUserId}&user_type=${currentUserType}`
      );
      
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      
      const data = await res.json();
      console.log('📋 Conversations:', data.conversations?.length || 0);

      if (data.success) {
        displayConversations(data.conversations);
        
        // Hide loading state
        const loadingEl = document.getElementById('conversations-loading');
        if (loadingEl) {
          loadingEl.style.opacity = '0';
          setTimeout(() => loadingEl.style.display = 'none', 300);
        }
        
        // Handle empty state
        if (data.conversations.length === 0) {
          const noConvEl = document.getElementById('no-conversations');
          if (noConvEl) {
            noConvEl.style.display = 'block';
            setTimeout(() => noConvEl.style.opacity = '1', 100);
          }
        } else {
          const noConvEl = document.getElementById('no-conversations');
          if (noConvEl) noConvEl.style.display = 'none';
          
          // Select target conversation or first one
          if (targetConversationId) {
            const targetConv = data.conversations.find(
              c => c.conversation_id == targetConversationId
            );
            if (targetConv) {
              console.log('✅ Opening target conversation');
              selectConversation(targetConv);
              window.history.replaceState({}, document.title, window.location.pathname);
            } else {
              selectConversation(data.conversations[0]);
            }
          } else if (!currentConversationId) {
            selectConversation(data.conversations[0]);
          }
        }
      } else {
        throw new Error(data.error || 'Failed to load conversations');
      }
    } catch (err) {
      console.error("❌ Load conversations error:", err);
      showNotification('Failed to load conversations', 'error');
    }
  }

  /* ========================================
     DISPLAY CONVERSATIONS
     ======================================== */
  
  function displayConversations(conversations) {
    const searchInput = document.querySelector(".search-chats");
    conversationsList.innerHTML = "";
    
    // Re-add search input
    if (searchInput) {
      conversationsList.appendChild(searchInput);
    }

    conversations.forEach((conv, index) => {
      const convDiv = document.createElement("div");
      convDiv.className = "convo d-flex align-items-center";
      convDiv.setAttribute("data-conversation-id", conv.conversation_id);
      convDiv.style.opacity = '0';
      convDiv.style.animation = `fadeIn 0.4s ease forwards ${index * 0.05}s`;
      
      const profilePic = conv.other_user_profile_pic ? 
        `../uploads/profiles/${conv.other_user_profile_pic}` : 
        "../img/default-avatar.png";
      
      const lastMessage = conv.last_message || 'No messages yet';
      const truncatedMessage = lastMessage.length > 35 ? 
        lastMessage.substring(0, 35) + '...' : 
        lastMessage;
      
      convDiv.innerHTML = `
        <img src="${profilePic}" alt="Profile" onerror="this.src='../img/home.png'">
        <div style="flex: 1; min-width: 0;">
          <h4>${escapeHtml(conv.other_user_name || conv.other_user_type)}</h4>
          <small>${escapeHtml(truncatedMessage)}</small>
        </div>
      `;

      convDiv.addEventListener("click", () => {
        selectConversation(conv);
      });

      conversationsList.appendChild(convDiv);
    });
  }

  /* ========================================
     SELECT CONVERSATION
     ======================================== */
  
  function selectConversation(conversation) {
    console.log('🎯 Selecting conversation:', conversation.conversation_id);
    
    // Update UI
    document.querySelectorAll(".convo").forEach(conv => {
      conv.classList.remove("active");
    });

    const selectedConv = document.querySelector(
      `[data-conversation-id="${conversation.conversation_id}"]`
    );
    if (selectedConv) {
      selectedConv.classList.add("active");
      selectedConv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    currentConversationId = conversation.conversation_id;
    
    // Update header with animation
    chatHeader.style.opacity = '0';
    setTimeout(() => {
      chatHeader.textContent = `Chat with ${conversation.other_user_name || conversation.other_user_type}`;
      chatHeader.style.opacity = '1';
    }, 150);
    
    // Enable input
    if (chatInput) {
      chatInput.disabled = false;
      chatInput.placeholder = "Type a message...";
    }
    if (sendBtn) sendBtn.disabled = false;
    
    // Clear previous interval
    if (loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
    }
    
    // Load messages
    loadMessages();
    loadMessagesInterval = setInterval(loadMessages, 3000);
  }

  /* ========================================
     SEND MESSAGE
     ======================================== */
  
  if (sendBtn) {
    sendBtn.addEventListener("click", sendMessage);
  }
  
  if (chatInput) {
    chatInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
    
    // Typing indicator (optional - for future implementation)
    chatInput.addEventListener("input", () => {
      if (typingTimeout) clearTimeout(typingTimeout);
      
      if (!isTyping && chatInput.value.trim()) {
        isTyping = true;
        // Send typing status to server
      }
      
      typingTimeout = setTimeout(() => {
        isTyping = false;
        // Send stop typing to server
      }, 1000);
    });
  }

  async function sendMessage() {
    const message = chatInput ? chatInput.value.trim() : '';
    
    if (!message && !selectedFile) return;
    if (!currentConversationId) {
      showNotification('Please select a conversation first', 'warning');
      return;
    }

    // Disable inputs
    if (chatInput) {
      chatInput.disabled = true;
      chatInput.style.opacity = '0.6';
    }
    if (sendBtn) {
      sendBtn.disabled = true;
      sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }

    const formData = new FormData();
    formData.append("conversation_id", currentConversationId);
    
    if (message) {
      formData.append("message", message);
    }
    
    if (selectedFile) {
      formData.append("file", selectedFile);
    }

    try {
      const res = await fetch("../api/send_message.php", {
        method: "POST",
        body: formData
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();
      
      if (data.success) {
        // Clear input
        if (chatInput) chatInput.value = "";
        if (selectedFile) removeFile();
        
        // Reload messages and conversations
        await loadMessages();
        await loadConversations();
        
        // Show success feedback
        if (sendBtn) {
          sendBtn.innerHTML = '<i class="fa-solid fa-check"></i>';
          setTimeout(() => {
            if (sendBtn) sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
          }, 1000);
        }
      } else {
        throw new Error(data.error || 'Failed to send message');
      }
    } catch (err) {
      console.error("❌ Send error:", err);
      showNotification(err.message || "Failed to send message", 'error');
      
      if (sendBtn) {
        sendBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        setTimeout(() => {
          if (sendBtn) sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
        }, 1500);
      }
    } finally {
      // Re-enable inputs
      if (chatInput) {
        chatInput.disabled = false;
        chatInput.style.opacity = '1';
        chatInput.focus();
      }
      if (sendBtn) sendBtn.disabled = false;
    }
  }

  /* ========================================
     LOAD MESSAGES
     ======================================== */
  
  async function loadMessages() {
    if (!currentConversationId || !chatMessages) return;

    try {
      const res = await fetch(
        `../api/get_messages.php?conversation_id=${currentConversationId}`
      );
      
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      
      const data = await res.json();

      if (data.success) {
        const wasAtBottom = isScrolledToBottom();
        
        chatMessages.innerHTML = "";
        
        // Empty state
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

        // Render messages
        data.messages.forEach((msg, index) => {
          const messageEl = createMessageElement(msg, index);
          chatMessages.appendChild(messageEl);
        });

        // Auto-scroll if was at bottom
        if (wasAtBottom) {
          smoothScrollToBottom();
        }
      } else {
        throw new Error(data.error || 'Failed to load messages');
      }
    } catch (err) {
      console.error("❌ Load messages error:", err);
      // Don't show notification for polling errors
    }
  }

  /* ========================================
     CREATE MESSAGE ELEMENT
     ======================================== */
  
  function createMessageElement(msg, index) {
    // Determine if message is mine
    const isMine = (parseInt(msg.sender_id) === parseInt(currentUserId)) && 
                   (msg.sender_type === currentUserType);
    
    const messageDiv = document.createElement("div");
    messageDiv.classList.add("message");
    messageDiv.classList.add(isMine ? "sent" : "received");
    messageDiv.style.animationDelay = `${index * 0.03}s`;
    
    // Avatar
    const avatar = document.createElement("img");
    avatar.classList.add("message-avatar");
    avatar.src = "../img/home.png";
    avatar.alt = "User";
    avatar.onerror = function() { this.src = "../img/home.png"; };
    
    // Bubble container
    const bubbleDiv = document.createElement("div");
    bubbleDiv.classList.add("message-bubble");
    
    // Content
    const contentDiv = document.createElement("div");
    contentDiv.classList.add("message-content");
    
    // Handle files
    if (msg.file_path) {
      const fileDiv = document.createElement("div");
      fileDiv.classList.add("message-file");
      
      if (msg.file_type === 'image') {
        const img = document.createElement("img");
        img.src = msg.file_path;
        img.alt = "Image";
        img.onclick = () => openImageModal(msg.file_path);
        img.style.maxWidth = '300px';
        img.style.maxHeight = '300px';
        fileDiv.appendChild(img);
        contentDiv.appendChild(fileDiv);
        contentDiv.style.background = 'transparent';
        contentDiv.style.border = 'none';
        contentDiv.style.padding = '0';
        contentDiv.style.boxShadow = 'none';
      } else {
        const link = document.createElement("a");
        link.href = msg.file_path;
        link.download = msg.message;
        link.innerHTML = `
          <div class="message-file-icon">
            <i class="fa-solid ${getFileIcon(msg.file_path)}"></i>
          </div>
          <div class="message-file-info">
            <p class="message-file-name">${escapeHtml(msg.message)}</p>
            <p class="message-file-size">${formatFileSize(msg.file_size)}</p>
          </div>
          <i class="fa-solid fa-download"></i>
        `;
        fileDiv.appendChild(link);
        contentDiv.appendChild(fileDiv);
      }
    } else {
      contentDiv.textContent = msg.message;
    }
    
    // Meta (time and status)
    const metaDiv = document.createElement("div");
    metaDiv.classList.add("message-meta");
    
    const timeSpan = document.createElement("span");
    timeSpan.classList.add("message-time");
    timeSpan.textContent = formatMessageTime(msg.created_at);
    timeSpan.title = new Date(msg.created_at).toLocaleString();
    
    metaDiv.appendChild(timeSpan);
    
    if (isMine) {
      const statusSpan = document.createElement("span");
      statusSpan.classList.add("message-status");
      statusSpan.innerHTML = '<i class="fa-solid fa-check-double status-read"></i>';
      metaDiv.appendChild(statusSpan);
    }
    
    // Assemble
    bubbleDiv.appendChild(contentDiv);
    bubbleDiv.appendChild(metaDiv);
    
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(bubbleDiv);
    
    return messageDiv;
  }

  /* ========================================
     FILE HANDLING
     ======================================== */
  
  if (fileInput) {
    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (!file) return;
      
      // Validate file size (10MB max)
      if (file.size > 10485760) {
        showNotification("File too large. Maximum size is 10MB.", 'error');
        fileInput.value = '';
        return;
      }
      
      // Validate file type
      const allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
      ];
      
      if (!allowedTypes.includes(file.type)) {
        showNotification("File type not supported", 'error');
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
    
    // Set appropriate icon
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

  /* ========================================
     SEARCH FUNCTIONALITY
     ======================================== */
  
  const searchInput = document.querySelector(".search-chats");
  if (searchInput) {
    let searchTimeout;
    
    searchInput.addEventListener("input", (e) => {
      clearTimeout(searchTimeout);
      
      const searchTerm = e.target.value.toLowerCase().trim();
      
      searchTimeout = setTimeout(() => {
        document.querySelectorAll(".convo").forEach((conv, index) => {
          const text = conv.textContent.toLowerCase();
          const matches = text.includes(searchTerm);
          
          if (matches) {
            conv.style.display = "flex";
            conv.style.animation = `fadeIn 0.3s ease forwards ${index * 0.05}s`;
          } else {
            conv.style.opacity = '0';
            setTimeout(() => {
              if (!searchTerm || !text.includes(searchTerm)) {
                conv.style.display = "none";
              }
            }, 300);
          }
        });
      }, 300);
    });
  }

  /* ========================================
     UTILITY FUNCTIONS
     ======================================== */
  
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
    
    return messageDate.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric' 
    });
  }

  function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const iconMap = {
      'pdf': 'fa-file-pdf',
      'doc': 'fa-file-word',
      'docx': 'fa-file-word',
      'xls': 'fa-file-excel',
      'xlsx': 'fa-file-excel',
      'txt': 'fa-file-lines',
      'jpg': 'fa-file-image',
      'jpeg': 'fa-file-image',
      'png': 'fa-file-image',
      'gif': 'fa-file-image',
      'webp': 'fa-file-image'
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
    return chatMessages.scrollTop + chatMessages.clientHeight >= 
           chatMessages.scrollHeight - 50;
  }

  function smoothScrollToBottom() {
    if (!chatMessages) return;
    chatMessages.scrollTo({
      top: chatMessages.scrollHeight,
      behavior: 'smooth'
    });
  }

  function openImageModal(imageSrc) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.9);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: zoom-out;
      animation: fadeIn 0.3s ease;
    `;
    
    const img = document.createElement('img');
    img.src = imageSrc;
    img.style.cssText = `
      max-width: 90%;
      max-height: 90%;
      border-radius: 8px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    `;
    
    modal.appendChild(img);
    document.body.appendChild(modal);
    
    modal.onclick = () => {
      modal.style.animation = 'fadeOut 0.3s ease';
      setTimeout(() => document.body.removeChild(modal), 300);
    };
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
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${color.bg};
      color: white;
      padding: 16px 24px;
      border-radius: 12px;
      z-index: 10000;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      align-items: center;
      gap: 12px;
      max-width: 400px;
      font-size: 14px;
      font-weight: 500;
    `;
    
    notification.innerHTML = `
      <i class="fa-solid fa-${color.icon}"></i>
      <span>${escapeHtml(message)}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.style.animation = "slideOut 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards";
      setTimeout(() => {
        if (document.body.contains(notification)) {
          document.body.removeChild(notification);
        }
      }, 400);
    }, 4000);
  }

  /* ========================================
     CLEANUP
     ======================================== */
  
  window.addEventListener("beforeunload", () => {
    if (loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
    }
  });

  // Initial focus
  if (chatInput && !chatInput.disabled) {
    chatInput.focus();
  }

  console.log('✅ Chat system initialized successfully');
});