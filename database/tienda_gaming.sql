-- =====================================================
-- Base de Datos: tienda_gaming
-- Sistema Web de Venta de Videojuegos
-- =====================================================

CREATE DATABASE IF NOT EXISTS tienda_gaming
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tienda_gaming;

-- ---------------------------------------------------
-- Tabla: Usuario
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS Usuario (
    id_usuario    INT          AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    correo        VARCHAR(150) NOT NULL UNIQUE,
    contrasena    VARCHAR(255) NOT NULL,
    rol           ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
    codigo_2fa    VARCHAR(10)  DEFAULT NULL,
    estado_2fa    TINYINT(1)   NOT NULL DEFAULT 0,
    fecha_registro DATETIME    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Tabla: Categoria
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS Categoria (
    id_categoria     INT          AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(100) NOT NULL,
    descripcion      TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Tabla: Producto
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS Producto (
    id_producto  INT            AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT            NOT NULL,
    nombre       VARCHAR(150)   NOT NULL,
    marca        VARCHAR(100),
    descripcion  TEXT,
    precio       DECIMAL(10,2)  NOT NULL,
    stock        INT            NOT NULL DEFAULT 0,
    imagen       VARCHAR(255)   DEFAULT 'placeholder.jpg',
    estado       ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    CONSTRAINT fk_prod_cat FOREIGN KEY (id_categoria)
        REFERENCES Categoria(id_categoria) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Tabla: Venta
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS Venta (
    id_venta     INT           AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT           NOT NULL,
    fecha        DATETIME      DEFAULT CURRENT_TIMESTAMP,
    total        DECIMAL(10,2) NOT NULL,
    estado_venta ENUM('pendiente','completada','cancelada') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_venta_usr FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Tabla: Detalle_Venta
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS Detalle_Venta (
    id_detalle  INT           AUTO_INCREMENT PRIMARY KEY,
    id_venta    INT           NOT NULL,
    id_producto INT           NOT NULL,
    cantidad    INT           NOT NULL,
    subtotal    DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_det_venta FOREIGN KEY (id_venta)
        REFERENCES Venta(id_venta) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_det_prod  FOREIGN KEY (id_producto)
        REFERENCES Producto(id_producto) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Tabla: Favorito
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS Favorito (
    id_favorito INT      AUTO_INCREMENT PRIMARY KEY,
    id_usuario  INT      NOT NULL,
    id_producto INT      NOT NULL,
    fecha       DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fav_usr  FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_fav_prod FOREIGN KEY (id_producto)
        REFERENCES Producto(id_producto) ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_favorito (id_usuario, id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DATOS DE EJEMPLO
-- =====================================================

-- Categorías
INSERT INTO Categoria (nombre_categoria, descripcion) VALUES
('PlayStation',  'Juegos exclusivos y accesorios para consolas PlayStation'),
('Xbox',         'Títulos y accesorios para la familia Xbox'),
('Nintendo',     'Videojuegos y accesorios Nintendo Switch'),
('PC Gaming',    'Juegos para PC, hardware y periféricos gaming'),
('Accesorios',   'Controles, headsets, sillas y más accesorios gaming');

-- Productos de ejemplo
INSERT INTO Producto (id_categoria, nombre, marca, descripcion, precio, stock) VALUES
(1, 'God of War Ragnarök',          'Sony Santa Monica',  'Kratos y Atreus enfrentan el Ragnarök en los reinos nórdicos.',          59.99, 25),
(1, 'Spider-Man 2',                 'Insomniac Games',    'Peter Parker y Miles Morales unen fuerzas contra Venom.',                 69.99, 18),
(1, 'Final Fantasy XVI',            'Square Enix',        'Un mundo oscuro donde Dominantes invocan poderosas Esencias.',            49.99, 12),
(2, 'Halo Infinite',                '343 Industries',     'El Master Chief regresa para salvar a la humanidad en Zeta Halo.',        49.99, 30),
(2, 'Forza Horizon 5',              'Playground Games',   'La mejor experiencia de carreras en mundo abierto, en México.',           59.99, 22),
(3, 'Zelda: Tears of the Kingdom',  'Nintendo',           'Link explora los cielos sobre Hyrule con nuevas habilidades.',            59.99, 20),
(3, 'Super Mario Odyssey',          'Nintendo',           'Mario y Cappy viajan por mundos únicos y mágicos.',                      49.99, 35),
(4, 'Cyberpunk 2077',               'CD Projekt Red',     'RPG de acción en Night City, un mundo cyberpunk sin igual.',              39.99, 50),
(4, 'Elden Ring',                   'FromSoftware',       'Vasto mundo abierto con combate desafiante. Co-creado con G.R.R. Martin.',59.99, 28),
(5, 'DualSense PS5',                'Sony',               'Control inalámbrico con haptic feedback y gatillos adaptativos.',         79.99, 15),
(5, 'Xbox Elite Controller Serie 2','Microsoft',          'Control profesional con palancas intercambiables y gatillos ajustables.',149.99,  8),
(5, 'HyperX Cloud Alpha',           'HyperX',             'Auriculares gaming con drivers de doble cámara y micrófono extraíble.',   99.99, 20);

-- NOTA: El usuario administrador se crea ejecutando setup.php
-- Credenciales por defecto: admin@gaming.com / admin123
