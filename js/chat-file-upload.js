// chat-file-upload.js - File upload functionality for chat system

let selectedFile = null;

// Handle file selection
document.getElementById('file-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        selectedFile = file;
        showFilePreview(file);
    }
});

// Show file preview
function showFilePreview(file) {
    const preview = document.getElementById('file-preview');
    const icon = preview.querySelector('.file-preview-icon i');
    const nameElem = preview.querySelector('.file-preview-name');
    const sizeElem = preview.querySelector('.file-preview-size');
    
    // Set icon based on file type
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

// Remove selected file
function removeFile() {
    selectedFile = null;
    document.getElementById('file-input').value = '';
    document.getElementById('file-preview').classList.remove('show');
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Update the sendMessage function to handle files
function updateSendMessageWithFiles(originalSendMessage) {
    return async function() {
        const message = chatInput.value.trim();
        
        // Must have either message or file
        if (!message && !selectedFile) return;
        if (!currentConversationId) return;

        // Disable input temporarily
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
                removeFile(); // Clear file selection
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
    };
}