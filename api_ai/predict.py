import os
import numpy as np
from PIL import Image
import io

# Try TFLite first (lightweight, works on free tier)
# Fall back to full TF/Keras if TFLite not available
_USE_TFLITE = False
_interpreter = None

def _load_tflite(model_path):
    global _USE_TFLITE, _interpreter
    tflite_path = model_path.replace('.h5', '.tflite')
    if not os.path.exists(tflite_path):
        return False
    try:
        import tflite_runtime.interpreter as tflite
        _interpreter = tflite.Interpreter(model_path=tflite_path)
        _interpreter.allocate_tensors()
        _USE_TFLITE = True
        print(f"[INFO] Usando tflite_runtime (ligero)")
        return True
    except ImportError:
        pass
    try:
        import tensorflow as tf
        _interpreter = tf.lite.Interpreter(model_path=tflite_path)
        _interpreter.allocate_tensors()
        _USE_TFLITE = True
        print(f"[INFO] Usando tf.lite.Interpreter")
        return True
    except Exception as e:
        print(f"[WARN] TFLite no disponible: {e}")
        return False


class ModelPredictor:
    def __init__(self, model_path, labels_path):
        if not os.path.exists(labels_path):
            raise FileNotFoundError(f"Labels no encontrado: {labels_path}")

        # Try TFLite first (much lighter on RAM)
        if _load_tflite(model_path):
            self._inp = _interpreter.get_input_details()
            self._out = _interpreter.get_output_details()
            print(f"[INFO] TFLite listo. Input: {self._inp[0]['shape']}")
        else:
            # Fall back to full Keras
            if not os.path.exists(model_path):
                raise FileNotFoundError(f"Modelo no encontrado: {model_path}")
            os.environ['TF_USE_LEGACY_KERAS'] = '1'
            os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
            import tf_keras as keras
            saved_model_dir = model_path.replace('.h5', '_saved')
            try:
                self._keras_model = keras.models.load_model(model_path, compile=False)
                print(f"[INFO] Keras .h5 cargado")
            except Exception as e1:
                print(f"[WARN] .h5 falló: {e1}")
                if os.path.isdir(saved_model_dir):
                    self._keras_model = keras.models.load_model(saved_model_dir, compile=False)
                    print(f"[INFO] SavedModel cargado")
                else:
                    raise

        # Load labels
        with open(labels_path, 'r', encoding='utf-8') as f:
            lines = [l.strip() for l in f.readlines() if l.strip()]
        self.labels = []
        for line in lines:
            parts = line.split(' ', 1)
            if len(parts) == 2 and parts[0].isdigit():
                self.labels.append(parts[1])
            else:
                self.labels.append(line)
        print(f"[INFO] Labels: {self.labels}")

    def predict(self, image_bytes):
        img = Image.open(io.BytesIO(image_bytes)).convert('RGB')
        img = img.resize((224, 224))
        img_array = np.array(img, dtype=np.float32) / 255.0
        img_array = np.expand_dims(img_array, axis=0)

        if _USE_TFLITE:
            _interpreter.set_tensor(self._inp[0]['index'], img_array)
            _interpreter.invoke()
            predictions = _interpreter.get_tensor(self._out[0]['index'])[0]
        else:
            predictions = self._keras_model.predict(img_array, verbose=0)[0]

        predicted_idx = int(np.argmax(predictions))
        confidence    = float(predictions[predicted_idx]) * 100
        categoria     = self.labels[predicted_idx] if predicted_idx < len(self.labels) else f"Categoría {predicted_idx+1}"

        all_scores = [
            {'categoria': self.labels[i] if i < len(self.labels) else f"Categoría {i+1}",
             'confianza': round(float(predictions[i]) * 100, 2)}
            for i in range(len(predictions))
        ]
        all_scores.sort(key=lambda x: x['confianza'], reverse=True)

        return {
            'categoria':    categoria,
            'confianza':    round(confidence, 2),
            'alternativas': all_scores[1:3],
        }
