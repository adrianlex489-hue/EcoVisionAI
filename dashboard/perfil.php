<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAuth();

$id_usuario = $_SESSION['usuario_id'];
$mensaje = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $nombres = limpiarDato($_POST['nombres']);
        $apellidos = limpiarDato($_POST['apellidos']);
        $correo = limpiarDato($_POST['correo']);

        if (!empty($nombres) && !empty($apellidos) && !empty($correo)) {
            $check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ?");
            $check->execute([$correo, $id_usuario]);
            if ($check->fetch()) {
                $error = 'El correo ya está en uso por otro usuario.';
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombres = ?, apellidos = ?, correo = ? WHERE id_usuario = ?");
                $stmt->execute([$nombres, $apellidos, $correo, $id_usuario]);
                $_SESSION['usuario_nombres'] = $nombres;
                $_SESSION['usuario_apellidos'] = $apellidos;
                $_SESSION['usuario_correo'] = $correo;
                $mensaje = 'Datos actualizados correctamente.';
                $usuario['nombres'] = $nombres;
                $usuario['apellidos'] = $apellidos;
                $usuario['correo'] = $correo;
            }
        } else {
            $error = 'Todos los campos son obligatorios.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $actual = $_POST['password_actual'];
        $nueva = $_POST['password_nueva'];
        $confirmar = $_POST['password_confirmar'];

        if (!empty($actual) && !empty($nueva) && !empty($confirmar)) {
            if (password_verify($actual, $usuario['password'])) {
                if ($nueva === $confirmar) {
                    if (strlen($nueva) >= 6) {
                        $hash = password_hash($nueva, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
                        $stmt->execute([$hash, $id_usuario]);
                        $mensaje = 'Contraseña cambiada correctamente.';
                    } else {
                        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
                    }
                } else {
                    $error = 'Las contraseñas nuevas no coinciden.';
                }
            } else {
                $error = 'La contraseña actual no es correcta.';
            }
        } else {
            $error = 'Todos los campos son obligatorios.';
        }
    }
}

