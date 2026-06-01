# EcoVision AI

Sistema web para la clasificacion automatica de residuos reciclables mediante inteligencia artificial. Los usuarios pueden subir fotografias de residuos y el sistema, utilizando un modelo TensorFlow/Keras entrenado con Teachable Machine, determina automaticamente la categoria del residuo (Plastico, Papel y carton, Vidrio, Metal, u Organico) junto con un porcentaje de confianza.

## Indice

1. [Descripcion general](#descripcion-general)
2. [Tecnologias utilizadas](#tecnologias-utilizadas)
3. [Arquitectura del sistema](#arquitectura-del-sistema)
4. [Estructura del proyecto](#estructura-del-proyecto)
5. [Flujo de clasificacion](#flujo-de-clasificacion)
6. [Flujo de reentrenamiento](#flujo-de-reentrenamiento)
7. [Base de datos](#base-de-datos)
8. [Rol de usuarios](#rol-de-usuarios)
9. [Componentes del frontend](#componentes-del-frontend)
10. [Componentes del backend](#componentes-del-backend)
11. [API de inteligencia artificial](#api-de-inteligencia-artificial)
12. [Sistema de autenticacion](#sistema-de-autenticacion)
13. [Sidebar push menu](#sidebar-push-menu)
14. [Instalacion](#instalacion)
15. [Configuracion de correo](#configuracion-de-correo)
16. [Licencia](#licencia)

---

## Descripcion general

EcoVision AI es una aplicacion web completa que permite a usuarios registrados clasificar imagenes de residuos en 5 categorias utilizando un modelo de deep learning. El sistema incluye:

- **Clasificador IA:** Interfaz drag-and-drop donde el usuario sube una imagen y obtiene al instante la categoria detectada y el porcentaje de confianza. La imagen se guarda en el servidor y el resultado se almacena en la base de datos para su consulta posterior.
- **Dashboard personal:** Panel con graficos estadisticos (donut de categorias, barras de actividad por dia, barras de confianza promedio por categoria, combo de clasificaciones diarias + confianza promedio), tarjetas con totales, y tabla de ultimas clasificaciones.
- **Historial:** Tabla interactiva con jQuery DataTables que permite buscar, ordenar, paginar y filtrar las clasificaciones por texto, categoria (selector modal con tarjetas de colores) y fecha. Incluye modal de detalle con la imagen, categoria coloreada, barra de confianza, fecha e ID. Las clasificaciones pueden eliminarse individualmente con confirmacion SweetAlert2.
- **Dashboard admin:** Panel administrativo con graficos globales (donut de categorias de todos los usuarios, linea de actividad de 7 dias, barras de confianza por categoria), tarjetas con totales de usuarios y clasificaciones, y tabla de los 5 usuarios mas activos.
- **Gestion de usuarios:** Listado con busqueda, activacion/desactivacion de cuentas, cambio de roles.
- **Reportes:** Filtrado de clasificaciones por rango de fechas con desglose por categoria.
- **Reentrenamiento del modelo:** Interfaz donde el administrador puede subir imagenes de entrenamiento organizadas por categoria (5 categorias, 5 zonas de drop independientes). Al enviar, se ejecuta un script Python que realiza transfer learning sobre el modelo existente, con augmentacion de datos (rotacion, desplazamiento, zoom, volteo, brillo), congelando el 70% de las capas iniciales y guardando respaldo del modelo anterior.
- **Perfil de usuario:** Actualizacion de datos personales, cambio de contrasena, resumen de actividad.
- **Recuperacion de contrasena:** Sistema de codigo de 6 digitos enviado por correo electronico via PHPMailer + Gmail SMTP, con expiracion de 15 minutos.
- **Modo oscuro:** Toggle de dark mode con persistencia en localStorage.
- **Responsive design:** Interfaz adaptable a escritorio, tablet y celular con sidebar push menu que cambia de comportamiento segun el tamano de pantalla.

---

## Tecnologias utilizadas

### Frontend
| Tecnologia | Version | Uso |
|---|---|---|
| HTML5 | — | Estructura de paginas |
| CSS3 | — | Estilos personalizados |
| Bootstrap | 5.3.2 | Framework de componentes responsive |
| JavaScript | Vanilla ES6 | Logica de cliente, manipulacion DOM |
| Chart.js | 4 | Graficos estadisticos (doughnut, bar, line, combo) |
| jQuery DataTables | 1.13.6 | Tabla interactiva con busqueda, orden y paginacion |
| DataTables Responsive | 2.5.0 | Adaptacion de tabla a dispositivos moviles |
| SweetAlert2 | 11 | Alertas y confirmaciones estilizadas |
| Bootstrap Icons | 1.11 | Iconografia del sistema |

### Backend
| Tecnologia | Version | Uso |
|---|---|---|
| PHP | 8.x | Logica de servidor, renderizado, API bridge |
| MySQL | 8.x | Base de datos relacional |
| PDO | — | Conexion segura a base de datos |
| PHPMailer | 7.1 | Envio de correos electronicos |
| Composer | — | Gestor de dependencias PHP |

### Inteligencia Artificial
| Tecnologia | Version | Uso |
|---|---|---|
| Python | 3.12 | Ejecucion del modelo de IA |
| Flask | — | Servidor HTTP para la API de prediccion |
| TensorFlow | — | Framework de deep learning |
| Keras (tf_keras) | Legacy | Carga y ejecucion del modelo `.h5` |
| Teachable Machine | — | Entrenamiento inicial del modelo |
| NumPy | — | Procesamiento numerico de imagenes |
| Pillow | — | Manipulacion de imagenes |

---

## Arquitectura del sistema

```
┌─────────────────────────────────────────────────────────────┐
│                      Navegador web                           │
│  (HTML + CSS + Bootstrap + JS + Chart.js + DataTables)      │
└──────────────────┬──────────────────────────────────────────┘
                   │ HTTP (Apache)
                   ▼
┌─────────────────────────────────────────────────────────────┐
│                      PHP (Servidor web)                      │
│  ┌───────────┐ ┌──────────┐ ┌───────────┐ ┌──────────────┐ │
│  │ auth.php  │ │ funciones│ │ header/   │ │ app.php      │ │
│  │           │ │ .php     │ │ footer    │ │ (bridge IA)  │ │
│  └───────────┘ └──────────┘ │ .php      │ └──────┬───────┘ │
│                            └───────────┘        │          │
│  ┌──────────────────────────────────────────┐   │ cURL     │
│  │        MySQL (ecovision_ai)              │   │          │
│  │  - usuarios                              │   │          │
│  │  - clasificaciones                       │   │          │
│  │  - reset_codigos                         │   │          │
│  └──────────────────────────────────────────┘   │          │
└─────────────────────────────────┬────────────────┼──────────┘
                                  │                │
                                  ▼                ▼
                      ┌──────────────────────────────────────┐
                      │        Flask API (Python)             │
                      │  http://127.0.0.1:5000                │
                      │  POST /predict → prediccion          │
                      │  GET  /health  → health check        │
                      │                                      │
                      │  ┌────────────┐ ┌──────────────────┐ │
                      │  │ predict.py │ │ keras_model.h5   │ │
                      │  │            │ │ labels.txt       │ │
                      │  └────────────┘ └──────────────────┘ │
                      └──────────────────────────────────────┘
```

### Flujo de datos

1. **El usuario** sube una imagen desde el clasificador (`dashboard/clasificador.php`)
2. **JavaScript** (`assets/js/clasificador.js`) captura el archivo y lo envia via fetch a `api_ai/app.php?action=predict`
3. **PHP bridge** (`api_ai/app.php`) recibe la imagen, la valida (formato, tamano), la guarda en `uploads/clasificaciones/`, y la reenvia via cURL a `http://127.0.0.1:5000/predict`
4. **Flask** (`api_ai/app.py`) recibe la imagen, la pasa a `predict.py` que la redimensiona a 224x224, normaliza los pixeles (division por 255.0), y ejecuta el modelo Keras. Retorna JSON con categoria y confianza.
5. **PHP bridge** recibe la respuesta de Flask, guarda el registro en la tabla `clasificaciones` con el ID del usuario, y retorna el resultado al frontend.
6. **JavaScript** muestra el resultado mediante SweetAlert2 con el nombre de la categoria y el porcentaje de confianza.

---

## Estructura del proyecto

```
EcoVisionAI/                          # Raiz del proyecto
│
├── index.php                         # Pagina de inicio: redirige a login o dashboard
├── login.php                         # Inicio de sesion (email + password)
├── register.php                      # Registro de nuevos usuarios
├── forgot_password.php               # Solicitud de recuperacion (envia codigo por email)
├── reset_password.php                # Verificacion de codigo + nuevo password
├── logout.php                        # Destruye sesion y redirige a login
├── composer.json                     # Dependencia PHPMailer
├── flask_err.txt                     # Log de errores de Flask
├── flask_out.txt                     # Log de salida de Flask
│
├── dashboard/                        # MODULO: Panel de usuario (4 paginas)
│   ├── dashboard.php                 # Dashboard principal con graficos Chart.js
│   │   - Grafico donut: Clasificaciones por categoria
│   │   - Grafico barras: Actividad ultimos 7 dias
│   │   - Grafico combo: Clasificaciones diarias + confianza promedio
│   │   - Grafico horizontal: Confianza promedio por categoria
│   │   - Tarjetas: Total clasificaciones, ultima clasificacion
│   │   - Tabla: Ultimas 5 clasificaciones
│   │
│   ├── clasificador.php              # Interfaz de clasificacion IA
│   │   - Zona drag-and-drop para subir imagen
│   │   - Vista previa de la imagen seleccionada
│   │   - Boton de clasificar
│   │   - Resultado con categoria + confianza
│   │
│   ├── historial.php                 # Historial de clasificaciones
│   │   - DataTable con todas las clasificaciones
│   │   - Filtros: busqueda de texto, categoria (modal con tarjetas de colores), fecha
│   │   - Modal detalle: imagen, categoria coloreada, barra confianza, fecha, ID
│   │   - Eliminacion con confirmacion SweetAlert2
│   │   - Botones Filtrar y Limpiar
│   │
│   └── perfil.php                    # Perfil y configuracion
│       - Editar nombre, apellidos, correo
│       - Cambiar contrasena
│       - Resumen de actividad
│
├── admin/                            # MODULO: Panel administrativo (4 paginas)
│   ├── dashboard.php                 # Dashboard admin
│   │   - Grafico donut: Categorias globales
│   │   - Grafico linea: Actividad 7 dias
│   │   - Grafico barras: Confianza por categoria
│   │   - Tarjetas: Total usuarios, clasificaciones
│   │   - Tabla: Top 5 usuarios mas activos
│   │
│   ├── usuarios.php                  # Gestion de usuarios
│   │   - Listado con busqueda
│   │   - Activar/desactivar cuentas
│   │   - Cambio de roles
│   │
│   ├── reportes.php                  # Reportes
│   │   - Filtro por rango de fechas
│   │   - Desglose por categoria
│   │   - Exportacion CSV
│   │
│   └── entrenamiento.php             # Reentrenamiento del modelo
│       - 5 zonas drag-and-drop (una por categoria)
│       - Vista previa de imagenes seleccionadas
│       - Barra de progreso durante el entrenamiento
│       - Ejecuta retrain.py con transfer learning
│
├── api_ai/                           # MODULO: API de IA (Python Flask)
│   ├── app.py                        # Servidor Flask
│   │   - POST /predict: recibe imagen, retorna categoria + confianza
│   │   - GET /health: health check
│   │   - CORS habilitado para todos los origenes
│   │
│   ├── predict.py                    # Motor de prediccion
│   │   - Class ModelPredictor
│   │   - Carga modelo Keras .h5
│   │   - Preprocesamiento: 224x224, float32, normalizar /255
│   │   - Prediccion con TensorFlow/Keras
│   │   - Retorna nombre de categoria + porcentaje de confianza
│   │
│   ├── retrain.py                    # Reentrenamiento
│   │   - Class ModelRetrainer
│   │   - Transfer learning: congela 70% capas iniciales
│   │   - Data augmentation: rotacion, shift, shear, zoom, flip, brillo
│   │   - Argumentos CLI: --epochs, --batch-size, --lr
│   │   - Guarda respaldo del modelo anterior
│   │
│   ├── app.php                       # Bridge PHP-Flask
│   │   - Recibe imagen del frontend
│   │   - Valida formato (jpg/png) y tamano (max 5MB)
│   │   - Reenvia a Flask via cURL
│   │   - Guarda registro en base de datos
│   │   - Retorna JSON al frontend
│   │
│   ├── requirements.txt              # Dependencias Python
│   ├── keras_model.h5                # Modelo entrenado (5 categorias, 224x224)
│   ├── labels.txt                    # Etiquetas de categorias
│   └── uploads/                      # Imagenes subidas para clasificar
│
├── includes/                         # MODULO: Componentes compartidos (8 archivos)
│   ├── conexion.php                  # Conexion PDO a MySQL
│   │   - Define constantes DB_HOST, DB_NAME, DB_USER, DB_PASS
│   │   - Define BASE_URL
│   │   - Funcion getConexion()
│   │
│   ├── auth.php                      # Sistema de autenticacion
│   │   - session_start() y verificacion de sesion
│   │   - estaAutenticado(): verifica si hay sesion activa
│   │   - esAdmin(): verifica si el usuario es ADMIN
│   │   - requerirAuth(): redirige a login si no autenticado
│   │   - requerirAdmin(): redirige si no es admin
│   │   - redireccionarSiAutenticado(): redirige a dashboard si ya hay sesion
│   │
│   ├── funciones.php                 # Funciones helper
│   │   - obtenerTotalClasificaciones($pdo, $id_usuario)
│   │   - obtenerUltimaClasificacion($pdo, $id_usuario)
│   │   - obtenerTotalUsuarios($pdo)
│   │   - obtenerClasificacionesPorCategoria($pdo)
│   │   - obtenerUsuariosMasActivos($pdo, $limite)
│   │   - obtenerClasificacionesPorFecha($pdo, $inicio, $fin)
│   │   - limpiarDato($dato): sanitiza input
│   │   - enviarCorreo($para, $asunto, $mensajeHTML)
│   │
│   ├── header.php                    # Cabecera HTML comun
│   │   - DOCTYPE, lang="es"
│   │   - Bootstrap 5.3.2 CSS (CDN)
│   │   - Bootstrap Icons (CDN)
│   │   - DataTables CSS (condicional)
│   │   - style.css, animations.css
│   │   - CSS condicional: dashboard.css, auth.css, admin.css
│   │   - Titulo dinamico via $titulo
│   │
│   ├── footer.php                    # Scripts comunes
│   │   - jQuery 3.7.1 (skippable via $skip_jquery)
│   │   - Bootstrap JS bundle (CDN)
│   │   - SweetAlert2 (CDN)
│   │   - DataTables JS (condicional)
│   │   - app.js
│   │   - JS condicional: auth.js, admin.js, entrenamiento.js
│   │
│   ├── sidebar.php                   # Barra de navegacion lateral (push menu)
│   │   - Logo EcoVision AI
│   │   - Enlaces: Dashboard, Clasificador IA, Historial, Perfil
│   │   - Enlaces admin: Panel Admin, Usuarios, Reportes, Entrenar Modelo
│   │   - Boton cerrar sesion
│   │   - Boton cerrar sidebar (visible solo en movil/tablet)
│   │   - Iconos Bootstrap Icons en cada enlace
│   │
│   └── mail_config.php               # Configuracion SMTP
│       - Servidor: smtp.gmail.com:587
│       - Seguridad: TLS
│       - Autenticacion: app password
│       - From: configurable
│
├── assets/                           # MODULO: Recursos estaticos
│   ├── css/
│   │   ├── style.css                 # Estilos principales (1614 lineas)
│   │   │   - Variables CSS: colores, sidebar width, border-radius
│   │   │   - Sidebar: fixed, push menu, overlay, responsive
│   │   │   - Main content: layout, transiciones push
│   │   │   - Navbar: estilos, boton toggle
│   │   │   - Stat cards: glassmorphism, hover effects
│   │   │   - Tablas: DataTables estilizado
│   │   │   - Botones, formularios, modales
│   │   │   - Scroll-to-top
│   │   │   - Dark mode
│   │   │   - Responsive: 5 breakpoints (1400, 1199, 991, 767, 575, 400px)
│   │   │   - Print styles
│   │   │
│   │   ├── dashboard.css             # Estilos especificos dashboard (39 lineas)
│   │   │   - Badges de categoria con colores
│   │   │   - Tabla de dashboard
│   │   │   - Paginacion DataTables
│   │   │
│   │   ├── auth.css                  # Estilos de autenticacion (464 lineas)
│   │   │   - Glassmorphism en tarjetas
│   │   │   - Gradient background
│   │   │   - Formularios centrados
│   │   │   - Responsive auth
│   │   │
│   │   ├── admin.css                 # Estilos admin (643 lineas)
│   │   │   - Stat cards admin
│   │   │   - Tabla de usuarios
│   │   │   - Scrollbar personalizada
│   │   │   - Modal de edicion
│   │   │   - Panel de entrenamiento
│   │   │   - Responsive admin
│   │   │
│   │   └── animations.css            # Animaciones (283 lineas)
│   │       - Keyframes: fadeIn, fadeInUp, slideInLeft, slideInRight
│   │       - Keyframes: bounce, pulse, shimmer, glow
│   │       - Clases: anim-fade-in, anim-fade-in-up, anim-slide-in
│   │       - Scroll-triggered con IntersectionObserver
│   │
│   └── js/
│       ├── app.js                    # JavaScript principal (490 lineas)
│       │   - DOMContentLoaded: inicializa todos los modulos
│       │   - initSidebarToggle(): push menu con toggle en navbar
│       │   - handleSidebarOverlay(): overlay en movil/tablet
│       │   - initSidebarNavClose(): cerrar sidebar al hacer clic en enlace
│       │   - initPopovers(), initTooltips()
│       │   - Auto-dismiss alerts
│       │   - initBackToTop(): scroll-to-top con boton flotante
│       │   - initDarkMode(): dark mode con persistencia localStorage
│       │   - initSmoothScroll(), initRippleEffect()
│       │   - initKeyboardShortcuts(): Ctrl+K para buscar, Escape para cerrar
│       │
│       ├── auth.js                   # Validacion de autenticacion (257 lineas)
│       │   - Validacion de formularios en login y registro
│       │   - Show/hide password con toggle
│       │   - Remember email en localStorage
│       │   - Submit con spinner y deshabilitacion
│       │
│       ├── clasificador.js           # Clasificador IA (114 lineas)
│       │   - Drag-and-drop con eventos dragover, dragleave, drop
│       │   - Vista previa de imagen con FileReader
│       │   - Fetch a api_ai/app.php con FormData
│       │   - Resultado con SweetAlert2 (categoria + confianza)
│       │   - Manejo de errores con SweetAlert2
│       │   - Boton de reintentar
│       │
│       ├── dashboard.js              # Dashboard (9 lineas)
│       │   - Animacion fade-in de tarjetas
│       │
│       ├── admin.js                  # Admin (293 lineas)
│       │   - CRUD de usuarios con fetch
│       │   - Activar/desactivar con SweetAlert2
│       │   - Busqueda en tabla
│       │   - Exportacion CSV
│       │   - Graficos Chart.js admin
│       │
│       └── entrenamiento.js          # Entrenamiento IA (314 lineas)
│           - 5 zonas drag-and-drop individuales por categoria
│           - Validacion de archivos (formato, tamano)
#           - Vista previa de cada imagen seleccionada
#           - Envio del formulario con barra de progreso
#           - Gestion de categorias
#
├── database/
#   └── ecovision_ai.sql              # Esquema completo de base de datos
#       - CREATE DATABASE ecovision_ai
#       - Tabla usuarios con datos por defecto (admin)
#       - Tabla clasificaciones con FK a usuarios
#       - Indices y constraints
#
├── uploads/
#   └── clasificaciones/              # Imagenes clasificadas por usuarios
#       - Archivos UUID-nombre (.jpg, .png)
#
└── vendor/                           # Dependencias Composer
    └── phpmailer/phpmailer/          # PHPMailer 7.1
```

---

## Flujo de clasificacion

### Paso a paso (usuario regular)

1. **Inicio de sesion:** El usuario ingresa con su email y contrasena en `login.php`
2. **Dashboard:** Al ingresar, ve el dashboard con graficos de su actividad y tarjetas con resumen
3. **Ir al clasificador:** Hace clic en "Clasificador IA" en el sidebar
4. **Subir imagen:** Arrastra una imagen a la zona de drop o hace clic para seleccionar
5. **Previsualizar:** La imagen se muestra en pantalla
6. **Clasificar:** Hace clic en "Clasificar"
7. **Procesamiento:**
   - JavaScript envía la imagen a `api_ai/app.php?action=predict`
   - PHP valida la imagen (formato: jpg/png, tamano max: 5MB)
   - PHP guarda la imagen en `uploads/clasificaciones/` con nombre UUID
   - PHP reenvia la imagen a Flask via cURL (`http://127.0.0.1:5000/predict`)
   - Flask recibe la imagen, la pasa a `predict.py`
   - `predict.py` carga el modelo Keras, redimensiona a 224x224, normaliza y predice
   - Flask retorna JSON con `categoria` y `confianza`
   - PHP guarda el resultado en la tabla `clasificaciones`
   - PHP retorna JSON al frontend
8. **Resultado:** SweetAlert2 muestra la categoria detectada con su color correspondiente y el porcentaje de confianza
9. **Historial:** El usuario puede ver su historial completo en la pagina "Historial" con DataTable, filtros y opciones de detalle/eliminacion

---

## Flujo de reentrenamiento

### Paso a paso (administrador)

1. **Acceder al panel:** El admin navega a "Entrenar Modelo" en el sidebar
2. **Subir imagenes:** Arrastra imagenes a cada una de las 5 zonas (Plastico, Papel y carton, Vidrio, Metal, Organico)
3. **Previsualizar:** Cada imagen subida se muestra con miniatura en su categoria
4. **Enviar:** Hace clic en "Entrenar Modelo"
5. **Procesamiento:**
   - PHP guarda las imagenes en `api_ai/training_data/[categoria]/`
   - PHP ejecuta `python retrain.py` con argumentos (epochs, batch-size, lr)
   - `retrain.py` carga el modelo existente, congela el 70% de las capas
   - Aplica data augmentation (rotacion, desplazamiento, shear, zoom, flip, brillo)
   - Entrena con las nuevas imagenes
   - Guarda respaldo del modelo anterior
   - Guarda el nuevo modelo en `keras_model.h5`
   - Actualiza `labels.txt` si es necesario
6. **Resultado:** El admin ve el resumen del entrenamiento (epochs, accuracy, loss)

---

## Base de datos

### Esquema

**Base de datos:** `ecovision_ai`
**Charset:** `utf8mb4`
**Conexion:** PDO (PHP Data Objects)

### Tabla: `usuarios`

Registra a todos los usuarios del sistema, tanto administradores como usuarios regulares.

| Columna | Tipo | Restricciones | Descripcion |
|---|---|---|---|
| `id_usuario` | INT | AUTO_INCREMENT, PRIMARY KEY | Identificador unico del usuario |
| `nombres` | VARCHAR(100) | NOT NULL | Nombre(s) del usuario |
| `apellidos` | VARCHAR(100) | NOT NULL | Apellidos del usuario |
| `correo` | VARCHAR(150) | NOT NULL, UNIQUE | Correo electronico (usado para login) |
| `password` | VARCHAR(255) | NOT NULL | Hash bcrypt de la contrasena |
| `rol` | ENUM('ADMIN','USUARIO') | NOT NULL, DEFAULT 'USUARIO' | Rol del usuario en el sistema |
| `estado` | TINYINT(1) | NOT NULL, DEFAULT 1 | 1=activo, 0=inactivo (los inactivos no pueden iniciar sesion) |
| `fecha_registro` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Fecha y hora de registro |

**Usuario administrador por defecto:**
- Email: `admin@ecovision.com`
- Password: `password` (hash bcrypt)
- Rol: `ADMIN`

### Tabla: `clasificaciones`

Almacena cada clasificacion realizada por los usuarios. Cada registro contiene la imagen, la categoria detectada por la IA, el porcentaje de confianza, y la referencia al usuario que la realizo.

| Columna | Tipo | Restricciones | Descripcion |
|---|---|---|---|
| `id_clasificacion` | INT | AUTO_INCREMENT, PRIMARY KEY | Identificador unico de la clasificacion |
| `id_usuario` | INT | FOREIGN KEY → usuarios(id_usuario) | Usuario que realizo la clasificacion |
| `imagen` | VARCHAR(255) | NOT NULL | Nombre del archivo de imagen (UUID) |
| `categoria_detectada` | VARCHAR(100) | NOT NULL | Categoria detectada por el modelo |
| `porcentaje_confianza` | DECIMAL(5,2) | NOT NULL | Porcentaje de confianza (0.00 - 100.00) |
| `fecha_clasificacion` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Fecha y hora de la clasificacion |

### Tabla: `reset_codigos` (creada dinamicamente)

Se crea automaticamente la primera vez que un usuario solicita recuperacion de contrasena. Almacena codigos de verificación de 6 digitos con expiracion de 15 minutos.

| Columna | Tipo | Restricciones | Descripcion |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Identificador unico |
| `id_usuario` | INT | FOREIGN KEY → usuarios(id_usuario) | Usuario que solicito el reset |
| `codigo` | VARCHAR(6) | NOT NULL | Codigo de 6 digitos |
| `expira` | DATETIME | NOT NULL | Fecha y hora de expiracion (15 min desde creacion) |
| `usado` | TINYINT(1) | DEFAULT 0 | 0=no usado, 1=ya utilizado |

---

## Rol de usuarios

### Usuario regular (rol: USUARIO)

El usuario regular tiene acceso a las siguientes funcionalidades:

1. **Dashboard personal (`dashboard/dashboard.php`):**
   - Ver total de clasificaciones realizadas
   - Ver ultima clasificacion con categoria y confianza
   - Grafico donut: distribucion de categorias clasificadas
   - Grafico de barras: actividad de los ultimos 7 dias
   - Grafico combo: clasificaciones diarias + confianza promedio
   - Grafico horizontal: confianza promedio por categoria
   - Tabla con las ultimas 5 clasificaciones

2. **Clasificador IA (`dashboard/clasificador.php`):**
   - Subir imagenes mediante drag-and-drop o selector de archivos
   - Ver previsualizacion de la imagen
   - Clasificar la imagen y ver resultado (categoria + confianza)
   - Reintentar con otra imagen

3. **Historial (`dashboard/historial.php`):**
   - Ver todas las clasificaciones realizadas
   - Buscar por texto en toda la tabla
   - Filtrar por categoria (selector modal con tarjetas de colores)
   - Filtrar por fecha (selector de fecha)
   - Ver detalle de cada clasificacion (modal con imagen, categoria coloreada, barra de confianza)
   - Eliminar clasificaciones individuales (con confirmacion)
   - Ordenar por cualquier columna
   - Paginacion configurable (5, 10, 25, 50, Todos)

4. **Perfil (`dashboard/perfil.php`):**
   - Actualizar nombre, apellidos y correo
   - Cambiar contrasena
   - Ver resumen de actividad (total clasificaciones, ultima actividad)

### Administrador (rol: ADMIN)

El administrador tiene acceso a todo lo del usuario regular, mas:

1. **Dashboard admin (`admin/dashboard.php`):**
   - Ver total de usuarios registrados
   - Ver total de clasificaciones globales
   - Grafico donut: categorias de todos los usuarios
   - Grafico de linea: actividad global de los ultimos 7 dias
   - Grafico de barras: confianza promedio por categoria (global)
   - Tabla con los 5 usuarios mas activos

2. **Gestion de usuarios (`admin/usuarios.php`):**
   - Listar todos los usuarios
   - Buscar usuarios por nombre o email
   - Activar/desactivar cuentas de usuario
   - Cambiar roles (USUARIO ↔ ADMIN)

3. **Reportes (`admin/reportes.php`):**
   - Filtrar clasificaciones por rango de fechas
   - Ver desglose por categoria
   - Exportar datos a CSV

4. **Reentrenamiento (`admin/entrenamiento.php`):**
   - Subir imagenes de entrenamiento para cada categoria
   - 5 zonas de drop independientes (una por categoria)
   - Ejecutar reentrenamiento del modelo con transfer learning
   - Ver progreso del entrenamiento

---

## Componentes del frontend

### Sistema de paginas

Todas las paginas del sistema siguen la misma estructura:

```
header.php (apertura HTML, CSS, <head>)
sidebar.php (barra de navegacion lateral)
  └── <div class="main-content">
        └── <navbar> (titulo de pagina + boton toggle + nombre usuario)
        └── Contenido especifico de la pagina
      </div>
footer.php (scripts JS, cierre HTML)
```

### Sidebar (push menu)

El sidebar es un componente de navegacion lateral que se comporta de manera diferente segun el tamano de pantalla:

**Escritorio (>=992px):**
- Sidebar visible por defecto, con todos los enlaces y texto completo
- Push menu: al abrir/cerrar el sidebar, el contenido principal se desplaza
- Boton toggle en el navbar (icono hamburguesa) para colapsar/expandir
- Sin overlay ni boton cerrar

**Tablet (768-991px):**
- Sidebar oculto por defecto (colapsado)
- Sin push: el sidebar se superpone con un overlay semitransparente
- Boton toggle en el navbar para abrir
- Boton cerrar (X) dentro del sidebar
- Al hacer clic en un enlace del sidebar, este se cierra automaticamente

**Celular (<768px):**
- Sidebar oculto por defecto (colapsado)
- Sin push: se superpone con overlay
- Modo icon-only: sidebar de 70px de ancho, solo muestra iconos, texto oculto
- Boton toggle en el navbar para abrir
- Sin boton cerrar (se cierra tocando el overlay o un enlace)
- Logo reducido (solo icono, sin texto)

**Estados del sidebar:**
- Clase `.collapsed` presente: sidebar oculto
- Clase `.collapsed` ausente: sidebar visible
- Transicion via `transform: translateX()` para deslizar
- En desktop se agrega `margin-left` al `.main-content` para el efecto push

### Graficos (Chart.js)

El sistema utiliza Chart.js 4 para los siguientes graficos:

| Grafico | Tipo | Ubicacion | Datos |
|---|---|---|---|
| Categorias | Doughnut | dashboard + admin | Conteo por categoria con colores personalizados |
| Actividad diaria | Bar | dashboard | Clasificaciones por dia (ultimos 7 dias) |
| Clasificaciones + Confianza | Combo (bar + line) | dashboard | Barras de clasificaciones diarias + linea de confianza promedio |
| Confianza por categoria | Horizontal bar | dashboard + admin | Promedio de confianza por cada categoria |
| Actividad 7 dias | Line | admin | Clasificaciones por dia (ultimos 7 dias, admin global) |

Los graficos usan `maintainAspectRatio: false` con altura flexible y `max-height` en movil.

### Tabla de historial (DataTables)

La pagina de historial utiliza jQuery DataTables 1.13.6 con las siguientes caracteristicas:

- **Responsive:** Las columnas se adaptan al ancho de pantalla. En movil aparece scroll horizontal.
- **Idioma:** Espanol (CDN es-ES.json)
- **Orden:** Por defecto ordenado por fecha descendente
- **Paginacion:** 10 registros por pagina, configurable (5, 10, 25, 50, Todos)
- **Columnas:** Imagen (miniatura 48px), Categoria (badge coloreado), Confianza (barra de progreso + porcentaje), Fecha, Accion (botones detalle + eliminar)
- **Filtros:** Busqueda de texto, categoria (modal con tarjetas de colores), fecha (input date)
- **Modal detalle:** Muestra la imagen completa, categoria con badge de color, barra de confianza, fecha e ID
- **Eliminacion:** Confirmacion con SweetAlert2 antes de eliminar

### Estilos

El sistema utiliza una paleta de colores verde oscuro con estilo glassmorphism:

- **Color primario:** `#198754` (Bootstrap success green)
- **Sidebar:** Fondo oscuro (`#1a1a2e`) con hover (`#16213e`)
- **Tarjetas:** Fondo blanco con sombras suaves, border-radius 12-16px
- **Efectos:** Hover con elevacion, transiciones suaves (0.3s ease)
- **Categorias:**
  - Plastico: Azul (`#0d6efd`)
  - Papel y carton: Verde (`#198754`)
  - Vidrio: Amarillo (`#ffc107`)
  - Metal: Rojo (`#dc3545`)
  - Organico: Naranja (`#fd7e14`)

### Responsive design

El sistema tiene 6 breakpoints:

| Breakpoint | Target | Cambios principales |
|---|---|---|
| <400px | Telefonos muy pequenos | Navbar compacto, padding reducido, tarjetas apiladas |
| <576px | Telefonos | Botones full-width, filtros apilados, modal categorias full-width |
| <768px | Telefonos grandes | Sidebar icon-only 70px, graficos max-height 180px, DataTable compacto |
| <992px | Tablets | Sidebar sin push, overlay, boton cerrar visible, sidebar completo |
| <1200px | Laptops pequenas | Sidebar 240px (vs 260px default) |
| <1400px | Escritorio | Ajustes menores de espaciado |

### Dark mode

El sistema incluye modo oscuro activable mediante un toggle en la esquina inferior derecha. La preferencia se guarda en `localStorage` y persiste entre sesiones. Las variables CSS se actualizan para cambiar fondos, colores de texto y sombras.

---

## Componentes del backend

### Sistema de rutas (PHP)

Todas las paginas PHP incluyen los siguientes archivos base:

```php
require_once '../includes/conexion.php';  // Conexion a BD y constantes
require_once '../includes/auth.php';      // Funciones de autenticacion
require_once '../includes/funciones.php'; // Funciones helper
requireAuth();                             // Verifica sesion activa

$titulo = 'Nombre Pagina';                 // Titulo de la pagina
include_once '../includes/header.php';     // Cabecera HTML
include_once '../includes/sidebar.php';    // Barra de navegacion
// ... contenido de la pagina ...
include_once '../includes/footer.php';     // Scripts JS
```

### Autenticacion (auth.php)

El sistema usa sesiones PHP nativas:

1. **Inicio de sesion:** `login.php` verifica email + password contra la BD usando `password_verify()`
2. **Sesion:** Se almacenan `usuario_id`, `usuario_nombres`, `usuario_correo`, `usuario_rol` en `$_SESSION`
3. **Verificacion:** Cada pagina protegida llama a `requerirAuth()` que redirige a `login.php` si no hay sesion
4. **Roles:** `esAdmin()` verifica `$_SESSION['usuario_rol'] === 'ADMIN'`
5. **Estado:** Se verifica que la cuenta este activa (`estado = 1`) al iniciar sesion
6. **Cierre:** `logout.php` destruye la sesion con `session_destroy()` y redirige a login

### Seguridad

- Contrasenas almacenadas con `password_hash()` (bcrypt)
- Entrada sanitizada con `htmlspecialchars(stripslashes(trim()))`
- Consultas parametrizadas via PDO (prepared statements)
- Validacion de archivos (formato, tamano) antes de procesar
- Proteccion de rutas admin con `requerirAdmin()`
- Codigos de recuperacion con expiracion de 15 minutos

---

## API de inteligencia artificial

### Endpoint Flask

**URL:** `http://127.0.0.1:5000/predict`

**Metodo:** `POST`

**Content-Type:** `multipart/form-data`

**Campo:** `image` (archivo de imagen)

**Respuesta exitosa (200):**
```json
{
  "categoria": "Plastico",
  "confianza": 99.87,
  "imagen": "uuid-imagen.jpg"
}
```

**Respuesta de error (400/500):**
```json
{
  "error": "Descripcion del error"
}
```

**Health check:** `GET http://127.0.0.1:5000/health`
```json
{
  "status": "ok"
}
```

### PHP Bridge

**URL:** `../api_ai/app.php?action=predict`

**Metodo:** `POST`

**Content-Type:** `multipart/form-data`

**Campo:** `imagen` (archivo de imagen)

**Validaciones:**
- Formato permitido: `jpg`, `jpeg`, `png`
- Tamano maximo: 5MB
- La imagen se guarda con nombre UUID en `uploads/clasificaciones/`

**Flujo interno:**
1. Recibe la imagen del frontend
2. Valida formato y tamano
3. Genera nombre UUID para el archivo
4. Guarda la imagen en `uploads/clasificaciones/`
5. Prepara cURL hacia `http://127.0.0.1:5000/predict`
6. Envia la imagen a Flask
7. Recibe la respuesta JSON de Flask
8. Inserta registro en tabla `clasificaciones` (id_usuario, nombre_imagen, categoria, confianza)
9. Retorna JSON al frontend con categoria, confianza y nombre de imagen

### Modelo de IA

- **Formato:** Keras HDF5 (`.h5`)
- **Entrada:** Imagen RGB de 224x224 pixeles
- **Preprocesamiento:** Redimensionar a 224x224, convertir a float32, normalizar dividiendo por 255.0
- **Salida:** 5 categorias con probabilidades
- **Framework:** TensorFlow/Keras modo legacy (`tf_keras`)
- **Categorias:**
  - `0 Plastico`
  - `1 Papel y carton`
  - `2 Vidrio`
  - `3 Metal`
  - `4 Organico`

### Reentrenamiento

El script `retrain.py` acepta los siguientes argumentos CLI:

```
python retrain.py --epochs 10 --batch-size 16 --lr 0.0001
```

**Parametros:**
- `--epochs`: Numero de epocas de entrenamiento (default: 10)
- `--batch-size`: Tamano del lote (default: 16)
- `--lr`: Tasa de aprendizaje (default: 0.0001)

**Proceso:**
1. Carga el modelo existente desde `keras_model.h5`
2. Congela el 70% de las capas iniciales (no se reentrenan)
3. Aplica data augmentation: rotacion 20°, desplazamiento 20%, shear 20%, zoom 20%, volteo horizontal, ajuste de brillo
4. Entrena con las imagenes de `training_data/` organizadas por subdirectorios (uno por categoria)
5. Guarda respaldo del modelo anterior en `backups/`
6. Guarda el nuevo modelo en `keras_model.h5`
7. Imprime resumen del entrenamiento en JSON

---

## Sistema de autenticacion

### Registro

1. El usuario completa el formulario en `register.php` con nombres, apellidos, correo y contrasena
2. PHP valida que el correo no exista ya en la BD
3. La contrasena se hashea con `password_hash(PASSWORD_DEFAULT)` (bcrypt)
4. Se inserta el registro con rol `USUARIO` y estado `1` (activo)
5. Se redirige a `login.php` con mensaje de exito

### Inicio de sesion

1. El usuario ingresa email y contrasena en `login.php`
2. PHP busca el usuario por correo en la BD
3. Verifica la contrasena con `password_verify()`
4. Verifica que la cuenta este activa (`estado = 1`)
5. Si todo es correcto, crea la sesion con los datos del usuario
6. Redirige a `dashboard/dashboard.php`
7. Si hay error, muestra mensaje con SweetAlert2

### Recuperacion de contrasena

1. El usuario ingresa su correo en `forgot_password.php`
2. PHP busca el usuario por correo
3. Genera un codigo aleatorio de 6 digitos
4. Crea la tabla `reset_codigos` si no existe
5. Guarda el codigo con expiracion de 15 minutos
6. Envia el codigo por correo electronico via PHPMailer + Gmail SMTP
7. Redirige a `reset_password.php` donde el usuario ingresa el codigo y establece una nueva contrasena
8. El codigo se marca como usado despues del reseteo exitoso

### Cierre de sesion

1. `logout.php` llama a `session_start()` y `session_destroy()`
2. Redirige a `login.php`

---

## Sidebar push menu

### Comportamiento por tamano de pantalla

| Tamano | Rango | Sidebar | Push | Ancho | Boton cerrar | Overlay |
|---|---|---|---|---|---|---|
| Escritorio | >=992px | Visible por defecto | Si (empuja contenido) | 260px (240px <1200px) | No | No |
| Tablet | 768-991px | Oculto por defecto | No (superpone) | 240px | Si | Si |
| Celular | <768px | Oculto por defecto | No (superpone) | 70px (solo iconos) | No | Si |

### Estados

- **`.collapsed` AUSENTE:** Sidebar visible. En desktop: contenido empujado. En tablet/celular: overlay activo.
- **`.collapsed` PRESENTE:** Sidebar oculto. Contenido a ancho completo.

### Transiciones

- **Sidebar:** `transform: translateX()` con transicion de 0.3s ease
- **Contenido:** `margin-left` con transicion de 0.3s ease (solo desktop)
- **Overlay:** `opacity` con transicion de 0.3s ease, pointer-events para交互

### Boton toggle

- Inyectado dinamicamente por JavaScript (`initSidebarToggle()`) dentro del navbar
- Visible en todos los tamanos de pantalla
- Icono hamburguesa (`bi-list`)
- Posicionado como primer elemento del `.container-fluid` del navbar
- Clase: `sidebar-navbar-toggle`

### JavaScript

Las funciones que manejan el sidebar estan en `assets/js/app.js`:

- **`initSidebarToggle()`:** Inyecta el boton en el navbar, configura eventos de click para toggle y close, establece el estado inicial segun el tamano de pantalla
- **`handleSidebarOverlay()`:** Crea/elimina el overlay semitransparente, solo en pantallas <992px
- **`initSidebarNavClose()`:** Cierra el sidebar al hacer clic en un enlace de navegacion (solo en movil/tablet)

---

## Instalacion

### Requisitos previos

- XAMPP (Apache + PHP 8.x + MySQL 8.x) o entorno equivalente
- Python 3.12+
- Composer (gestor de dependencias PHP)
- Git (opcional)

### Pasos

#### 1. Clonar o copiar el proyecto

```bash
# Si usas Git:
git clone https://github.com/tu-usuario/EcoVisionAI.git C:\xampp\htdocs\EcoVisionAI

# O simplemente copia los archivos a C:\xampp\htdocs\EcoVisionAI
```

#### 2. Configurar la base de datos

```bash
# Opcion 1: Importar desde phpMyAdmin
# - Abre http://localhost/phpmyadmin
# - Crea una base de datos llamada "ecovision_ai"
# - Importa el archivo database/ecovision_ai.sql

# Opcion 2: Importar desde linea de comandos
mysql -u root -p < database/ecovision_ai.sql
```

#### 3. Configurar la conexion a base de datos

Editar `includes/conexion.php` si es necesario:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecovision_ai');
define('DB_USER', 'root');
define('DB_PASS', '');  // Tu contrasena de MySQL si la tienes
```

#### 4. Instalar dependencias PHP

```bash
cd C:\xampp\htdocs\EcoVisionAI
composer install
```

Esto instalara PHPMailer 7.1 en la carpeta `vendor/`.

#### 5. Instalar dependencias Python

```bash
cd C:\xampp\htdocs\EcoVisionAI\api_ai
pip install -r requirements.txt
```

Esto instalara Flask, TensorFlow, numpy, Pillow y flask-cors.

#### 6. Configurar el envio de correo

Editar `includes/mail_config.php`:

```php
$mail->Username = 'tu-correo@gmail.com';
$mail->Password = 'xxxx xxxx xxxx xxxx';  // App password de Gmail
$mail->setFrom('tu-correo@gmail.com', 'EcoVision AI');
```

#### 7. Iniciar el servidor Flask

```bash
cd C:\xampp\htdocs\EcoVisionAI\api_ai
python app.py
```

El servidor Flask debe quedar corriendo en `http://127.0.0.1:5000`. Manten esta terminal abierta.

#### 8. Iniciar XAMPP

- Abre XAMPP Control Panel
- Inicia Apache
- Inicia MySQL

#### 9. Acceder a la aplicacion

Abre tu navegador y visita:

```
http://localhost/EcoVisionAI
```

#### 10. Credenciales por defecto

**Administrador:**
- Email: `admin@ecovision.com`
- Password: `password`

**Usuario regular:**
- Registrarse desde la pagina de login

---

## Configuracion de correo

Para que funcione la recuperacion de contrasena, necesitas configurar PHPMailer con una cuenta de Gmail:

1. Activar la verificacion en dos pasos en tu cuenta de Google
2. Generar una contrasena de aplicacion en: https://myaccount.google.com/apppasswords
3. Editar `includes/mail_config.php` con tu correo y la contrasena de aplicacion

**Importante:** No uses tu contrasena personal de Gmail. Usa siempre una "App Password" generada especificamente.

---

## Licencia

Proyecto desarrollado con fines educativos y de investigacion. Uso libre para aprendizaje y referencia.

---

*Documentacion generada el 30 de mayo de 2026.*
