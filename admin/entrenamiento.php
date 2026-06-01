<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';
requerirAdmin();

$training_dir = __DIR__ . '/../api_ai/training_data';
$python_exe   = 'C:\\Users\\ADRIA\\AppData\\Local\\Programs\\Python\\Python312\\python.exe';

$categorias = [
    'Plastico'       => 'Plástico',
    'Papel_y_carton' => 'Papel y Cartón',
    'Vidrio'         => 'Vidrio',
    'Metal'          => 'Metal',
    'Organico'       => 'Orgánico',
];

// ── Helpers ────────────────────────────────────────────────────────────────
function contarImagenes(string $dir): int {
    if (!is_dir($dir)) return 0;
    return count(array_filter(
        array_diff(scandir($dir), ['.', '..']),
        fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp'])
    ));
}

function recalcularConteos(array $categorias, string $training_dir): array {
    $c = [];
    foreach ($categorias as $folder => $_) {
        $c[$folder] = contarImagenes($training_dir . '/' . $folder);
    }
    return $c;
}

/**
 * Recommend epochs based on total images.
 * Returns ['epochs'=>int, 'nivel'=>string, 'descripcion'=>string]
 */
function recomendarEpocas(int $total_imgs): array {
    if ($total_imgs < 20)       return ['epochs'=>10,  'nivel'=>'basico',   'descripcion'=>'Pocas imágenes — épocas reducidas para evitar sobreajuste'];
    if ($total_imgs < 50)       return ['epochs'=>20,  'nivel'=>'bajo',     'descripcion'=>'Conjunto pequeño — entrenamiento moderado'];
    if ($total_imgs < 150)      return ['epochs'=>30,  'nivel'=>'medio',    'descripcion'=>'Conjunto mediano — buen balance velocidad/precisión'];
    if ($total_imgs < 500)      return ['epochs'=>40,  'nivel'=>'alto',     'descripcion'=>'Conjunto grande — entrenamiento completo'];
    return                             ['epochs'=>50,  'nivel'=>'experto',  'descripcion'=>'Conjunto extenso — máxima precisión'];
}

