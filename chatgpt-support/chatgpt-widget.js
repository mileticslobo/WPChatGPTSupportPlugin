document.addEventListener("DOMContentLoaded", function () {
    let chatContainer = document.createElement("div");
    chatContainer.id = "chatgpt-popup-container";
    chatContainer.innerHTML = `
        <button id="chatgpt-toggle">💬 Chat</button>
        <div id="chatgpt-widget" style="display: none;"> <!-- Set display to none -->
            <div id="chatgpt-header">
                <span>Hello</span>
                <button id="chatgpt-close">✖</button>
            </div>
            <div id="chatgpt-messages"></div>
            <div id="chatgpt-input-container">
                <input type="text" id="chatgpt-input" placeholder="Ask the question..." />
                <button id="chatgpt-send">Send</button>
            </div>
            <div id="chatgpt-typing-indicator" style="display: none;">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    `;
    document.body.appendChild(chatContainer);

    let chatWidget = document.getElementById("chatgpt-widget");
    let chatToggle = document.getElementById("chatgpt-toggle");
    let chatClose = document.getElementById("chatgpt-close");
    let chatInput = document.getElementById("chatgpt-input");
    let chatSend = document.getElementById("chatgpt-send");
    let chatMessages = document.getElementById("chatgpt-messages");
    let typingIndicator = document.getElementById("chatgpt-typing-indicator");

    // Load chat messages from localStorage
    function loadMessages() {
        const savedMessages = JSON.parse(localStorage.getItem("chatgpt-messages")) || [];
        savedMessages.forEach(({ content, type }) => {
            addMessage(content, type);
        });
    }

    // Save chat messages to localStorage
    function saveMessages() {
        const messages = Array.from(chatMessages.children).map((messageDiv) => {
            return {
                content: messageDiv.textContent,
                type: messageDiv.className.replace("-msg", "")
            };
        });
        localStorage.setItem("chatgpt-messages", JSON.stringify(messages));
    }

    // Scroll to bottom of chat
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Show typing indicator
    function showTypingIndicator() {
        typingIndicator.style.display = "flex";
        scrollToBottom();
    }

    // Hide typing indicator
    function hideTypingIndicator() {
        typingIndicator.style.display = "none";
        scrollToBottom();
    }

    // Add message to chat
    function addMessage(content, type) {
        const messageDiv = document.createElement("div");
        messageDiv.className = `${type}-msg`;
        messageDiv.textContent = `${type === 'user' ? 'Korisnik' : 'Bot'}: ${content}`;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
        saveMessages(); // Save messages after adding a new one
    }

    async function sendChatRequest(input) {
        const formData = new FormData();
        formData.append('action', 'chatgpt_request');
        formData.append('message', input);

        const response = await fetch("/wp-admin/admin-ajax.php", {
            method: "POST",
            body: formData
        });

        if (!response.ok) {
            throw new Error('Server error: Unable to process the request.');
        }

        return response.json();
    }

    // Handle message sending
    async function handleSendMessage() {
        const input = chatInput.value.trim();
        if (!input) return;

        // Disable input and send button while processing
        chatInput.disabled = true;
        chatSend.disabled = true;

        // Add user message
        addMessage(input, 'user');
        chatInput.value = "";

        // Show typing indicator
        showTypingIndicator();

        try {
            const data = await sendChatRequest(input);

            // Hide typing indicator
            hideTypingIndicator();

            if (data.success) {
                addMessage(data.data, 'bot');
            } else {
                addMessage(data.data || 'Unknown server error.', 'error');
            }
        } catch (error) {
            console.error("Fetch error:", error);
            hideTypingIndicator();
            addMessage('Error: Unable to contact the server.', 'error');
        } finally {
            // Re-enable input and send button
            chatInput.disabled = false;
            chatSend.disabled = false;
            chatInput.focus();
        }
    }

    // Event listeners
    chatToggle.addEventListener("click", function () {
        chatWidget.style.display = "block";
        chatToggle.style.display = "none";
        chatInput.focus();

        // Check if it's the first time opening the chat
        if (!localStorage.getItem("chatgpt-welcome-shown")) {
            addMessage("Dobrodošli! Kako vam mogu pomoći danas?", "bot"); // Welcome message
            localStorage.setItem("chatgpt-welcome-shown", "true");
        }

        // Save state to localStorage
        localStorage.setItem("chatgpt-widget-opened", "true");
    });

    chatClose.addEventListener("click", function () {
        chatWidget.style.display = "none";
        chatToggle.style.display = "block";

        // Save state to localStorage
        localStorage.setItem("chatgpt-widget-opened", "false");
    });

    chatSend.addEventListener("click", handleSendMessage);

    // Handle Enter key
    chatInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    });

    // Load messages on page load
    loadMessages();
});