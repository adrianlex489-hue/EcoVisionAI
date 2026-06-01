<?php
ob_start();
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAuth();

// ── DELETE handler ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_clasificacion') {
    ob_clean();
    header('Content-Type: application/json');
    $id         = (int)($_POST['id'] ?? 0);
    $id_usuario = (int)$_SESSION['usuario_id'];
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID no válido.']);
        exit;
    }
    try {
        // Obtener nombre de imagen antes de borrar
        $stmt = $pdo->prepare("SELECT imagen FROM clasificaciones WHERE id_clasificacion = ? AND id_usuario = ?");
        $stmt->execute([$id, $id_usuario]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Registro no encontrado.']);
            exit;
        }
        // Borrar registro de BD
        $del = $pdo->prepare("DELETE FROM clasificaciones WHERE id_clasificacion = ? AND id_usuario = ?");
        $del->execute([$id, $id_usuario]);
        // Borrar imagen del disco si existe
        if (!empty($row['imagen'])) {
            $img_path = __DIR__ . '/../uploads/clasificaciones/' . basename($row['imagen']);
            if (file_exists($img_path)) @unlink($img_path);
        }
        echo json_encode(['success' => true, 'message' => 'Clasificación eliminada correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

$skip_jquery = true;
$id_usuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT * FROM clasificaciones WHERE id_usuario = ? ORDER BY fecha_clasificacion DESC");
$stmt->execute([$id_usuario]);
$clasificaciones = $stmt->fetchAll();

$stmt_cat = $pdo->prepare("SELECT DISTINCT categoria_detectada FROM clasificaciones WHERE id_usuario = ? ORDER BY categoria_detectada");
$stmt_cat->execute([$id_usuario]);
$categorias = $stmt_cat->fetchAll();

$titulo = 'Historial';
$css_extra = 'dashboard.css';
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<style>
div.dt-container .dt-paging .dt-paging-button.current,
div.dt-container .dt-paging .dt-paging-button.current:hover {
    background: #198754 !important;
    color: #fff !important;
    border-color: #198754 !important;
}
div.dt-container .dt-paging .dt-paging-button:hover {
    background: #f0faf4 !important;
    color: #198754 !important;
    border-color: #198754 !important;
}
div.dt-container .dt-search input:focus,
div.dt-container .dt-length select:focus {
    border-color: #198754 !important;
    box-shadow: 0 0 0 2px rgba(25,135,84,0.15) !important;
}
div.dt-container .dt-info {
    color: #6c757d !important;
    font-size: 0.875rem !important;
}
table.dataTable > tbody > tr:hover {
    background: #f0faf4 !important;
}
.cat-option:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
    border-color: #198754 !important;
}
.cat-option.selected {
    border-color: #198754 !important;
    border-width: 2px !important;
    box-shadow: 0 0 0 3px rgba(25,135,84,0.15) !important;
}
</style>
<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de Clasificaciones</h5>
            <div class="d-flex align-items-center">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo $_SESSION['usuario_nombres']; ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="card border-0 shadow-sm mb-4 anim-fade-in-up">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-medium"><i class="bi bi-search me-1"></i>Buscar</label>
                        <input type="text" id="filtroBusqueda" class="form-control" placeholder="Buscar en tabla...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium"><i class="bi bi-tag me-1"></i>Categoria</label>
                        <div class="position-relative">
                            <input type="text" id="filtroCategoriaDisplay" class="form-control" placeholder="Todas las categorias" readonly style="cursor:pointer;background:#fff;" data-value="">
                            <input type="hidden" id="filtroCategoria" value="">
                            <span class="position-absolute end-0 top-50 translate-middle-y me-3 text-muted"><i class="bi bi-chevron-down small"></i></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium"><i class="bi bi-calendar me-1"></i>Fecha</label>
                        <input type="date" id="filtroFecha" class="form-control">
                    </div>
                    <div class="col-md-3 d-flex gap-2 align-items-end">
                        <button type="button" id="btnFiltrar" class="btn btn-success flex-grow-1"><i class="bi bi-search"></i><span class="filter-btn-text ms-1">Filtrar</span></button>
                        <button type="button" id="btnLimpiar" class="btn btn-outline-secondary flex-grow-1"><i class="bi bi-x-lg"></i><span class="filter-btn-text ms-1 d-md-none">Limpiar</span></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm anim-fade-in-up">
            <div class="card-body p-0">
                <div class="table-responsive" style="overflow-x: auto;">
                <table id="historialTable" class="table table-hover mb-0 display" style="width:100%;">
                    <thead class="bg-light">
                        <tr>
                            <th>Imagen</th>
                            <th>Categoria</th>
                            <th>Confianza</th>
                            <th>Fecha</th>
                            <th class="text-end" data-orderable="false">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clasificaciones) > 0): ?>
                            <?php foreach ($clasificaciones as $c):
                                $confianza = (int)$c['porcentaje_confianza'];
                                $confianzaClass = $confianza >= 80 ? 'success' : ($confianza >= 50 ? 'warning' : 'danger');
                            ?>
                            <tr class="align-middle">
                                <td>
                                    <img src="<?php echo BASE_URL; ?>/uploads/clasificaciones/<?php echo $c['imagen']; ?>"
                                         class="rounded-3" style="width: 48px; height: 48px; object-fit: cover; border: 2px solid rgba(25,135,84,0.1);"
                                         alt="Clasificacion">
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 cat-badge" data-cat="<?php echo $c['categoria_detectada']; ?>">
                                        <i class="bi bi-tag me-1"></i><?php echo $c['categoria_detectada']; ?>
                                    </span>
                                </td>
                                <td data-order="<?php echo $confianza; ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                            <div class="progress-bar bg-<?php echo $confianzaClass; ?> progress-bar-striped" style="width: <?php echo $confianza; ?>%;"></div>
                                        </div>
                                        <small class="text-<?php echo $confianzaClass; ?> fw-medium"><?php echo $confianza; ?>%</small>
                                    </div>
                                </td>
                                <td data-order="<?php echo $c['fecha_clasificacion']; ?>" data-search="<?php echo $c['fecha_clasificacion']; ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar3 text-muted me-2 small"></i>
                                        <span><?php echo date('d/m/Y H:i', strtotime($c['fecha_clasificacion'])); ?></span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-success me-1"
                                            onclick="verDetalle(this)"
                                            title="Ver detalle IA"
                                            data-id="<?php echo $c['id_clasificacion']; ?>"
                                            data-imagen="<?php echo $c['imagen']; ?>"
                                            data-categoria="<?php echo $c['categoria_detectada']; ?>"
                                            data-confianza="<?php echo $c['porcentaje_confianza']; ?>"
                                            data-fecha="<?php echo $c['fecha_clasificacion']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarEliminacion(<?php echo $c['id_clasificacion']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="categoriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-tags me-2"></i>Seleccionar Categoria</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3" id="categoriaOpciones">
                    <div class="col-6">
                        <div class="cat-option card border h-100" data-value="" style="cursor:pointer;transition:all 0.2s;border-radius:12px;">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-grid-3x3-gap-fill fs-2 text-secondary"></i>
                                <p class="mb-0 mt-2 fw-semibold">Todas</p>
                            </div>
                        </div>
                    </div>
                    <?php
                    $cat_icons = [
                        'Plastico' => 'bi bi-box-seam', 'Papel' => 'bi bi-file-text',
                        'Papel y carton' => 'bi bi-file-earmark-text', 'Vidrio' => 'bi bi-cup-straw',
                        'Metal' => 'bi bi-tools', 'Organico' => 'bi bi-flower2'
                    ];
                    $cat_colores = [
                        'Plastico' => '#1565c0', 'Papel' => '#2e7d32',
                        'Papel y carton' => '#2e7d32', 'Vidrio' => '#f9a825',
                        'Metal' => '#c62828', 'Organico' => '#5d4037'
                    ];
                    $cat_bg = [
                        'Plastico' => '#e3f2fd', 'Papel' => '#e8f5e9',
                        'Papel y carton' => '#e8f5e9', 'Vidrio' => '#fff8e1',
                        'Metal' => '#ffebee', 'Organico' => '#efebe9'
                    ];
                    foreach ($categorias as $cat):
                        $nombre = $cat['categoria_detectada'];
                        $icono = $cat_icons[$nombre] ?? 'bi bi-recycle';
                        $color = $cat_colores[$nombre] ?? '#198754';
                        $bg = $cat_bg[$nombre] ?? '#e8f5e9';
                    ?>
                    <div class="col-6">
                        <div class="cat-option card border h-100" data-value="<?php echo $nombre; ?>" style="cursor:pointer;transition:all 0.2s;border-radius:12px;background:<?php echo $bg; ?>;border-color:<?php echo $color; ?>33;">
                            <div class="card-body text-center py-3">
                                <i class="<?php echo $icono; ?> fs-2" style="color:<?php echo $color; ?>"></i>
                                <p class="mb-0 mt-2 fw-semibold" style="color:<?php echo $color; ?>"><?php echo $nombre; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden">
            <div class="row g-0">
                <div class="col-md-6 d-flex align-items-center bg-light">
                    <img id="detalleImagen" class="img-fluid p-3" alt="Imagen clasificacion" style="max-height: 400px; object-fit: contain;">
                </div>
                <div class="col-md-6">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-robot me-2 text-success"></i>Detalle de clasificacion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div id="detalleContent">
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Categoria detectada</small>
                                <span id="detalleCategoria" class="badge rounded-pill px-3 py-2 fs-6"></span>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Confianza</small>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px;">
                                        <div id="detalleBarra" class="progress-bar" style="width: 0%;"></div>
                                    </div>
                                    <strong id="detalleConfianza" class="small"></strong>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Fecha de clasificacion</small>
                                <p class="mb-0 fw-medium" id="detalleFecha"></p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">ID de clasificacion</small>
                                <p class="mb-0 fw-medium" id="detalleId"></p>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-3 p-3 mt-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-cpu fs-5 text-success"></i>
                                    <div>
                                        <small class="text-success fw-medium d-block">Procesado por IA</small>
                                        <small class="text-muted">Modelo TensorFlow / Keras</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
