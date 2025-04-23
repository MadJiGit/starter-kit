function showFlashMessage(type, message) {
    removeOldMessages();

    const now = Date.now();
    if (now - lastMessageTime < 2000) {
        return;
    }
    lastMessageTime = now;

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

    setTimeout(() => {
        flashDiv.classList.add("fade-out");
        setTimeout(() => flashDiv.remove(), 500);
    }, 5000);
}

let lastMessageTime = 0;

function removeOldMessages() {
    const existingMessages = document.querySelectorAll("#flash-container .flash-message:not(.flash-message-php)");
    existingMessages.forEach(msg => msg.remove());
}