<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!estaAutenticado()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// AI_SERVICE_URL ya viene definida desde conexion.php
// En local: http://localhost:5000/predict
// En producción: https://TU-APP.onrender.com/predict  (via .env.php)

$action = $_GET['action'] ?? '';

if ($action === 'predict') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Error al recibir la imagen']);
        exit;
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'Formato de imagen no válido. Use JPG, PNG o WEBP.']);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['error' => 'La imagen no debe superar los 10MB.']);
        exit;
    }

    $ch = curl_init(AI_SERVICE_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Allow localhost connections (needed for XAMPP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        $msg = strpos($curl_error, 'Connection refused') !== false || strpos($curl_error, 'couldn\'t connect') !== false
            ? 'El servicio de IA no está activo. Contacta al administrador.'
            : 'Error de conexión con el servicio IA: ' . $curl_error;
        echo json_encode(['error' => $msg]);
        exit;
    }

    if ($http_code !== 200) {
        $flask_error = '';
        if ($response) {
            $decoded = json_decode($response, true);
            $flask_error = $decoded['error'] ?? '';
        }
        $msg = $flask_error ?: 'El servicio IA respondió con error (HTTP ' . $http_code . ')';
        echo json_encode(['error' => $msg]);
        exit;
    }

    $result = json_decode($response, true);

    if (!$result || isset($result['error'])) {
        echo json_encode(['error' => $result['error'] ?? 'Error al procesar la predicción']);
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/clasificaciones/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = uniqid('clasif_') . '.' . $ext;
    $dest_path = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        echo json_encode(['error' => 'Error al guardar la imagen']);
        exit;
    }

    $id_usuario = $_SESSION['usuario_id'];
    $categoria = $result['categoria'];
    $confianza = $result['confianza'];

    $stmt = $pdo->prepare("INSERT INTO clasificaciones (id_usuario, imagen, categoria_detectada, porcentaje_confianza) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_usuario, $filename, $categoria, $confianza]);

    echo json_encode([
        'categoria' => $categoria,
        'confianza' => $confianza,
        'imagen' => $filename
    ]);
} else {
    echo json_encode(['error' => 'Acción no válida']);
}
