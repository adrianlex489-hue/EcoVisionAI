<?php

// ── Detectar entorno automáticamente ──────────────────────────────────────
// En producción (InfinityFree) define estas constantes en un archivo
// .env.php que NO se sube a GitHub (está en .gitignore)
$env_file = __DIR__ . '/../.env.php';
if (file_exists($env_file)) {
    require_once $env_file;
}

// Credenciales de BD — en producción vienen de .env.php
$host     = defined('DB_HOST')     ? DB_HOST     : 'localhost';
$dbname   = defined('DB_NAME')     ? DB_NAME     : 'ecovision_ai';
$username = defined('DB_USER')     ? DB_USER     : 'root';
$password = defined('DB_PASS')     ? DB_PASS     : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// BASE_URL — en local es /EcoVisionAI, en producción es / o el subdominio
if (!defined('BASE_URL')) {
    define('BASE_URL', defined('APP_BASE_URL') ? APP_BASE_URL : '/EcoVisionAI');
}

// URL del servicio Flask IA — en producción apunta a Render
if (!defined('AI_SERVICE_URL')) {
    $default_flask = defined('FLASK_URL') ? FLASK_URL : 'http://localhost:5000/predict';
    define('AI_SERVICE_URL', getenv('AI_SERVICE_URL') ?: $default_flask);
}

// URL base del servicio para /reload (sin /predict)
if (!defined('AI_BASE_URL')) {
    $ai_url = AI_SERVICE_URL;
    define('AI_BASE_URL', str_replace('/predict', '', $ai_url));
}

function getConexion() {
    global $pdo;
    return $pdo;
}
