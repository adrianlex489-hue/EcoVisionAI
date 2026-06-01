<?php
ob_start();
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAdmin();

// ── DELETE handler ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_entrenamiento') {
    ob_clean();
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID no válido.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM entrenamientos WHERE id_entrenamiento = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => "Registro #$id eliminado correctamente."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el registro.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ── DELETE ALL handler ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_all_entrenamientos') {
    ob_clean();
    header('Content-Type: application/json');
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM entrenamientos")->fetchColumn();
        $pdo->exec("DELETE FROM entrenamientos");
        echo json_encode(['success' => true, 'message' => "Se eliminaron $count registro(s) del historial."]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Paginación
$por_pagina = 15;
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$offset     = ($pagina - 1) * $por_pagina;
$total_rows = $pdo->query("SELECT COUNT(*) FROM entrenamientos")->fetchColumn();
$total_pags = max(1, ceil($total_rows / $por_pagina));

$registros = $pdo->prepare("SELECT e.*, u.nombres, u.apellidos
    FROM entrenamientos e
    LEFT JOIN usuarios u ON e.id_admin = u.id_usuario
    ORDER BY e.fecha_inicio DESC
    LIMIT $por_pagina OFFSET $offset");
$registros->execute();
$registros = $registros->fetchAll();

$titulo    = 'Historial de Entrenamientos';
$css_extra = 'dashboard.css';
$css_admin = true;
$js_admin  = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<div class="main-content">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
  <div class="container-fluid">
    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de Entrenamientos</h5>
    <a href="entrenamiento.php" class="btn btn-sm btn-outline-success">
      <i class="bi bi-arrow-left me-1"></i>Volver a Entrenar
    </a>
    <button class="btn btn-sm btn-outline-danger ms-2" id="btnLimpiarHistorial">
      <i class="bi bi-trash3 me-1"></i>Limpiar historial
    </button>
  </div>
</nav>

<div class="container-fluid p-4">

<!-- Resumen stats -->
<?php
$stats = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(estado='completado') as completados,
    SUM(estado='error') as errores,
    ROUND(AVG(CASE WHEN estado='completado' THEN precision_final END),2) as avg_precision,
    ROUND(MAX(precision_mejor),2) as max_precision
    FROM entrenamientos")->fetch();
?>
<div class="row mb-4">
  <?php
  $cards = [
    ['label'=>'Total entrenamientos', 'val'=>$stats['total'],        'color'=>'primary',  'icon'=>'bi-cpu'],
    ['label'=>'Completados',          'val'=>$stats['completados'],   'color'=>'success',  'icon'=>'bi-check-circle'],
    ['label'=>'Con errores',          'val'=>$stats['errores'],       'color'=>'danger',   'icon'=>'bi-x-circle'],
    ['label'=>'Precisión promedio',   'val'=>($stats['avg_precision'] ?? '—').'%', 'color'=>'info', 'icon'=>'bi-graph-up'],
    ['label'=>'Mejor precisión',      'val'=>($stats['max_precision'] ?? '—').'%', 'color'=>'warning','icon'=>'bi-trophy'],
  ];
  foreach ($cards as $c): ?>
  <div class="col mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body"><div class="d-flex justify-content-between align-items-center">
        <div><p class="text-muted small mb-1"><?php echo $c['label']; ?></p>
          <h4 class="mb-0 text-<?php echo $c['color']; ?>"><?php echo $c['val']; ?></h4></div>
        <div class="rounded-3 p-2 bg-<?php echo $c['color']; ?>-subtle">
          <i class="bi <?php echo $c['icon']; ?> fs-3 text-<?php echo $c['color']; ?>"></i>
        </div>
      </div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabla -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($registros)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-2"></i>
      <p>No hay entrenamientos registrados aún.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th class="ps-3">#</th>
            <th>Fecha inicio</th>
            <th>Estado</th>
            <th>Épocas</th>
            <th>Precisión final</th>
            <th>Mejor precisión</th>
            <th>Validación</th>
            <th>Imágenes</th>
            <th>Duración</th>
            <th>Admin</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $h):
          $badge = match($h['estado']) { 'completado'=>'success','error'=>'danger',default=>'warning' };
          $icono = match($h['estado']) { 'completado'=>'bi-check-circle-fill','error'=>'bi-x-circle-fill',default=>'bi-hourglass-split' };
          $secs  = ($h['fecha_fin'] && $h['fecha_inicio']) ? strtotime($h['fecha_fin']) - strtotime($h['fecha_inicio']) : null;
          $dur   = $secs !== null ? ($secs >= 60 ? floor($secs/60).'m '.($secs%60).'s' : $secs.'s') : '—';
          $cats  = $h['categorias'] ? implode(', ', json_decode($h['categorias'], true) ?? []) : '—';
        ?>
        <tr>
          <td class="ps-3 text-muted small">#<?php echo $h['id_entrenamiento']; ?></td>
          <td class="small"><?php echo date('d/m/Y H:i', strtotime($h['fecha_inicio'])); ?></td>
          <td><span class="badge bg-<?php echo $badge; ?>-subtle text-<?php echo $badge; ?> border border-<?php echo $badge; ?>">
            <i class="bi <?php echo $icono; ?> me-1"></i><?php echo ucfirst($h['estado']); ?>
          </span></td>
          <td class="small"><?php echo $h['epocas_completadas'] ?? $h['epocas_solicitadas']; ?></td>
          <td class="fw-bold <?php echo $h['estado']==='completado'?'text-success':'text-muted'; ?>">
            <?php echo $h['precision_final'] !== null ? $h['precision_final'].'%' : '—'; ?>
          </td>
          <td class="small text-primary fw-semibold">
            <?php echo $h['precision_mejor'] !== null ? $h['precision_mejor'].'%' : '—'; ?>
          </td>
          <td class="small"><?php echo $h['precision_val'] !== null ? $h['precision_val'].'%' : '—'; ?></td>
          <td class="small"><?php echo $h['total_imagenes']; ?></td>
          <td class="small text-muted"><?php echo $dur; ?></td>
          <td class="small"><?php echo $h['nombres'] ? htmlspecialchars($h['nombres'].' '.$h['apellidos']) : '—'; ?></td>
          <td>
            <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary btn-ver-detalle"
                    data-id="<?php echo $h['id_entrenamiento']; ?>"
                    data-acc="<?php echo htmlspecialchars($h['historial_acc'] ?? '[]'); ?>"
                    data-loss="<?php echo htmlspecialchars($h['historial_loss'] ?? '[]'); ?>"
                    data-cats="<?php echo htmlspecialchars($cats); ?>"
                    data-error="<?php echo htmlspecialchars($h['mensaje_error'] ?? ''); ?>"
                    data-estado="<?php echo $h['estado']; ?>"
                    data-precision="<?php echo $h['precision_final'] ?? ''; ?>"
                    data-mejor="<?php echo $h['precision_mejor'] ?? ''; ?>"
                    data-epocas="<?php echo $h['epocas_completadas'] ?? $h['epocas_solicitadas']; ?>"
                    data-imagenes="<?php echo $h['total_imagenes']; ?>"
                    data-duracion="<?php echo $dur; ?>"
                    title="Ver resultado">
              <i class="bi bi-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger btn-eliminar-fila"
                    data-id="<?php echo $h['id_entrenamiento']; ?>"
                    title="Eliminar registro">
              <i class="bi bi-trash3"></i>
            </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_pags > 1): ?>
    <div class="d-flex justify-content-center py-3">
      <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $total_pags; $p++): ?>
        <li class="page-item <?php echo $p === $pagina ? 'active' : ''; ?>">
          <a class="page-link" href="?pagina=<?php echo $p; ?>"><?php echo $p; ?></a>
        </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

