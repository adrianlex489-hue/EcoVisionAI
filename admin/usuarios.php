<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_estado'])) {
        $id = (int)$_POST['id_usuario'];
        $stmt = $pdo->prepare("SELECT estado FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            $nuevo_estado = $user['estado'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
            $stmt->execute([$nuevo_estado, $id]);
        }
    }

    if (isset($_POST['editar_usuario'])) {
        $id = (int)$_POST['id_usuario'];
        $nombres = limpiarDato($_POST['nombres']);
        $apellidos = limpiarDato($_POST['apellidos']);
        $correo = limpiarDato($_POST['correo']);
        $rol = limpiarDato($_POST['rol']);

        $stmt = $pdo->prepare("UPDATE usuarios SET nombres = ?, apellidos = ?, correo = ?, rol = ? WHERE id_usuario = ?");
        $stmt->execute([$nombres, $apellidos, $correo, $rol, $id]);
    }
}

$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC");
$usuarios = $stmt->fetchAll();

$titulo = 'Gestión de Usuarios';
$css_admin = true;
$js_admin = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Gestión de Usuarios</h5>
            <div class="d-flex align-items-center">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo $_SESSION['usuario_nombres']; ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="panel-card mb-4 anim-fade-in-up anim-delay-1">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="tableSearch" class="form-control border-start-0 ps-0" placeholder="Buscar usuarios por nombre, correo o rol..." style="border-radius:0 8px 8px 0;">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="roleFilter" class="form-select" onchange="filtrarPorRol(this.value)">
                            <option value="">Todos los roles</option>
                            <option value="ADMIN">ADMIN</option>
                            <option value="USUARIO">USUARIO</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button class="btn btn-outline-success btn-sm" onclick="document.getElementById('selectAll').click()">
                            <i class="bi bi-check-all me-1"></i>Seleccionar Todos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-card anim-fade-in-up anim-delay-2">
            <div class="card-body p-0">
                <div class="table-responsive-admin">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr class="user-row">
                                <td><input type="checkbox" class="form-check-input batch-item" value="<?php echo $u['id_usuario']; ?>"></td>
                                <td><span class="fw-semibold">#<?php echo $u['id_usuario']; ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-<?php echo $u['rol'] === 'ADMIN' ? 'danger' : 'primary'; ?> bg-opacity-10 text-<?php echo $u['rol'] === 'ADMIN' ? 'danger' : 'primary'; ?> d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:0.8rem;">
                                            <?php echo strtoupper(substr($u['nombres'], 0, 1) . substr($u['apellidos'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo $u['nombres'] . ' ' . $u['apellidos']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-muted"><?php echo $u['correo']; ?></span></td>
                                <td>
                                    <span class="admin-badge-role badge bg-<?php echo $u['rol'] === 'ADMIN' ? 'danger' : 'primary'; ?>">
                                        <i class="bi bi-<?php echo $u['rol'] === 'ADMIN' ? 'shield-lock' : 'person'; ?> me-1"></i>
                                        <?php echo $u['rol']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="estado-dot <?php echo $u['estado'] ? 'activo' : 'inactivo'; ?>">
                                        <?php echo $u['estado'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td><span class="text-muted small"><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></span></td>
                                <td>
                                    <div class="accion-grupo">
                                        <button class="accion-btn accion-btn-editar" onclick="editarUsuario(<?php echo $u['id_usuario']; ?>, '<?php echo addslashes($u['nombres']); ?>', '<?php echo addslashes($u['apellidos']); ?>', '<?php echo $u['correo']; ?>', '<?php echo $u['rol']; ?>')" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" data-toggle="user-status" style="display:inline;">
                                            <input type="hidden" name="toggle_estado" value="1">
                                            <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                                            <button type="submit" class="accion-btn <?php echo $u['estado'] ? 'accion-btn-desactivar' : 'accion-btn-activar'; ?>" data-estado="<?php echo $u['estado']; ?>" title="<?php echo $u['estado'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="bi bi-<?php echo $u['estado'] ? 'pause-circle' : 'play-circle'; ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 px-3 py-2 text-muted small">
                <i class="bi bi-info-circle me-1"></i>
                Mostrando <?php echo count($usuarios); ?> usuario(s)
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-admin" id="editarUsuarioModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2 text-success"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="editar_usuario" value="1">
                    <input type="hidden" name="id_usuario" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="bi bi-person me-1 text-success"></i>Nombres</label>
                        <input type="text" id="edit_nombres" name="nombres" class="form-control form-control-lg" required placeholder="Ingrese los nombres">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="bi bi-person me-1 text-success"></i>Apellidos</label>
                        <input type="text" id="edit_apellidos" name="apellidos" class="form-control form-control-lg" required placeholder="Ingrese los apellidos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="bi bi-envelope me-1 text-success"></i>Correo Electrónico</label>
                        <input type="email" id="edit_correo" name="correo" class="form-control form-control-lg" required placeholder="correo@ejemplo.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="bi bi-shield me-1 text-success"></i>Rol</label>
                        <select name="rol" id="edit_rol" class="form-select form-select-lg">
                            <option value="USUARIO">USUARIO</option>
                            <option value="ADMIN">ADMIN</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-lg px-4"><i class="bi bi-check-lg me-1"></i>Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarUsuario(id, nombres, apellidos, correo, rol) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombres').value = nombres;
    document.getElementById('edit_apellidos').value = apellidos;
    document.getElementById('edit_correo').value = correo;
    document.getElementById('edit_rol').value = rol;
    new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
}

function filtrarPorRol(rol) {
    var rows = document.querySelectorAll('.user-row');
    var searchVal = document.getElementById('tableSearch').value.toLowerCase();
    rows.forEach(function(row) {
        var roleCell = row.querySelector('td:nth-child(5)').textContent.trim();
        var text = row.textContent.toLowerCase();
        var matchRole = !rol || roleCell === rol;
        var matchSearch = text.indexOf(searchVal) > -1;
        row.style.display = matchRole && matchSearch ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var rol = document.getElementById('roleFilter').value;
            filtrarPorRol(rol);
        });
    }
});
</script>
<?php include_once '../includes/footer.php'; ?>
