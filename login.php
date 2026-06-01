<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/auth.php';
redireccionarSiAutenticado();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = limpiarDato($_POST['correo']);
    $password = $_POST['password'];

    if (!empty($correo) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            if ($usuario['estado'] == 1) {
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_nombres'] = $usuario['nombres'];
                $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
                $_SESSION['usuario_correo'] = $usuario['correo'];
                $_SESSION['usuario_rol'] = $usuario['rol'];
                $destino = $usuario['rol'] === 'ADMIN' ? '/admin/dashboard.php' : '/dashboard/dashboard.php';
                header('Location: ' . BASE_URL . $destino);
                exit;
            } else {
                $error = 'Tu cuenta está desactivada. Contacta al administrador.';
            }
        } else {
            $error = 'Credenciales incorrectas.';
        }
    } else {
        $error = 'Todos los campos son obligatorios.';
    }
}

$css_auth = true;
$css_extra = 'dashboard.css';
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
            <i class="bi bi-recycle"></i>
            <h2>EcoVision AI</h2>
            <p>Inicia sesi&oacute;n para continuar</p>
        </div>

        <?php if ($error): ?>
        <div class="auth-error-msg" id="errorMsg">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" autocomplete="on">
            <div class="auth-input-group">
                <input type="email" name="correo" class="form-control" id="correo" required>
                <label for="correo" class="auth-floating-label">
                    <i class="bi bi-envelope me-1"></i>Correo Electr&oacute;nico
                </label>
            </div>

            <div class="auth-input-group">
                <input type="password" name="password" class="form-control" id="password" required>
                <label for="password" class="auth-floating-label">
                    <i class="bi bi-lock me-1"></i>Contrase&ntilde;a
                </label>
                <span class="input-icon" id="togglePassword" style="cursor:pointer;pointer-events:auto">
                    <i class="bi bi-eye-slash"></i>
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <label class="auth-checkbox">
                    <input type="checkbox" name="recordar" id="recordar">
                    <span class="checkmark"><i class="bi bi-check"></i></span>
                    Recordarme
                </label>
                <a href="forgot_password.php" class="auth-forgot-link">&iquest;Olvidaste tu contrase&ntilde;a?</a>
            </div>

            <button type="submit" class="auth-btn" id="loginBtn">
                <span id="btnText"><i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesi&oacute;n</span>
                <span id="btnSpinner" class="d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Ingresando...
                </span>
            </button>
        </form>

        <div class="auth-footer-text">
            &iquest;No tienes cuenta? <a href="register.php" class="auth-link-strong">Reg&iacute;strate aqu&iacute;</a>
        </div>
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

    var toggle = document.getElementById('togglePassword');
    var password = document.getElementById('password');
    if (toggle && password) {
        toggle.addEventListener('click', function() {
            var isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            this.querySelector('i').className = isPassword ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }

    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            var btn = document.getElementById('loginBtn');
            document.getElementById('btnText').classList.add('d-none');
            document.getElementById('btnSpinner').classList.remove('d-none');
            btn.disabled = true;
        });
    }

    <?php if ($error): ?>
    var errorMsg = document.getElementById('errorMsg');
    if (errorMsg) {
        errorMsg.addEventListener('animationend', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de inicio de sesi\u00f3n',
                text: <?php echo json_encode($error); ?>,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Entendido',
                background: '#1a1a2e',
                color: '#fff',
                backdrop: 'rgba(0,0,0,0.6)',
                timer: 6000,
                timerProgressBar: true
            });
        });
    }
    <?php endif; ?>

    initFloatingLabels();
})();
</script>
</body>
</html>
