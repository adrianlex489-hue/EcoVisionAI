<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAdmin();

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

$clasificaciones_categoria = obtenerClasificacionesPorCategoria($pdo);
$clasificaciones_fecha = obtenerClasificacionesPorFecha($pdo, $fecha_inicio, $fecha_fin);
$usuarios_activos = obtenerUsuariosMasActivos($pdo, 10);

$titulo = 'Reportes';
$css_admin = true;
$js_admin = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <h5 class="mb-0"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes</h5>
            <div class="d-flex align-items-center">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo $_SESSION['usuario_nombres']; ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="admin-grid admin-grid-4 mb-4">
            <div class="stat-card-admin stat-card-verde anim-fade-in-up anim-delay-1">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label">Total Clasificaciones</p>
                            <h3 class="stat-number"><?php echo array_sum(array_column($clasificaciones_categoria, 'total')); ?></h3>
                            <small class="text-muted">Histórico completo</small>
                        </div>
                        <div class="stat-icon-box">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="stat-card-admin stat-card-azul anim-fade-in-up anim-delay-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label">Rango Actual</p>
                            <h3 class="stat-number" style="font-size:1.2rem;"><?php echo date('d/m', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></h3>
                            <small class="text-muted">Período de filtro</small>
                        </div>
                        <div class="stat-icon-box">
                            <i class="bi bi-calendar-range"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="stat-card-admin stat-card-naranja anim-fade-in-up anim-delay-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label">Categorías</p>
                            <h3 class="stat-number"><?php echo count($clasificaciones_categoria); ?></h3>
                            <small class="text-muted">Tipos de residuo</small>
                        </div>
                        <div class="stat-icon-box">
                            <i class="bi bi-tags"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="stat-card-admin stat-card-morado anim-fade-in-up anim-delay-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label">Usuarios Activos</p>
                            <h3 class="stat-number"><?php echo count($usuarios_activos); ?></h3>
                            <small class="text-muted">Top clasificadores</small>
                        </div>
                        <div class="stat-icon-box">
                            <i class="bi bi-trophy"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-card mb-4 anim-fade-in-up anim-delay-2">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><i class="bi bi-calendar-start me-1 text-success"></i>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-lg" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><i class="bi bi-calendar-end me-1 text-success"></i>Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control form-control-lg" value="<?php echo $fecha_fin; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg flex-grow-1"><i class="bi bi-search me-1"></i>Filtrar</button>
                            <a href="reportes.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-muted small">&nbsp;</label>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="?fecha_inicio=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-success <?php echo $fecha_inicio === date('Y-m-d', strtotime('-7 days')) ? 'active' : ''; ?>">7 días</a>
                            <a href="?fecha_inicio=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-success <?php echo $fecha_inicio === date('Y-m-d', strtotime('-30 days')) ? 'active' : ''; ?>">30 días</a>
                            <a href="?fecha_inicio=<?php echo date('Y-m-d', strtotime('-90 days')); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-success <?php echo $fecha_inicio === date('Y-m-d', strtotime('-90 days')) ? 'active' : ''; ?>">90 días</a>
                            <a href="?fecha_inicio=<?php echo date('Y-01-01'); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-success <?php echo $fecha_inicio === date('Y-01-01') ? 'active' : ''; ?>">Este año</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="panel-card anim-fade-in-up anim-delay-3">
                    <div class="card-header-icon">
                        <div class="icon-circle icon-verde">
                            <i class="bi bi-pie-chart"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Clasificaciones por Categoría</h6>
                            <small class="text-muted">Distribución general</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $total_clasif = array_sum(array_column($clasificaciones_categoria, 'total'));
                        $colores_bar = ['success', 'primary', 'warning', 'danger', 'info', 'secondary', 'dark'];
                        if (count($clasificaciones_categoria) > 0):
                        ?>
                        <div class="table-responsive">
                            <table class="table admin-table">
                                <thead><tr><th>Categoría</th><th>Total</th><th>Porcentaje</th></tr></thead>
                                <tbody>
                                    <?php foreach ($clasificaciones_categoria as $k => $cat):
                                        $color = $colores_bar[$k % count($colores_bar)];
                                        $porcentaje = $total_clasif > 0 ? round(($cat['total'] / $total_clasif) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> px-3 py-2">
                                                <i class="bi bi-dot me-1"></i><?php echo $cat['categoria_detectada']; ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold"><?php echo $cat['total']; ?></td>
                                        <td style="min-width:180px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:10px;border-radius:5px;">
                                                    <div class="progress-bar bg-<?php echo $color; ?> progress-bar-striped-custom" style="width:<?php echo $porcentaje; ?>%;" role="progressbar"></div>
                                                </div>
                                                <span class="fw-bold small text-<?php echo $color; ?>"><?php echo $porcentaje; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-2 mb-0">No hay clasificaciones registradas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="panel-card anim-fade-in-up anim-delay-4">
                    <div class="card-header-icon">
                        <div class="icon-circle icon-azul">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Clasificaciones por Fecha</h6>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($clasificaciones_fecha) > 0):
                            $max_fecha = max(array_column($clasificaciones_fecha, 'total'));
                        ?>
                        <div class="table-responsive" style="max-height:350px;">
                            <table class="table admin-table">
                                <thead><tr><th>Fecha</th><th>Total</th><th>Actividad</th></tr></thead>
                                <tbody>
                                    <?php foreach ($clasificaciones_fecha as $f):
                                        $pct_bar = $max_fecha > 0 ? round(($f['total'] / $max_fecha) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><span class="fw-semibold"><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></span></td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success px-3 py-2"><?php echo $f['total']; ?></span></td>
                                        <td style="min-width:120px;">
                                            <div class="progress" style="height:6px;border-radius:3px;">
                                                <div class="progress-bar bg-success" style="width:<?php echo $pct_bar; ?>%;" role="progressbar"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                <p class="text-muted mt-2 mb-0">No hay clasificaciones en este rango.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-card mb-4 anim-fade-in-up anim-delay-5">
            <div class="card-header-icon">
                <div class="icon-circle icon-naranja">
                    <i class="bi bi-trophy"></i>
                </div>
                <div>
                    <h6 class="mb-0">Usuarios Más Activos</h6>
                    <small class="text-muted">Top 10 clasificadores</small>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr><th>#</th><th>Usuario</th><th>Correo</th><th>Clasificaciones</th><th>Rendimiento</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios_activos) > 0 && $usuarios_activos[0]['total'] > 0):
                                $max_total = $usuarios_activos[0]['total'];
                                $podio = ['bg-warning text-dark', 'bg-secondary', 'bg-danger'];
                                $iconos = ['trophy-fill', 'trophy-fill', 'trophy-fill'];
                                foreach ($usuarios_activos as $i => $u):
                                    $pct = $max_total > 0 ? round(($u['total'] / $max_total) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($i < 3): ?>
                                        <span class="badge <?php echo $podio[$i]; ?> rounded-circle p-2" style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-<?php echo $iconos[$i]; ?>"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="fw-bold text-muted ms-2"><?php echo $i + 1; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-<?php echo ['success','primary','warning','info','danger','secondary','dark','success','primary','warning'][$i % 10]; ?> text-white d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:0.8rem;">
                                            <?php echo strtoupper(substr($u['nombres'], 0, 1) . substr($u['apellidos'], 0, 1)); ?>
                                        </div>
                                        <span class="fw-semibold"><?php echo $u['nombres'] . ' ' . $u['apellidos']; ?></span>
                                    </div>
                                </td>
                                <td><span class="text-muted"><?php echo $u['correo']; ?></span></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success fs-6 px-3 py-2"><?php echo $u['total']; ?></span></td>
                                <td style="min-width:150px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;border-radius:4px;">
                                            <div class="progress-bar bg-success" style="width:<?php echo $pct; ?>%;" role="progressbar"></div>
                                        </div>
                                        <small class="fw-bold text-muted"><?php echo $pct; ?>%</small>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Sin actividad registrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row anim-fade-in-up anim-delay-5">
            <div class="col-12 mb-4">
                <div class="panel-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-download me-2 text-success"></i>Exportar Reportes</h6>
                                <small class="text-muted">Descarga los datos en formato CSV</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button id="exportCSV" class="btn btn-success btn-lg px-4">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once '../includes/footer.php'; ?>
