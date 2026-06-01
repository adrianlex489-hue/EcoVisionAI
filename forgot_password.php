<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/auth.php';
redireccionarSiAutenticado();

$css_auth = true;
$error = '';
$success = '';
$correo_enviado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = limpiarDato($_POST['correo']);

    if (!empty($correo)) {
        $stmt = $pdo->prepare("SELECT id_usuario, nombres FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $stmt = $pdo->prepare("UPDATE reset_codigos SET usado = 1 WHERE id_usuario = ? AND usado = 0");
            $stmt->execute([$usuario['id_usuario']]);

            $stmt = $pdo->prepare("INSERT INTO reset_codigos (id_usuario, codigo, expira) VALUES (?, ?, ?)");
            $stmt->execute([$usuario['id_usuario'], $codigo, $expira]);

            $asunto = 'Codigo de recuperacion - EcoVision AI';
            $mensaje = '
                <div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 30px; background: #f9fdfb; border-radius: 16px; border: 1px solid #d1f0dc;">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <div style="font-size: 3rem; color: #198754;">&#x267B;</div>
                        <h2 style="color: #0f3823; margin: 8px 0 4px;">EcoVision AI</h2>
                        <p style="color: #6c757d; font-size: 14px; margin: 0;">Recuperacion de contrasena</p>
                    </div>
                    <p style="color: #333; font-size: 15px;">Hola <strong>' . htmlspecialchars($usuario['nombres']) . '</strong>,</p>
                    <p style="color: #555; font-size: 14px;">Recibimos una solicitud para restablecer tu contrasena. Ingresa el siguiente codigo en la aplicacion para continuar:</p>
                    <div style="text-align: center; margin: 24px 0; padding: 16px; background: #e8f5e9; border-radius: 12px; border: 2px dashed #198754;">
                        <span style="font-size: 2.2rem; font-weight: 700; letter-spacing: 8px; color: #198754; font-family: monospace;">' . $codigo . '</span>
                    </div>
                    <p style="color: #888; font-size: 13px;">Este codigo es valido por <strong>15 minutos</strong>.</p>
                    <p style="color: #888; font-size: 13px;">Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                    <p style="color: #aaa; font-size: 11px; text-align: center;">EcoVision AI - Clasificacion de residuos con inteligencia artificial</p>
                </div>
            ';

            $resultado = enviarCorreo($correo, $asunto, $mensaje);
            if ($resultado['ok']) {
                $correo_enviado = $correo;
                $success = 'Hemos enviado un codigo de verificacion a <strong>' . htmlspecialchars($correo) . '</strong>. Revisa tu bandeja de entrada.';
            } else {
                $pdo->prepare("UPDATE reset_codigos SET usado = 1 WHERE id_usuario = ? AND codigo = ?")->execute([$usuario['id_usuario'], $codigo]);
                $error = 'No pudimos enviar el correo. Intenta nuevamente en unos minutos.';
            }
        } else {
            $error = 'No encontramos una cuenta con ese correo electronico.';
        }
    } else {
        $error = 'Ingresa tu correo electronico.';
    }
}

require_once 'includes/header.php';
?>
<style>
    body {
        background: linear-gradient(-45deg, #0a2213, #1a6e3e, #0d4f2b, #0f3823) !important;
        background-size: 400% 400% !important;
        animation: gradientShift 15s ease infinite !important;
        min-height: 100vh;
    }
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="bi bi-shield-lock"></i>
            <h2>Recuperar Contrasena</h2>
            <p>Ingresa tu correo para recibir un codigo de verificacion</p>
        </div>

        <?php if ($error): ?>
        <div class="auth-error-msg">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="auth-success-msg">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
        </div>

        <a href="reset_password.php?correo=<?php echo urlencode($correo_enviado); ?>" class="auth-btn d-block text-center text-decoration-none">
            <i class="bi bi-arrow-right me-2"></i>Ingresar codigo
        </a>

        <div class="text-center mt-3">
            <a href="forgot_password.php" class="auth-link">Reenviar codigo</a>
        </div>
        <?php else: ?>

        <form method="POST" action="" id="forgotForm" autocomplete="on">
            <div class="auth-input-group">
                <input type="email" name="correo" class="form-control" id="correo" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" required>
                <label for="correo" class="auth-floating-label">
                    <i class="bi bi-envelope me-1"></i>Correo Electronico
                </label>
            </div>

            <button type="submit" class="auth-btn" id="sendBtn">
                <span id="btnText"><i class="bi bi-send me-2"></i>Enviar codigo</span>
                <span id="btnSpinner" class="d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Enviando...
                </span>
            </button>
        </form>

        <div class="auth-footer-text mt-3">
            <a href="login.php" class="auth-link"><i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesion</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    'use strict';

    function initFloatingLabels() {
        var inputs = document.querySelectorAll('.auth-input-group .form-control');
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            input.setAttribute('placeholder', ' ');
            if (input.value.trim() !== '') {
                input.classList.add('filled');
            }
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.classList.add('filled');
                } else {
                    this.classList.remove('filled');
                }
            });
        }
    }

    var forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {
        forgotForm.addEventListener('submit', function() {
            var btn = document.getElementById('sendBtn');
            document.getElementById('btnText').classList.add('d-none');
            document.getElementById('btnSpinner').classList.remove('d-none');
            btn.disabled = true;
        });
    }

    initFloatingLabels();
})();
</script>
</body>
</html>
