document.addEventListener("DOMContentLoaded", function () {
    let chatContainer = document.createElement("div");
    chatContainer.id = "chatgpt-popup-container";
    chatContainer.innerHTML = `
        <button id="chatgpt-toggle">💬 Chat</button>
        <div id="chatgpt-widget">
            <div id="chatgpt-header">
                <span>Podrška</span>
                <button id="chatgpt-close">✖</button>
            </div>
            <div id="chatgpt-messages"></div>
            <input type="text" id="chatgpt-input" placeholder="Postavite pitanje..." />
            <button id="chatgpt-send">Pošaljite</button>
        </div>
    `;
    document.body.appendChild(chatContainer);

    let chatWidget = document.getElementById("chatgpt-widget");
    let chatToggle = document.getElementById("chatgpt-toggle");
    let chatClose = document.getElementById("chatgpt-close");

    // Prikaz/sakrivanje chata
    chatToggle.addEventListener("click", function () {
        chatWidget.style.display = "block";
        chatToggle.style.display = "none";
    });

    chatClose.addEventListener("click", function () {
        chatWidget.style.display = "none";
        chatToggle.style.display = "block";
    });

    document.getElementById("chatgpt-send").addEventListener("click", function () {
        let input = document.getElementById("chatgpt-input").value.trim();
        let chatBox = document.getElementById("chatgpt-messages");

        if (!input) return; // Ne dozvoljava slanje praznih poruka

        chatBox.innerHTML += `<div class="user-msg">Korisnik: ${input}</div>`;
        document.getElementById("chatgpt-input").value = "";

        // Korišćenje FormData umesto application/x-www-form-urlencoded
        let formData = new FormData();
        formData.append('action', 'chatgpt_request');
        formData.append('message', input);

        // Dodavanje loga da proverimo šta se šalje
        console.log("Sending FormData:", formData);

        fetch("/wp-admin/admin-ajax.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("Server Response:", data); // Dijagnostika odgovora

            if (data.success) {
                chatBox.innerHTML += `<div class="bot-msg">Bot: ${data.data}</div>`;
            } else {
                chatBox.innerHTML += `<div class="error-msg">Greška: ${data.data || 'Nepoznata greška sa servera.'}</div>`;
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            chatBox.innerHTML += `<div class="error-msg">Greška: Nije moguće kontaktirati server.</div>`;
        });
    });
});