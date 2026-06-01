(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initUserManagement();
        initTableSearch();
        initBatchSelect();
        initDateRangeValidation();
        initExportCSV();
        initDashboardCharts();
        initTrainingPanel();
        initImagePreview();
    });

    function initUserManagement() {
        var toggleForms = document.querySelectorAll('form[data-toggle="user-status"]');
        toggleForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                var isActive = btn.dataset.estado === '1';
                Swal.fire({
                    title: isActive ? '¿Desactivar usuario?' : '¿Activar usuario?',
                    text: isActive ? 'El usuario no podrá iniciar sesión.' : 'El usuario podrá iniciar sesión nuevamente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isActive ? '#dc3545' : '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: isActive ? 'Sí, desactivar' : 'Sí, activar',
                    cancelButtonText: 'Cancelar'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        var deleteForms = document.querySelectorAll('form[data-action="delete-user"]');
        deleteForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Eliminar usuario?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    }

    window.editarUsuario = function (id, nombres, apellidos, correo, rol) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombres').value = nombres;
        document.getElementById('edit_apellidos').value = apellidos;
        document.getElementById('edit_correo').value = correo;
        document.getElementById('edit_rol').value = rol;
        var modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
        modal.show();
    };

    function initTableSearch() {
        var searchInput = document.getElementById('tableSearch');
        if (!searchInput) return;

        searchInput.addEventListener('keyup', function () {
            var q = this.value.toLowerCase();
            var table = document.querySelector('.table-responsive table');
            if (!table) return;
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(q) > -1 ? '' : 'none';
            });
        });
    }

    function initBatchSelect() {
        var selectAll = document.getElementById('selectAll');
        if (!selectAll) return;

        selectAll.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('.batch-item');
            checkboxes.forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('batch-item')) {
                var all = document.querySelectorAll('.batch-item');
                var checked = document.querySelectorAll('.batch-item:checked');
                if (selectAll) {
                    selectAll.checked = all.length === checked.length;
                    selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
                }
            }
        });
    }

    function initDateRangeValidation() {
        var dateStart = document.querySelector('input[name="fecha_inicio"]');
        var dateEnd = document.querySelector('input[name="fecha_fin"]');
        if (!dateStart || !dateEnd) return;

        function validateRange() {
            if (dateStart.value && dateEnd.value && dateEnd.value < dateStart.value) {
                dateEnd.setCustomValidity('La fecha fin no puede ser anterior a la fecha inicio.');
                dateEnd.classList.add('is-invalid');
            } else {
                dateEnd.setCustomValidity('');
                dateEnd.classList.remove('is-invalid');
            }
        }

        dateStart.addEventListener('change', validateRange);
        dateEnd.addEventListener('change', validateRange);
    }

    function initExportCSV() {
        var exportBtn = document.getElementById('exportCSV');
        if (!exportBtn) return;

        exportBtn.addEventListener('click', function () {
            var table = document.querySelector('.table-responsive table');
            if (!table) return;

            var rows = [];
            var headers = [];
            table.querySelectorAll('thead th').forEach(function (th) {
                var txt = th.textContent.trim();
                if (txt.toLowerCase() !== 'acciones') {
                    headers.push(txt);
                }
            });
            rows.push(headers.join(','));

            table.querySelectorAll('tbody tr').forEach(function (tr) {
                if (tr.style.display === 'none') return;
                var data = [];
                tr.querySelectorAll('td').forEach(function (td, idx) {
                    var th = table.querySelectorAll('thead th')[idx];
                    if (th && th.textContent.trim().toLowerCase() !== 'acciones') {
                        var val = td.textContent.trim().replace(/,/g, '');
                        data.push(val);
                    }
                });
                if (data.length > 0) rows.push(data.join(','));
            });

            var csv = rows.join('\n');
            var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'reporte_ecovision_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });
    }

    function initDashboardCharts() {
        var chartDataEl = document.getElementById('chartData');
        if (!chartDataEl) return;

        try {
            var data = JSON.parse(chartDataEl.textContent);
            if (typeof Chart !== 'undefined') {
                var ctx = document.getElementById('mainChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: data.type || 'bar',
                        data: {
                            labels: data.labels || [],
                            datasets: [{
                                label: data.label || 'Datos',
                                data: data.values || [],
                                backgroundColor: data.colors || ['#198754']
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: !!data.label }
                            }
                        }
                    });
                }
            }
            window.__chartData = data;
        } catch (e) {
            console.warn('No se pudieron cargar los datos del gráfico.');
        }
    }

    function initTrainingPanel() {
        var dropZones = document.querySelectorAll('.training-dropzone');
        if (dropZones.length === 0) return;

        dropZones.forEach(function (zone) {
            var input = zone.querySelector('input[type="file"]');
            var preview = zone.querySelector('.preview-grid');

            zone.addEventListener('click', function () {
                if (input) input.click();
            });

            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                zone.classList.add('dragover');
            });

            zone.addEventListener('dragleave', function () {
                zone.classList.remove('dragover');
            });

            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                zone.classList.remove('dragover');
                if (input && e.dataTransfer.files.length > 0) {
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                }
            });

            if (input) {
                input.addEventListener('change', function () {
                    showTrainingPreview(this, preview);
                    updateTrainingProgress();
                });
            }
        });
    }

    function showTrainingPreview(input, preview) {
        if (!preview) return;
        preview.innerHTML = '';
        Array.from(input.files).forEach(function (file) {
            if (!file.type.startsWith('image/')) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                var col = document.createElement('div');
                col.className = 'col-4 col-md-3 mb-2';
                col.innerHTML = '<img src="' + e.target.result + '" class="img-fluid rounded border" style="height:80px;object-fit:cover;">';
                preview.appendChild(col);
            };
            reader.readAsDataURL(file);
        });
    }

    function updateTrainingProgress() {
        var total = 0;
        var loaded = 0;
        document.querySelectorAll('.training-dropzone input[type="file"]').forEach(function (input) {
            total += input.files.length;
        });
        document.querySelectorAll('.training-dropzone').forEach(function (zone) {
            var input = zone.querySelector('input[type="file"]');
            var bar = zone.querySelector('.progress-bar');
            var count = zone.querySelector('.file-count');
            if (bar) {
                var pct = total > 0 ? Math.round((input.files.length / total) * 100) : 0;
                bar.style.width = pct + '%';
                bar.textContent = pct > 0 ? pct + '%' : '';
            }
            if (count) count.textContent = input.files.length + ' archivos';
        });
    }

    function initImagePreview() {
        document.addEventListener('click', function (e) {
            var img = e.target.closest('.img-preview-trigger');
            if (img) {
                Swal.fire({
                    imageUrl: img.src,
                    imageAlt: 'Vista previa',
                    width: 600,
                    showConfirmButton: false,
                    showCloseButton: true
                });
            }
        });
    }

})();
