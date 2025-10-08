document.addEventListener("DOMContentLoaded", async () => {
  const chatMessages = document.querySelector(".chat-messages");
  const chatInput = document.querySelector(".chat-input input[type='text']");
  const sendBtn = document.querySelector(".chat-input button[type='submit']");
  const conversationsList = document.querySelector(".side1");
  const chatHeader = document.querySelector(".chat-header");

  let currentUserId = null;
  let currentConversationId = null;
  let currentUserType = null;
  let loadMessagesInterval = null;
  let selectedFile = null;

  // Check session and get user info
  try {
    const sessionRes = await fetch("../api/session_check.php");
    const sessionData = await sessionRes.json();
    
    if (!sessionData.success) {
      window.location.href = sessionData.redirect || "../LOGIN/login.php";
      return;
    }
    
    currentUserId = sessionData.user_id;
    currentUserType = sessionData.user_type;
    
    loadConversations();
    
  } catch (err) {
    console.error("Session check error:", err);
    if (window.currentUser) {
      currentUserId = window.currentUser.id;
      currentUserType = window.currentUser.type;
      loadConversations();
    } else {
      window.location.href = "../LOGIN/login.php";
    }
  }

  // Check for conversation_id in URL
  const urlParams = new URLSearchParams(window.location.search);
  const targetConversationId = urlParams.get('conversation_id');

  // Load conversations
  async function loadConversations() {
    try {
      const res = await fetch(`../api/get_conversations.php?user_id=${currentUserId}&user_type=${currentUserType}`);
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
      } else {
        console.error("Error loading conversations:", data.error);
      }
    } catch (err) {
      console.error("Fetch error:", err);
    }
  }

  // Display conversations in sidebar
  function displayConversations(conversations) {
    const searchInput = document.querySelector(".search-chats");
    conversationsList.innerHTML = "";
    if (searchInput) {
      conversationsList.appendChild(searchInput);
    }

    conversations.forEach((conv, index) => {
      const convDiv = document.createElement("div");
      convDiv.className = "convo d-flex align-items-center";
      convDiv.setAttribute("data-conversation-id", conv.conversation_id);
      convDiv.style.animationDelay = `${index * 0.1}s`;
      
      const profilePic = conv.other_user_profile_pic ? 
        `../uploads/profiles/${conv.other_user_profile_pic}` : 
        "../img/default-avatar.png";
      
      convDiv.innerHTML = `
        <img src="${profilePic}" alt="" onerror="this.src='../img/home.png'">
        <div style="flex: 1;">
          <h4>${conv.other_user_name || conv.other_user_type + ' (ID: ' + conv.other_user_id + ')'}</h4>
          <small>${conv.last_message || 'No messages yet'}</small>
        </div>
      `;

      convDiv.addEventListener("click", () => {
        selectConversation(conv);
      });

      conversationsList.appendChild(convDiv);
    });
  }

  // Select a conversation
  function selectConversation(conversation) {
    document.querySelectorAll(".convo").forEach(conv => {
      conv.classList.remove("active");
    });

    const selectedConv = document.querySelector(`[data-conversation-id="${conversation.conversation_id}"]`);
    if (selectedConv) {
      selectedConv.classList.add("active");
    }

    currentConversationId = conversation.conversation_id;
    chatHeader.textContent = `Chat with ${conversation.other_user_name || conversation.other_user_type}`;
    
    chatInput.disabled = false;
    sendBtn.disabled = false;
    chatInput.placeholder = "Type a message...";
    
    if (loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
    }
    
    loadMessages();
    loadMessagesInterval = setInterval(loadMessages, 2000);
  }

  // Send message
  sendBtn.addEventListener("click", sendMessage);
  
  chatInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      sendMessage();
    }
  });

  async function sendMessage() {
    const message = chatInput.value.trim();
    
    if (!message && !selectedFile) return;
    if (!currentConversationId) return;

    chatInput.disabled = true;
    sendBtn.disabled = true;

    const formData = new FormData();
    formData.append("conversation_id", currentConversationId);
    formData.append("sender_id", currentUserId);
    
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

      const data = await res.json();
      if (data.success) {
        chatInput.value = "";
        if (selectedFile) removeFile();
        loadMessages();
        loadConversations();
      } else {
        console.error("Error sending message:", data.error);
        showError("Failed to send: " + data.error);
      }
    } catch (err) {
      console.error("Fetch error:", err);
      showError("Network error. Please try again.");
    } finally {
      chatInput.disabled = false;
      sendBtn.disabled = false;
      chatInput.focus();
    }
  }

  // Show error message
  function showError(message) {
    const errorDiv = document.createElement("div");
    errorDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #dc3545;
      color: white;
      padding: 15px 20px;
      border-radius: 8px;
      z-index: 1000;
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
      animation: slideIn 0.3s ease-out;
    `;
    errorDiv.textContent = message;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
      errorDiv.style.animation = "slideOut 0.3s ease-in forwards";
      setTimeout(() => {
        if (document.body.contains(errorDiv)) {
          document.body.removeChild(errorDiv);
        }
      }, 300);
    }, 3000);
  }

  // Load messages
  async function loadMessages() {
    if (!currentConversationId) return;

    try {
      const res = await fetch(`../api/get_messages.php?conversation_id=${currentConversationId}`);
      const data = await res.json();

      if (data.success) {
        const wasAtBottom = chatMessages.scrollTop + chatMessages.clientHeight >= chatMessages.scrollHeight - 10;
        
        chatMessages.innerHTML = "";
        
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

        data.messages.forEach((msg, index) => {
          const messageDiv = document.createElement("div");
          messageDiv.classList.add("message");
          messageDiv.classList.add(msg.sender_id == currentUserId ? "sent" : "received");
          messageDiv.style.animationDelay = `${index * 0.05}s`;
          
          const avatar = document.createElement("img");
          avatar.classList.add("message-avatar");
          avatar.src = "../img/home.png";
          avatar.alt = "User avatar";
          avatar.onerror = function() { this.src = "../img/home.png"; };
          
          const bubbleDiv = document.createElement("div");
          bubbleDiv.classList.add("message-bubble");
          
          const contentDiv = document.createElement("div");
          contentDiv.classList.add("message-content");
          
          // Handle files
          if (msg.file_path) {
            const fileDiv = document.createElement("div");
            fileDiv.classList.add("message-file");
            
            if (msg.file_type === 'image') {
              // Just show the image, no background, no caption
              const img = document.createElement("img");
              img.src = msg.file_path;
              img.alt = "Image";
              img.onclick = function() {
                window.open(msg.file_path, '_blank');
              };
              img.style.background = 'none';
              img.style.border = 'none';
              fileDiv.appendChild(img);
              contentDiv.appendChild(fileDiv);
              contentDiv.style.background = 'none';
              contentDiv.style.border = 'none';
              contentDiv.style.padding = '0';
              contentDiv.style.boxShadow = 'none';
            } else {
              const link = document.createElement("a");
              link.href = msg.file_path;
              link.download = msg.message;
              link.innerHTML = `
                <div class="message-file-icon">
                  <i class="fa-solid fa-file"></i>
                </div>
                <div class="message-file-info">
                  <p class="message-file-name">${msg.message}</p>
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
          
          const metaDiv = document.createElement("div");
          metaDiv.classList.add("message-meta");
          
          const messageDate = new Date(msg.created_at);
          const now = new Date();
          const diffMs = now - messageDate;
          const diffMins = Math.floor(diffMs / 60000);
          const diffHours = Math.floor(diffMs / 3600000);
          const diffDays = Math.floor(diffMs / 86400000);
          
          let timeText;
          if (diffMins < 1) {
            timeText = "Just now";
          } else if (diffMins < 60) {
            timeText = `${diffMins}m ago`;
          } else if (diffHours < 24) {
            timeText = `${diffHours}h ago`;
          } else if (diffDays === 1) {
            timeText = "Yesterday";
          } else if (diffDays < 7) {
            timeText = `${diffDays}d ago`;
          } else {
            timeText = messageDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
          }
          
          const timeSpan = document.createElement("span");
          timeSpan.classList.add("message-time");
          timeSpan.textContent = timeText;
          timeSpan.title = messageDate.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });
          
          metaDiv.appendChild(timeSpan);
          
          if (msg.sender_id == currentUserId) {
            const statusSpan = document.createElement("span");
            statusSpan.classList.add("message-status");
            statusSpan.innerHTML = '<i class="fa-solid fa-check-double status-read"></i>';
            metaDiv.appendChild(statusSpan);
          }
          
          bubbleDiv.appendChild(contentDiv);
          bubbleDiv.appendChild(metaDiv);
          
          messageDiv.appendChild(avatar);
          messageDiv.appendChild(bubbleDiv);
          
          chatMessages.appendChild(messageDiv);
        });

        if (wasAtBottom) {
          chatMessages.scrollTop = chatMessages.scrollHeight;
        }
      } else {
        console.error("Error loading messages:", data.error);
      }
    } catch (err) {
      console.error("Fetch error:", err);
    }
  }

  // File handling
  const fileInput = document.getElementById('file-input');
  if (fileInput) {
    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        // Check file size (10MB max)
        if (file.size > 10485760) {
          showError("File too large. Maximum size is 10MB.");
          fileInput.value = '';
          return;
        }
        selectedFile = file;
        showFilePreview(file);
      }
    });
  }

  function showFilePreview(file) {
    const preview = document.getElementById('file-preview');
    if (!preview) return;
    
    const icon = preview.querySelector('.file-preview-icon i');
    const nameElem = preview.querySelector('.file-preview-name');
    const sizeElem = preview.querySelector('.file-preview-size');
    
    if (file.type.startsWith('image/')) {
      icon.className = 'fa-solid fa-image';
    } else if (file.type.includes('pdf')) {
      icon.className = 'fa-solid fa-file-pdf';
    } else if (file.type.includes('word') || file.type.includes('document')) {
      icon.className = 'fa-solid fa-file-word';
    } else if (file.type.includes('excel') || file.type.includes('spreadsheet')) {
      icon.className = 'fa-solid fa-file-excel';
    } else {
      icon.className = 'fa-solid fa-file';
    }
    
    nameElem.textContent = file.name;
    sizeElem.textContent = formatFileSize(file.size);
    preview.classList.add('show');
  }

  window.removeFile = function() {
    selectedFile = null;
    const fileInput = document.getElementById('file-input');
    if (fileInput) fileInput.value = '';
    const preview = document.getElementById('file-preview');
    if (preview) preview.classList.remove('show');
  }

  function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  // Search functionality
  const searchInput = document.querySelector(".search-chats");
  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      const searchTerm = e.target.value.toLowerCase();
      document.querySelectorAll(".convo").forEach(conv => {
        const text = conv.textContent.toLowerCase();
        conv.style.display = text.includes(searchTerm) ? "flex" : "none";
      });
    });
  }

  // Cleanup
  window.addEventListener("beforeunload", () => {
    if (loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
    }
  });
});