// ── Conteos iniciales ──────────────────────────────────────────────────────
$conteos       = recalcularConteos($categorias, $training_dir);
$total_imgs    = array_sum($conteos);
$recomendacion = recomendarEpocas($total_imgs);

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ── upload_images ──────────────────────────────────────────────────────
    if ($_POST['action'] === 'upload_images') {
        $is_ajax   = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
        $categoria = $_POST['categoria'] ?? '';
        if (!array_key_exists($categoria, $categorias)) {
            if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Categoría no válida.']); exit; }
        } else {
            $target = $training_dir . '/' . $categoria;
            if (!is_dir($target)) mkdir($target, 0777, true);
            $uploaded = 0;
            if (isset($_FILES['images'])) {
                $f = $_FILES['images'];
                for ($i = 0; $i < count($f['name']); $i++) {
                    if ($f['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($f['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                            move_uploaded_file($f['tmp_name'][$i], $target . '/' . uniqid($categoria.'_') . '.' . $ext);
                            $uploaded++;
                        }
                    }
                }
            }
            if ($is_ajax) {
                $nc = recalcularConteos($categorias, $training_dir);
                $nt = array_sum($nc);
                $rec = recomendarEpocas($nt);
                header('Content-Type: application/json');
                echo json_encode($uploaded > 0
                    ? ['success'=>true,'message'=>"Se subieron $uploaded imagen(es) a {$categorias[$categoria]}.","uploaded"=>$uploaded,"categoria"=>$categoria,"conteos"=>$nc,"total"=>$nt,"recomendacion"=>$rec]
                    : ['success'=>false,'message'=>'No se pudo subir ninguna imagen.']);
                exit;
            }
        }
    }

    // ── delete_category_images ─────────────────────────────────────────────
    if ($_POST['action'] === 'delete_category_images') {
        header('Content-Type: application/json');
        $categoria = $_POST['categoria'] ?? '';
        if (!array_key_exists($categoria, $categorias)) { echo json_encode(['success'=>false,'message'=>'Categoría no válida.']); exit; }
        $cat_path = $training_dir . '/' . $categoria;
        $deleted  = 0;
        if (is_dir($cat_path)) {
            foreach (array_diff(scandir($cat_path), ['.','..']) as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) { unlink($cat_path.'/'.$file); $deleted++; }
            }
        }
        $nc  = recalcularConteos($categorias, $training_dir);
        $nt  = array_sum($nc);
        $rec = recomendarEpocas($nt);
        echo json_encode(['success'=>true,'message'=>"Se eliminaron $deleted imagen(es) de {$categorias[$categoria]}.","deleted"=>$deleted,"conteos"=>$nc,"total"=>$nt,"recomendacion"=>$rec]);
        exit;
    }

    // ── check_env ──────────────────────────────────────────────────────────
    if ($_POST['action'] === 'check_env') {
        header('Content-Type: application/json');
        $api_dir = realpath(__DIR__ . '/../api_ai');
        $py_ver  = shell_exec("\"$python_exe\" --version 2>&1");
        $tf_test = shell_exec("\"$python_exe\" -c \"import tf_keras; print(tf_keras.__version__)\" 2>&1");
        echo json_encode(['success'=>true,'checks'=>[
            'python'        => ['ok'=>(bool)$py_ver && stripos($py_ver,'python')!==false, 'value'=>$py_ver ? trim($py_ver).' ('.$python_exe.')' : 'No encontrado'],
            'model'         => ['ok'=>file_exists($api_dir.'/keras_model.h5'), 'value'=>file_exists($api_dir.'/keras_model.h5') ? 'keras_model.h5 ('.round(filesize($api_dir.'/keras_model.h5')/1024/1024,2).' MB)' : 'No encontrado'],
            'training_data' => ['ok'=>is_dir($api_dir.'/training_data'), 'value'=>is_dir($api_dir.'/training_data') ? 'Directorio existe' : 'No encontrado'],
            'tf_keras'      => ['ok'=>$tf_test && stripos($tf_test,'No module')===false, 'value'=>$tf_test ? trim($tf_test) : 'No instalado'],
            'php_timeout'   => ['ok'=>true, 'value'=>ini_get('max_execution_time').'s (anulado con set_time_limit(0))'],
            'upload_max'    => ['ok'=>true, 'value'=>ini_get('upload_max_filesize')],
        ]]);
        exit;
    }

    // ── retrain ────────────────────────────────────────────────────────────
    if ($_POST['action'] === 'retrain') {
        header('Content-Type: application/json');
        set_time_limit(0);
        ignore_user_abort(true);

        $api_dir = realpath(__DIR__ . '/../api_ai');
        $nc      = recalcularConteos($categorias, $training_dir);
        $nt      = array_sum($nc);

        // Validaciones previas
        $cats_ok = count(array_filter($nc, fn($v) => $v > 0));
        if ($cats_ok < 2) { echo json_encode(['success'=>false,'message'=>"Necesitas imágenes en al menos 2 categorías (tienes $cats_ok)."]); exit; }
        if ($nt < 10)     { echo json_encode(['success'=>false,'message'=>"Necesitas al menos 10 imágenes en total (tienes $nt)."]); exit; }
        if (!file_exists($api_dir.'/keras_model.h5')) { echo json_encode(['success'=>false,'message'=>'No se encontró keras_model.h5.']); exit; }

        // Épocas: usar recomendación automática, ignorar lo que mande el usuario
        $rec    = recomendarEpocas($nt);
        $epochs = $rec['epochs'];

        // Verificar Python
        $py_ver = shell_exec("\"$python_exe\" --version 2>&1");
        if (!$py_ver || stripos($py_ver,'python') === false) {
            echo json_encode(['success'=>false,'message'=>'Python no encontrado en: '.$python_exe]);
            exit;
        }

        // Registrar inicio en BD
        $id_admin = $_SESSION['usuario_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO entrenamientos (epocas_solicitadas, total_imagenes, estado, id_admin) VALUES (?,?,?,?)");
        $stmt->execute([$epochs, $nt, 'en_proceso', $id_admin]);
        $id_entrenamiento = $pdo->lastInsertId();

        // Ejecutar Python
        $script = $api_dir . '/retrain.py';
        $cmd    = "set PYTHONIOENCODING=utf-8 && \"$python_exe\" \"$script\" --epochs $epochs 2>&1";
        $output = shell_exec($cmd);

        if (!mb_check_encoding($output ?? '', 'UTF-8')) {
            $output = mb_convert_encoding($output ?? '', 'UTF-8', 'CP1252');
        }

        // Extraer JSON del output
        $json_str   = '';
        $last_brace = strrpos($output ?? '', '}');
        if ($last_brace !== false) {
            $depth = 0;
            for ($i = $last_brace; $i >= 0; $i--) {
                if ($output[$i] === '}') $depth++;
                if ($output[$i] === '{') $depth--;
                if ($depth === 0) { $json_str = substr($output, $i, $last_brace - $i + 1); break; }
            }
        }

        if ($json_str && ($result = json_decode($json_str, true)) && isset($result['status'])) {
            if ($result['status'] === 'success') {
                // Guardar resultado en BD
                $pdo->prepare("UPDATE entrenamientos SET
                    fecha_fin=NOW(), estado='completado',
                    epocas_completadas=?, precision_final=?, precision_mejor=?,
                    precision_val=?, perdida_final=?, imagenes_train=?, imagenes_val=?,
                    categorias=?, batch_size=?, steps_por_epoca=?, augmentacion=?,
                    historial_acc=?, historial_loss=?
                    WHERE id_entrenamiento=?")->execute([
                    $result['epochs_completed'],
                    $result['final_accuracy'],
                    $result['best_accuracy'] ?? $result['final_accuracy'],
                    $result['final_val_accuracy'],
                    $result['final_loss'],
                    $result['training_samples'],
                    $result['validation_samples'],
                    json_encode($result['categories'] ?? []),
                    $result['batch_size_used'] ?? null,
                    $result['steps_per_epoch'] ?? null,
                    $result['augmentation'] ?? null,
                    json_encode($result['history']['accuracy'] ?? []),
                    json_encode($result['history']['loss'] ?? []),
                    $id_entrenamiento,
                ]);
                $msg = "Reentrenamiento completado — {$result['epochs_completed']} épocas | Precisión: {$result['final_accuracy']}%";
                if (($result['training_samples'] ?? 0) < 20) $msg .= " (sube más imágenes para mayor precisión)";

                // Notify Flask to reload the model from disk
                $flask_reload = @file_get_contents('http://localhost:5000/reload', false,
                    stream_context_create(['http' => ['method'=>'POST','timeout'=>5,
                        'header'=>'Content-Type: application/json','content'=>'{}']]));
                $model_reloaded = ($flask_reload && strpos($flask_reload, '"ok"') !== false);

                echo json_encode([
                    'success'         => true,
                    'message'         => $msg,
                    'data'            => $result,
                    'id_entrenamiento'=> $id_entrenamiento,
                    'epocas_usadas'   => $epochs,
                    'recomendacion'   => $rec,
                    'model_reloaded'  => $model_reloaded,
                ]);
            } else {
                $pdo->prepare("UPDATE entrenamientos SET fecha_fin=NOW(), estado='error', mensaje_error=? WHERE id_entrenamiento=?")
                    ->execute([$result['message'] ?? 'Error Python', $id_entrenamiento]);
                echo json_encode(['success'=>false,'message'=>$result['message'] ?? 'Error en Python.','raw'=>mb_substr($output,-3000)]);
            }
        } else {
            $pdo->prepare("UPDATE entrenamientos SET fecha_fin=NOW(), estado='error', mensaje_error=? WHERE id_entrenamiento=?")
                ->execute(['No se pudo parsear la salida del script.', $id_entrenamiento]);
            echo json_encode(['success'=>false,'message'=>'No se pudo interpretar la salida del script.','raw'=>mb_substr($output ?? '',-3000)]);
        }
        exit;
    }
}

