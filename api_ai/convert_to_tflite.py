"""
Convierte keras_model.h5 a keras_model.tflite
Ejecutar UNA VEZ localmente. El .tflite es mucho más ligero para Render.
"""
import os
os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'

import tf_keras as keras
import tensorflow as tf
import numpy as np

BASE = os.path.dirname(os.path.abspath(__file__))
MODEL_H5   = os.path.join(BASE, 'keras_model.h5')
MODEL_LITE = os.path.join(BASE, 'keras_model.tflite')

print("[INFO] Cargando modelo Keras...")
model = keras.models.load_model(MODEL_H5, compile=False)
print(f"[INFO] Input: {model.input_shape} | Output: {model.output_shape}")

print("[INFO] Convirtiendo a TFLite...")
converter = tf.lite.TFLiteConverter.from_keras_model(model)
converter.optimizations = [tf.lite.Optimize.DEFAULT]  # quantization ligera
tflite_model = converter.convert()

with open(MODEL_LITE, 'wb') as f:
    f.write(tflite_model)

size_kb = len(tflite_model) / 1024
print(f"[OK] Guardado: {MODEL_LITE} ({size_kb:.1f} KB)")

# Verificar que funciona
print("[INFO] Verificando modelo TFLite...")
interpreter = tf.lite.Interpreter(model_path=MODEL_LITE)
interpreter.allocate_tensors()
inp = interpreter.get_input_details()
out = interpreter.get_output_details()
print(f"[OK] Input: {inp[0]['shape']} | Output: {out[0]['shape']}")

dummy = np.zeros((1, 224, 224, 3), dtype=np.float32)
interpreter.set_tensor(inp[0]['index'], dummy)
interpreter.invoke()
result = interpreter.get_tensor(out[0]['index'])
print(f"[OK] Predicción de prueba: {result}")
print("[DONE] Listo para subir a GitHub.")
