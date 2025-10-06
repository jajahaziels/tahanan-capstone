document.addEventListener("DOMContentLoaded", async () => {
  const chatMessages = document.querySelector(".chat-messages");
  const chatInput = document.querySelector(".chat-input input");
  const sendBtn = document.querySelector(".chat-input button");
  const conversationsList = document.querySelector(".side1");
  const chatHeader = document.querySelector(".chat-header");

  let currentUserId = null;
  let currentConversationId = null;
  let currentUserType = null;
  let loadMessagesInterval = null;

  // Check session and get user info
  try {
    const sessionRes = await fetch("../api/session_check.php");
    const sessionData = await sessionRes.json();
    
    if (!sessionData.success) {
      // Redirect to login if not authenticated
      window.location.href = sessionData.redirect || "../LOGIN/login.php";
      return;
    }
    
    currentUserId = sessionData.user_id;
    currentUserType = sessionData.user_type;
    
    // Load conversations for this user
    loadConversations();
    
  } catch (err) {
    console.error("Session check error:", err);
    // Use data from PHP if available (fallback for development)
    if (window.currentUser) {
      currentUserId = window.currentUser.id;
      currentUserType = window.currentUser.type;
      loadConversations();
    } else {
      window.location.href = "../LOGIN/login.php";
    }
  }

  // Check if there's a conversation_id in URL (from contact landlord)
  const urlParams = new URLSearchParams(window.location.search);
  const targetConversationId = urlParams.get('conversation_id');

  // Load conversations
  async function loadConversations() {
    try {
      const res = await fetch(`../api/get_conversations.php?user_id=${currentUserId}&user_type=${currentUserType}`);
      const data = await res.json();

      if (data.success) {
        displayConversations(data.conversations);
        
        // Hide loading, show conversations or no-conversations message
        const loadingEl = document.getElementById('conversations-loading');
        if (loadingEl) loadingEl.style.display = 'none';
        
        if (data.conversations.length === 0) {
          const noConvEl = document.getElementById('no-conversations');
          if (noConvEl) noConvEl.style.display = 'block';
        } else {
          const noConvEl = document.getElementById('no-conversations');
          if (noConvEl) noConvEl.style.display = 'none';
          // Auto-select first conversation if available
          if (!currentConversationId) {
            selectConversation(data.conversations[0]);
          }
        }
      } else {
        console.error("Error loading conversations:", data.error);
        const loadingEl = document.getElementById('conversations-loading');
        if (loadingEl) {
          loadingEl.innerHTML = '<p style="color: rgba(255,255,255,0.8);">Error loading conversations</p>';
        }
      }
    } catch (err) {
      console.error("Fetch error:", err);
      const loadingEl = document.getElementById('conversations-loading');
      if (loadingEl) {
        loadingEl.innerHTML = '<p style="color: rgba(255,255,255,0.8);">Network error</p>';
      }
    }
  }

  // Display conversations in sidebar
  function displayConversations(conversations) {
    // Clear existing conversations (keep search input)
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
      
      // Use profile picture if available, otherwise default
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

    // Add loading states to DOM if they don't exist
    if (!document.getElementById('conversations-loading')) {
      const loadingDiv = document.createElement("div");
      loadingDiv.id = "conversations-loading";
      loadingDiv.className = "loading-state";
      loadingDiv.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        <p>Loading conversations...</p>
      `;
      conversationsList.appendChild(loadingDiv);
    }

    if (!document.getElementById('no-conversations')) {
      const noConvDiv = document.createElement("div");
      noConvDiv.id = "no-conversations";
      noConvDiv.className = "no-conversations-state";
      noConvDiv.innerHTML = `
        <i class="fa-solid fa-comments"></i>
        <p>No conversations yet</p>
        <small>Contact your landlord to start chatting</small>
      `;
      conversationsList.appendChild(noConvDiv);
    }
  }

  // Select a conversation
  function selectConversation(conversation) {
    // Remove active class from previous selection
    document.querySelectorAll(".convo").forEach(conv => {
      conv.classList.remove("active");
    });

    // Add active class to current selection
    const selectedConv = document.querySelector(`[data-conversation-id="${conversation.conversation_id}"]`);
    if (selectedConv) {
      selectedConv.classList.add("active");
    }

    currentConversationId = conversation.conversation_id;
    chatHeader.textContent = `Chat with ${conversation.other_user_name || conversation.other_user_type}`;
    
    // Enable input
    chatInput.disabled = false;
    sendBtn.disabled = false;
    chatInput.placeholder = "Type a message...";
    
    // Clear previous interval
    if (loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
    }
    
    // Load messages for this conversation
    loadMessages();
    
    // Start auto-refresh
    loadMessagesInterval = setInterval(loadMessages, 2000);
  }

  // Send message
  sendBtn.addEventListener("click", sendMessage);
  
  // Send message on Enter key
  chatInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      sendMessage();
    }
  });

  async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message || !currentConversationId) return;

    // Disable input temporarily
    chatInput.disabled = true;
    sendBtn.disabled = true;

    const formData = new FormData();
    formData.append("conversation_id", currentConversationId);
    formData.append("sender_id", currentUserId);
    formData.append("message", message);

    try {
      const res = await fetch("../api/send_message.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();
      if (data.success) {
        chatInput.value = "";
        loadMessages(); // Immediately load messages after sending
        loadConversations(); // Refresh conversations to update last message
      } else {
        console.error("Error sending message:", data.error);
        showError("Failed to send message: " + data.error);
      }
    } catch (err) {
      console.error("Fetch error:", err);
      showError("Network error. Please try again.");
    } finally {
      // Re-enable input
      chatInput.disabled = false;
      sendBtn.disabled = false;
      chatInput.focus();
    }
  }

  // Show error message
  function showError(message) {
    // Create temporary error notification
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
    
    // Remove after 3 seconds
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
        
        // If no messages, show empty state
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
          
          // Create avatar
          const avatar = document.createElement("img");
          avatar.classList.add("message-avatar");
          avatar.src = "../img/home.png"; // Default avatar
          avatar.alt = "User avatar";
          avatar.onerror = function() { this.src = "../img/home.png"; };
          
          // Create message bubble container
          const bubbleDiv = document.createElement("div");
          bubbleDiv.classList.add("message-bubble");
          
          // Create message content
          const contentDiv = document.createElement("div");
          contentDiv.classList.add("message-content");
          contentDiv.textContent = msg.message;
          
          // Create message meta (timestamp and status)
          const metaDiv = document.createElement("div");
          metaDiv.classList.add("message-meta");
          
          // Format timestamp
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
          
          // Add status icon for sent messages
          if (msg.sender_id == currentUserId) {
            const statusSpan = document.createElement("span");
            statusSpan.classList.add("message-status");
            statusSpan.innerHTML = '<i class="fa-solid fa-check-double status-read"></i>';
            metaDiv.appendChild(statusSpan);
          }
          
          // Assemble the message
          bubbleDiv.appendChild(contentDiv);
          bubbleDiv.appendChild(metaDiv);
          
          messageDiv.appendChild(avatar);
          messageDiv.appendChild(bubbleDiv);
          
          chatMessages.appendChild(messageDiv);
        });

        // Scroll to bottom if user was at bottom before update
        if (wasAtBottom) {
          chatMessages.scrollTop = chatMessages.scrollHeight;
        }
      } else {
        console.error("Error loading messages:", data.error);
        chatMessages.innerHTML = `
          <div class="empty-chat">
            <div class="empty-chat-content">
              <i class="fa-solid fa-exclamation-triangle" style="color: #dc3545;"></i>
              <p style="color: #dc3545;">Error loading messages</p>
              <small style="color: #666;">${data.error}</small>
            </div>
          </div>
        `;
      }
    } catch (err) {
      console.error("Fetch error:", err);
      chatMessages.innerHTML = `
        <div class="empty-chat">
          <div class="empty-chat-content">
            <i class="fa-solid fa-wifi" style="color: #dc3545;"></i>
            <p style="color: #dc3545;">Connection error</p>
            <small style="color: #666;">Please check your internet connection</small>
          </div>
        </div>
      `;
    }
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

  // Add CSS for error notifications
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
  `;
  document.head.appendChild(style);

  // Cleanup on page unload
  window.addEventListener("beforeunload", () => {
    if (loadMessagesInterval) {
      clearInterval(loadMessagesInterval);
    }
  });
});