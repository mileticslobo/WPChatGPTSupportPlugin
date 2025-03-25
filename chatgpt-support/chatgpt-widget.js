document.addEventListener("DOMContentLoaded", function () {
    let chatContainer = document.createElement("div");
    chatContainer.innerHTML = `
        <div id="chatgpt-widget" style="position: fixed; bottom: 20px; right: 20px; width: 300px; height: 400px; background: white; border: 1px solid black; padding: 10px;">
            <div id="chatgpt-messages" style="height: 80%; overflow-y: auto;"></div>
            <input type="text" id="chatgpt-input" placeholder="Postavite pitanje..." style="width: 100%;" />
            <button id="chatgpt-send">Pošalji</button>
        </div>
    `;
    document.body.appendChild(chatContainer);

    document.getElementById("chatgpt-send").addEventListener("click", function () {
        let input = document.getElementById("chatgpt-input").value;
        let chatBox = document.getElementById("chatgpt-messages");

        chatBox.innerHTML += `<div>Korisnik: ${input}</div>`;

        fetch('/wp-admin/admin-ajax.php?action=chatgpt_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(input)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chatBox.innerHTML += `<div>Bot: ${data.data}</div>`;
            } else {
                chatBox.innerHTML += `<div>Greška: ${data.data}</div>`;
            }
        });
    });
});