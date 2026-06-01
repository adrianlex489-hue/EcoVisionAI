import os
import time
import traceback

os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'

import uuid
from flask import Flask, request, jsonify
from flask_cors import CORS
from predict import ModelPredictor

app = Flask(__name__)
CORS(app)

UPLOAD_FOLDER = os.path.join(os.path.dirname(__file__), 'uploads')
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

MODEL_PATH  = os.path.join(os.path.dirname(__file__), 'keras_model.h5')
LABELS_PATH = os.path.join(os.path.dirname(__file__), 'labels.txt')

# ── Auto-reload predictor when model or labels file changes on disk ──────────
_predictor       = None
_model_mtime     = 0
_labels_mtime    = 0

def get_predictor():
    global _predictor, _model_mtime, _labels_mtime

    try:
        current_model_mtime  = os.path.getmtime(MODEL_PATH)  if os.path.exists(MODEL_PATH)  else 0
        current_labels_mtime = os.path.getmtime(LABELS_PATH) if os.path.exists(LABELS_PATH) else 0
    except OSError:
        current_model_mtime  = 0
        current_labels_mtime = 0

    if (_predictor is None
            or current_model_mtime  != _model_mtime
            or current_labels_mtime != _labels_mtime):
        print(f"[INFO] Cargando modelo desde disco...")
        _predictor       = ModelPredictor(MODEL_PATH, LABELS_PATH)
        _model_mtime     = current_model_mtime
        _labels_mtime    = current_labels_mtime
        print(f"[INFO] Modelo cargado. Categorías: {_predictor.labels}")

    return _predictor


# ── Routes ───────────────────────────────────────────────────────────────────
@app.route('/predict', methods=['POST'])
def predict():
    if 'image' not in request.files:
        return jsonify({'error': 'No se envió ninguna imagen'}), 400

    file = request.files['image']
    if file.filename == '':
        return jsonify({'error': 'Nombre de archivo vacío'}), 400

    try:
        image_bytes = file.read()
        predictor   = get_predictor()
        result      = predictor.predict(image_bytes)

        # Save uploaded image
        ext      = file.filename.rsplit('.', 1)[1].lower() if '.' in file.filename else 'jpg'
        filename = f"{uuid.uuid4().hex}.{ext}"
        filepath = os.path.join(UPLOAD_FOLDER, filename)
        with open(filepath, 'wb') as f:
            f.write(image_bytes)

        result['imagen'] = filename
        return jsonify(result)

    except Exception as e:
        traceback.print_exc()
        return jsonify({'error': f'Error al procesar la imagen: {str(e)}'}), 500


@app.route('/reload', methods=['POST'])
def reload_model():
    """Force reload the model — call this after retraining."""
    global _predictor, _model_mtime, _labels_mtime
    _predictor    = None
    _model_mtime  = 0
    _labels_mtime = 0
    try:
        get_predictor()
        return jsonify({'status': 'ok', 'message': 'Modelo recargado correctamente.',
                        'categorias': _predictor.labels})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500


@app.route('/health', methods=['GET'])
def health():
    try:
        p = get_predictor()
        return jsonify({'status': 'ok', 'categorias': p.labels,
                        'model_path': MODEL_PATH})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e),
                        'model_exists': os.path.exists(MODEL_PATH),
                        'labels_exists': os.path.exists(LABELS_PATH)}), 500


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)
