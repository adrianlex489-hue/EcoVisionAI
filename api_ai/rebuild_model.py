"""
Reconstruye el modelo desde cero copiando los pesos,
eliminando el parámetro 'optional' incompatible.
"""
import os
os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

import tf_keras as keras
import tensorflow as tf
import numpy as np
import json, h5py

BASE       = os.path.dirname(os.path.abspath(__file__))
MODEL_IN   = os.path.join(BASE, 'keras_model.h5')
MODEL_OUT  = os.path.join(BASE, 'keras_model.h5')
SAVED_OUT  = os.path.join(BASE, 'keras_model_saved')

print("[1] Leyendo arquitectura y pesos del modelo original con h5py...")
with h5py.File(MODEL_IN, 'r') as f:
    # Read model config
    model_config = f.attrs.get('model_config')
    if isinstance(model_config, bytes):
        model_config = model_config.decode('utf-8')
    config = json.loads(model_config)

print("[2] Limpiando parámetro 'optional' del config...")
config_str = json.dumps(config)
# Remove 'optional' key from all layer configs
import re
config_str = re.sub(r',\s*"optional":\s*(true|false)', '', config_str)
config_str = re.sub(r'"optional":\s*(true|false),\s*', '', config_str)
config = json.loads(config_str)
print("    Config limpiado OK")

print("[3] Reconstruyendo modelo desde config limpio...")
clean_model = keras.models.model_from_json(json.dumps(config))

print("[4] Copiando pesos del modelo original...")
original = keras.models.load_model(MODEL_IN, compile=False)
clean_model.set_weights(original.get_weights())
print(f"    Pesos copiados. Input: {clean_model.input_shape} | Output: {clean_model.output_shape}")

print("[5] Verificando predicción...")
dummy = np.zeros((1, 224, 224, 3), dtype=np.float32)
pred = clean_model.predict(dummy, verbose=0)
print(f"    Predicción OK: shape={pred.shape}, sum={pred.sum():.4f}")

print("[6] Guardando modelo limpio como .h5...")
clean_model.save(MODEL_OUT, save_format='h5')
print(f"    Guardado: {MODEL_OUT}")

print("[7] Guardando SavedModel limpio...")
clean_model.save(SAVED_OUT, save_format='tf')
print(f"    Guardado: {SAVED_OUT}")

print("[DONE] Modelo reconstruido sin 'optional'. Listo para subir a GitHub.")
