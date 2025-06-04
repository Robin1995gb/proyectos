-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS sistema_escolar;
USE sistema_escolar;

-- Tabla de roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cedula VARCHAR(10) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Tabla de niveles educativos
CREATE TABLE niveles_educativos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT
);

-- Tabla de cursos
CREATE TABLE cursos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    nivel_id INT NOT NULL,
    paralelo CHAR(1) NOT NULL,
    capacidad_maxima INT DEFAULT 25,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nivel_id) REFERENCES niveles_educativos(id),
    UNIQUE KEY unique_curso_paralelo (nombre, paralelo)
);

-- Tabla de asignaturas
CREATE TABLE asignaturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

-- Tabla de asignaciones de docentes
CREATE TABLE IF NOT EXISTS asignaciones_docentes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    docente_id INT NOT NULL,
    curso_id INT NOT NULL,
    asignatura_id INT NOT NULL,
    periodo_lectivo VARCHAR(9) NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asignacion (docente_id, curso_id, asignatura_id, periodo_lectivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de estudiantes
CREATE TABLE estudiantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cedula VARCHAR(10) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    sexo ENUM('M', 'F') NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    direccion TEXT NOT NULL,
    nombre_representante VARCHAR(200) NOT NULL,
    celular_representante VARCHAR(15) NOT NULL,
    correo_representante VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de matrículas
CREATE TABLE matriculas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    periodo_lectivo VARCHAR(9) NOT NULL,
    fecha_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
);

-- Tabla de calificaciones
CREATE TABLE IF NOT EXISTS calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    docente_id INT NOT NULL,
    periodo_lectivo VARCHAR(9) NOT NULL,
    tareas DECIMAL(4,2) NOT NULL,
    conducta DECIMAL(4,2) NOT NULL,
    evaluaciones DECIMAL(4,2) NOT NULL,
    examen DECIMAL(4,2) NOT NULL,
    promedio DECIMAL(4,2) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_calificacion (estudiante_id, curso_id, periodo_lectivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de actividades
CREATE TABLE actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    detalles TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Insertar roles básicos
INSERT INTO roles (nombre, descripcion) VALUES
('Administrador', 'Control total del sistema'),
('Docente', 'Gestión de calificaciones y materias'),
('Secretaria', 'Gestión de matrículas y estudiantes'),
('Rector', 'Supervisión general'),
('Vicerrector', 'Apoyo en la gestión'),
('Inspector', 'Control disciplinario');

-- Insertar niveles educativos
INSERT INTO niveles_educativos (nombre, descripcion) VALUES
('Jardín', 'Educación inicial'),
('Escuela', 'Educación básica elemental'),
('Colegio', 'Educación básica superior y bachillerato');

-- Insertar cursos básicos
INSERT INTO cursos (nombre, nivel_id, paralelo) VALUES
('Jardín', 1, 'A'),
('Jardín', 1, 'B'),
('Primero', 2, 'A'),
('Primero', 2, 'B'),
('Primero', 2, 'C'),
('Primero', 2, 'D'),
('Segundo', 2, 'A'),
('Segundo', 2, 'B'),
('Segundo', 2, 'C'),
('Segundo', 2, 'D'),
('Tercero', 2, 'A'),
('Tercero', 2, 'B'),
('Tercero', 2, 'C'),
('Tercero', 2, 'D'),
('Cuarto', 2, 'A'),
('Cuarto', 2, 'B'),
('Cuarto', 2, 'C'),
('Cuarto', 2, 'D'),
('Quinto', 2, 'A'),
('Quinto', 2, 'B'),
('Quinto', 2, 'C'),
('Quinto', 2, 'D'),
('Sexto', 2, 'A'),
('Sexto', 2, 'B'),
('Sexto', 2, 'C'),
('Sexto', 2, 'D'),
('Séptimo', 2, 'A'),
('Séptimo', 2, 'B'),
('Séptimo', 2, 'C'),
('Séptimo', 2, 'D'),
('Octavo', 3, 'A'),
('Octavo', 3, 'B'),
('Octavo', 3, 'C'),
('Octavo', 3, 'D'),
('Noveno', 3, 'A'),
('Noveno', 3, 'B'),
('Noveno', 3, 'C'),
('Noveno', 3, 'D'),
('Décimo', 3, 'A'),
('Décimo', 3, 'B'),
('Décimo', 3, 'C'),
('Décimo', 3, 'D');

-- Insertar cursos de bachillerato
INSERT INTO cursos (nombre, nivel_id, paralelo) VALUES
('Primero FIMA', 3, 'A'),
('Primero FIMA', 3, 'B'),
('Primero QUIBIO', 3, 'A'),
('Primero QUIBIO', 3, 'B'),
('Primero SISTEMAS', 3, 'A'),
('Primero SISTEMAS', 3, 'B'),
('Primero CONTABILIDAD', 3, 'A'),
('Primero CONTABILIDAD', 3, 'B'),
('Primero CIENCIAS', 3, 'A'),
('Primero CIENCIAS', 3, 'B'),
('Segundo FIMA', 3, 'A'),
('Segundo FIMA', 3, 'B'),
('Segundo QUIBIO', 3, 'A'),
('Segundo QUIBIO', 3, 'B'),
('Segundo SISTEMAS', 3, 'A'),
('Segundo SISTEMAS', 3, 'B'),
('Segundo CONTABILIDAD', 3, 'A'),
('Segundo CONTABILIDAD', 3, 'B'),
('Segundo CIENCIAS', 3, 'A'),
('Segundo CIENCIAS', 3, 'B'),
('Tercero FIMA', 3, 'A'),
('Tercero FIMA', 3, 'B'),
('Tercero QUIBIO', 3, 'A'),
('Tercero QUIBIO', 3, 'B'),
('Tercero SISTEMAS', 3, 'A'),
('Tercero SISTEMAS', 3, 'B'),
('Tercero CONTABILIDAD', 3, 'A'),
('Tercero CONTABILIDAD', 3, 'B'),
('Tercero CIENCIAS', 3, 'A'),
('Tercero CIENCIAS', 3, 'B');

-- Insertar asignaturas básicas
INSERT INTO asignaturas (nombre, descripcion) VALUES
('Matemáticas', 'Matemáticas generales'),
('Lenguaje', 'Lengua y Literatura'),
('Ciencias Naturales', 'Ciencias Naturales'),
('Estudios Sociales', 'Estudios Sociales'),
('Inglés', 'Idioma Inglés'),
('Computación', 'Informática'),
('Educación Física', 'Deportes y actividad física'),
('Artes', 'Educación artística'),
('Música', 'Educación musical');

-- Crear usuario administrador por defecto
INSERT INTO usuarios (cedula, nombres, apellidos, correo, usuario, password, rol_id) VALUES
('admin', 'Administrador', 'Sistema', 'admin@sistema.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1); 