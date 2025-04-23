window.addEventListener("load", function () {

    setTimeout(function () {
        let deleteUrl = '';
        let deleteToken = '';
        const confirmDeleteModalElement = document.getElementById('confirmDeleteModal');

        if (!confirmDeleteModalElement) {
            console.error("ConfirmDeleteModal not found in the DOM!");
            return;
        }

        const confirmDeleteModal = new bootstrap.Modal(confirmDeleteModalElement);

        document.querySelectorAll(".delete-item").forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();

                deleteUrl = this.dataset.url;
                deleteToken = this.dataset.token;

                confirmDeleteModal.show();
            });
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
            // console.log("Delete URL:", deleteUrl);
            // console.log("Delete Token:", deleteToken);

            fetch(deleteUrl, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `_token=${deleteToken}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        if (typeof window.translations !== 'undefined' && window.translations.js && window.translations.js.delete && window.translations.js.delete.failed) {
                            showFlashMessage("danger", window.translations.js.delete.failed);
                        } else {
                            showFlashMessage("danger", data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error(error);
                    if (typeof window.translations !== 'undefined' && window.translations.js && window.translations.js.delete && window.translations.js.delete.failed) {
                        showFlashMessage("danger", window.translations.js.delete.failed);
                    } else {
                        showFlashMessage("danger", window.translations.js.delete.error || "Error: Unable to delete item.");
                    }
                });

            confirmDeleteModal.hide();
        });

    }, 100); // Изчакване 100ms, за да се зареди DOM-а напълно
});