// ── Preparar vista ─────────────────────────────────────────────────────────
$cats_con_imgs = count(array_filter($conteos, fn($v) => $v > 0));
$can_retrain   = ($cats_con_imgs >= 2 && $total_imgs >= 10);

// Historial reciente (últimos 8)
$historial = $pdo->query("SELECT * FROM entrenamientos ORDER BY fecha_inicio DESC LIMIT 8")->fetchAll();

$titulo        = 'Entrenar Modelo';
$css_extra     = 'dashboard.css';
$css_admin     = true;
$js_admin      = true;
$js_entrenamiento = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>
<script>
var TRAINING_TOTAL       = <?php echo (int)$total_imgs; ?>;
var EPOCAS_RECOMENDADAS  = <?php echo (int)$recomendacion['epochs']; ?>;
var NIVEL_RECOMENDADO    = <?php echo json_encode($recomendacion['nivel']); ?>;
var DESC_RECOMENDADA     = <?php echo json_encode($recomendacion['descripcion']); ?>;
</script>

<div class="main-content">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
  <div class="container-fluid">
    <h5 class="mb-0"><i class="bi bi-cpu me-2"></i>Entrenar Modelo IA</h5>
    <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['usuario_nombres']); ?></span>
  </div>
</nav>

<div class="container-fluid p-4">

