CREATE DATABASE IF NOT EXISTS ecovision_ai DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ecovision_ai;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('ADMIN','USUARIO') DEFAULT 'USUARIO',
    estado TINYINT DEFAULT 1,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clasificaciones (
    id_clasificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    categoria_detectada VARCHAR(100) NOT NULL,
    porcentaje_confianza DECIMAL(5,2) NOT NULL,
    fecha_clasificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE IF NOT EXISTS entrenamientos (
    id_entrenamiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    estado ENUM('en_proceso','completado','error') DEFAULT 'en_proceso',
    epocas_solicitadas INT DEFAULT 10,
    epocas_completadas INT NULL,
    precision_final DECIMAL(5,2) NULL,
    precision_mejor DECIMAL(5,2) NULL,
    precision_val DECIMAL(5,2) NULL,
    perdida_final DECIMAL(8,4) NULL,
    total_imagenes INT DEFAULT 0,
    imagenes_train INT NULL,
    imagenes_val INT NULL,
    categorias JSON NULL,
    batch_size INT NULL,
    steps_por_epoca INT NULL,
    augmentacion VARCHAR(20) NULL,
    historial_acc JSON NULL,
    historial_loss JSON NULL,
    mensaje_error TEXT NULL,
    id_admin INT NULL,
    FOREIGN KEY (id_admin) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

-- Usuario admin por defecto (password: password)
INSERT IGNORE INTO usuarios (nombres, apellidos, correo, password, rol, estado) VALUES
('Admin', 'EcoVision', 'admin@ecovision.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN', 1);
