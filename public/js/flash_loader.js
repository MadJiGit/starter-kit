document.addEventListener("DOMContentLoaded", () => {
    const flashElement = document.getElementById("flash-data");
    if (!flashElement) return;

    try {
        const messagesJson = flashElement.getAttribute("data-messages");
        const messages = JSON.parse(messagesJson);

        messages.forEach(({ type, message }) => {
            const flashDiv = document.createElement("div");
            flashDiv.className = `alert alert-${type} flash-message flash-message-php`;

            const messageText = document.createTextNode(message);
            flashDiv.appendChild(messageText);

            const closeBtn = document.createElement("span");
            closeBtn.className = "flash-close";
            closeBtn.appendChild(document.createTextNode("×"));
            closeBtn.onclick = () => flashDiv.remove();
            flashDiv.appendChild(closeBtn);

            let container = document.getElementById("flash-container");
            if (!container) {
                container = document.createElement("div");
                container.id = "flash-container";
                document.body.appendChild(container);
            }

            container.appendChild(flashDiv);
        });

        setTimeout(() => {
            const phpFlashes = document.querySelectorAll('.flash-message-php');
            phpFlashes.forEach(msg => {
                msg.classList.add('fade-out');
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    } catch (e) {
        console.error("⚠️ Failed to parse flash messages:", e);
    }
});