var catBadgeColors = {
    'Plastico':'primary','Papel y carton':'success','Papel':'success',
    'Vidrio':'warning','Metal':'danger','Organico':'secondary'
};
function normalize(cat) {
    var map = {'\u00e1':'a','\u00e9':'e','\u00ed':'i','\u00f3':'o','\u00fa':'u','\u00c1':'A','\u00c9':'E','\u00cd':'I','\u00d3':'O','\u00da':'U','\u00f1':'n'};
    return cat.replace(/[^\x00-\x7F]/g, function(ch) { return map[ch] || ch; }).toLowerCase();
}
function getCatBadgeColor(cat) {
    var ncat = normalize(cat);
    for (var key in catBadgeColors) {
        if (ncat.indexOf(normalize(key)) !== -1) return catBadgeColors[key];
    }
    return 'success';
}

$(document).ready(function() {
    $('.cat-badge').each(function() {
        $(this).addClass('bg-' + getCatBadgeColor($(this).data('cat')));
    });

    var table = $('#historialTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        order: [[3, 'desc']],
        columnDefs: [
            { orderable: false, targets: 4 }
        ],
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'Todos']]
    });

    $('#filtroBusqueda').on('keyup', function() {
        table.search(this.value).draw();
    });

    $('#filtroCategoriaDisplay').on('click', function() {
        new bootstrap.Modal(document.getElementById('categoriaModal')).show();
    });

    $('#categoriaOpciones').on('click', '.cat-option', function() {
        var val = $(this).data('value');
        var label = val || 'Todas las categorias';
        $('#filtroCategoria').val(val);
        $('#filtroCategoriaDisplay').val(label).data('value', val);
        bootstrap.Modal.getInstance(document.getElementById('categoriaModal')).hide();
    });

    function aplicarFiltros() {
        var cat = $('#filtroCategoria').val();
        var fecha = $('#filtroFecha').val();
        table.column(1).search(cat);
        if (fecha) table.column(3).search(fecha);
        else table.column(3).search('');
        table.draw();
    }

    $('#btnFiltrar').on('click', aplicarFiltros);
    $('#btnLimpiar').on('click', function() {
        $('#filtroBusqueda, #filtroCategoria').val('');
        $('#filtroCategoriaDisplay').val('').data('value', '');
        $('#filtroFecha').val('');
        table.search('').columns().search('').draw();
    });
});