$titulo = 'Perfil';
$css_extra = 'dashboard.css';
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <h5 class="mb-0"><i class="bi bi-person me-2"></i>Mi Perfil</h5>
            <div class="d-flex align-items-center">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo $_SESSION['usuario_nombres']; ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm text-center anim-fade-in-up card-hover">
                    <div class="card-body p-4">
                        <div class="avatar-placeholder mx-auto mb-3">
                            <span class="avatar-initials fs-1 fw-bold text-white"><?php echo strtoupper(substr($usuario['nombres'], 0, 1) . substr($usuario['apellidos'], 0, 1)); ?></span>
                        </div>
                        <h5 class="mb-1"><?php echo $usuario['nombres'] . ' ' . $usuario['apellidos']; ?></h5>
                        <p class="text-muted small mb-3"><i class="bi bi-envelope me-1"></i><?php echo $usuario['correo']; ?></p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                                <i class="bi bi-person-badge me-1"></i>Usuario
                            </span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                                <i class="bi bi-calendar me-1"></i><?php echo date('Y', strtotime($usuario['fecha_registro'] ?? 'now')); ?>
                            </span>
                        </div>
                        <hr class="my-3">
                        <div class="text-start small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Miembro desde:</span>
                                <span class="fw-medium"><?php echo isset($usuario['fecha_registro']) ? date('d/m/Y', strtotime($usuario['fecha_registro'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <ul class="nav nav-tabs mb-4 border-0" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2 border-0" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                            <i class="bi bi-pencil-square"></i> Información Personal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2 border-0" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                            <i class="bi bi-shield-lock"></i> Cambiar Contraseña
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    <div class="tab-pane fade show active" id="info" role="tabpanel">
                        <div class="card border-0 shadow-sm anim-fade-in-up">
                            <div class="card-header bg-white border-0 d-flex align-items-center py-3">
                                <i class="bi bi-pencil me-2 text-success"></i>
                                <h6 class="mb-0">Editar Información</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="profileForm">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Nombres</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                            <input type="text" name="nombres" class="form-control border-start-0" value="<?php echo $usuario['nombres']; ?>" required>
                                        </div>
                                        <div class="invalid-feedback">El nombre es obligatorio.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Apellidos</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-people text-muted"></i></span>
                                            <input type="text" name="apellidos" class="form-control border-start-0" value="<?php echo $usuario['apellidos']; ?>" required>
                                        </div>
                                        <div class="invalid-feedback">Los apellidos son obligatorios.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Correo Electrónico</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                            <input type="email" name="correo" class="form-control border-start-0" value="<?php echo $usuario['correo']; ?>" required>
                                        </div>
                                        <div class="invalid-feedback">Ingresa un correo válido.</div>
                                    </div>
                                    <button type="submit" class="btn btn-success" id="saveProfileBtn">
                                        <i class="bi bi-save me-1"></i>Guardar Cambios
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="password" role="tabpanel">
                        <div class="card border-0 shadow-sm anim-fade-in-up">
                            <div class="card-header bg-white border-0 d-flex align-items-center py-3">
                                <i class="bi bi-shield-lock me-2 text-warning"></i>
                                <h6 class="mb-0">Cambiar Contraseña</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="passwordForm">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Contraseña Actual</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                            <input type="password" name="password_actual" class="form-control border-start-0" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Nueva Contraseña</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                                            <input type="password" name="password_nueva" class="form-control border-start-0" required minlength="6" id="newPassword">
                                        </div>
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span class="text-muted">Seguridad de contraseña</span>
                                                <span id="strengthLabel" class="fw-medium">Muy débil</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div id="strengthBar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                            </div>
                                            <small class="text-muted">Mínimo 6 caracteres</small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Confirmar Nueva Contraseña</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-check-circle text-muted"></i></span>
                                            <input type="password" name="password_confirmar" class="form-control border-start-0" required minlength="6" id="confirmPassword">
                                        </div>
                                        <div id="passwordMatch" class="small mt-1 d-none">
                                            <i class="bi bi-check-circle-fill text-success me-1"></i>Las contraseñas coinciden
                                        </div>
                                        <div id="passwordMismatch" class="small mt-1 d-none">
                                            <i class="bi bi-exclamation-circle-fill text-danger me-1"></i>Las contraseñas no coinciden
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-warning" id="changePwdBtn">
                                        <i class="bi bi-key me-1"></i>Cambiar Contraseña
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #198754, #0d6e3e);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(25,135,84,0.3);
}

.nav-tabs .nav-link {
    border-radius: 10px 10px 0 0;
    padding: 12px 20px;
    color: #6c757d;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link:not(.active):hover {
    background-color: #f0faf4;
    color: #198754;
}

.nav-tabs .nav-link.active {
    color: #198754;
    background-color: #fff;
    box-shadow: 0 -2px 0 #198754 inset;
}

.input-group-text {
    border-radius: 10px 0 0 10px;
}

.form-control.border-start-0 {
    border-left: none;
    border-radius: 0 10px 10px 0;
}

.form-control.border-start-0:focus {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
}

.input-group:focus-within .input-group-text {
    border-color: #198754;
}

@media (max-width: 768px) {
    .nav-tabs .nav-link {
        padding: 10px 14px;
        font-size: 0.875rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($mensaje): ?>
    Swal.fire({
        icon: 'success',
        title: '¡Actualizado!',
        text: '<?php echo $mensaje; ?>',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
    });
    <?php endif; ?>

    <?php if ($error): ?>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $error; ?>',
        toast: false,
        confirmButtonColor: '#198754'
    });
    <?php endif; ?>

    const newPwd = document.getElementById('newPassword');
    const confirmPwd = document.getElementById('confirmPassword');
    const strengthBar = document.getElementById('strengthBar');
    const strengthLabel = document.getElementById('strengthLabel');
    const matchDiv = document.getElementById('passwordMatch');
    const mismatchDiv = document.getElementById('passwordMismatch');

    function checkStrength(password) {
        let score = 0;
        if (password.length >= 6) score += 25;
        if (password.length >= 8) score += 15;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 20;
        if (/\d/.test(password)) score += 20;
        if (/[^a-zA-Z0-9]/.test(password)) score += 20;
        return Math.min(100, score);
    }

    function updateStrength() {
        const score = checkStrength(newPwd.value);
        strengthBar.style.width = score + '%';
        strengthBar.style.transition = 'width 0.3s ease';

        if (score < 25) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthLabel.textContent = 'Muy débil';
        } else if (score < 50) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthLabel.textContent = 'Débil';
        } else if (score < 75) {
            strengthBar.className = 'progress-bar bg-info';
            strengthLabel.textContent = 'Media';
        } else if (score < 90) {
            strengthBar.className = 'progress-bar bg-primary';
            strengthLabel.textContent = 'Fuerte';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthLabel.textContent = 'Muy fuerte';
        }
    }

    function checkMatch() {
        if (!confirmPwd.value) {
            matchDiv.classList.add('d-none');
            mismatchDiv.classList.add('d-none');
            return;
        }
        if (newPwd.value === confirmPwd.value) {
            matchDiv.classList.remove('d-none');
            mismatchDiv.classList.add('d-none');
        } else {
            matchDiv.classList.add('d-none');
            mismatchDiv.classList.remove('d-none');
        }
    }

    newPwd.addEventListener('input', function() {
        updateStrength();
        checkMatch();
    });
    confirmPwd.addEventListener('input', checkMatch);

    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const nombres = this.querySelector('[name="nombres"]');
        const apellidos = this.querySelector('[name="apellidos"]');
        const correo = this.querySelector('[name="correo"]');
        let valid = true;

        [nombres, apellidos, correo].forEach(function(el) {
            el.classList.remove('is-invalid');
            if (!el.value.trim()) {
                el.classList.add('is-invalid');
                valid = false;
            }
        });

        if (!valid) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Todos los campos son obligatorios.',
                confirmButtonColor: '#198754'
            });
        }
    });

    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const actual = this.querySelector('[name="password_actual"]');
        const nueva = this.querySelector('[name="password_nueva"]');
        const confirmar = this.querySelector('[name="password_confirmar"]');

        if (!actual.value || !nueva.value || !confirmar.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Todos los campos son obligatorios.',
                confirmButtonColor: '#198754'
            });
            return;
        }

        if (nueva.value !== confirmar.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas nuevas no coinciden.',
                confirmButtonColor: '#198754'
            });
            return;
        }

        if (nueva.value.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La nueva contraseña debe tener al menos 6 caracteres.',
                confirmButtonColor: '#198754'
            });
        }
    });
});
</script>
<?php include_once '../includes/footer.php'; ?>
