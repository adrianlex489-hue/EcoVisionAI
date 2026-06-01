"""
Convierte keras_model.h5 a keras_model.tflite con compatibilidad maxima.
Ejecutar localmente antes de subir a GitHub.
"""
import os
os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

import tf_keras as keras
import tensorflow as tf
import numpy as np

BASE       = os.path.dirname(os.path.abspath(__file__))
MODEL_H5   = os.path.join(BASE, 'keras_model.h5')
MODEL_LITE = os.path.join(BASE, 'keras_model.tflite')

print("[1] Cargando modelo Keras...")
model = keras.models.load_model(MODEL_H5, compile=False)
print(f"    Input: {model.input_shape} | Output: {model.output_shape}")

print("[2] Convirtiendo a TFLite con compatibilidad maxima...")
converter = tf.lite.TFLiteConverter.from_keras_model(model)

# NO usar optimizaciones — mantener float32 puro para maxima compatibilidad
converter.optimizations = []

# Forzar operadores builtin compatibles con versiones antiguas
converter.target_spec.supported_ops = [tf.lite.OpsSet.TFLITE_BUILTINS]

# Bajar la version del flatbuffer para compatibilidad con TF 2.14/2.15
converter._experimental_lower_tensor_list_ops = False

tflite_model = converter.convert()

with open(MODEL_LITE, 'wb') as f:
    f.write(tflite_model)

size_kb = len(tflite_model) / 1024
print(f"[3] Guardado: {MODEL_LITE} ({size_kb:.1f} KB)")

print("[4] Verificando con TF local...")
interp = tf.lite.Interpreter(model_path=MODEL_LITE)
interp.allocate_tensors()
inp = interp.get_input_details()
out = interp.get_output_details()
print(f"    Input: {inp[0]['shape']} dtype={inp[0]['dtype']}")
print(f"    Output: {out[0]['shape']}")

dummy = np.zeros((1, 224, 224, 3), dtype=np.float32)
interp.set_tensor(inp[0]['index'], dummy)
interp.invoke()
result = interp.get_tensor(out[0]['index'])
print(f"    Prediccion OK: {result}")
print("[DONE] Listo para subir a GitHub.")
