<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/auth.php';
redireccionarSiAutenticado();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = limpiarDato($_POST['nombres']);
    $apellidos = limpiarDato($_POST['apellidos']);
    $correo = limpiarDato($_POST['correo']);
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar_password'];

    if (!empty($nombres) && !empty($apellidos) && !empty($correo) && !empty($password) && !empty($confirmar)) {
        if ($password !== $confirmar) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            if ($stmt->fetch()) {
                $error = 'El correo ya está registrado.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombres, apellidos, correo, password, rol) VALUES (?, ?, ?, ?, 'USUARIO')");
                $stmt->execute([$nombres, $apellidos, $correo, $hash]);
                $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
            }
        }
    } else {
        $error = 'Todos los campos son obligatorios.';
    }
}

$css_auth = true;
$css_extra = 'dashboard.css';
$titulo = 'Registrarse';
require_once 'includes/header.php';
?>
<style>
.auth-body {
    background: linear-gradient(135deg, #0f3823, #1a6e3e, #0f3823);
    background-size: 400% 400%;
    animation: gradientShift 12s ease infinite;
    min-height: 100vh;
}
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 32px;
}
.step-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.35);
    transition: color 0.4s ease;
}
.step-item.active { color: #4ade80; }
.step-item.done { color: #198754; }
.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 700;
    border: 2px solid currentColor;
    background: transparent;
    transition: all 0.4s ease;
    flex-shrink: 0;
}
.step-item.active .step-circle {
    background: #198754;
    border-color: #198754;
    color: #fff;
    box-shadow: 0 0 20px rgba(25,135,84,0.5);
}
.step-item.done .step-circle {
    background: #198754;
    border-color: #198754;
    color: #fff;
}
.step-label {
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
}
.step-connector {
    width: 48px;
    height: 2px;
    background: rgba(255,255,255,0.15);
    margin: 0 12px;
    position: relative;
    transition: background 0.4s ease;
}
.step-connector.done { background: #198754; }
.step-panel {
    animation: fadeInUp 0.5s cubic-bezier(0.4,0,0.2,1) both;
}
.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255,255,255,0.35);
    cursor: pointer;
    padding: 4px 8px;
    z-index: 5;
    transition: color 0.2s ease;
    font-size: 1.1rem;
}
.password-toggle:hover { color: #4ade80; }
.auth-input-group .form-control { padding-right: 44px; }
#strengthBar {
    transition: width 0.3s ease, background 0.3s ease;
    border-radius: 4px;
}
#matchLabel { font-size: 0.75rem; transition: color 0.3s ease; }
.auth-checkbox a { color: #4ade80; text-decoration: none; font-weight: 600; }
.auth-checkbox a:hover { color: #22c55e; text-decoration: underline; }
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 480px) {
    .step-label { display: none; }
    .step-connector { width: 28px; }
}
</style>
<div class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <i class="bi bi-recycle"></i>
                <h2>Crear Cuenta</h2>
                <p>Regístrate y empieza a clasificar residuos</p>
            </div>

            <div class="steps">
                <div class="step-item active" data-step="1">
                    <div class="step-circle">1</div>
                    <span class="step-label">Datos Personales</span>
                </div>
                <div class="step-connector" id="connector"></div>
                <div class="step-item" data-step="2">
                    <div class="step-circle">2</div>
                    <span class="step-label">Cuenta</span>
                </div>
            </div>

            <?php if ($error): ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'error', title: 'Error', text: '<?php echo addslashes($error); ?>', confirmButtonColor: '#dc3545', background: '#1a1a2e', color: '#fff', confirmButtonText: 'Entendido' });
            });
            </script>
            <?php endif; ?>
            <?php if ($success): ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'success', title: '¡Registro Exitoso!', text: '<?php echo addslashes($success); ?>', confirmButtonColor: '#198754', background: '#1a1a2e', color: '#fff', confirmButtonText: 'Iniciar Sesión' }).then(function() { window.location.href = 'login.php'; });
            });
            </script>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="" novalidate>
                <div class="step-panel" id="step1">
                    <div class="auth-input-group">
                        <input type="text" name="nombres" class="form-control" required>
                        <label class="auth-floating-label"><i class="bi bi-person me-1"></i>Nombres</label>
                    </div>
                    <div class="auth-input-group">
                        <input type="text" name="apellidos" class="form-control" required>
                        <label class="auth-floating-label"><i class="bi bi-person me-1"></i>Apellidos</label>
                    </div>
                    <div class="auth-input-group">
                        <input type="email" name="correo" class="form-control" required>
                        <label class="auth-floating-label"><i class="bi bi-envelope me-1"></i>Correo Electr&oacute;nico</label>
                    </div>
                    <button type="button" class="auth-btn" id="nextStepBtn">Siguiente <i class="bi bi-arrow-right ms-1"></i></button>
                </div>

                <div class="step-panel" id="step2" style="display:none;">
                    <div class="auth-input-group">
                        <input type="password" name="password" class="form-control" id="regPassword" required minlength="6">
                        <label class="auth-floating-label"><i class="bi bi-lock me-1"></i>Contrase&ntilde;a</label>
                        <button type="button" class="password-toggle" tabindex="-1" onclick="togglePass(this,'regPassword')"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="progress mb-1" style="height:6px;background:rgba(255,255,255,0.1);border-radius:4px;">
                        <div class="progress-bar" id="strengthBar" role="progressbar" style="width:0%;background:#6c757d;"></div>
                    </div>
                    <small style="display:block;font-size:0.75rem;color:rgba(255,255,255,0.4)!important;margin-bottom:20px;min-height:18px;" id="strengthLabel">Fortaleza: <span>Escribe una contrase&ntilde;a</span></small>

                    <div class="auth-input-group">
                        <input type="password" name="confirmar_password" class="form-control" id="regConfirm" required minlength="6">
                        <label class="auth-floating-label"><i class="bi bi-lock me-1"></i>Confirmar Contrase&ntilde;a</label>
                        <button type="button" class="password-toggle" tabindex="-1" onclick="togglePass(this,'regConfirm')"><i class="bi bi-eye"></i></button>
                    </div>
                    <small style="display:block;font-size:0.75rem;color:rgba(255,255,255,0.4)!important;min-height:18px;margin-bottom:16px;" id="matchLabel"></small>

                    <label class="auth-checkbox mb-4">
                        <input type="checkbox" name="terminos" id="termsCheck" required>
                        <span class="checkmark"><i class="bi bi-check"></i></span>
                        Acepto los <a href="#" onclick="event.preventDefault();Swal.fire({title:'T&eacute;rminos y Condiciones',text:'Al registrarte aceptas nuestras pol&iacute;ticas de privacidad y t&eacute;rminos de servicio.',icon:'info',confirmButtonColor:'#198754',background:'#1a1a2e',color:'#fff'});">T&eacute;rminos y Condiciones</a>
                    </label>

                    <button type="submit" class="auth-btn mb-2" id="submitBtn"><i class="bi bi-person-plus me-1"></i> Registrarse</button>
                    <button type="button" class="btn w-100" style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px;font-size:0.85rem;" id="prevStepBtn"><i class="bi bi-arrow-left me-1"></i> Volver atr&aacute;s</button>
                </div>
            </form>

            <p class="auth-footer-text">¿Ya tienes cuenta? <a href="login.php" class="auth-link-strong">Inicia sesión</a></p>
        </div>
    </div>
