import os
import sys
import math

# Force UTF-8 output on Windows to avoid encoding issues with accented characters
if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass

# Must be set before importing tensorflow
os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'  # Suppress TF C++ info/warning logs
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'  # Suppress oneDNN messages

import warnings
warnings.filterwarnings('ignore', category=UserWarning)
warnings.filterwarnings('ignore', category=DeprecationWarning)

import tf_keras as keras
from tf_keras.src.preprocessing.image import ImageDataGenerator
import numpy as np
import json
import argparse
import shutil
from datetime import datetime

# Map folder names → display names (must match PHP $categorias)
CATEGORY_DISPLAY_NAMES = {
    'Plastico':       'Plástico',
    'Papel_y_carton': 'Papel y Cartón',
    'Vidrio':         'Vidrio',
    'Metal':          'Metal',
    'Organico':       'Orgánico',
}

# Minimum images to use a validation split
MIN_IMAGES_FOR_VALIDATION = 20


class ModelRetrainer:
    def __init__(self, model_path, labels_path, data_dir, output_dir=None):
        self.model_path  = model_path
        self.labels_path = labels_path
        self.data_dir    = data_dir
        self.output_dir  = output_dir or os.path.dirname(model_path)

    def get_category_folders(self):
        """Return list of category dicts that have at least 1 valid image."""
        categories = []
        if not os.path.exists(self.data_dir):
            return categories
        for item in sorted(os.listdir(self.data_dir)):
            item_path = os.path.join(self.data_dir, item)
            if os.path.isdir(item_path):
                images = [
                    f for f in os.listdir(item_path)
                    if f.lower().endswith(('.jpg', '.jpeg', '.png', '.webp'))
                ]
                if images:
                    categories.append({
                        'name':         item,
                        'display_name': CATEGORY_DISPLAY_NAMES.get(item, item),
                        'path':         item_path,
                        'count':        len(images),
                    })
        return categories

    def retrain(self, epochs=10, batch_size=16, learning_rate=0.0001):
        try:
            # ── 1. Load existing model ──────────────────────────────────────
            print("[INFO] Cargando modelo existente...")
            model = keras.models.load_model(self.model_path, compile=False)

            # ── 2. Validate categories ──────────────────────────────────────
            categories = self.get_category_folders()
            if len(categories) < 2:
                raise ValueError(
                    f"Se necesitan al menos 2 categorias con imagenes. "
                    f"Encontradas: {len(categories)}"
                )

            total_images = sum(c['count'] for c in categories)
            print(f"[INFO] Categorias con imagenes: {len(categories)}")
            for cat in categories:
                print(f"  - {cat['name']}: {cat['count']} imagenes")
            print(f"[INFO] Total imagenes: {total_images}")

            # ── 3. Decide validation strategy based on image count ──────────
            use_validation = total_images >= MIN_IMAGES_FOR_VALIDATION
            validation_split = 0.2 if use_validation else 0.0

            if not use_validation:
                print(f"[INFO] Pocas imagenes ({total_images}), sin split de validacion. "
                      f"Se usaran todas para entrenamiento.")
            else:
                print(f"[INFO] Usando 80/20 split para entrenamiento/validacion.")

            # ── 4. Adaptive batch_size ──────────────────────────────────────
            # batch_size must be <= number of training samples
            train_samples_approx = int(total_images * (1.0 - validation_split)) if use_validation else total_images
            # Use small batch for few images so we get multiple steps per epoch
            if train_samples_approx <= 4:
                effective_batch = 1
            elif train_samples_approx <= 8:
                effective_batch = 2
            elif train_samples_approx <= 16:
                effective_batch = 4
            else:
                effective_batch = min(batch_size, train_samples_approx)

            print(f"[INFO] batch_size efectivo: {effective_batch} "
                  f"(muestras entrenamiento aprox: {train_samples_approx})")

            # ── 5. Freeze early layers (transfer learning) ─────────────────
            total_layers    = len(model.layers)
            # With very few images, freeze MORE layers to prevent overfitting
            # (only fine-tune the last classification layer)
            if total_images < 30:
                freeze_pct = 0.95   # freeze 95% — only train final layer
            elif total_images < 100:
                freeze_pct = 0.85   # freeze 85%
            else:
                freeze_pct = 0.70   # freeze 70% — standard transfer learning

            trainable_start = int(total_layers * freeze_pct)
            # Always keep at least 1 layer trainable
            trainable_start = min(trainable_start, total_layers - 1)
            for i, layer in enumerate(model.layers):
                layer.trainable = i >= trainable_start
            print(f"[INFO] Capas congeladas: {trainable_start} | "
                  f"Entrenables: {total_layers - trainable_start}")

            # ── 6. Recompile ────────────────────────────────────────────────
            # Lower learning rate when few images to avoid overfitting
            if total_images < 30:
                effective_lr = learning_rate * 0.1   # 0.00001
            elif total_images < 100:
                effective_lr = learning_rate * 0.5   # 0.00005
            else:
                effective_lr = learning_rate          # 0.0001

            model.compile(
                optimizer=keras.optimizers.Adam(learning_rate=effective_lr),
                loss='categorical_crossentropy',
                metrics=['accuracy'],
            )
            print(f"[INFO] Learning rate efectivo: {effective_lr}")

            # ── 7. Data generators ──────────────────────────────────────────
            print("[INFO] Preparando datos con aumentacion...")

            # Augmentation — more aggressive when few images to help generalization
            augmentation_strength = 'strong' if total_images < 30 else 'normal'

            if augmentation_strength == 'strong':
                datagen_train = ImageDataGenerator(
                    rescale=1.0 / 255,
                    rotation_range=40,
                    width_shift_range=0.3,
                    height_shift_range=0.3,
                    shear_range=0.3,
                    zoom_range=0.3,
                    horizontal_flip=True,
                    vertical_flip=False,
                    brightness_range=[0.7, 1.3],
                    fill_mode='nearest',
                )
            else:
                datagen_train = ImageDataGenerator(
                    rescale=1.0 / 255,
                    rotation_range=20,
                    width_shift_range=0.2,
                    height_shift_range=0.2,
                    shear_range=0.2,
                    zoom_range=0.2,
                    horizontal_flip=True,
                    brightness_range=[0.8, 1.2],
                    fill_mode='nearest',
                )

            datagen_val = ImageDataGenerator(rescale=1.0 / 255)

            if use_validation:
                # Use subset split
                datagen_split = ImageDataGenerator(
                    rescale=1.0 / 255,
                    rotation_range=40 if augmentation_strength == 'strong' else 20,
                    width_shift_range=0.3 if augmentation_strength == 'strong' else 0.2,
                    height_shift_range=0.3 if augmentation_strength == 'strong' else 0.2,
                    shear_range=0.3 if augmentation_strength == 'strong' else 0.2,
                    zoom_range=0.3 if augmentation_strength == 'strong' else 0.2,
                    horizontal_flip=True,
                    brightness_range=[0.7, 1.3] if augmentation_strength == 'strong' else [0.8, 1.2],
                    fill_mode='nearest',
                    validation_split=validation_split,
                )
                train_gen = datagen_split.flow_from_directory(
                    self.data_dir,
                    target_size=(224, 224),
                    batch_size=effective_batch,
                    class_mode='categorical',
                    subset='training',
                    shuffle=True,
                )
                val_gen = datagen_split.flow_from_directory(
                    self.data_dir,
                    target_size=(224, 224),
                    batch_size=effective_batch,
                    class_mode='categorical',
                    subset='validation',
                    shuffle=False,
                )
            else:
                # No validation split — use all images for training
                train_gen = datagen_train.flow_from_directory(
                    self.data_dir,
                    target_size=(224, 224),
                    batch_size=effective_batch,
                    class_mode='categorical',
                    shuffle=True,
                )
                val_gen = None

            actual_train_samples = train_gen.samples
            actual_val_samples   = val_gen.samples if val_gen else 0

            print(f"[INFO] Muestras entrenamiento: {actual_train_samples} | "
                  f"Validacion: {actual_val_samples}")

            if actual_train_samples == 0:
                raise ValueError("No hay imagenes en el generador de entrenamiento.")

            # steps_per_epoch: use ceil so every image is seen each epoch
            steps_per_epoch = max(1, math.ceil(actual_train_samples / effective_batch))
            print(f"[INFO] steps_per_epoch: {steps_per_epoch}")

            # ── 8. Train ────────────────────────────────────────────────────
            print(f"[INFO] Entrenando por {epochs} epocas...")

            fit_kwargs = {
                'steps_per_epoch': steps_per_epoch,
                'epochs':          epochs,
                'verbose':         1,
            }

            if val_gen and actual_val_samples > 0:
                val_steps = max(1, math.ceil(actual_val_samples / effective_batch))
                fit_kwargs['validation_data']  = val_gen
                fit_kwargs['validation_steps'] = val_steps

            history = model.fit(train_gen, **fit_kwargs)

            # ── 9. Save model with backup ───────────────────────────────────
            timestamp   = datetime.now().strftime("%Y%m%d_%H%M%S")
            backup_path = os.path.join(self.output_dir, f"keras_model_backup_{timestamp}.h5")
            shutil.copy2(self.model_path, backup_path)
            print(f"[INFO] Backup guardado: {backup_path}")

            model_out = os.path.join(self.output_dir, 'keras_model.h5')
            model.save(model_out)
            print(f"[INFO] Nuevo modelo guardado: {model_out}")

            # ── 10. Update labels.txt ───────────────────────────────────────
            class_indices  = train_gen.class_indices
            sorted_folders = [k for k, v in sorted(class_indices.items(), key=lambda x: x[1])]
            labels_out     = os.path.join(os.path.dirname(self.labels_path), 'labels.txt')
            with open(labels_out, 'w', encoding='utf-8') as f:
                for idx, folder in enumerate(sorted_folders):
                    display = CATEGORY_DISPLAY_NAMES.get(folder, folder)
                    f.write(f"{idx} {display}\n")
            print(f"[INFO] Labels actualizados: {labels_out}")

            # ── 11. Build result ────────────────────────────────────────────
            acc_hist     = history.history.get('accuracy', [0.0])
            val_acc_hist = history.history.get('val_accuracy', [])
            loss_hist    = history.history.get('loss', [0.0])
            val_loss_hist = history.history.get('val_loss', [])

            final_acc     = round(float(acc_hist[-1]) * 100, 2)
            final_val_acc = round(float(val_acc_hist[-1]) * 100, 2) if val_acc_hist else None
            final_loss    = round(float(loss_hist[-1]), 4)

            # Best accuracy across all epochs
            best_acc = round(float(max(acc_hist)) * 100, 2)

            return {
                'status':             'success',
                'epochs_completed':   epochs,
                'final_accuracy':     final_acc,
                'best_accuracy':      best_acc,
                'final_val_accuracy': final_val_acc,
                'final_loss':         final_loss,
                'training_samples':   actual_train_samples,
                'validation_samples': actual_val_samples,
                'steps_per_epoch':    steps_per_epoch,
                'batch_size_used':    effective_batch,
                'augmentation':       augmentation_strength,
                'categories':         [CATEGORY_DISPLAY_NAMES.get(f, f) for f in sorted_folders],
                'backup_path':        backup_path,
                'history': {
                    'accuracy':     [round(float(a) * 100, 2) for a in acc_hist],
                    'val_accuracy': [round(float(a) * 100, 2) for a in val_acc_hist],
                    'loss':         [round(float(l), 4) for l in loss_hist],
                    'val_loss':     [round(float(l), 4) for l in val_loss_hist],
                },
            }

        except Exception as e:
            error_msg = f"Error durante el reentrenamiento: {str(e)}"
            print(f"[ERROR] {error_msg}")
            return {
                'status':     'error',
                'message':    error_msg,
                'error_type': type(e).__name__,
            }