</div><!-- /.container-fluid -->
</div><!-- /.main-content -->

<!-- Modal detalle — amigable para usuarios no técnicos -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modalDetalleTitulo">Resultado del Entrenamiento</h5>
          <p class="text-muted small mb-0" id="modalDetalleSub"></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3" id="modalDetalleBody"></div>
      <div class="modal-footer border-top-0">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.btn-ver-detalle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id   = this.dataset.id;
        var acc  = JSON.parse(this.dataset.acc  || '[]');
        var loss = JSON.parse(this.dataset.loss || '[]');
        var cats = (this.dataset.cats || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
        var err  = this.dataset.error || '';

        // ── Subtitle
        document.getElementById('modalDetalleSub').textContent = 'Entrenamiento #' + id;

        var html = '';

        // ── ERROR STATE ──────────────────────────────────────────────────
        if (err) {
            html += '<div class="alert alert-danger d-flex gap-3 align-items-start">' +
                '<i class="bi bi-x-octagon-fill fs-3 flex-shrink-0"></i>' +
                '<div><strong class="d-block mb-1">El entrenamiento no pudo completarse</strong>' +
                '<span class="small">' + escHtml(err) + '</span></div></div>';
            document.getElementById('modalDetalleBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
            return;
        }

        // ── SCORE CARD ───────────────────────────────────────────────────
        var finalAcc = acc.length ? acc[acc.length - 1] : null;
        var bestAcc  = acc.length ? Math.max.apply(null, acc) : null;

        var scoreColor, scoreIcon, scoreLabel, scoreMsg;
        if (bestAcc === null) {
            scoreColor = 'secondary'; scoreIcon = 'bi-question-circle';
            scoreLabel = 'Sin datos'; scoreMsg = 'No hay información de precisión disponible.';
        } else if (bestAcc >= 85) {
            scoreColor = 'success'; scoreIcon = 'bi-patch-check-fill';
            scoreLabel = '¡Excelente!'; scoreMsg = 'El modelo aprendió muy bien. Puede clasificar residuos con alta confianza.';
        } else if (bestAcc >= 65) {
            scoreColor = 'primary'; scoreIcon = 'bi-hand-thumbs-up-fill';
            scoreLabel = 'Bueno'; scoreMsg = 'El modelo aprendió correctamente. Agregar más imágenes mejoraría los resultados.';
        } else if (bestAcc >= 40) {
            scoreColor = 'warning'; scoreIcon = 'bi-exclamation-triangle-fill';
            scoreLabel = 'Regular'; scoreMsg = 'El modelo aprendió algo, pero necesita más imágenes por categoría para ser confiable.';
        } else {
            scoreColor = 'danger'; scoreIcon = 'bi-emoji-frown-fill';
            scoreLabel = 'Necesita mejorar'; scoreMsg = 'El modelo tuvo dificultades. Se recomienda subir al menos 20 imágenes por categoría.';
        }

        html += '<div class="p-4 rounded-3 bg-' + scoreColor + '-subtle border border-' + scoreColor + ' mb-4 text-center">' +
            '<i class="bi ' + scoreIcon + ' text-' + scoreColor + '" style="font-size:3rem"></i>' +
            '<h4 class="fw-bold text-' + scoreColor + ' mt-2 mb-1">' + scoreLabel + '</h4>' +
            '<p class="mb-0 text-dark">' + scoreMsg + '</p>' +
            '</div>';

        // ── KEY METRICS (plain language) ─────────────────────────────────
        if (bestAcc !== null) {
            html += '<div class="row g-3 mb-4">';

            // Accuracy meter
            var meterColor = bestAcc >= 85 ? '#198754' : bestAcc >= 65 ? '#0d6efd' : bestAcc >= 40 ? '#ffc107' : '#dc3545';
            html += '<div class="col-md-6">' +
                '<div class="card border-0 bg-light h-100">' +
                '<div class="card-body text-center">' +
                '<p class="text-muted small mb-2 fw-semibold">¿Qué tan bien aprendió el modelo?</p>' +
                '<div style="position:relative;width:120px;height:120px;margin:0 auto 8px">' +
                  '<svg viewBox="0 0 36 36" style="width:120px;height:120px;transform:rotate(-90deg)">' +
                    '<circle cx="18" cy="18" r="15.9" fill="none" stroke="#e9ecef" stroke-width="3"/>' +
                    '<circle cx="18" cy="18" r="15.9" fill="none" stroke="' + meterColor + '" stroke-width="3" ' +
                      'stroke-dasharray="' + bestAcc + ' ' + (100 - bestAcc) + '" stroke-linecap="round"/>' +
                  '</svg>' +
                  '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center">' +
                    '<div class="fw-bold" style="font-size:1.4rem;color:' + meterColor + '">' + bestAcc + '%</div>' +
                  '</div>' +
                '</div>' +
                '<p class="mb-0 small text-muted">Precisión máxima alcanzada</p>' +
                '</div></div></div>';

            // Trend
            var trend = '';
            if (acc.length >= 3) {
                var first3avg = (acc[0] + acc[1] + acc[2]) / 3;
                var last3avg  = (acc[acc.length-3] + acc[acc.length-2] + acc[acc.length-1]) / 3;
                if (last3avg > first3avg + 5)       trend = '<span class="text-success"><i class="bi bi-arrow-up-circle-fill me-1"></i>Mejoró durante el entrenamiento</span>';
                else if (last3avg < first3avg - 5)  trend = '<span class="text-warning"><i class="bi bi-arrow-down-circle me-1"></i>Bajó al final (puede necesitar más datos)</span>';
                else                                trend = '<span class="text-primary"><i class="bi bi-dash-circle me-1"></i>Se mantuvo estable</span>';
            }

            html += '<div class="col-md-6">' +
                '<div class="card border-0 bg-light h-100">' +
                '<div class="card-body">' +
                '<p class="text-muted small mb-3 fw-semibold">Resumen del entrenamiento</p>' +
                '<ul class="list-unstyled mb-0 small">' +
                '<li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Precisión final: <strong>' + (finalAcc !== null ? finalAcc + '%' : '—') + '</strong></li>' +
                '<li class="mb-2"><i class="bi bi-trophy text-warning me-2"></i>Mejor precisión: <strong>' + bestAcc + '%</strong></li>' +
                '<li class="mb-2"><i class="bi bi-arrow-repeat text-primary me-2"></i>Rondas de aprendizaje: <strong>' + acc.length + '</strong></li>' +
                (trend ? '<li class="mb-0"><i class="bi bi-graph-up me-2 text-muted"></i>' + trend + '</li>' : '') +
                '</ul>' +
                '</div></div></div>';

            html += '</div>'; // row
        }

        // ── CATEGORIES ───────────────────────────────────────────────────
        if (cats.length) {
            var catIcons = { 'Plastico':'🧴', 'Papel_y_carton':'📦', 'Vidrio':'🍶', 'Metal':'🥫', 'Organico':'🍃' };
            html += '<div class="mb-4">' +
                '<p class="fw-semibold mb-2 small">Tipos de residuos entrenados</p>' +
                '<div class="d-flex flex-wrap gap-2">';
            cats.forEach(function(c) {
                var icon = catIcons[c] || '♻️';
                var label = c.replace('_y_', ' y ').replace('_', ' ');
                html += '<span class="badge rounded-pill bg-success-subtle text-success border border-success px-3 py-2" style="font-size:0.85rem">' +
                    icon + ' ' + label + '</span>';
            });
            html += '</div></div>';
        }

        // ── PROGRESS CHART (simplified) ──────────────────────────────────
        if (acc.length > 1) {
            // Sample at most 12 points evenly for the chart
            var sample = [];
            var step = Math.max(1, Math.floor(acc.length / 12));
            for (var i = 0; i < acc.length; i += step) sample.push({ ep: i+1, v: acc[i] });
            if (sample[sample.length-1].ep !== acc.length) sample.push({ ep: acc.length, v: acc[acc.length-1] });

            html += '<div class="mb-3">' +
                '<p class="fw-semibold mb-1 small">¿Cómo fue aprendiendo el modelo?</p>' +
                '<p class="text-muted mb-2" style="font-size:11px">Cada barra muestra qué tan bien clasificaba el modelo en esa ronda. Más alto = mejor.</p>' +
                '<div class="d-flex align-items-end gap-1" style="height:80px;background:#f8f9fa;border-radius:8px;padding:8px 10px">';
            sample.forEach(function(pt) {
                var h  = Math.max(4, Math.round((pt.v / 100) * 64));
                var bg = pt.v >= 85 ? '#198754' : pt.v >= 65 ? '#0d6efd' : pt.v >= 40 ? '#ffc107' : '#dc3545';
                html += '<div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:2px">' +
                    '<div style="width:100%;height:' + h + 'px;background:' + bg + ';border-radius:3px 3px 0 0;min-width:6px" title="Ronda ' + pt.ep + ': ' + pt.v + '%"></div>' +
                    '<span style="font-size:9px;color:#6c757d">' + pt.ep + '</span>' +
                    '</div>';
            });
            html += '</div>' +
                '<div class="d-flex justify-content-between mt-1" style="font-size:10px;color:#6c757d;padding:0 10px">' +
                '<span>Inicio</span><span>Fin del entrenamiento</span></div>' +
                '</div>';
        }

        // ── RECOMMENDATION ───────────────────────────────────────────────
        var recHtml = '';
        if (bestAcc !== null && bestAcc < 85) {
            recHtml = '<div class="alert alert-info d-flex gap-2 align-items-start mb-0 small">' +
                '<i class="bi bi-lightbulb-fill text-info fs-5 flex-shrink-0 mt-1"></i>' +
                '<div><strong class="d-block mb-1">¿Cómo mejorar los resultados?</strong>' +
                '<ul class="mb-0 ps-3">' +
                '<li>Sube más imágenes por categoría (mínimo 20, ideal 50+)</li>' +
                '<li>Asegúrate de que las fotos sean claras y bien iluminadas</li>' +
                '<li>Incluye imágenes desde distintos ángulos</li>' +
                '</ul></div></div>';
        } else if (bestAcc !== null && bestAcc >= 85) {
            recHtml = '<div class="alert alert-success d-flex gap-2 align-items-start mb-0 small">' +
                '<i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0 mt-1"></i>' +
                '<div><strong>El modelo está listo para usar.</strong> ' +
                'Puedes seguir agregando imágenes para mantenerlo actualizado.</div></div>';
        }
        if (recHtml) html += recHtml;

        document.getElementById('modalDetalleBody').innerHTML = html;
        new bootstrap.Modal(document.getElementById('modalDetalle')).show();
    });
});

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── ELIMINAR FILA INDIVIDUAL ──────────────────────────────────────────────
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-eliminar-fila');
    if (!btn) return;
    var id = btn.dataset.id;
    Swal.fire({
        title: '¿Eliminar registro?',
        html: 'Se eliminará el entrenamiento <strong>#' + id + '</strong> del historial.<br><small class="text-danger">Esta acción no se puede deshacer.</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then(function(res) {
        if (!res.isConfirmed) return;
        var fd = new FormData();
        fd.append('action', 'delete_entrenamiento');
        fd.append('id', id);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    // Eliminar la fila de la tabla
                    var fila = btn.closest('tr');
                    if (fila) {
                        fila.style.transition = 'opacity 0.3s';
                        fila.style.opacity = '0';
                        setTimeout(function() { fila.remove(); }, 300);
                    }
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: r.message, timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                }
            } catch(e) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Respuesta inesperada del servidor.' });
            }
        };
        xhr.onerror = function() { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' }); };
        xhr.send(fd);
    });
});

// ── LIMPIAR TODO EL HISTORIAL ─────────────────────────────────────────────
var btnLimpiar = document.getElementById('btnLimpiarHistorial');
if (btnLimpiar) {
    btnLimpiar.addEventListener('click', function() {
        Swal.fire({
            title: '¿Limpiar todo el historial?',
            html: 'Se eliminarán <strong>todos los registros</strong> de entrenamientos.<br><small class="text-danger">Esta acción no se puede deshacer.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Sí, limpiar todo',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function(res) {
            if (!res.isConfirmed) return;
            var fd = new FormData();
            fd.append('action', 'delete_all_entrenamientos');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.pathname, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.success) {
                        Swal.fire({
                            icon: 'success', title: 'Historial limpiado',
                            text: r.message, timer: 2000, showConfirmButton: false
                        }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                    }
                } catch(e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Respuesta inesperada del servidor.' });
                }
            };
            xhr.onerror = function() { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' }); };
            xhr.send(fd);
        });
    });
}
</script>
<?php include_once '../includes/footer.php'; ?>
