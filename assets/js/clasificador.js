document.addEventListener('DOMContentLoaded', function () {
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');
    const previewArea = document.getElementById('previewArea');
    const imagePreview = document.getElementById('imagePreview');
    const classifyBtn = document.getElementById('classifyBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const resultArea = document.getElementById('resultArea');
    const categoriaResultado = document.getElementById('categoriaResultado');
    const confianzaResultado = document.getElementById('confianzaResultado');

    let selectedFile = null;

    uploadArea.addEventListener('click', function () {
        imageInput.click();
    });

    uploadArea.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function (e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    imageInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            handleFile(this.files[0]);
        }
    });

    function handleFile(file) {
        if (!file.type.startsWith('image/')) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo no válido',
                text: 'Por favor selecciona una imagen válida.'
            });
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'La imagen debe ser menor a 10MB.'
            });
            return;
        }

        selectedFile = file;
        const reader = new FileReader();
        reader.onload = function (e) {
            imagePreview.src = e.target.result;
            previewArea.classList.remove('d-none');
            classifyBtn.disabled = false;
        };
        reader.readAsDataURL(file);
    }

    classifyBtn.addEventListener('click', function () {
        if (!selectedFile) return;

        loadingSpinner.classList.remove('d-none');
        resultArea.classList.add('d-none');
        classifyBtn.disabled = true;

        const formData = new FormData();
        formData.append('image', selectedFile);

        fetch('../api_ai/app.php?action=predict', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loadingSpinner.classList.add('d-none');
            classifyBtn.disabled = false;

            if (data.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error
                });
                return;
            }

            categoriaResultado.textContent = data.categoria;
            confianzaResultado.textContent = data.confianza + '%';
            resultArea.classList.remove('d-none');
        })
        .catch(error => {
            loadingSpinner.classList.add('d-none');
            classifyBtn.disabled = false;
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servicio de inteligencia artificial.'
            });
        });
    });
});
