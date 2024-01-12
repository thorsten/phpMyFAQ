export const handleUploadCSVForm = async () => {
    const submitButton = document.getElementById('submitButton');
    if (submitButton) {
        submitButton.addEventListener('click', async (event) => {
            const fileInput = document.getElementById('fileInputCSVUpload');
            const form = document.getElementById('uploadCSVFileForm');
            const csrf = form.getAttribute('data-pmf-csrf');
            const file = fileInput.files[0];
            event.preventDefault();
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf', csrf);
            try {
                const response = await fetch('./api/faq/import', {
                    method: 'POST',
                    body: formData
                });
                if (response.ok) {
                    const jsonResponse = await response.json();
                    document.getElementById('divImportColumns').insertAdjacentHTML('beforebegin',
                            '<div class="alert alert-success alert-dismissible fade show">' + jsonResponse.success +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                    fileInput.value = null;
                } else {
                    const errorResponse = await response.json();
                    throw new Error('Network response was not ok: ' + JSON.stringify(errorResponse));
                }
            } catch (error) {
                if (error.storedAll === false) {
                    error.messages.forEach(message => {
                        document.getElementById('divImportColumns').insertAdjacentHTML('beforebegin',
                                '<div class="alert alert-success alert-dismissible fade show">' + message +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                    });
                }
            }
        });
    }
};

