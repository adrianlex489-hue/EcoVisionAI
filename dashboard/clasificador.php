<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAuth();

$titulo = 'Clasificador IA';
$css_extra = 'dashboard.css';
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <h5 class="mb-0"><i class="bi bi-camera me-2"></i>Clasificador IA</h5>
            <div class="d-flex align-items-center">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo $_SESSION['usuario_nombres']; ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card border-0 shadow-sm classifier-card anim-fade-in-up">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fs-6">
                                <i class="bi bi-robot me-1"></i> Clasificación por IA
                            </span>
                            <h4 class="mt-3 mb-1">Clasificador de Residuos</h4>
                            <p class="text-muted mb-0">Sube una o varias imágenes y el sistema detectará automáticamente la categoría de cada una.</p>
                        </div>

                        <div class="upload-area mb-4 text-center" id="uploadArea">
                            <div class="upload-icon mb-2">
                                <i class="bi bi-cloud-arrow-up fs-1 text-success"></i>
                            </div>
                            <p class="mt-2 mb-0 fw-medium">Haz clic o arrastra imágenes aquí</p>
                            <small class="text-muted">JPG, PNG, WEBP - Máx 10MB c/u - Puedes seleccionar varias</small>
                            <input type="file" id="imageInput" accept="image/*" class="d-none" multiple>
                        </div>

                        <div id="imageQueue" class="mb-3 d-none">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-medium" id="queueCount">0 imágenes seleccionadas</span>
                                <button id="clearAllBtn" class="btn btn-sm btn-outline-danger d-none"><i class="bi bi-trash me-1"></i>Limpiar todo</button>
                            </div>
                            <div class="d-flex flex-wrap gap-2" id="queueContainer"></div>
                        </div>

                        <div id="loadingArea" class="d-none mb-4">
                            <div class="card bg-light border-0 rounded-3 p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="pulse-dots me-3">
                                        <span class="pulse-dot bg-success"></span>
                                        <span class="pulse-dot bg-success"></span>
                                        <span class="pulse-dot bg-success"></span>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium" id="loadingStatus">Clasificando imágenes...</p>
                                        <small class="text-muted" id="loadingProgress">Procesando 0 de 0</small>
                                    </div>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div id="mainProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%;">0%</div>
                                </div>
                            </div>
                        </div>

                        <div id="resultsArea" class="d-none mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0"><i class="bi bi-check2-square text-success me-2"></i>Resultados</h5>
                                <span class="badge bg-success rounded-pill px-3 py-2" id="resultsSummary">0 clasificadas</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="resultsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th style="width: 80px;">Vista</th>
                                            <th>Archivo</th>
                                            <th>Categoría</th>
                                            <th>Confianza</th>
                                            <th style="width: 60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="resultsBody"></tbody>
                                </table>
                            </div>
                            <div id="resultsChart" class="mt-3 p-3 bg-light rounded-3 d-none">
                                <h6 class="mb-2"><i class="bi bi-pie-chart me-2"></i>Resumen por categoría</h6>
                                <div id="categorySummary" class="d-flex flex-wrap gap-3"></div>
                            </div>
                        </div>

                        <button id="classifyBtn" class="btn btn-success btn-lg w-100" disabled>
                            <i class="bi bi-search me-2"></i>Clasificar todas las imágenes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upload-area {
    border: 2px dashed #198754;
    border-radius: 16px;
    padding: 40px 20px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: #f0faf4;
}
.upload-area:hover {
    border-color: #0d6e3e;
    background-color: #d1f0dc;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(25, 135, 84, 0.15);
}
.upload-area.dragover {
    border-color: #0d6e3e;
    background-color: #d1f0dc;
    transform: scale(1.02);
    box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.2);
}
.upload-icon { transition: transform 0.3s ease; }
.upload-area:hover .upload-icon { transform: translateY(-5px); }
.queue-thumb {
    width: 70px; height: 70px; object-fit: cover;
    border-radius: 10px; cursor: pointer;
    transition: all 0.2s ease;
    border: 3px solid transparent;
    position: relative;
}
.queue-thumb:hover { transform: scale(1.1); border-color: #198754; }
.queue-thumb.done { border-color: #198754; opacity: 0.9; }
.queue-thumb.done::after {
    content: '\F26E'; font-family: 'bootstrap-icons';
    position: absolute; top: -6px; right: -6px;
    color: #198754; font-size: 18px;
    background: white; border-radius: 50%;
}
.queue-thumb.error { border-color: #dc3545; }
.pulse-dots { display: flex; align-items: center; gap: 6px; }
.pulse-dot {
    width: 10px; height: 10px; border-radius: 50%;
    animation: pulseDot 1.4s ease-in-out infinite;
}
.pulse-dot:nth-child(2) { animation-delay: 0.2s; }
.pulse-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes pulseDot {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}
.result-row { animation: fadeInUp 0.3s ease forwards; opacity: 0; }
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.confidence-bar { height: 8px; border-radius: 4px; transition: width 0.6s ease; }
.result-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
.status-badge { font-size: 0.75rem; padding: 4px 10px; }
@media (max-width: 576px) {
    .upload-area { padding: 25px 15px; }
    .upload-area i.fs-1 { font-size: 2rem !important; }
}
</style>

<script>
(function() {
    'use strict';

    var uploadArea = document.getElementById('uploadArea');
    var imageInput = document.getElementById('imageInput');
    var classifyBtn = document.getElementById('classifyBtn');
    var imageQueue = document.getElementById('imageQueue');
    var queueContainer = document.getElementById('queueContainer');
    var queueCount = document.getElementById('queueCount');
    var clearAllBtn = document.getElementById('clearAllBtn');
    var loadingArea = document.getElementById('loadingArea');
    var loadingStatus = document.getElementById('loadingStatus');
    var loadingProgress = document.getElementById('loadingProgress');
    var mainProgressBar = document.getElementById('mainProgressBar');
    var resultsArea = document.getElementById('resultsArea');
    var resultsBody = document.getElementById('resultsBody');
    var resultsSummary = document.getElementById('resultsSummary');
    var resultsChart = document.getElementById('resultsChart');
    var categorySummary = document.getElementById('categorySummary');

    if (!uploadArea || !classifyBtn) return;

    var imageQueueList = [];
    var resultsList = [];

    var categoryColors = {
        'Pl\u00e1stico': { bg: '#e3f2fd', text: '#1565c0', bar: '#1565c0' },
        'Papel y cart\u00f3n': { bg: '#e8f5e9', text: '#2e7d32', bar: '#2e7d32' },
        'Vidrio': { bg: '#fff8e1', text: '#f9a825', bar: '#f9a825' },
        'Metal': { bg: '#ffebee', text: '#c62828', bar: '#c62828' },
        'Org\u00e1nico': { bg: '#efebe9', text: '#5d4037', bar: '#795548' }
    };

    function normalize(cat) {
        var map = { '\u00e1':'a','\u00e9':'e','\u00ed':'i','\u00f3':'o','\u00fa':'u','\u00c1':'A','\u00c9':'E','\u00cd':'I','\u00d3':'O','\u00da':'U','\u00f1':'n' };
        return cat.replace(/[^\x00-\x7F]/g, function(ch) { return map[ch] || ch; }).toLowerCase();
    }

    function getCS(cat) {
        var ncat = normalize(cat);
        for (var key in categoryColors) {
            if (ncat.indexOf(normalize(key)) !== -1) return categoryColors[key];
        }
        return { bg: '#f3e5f5', text: '#7b1fa2', bar: '#7b1fa2' };
    }

    function handleFiles(files) {
        for (var j = 0; j < files.length; j++) {
            var f = files[j];
            if (!f.type || f.type.indexOf('image/') !== 0) continue;
            if (f.size > 10 * 1024 * 1024) continue;
            var dup = false;
            for (var k = 0; k < imageQueueList.length; k++) {
                if (imageQueueList[k].name === f.name && imageQueueList[k].size === f.size) { dup = true; break; }
            }
            if (!dup) imageQueueList.push(f);
        }
        updateQueue();
    }

    function updateQueue() {
        var len = imageQueueList.length;
        imageQueue.classList.toggle('d-none', len === 0);
        clearAllBtn.classList.toggle('d-none', len <= 1);
        queueCount.textContent = len + ' imagen' + (len !== 1 ? 'es' : '') + ' seleccionada' + (len !== 1 ? 's' : '');
        classifyBtn.disabled = len === 0;
        classifyBtn.innerHTML = '<i class="bi bi-search me-2"></i>Clasificar ' + (len > 1 ? 'todas las' : 'la') + ' im\u00e1genes (' + len + ')';
        if (len > 0) {
            classifyBtn.classList.remove('d-none');
            var historyBtn = document.getElementById('historyBtn');
            if (historyBtn) historyBtn.classList.add('d-none');
        }
        queueContainer.innerHTML = '';
        for (var i = 0; i < len; i++) {
            (function(idx) {
                var file = imageQueueList[idx];
                var url = URL.createObjectURL(file);
                var wrap = document.createElement('div');
                wrap.style.position = 'relative';
                var img = document.createElement('img');
                img.src = url;
                img.className = 'queue-thumb';
                img.title = file.name;
                var del = document.createElement('button');
                del.innerHTML = '<i class="bi bi-x"></i>';
                del.className = 'btn btn-sm btn-danger position-absolute';
                del.style.cssText = 'top:-6px;right:-6px;width:22px;height:22px;border-radius:50%;padding:0;font-size:11px;line-height:1;';
                del.onclick = function(e) {
                    e.stopPropagation();
                    imageQueueList.splice(idx, 1);
                    updateQueue();
                };
                wrap.appendChild(img);
                wrap.appendChild(del);
                queueContainer.appendChild(wrap);
            })(i);
        }
    }

    uploadArea.onclick = function() { imageInput.click(); };
    uploadArea.ondragover = function(e) { e.preventDefault(); this.classList.add('dragover'); };
    uploadArea.ondragleave = function(e) { e.preventDefault(); this.classList.remove('dragover'); };
    uploadArea.ondrop = function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files);
    };
    imageInput.onchange = function(e) {
        if (e.target.files.length) handleFiles(e.target.files);
    };

    clearAllBtn.onclick = function() {
        imageQueueList = [];
        resultsList = [];
        resultsArea.classList.add('d-none');
        var historyBtn = document.getElementById('historyBtn');
        if (historyBtn) historyBtn.classList.add('d-none');
        classifyBtn.classList.remove('d-none');
        updateQueue();
    };

    function processNext(index) {
        if (index >= imageQueueList.length) {
            mainProgressBar.style.width = '100%';
            mainProgressBar.textContent = '100%';
            loadingStatus.textContent = 'Clasificaci\u00f3n completada';
            loadingProgress.textContent = imageQueueList.length + ' imagen' + (imageQueueList.length !== 1 ? 'es' : '') + ' clasificada' + (imageQueueList.length !== 1 ? 's' : '');
            setTimeout(function() {
                loadingArea.classList.add('d-none');
                resultsArea.classList.remove('d-none');
                classifyBtn.classList.add('d-none');
                var historyBtn = document.getElementById('historyBtn');
                if (!historyBtn) {
                    historyBtn = document.createElement('a');
                    historyBtn.id = 'historyBtn';
                    historyBtn.className = 'btn btn-outline-success btn-lg w-100 mt-2';
                    historyBtn.innerHTML = '<i class="bi bi-clock-history me-2"></i>Revisar historial';
                    historyBtn.href = 'historial.php';
                    classifyBtn.parentNode.insertBefore(historyBtn, classifyBtn.nextSibling);
                } else {
                    historyBtn.classList.remove('d-none');
                }
                renderResults();
                renderChart();
                var ok = 0;
                for (var i = 0; i < resultsList.length; i++) { if (!resultsList[i].error) ok++; }
                Swal.fire({ icon: 'success', title: 'Clasificaci\u00f3n completada', text: ok + ' de ' + resultsList.length + ' im\u00e1genes clasificadas correctamente.', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
            }, 800);
            return;
        }

        var file = imageQueueList[index];
        loadingStatus.textContent = 'Clasificando "' + file.name + '"...';
        loadingProgress.textContent = 'Procesando ' + (index + 1) + ' de ' + imageQueueList.length;
        var pct = Math.round((index / imageQueueList.length) * 100);
        mainProgressBar.style.width = pct + '%';
        mainProgressBar.textContent = pct + '%';

        var formData = new FormData();
        formData.append('image', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../api_ai/app.php?action=predict', true);
        xhr.onload = function() {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.categoria) {
                    resultsList.push({ file: file, categoria: data.categoria, confianza: data.confianza, error: false });
                } else {
                    resultsList.push({ file: file, categoria: data.error || 'Error', confianza: 0, error: true });
                }
            } catch (e) {
                resultsList.push({ file: file, categoria: 'Error al procesar respuesta', confianza: 0, error: true });
            }
            processNext(index + 1);
        };
        xhr.onerror = function() {
            resultsList.push({ file: file, categoria: 'Error de conexion', confianza: 0, error: true });
            processNext(index + 1);
        };
        xhr.ontimeout = function() {
            resultsList.push({ file: file, categoria: 'Tiempo de espera agotado', confianza: 0, error: true });
            processNext(index + 1);
        };
        xhr.timeout = 60000;
        xhr.send(formData);
    }

    classifyBtn.onclick = function() {
        if (imageQueueList.length === 0) return;

        resultsList = [];
        resultsBody.innerHTML = '';
        resultsArea.classList.add('d-none');
        resultsChart.classList.add('d-none');
        loadingArea.classList.remove('d-none');
        classifyBtn.disabled = true;

        processNext(0);
    };

    function renderResults() {
        resultsBody.innerHTML = '';
        var ok = 0;
        for (var i = 0; i < resultsList.length; i++) {
            var r = resultsList[i];
            var style = getCS(r.categoria);
            var conf = Math.round(r.confianza);
            var tr = document.createElement('tr');
            if (!r.error) ok++;
            var thumbUrl = URL.createObjectURL(r.file);
            var nameEscaped = r.file.name.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            var catHtml = r.error
                ? '<span class="badge bg-danger">' + r.categoria.replace(/</g, '&lt;') + '</span>'
                : '<span class="badge rounded-pill" style="background:' + style.bg + ';color:' + style.text + ';border:1px solid ' + style.text + '33">' + r.categoria + '</span>';
            var confHtml = r.error
                ? '<small class="text-danger">-</small>'
                : '<div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar confidence-bar" style="width:' + conf + '%;background:' + style.bar + '"></div></div><small class="fw-medium" style="color:' + style.text + '">' + conf + '%</small></div>';
            var iconHtml = r.error ? '<i class="bi bi-x-circle text-danger"></i>' : '<i class="bi bi-check-circle-fill text-success"></i>';
            tr.innerHTML = '<td class="fw-medium text-muted">' + (i + 1) + '</td><td><img src="' + thumbUrl + '" class="result-thumb" alt=""></td><td class="small text-truncate" style="max-width:150px">' + nameEscaped + '</td><td>' + catHtml + '</td><td style="min-width:120px">' + confHtml + '</td><td>' + iconHtml + '</td>';
            resultsBody.appendChild(tr);
        }
        resultsSummary.textContent = ok + '/' + resultsList.length + ' clasificadas';
    }

    function renderChart() {
        var counts = {};
        for (var i = 0; i < resultsList.length; i++) {
            if (!resultsList[i].error) {
                counts[resultsList[i].categoria] = (counts[resultsList[i].categoria] || 0) + 1;
            }
        }
        var cats = Object.keys(counts);
        if (cats.length === 0) return;
        resultsChart.classList.remove('d-none');
        categorySummary.innerHTML = '';
        var total = 0;
        for (var c in counts) total += counts[c];
        for (var ci = 0; ci < cats.length; ci++) {
            var cat = cats[ci];
            var style = getCS(cat);
            var pct = Math.round((counts[cat] / total) * 100);
            var div = document.createElement('div');
            div.className = 'd-flex align-items-center gap-2';
            div.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:' + style.bar + '"></span><span class="small">' + cat + '</span><span class="fw-medium small">' + counts[cat] + '</span><span class="small text-muted">(' + pct + '%)</span>';
            categorySummary.appendChild(div);
        }
    }
})();
</script>
<?php include_once '../includes/footer.php'; ?>
