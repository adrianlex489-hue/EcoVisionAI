<div class="sidebar d-flex flex-column">
    <div class="sidebar-header text-center py-4">
        <button id="sidebarClose" class="sidebar-close-btn d-lg-none" aria-label="Cerrar">
            <i class="bi bi-x-lg"></i>
        </button>
        <i class="bi bi-recycle fs-1 text-success"></i>
        <h5 class="mt-2 text-white">EcoVision AI</h5>
    </div>
    <ul class="nav nav-pills flex-column px-3">
        <?php if (esAdmin()): ?>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/dashboard.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/usuarios.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/usuarios.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-people me-2"></i> Usuarios
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/reportes.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/reportes.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart me-2"></i> Reportes
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/entrenamiento.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/entrenamiento.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-cpu me-2"></i> Entrenar Modelo
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/historial_entrenamiento.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'historial_entrenamiento.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-clock-history me-2"></i> Historial IA
            </a>
        </li>
        <?php else: ?>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/dashboard/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/dashboard/clasificador.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clasificador.php' ? 'active' : ''; ?>">
                <i class="bi bi-camera me-2"></i> Clasificador IA
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/dashboard/historial.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'historial.php' ? 'active' : ''; ?>">
                <i class="bi bi-clock-history me-2"></i> Historial
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/dashboard/perfil.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <i class="bi bi-person me-2"></i> Perfil
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <div class="mt-auto px-3 pb-3">
        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline-light btn-sm w-100">
            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
        </a>
    </div>
</div>
