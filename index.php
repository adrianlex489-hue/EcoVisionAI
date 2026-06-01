<?php
require_once 'includes/conexion.php';
require_once 'includes/auth.php';
if (estaAutenticado()) {
    $destino = esAdmin() ? '/admin/dashboard.php' : '/dashboard/dashboard.php';
    header('Location: ' . BASE_URL . $destino);
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
