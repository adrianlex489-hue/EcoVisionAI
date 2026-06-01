<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAuth();

$id_usuario = $_SESSION['usuario_id'];
$total_clasificaciones = obtenerTotalClasificaciones($pdo, $id_usuario);
$ultima_clasificacion = obtenerUltimaClasificacion($pdo, $id_usuario);

$stmt_cat = $pdo->prepare("SELECT categoria_detectada, COUNT(*) as total FROM clasificaciones WHERE id_usuario = ? GROUP BY categoria_detectada ORDER BY total DESC");
$stmt_cat->execute([$id_usuario]);
$clasificaciones_categoria = $stmt_cat->fetchAll();

$stmt_dias = $pdo->prepare("SELECT DATE(fecha_clasificacion) as fecha, COUNT(*) as total FROM clasificaciones WHERE id_usuario = ? AND fecha_clasificacion >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(fecha_clasificacion) ORDER BY fecha ASC");
$stmt_dias->execute([$id_usuario]);
$clasificaciones_dias = $stmt_dias->fetchAll();

$stmt_confianza = $pdo->prepare("SELECT categoria_detectada, AVG(porcentaje_confianza) as promedio, COUNT(*) as total FROM clasificaciones WHERE id_usuario = ? GROUP BY categoria_detectada ORDER BY promedio DESC");
$stmt_confianza->execute([$id_usuario]);
$confianza_categoria = $stmt_confianza->fetchAll();

$stmt_ultimas = $pdo->prepare("SELECT * FROM clasificaciones WHERE id_usuario = ? ORDER BY fecha_clasificacion DESC LIMIT 5");
$stmt_ultimas->execute([$id_usuario]);
$ultimas_clasificaciones = $stmt_ultimas->fetchAll();

$stmt_conf_dias = $pdo->prepare("SELECT DATE(fecha_clasificacion) as fecha, AVG(porcentaje_confianza) as promedio FROM clasificaciones WHERE id_usuario = ? AND fecha_clasificacion >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(fecha_clasificacion) ORDER BY fecha ASC");
$stmt_conf_dias->execute([$id_usuario]);
$confianza_dias = $stmt_conf_dias->fetchAll();

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

$conf_por_dia = [];
foreach ($confianza_dias as $cd) {
    $conf_por_dia[$cd['fecha']] = round((float)$cd['promedio'], 1);
}
$dias_confianza = array_map(function($f) use ($conf_por_dia) {
    return $conf_por_dia[$f] ?? 0;
}, $dias_labels);

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