<!-- ── Stats cards ─────────────────────────────────────────────────────── -->
<div class="row mb-4">
  <div class="col-md-3 mb-3">
    <div class="stat-card card border-0 shadow-sm">
      <div class="card-body"><div class="d-flex justify-content-between">
        <div><p class="text-muted mb-1">Total Imágenes</p><h3 class="mb-0 text-success" id="statTotalImgs"><?php echo $total_imgs; ?></h3></div>
        <div class="stat-icon bg-success-subtle rounded-3 p-3"><i class="bi bi-images fs-2 text-success"></i></div>
      </div></div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="stat-card card border-0 shadow-sm">
      <div class="card-body"><div class="d-flex justify-content-between">
        <div><p class="text-muted mb-1">Categorías</p><h3 class="mb-0 text-primary"><?php echo count($categorias); ?></h3></div>
        <div class="stat-icon bg-primary-subtle rounded-3 p-3"><i class="bi bi-tags fs-2 text-primary"></i></div>
      </div></div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="stat-card card border-0 shadow-sm">
      <div class="card-body"><div class="d-flex justify-content-between">
        <div><p class="text-muted mb-1">Épocas recomendadas</p><h3 class="mb-0 text-warning" id="epochsDisplay"><?php echo $recomendacion['epochs']; ?></h3></div>
        <div class="stat-icon bg-warning-subtle rounded-3 p-3"><i class="bi bi-arrow-repeat fs-2 text-warning"></i></div>
      </div></div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="stat-card card border-0 shadow-sm">
      <div class="card-body"><div class="d-flex justify-content-between">
        <div><p class="text-muted mb-1">Entrenamientos</p><h3 class="mb-0 text-info"><?php echo count($historial); ?></h3></div>
        <div class="stat-icon bg-info-subtle rounded-3 p-3"><i class="bi bi-clock-history fs-2 text-info"></i></div>
      </div></div>
    </div>
  </div>
</div>