</div>

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

    var currentStep = 1;

    function updateSteps() {
        var items = document.querySelectorAll('.step-item');
        for (var i = 0; i < items.length; i++) {
            var el = items[i];
            var s = parseInt(el.getAttribute('data-step'));
            el.classList.toggle('active', s === currentStep);
            el.classList.toggle('done', s < currentStep);
        }
        document.getElementById('connector').classList.toggle('done', currentStep > 1);
        document.getElementById('step1').style.display = currentStep === 1 ? 'block' : 'none';
        document.getElementById('step2').style.display = currentStep === 2 ? 'block' : 'none';
        setTimeout(initFloatingLabels, 50);
    }

    function validateStep1() {
        var nombres = document.querySelector('input[name="nombres"]');
        var apellidos = document.querySelector('input[name="apellidos"]');
        var correo = document.querySelector('input[name="correo"]');
        if (!nombres.value.trim()) { shakeField(nombres); return false; }
        if (!apellidos.value.trim()) { shakeField(apellidos); return false; }
        if (!correo.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value.trim())) { shakeField(correo); return false; }
        return true;
    }

    function shakeField(el) {
        el.classList.add('is-invalid');
        el.style.animation = 'none';
        el.offsetHeight;
        el.style.animation = 'shakeAuth 0.4s ease';
        setTimeout(function() { el.classList.remove('is-invalid'); }, 1500);
        el.focus();
    }

    document.getElementById('nextStepBtn').addEventListener('click', function() {
        if (validateStep1()) { currentStep = 2; updateSteps(); }
    });

    document.getElementById('prevStepBtn').addEventListener('click', function() {
        currentStep = 1; updateSteps();
    });

    var password = document.getElementById('regPassword');
    var confirmP = document.getElementById('regConfirm');

    if (password && confirmP) {
        var strengthBar = document.getElementById('strengthBar');
        var strengthLabel = document.getElementById('strengthLabel').querySelector('span');
        var matchLabel = document.getElementById('matchLabel');

        password.addEventListener('input', updateStrength);
        confirmP.addEventListener('input', updateMatch);
        password.addEventListener('input', updateMatch);

        function updateStrength() {
            var val = password.value;
            var score = 0;
            if (val.length >= 6) score += 20;
            if (val.length >= 10) score += 15;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score += 20;
            if (/\d/.test(val)) score += 20;
            if (/[^a-zA-Z0-9]/.test(val)) score += 25;
            var pct = Math.min(score, 100);
            var color, label;
            if (score < 30) { color = '#dc3545'; label = 'Debil'; }
            else if (score < 60) { color = '#ffc107'; label = 'Media'; }
            else if (score < 80) { color = '#0dcaf0'; label = 'Buena'; }
            else { color = '#28a745'; label = 'Muy fuerte'; }
            strengthBar.style.width = pct + '%';
            strengthBar.style.background = color;
            strengthLabel.textContent = val.length > 0 ? label : 'Escribe una contrasena';
        }

        function updateMatch() {
            var p = password.value;
            var c = confirmP.value;
            if (c.length === 0) { matchLabel.textContent = ''; return; }
            if (p === c) { matchLabel.innerHTML = '<i class="bi bi-check-circle-fill me-1" style="color:#28a745;"></i><span style="color:#28a745;">Las contrasenas coinciden</span>'; }
            else { matchLabel.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1" style="color:#dc3545;"></i><span style="color:#dc3545;">Las contrasenas no coinciden</span>'; }
        }
    }

    document.getElementById('registerForm').addEventListener('submit', function(e) {
        var btn = document.getElementById('submitBtn');
        var p = password ? password.value : '';
        var c = confirmP ? confirmP.value : '';
        if (p.length < 6) { e.preventDefault(); if (password) shakeField(password); return; }
        if (p !== c) { e.preventDefault(); if (confirmP) shakeField(confirmP); return; }
        if (!document.getElementById('termsCheck').checked) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Terminos requeridos', text: 'Debes aceptar los terminos y condiciones.', confirmButtonColor: '#ffc107', background: '#1a1a2e', color: '#fff' });
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Registrando...';
    });

    function togglePass(btn, inputId) {
        var inp = document.getElementById(inputId);
        var isPassword = inp.type === 'password';
        inp.type = isPassword ? 'text' : 'password';
        btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    }
    window.togglePass = togglePass;

    initFloatingLabels();
})();
</script>

<?php require_once 'includes/footer.php'; ?>
