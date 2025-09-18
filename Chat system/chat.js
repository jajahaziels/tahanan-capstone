function sendMessage() {
      const input = document.getElementById("msgInput");
      const messageText = input.value.trim();
      if (messageText === "") return;

      const msgContainer = document.createElement("div");
      msgContainer.classList.add("message", "sent");
      msgContainer.textContent = messageText;

      document.getElementById("chat-messages").appendChild(msgContainer);
      input.value = "";
      input.focus();

      // Auto scroll to bottom
      const chatMessages = document.getElementById("chat-messages");
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }