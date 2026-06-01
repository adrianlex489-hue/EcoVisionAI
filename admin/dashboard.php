<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAdmin();

$total_usuarios = obtenerTotalUsuarios($pdo);
$total_clasificaciones = obtenerTotalClasificaciones($pdo);
$clasificaciones_categoria = obtenerClasificacionesPorCategoria($pdo);
$usuarios_activos = obtenerUsuariosMasActivos($pdo);

$stmt_dias = $pdo->query("SELECT DATE(fecha_clasificacion) as fecha, COUNT(*) as total FROM clasificaciones WHERE fecha_clasificacion >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(fecha_clasificacion) ORDER BY fecha ASC");
$clasificaciones_dias = $stmt_dias->fetchAll();
$fechas = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-{$i} days"));
    $fechas[$fecha] = 0;
}
foreach ($clasificaciones_dias as $d) {
    $fechas[$d['fecha']] = (int)$d['total'];
}
$dias_labels = array_keys($fechas);
$dias_data = array_values($fechas);

$cat_labels = [];
$cat_data = [];
$cat_colors = [];
$color_map = [
    'Plastico' => '#1565c0', 'Papel' => '#2e7d32', 'Vidrio' => '#f9a825',
    'Metal' => '#c62828', 'Organico' => '#5d4037', 'Papel y carton' => '#2e7d32'
];
foreach ($clasificaciones_categoria as $c) {
    $cat_labels[] = $c['categoria_detectada'];
    $cat_data[] = (int)$c['total'];
    $matched = false;
    foreach ($color_map as $k => $col) {
        if (stripos(strtr($c['categoria_detectada'], 'áéíóúñ', 'aeioun'), $k) !== false) {
            $cat_colors[] = $col; $matched = true; break;
        }
    }
    if (!$matched) $cat_colors[] = '#7b1fa2';
}

$stmt_confianza = $pdo->query("SELECT categoria_detectada, AVG(porcentaje_confianza) as promedio, COUNT(*) as total FROM clasificaciones GROUP BY categoria_detectada ORDER BY promedio DESC");
$confianza_categoria = $stmt_confianza->fetchAll();

$titulo = 'Panel Administrativo';
$css_admin = true;
$js_admin = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Panel Administrativo</h5>
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
                            <p class="stat-label">Total Usuarios</p>
                            <h3 class="stat-number"><?php echo $total_usuarios; ?></h3>
                            <small class="text-muted">Usuarios registrados</small>
                        </div>
                        <div class="stat-icon-box">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="stat-card-admin stat-card-azul anim-fade-in-up anim-delay-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label">Total Clasificaciones</p>
                            <h3 class="stat-number"><?php echo $total_clasificaciones; ?></h3>
                            <small class="text-muted">Residuos clasificados</small>
                        </div>
                        <div class="stat-icon-box">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="stat-card-admin stat-card-naranja anim-fade-in-up anim-delay-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-label">Categorias</p>
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
                            <h3 class="stat-number"><?php
                                $activos = 0;
                                foreach ($usuarios_activos as $u) { if ($u['total'] > 0) $activos++; }
                                echo $activos;
                            ?></h3>
                            <small class="text-muted">Con actividad reciente</small>
                        </div>
                        <div class="stat-icon-box">
                            <i class="bi bi-activity"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="panel-card anim-fade-in-up anim-delay-3">
                    <div class="card-header-icon">
                        <div class="icon-circle icon-verde">
                            <i class="bi bi-pie-chart"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Clasificaciones por Categoria</h6>
                            <small class="text-muted">Distribucion de residuos detectados</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($clasificaciones_categoria) > 0): ?>
                        <div class="row">
                            <div class="col-md-5">
                                <canvas id="chartCategoriaAdmin" height="240"></canvas>
                            </div>
                            <div class="col-md-7">
                                <div class="table-responsive">
                                    <table class="table admin-table mb-0">
                                        <thead>
                                            <tr><th>Categoria</th><th>Total</th><th>Porcentaje</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php $total_clasif = array_sum(array_column($clasificaciones_categoria, 'total'));
                                            $colores = ['success', 'primary', 'warning', 'danger', 'info', 'secondary', 'dark'];
                                            foreach ($clasificaciones_categoria as $j => $cat):
                                                $color = $colores[$j % count($colores)];
                                                $pct = $total_clasif > 0 ? round(($cat['total'] / $total_clasif) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td><span class="badge bg-<?php echo $color; ?>"><?php echo $cat['categoria_detectada']; ?></span></td>
                                                <td><strong><?php echo $cat['total']; ?></strong></td>
                                                <td style="min-width:120px;">
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-<?php echo $color; ?> progress-bar-striped-custom" style="width: <?php echo $pct; ?>%;"></div>
                                                    </div>
                                                    <small class="text-muted"><?php echo $pct; ?>%</small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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
            <div class="col-md-4 mb-4">
                <div class="panel-card anim-fade-in-up anim-delay-3">
                    <div class="card-header-icon">
                        <div class="icon-circle icon-azul">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Actividad (7 dias)</h6>
                            <small class="text-muted">Clasificaciones por dia</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($total_clasificaciones > 0): ?>
                        <canvas id="chartActividadAdmin" height="200"></canvas>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">Sin actividad aun.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="panel-card anim-fade-in-up anim-delay-4">
                    <div class="card-header-icon">
                        <div class="icon-circle icon-naranja">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Usuarios Mas Activos</h6>
                            <small class="text-muted">Ranking de contribuciones</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($usuarios_activos) > 0 && $usuarios_activos[0]['total'] > 0): ?>
                        <div class="table-responsive">
                            <table class="table admin-table">
                                <thead>
                                    <tr><th>#</th><th>Usuario</th><th>Clasificaciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios_activos as $i => $u): ?>
                                    <tr>
                                        <td>
                                            <?php if ($i === 0): ?>
                                                <span class="badge bg-warning text-dark rounded-circle p-2"><i class="bi bi-trophy-fill"></i></span>
                                            <?php elseif ($i === 1): ?>
                                                <span class="badge bg-secondary rounded-circle p-2"><i class="bi bi-trophy-fill"></i></span>
                                            <?php elseif ($i === 2): ?>
                                                <span class="badge bg-danger rounded-circle p-2"><i class="bi bi-trophy-fill"></i></span>
                                            <?php else: ?>
                                                <span class="fw-bold text-muted"><?php echo $i + 1; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-<?php echo ['success','primary','warning','info','danger','secondary','dark'][$i % 7]; ?> text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:0.8rem;font-weight:600;">
                                                    <?php echo strtoupper(substr($u['nombres'], 0, 1) . substr($u['apellidos'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo $u['nombres'] . ' ' . $u['apellidos']; ?></div>
                                                    <small class="text-muted"><?php echo $u['correo']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 fs-6 fw-bold">
                                                <?php echo $u['total']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-people fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No hay actividad registrada.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="panel-card anim-fade-in-up anim-delay-5">
                    <div class="card-header-icon">
                        <div class="icon-circle icon-morado">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Confianza Promedio por Categoria</h6>
                            <small class="text-muted">Precision del modelo IA por tipo de residuo</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($confianza_categoria) > 0): ?>
                        <canvas id="chartConfianzaAdmin" height="240"></canvas>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No hay datos de confianza disponibles.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    <?php if (count($clasificaciones_categoria) > 0): ?>
    var ctxCat = document.getElementById('chartCategoriaAdmin');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($cat_data); ?>,
                    backgroundColor: <?php echo json_encode($cat_colors); ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, boxWidth: 10, font: { size: 11 } } }
                },
                cutout: '60%'
            }
        });
    }
    <?php endif; ?>

    <?php if ($total_clasificaciones > 0): ?>
    var ctxAct = document.getElementById('chartActividadAdmin');
    if (ctxAct) {
        new Chart(ctxAct, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { return date('d/m', strtotime($d)); }, $dias_labels)); ?>,
                datasets: [{
                    label: 'Clasificaciones',
                    data: <?php echo json_encode($dias_data); ?>,
                    fill: true,
                    backgroundColor: 'rgba(25,135,84,0.1)',
                    borderColor: '#198754',
                    borderWidth: 2,
                    pointBackgroundColor: '#198754',
                    pointRadius: 3,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: '#adb5bd' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { ticks: { color: '#6c757d', font: { size: 10 } }, grid: { display: false } }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (count($confianza_categoria) > 0): ?>
    var ctxConf = document.getElementById('chartConfianzaAdmin');
    if (ctxConf) {
        <?php
        $conf_labels = [];
        $conf_data = [];
        $conf_totals = [];
        foreach ($confianza_categoria as $c) {
            $conf_labels[] = $c['categoria_detectada'];
            $conf_data[] = round((float)$c['promedio'], 1);
            $conf_totals[] = (int)$c['total'];
        }
        $conf_colors_chart = [];
        foreach ($conf_labels as $l) {
            $matched = false;
            foreach ($color_map as $k => $col) {
                if (stripos(strtr($l, 'áéíóúñ', 'aeioun'), $k) !== false) {
                    $conf_colors_chart[] = $col; $matched = true; break;
                }
            }
            if (!$matched) $conf_colors_chart[] = '#7b1fa2';
        }
        ?>
        new Chart(ctxConf, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($conf_labels); ?>,
                datasets: [{
                    label: 'Confianza Promedio (%)',
                    data: <?php echo json_encode($conf_data); ?>,
                    backgroundColor: <?php echo json_encode($conf_colors_chart); ?>,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(ctx) {
                                var totals = <?php echo json_encode($conf_totals); ?>;
                                return 'Muestras: ' + totals[ctx.dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 100,
                        ticks: { callback: function(v) { return v + '%'; }, color: '#adb5bd' },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    y: {
                        ticks: { color: '#6c757d', font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }
    <?php endif; ?>
})();
</script>
<?php include_once '../includes/footer.php'; ?>