<!-- ── Upload + Category stats ─────────────────────────────────────────── -->
<div class="row">
  <!-- Upload -->
  <div class="col-md-7 mb-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Subir Imágenes de Entrenamiento</h6>
        <small class="text-muted">JPG, PNG, WEBP · Máx 5 MB c/u</small>
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="action" value="upload_images">
          <div class="mb-3">
            <label class="form-label fw-semibold">Categoría</label>
            <select name="categoria" class="form-select" id="categoriaSelect" required>
              <option value="">-- Selecciona una categoría --</option>
              <?php foreach ($categorias as $folder => $nombre): ?>
              <option value="<?php echo $folder; ?>" data-nombre="<?php echo $nombre; ?>"><?php echo $nombre; ?> (<?php echo $conteos[$folder]; ?> imágenes)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <div class="form-label fw-semibold d-block mb-2">Imágenes</div>
            <label class="upload-area text-center" id="trainingUploadArea">
              <i class="bi bi-cloud-arrow-up fs-1 text-success"></i>
              <p class="mt-2 mb-0 fw-medium">Arrastra imágenes aquí o haz clic para seleccionar</p>
              <small class="text-muted">Puedes seleccionar múltiples imágenes a la vez</small>
              <input type="file" name="images[]" id="trainingImages" accept="image/*" multiple
                     style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">
            </label>
            <div id="fileCountInfo" class="small text-muted mt-2">0 archivos seleccionados</div>
            <div id="imagePreviewGrid" class="row g-2 mt-2"></div>
          </div>
          <button type="submit" class="btn btn-success w-100" id="uploadBtn">
            <i class="bi bi-cloud-upload me-2"></i>Subir Imágenes
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Category stats -->
  <div class="col-md-5 mb-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0"><h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Imágenes por Categoría</h6></div>
      <div class="card-body">
        <?php
        $colors = ['success','primary','warning','danger','info'];
        $ci = 0;
        foreach ($categorias as $folder => $nombre):
            $cnt     = $conteos[$folder];
            $maxc    = max(array_values($conteos)) ?: 1;
            $pct     = round(($cnt / $maxc) * 100);
            $color   = $colors[$ci++ % count($colors)];
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span><i class="bi bi-circle-fill text-<?php echo $color; ?> me-1" style="font-size:8px"></i><?php echo $nombre; ?></span>
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold" id="catCount_<?php echo $folder; ?>"><?php echo $cnt; ?> img</span>
              <?php if ($cnt > 0): ?>
              <button type="button" class="btn btn-link btn-sm p-0 text-danger btn-delete-cat"
                      data-folder="<?php echo $folder; ?>" data-nombre="<?php echo $nombre; ?>"
                      title="Eliminar imágenes de <?php echo $nombre; ?>">
                <i class="bi bi-trash3" style="font-size:13px"></i>
              </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="progress" style="height:10px">
            <div class="progress-bar bg-<?php echo $color; ?>" id="catBar_<?php echo $folder; ?>" style="width:<?php echo $pct; ?>%" role="progressbar"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="mt-3 p-3 bg-light rounded-3">
          <div class="d-flex justify-content-between align-items-center">
            <div><p class="mb-0 text-muted small">Total imágenes</p><h4 class="mb-0 text-success" id="panelTotal"><?php echo $total_imgs; ?></h4></div>
            <i class="bi bi-bar-chart fs-2 text-muted"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Reentrenamiento ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0"><h6 class="mb-0"><i class="bi bi-cpu me-2"></i>Reentrenar Modelo</h6></div>
  <div class="card-body">

    <!-- Checklist -->
    <div class="mb-4 p-3 rounded-3 border <?php echo $can_retrain ? 'border-success bg-success-subtle' : 'border-warning bg-warning-subtle'; ?>" id="retrainChecklist">
      <p class="fw-semibold mb-2 <?php echo $can_retrain ? 'text-success' : 'text-warning'; ?>" id="retrainChecklistTitle">
        <i class="bi <?php echo $can_retrain ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
        <?php echo $can_retrain ? 'Listo para reentrenar' : 'Requisitos pendientes'; ?>
      </p>
      <div class="row g-2">
        <div class="col-sm-6">
          <div class="d-flex align-items-center gap-2">
            <i class="bi <?php echo $cats_con_imgs >= 2 ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'; ?>" id="checkCats"></i>
            <span class="small" id="checkCatsLabel">Al menos 2 categorías con imágenes <strong>(<?php echo $cats_con_imgs; ?>/<?php echo count($categorias); ?>)</strong></span>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="d-flex align-items-center gap-2">
            <i class="bi <?php echo $total_imgs >= 10 ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'; ?>" id="checkTotal"></i>
            <span class="small" id="checkTotalLabel">Mínimo 10 imágenes en total <strong>(<?php echo $total_imgs; ?> actuales)</strong></span>
          </div>
        </div>
        <?php foreach ($categorias as $folder => $nombre): ?>
        <div class="col-sm-6 col-md-4">
          <div class="d-flex align-items-center gap-2">
            <i class="bi <?php echo $conteos[$folder] > 0 ? 'bi-check-circle text-success' : 'bi-dash-circle text-muted'; ?>" id="checkCat_<?php echo $folder; ?>"></i>
            <span class="small" id="checkCatLabel_<?php echo $folder; ?>"><?php echo $nombre; ?>: <strong><?php echo $conteos[$folder]; ?> img</strong></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="small text-muted mt-2 mb-0" id="retrainHint" <?php echo $can_retrain ? 'style="display:none"' : ''; ?>>
        <i class="bi bi-info-circle me-1"></i>Sube imágenes a las categorías. Se recomiendan al menos 20 por categoría.
      </p>
    </div>

    <!-- Guía de épocas automática -->
    <div class="mb-4 p-3 rounded-3 border border-info bg-info-subtle" id="epochsGuide">
      <div class="d-flex align-items-start gap-3">
        <i class="bi bi-lightbulb-fill text-info fs-4 mt-1"></i>
        <div class="flex-grow-1">
          <p class="fw-semibold mb-1 text-info">Épocas calculadas automáticamente</p>
          <p class="small mb-2 text-dark" id="epochsGuideDesc"><?php echo $recomendacion['descripcion']; ?></p>
          <div class="row g-2" id="epochsGuideCards">
            <?php
            $niveles = [
              ['imgs'=>'< 20',   'epochs'=>15, 'label'=>'Básico',   'color'=>'secondary', 'desc'=>'Pocas imágenes'],
              ['imgs'=>'20–49',  'epochs'=>20, 'label'=>'Bajo',     'color'=>'info',      'desc'=>'Conjunto pequeño'],
              ['imgs'=>'50–149', 'epochs'=>30, 'label'=>'Medio',    'color'=>'primary',   'desc'=>'Buen balance'],
              ['imgs'=>'150–499','epochs'=>40, 'label'=>'Alto',     'color'=>'success',   'desc'=>'Conjunto grande'],
              ['imgs'=>'500+',   'epochs'=>50, 'label'=>'Experto',  'color'=>'warning',   'desc'=>'Máxima precisión'],
            ];
            foreach ($niveles as $n):
              $active = ($n['label'] === ucfirst($recomendacion['nivel'])) ? 'border-2' : 'opacity-50';
            ?>
            <div class="col">
              <div class="text-center p-2 border rounded-3 <?php echo $active; ?> border-<?php echo $n['color']; ?>" style="min-width:80px">
                <div class="fw-bold text-<?php echo $n['color']; ?> fs-5"><?php echo $n['epochs']; ?></div>
                <div class="small fw-semibold"><?php echo $n['label']; ?></div>
                <div class="text-muted" style="font-size:10px"><?php echo $n['imgs']; ?> imgs</div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row align-items-center">
      <div class="col-md-8">
        <p class="mb-1 fw-medium">El sistema calculará automáticamente las épocas óptimas según tus imágenes.</p>
        <p class="text-muted small mb-0">Actualmente se usarán <strong id="epochsAutoLabel"><?php echo $recomendacion['epochs']; ?> épocas</strong> para <?php echo $total_imgs; ?> imágenes.</p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <button class="btn btn-success btn-lg" id="retrainBtn" <?php echo !$can_retrain ? 'disabled' : ''; ?>>
          <i class="bi bi-play-fill me-2"></i>Iniciar Reentrenamiento
        </button>
      </div>
    </div>

    <!-- Progress + Log -->
    <div id="trainingProgress" class="d-none mt-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small fw-semibold text-muted" id="trainingStatusText">Iniciando...</span>
        <span class="small fw-bold text-success" id="trainingPct">0%</span>
      </div>
      <div class="progress mb-3" style="height:20px;border-radius:10px">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="trainingProgressBar"
             style="width:0%;border-radius:10px;transition:width 0.6s ease">0%</div>
      </div>
      <div class="training-log bg-dark text-light p-3 rounded-3"
           style="max-height:260px;overflow-y:auto;font-family:'Consolas',monospace;font-size:12px;line-height:1.7">
        <div id="logOutput"><span class="text-muted">[SISTEMA] Inicializando...</span></div>
      </div>
    </div>
  </div>
