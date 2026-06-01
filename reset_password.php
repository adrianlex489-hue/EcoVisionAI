<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/auth.php';
redireccionarSiAutenticado();

$css_auth = true;
$error = '';
$success = '';
$correo = $_GET['correo'] ?? ($_POST['correo'] ?? '');
$paso = 1;
$id_reset = null;

if ($correo) {
    $stmt = $pdo->prepare("SELECT u.id_usuario FROM usuarios u WHERE u.correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['codigo']) && !isset($_POST['password'])) {
                $codigo = limpiarDato($_POST['codigo']);
                $stmt = $pdo->prepare("SELECT id FROM reset_codigos WHERE id_usuario = ? AND codigo = ? AND usado = 0 AND expira > NOW() ORDER BY id DESC LIMIT 1");
                $stmt->execute([$usuario['id_usuario'], $codigo]);
                $reset = $stmt->fetch();

                if ($reset) {
                    $_SESSION['reset_id'] = $reset['id'];
                    $_SESSION['reset_codigo'] = $codigo;
                    $paso = 2;
                } else {
                    $error = 'Codigo invalido o expirado. Solicita uno nuevo.';
                }
            } elseif (isset($_POST['password'])) {
                $password = $_POST['password'];
                $password2 = $_POST['password2'];

                if (!isset($_SESSION['reset_id']) || !isset($_SESSION['reset_codigo'])) {
                    $error = 'Debes verificar el codigo primero.';
                } elseif ($password !== $password2) {
                    $error = 'Las contrasenas no coinciden.';
                } elseif (strlen($password) < 6) {
                    $error = 'La contrasena debe tener al menos 6 caracteres.';
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM reset_codigos WHERE id = ? AND codigo = ? AND usado = 0 AND expira > NOW()");
                    $stmt->execute([$_SESSION['reset_id'], $_SESSION['reset_codigo']]);
                    $reset = $stmt->fetch();

                    if ($reset) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?")->execute([$hash, $usuario['id_usuario']]);
                        $pdo->prepare("UPDATE reset_codigos SET usado = 1 WHERE id = ?")->execute([$reset['id']]);
                        unset($_SESSION['reset_id'], $_SESSION['reset_codigo']);
                        $success = 'Contrasena actualizada correctamente.';
                    } else {
                        $error = 'Codigo invalido o expirado. Solicita uno nuevo.';
                    }
                }
            }
        } else {
            $paso = 1;
        }
    } else {
        $error = 'Correo no encontrado. Solicita un nuevo codigo.';
    }
} else {
    $error = 'No se proporciono un correo.';
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
    .code-digit-box {
        width: 56px;
        height: 64px;
        text-align: center;
        font-size: 1.6rem;
        font-weight: 700;
        font-family: 'Courier New', monospace;
        border: 2px solid rgba(255,255,255,0.2);
        border-radius: 12px;
        background: rgba(255,255,255,0.08);
        color: #fff;
        outline: none;
        transition: border-color 0.2s ease;
    }
    .code-digit-box:focus {
        border-color: #198754;
        box-shadow: 0 0 0 3px rgba(25,135,84,0.25);
    }
    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 24px;
    }
    .step-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        transition: all 0.3s ease;
    }
    .step-dot.active {
        background: #198754;
        width: 28px;
        border-radius: 5px;
    }
    .step-dot.done {
        background: #4ade80;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <?php if ($success): ?>
        <div class="auth-logo">
            <i class="bi bi-check-circle" style="color: #4ade80;"></i>
            <h2>Contrasena actualizada</h2>
            <p>Tu contrasena se actualizo correctamente.</p>
        </div>
        <a href="login.php" class="auth-btn d-block text-center text-decoration-none">
            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesion
        </a>
        <?php elseif ($error && !$correo): ?>
        <div class="auth-logo">
            <i class="bi bi-shield-lock"></i>
            <h2>Restablecer Contrasena</h2>
        </div>
        <div class="auth-error-msg">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
        <div class="auth-footer-text">
            <a href="forgot_password.php" class="auth-link-strong">Solicitar nuevo codigo</a>
        </div>
        <?php else: ?>

        <div class="auth-logo">
            <i class="bi bi-shield-lock"></i>
            <h2><?php echo $paso === 1 ? 'Verificar codigo' : 'Nueva contrasena'; ?></h2>
            <p><?php echo $paso === 1 ? 'Ingresa el codigo que enviamos a tu correo' : 'Escribe tu nueva contrasena'; ?></p>
        </div>

        <div class="step-indicator">
            <span class="step-dot <?php echo $paso >= 1 ? 'done' : ''; ?>"></span>
            <span class="step-dot <?php echo $paso === 2 ? 'active' : ($paso > 2 ? 'done' : ''); ?>"></span>
        </div>

        <?php if ($error): ?>
        <div class="auth-error-msg">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if ($paso === 1): ?>
        <form method="POST" action="" id="codeForm" autocomplete="off">
            <input type="hidden" name="correo" value="<?php echo htmlspecialchars($correo); ?>">

            <div class="d-flex gap-2 justify-content-center mb-3" id="codeInputs">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" name="digito_<?php echo $i; ?>" class="code-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" required autocomplete="off">
                <?php endfor; ?>
            </div>
            <input type="hidden" name="codigo" id="codigoCompleto">

            <button type="submit" class="auth-btn">
                <i class="bi bi-check-circle me-2"></i>Verificar codigo
            </button>
        </form>

        <div class="auth-footer-text mt-3">
            <a href="forgot_password.php" class="auth-link">Reenviar codigo</a>
        </div>

        <?php elseif ($paso === 2): ?>
        <form method="POST" action="" id="passwordForm" autocomplete="on">
            <input type="hidden" name="correo" value="<?php echo htmlspecialchars($correo); ?>">

            <div class="auth-input-group">
                <input type="password" name="password" class="form-control" id="password" minlength="6" required>
                <label for="password" class="auth-floating-label">
                    <i class="bi bi-lock me-1"></i>Nueva contrasena
                </label>
                <span class="input-icon" id="togglePassword" style="cursor:pointer;pointer-events:auto">
                    <i class="bi bi-eye-slash"></i>
                </span>
            </div>

            <div class="auth-input-group">
                <input type="password" name="password2" class="form-control" id="password2" minlength="6" required>
                <label for="password2" class="auth-floating-label">
                    <i class="bi bi-lock me-1"></i>Confirmar contrasena
                </label>
            </div>

            <button type="submit" class="auth-btn" id="resetBtn">
                <span id="btnText"><i class="bi bi-check-circle me-2"></i>Restablecer</span>
                <span id="btnSpinner" class="d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Procesando...
                </span>
            </button>
        </form>

        <div class="auth-footer-text mt-3">
            <a href="forgot_password.php" class="auth-link">Solicitar nuevo codigo</a>
        </div>
        <?php endif; ?>

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

    var toggle = document.getElementById('togglePassword');
    var password = document.getElementById('password');
    if (toggle && password) {
        toggle.addEventListener('click', function() {
            var isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            this.querySelector('i').className = isPassword ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }

    var codeDigits = document.querySelectorAll('.code-digit-box');
    if (codeDigits.length) {
        for (var i = 0; i < codeDigits.length; i++) {
            (function(idx, input) {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value && idx < codeDigits.length - 1) {
                        codeDigits[idx + 1].focus();
                    }
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && idx > 0) {
                        codeDigits[idx - 1].focus();
                    }
                });
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    var paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                    for (var j = 0; j < paste.length && idx + j < codeDigits.length; j++) {
                        codeDigits[idx + j].value = paste[j];
                    }
                    var next = Math.min(idx + paste.length, codeDigits.length - 1);
                    codeDigits[next].focus();
                });
            })(i, codeDigits[i]);
        }
    }

    var codeForm = document.getElementById('codeForm');
    if (codeForm) {
        codeForm.addEventListener('submit', function() {
            var full = '';
            for (var i = 0; i < codeDigits.length; i++) {
                full += codeDigits[i].value || '';
            }
            document.getElementById('codigoCompleto').value = full;
        });
    }

    var passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function() {
            var btn = document.getElementById('resetBtn');
            document.getElementById('btnText').classList.add('d-none');
            document.getElementById('btnSpinner').classList.remove('d-none');
            btn.disabled = true;
        });
    }

    initFloatingLabels();

    if (codeDigits.length) {
        codeDigits[0].focus();
    }
})();
</script>
</body>
</html>
