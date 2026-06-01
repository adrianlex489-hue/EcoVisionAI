import os
import numpy as np
from PIL import Image
import io

os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'
os.environ['CUDA_VISIBLE_DEVICES'] = '-1'


class ModelPredictor:
    def __init__(self, model_path, labels_path):
        if not os.path.exists(labels_path):
            raise FileNotFoundError(f"Labels no encontrado: {labels_path}")

        # Use TFLite exclusively — avoids the 'optional' kwarg bug in tf-keras
        tflite_path = model_path.replace('.h5', '.tflite')
        if not os.path.exists(tflite_path):
            raise FileNotFoundError(
                f"No se encontró keras_model.tflite en: {tflite_path}\n"
                f"Ejecuta convert_to_tflite.py localmente y sube el archivo."
            )

        import tensorflow as tf
        self._interp = tf.lite.Interpreter(model_path=tflite_path)
        self._interp.allocate_tensors()
        self._inp = self._interp.get_input_details()
        self._out = self._interp.get_output_details()
        print(f"[INFO] TFLite cargado OK. Input: {self._inp[0]['shape']}")

        # Load labels
        with open(labels_path, 'r', encoding='utf-8') as f:
            lines = [l.strip() for l in f if l.strip()]
        self.labels = []
        for line in lines:
            parts = line.split(' ', 1)
            self.labels.append(parts[1] if len(parts) == 2 and parts[0].isdigit() else line)
        print(f"[INFO] Labels: {self.labels}")

    def predict(self, image_bytes):
        img = Image.open(io.BytesIO(image_bytes)).convert('RGB').resize((224, 224))
        arr = np.expand_dims(np.array(img, dtype=np.float32) / 255.0, axis=0)

        self._interp.set_tensor(self._inp[0]['index'], arr)
        self._interp.invoke()
        preds = self._interp.get_tensor(self._out[0]['index'])[0]

        idx        = int(np.argmax(preds))
        confidence = float(preds[idx]) * 100
        categoria  = self.labels[idx] if idx < len(self.labels) else f"Categoría {idx+1}"

        scores = sorted([
            {'categoria': self.labels[i] if i < len(self.labels) else f"Categoría {i+1}",
             'confianza': round(float(preds[i]) * 100, 2)}
            for i in range(len(preds))
        ], key=lambda x: x['confianza'], reverse=True)

        return {
            'categoria':    categoria,
            'confianza':    round(confidence, 2),
            'alternativas': scores[1:3],
        }
