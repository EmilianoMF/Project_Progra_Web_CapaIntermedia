-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS mundialesDB;
USE mundialesDB;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    foto BLOB, 
    genero ENUM('Male','Female','Otro') NOT NULL,
    genero_personalizado VARCHAR(50) NULL,
    pais_nacimiento VARCHAR(50) NOT NULL,
    nacionalidad VARCHAR(50) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL, 
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rol ENUM('Admin','Usuario') NOT NULL DEFAULT 'Usuario'
);



-- Tabla de administradores
CREATE TABLE administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Tabla de mundiales
CREATE TABLE mundiales (
    id_mundial INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    anio YEAR NOT NULL,
    sede VARCHAR(100) NOT NULL,
    logotipo LONGBLOB,
    imagen LONGBLOB, 
    reseña TEXT
);


-- Tabla de categorías
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(50) NOT NULL
);

-- Tabla de publicaciones
CREATE TABLE publicaciones (
    id_publicacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_mundial INT NOT NULL,
    id_categoria INT NOT NULL,
    seleccion VARCHAR(100) NULL,
    descripcion TEXT,
    fecha_elaboracion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion TIMESTAMP NULL,
    aprobado BOOLEAN DEFAULT FALSE,
    rechazado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_mundial) REFERENCES mundiales(id_mundial),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);



CREATE TABLE publicacion_archivos (
    id_archivo INT AUTO_INCREMENT PRIMARY KEY,
    id_publicacion INT NOT NULL,
    contenido LONGBLOB NOT NULL,
    tipo ENUM('imagen', 'video') NOT NULL,
    orden INT DEFAULT 1,              -- para controlar el orden que se muestran
    FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion)
);


-- Tabla de interacciones (likes y comentarios)
CREATE TABLE interacciones (
    id_interaccion INT AUTO_INCREMENT PRIMARY KEY,
    id_publicacion INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo ENUM('Like','Comentario') NOT NULL,
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Tabla de estadísticas (vistas)
CREATE TABLE estadisticas (
    id_estadistica INT AUTO_INCREMENT PRIMARY KEY,
    id_publicacion INT NOT NULL,
    vistas INT DEFAULT 0,
    likes INT DEFAULT 0,
    FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion)
);
