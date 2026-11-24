-- =====================================================================
--  BiblioNube - Sistema de préstamos de biblioteca
--  Base de datos: biblioteca  (MariaDB 10.x / MySQL 8)
--  Importar con:  mysql -h ENDPOINT -u USUARIO -p biblioteca < bd/biblioteca.sql
--  (La base "biblioteca" debe existir antes; este archivo NO la crea)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS prestamos;
DROP TABLE IF EXISTS libros;
DROP TABLE IF EXISTS lectores;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- Usuarios del sistema (bibliotecarios)
CREATE TABLE usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  usuario       VARCHAR(50)  NOT NULL UNIQUE,
  nombre        VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('admin','bibliotecario') NOT NULL DEFAULT 'bibliotecario',
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categorías de libros
CREATE TABLE categorias (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(80)  NOT NULL UNIQUE,
  descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Libros (catálogo)
CREATE TABLE libros (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  isbn         VARCHAR(20)  NOT NULL UNIQUE,
  titulo       VARCHAR(200) NOT NULL,
  autor        VARCHAR(150) NOT NULL,
  anio         SMALLINT,
  categoria_id INT,
  stock        INT NOT NULL DEFAULT 1,
  creado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_libro_categoria FOREIGN KEY (categoria_id)
    REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lectores (estudiantes / docentes)
CREATE TABLE lectores (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  dni       CHAR(8)      NOT NULL UNIQUE,
  nombres   VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  correo    VARCHAR(120),
  telefono  VARCHAR(15),
  tipo      ENUM('Estudiante','Docente','Administrativo') NOT NULL DEFAULT 'Estudiante',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Préstamos (relaciona libros y lectores)
CREATE TABLE prestamos (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  libro_id          INT NOT NULL,
  lector_id         INT NOT NULL,
  usuario_id        INT,
  fecha_prestamo    DATE NOT NULL,
  fecha_limite      DATE NOT NULL,
  fecha_devolucion  DATE NULL,
  estado            ENUM('Prestado','Devuelto') NOT NULL DEFAULT 'Prestado',
  observacion       VARCHAR(255),
  CONSTRAINT fk_prestamo_libro   FOREIGN KEY (libro_id)   REFERENCES libros(id),
  CONSTRAINT fk_prestamo_lector  FOREIGN KEY (lector_id)  REFERENCES lectores(id),
  CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Datos de ejemplo
-- ---------------------------------------------------------------------

-- Usuarios: admin / admin123   y   biblio / biblio2026
INSERT INTO usuarios (usuario, nombre, password_hash, rol) VALUES
('admin',  'Administrador del sistema', '$2y$10$WBdeg5KwqiUBMjKDSTwiWuEtpJXuFOfvI1eJE725C8ee.V.OLuVee', 'admin'),
('biblio', 'Bibliotecario de turno',    '$2y$10$61yKIZFPYqHXNgD65Dd9R.EOcgXnR78O/jqFEXi/N9WRKYRv9bXHi', 'bibliotecario');

INSERT INTO categorias (nombre, descripcion) VALUES
('Computación en la Nube', 'AWS, Azure, virtualización y servicios cloud'),
('Programación',           'Lenguajes y desarrollo de software'),
('Base de Datos',          'Modelado, SQL y administración'),
('Redes',                  'Redes de computadoras y seguridad'),
('Matemática',             'Cálculo, álgebra y estadística');

INSERT INTO libros (isbn, titulo, autor, anio, categoria_id, stock) VALUES
('9781492046479', 'Cloud Native Patterns',                     'Cornelia Davis',          2019, 1, 3),
('9781119138556', 'AWS Certified Solutions Architect Study Guide','Ben Piper, David Clinton', 2020, 1, 2),
('9780134685991', 'Effective Java',                            'Joshua Bloch',            2018, 2, 4),
('9780132350884', 'Clean Code',                                'Robert C. Martin',        2008, 2, 5),
('9781449373320', 'Designing Data-Intensive Applications',     'Martin Kleppmann',        2017, 3, 2),
('9780133970777', 'Fundamentos de Bases de Datos',             'Silberschatz, Korth',     2014, 3, 3),
('9780132126953', 'Redes de Computadoras',                     'Andrew S. Tanenbaum',     2012, 4, 3),
('9786071508485', 'Cálculo de una variable',                   'James Stewart',           2012, 5, 6);

INSERT INTO lectores (dni, nombres, apellidos, correo, telefono, tipo) VALUES
('71234567', 'Ana Lucía',  'Ramírez Torres',  'ana.ramirez@unas.edu.pe',  '987654321', 'Estudiante'),
('72345678', 'Carlos',     'Huamán Pérez',    'carlos.huaman@unas.edu.pe','976543210', 'Estudiante'),
('73456789', 'María José', 'Flores Ríos',     'maria.flores@unas.edu.pe', '965432109', 'Estudiante'),
('40123456', 'Jorge',      'Salazar Mendoza', 'jorge.salazar@unas.edu.pe','954321098', 'Docente'),
('41234567', 'Rosa',       'Vargas Céspedes', 'rosa.vargas@unas.edu.pe',  '943210987', 'Administrativo');

INSERT INTO prestamos (libro_id, lector_id, usuario_id, fecha_prestamo, fecha_limite, fecha_devolucion, estado, observacion) VALUES
(1, 1, 1, '2026-11-10', '2026-11-17', '2026-11-16', 'Devuelto', 'Devuelto en buen estado'),
(4, 2, 1, '2026-11-18', '2026-11-25', NULL,         'Prestado', NULL),
(5, 4, 2, '2026-11-20', '2026-11-27', NULL,         'Prestado', 'Para preparar clase'),
(7, 3, 2, '2026-11-05', '2026-11-12', NULL,         'Prestado', 'Préstamo vencido de ejemplo');
