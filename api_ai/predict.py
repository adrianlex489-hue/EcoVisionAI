import os
import numpy as np
from PIL import Image
import io

os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'
os.environ['CUDA_VISIBLE_DEVICES'] = '-1'


class ModelPredictor:
    def __init__(self, model_path, labels_path):
        import tf_keras as keras

        if not os.path.exists(labels_path):
            raise FileNotFoundError(f"Labels no encontrado: {labels_path}")

        # Priority: SavedModel > TFLite > .h5
        saved_dir   = model_path.replace('.h5', '_saved')
        tflite_path = model_path.replace('.h5', '.tflite')

        if os.path.isdir(saved_dir):
            print(f"[INFO] Cargando SavedModel: {saved_dir}")
            self.model = keras.models.load_model(saved_dir, compile=False)
            self._mode = 'keras'
            print(f"[INFO] SavedModel OK. Output: {self.model.output_shape}")

        elif os.path.exists(tflite_path):
            print(f"[INFO] Cargando TFLite: {tflite_path}")
            import tensorflow as tf
            self._interp = tf.lite.Interpreter(model_path=tflite_path)
            self._interp.allocate_tensors()
            self._inp = self._interp.get_input_details()
            self._out = self._interp.get_output_details()
            self._mode = 'tflite'
            print(f"[INFO] TFLite OK")

        elif os.path.exists(model_path):
            print(f"[INFO] Cargando .h5: {model_path}")
            self.model = keras.models.load_model(model_path, compile=False)
            self._mode = 'keras'
            print(f"[INFO] .h5 OK")

        else:
            raise FileNotFoundError(f"No se encontró modelo en: {model_path}")

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

        if self._mode == 'tflite':
            self._interp.set_tensor(self._inp[0]['index'], arr)
            self._interp.invoke()
            preds = self._interp.get_tensor(self._out[0]['index'])[0]
        else:
            preds = self.model.predict(arr, verbose=0)[0]

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
