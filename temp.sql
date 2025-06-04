USE sistema_escolar;

CREATE TABLE IF NOT EXISTS asignaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar algunas asignaturas básicas
INSERT INTO asignaturas (nombre, descripcion) VALUES
('Matemáticas', 'Estudio de números, cantidades y formas'),
('Lengua y Literatura', 'Estudio del idioma y sus manifestaciones artísticas'),
('Ciencias Naturales', 'Estudio de los fenómenos naturales'),
('Estudios Sociales', 'Estudio de la sociedad y su historia'),
('Inglés', 'Estudio del idioma inglés'),
('Educación Física', 'Desarrollo físico y deportivo'),
('Computación', 'Estudio de la informática y tecnología'),
('Arte', 'Expresión artística y creativa'),
('Música', 'Estudio de la música y sus elementos'),
('Religión', 'Estudio de valores y principios religiosos'); 