(function () {
    'use strict';

    var MAX_FILE_SIZE  = 5 * 1024 * 1024;
    var ALLOWED_TYPES  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    var ALLOWED_EXTS   = ['jpg', 'jpeg', 'png', 'webp'];
    var pendingFiles   = [];

    document.addEventListener('DOMContentLoaded', function () {
        initUploadArea();
        initRetrainButton();
        initEnvCheck();
        initDeleteButtons();
    });

    /* ═══════════════════════════════════════════════════════════
       UPLOAD AREA
    ═══════════════════════════════════════════════════════════ */
    function initUploadArea() {
        var area  = document.getElementById('trainingUploadArea');
        var input = document.getElementById('trainingImages');
        var grid  = document.getElementById('imagePreviewGrid');
        var info  = document.getElementById('fileCountInfo');
        var sel   = document.querySelector('select[name="categoria"]');
        if (!area || !input) return;

        area.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.style.borderColor = '#198754';
            this.style.background  = 'rgba(25,135,84,0.05)';
        });
        area.addEventListener('dragleave', function () {
            this.style.borderColor = '';
            this.style.background  = '';
        });
        area.addEventListener('drop', function (e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.background  = '';
            if (e.dataTransfer && e.dataTransfer.files.length > 0)
                processFiles(e.dataTransfer.files, grid, info);
        });
        input.addEventListener('change', function () {
            processFiles(this.files, grid, info);
        });
        if (sel) {
            sel.addEventListener('change', function () {
                pendingFiles = [];
                if (grid) grid.innerHTML = '';
                if (info) info.textContent = '0 archivos seleccionados';
            });
        }

        var form = document.getElementById('uploadForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitUploadAjax(grid, info);
            });
        }
    }

    function submitUploadAjax(grid, info) {
        var btn = document.getElementById('uploadBtn');
        var sel = document.getElementById('categoriaSelect');

        if (!sel || !sel.value) {
            Swal.fire({ icon: 'warning', title: 'Categoría requerida', text: 'Selecciona una categoría antes de subir.', confirmButtonText: 'Entendido' });
            return;
        }
        if (pendingFiles.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Sin imágenes', text: 'Selecciona al menos una imagen para subir.', confirmButtonText: 'Entendido' });
            return;
        }

        var fd = new FormData();
        fd.append('action', 'upload_images');
        fd.append('categoria', sel.value);
        pendingFiles.forEach(function (f) { fd.append('images[]', f); });

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Subiendo...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Subir Imágenes';
            if (xhr.status !== 200) { showAlert('Error de conexión HTTP ' + xhr.status, 'error'); return; }
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    showAlert(r.message, 'success');
                    updateStatsDOM(r.conteos, r.total);
                    updateEpochsGuide(r.recomendacion, r.total);
                    updateRetrainChecklist(r.conteos, r.total);
                    TRAINING_TOTAL = r.total;
                    pendingFiles = [];
                    if (grid) grid.innerHTML = '';
                    if (info) info.textContent = '0 archivos seleccionados';
                    var fi = document.getElementById('trainingImages');
                    if (fi) fi.value = '';
                } else {
                    showAlert(r.message, 'error');
                }
            } catch (e) { showAlert('Respuesta inesperada del servidor.', 'error'); }
        };
        xhr.onerror = function () {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Subir Imágenes';
            showAlert('No se pudo conectar con el servidor.', 'error');
        };
        xhr.send(fd);
    }

    function processFiles(files, grid, info) {
        if (!files || !files.length) return;
        if (info) info.textContent = files.length + ' archivo(s) detectado(s)...';
        var valid = [], errors = [];
        for (var i = 0; i < files.length; i++) {
            var f   = files[i];
            var ext = f.name.split('.').pop().toLowerCase();
            if (ALLOWED_TYPES.indexOf(f.type) === -1 && ALLOWED_EXTS.indexOf(ext) === -1) {
                errors.push(f.name + ': formato no válido (solo JPG, PNG, WebP).');
                continue;
            }
            if (f.size > MAX_FILE_SIZE) { errors.push(f.name + ': supera 5 MB.'); continue; }
            valid.push(f);
        }
        if (errors.length) Swal.fire({ icon: 'warning', title: 'Archivos no válidos', html: errors.join('<br>'), confirmButtonText: 'Entendido' });
        if (!valid.length) return;
        pendingFiles = pendingFiles.concat(valid);
        if (info) info.textContent = pendingFiles.length + ' archivo(s) seleccionado(s)';
        buildPreview(grid);
    }

    function buildPreview(grid) {
        if (!grid) return;
        grid.innerHTML = '';
        var U = window.URL || window.webkitURL;
        pendingFiles.forEach(function (file, idx) {
            var src = '', fallback = false;
            if (U && U.createObjectURL) { try { src = U.createObjectURL(file); } catch (e) { fallback = true; } }
            else { fallback = true; }
            var html = '<div class="col-3 col-md-2 mb-2">' +
                '<div class="position-relative border rounded overflow-hidden" style="height:80px;background:#e9ecef;display:flex;align-items:center;justify-content:center;">' +
                '<img src="' + src + '" alt="" style="max-width:100%;max-height:100%;display:' + (fallback ? 'none' : 'block') + ';" onerror="this.style.display=\'none\'">' +
                (fallback ? '<span class="small text-muted text-center px-1">' + escapeHtml(file.name) + '</span>' : '') +
                '<button type="button" class="btn-preview-remove" style="position:absolute;top:2px;right:2px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:14px;cursor:pointer;z-index:2;padding:0;display:flex;align-items:center;justify-content:center;">&times;</button>' +
                '</div></div>';
            grid.insertAdjacentHTML('beforeend', html);
            grid.lastElementChild.querySelector('.btn-preview-remove').addEventListener('click', function () {
                pendingFiles.splice(idx, 1);
                var inf = document.getElementById('fileCountInfo');
                if (inf) inf.textContent = pendingFiles.length + ' archivo(s) seleccionado(s)';
                buildPreview(grid);
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
       RETRAIN
    ═══════════════════════════════════════════════════════════ */
    function initRetrainButton() {
        var btn = document.getElementById('retrainBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var total = typeof TRAINING_TOTAL !== 'undefined' ? TRAINING_TOTAL : 0;
            var epocas = typeof EPOCAS_RECOMENDADAS !== 'undefined' ? EPOCAS_RECOMENDADAS : 10;
            var desc   = typeof DESC_RECOMENDADA   !== 'undefined' ? DESC_RECOMENDADA   : '';

            if (total < 10) {
                Swal.fire({
                    icon: 'warning', title: 'Imágenes insuficientes',
                    html: 'Necesitas al menos <strong>10 imágenes</strong> en 2 o más categorías.<br>Actualmente tienes <strong>' + total + '</strong>.',
                    confirmButtonText: 'Entendido', confirmButtonColor: '#198754'
                });
                return;
            }
            Swal.fire({
                title: '¿Iniciar reentrenamiento?',
                html: '<div class="text-start">' +
                      '<p class="mb-2">Se entrenará el modelo con <strong>' + total + ' imágenes</strong>.</p>' +
                      '<div class="alert alert-info p-2 small mb-2"><i class="bi bi-lightbulb me-1"></i>' +
                      '<strong>Épocas automáticas: ' + epocas + '</strong> — ' + escapeHtml(desc) + '</div>' +
                      '<p class="text-muted small mb-0"><i class="bi bi-clock me-1"></i>Este proceso puede tomar varios minutos. No cierres la página.</p>' +
                      '</div>',
                icon: 'question', showCancelButton: true,
                confirmButtonColor: '#198754', cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-play-fill me-1"></i>Sí, iniciar',
                cancelButtonText: 'Cancelar', reverseButtons: true
            }).then(function (res) { if (res.isConfirmed) startRetraining(total, epocas); });
        });
    }

    function startRetraining(total, epocas) {
        var progressDiv = document.getElementById('trainingProgress');
        var bar         = document.getElementById('trainingProgressBar');
        var log         = document.getElementById('logOutput');
        var statusTxt   = document.getElementById('trainingStatusText');
        var pctLbl      = document.getElementById('trainingPct');
        var btn         = document.getElementById('retrainBtn');

        progressDiv.classList.remove('d-none');
        bar.style.width = '0%'; bar.textContent = '0%';
        bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
        if (pctLbl)   pctLbl.textContent   = '0%';
        if (statusTxt) statusTxt.textContent = 'Iniciando...';
        log.innerHTML = '<span class="text-muted">[SISTEMA] Iniciando con ' + total + ' imágenes · ' + epocas + ' épocas automáticas...</span>';
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Entrenando...';

        var steps = [
            { pct: 8,  msg: '[IA] Verificando entorno Python y dependencias...', cls: 'text-info' },
            { pct: 18, msg: '[IA] Cargando modelo keras_model.h5...', cls: 'text-info' },
            { pct: 28, msg: '[IA] Escaneando imágenes de entrenamiento...', cls: 'text-info' },
            { pct: 38, msg: '[IA] Congelando capas base (transfer learning 70%)...', cls: 'text-info' },
            { pct: 48, msg: '[IA] Configurando data augmentation...', cls: 'text-cyan' },
            { pct: 58, msg: '[IA] Entrenando épocas iniciales...', cls: 'text-warning' },
            { pct: 70, msg: '[IA] Ajustando pesos del modelo...', cls: 'text-warning' },
            { pct: 80, msg: '[IA] Validando precisión...', cls: 'text-warning' },
            { pct: 88, msg: '[IA] Guardando modelo actualizado...', cls: 'text-success' },
        ];
        var stepIdx = 0, progress = 0;

        var interval = setInterval(function () {
            if (progress < 90) {
                progress = Math.min(90, progress + Math.random() * 7 + 3);
                var p = Math.round(progress);
                bar.style.width = p + '%'; bar.textContent = p + '%';
                if (pctLbl)   pctLbl.textContent   = p + '%';
                if (statusTxt) statusTxt.textContent = 'Entrenando... ' + p + '%';
                while (stepIdx < steps.length && progress >= steps[stepIdx].pct) {
                    var s = steps[stepIdx++];
                    log.innerHTML += '<br><span class="' + s.cls + '">' + s.msg + '</span>';
                    log.scrollTop  = log.scrollHeight;
                }
            }
        }, 2500);

        var fd = new FormData();
        fd.append('action', 'retrain');
        fd.append('epochs', epocas);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname, true);
        xhr.onload = function () {
            clearInterval(interval);
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-play-fill me-2"></i>Iniciar Reentrenamiento';

            if (xhr.status !== 200) {
                setBarError(bar, statusTxt);
                log.innerHTML += '<br><span class="text-danger">[✗] Error HTTP ' + xhr.status + '</span>';
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'HTTP ' + xhr.status });
                return;
            }
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    bar.style.width = '100%'; bar.textContent = '100%';
                    bar.className = 'progress-bar bg-success';
                    if (pctLbl)   pctLbl.textContent   = '100%';
                    if (statusTxt) statusTxt.textContent = '¡Completado!';

                    log.innerHTML += '<br><span class="text-success fw-bold">[✓] ' + escapeHtml(r.message) + '</span>';
                    if (r.data) {
                        log.innerHTML += '<br><span class="text-success">[✓] Épocas: ' + (r.data.epochs_completed || epocas) +
                            ' · Muestras: ' + (r.data.training_samples || '?') + '</span>';
                        if (r.data.categories)
                            log.innerHTML += '<br><span class="text-success">[✓] Categorías: ' + escapeHtml(r.data.categories.join(', ')) + '</span>';
                    }
                    log.scrollTop = log.scrollHeight;

                    // Update epochs guide with new recommendation if returned
                    if (r.recomendacion) updateEpochsGuide(r.recomendacion, TRAINING_TOTAL);

                    // Add row to historial table
                    if (r.id_entrenamiento) appendHistorialRow(r);

                    Swal.fire({
                        icon: 'success', title: '¡Reentrenamiento completado!',
                        html: buildSuccessHtml(r, epocas),
                        confirmButtonText: 'Aceptar', confirmButtonColor: '#198754'
                    }).then(function () {
                        // Reset the entire training UI so admin can start a new cycle
                        resetTrainingUI(progressDiv, bar, log, statusTxt, pctLbl);
                    });
                } else {
                    setBarError(bar, statusTxt);
                    log.innerHTML += '<br><span class="text-danger fw-bold">[✗] ' + escapeHtml(r.message) + '</span>';
                    if (r.raw) {
                        r.raw.split('\n').filter(Boolean).slice(-8).forEach(function (l) {
                            log.innerHTML += '<br><span class="text-warning">' + escapeHtml(l) + '</span>';
                        });
                    }
                    log.scrollTop = log.scrollHeight;
                    Swal.fire({
                        icon: 'error', title: 'Error en el reentrenamiento',
                        html: '<p>' + escapeHtml(r.message) + '</p><small class="text-muted">Revisa el log para más detalles.</small>',
                        confirmButtonColor: '#dc3545'
                    }).then(function () {
                        resetTrainingUI(progressDiv, bar, log, statusTxt, pctLbl);
                    });
                }
            } catch (e) {
                setBarError(bar, statusTxt);
                log.innerHTML += '<br><span class="text-danger">[✗] Respuesta inesperada del servidor.</span>';
                Swal.fire({ icon: 'error', title: 'Error', text: 'Respuesta inesperada del servidor.' })
                    .then(function () { resetTrainingUI(progressDiv, bar, log, statusTxt, pctLbl); });
            }
        };
        xhr.onerror = function () {
            clearInterval(interval);
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-play-fill me-2"></i>Iniciar Reentrenamiento';
            setBarError(bar, statusTxt);
            log.innerHTML += '<br><span class="text-danger">[✗] Error de conexión.</span>';
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar con el servidor.' });
        };
        xhr.send(fd);
    }

    function setBarError(bar, statusTxt) {
        bar.className = 'progress-bar bg-danger';
        if (statusTxt) statusTxt.textContent = 'Error';
    }

    function resetTrainingUI(progressDiv, bar, log, statusTxt, pctLbl) {
        // Hide progress section
        if (progressDiv) progressDiv.classList.add('d-none');
        // Reset bar
        if (bar) {
            bar.style.width = '0%';
            bar.textContent = '0%';
            bar.className   = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
        }
        // Reset labels
        if (pctLbl)   pctLbl.textContent   = '0%';
        if (statusTxt) statusTxt.textContent = 'Iniciando...';
        // Reset log
        if (log) log.innerHTML = '<span class="text-muted">[SISTEMA] Inicializando...</span>';
        // Reset upload form
        pendingFiles = [];
        var grid = document.getElementById('imagePreviewGrid');
        var info = document.getElementById('fileCountInfo');
        var fi   = document.getElementById('trainingImages');
        var sel  = document.getElementById('categoriaSelect');
        if (grid) grid.innerHTML = '';
        if (info) info.textContent = '0 archivos seleccionados';
        if (fi)   fi.value = '';
        if (sel)  sel.value = '';
    }

    function buildSuccessHtml(r, epocas) {
        var d   = r.data || {};
        var acc = d.final_accuracy  != null ? d.final_accuracy  : '?';
        var best= d.best_accuracy   != null ? d.best_accuracy   : acc;
        var val = d.final_val_accuracy != null ? d.final_val_accuracy : null;
        var ep  = d.epochs_completed   != null ? d.epochs_completed   : epocas;
        // epochs_completed in Python is the REAL number used (after internal multiplication was removed)
        var trainSamples = d.training_samples != null ? d.training_samples : null;

        var accHtml = acc + '%';
        if (best !== acc && best !== '?') accHtml += ' <small class="text-muted d-block">(mejor: ' + best + '%)</small>';
        var valHtml = val !== null
            ? '<div class="fw-bold text-primary fs-5">' + val + '%</div><small class="text-muted">Validación</small>'
            : '<div class="fw-bold text-secondary fs-5">—</div><small class="text-muted">Sin validación</small>';

        var html = '<p class="mb-2">' + escapeHtml(r.message) + '</p>' +
            '<div class="row text-center g-2 mt-1">' +
            '<div class="col-4"><div class="border rounded p-2"><div class="fw-bold text-success fs-5">' + accHtml + '</div><small class="text-muted">Precisión</small></div></div>' +
            '<div class="col-4"><div class="border rounded p-2">' + valHtml + '</div></div>' +
            '<div class="col-4"><div class="border rounded p-2"><div class="fw-bold text-warning fs-5">' + ep + '</div><small class="text-muted">Épocas usadas</small></div></div>' +
            '</div>';
        if (trainSamples !== null && trainSamples < 20) {
            html += '<div class="alert alert-info mt-3 mb-0 text-start small p-2">' +
                '<i class="bi bi-info-circle me-1"></i>Con ' + trainSamples +
                ' imágenes la precisión puede ser baja. Sube al menos 20 por categoría para mejores resultados.</div>';
        }
        return html;
    }

    function appendHistorialRow(r) {
        var tbody = document.getElementById('historialBody');
        if (!tbody) return;
        var d    = r.data || {};
        var acc  = d.final_accuracy  != null ? d.final_accuracy  + '%' : '—';
        var best = d.best_accuracy   != null ? d.best_accuracy   + '%' : '—';
        var ep   = d.epochs_completed != null ? d.epochs_completed : (r.epocas_usadas || '?');
        var imgs = TRAINING_TOTAL;
        var now  = new Date();
        var fecha = ('0'+now.getDate()).slice(-2)+'/'+('0'+(now.getMonth()+1)).slice(-2)+'/'+now.getFullYear()+
                    ' '+('0'+now.getHours()).slice(-2)+':'+('0'+now.getMinutes()).slice(-2);
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="ps-3 text-muted small">#' + r.id_entrenamiento + '</td>' +
            '<td class="small">' + fecha + '</td>' +
            '<td><span class="badge bg-success-subtle text-success border border-success">' +
              '<i class="bi bi-check-circle-fill me-1"></i>Completado</span></td>' +
            '<td class="small">' + ep + '</td>' +
            '<td class="fw-bold text-success">' + acc + '</td>' +
            '<td class="small text-primary fw-semibold">' + best + '</td>' +
            '<td class="small">' + imgs + '</td>' +
            '<td class="small text-muted">—</td>';
        tbody.insertBefore(tr, tbody.firstChild);

        // Update the "Entrenamientos" stat card count
        var statCard = document.querySelector('.stat-card .text-info');
        if (statCard) {
            var cur = parseInt(statCard.textContent) || 0;
            statCard.textContent = cur + 1;
        }
    }

    /* ═══════════════════════════════════════════════════════════
       EPOCHS GUIDE — live update when images change
    ═══════════════════════════════════════════════════════════ */
    function updateEpochsGuide(rec, total) {
        if (!rec) return;
        // Stat card
        var disp = document.getElementById('epochsDisplay');
        if (disp) disp.textContent = rec.epochs;

        // Guide description
        var desc = document.getElementById('epochsGuideDesc');
        if (desc) desc.textContent = rec.descripcion;

        // Auto label under button
        var lbl = document.getElementById('epochsAutoLabel');
        if (lbl) lbl.innerHTML = '<strong>' + rec.epochs + ' épocas</strong> para ' + total + ' imágenes.';

        // Update EPOCAS_RECOMENDADAS global
        if (typeof EPOCAS_RECOMENDADAS !== 'undefined') EPOCAS_RECOMENDADAS = rec.epochs;
        if (typeof DESC_RECOMENDADA    !== 'undefined') DESC_RECOMENDADA    = rec.descripcion;
        if (typeof NIVEL_RECOMENDADO   !== 'undefined') NIVEL_RECOMENDADO   = rec.nivel;

        // Highlight the active card in the guide
        var niveles = ['Básico','Bajo','Medio','Alto','Experto'];
        var activeLabel = rec.nivel.charAt(0).toUpperCase() + rec.nivel.slice(1);
        var cards = document.querySelectorAll('#epochsGuideCards .col > div');
        cards.forEach(function (card) {
            var labelEl = card.querySelector('.small.fw-semibold');
            if (!labelEl) return;
            var isActive = labelEl.textContent.trim() === activeLabel;
            card.style.opacity = isActive ? '1' : '0.45';
            card.style.fontWeight = isActive ? 'bold' : '';
        });
    }

    /* ═══════════════════════════════════════════════════════════
       RETRAIN CHECKLIST — live update
    ═══════════════════════════════════════════════════════════ */
    function updateRetrainChecklist(conteos, total) {
        var cats = 0;
        for (var f in conteos) { if (conteos[f] > 0) cats++; }
        var ok = cats >= 2 && total >= 10;

        var btn = document.getElementById('retrainBtn');
        if (btn) btn.disabled = !ok;

        var panel = document.getElementById('retrainChecklist');
        if (panel) panel.className = 'mb-4 p-3 rounded-3 border ' + (ok ? 'border-success bg-success-subtle' : 'border-warning bg-warning-subtle');

        var title = document.getElementById('retrainChecklistTitle');
        if (title) {
            title.className = 'fw-semibold mb-2 ' + (ok ? 'text-success' : 'text-warning');
            title.innerHTML = '<i class="bi ' + (ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') + ' me-2"></i>' + (ok ? 'Listo para reentrenar' : 'Requisitos pendientes');
        }

        var ic = document.getElementById('checkCats');
        if (ic) ic.className = 'bi ' + (cats >= 2 ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger');
        var cl = document.getElementById('checkCatsLabel');
        if (cl) cl.innerHTML = 'Al menos 2 categorías con imágenes <strong>(' + cats + '/' + Object.keys(conteos).length + ')</strong>';

        var it = document.getElementById('checkTotal');
        if (it) it.className = 'bi ' + (total >= 10 ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger');
        var tl = document.getElementById('checkTotalLabel');
        if (tl) tl.innerHTML = 'Mínimo 10 imágenes en total <strong>(' + total + ' actuales)</strong>';

        for (var cat in conteos) {
            var el = document.getElementById('checkCat_' + cat);
            if (el) el.className = 'bi ' + (conteos[cat] > 0 ? 'bi-check-circle text-success' : 'bi-dash-circle text-muted');
            var ll = document.getElementById('checkCatLabel_' + cat);
            if (ll) { var s = ll.querySelector('strong'); if (s) s.textContent = conteos[cat] + ' img'; }
        }

        var hint = document.getElementById('retrainHint');
        if (hint) hint.style.display = ok ? 'none' : 'block';
    }

    /* ═══════════════════════════════════════════════════════════
       STATS DOM — update counters, bars, select
    ═══════════════════════════════════════════════════════════ */
    function updateStatsDOM(conteos, total) {
        var st = document.getElementById('statTotalImgs');
        if (st) st.textContent = total;
        var pt = document.getElementById('panelTotal');
        if (pt) pt.textContent = total;

        var maxVal = 1;
        for (var f in conteos) { if (conteos[f] > maxVal) maxVal = conteos[f]; }

        for (var cat in conteos) {
            var ce = document.getElementById('catCount_' + cat);
            var be = document.getElementById('catBar_'   + cat);
            var de = document.querySelector('.btn-delete-cat[data-folder="' + cat + '"]');
            if (ce) ce.textContent  = conteos[cat] + ' img';
            if (be) be.style.width  = Math.round(conteos[cat] / maxVal * 100) + '%';
            if (de) de.style.display = conteos[cat] > 0 ? '' : 'none';
        }

        var sel = document.getElementById('categoriaSelect');
        if (sel) {
            sel.querySelectorAll('option[value]').forEach(function (o) {
                if (o.value && conteos[o.value] !== undefined) {
                    var n = o.getAttribute('data-nombre') || o.value;
                    o.textContent = n + ' (' + conteos[o.value] + ' imágenes)';
                }
            });
        }
    }

    /* ═══════════════════════════════════════════════════════════
       DELETE CATEGORY IMAGES
    ═══════════════════════════════════════════════════════════ */
    function initDeleteButtons() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-delete-cat');
            if (!btn) return;
            var folder = btn.dataset.folder;
            var nombre = btn.dataset.nombre;
            Swal.fire({
                title: '¿Eliminar imágenes?',
                html: 'Se eliminarán <strong>todas las imágenes</strong> de <strong>' + escapeHtml(nombre) + '</strong>.<br><small class="text-danger">Esta acción no se puede deshacer.</small>',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Sí, eliminar',
                cancelButtonText: 'Cancelar', reverseButtons: true
            }).then(function (res) {
                if (!res.isConfirmed) return;
                var fd = new FormData();
                fd.append('action', 'delete_category_images');
                fd.append('categoria', folder);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.pathname, true);
                xhr.onload = function () {
                    try {
                        var r = JSON.parse(xhr.responseText);
                        if (r.success) {
                            showAlert(r.message, 'success');
                            updateStatsDOM(r.conteos, r.total);
                            updateEpochsGuide(r.recomendacion, r.total);
                            updateRetrainChecklist(r.conteos, r.total);
                            TRAINING_TOTAL = r.total;
                            btn.style.display = 'none';
                        } else { showAlert(r.message || 'Error al eliminar.', 'error'); }
                    } catch (e) { showAlert('Error inesperado.', 'error'); }
                };
                xhr.onerror = function () { showAlert('Error de conexión.', 'error'); };
                xhr.send(fd);
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
       ENV CHECK
    ═══════════════════════════════════════════════════════════ */
    function initEnvCheck() {
        var btn = document.getElementById('btnCheckEnv');
        if (!btn) return;
        btn.addEventListener('click', function () {
            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verificando...';
            var fd = new FormData(); fd.append('action', 'check_env');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.pathname, true);
            xhr.onload = function () {
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-search me-1"></i>Verificar entorno';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.success) renderEnvResults(r.checks);
                } catch (e) {}
            };
            xhr.onerror = function () { btn.disabled = false; btn.innerHTML = '<i class="bi bi-search me-1"></i>Verificar entorno'; };
            xhr.send(fd);
        });
    }

    function renderEnvResults(checks) {
        var el = document.getElementById('envResults');
        if (!el) return;
        var labels = { python:'Python', model:'Modelo keras_model.h5', training_data:'Directorio training_data', tf_keras:'tf-keras', php_timeout:'PHP timeout', upload_max:'upload_max_filesize' };
        var html = '<div class="row g-2 mt-1">';
        for (var k in checks) {
            var c = checks[k];
            var icon = c.ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger';
            html += '<div class="col-sm-6"><div class="d-flex align-items-start gap-2">' +
                '<i class="bi ' + icon + ' mt-1 flex-shrink-0"></i>' +
                '<div><div class="small fw-semibold">' + (labels[k] || k) + '</div>' +
                '<div class="small text-muted">' + escapeHtml(String(c.value)) + '</div></div></div></div>';
        }
        html += '</div>';
        el.innerHTML = html;
        el.style.display = 'block';
    }

    /* ═══════════════════════════════════════════════════════════
       HELPERS
    ═══════════════════════════════════════════════════════════ */
    function showAlert(message, type) {
        var ex = document.getElementById('uploadAlertMsg');
        if (ex) ex.remove();
        var cls  = type === 'success' ? 'alert-success' : 'alert-danger';
        var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        var div  = document.createElement('div');
        div.id        = 'uploadAlertMsg';
        div.className = 'alert ' + cls + ' alert-dismissible fade show';
        div.innerHTML = '<i class="bi ' + icon + ' me-2"></i>' + message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        var c = document.querySelector('.container-fluid.p-4');
        if (c) { c.insertBefore(div, c.firstChild); setTimeout(function () { if (div.parentNode) div.remove(); }, 4500); }
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

})();
