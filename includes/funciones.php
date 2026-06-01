<?php

function obtenerTotalClasificaciones($pdo, $id_usuario = null) {
    if ($id_usuario) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clasificaciones WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clasificaciones");
    }
    return $stmt->fetch()['total'];
}

function obtenerUltimaClasificacion($pdo, $id_usuario) {
    $stmt = $pdo->prepare("SELECT * FROM clasificaciones WHERE id_usuario = ? ORDER BY fecha_clasificacion DESC LIMIT 1");
    $stmt->execute([$id_usuario]);
    return $stmt->fetch();
}

function obtenerTotalUsuarios($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    return $stmt->fetch()['total'];
}

function obtenerClasificacionesPorCategoria($pdo) {
    $stmt = $pdo->query("SELECT categoria_detectada, COUNT(*) as total FROM clasificaciones GROUP BY categoria_detectada ORDER BY total DESC");
    return $stmt->fetchAll();
}

function obtenerUsuariosMasActivos($pdo, $limite = 5) {
    $limite = (int)$limite;
    $stmt = $pdo->prepare("SELECT u.id_usuario, u.nombres, u.apellidos, u.correo, COUNT(c.id_clasificacion) as total
                         FROM usuarios u LEFT JOIN clasificaciones c ON u.id_usuario = c.id_usuario
                         GROUP BY u.id_usuario ORDER BY total DESC LIMIT $limite");
    $stmt->execute();
    return $stmt->fetchAll();
}

function obtenerClasificacionesPorFecha($pdo, $fecha_inicio = null, $fecha_fin = null) {
    $sql = "SELECT DATE(fecha_clasificacion) as fecha, COUNT(*) as total FROM clasificaciones";
    $params = [];
    if ($fecha_inicio && $fecha_fin) {
        $sql .= " WHERE DATE(fecha_clasificacion) BETWEEN ? AND ?";
        $params = [$fecha_inicio, $fecha_fin];
    }
    $sql .= " GROUP BY DATE(fecha_clasificacion) ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function limpiarDato($dato) {
    return htmlspecialchars(stripslashes(trim($dato)));
}

function enviarCorreo($para, $asunto, $mensajeHTML) {
    require_once __DIR__ . '/mail_config.php';
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        // Fix for XAMPP: OpenSSL certificate verification fails on local environments
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($para);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensajeHTML;

        $mail->send();
        return ['ok' => true];
    } catch (Exception $e) {
        error_log('[EcoVisionAI Mail Error] ' . $mail->ErrorInfo);
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}
