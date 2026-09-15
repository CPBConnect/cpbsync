document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('cpbsync-import-form');

    if (!form) {
        return;
    }

    const progressContainer = document.getElementById(
        'cpbsync-import-progress'
    );

    const progressBar = document.getElementById(
        'cpbsync-progress-bar'
    );

    const progressText = document.getElementById(
        'cpbsync-progress-text'
    );

    const successElement = document.getElementById(
        'cpbsync-success'
    );

    const errorsElement = document.getElementById(
        'cpbsync-errors'
    );

    const resultElement = document.getElementById(
        'cpbsync-import-result'
    );

    const submitButton = document.getElementById(
        'cpbsync-import-submit'
    );

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(form);

        submitButton.disabled = true;

        progressContainer.style.display = 'block';

        resultElement.innerHTML =
            '<div class="alert alert-info">'
            + 'Subiendo archivo...'
            + '</div>';

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.message);
                }

                window.cpbsyncImportId = data.import_id;

                progressText.textContent =
                    '0 / ' + data.total;

                resultElement.innerHTML =
                    '<div class="alert alert-info">'
                    + 'Archivo cargado. Iniciando importación...'
                    + '</div>';

                processNextBatch(
                    data.import_id
                );
            })
            .catch(function (error) {
                submitButton.disabled = false;

                resultElement.innerHTML =
                    '<div class="alert alert-danger">'
                    + error.message
                    + '</div>';
            });
    });

    if (
        window.cpbsyncImportId
        && window.cpbsyncImportId > 0
    ) {
        processNextBatch(
            window.cpbsyncImportId
        );
    }

    function processNextBatch(importId) {
        const url = new URL(
            window.cpbsyncProcessUrl
        );

        url.searchParams.set(
            'import_id',
            importId
        );

        fetch(url.toString(), {
            method: 'GET'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(
                        data.message
                    );
                }

                updateProgress(data);

                if (data.status === 'completed') {
                    resultElement.innerHTML =
                        '<div class="alert alert-success">'
                        + 'Importación completada correctamente.'
                        + '</div>';

                    submitButton.disabled = false;

                    return;
                }

                if (data.status === 'failed') {
                    resultElement.innerHTML =
                        '<div class="alert alert-danger">'
                        + 'La importación terminó con errores.'
                        + '</div>';

                    submitButton.disabled = false;

                    return;
                }

                setTimeout(function () {
                    processNextBatch(importId);
                }, 300);

            })
            .catch(function (error) {
                resultElement.innerHTML =
                    '<div class="alert alert-danger">'
                    + '<strong>Error en la importación:</strong><br>'
                    + error.message
                    + '</div>';

                submitButton.disabled = false;
            });
    }

    function updateProgress(data) {
        progressContainer.style.display = 'block';

        progressBar.style.width =
            data.progress + '%';

        progressBar.textContent =
            data.progress + '%';

        progressText.textContent =
            data.processed
            + ' / '
            + data.total;

        successElement.textContent =
            data.success_count;

        errorsElement.textContent =
            data.errors;
    }
});