$titulo = 'Dashboard';
$css_extra = 'dashboard.css';
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <h5 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h5>
            <div class="d-flex align-items-center">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo $_SESSION['usuario_nombres']; ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="welcome-card bg-success text-white rounded-4 p-4 shadow anim-fade-in-up" style="background: linear-gradient(135deg, #198754, #0d6e3e, #145c32); background-size: 200% 200%; animation: fadeInUp 0.6s ease both, gradientShift 6s ease infinite;">
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-none d-sm-block">
                            <i class="bi bi-recycle" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><i class="bi bi-hand-wave me-2"></i>Bienvenido, <?php echo $_SESSION['usuario_nombres']; ?>!</h3>
                            <p class="mb-0 fs-5">Has realizado <strong><?php echo $total_clasificaciones; ?></strong> clasificaciones hasta ahora.</p>
                            <div class="mt-2">
                                <span class="badge bg-white text-success rounded-pill px-3 py-2">
                                    <i class="bi bi-star-fill me-1"></i>
                                    <?php echo $total_clasificaciones >= 100 ? 'Experto en reciclaje' : ($total_clasificaciones >= 50 ? 'Reciclador avanzado' : ($total_clasificaciones >= 10 ? 'Reciclador activo' : 'Empieza a clasificar!')); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm card-hover stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Clasificaciones</p>
                                <h2 class="mb-0 text-success fw-bold"><?php echo $total_clasificaciones; ?></h2>
                            </div>
                            <div class="stat-icon bg-success-subtle rounded-3 p-3">
                                <i class="bi bi-box-seam fs-2 text-success"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, $total_clasificaciones); ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm card-hover stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Categorias</p>
                                <h2 class="mb-0 text-success fw-bold"><?php echo count($clasificaciones_categoria); ?></h2>
                            </div>
                            <div class="stat-icon bg-success-subtle rounded-3 p-3">
                                <i class="bi bi-tags fs-2 text-success"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted"><?php echo $total_clasificaciones > 0 ? 'Distintos tipos clasificados' : 'Aun sin clasificar'; ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm card-hover stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Acceso Rapido</p>
                                <a href="clasificador.php" class="btn btn-success mt-2 ripple-btn">
                                    <i class="bi bi-camera me-1"></i> Clasificar
                                </a>
                            </div>
                            <div class="stat-icon bg-success-subtle rounded-3 p-3">
                                <i class="bi bi-camera fs-2 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <?php if ($total_clasificaciones > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 d-flex align-items-center pt-3 px-4">
                        <i class="bi bi-pie-chart me-2 text-success fs-5"></i>
                        <h6 class="mb-0 flex-grow-1">Clasificaciones por Categoria</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="chartCategoria" height="220"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 d-flex align-items-center pt-3 px-4">
                        <i class="bi bi-bar-chart me-2 text-success fs-5"></i>
                        <h6 class="mb-0 flex-grow-1">Actividad (Ultimos 7 dias)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="chartActividad" height="220"></canvas>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                        <h5 class="text-muted">Aun no has realizado clasificaciones</h5>
                        <p class="text-muted mb-3">Sube una imagen para ver tus estadisticas aqui.</p>
                        <a href="clasificador.php" class="btn btn-success"><i class="bi bi-camera me-1"></i> Ir al clasificador</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($total_clasificaciones > 0): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm card-hover anim-fade-in-up">
                    <div class="card-header bg-white border-0 d-flex align-items-center">
                        <i class="bi bi-bar-chart-steps me-2 text-success"></i>
                        <h6 class="mb-0 flex-grow-1">Clasificacion Diaria</h6>
                        <span class="badge bg-success-subtle text-success rounded-pill">7 dias</span>
                    </div>
                    <div class="card-body">
                        <canvas id="chartCombo" height="240"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm card-hover anim-fade-in-up">
                    <div class="card-header bg-white border-0 d-flex align-items-center">
                        <i class="bi bi-bar-chart-line me-2 text-success"></i>
                        <h6 class="mb-0 flex-grow-1">Confianza Promedio por Categoria</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($confianza_categoria) > 0): ?>
                        <canvas id="chartConfianza" height="220"></canvas>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0">No hay datos de confianza disponibles.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($ultimas_clasificaciones) > 0): ?>
        <div class="row mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm anim-fade-in-up">
                    <div class="card-header bg-white border-0 d-flex align-items-center pt-3 px-4">
                        <i class="bi bi-clock-history me-2 text-success fs-5"></i>
                        <h6 class="mb-0 flex-grow-1">Ultimas 5 Clasificaciones</h6>
                        <a href="historial.php" class="btn btn-sm btn-outline-success">Ver historial completo <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Imagen</th>
                                        <th>Categoria</th>
                                        <th>Confianza</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimas_clasificaciones as $c):
                                        $catColor = ['Plastico'=>'primary','Papel'=>'success','Papel y carton'=>'success','Vidrio'=>'warning','Metal'=>'danger','Organico'=>'secondary'];
                                        $cc = 'success';
                                        foreach ($catColor as $k => $col) {
                                            if (stripos(strtr($c['categoria_detectada'], 'áéíóúñ', 'aeioun'), $k) !== false) { $cc = $col; break; }
                                        }
                                        $conf = (int)$c['porcentaje_confianza'];
                                        $cl = $conf >= 80 ? 'success' : ($conf >= 50 ? 'warning' : 'danger');
                                    ?>
                                    <tr class="align-middle">
                                        <td class="ps-4">
                                            <img src="<?php echo BASE_URL; ?>/uploads/clasificaciones/<?php echo $c['imagen']; ?>"
                                                 class="rounded-3" style="width: 42px; height: 42px; object-fit: cover;">
                                        </td>
                                        <td><span class="badge bg-<?php echo $cc; ?> rounded-pill px-3 py-2"><?php echo $c['categoria_detectada']; ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress" style="height: 6px; width: 80px;">
                                                    <div class="progress-bar bg-<?php echo $cl; ?>" style="width: <?php echo $conf; ?>%;"></div>
                                                </div>
                                                <small class="text-<?php echo $cl; ?> fw-medium"><?php echo $conf; ?>%</small>
                                            </div>
                                        </td>
                                        <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($c['fecha_clasificacion'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.ripple-btn { position: relative; overflow: hidden; }
.welcome-card { position: relative; overflow: hidden; }
.welcome-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
@media (max-width: 576px) {
    .welcome-card h3 { font-size: 1.25rem; }
    .stat-card h2 { font-size: 1.5rem; }
}
</style>

<script>
(function() {
    'use strict';

    <?php if ($total_clasificaciones > 0): ?>
    var ctxCat = document.getElementById('chartCategoria');
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
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, color: '#6c757d' } }
                },
                cutout: '65%'
            }
        });
    }

    var ctxAct = document.getElementById('chartActividad');
    if (ctxAct) {
        new Chart(ctxAct, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { return date('d/m', strtotime($d)); }, $dias_labels)); ?>,
                datasets: [{
                    label: 'Clasificaciones',
                    data: <?php echo json_encode($dias_data); ?>,
                    backgroundColor: 'rgba(25,135,84,0.2)',
                    borderColor: '#198754',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: '#adb5bd' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { ticks: { color: '#6c757d' }, grid: { display: false } }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (count($confianza_categoria) > 0): ?>
    var ctxConf = document.getElementById('chartConfianza');
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

    <?php if ($total_clasificaciones > 0 && count($dias_confianza) > 0): ?>
    var ctxCombo = document.getElementById('chartCombo');
    if (ctxCombo) {
        new Chart(ctxCombo, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { return date('d/m', strtotime($d)); }, $dias_labels)); ?>,
                datasets: [{
                    label: 'Clasificaciones',
                    data: <?php echo json_encode($dias_data); ?>,
                    backgroundColor: 'rgba(25,135,84,0.3)',
                    borderColor: '#198754',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                    order: 2,
                    yAxisID: 'y'
                }, {
                    label: 'Confianza promedio',
                    type: 'line',
                    data: <?php echo json_encode($dias_confianza); ?>,
                    borderColor: '#1565c0',
                    backgroundColor: 'rgba(21,101,192,0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#1565c0',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.3,
                    order: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 12, font: { size: 11 }, color: '#6c757d' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.dataset.yAxisID === 'y1') return ctx.parsed.y + '% confianza';
                                return ctx.parsed.y + ' clasificaciones';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#adb5bd' },
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        title: { display: true, text: 'Clasificaciones', color: '#6c757d', font: { size: 10 } }
                    },
                    y1: {
                        position: 'right',
                        min: 0,
                        max: 100,
                        ticks: { callback: function(v) { return v + '%'; }, color: '#1565c0' },
                        grid: { display: false },
                        title: { display: true, text: 'Confianza', color: '#1565c0', font: { size: 10 } }
                    },
                    x: {
                        ticks: { color: '#6c757d', font: { size: 10 } },
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