def main():
    parser = argparse.ArgumentParser(description='Reentrenar modelo EcoVision AI')
    parser.add_argument('--epochs',     type=int,   default=10,     help='Numero de epocas')
    parser.add_argument('--batch-size', type=int,   default=16,     help='Tamano de batch')
    parser.add_argument('--lr',         type=float, default=0.0001, help='Tasa de aprendizaje')
    args = parser.parse_args()

    base_dir    = os.path.dirname(os.path.abspath(__file__))
    model_path  = os.path.join(base_dir, 'keras_model.h5')
    labels_path = os.path.join(base_dir, 'labels.txt')
    data_dir    = os.path.join(base_dir, 'training_data')

    if not os.path.exists(model_path):
        result = {'status': 'error', 'message': f'No se encuentra el modelo: {model_path}'}
        print(json.dumps(result, ensure_ascii=True))
        sys.exit(1)

    if not os.path.exists(data_dir):
        result = {'status': 'error', 'message': f'No se encuentra el directorio de datos: {data_dir}'}
        print(json.dumps(result, ensure_ascii=True))
        sys.exit(1)

    retrainer = ModelRetrainer(model_path, labels_path, data_dir)
    result    = retrainer.retrain(
        epochs=args.epochs,
        batch_size=args.batch_size,
        learning_rate=args.lr,
    )

    # Always print JSON as the LAST line — PHP parses it by finding the last {...} block
    print(json.dumps(result, indent=2, ensure_ascii=True))

    if result.get('status') == 'error':
        sys.exit(1)


if __name__ == '__main__':
    main()
