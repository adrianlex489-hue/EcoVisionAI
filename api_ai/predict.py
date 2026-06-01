import os
os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'

import tf_keras as keras
import numpy as np
from PIL import Image
import io


class ModelPredictor:
    def __init__(self, model_path, labels_path):
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"Modelo no encontrado: {model_path}")
        if not os.path.exists(labels_path):
            raise FileNotFoundError(f"Labels no encontrado: {labels_path}")

        # Try loading .h5 first, fall back to SavedModel directory
        saved_model_dir = model_path.replace('.h5', '_saved')
        try:
            self.model = keras.models.load_model(model_path, compile=False)
        except Exception as e1:
            print(f"[WARN] No se pudo cargar .h5: {e1}")
            if os.path.isdir(saved_model_dir):
                print(f"[INFO] Intentando cargar SavedModel desde: {saved_model_dir}")
                self.model = keras.models.load_model(saved_model_dir, compile=False)
            else:
                raise

        with open(labels_path, 'r', encoding='utf-8') as f:
            lines = [l.strip() for l in f.readlines() if l.strip()]

        # Support both "0 Label" and plain "Label" formats
        self.labels = []
        for line in lines:
            parts = line.split(' ', 1)
            if len(parts) == 2 and parts[0].isdigit():
                self.labels.append(parts[1])
            else:
                self.labels.append(line)

        print(f"[INFO] Labels cargados: {self.labels}")

        # Validate that model output matches label count
        model_output_size = self.model.output_shape[-1]
        if model_output_size != len(self.labels):
            print(f"[WARN] El modelo tiene {model_output_size} salidas pero labels.txt tiene "
                  f"{len(self.labels)} etiquetas. Se ajustará automáticamente.")
            # Pad or trim labels to match model output
            if model_output_size > len(self.labels):
                for i in range(len(self.labels), model_output_size):
                    self.labels.append(f"Categoría {i+1}")
            else:
                self.labels = self.labels[:model_output_size]

    def predict(self, image_bytes):
        img = Image.open(io.BytesIO(image_bytes)).convert('RGB')
        img = img.resize((224, 224))

        img_array = np.array(img, dtype=np.float32)
        img_array = np.expand_dims(img_array, axis=0)
        img_array = img_array / 255.0

        predictions = self.model.predict(img_array, verbose=0)
        predicted_idx = int(np.argmax(predictions[0]))
        confidence    = float(predictions[0][predicted_idx]) * 100

        # Safe label lookup
        categoria = self.labels[predicted_idx] if predicted_idx < len(self.labels) else f"Categoría {predicted_idx+1}"

        # Build all category scores for richer response
        all_scores = []
        for i, score in enumerate(predictions[0]):
            label = self.labels[i] if i < len(self.labels) else f"Categoría {i+1}"
            all_scores.append({'categoria': label, 'confianza': round(float(score) * 100, 2)})

        # Sort by confidence descending
        all_scores.sort(key=lambda x: x['confianza'], reverse=True)

        return {
            'categoria':  categoria,
            'confianza':  round(confidence, 2),
            'alternativas': all_scores[1:3],  # top 2 alternatives
        }