</div>

<!-- ── Historial de entrenamientos ─────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de Entrenamientos</h6>
    <a href="historial_entrenamiento.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-list-ul me-1"></i>Ver todo
    </a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($historial)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-2"></i>
      <p class="mb-0">Aún no hay entrenamientos registrados.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th class="ps-3">#</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Épocas</th>
            <th>Precisión</th>
            <th>Mejor</th>
            <th>Imágenes</th>
            <th>Duración</th>
          </tr>
        </thead>
        <tbody id="historialBody">
        <?php foreach ($historial as $h):
          $badge = match($h['estado']) {
            'completado' => 'success',
            'error'      => 'danger',
            default      => 'warning',
          };
          $icono = match($h['estado']) {
            'completado' => 'bi-check-circle-fill',
            'error'      => 'bi-x-circle-fill',
            default      => 'bi-hourglass-split',
          };
          $duracion = '—';
          if ($h['fecha_fin'] && $h['fecha_inicio']) {
              $secs = strtotime($h['fecha_fin']) - strtotime($h['fecha_inicio']);
              $duracion = $secs >= 60 ? floor($secs/60).'m '.($secs%60).'s' : $secs.'s';
          }
        ?>
        <tr>
          <td class="ps-3 text-muted small">#<?php echo $h['id_entrenamiento']; ?></td>
          <td class="small"><?php echo date('d/m/Y H:i', strtotime($h['fecha_inicio'])); ?></td>
          <td><span class="badge bg-<?php echo $badge; ?>-subtle text-<?php echo $badge; ?> border border-<?php echo $badge; ?>">
            <i class="bi <?php echo $icono; ?> me-1"></i><?php echo ucfirst($h['estado']); ?>
          </span></td>
          <td class="small"><?php echo $h['epocas_completadas'] ?? $h['epocas_solicitadas']; ?></td>
          <td class="fw-bold <?php echo $h['estado']==='completado' ? 'text-success' : 'text-muted'; ?>">
            <?php echo $h['precision_final'] !== null ? $h['precision_final'].'%' : '—'; ?>
          </td>
          <td class="small text-primary">
            <?php echo $h['precision_mejor'] !== null ? $h['precision_mejor'].'%' : '—'; ?>
          </td>
          <td class="small"><?php echo $h['total_imagenes']; ?></td>
          <td class="small text-muted"><?php echo $duracion; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Diagnóstico ─────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-terminal me-2"></i>Diagnóstico del Entorno</h6>
    <button class="btn btn-sm btn-outline-secondary" id="btnCheckEnv">
      <i class="bi bi-search me-1"></i>Verificar entorno
    </button>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-2">Verifica que Python, TensorFlow y el modelo estén disponibles antes de reentrenar.</p>
    <div id="envResults" style="display:none"></div>
  </div>
</div>

</div><!-- /.container-fluid -->
</div><!-- /.main-content -->
<?php include_once '../includes/footer.php'; ?>
