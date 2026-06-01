<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'ADMIN';
}

function requerirAuth() {
    if (!estaAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

function requerirAdmin() {
    requerirAuth();
    if (!esAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function redireccionarSiAutenticado() {
    if (estaAutenticado()) {
        header('Location: ' . BASE_URL . '/dashboard/dashboard.php');
        exit;
    }
}