function verDetalle(btn) {
    document.getElementById('detalleImagen').src = '<?php echo BASE_URL; ?>/uploads/clasificaciones/' + btn.getAttribute('data-imagen');
    var cat = btn.getAttribute('data-categoria');
    var badge = document.getElementById('detalleCategoria');
    badge.textContent = cat;
    badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-' + getCatBadgeColor(cat);
    var ci = Math.round(parseFloat(btn.getAttribute('data-confianza')));
    document.getElementById('detalleBarra').style.width = ci + '%';
    document.getElementById('detalleConfianza').textContent = ci + '%';
    document.getElementById('detalleFecha').textContent = btn.getAttribute('data-fecha');
    document.getElementById('detalleId').textContent = '#' + btn.getAttribute('data-id');
    new bootstrap.Modal(document.getElementById('detalleModal')).show();
}
function confirmarEliminacion(id) {
    Swal.fire({
        title: '\u00bfEliminar clasificaci\u00f3n?',
        text: 'Esta acci\u00f3n no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash me-1"></i>S\u00ed, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then(function(r) {
        if (!r.isConfirmed) return;
        var fd = new FormData();
        fd.append('action', 'delete_clasificacion');
        fd.append('id', id);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    // Eliminar la fila de la tabla DataTables
                    var btn = document.querySelector('[data-id="' + id + '"]');
                    if (btn) {
                        var fila = btn.closest('tr');
                        if (fila) {
                            var dt = $('#historialTable').DataTable();
                            dt.row(fila).remove().draw();
                        }
                    }
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: res.message, timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            } catch(e) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Respuesta inesperada del servidor.' });
            }
        };
        xhr.onerror = function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' });
        };
        xhr.send(fd);
    });
}
</script>
<?php include_once '../includes/footer.php'; ?>
