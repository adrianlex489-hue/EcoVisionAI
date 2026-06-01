# Desplegar el servicio IA en Render.com (GRATIS)

## Pasos

### 1. Crear cuenta en Render
Ve a https://render.com y crea una cuenta gratuita.

### 2. Subir el modelo a GitHub
El modelo `keras_model.h5` es muy grande para GitHub (>100MB).
Usa Git LFS o sube el modelo directamente en el paso 4.

### 3. Crear nuevo Web Service en Render
- Clic en "New" → "Web Service"
- Conecta tu repositorio de GitHub
- Selecciona la carpeta `api_ai/` como root directory
- Runtime: Python 3
- Build Command: `pip install -r render_requirements.txt`
- Start Command: `gunicorn app:app --bind 0.0.0.0:$PORT --timeout 120`

### 4. Subir el modelo
Dado que keras_model.h5 es grande, tienes dos opciones:
- **Opción A**: Incluirlo en el repo con Git LFS
- **Opción B**: Usar Render Disk (persistent storage) y subir el archivo

### 5. Actualizar la URL en app.php
Una vez desplegado, Render te da una URL como:
`https://ecovision-ai.onrender.com`

Cambia en `api_ai/app.php`:
```php
define('AI_SERVICE_URL', 'https://ecovision-ai.onrender.com/predict');
```

## Nota sobre el tier gratuito de Render
- El servicio se "duerme" después de 15 minutos de inactividad
- La primera petición después de dormir tarda ~30-60 segundos (cold start)
- Para evitar esto, usa un servicio de ping como https://uptimerobot.com
