document.addEventListener("DOMContentLoaded", () => {
  const chatMessages = document.querySelector(".chat-messages");
  const chatInput = document.querySelector(".chat-input input");
  const sendBtn = document.querySelector(".chat-input button");

  // Replace with actual session values (dynamic later)
  const senderId = 1;   // Example landlord ID
  const conversationId = 101; // Example conversation ID

  // Send message
  sendBtn.addEventListener("click", async () => {
    const message = chatInput.value.trim();
    if (!message) return;

    const formData = new FormData();
    formData.append("conversation_id", conversationId);
    formData.append("sender_id", senderId);
    formData.append("message", message);

    try {
      const res = await fetch("../api/send_message.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();
      if (data.success) {
        chatInput.value = "";
        loadMessages();
      } else {
        console.error("Error sending message:", data.error);
      }
    } catch (err) {
      console.error("Fetch error:", err);
    }
  });

  // Load messages
  async function loadMessages() {
    try {
      const res = await fetch(`../api/get_messages.php?conversation_id=${conversationId}`);
      const data = await res.json();

      if (data.success) {
        chatMessages.innerHTML = "";
        data.messages.forEach(msg => {
          const div = document.createElement("div");
          div.classList.add("message");
          div.classList.add(msg.sender_id == senderId ? "sent" : "received");
          div.textContent = msg.message;
          chatMessages.appendChild(div);
        });

        chatMessages.scrollTop = chatMessages.scrollHeight;
      } else {
        console.error("Error loading messages:", data.error);
      }
    } catch (err) {
      console.error("Fetch error:", err);
    }
  }

  // Refresh messages every 2s
  setInterval(loadMessages, 2000);
  loadMessages();
});
